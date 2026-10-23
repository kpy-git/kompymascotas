<?php
/**
 * Barre las reglas de carrito que la «2ª unidad» y el «regalo con la compra» dejan en cada cesta.
 *
 * La 2ª unidad no puede ser una regla para todos: su descuento depende de lo que lleve cada cesta
 * (media unidad por par), así que cada cesta que la activa tiene la suya (`KPY2U-…`); el regalo,
 * igual (`KPYRG-…`). Una cesta abandonada la dejaba para siempre (Rafa, 30/09/2026: «se quedan
 * creados y no se borran ya»).
 *
 * Aquí se borran las que no están en ningún pedido y ya no hacen falta:
 *
 *   - su cesta ya no existe, o ya es un pedido que no la usó;
 *   - ha pasado su fecha de fin, o su promoción ya no está en marcha;
 *   - su cesta lleva más de 2 días sin tocarse (abandonada). Si el cliente vuelve, la regla se crea
 *     otra vez al ver la cesta o al cambiarla, con el importe de ese momento.
 *
 * Las que están en un pedido no se tocan nunca: son parte de la factura.
 *
 * Sin cron: cada 6 horas, la primera visita de la tienda lo hace **después de contestar** (el
 * cliente no espera), y solo una a la vez. `bin/sincronizar.php` lo hace también a mano.
 */
class KpcLimpieza
{
    /** Cuándo toca el próximo barrido (marca de tiempo). */
    public const CUANDO = 'KPC_LIMPIEZA';

    /** Cada cuánto se barre, en segundos. */
    public const CADA = 21600;

    /** Una cesta sin tocar desde hace estos días está abandonada. */
    public const DIAS_ABANDONADA = 2;

    /** Qué quiere decir cada motivo, para `bin/sincronizar.php`. */
    public const MOTIVOS = [
        'sin_cesta' => 'su cesta ya no existe',
        'pedida' => 'su cesta ya es un pedido y no la usó',
        'caducada' => 'ha pasado su fecha de fin',
        'terminada' => 'su promoción ya no está en marcha',
        'abandonada' => 'cesta sin tocar desde hace más de ' . self::DIAS_ABANDONADA . ' días',
    ];

    /** Si toca, barre después de contestar a la visita. En las visitas normales no cuesta nada. */
    public static function siToca(): void
    {
        if ((int) Configuration::getGlobalValue(self::CUANDO) > time()) {
            return;
        }
        static $apuntado = false;
        if ($apuntado) {
            return;
        }
        $apuntado = true;
        register_shutdown_function(static function (): void {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            self::conCerrojo();
        });
    }

