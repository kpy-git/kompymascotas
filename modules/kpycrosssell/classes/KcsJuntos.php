<?php
/**
 * «Llévalos juntos y un X % en los dos».
 *
 * Es el empujón del bloque: no es un cupón del catálogo (no depende de qué producto sea), sino un
 * premio por **combinar** el producto que se está viendo con algo que le pega. Por eso vive aquí y
 * no en el módulo de cupones.
 *
 * PrestaShop no sabe expresar «compra A y B», así que se hace igual que la «2ª unidad»: se calcula
 * el importe exacto y se mete en una regla de carrito **de esa cesta**, para que la suma, el pedido
 * y la factura los siga haciendo PrestaShop.
 *
 * Reglas:
 *   - hacen falta **dos productos distintos** y que uno sea sugerencia del otro;
 *   - cada línea entra en **una sola** pareja (si no, tres productos darían tres descuentos);
 *   - una línea que ya lleva un cupón o una «2ª unidad» se queda fuera: dos promociones sobre lo
 *     mismo es justo el fallo que ya se corrigió en la cesta.
 */
class KcsJuntos
{
    public const PREFIJO = 'KCSJ';

    /** Cuándo toca el próximo barrido de reglas (marca de tiempo). */
    public const LIMPIEZA = 'KCS_LIMPIEZA';

    /** Una cesta sin tocar desde hace estos días está abandonada. */
    public const DIAS_ABANDONADA = 2;

    /** @var bool evita la pescadilla: meter la regla vuelve a guardar la cesta */
    private static bool $dentro = false;

    /** El descuento que le toca a una marca ahora mismo, o `null` si no tiene. */
    public static function regla(int $idMarca): ?array
    {
        return KcsPromo::deLaMarca($idMarca);
    }

    /**
     * Revisa la cesta y deja la regla como toque. Devuelve lo que se descuenta ahora.
     */
    public static function sincronizar(Cart $cart, Kpycrosssell $modulo): float
    {
        if (self::$dentro || !$cart->id) {
            return 0.0;
        }
        self::$dentro = true;
        try {
            $cuenta = self::calcular($cart);
            $codigo = self::codigo($cart);
            $idRegla = (int) CartRule::getIdByCode($codigo);
            $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));

            if ($cuenta['importe'] <= 0) {
                if ($idRegla > 0) {
                    if (in_array($idRegla, $puestas, true)) {
                        $cart->removeCartRule($idRegla);
                    }
                    self::borrarSiSobra($idRegla, $cart);
                }

                return 0.0;
            }

            $regla = $idRegla > 0 ? new CartRule($idRegla) : new CartRule();
            $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
            $nombre = $modulo->t('Llevados juntos: %pct% % en los dos', ['%pct%' => self::texto($cuenta['pct'])]);
            // Hasta que acaba el descuento de la marca (antes, un mes aunque acabara antes); sin
            // fecha de fin, una semana que se alarga sola al tocar la cesta.
            $hasta = $cuenta['hasta'] ?? date('Y-m-d H:i:s', strtotime('+7 days'));
            // La cesta se repasa a menudo: si la regla ya está como tiene que estar, no se reescribe.
            $igual = $regla->id
                && abs((float) $regla->reduction_amount - $cuenta['importe']) < 0.005
                && (int) $regla->id_customer === (int) $cart->id_customer
                && (int) $regla->reduction_currency === (int) $cart->id_currency
                && (string) (((array) $regla->name)[$idioma] ?? '') === $nombre
                && ((string) $regla->date_to === $hasta || (($cuenta['hasta'] ?? null) === null && strtotime((string) $regla->date_to) > strtotime('+6 days')));
            if (!$igual) {
                $regla->name = [$idioma => $nombre];
                $regla->code = $codigo;
                $regla->id_customer = (int) $cart->id_customer;
                if (!$regla->id) {
                    $regla->date_from = date('Y-m-d H:i:s', strtotime('-1 hour'));
                }
                $regla->date_to = $hasta;
                $regla->quantity = 1;
                $regla->quantity_per_user = 1;
                $regla->reduction_amount = $cuenta['importe'];
                $regla->reduction_tax = 1;
                $regla->reduction_currency = (int) $cart->id_currency;
                $regla->reduction_percent = 0;
                $regla->reduction_product = 0;
                $regla->free_shipping = 0;
                $regla->partial_use = 0;
                $regla->highlight = 0;
                $regla->active = 1;
                $regla->id ? $regla->update() : $regla->add();
            }

            if (!in_array((int) $regla->id, $puestas, true)) {
                $cart->addCartRule((int) $regla->id);
            }

            return $cuenta['importe'];
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycrosssell: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);

            return 0.0;
        } finally {
            self::$dentro = false;
        }
    }

    /**
     * Cuánto se descuenta en esta cesta y por qué parejas.
     *
     * El porcentaje lo pone la regla de **la marca del producto que manda en la pareja** (el más
     * caro de los dos), que es el que el cliente ha ido a comprar.
     *
     * @return array{importe: float, pct: float, parejas: array<int, array{a: string, b: string, importe: float, pct: float}>, hasta: ?string}
     *         hasta: cuándo acaba el primero de los descuentos que se usan (null si ninguno tiene fin)
     */
    public static function calcular(Cart $cart): array
    {
        if (KcsPromo::enVigor() === []) {
            return ['importe' => 0.0, 'pct' => 0.0, 'parejas' => []];
        }
        $hasta = null;
        $lineas = [];
        foreach ($cart->getProducts() as $linea) {
            $id = (int) ($linea['id_product'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $lineas[$id] = [
                'id_product' => $id,
                'id_manufacturer' => (int) ($linea['id_manufacturer'] ?? 0),
                'nombre' => (string) ($linea['name'] ?? ''),
                'importe' => round((float) ($linea['price_wt'] ?? 0) * (int) ($linea['cart_quantity'] ?? 1), 2),
                'precio' => (float) ($linea['price_wt'] ?? 0),
            ];
        }
        if (count($lineas) < 2) {
            return ['importe' => 0.0, 'pct' => 0.0, 'parejas' => []];
        }

        foreach (self::conOtraPromo($cart) as $fuera) {
            unset($lineas[$fuera]);
        }
        if (count($lineas) < 2) {
            return ['importe' => 0.0, 'pct' => 0.0, 'parejas' => []];
        }

        // De lo más caro a lo más barato: así el principal es el que manda y busca su acompañante.
        uasort($lineas, static fn (array $a, array $b): int => $b['importe'] <=> $a['importe']);

        $libres = $lineas;
        $parejas = [];
        $importe = 0.0;
        $mayor = 0.0;
        foreach ($lineas as $id => $linea) {
            if (!isset($libres[$id])) {
                continue;
            }
            unset($libres[$id]);
            $regla = KcsPromo::deLaMarca((int) $linea['id_manufacturer']);
            if ($regla === null) {
                continue; // Esa marca no tiene descuento ahora mismo.
            }
            $sugeridas = array_column(KcsSugerencias::para([
                'id_product' => $id,
                'id_manufacturer' => $linea['id_manufacturer'],
                'precio' => $linea['precio'],
            ], ['limite' => 12]), 'id_product');

            foreach ($sugeridas as $idSugerido) {
                if (!isset($libres[$idSugerido])) {
                    continue;
                }
                $companero = $libres[$idSugerido];
                if (!KcsPromo::valeLaPareja($regla, (int) $linea['id_manufacturer'], (int) $companero['id_manufacturer'])) {
                    continue;
                }
                unset($libres[$idSugerido]);
                $suma = round(($linea['importe'] + $companero['importe']) * $regla['porcentaje'] / 100, 2);
                $importe += $suma;
                $mayor = max($mayor, $regla['porcentaje']);
                if (($regla['hasta'] ?? null) !== null && ($hasta === null || $regla['hasta'] < $hasta)) {
                    $hasta = $regla['hasta'];
                }
                $parejas[] = [
                    'a' => $linea['nombre'],
                    'b' => $companero['nombre'],
                    'importe' => $suma,
                    'pct' => $regla['porcentaje'],
                ];
                break;
            }
        }

        return ['importe' => round($importe, 2), 'pct' => $mayor, 'parejas' => $parejas, 'hasta' => $hasta];
    }

    /**
     * Los productos de la cesta que ya llevan una promoción del módulo de cupones. No se les pone
     * otra encima.
     *
     * @return int[] id_product
     */
    private static function conOtraPromo(Cart $cart): array
    {
        if (!class_exists('KpcCarrito') || !method_exists('KpcCarrito', 'productosConPromo')) {
            return [];
        }

        return KpcCarrito::productosConPromo($cart, (int) Context::getContext()->shop->id);
    }

    /** Un código por cesta, imposible de adivinar desde fuera. */
    public static function codigo(Cart $cart): string
    {
        $firma = substr(md5($cart->secure_key . '|juntos|' . _COOKIE_KEY_), 0, 8);

        return self::PREFIJO . '-' . (int) $cart->id . '-' . mb_strtoupper($firma);
    }

    /** Las reglas de una cesta sin pedido no hacen falta para nada: se borran. */
    private static function borrarSiSobra(int $idRegla, Cart $cart): void
    {
        if ($cart->orderExists()) {
            return;
        }
        $regla = new CartRule($idRegla);
        if (Validate::isLoadedObject($regla) && strpos((string) $regla->code, self::PREFIJO . '-') === 0) {
            $regla->delete();
        }
    }

    /**
     * Cada 6 horas, la primera visita barre las reglas que sobran **después de contestar** (el
     * cliente no espera), sin cron. Es lo mismo que hace `kpycoupons` con las de la 2ª unidad.
     */
    public static function limpiarSiToca(): void
    {
        if ((int) Configuration::getGlobalValue(self::LIMPIEZA) > time()) {
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
            $db = Db::getInstance();
            $nombre = pSQL(_DB_NAME_ . '_kpycrosssell_limpieza');
            if (!(int) $db->getValue("SELECT GET_LOCK('{$nombre}', 0)")) {
                return;
            }
            try {
                $cuando = (int) $db->getValue(
                    'SELECT value FROM ' . _DB_PREFIX_ . "configuration WHERE name = '" . pSQL(self::LIMPIEZA) . "' AND id_shop IS NULL AND id_shop_group IS NULL"
                );
                if ($cuando <= time()) {
                    Configuration::updateGlobalValue(self::LIMPIEZA, time() + 21600);
                    self::barrer();
                }
            } catch (Throwable $e) {
                PrestaShopLogger::addLog('kpycrosssell, limpieza de reglas: ' . $e->getMessage(), 2, null, 'KcsJuntos', 0, true);
            } finally {
                $db->getValue("SELECT RELEASE_LOCK('{$nombre}')");
            }
        });
    }

    /**
     * Borra las reglas de «llevados juntos» que sobran: cada cesta que activa el descuento deja la
     * suya, y una cesta abandonada la dejaba para siempre. Fuera las que no están en ningún pedido y
     * cuya cesta ya no existe, ya es un pedido que no la usó, lleva más de 2 días sin tocarse o ya
     * no tiene descuento que dar (caducada o sin ninguno encendido). Si el cliente vuelve, se crea
     * otra vez al ver la cesta o al cambiarla.
     *
     * @return array<string, int> motivo => cuántas
     */
    public static function barrer(bool $ensayo = false): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $candidatas = $db->executeS(
            "SELECT cr.id_cart_rule, cr.code, cr.date_to, c.id_cart, c.date_upd,
                    EXISTS(SELECT 1 FROM {$p}orders o WHERE o.id_cart = c.id_cart) AS pedida
             FROM {$p}cart_rule cr
             LEFT JOIN {$p}cart c ON c.id_cart = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(cr.code, '-', 2), '-', -1) AS UNSIGNED)
             WHERE cr.code LIKE '" . pSQL(self::PREFIJO) . "-%'
               AND NOT EXISTS (SELECT 1 FROM {$p}order_cart_rule ocr WHERE ocr.id_cart_rule = cr.id_cart_rule)"
        ) ?: [];
        $sinDescuentos = KcsPromo::enVigor() === [];
        $abandonada = time() - self::DIAS_ABANDONADA * 86400;
        $cuenta = [];
        foreach ($candidatas as $f) {
            if (empty($f['id_cart'])) {
                $motivo = 'sin_cesta';
            } elseif ((int) $f['pedida']) {
                $motivo = 'pedida';
            } elseif ((int) strtotime((string) $f['date_to']) < time() || $sinDescuentos) {
                $motivo = 'caducada';
            } elseif ((int) strtotime((string) $f['date_upd']) < $abandonada) {
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

    /** @return int cuántas se han borrado (para quien lo llamaba antes) */
    public static function limpiarViejas(): int
    {
        return array_sum(self::barrer());
    }

    public static function texto(float $pct): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($pct, 1, '.', ''), '0'), '.'));
    }
}