    /** Barre con cerrojo en la base de datos, para que dos visitas a la vez no lo hagan dos veces. */
    public static function conCerrojo(): void
    {
        $db = Db::getInstance();
        $nombre = pSQL(_DB_NAME_ . '_kpycoupons_limpieza');
        if (!(int) $db->getValue("SELECT GET_LOCK('{$nombre}', 0)")) {
            return;
        }
        try {
            // Otra visita puede haberlo hecho mientras: se mira en la tabla, no en la caché.
            $cuando = (int) $db->getValue(
                'SELECT value FROM ' . _DB_PREFIX_ . "configuration WHERE name = '" . pSQL(self::CUANDO) . "' AND id_shop IS NULL AND id_shop_group IS NULL"
            );
            if ($cuando <= time()) {
                // La fecha, primero: si el barrido falla, no se repite en cada visita.
                Configuration::updateGlobalValue(self::CUANDO, time() + self::CADA);
                self::barrer();
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, limpieza de reglas: ' . $e->getMessage(), 2, null, 'KpcLimpieza', 0, true);
        } finally {
            $db->getValue("SELECT RELEASE_LOCK('{$nombre}')");
        }
    }

    /**
     * Borra las reglas de 2ª unidad que sobran.
     *
     * @param bool $ensayo true = solo cuenta, no borra nada
     *
     * @return array<string, int> motivo (ver `MOTIVOS`) => cuántas
     */
    public static function barrer(bool $ensayo = false): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        // Las filas de regalo de cestas que ya no existen o ya son un pedido, fuera (no cuentan aquí);
        // con ellas, los precios de cesta y las reservas del cupo de cestas muertas.
        KpcRegalo::limpiarCestas();

        $candidatas = $db->executeS(
            "SELECT cr.id_cart_rule, cr.code, cr.date_to FROM {$p}cart_rule cr
             WHERE (" . implode(' OR ', array_map(static fn (string $prefijo): string => "cr.code LIKE '" . pSQL($prefijo) . "-%'", KpcVale::PREFIJOS)) . ")
               AND NOT EXISTS (SELECT 1 FROM {$p}order_cart_rule ocr WHERE ocr.id_cart_rule = cr.id_cart_rule)"
        ) ?: [];
        if ($candidatas === []) {
            return [];
        }

        // El código dice de qué promoción y de qué cesta es: KPY2U-{promoción}-{cesta}-{firma} (y lo
        // mismo el cupón con cupo, KPYCP-{promoción}-{cesta}-{producto}-{firma}); la de un regalo, de
        // qué cesta y de qué producto: KPYRG-{cesta}-{producto}-{firma} (y lo mismo el Special Price
        // de una línea mezclada, KPYSP-…).
        $idsCesta = [];
        foreach ($candidatas as $i => $f) {
            $partes = explode('-', (string) $f['code']);
            $esRegalo = in_array($partes[0] ?? '', [KpcRegalo::PREFIJO, KpcRegalo::PREFIJO_ESPECIAL], true);
            $candidatas[$i]['promo'] = $esRegalo ? 0 : (int) ($partes[1] ?? 0);
            $candidatas[$i]['cesta'] = (int) ($partes[$esRegalo ? 1 : 2] ?? 0);
            $idsCesta[$candidatas[$i]['cesta']] = true;
        }
        $cestas = [];
        foreach (array_chunk(array_keys($idsCesta), 500) as $trozo) {
            foreach ($db->executeS(
                "SELECT c.id_cart, c.date_upd, EXISTS(SELECT 1 FROM {$p}orders o WHERE o.id_cart = c.id_cart) AS pedida
                 FROM {$p}cart c WHERE c.id_cart IN (" . implode(',', array_map('intval', $trozo)) . ')'
            ) ?: [] as $c) {
                $cestas[(int) $c['id_cart']] = $c;
            }
        }
        // Las promociones en marcha (una agotada ya no lo está): las reglas de las demás, fuera.
        $enMarcha = [];
        foreach (KpcCupon::enVigor((int) Configuration::get('PS_SHOP_DEFAULT')) as $c) {
            if (in_array($c['tipo'] ?? '', ['segunda_unidad', 'cupon'], true)) {
                $enMarcha[(int) $c['id_kpy_coupon']] = true;
            }
        }

        $abandonada = time() - self::DIAS_ABANDONADA * 86400;
        $cuenta = [];
        foreach ($candidatas as $f) {
            $cesta = $cestas[$f['cesta']] ?? null;
            if ($cesta === null) {
                $motivo = 'sin_cesta';
            } elseif ((int) $cesta['pedida']) {
                $motivo = 'pedida';
            } elseif ((int) strtotime((string) $f['date_to']) < time()) {
                $motivo = 'caducada';
            } elseif ($f['promo'] > 0 && !isset($enMarcha[$f['promo']])) {
                $motivo = 'terminada';
            } elseif ((int) strtotime((string) $cesta['date_upd']) < $abandonada) {
                $motivo = 'abandonada';
            } else {
                continue;
            }
            if (!$ensayo) {
                $regla = new CartRule((int) $f['id_cart_rule']);
                if (!Validate::isLoadedObject($regla) || !$regla->delete()) {
                    continue;
                }
            }
            $cuenta[$motivo] = ($cuenta[$motivo] ?? 0) + 1;
        }

        return $cuenta;
    }
}
