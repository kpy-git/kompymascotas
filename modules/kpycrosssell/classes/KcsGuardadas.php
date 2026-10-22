<?php
/**
 * Las sugerencias de cada producto, **calculadas una vez y guardadas**.
 *
 * Se guardan donde PrestaShop guarda los productos relacionados, la tabla `accessory`: es la que se
 * ve y se edita en el back office, en la ficha de cada producto («Productos relacionados»), así que
 * cualquiera puede quitar o poner uno a mano sin pasar por este módulo.
 *
 * `accessory` solo dice **qué** productos, no en qué orden. El orden —el primer hueco es el que más
 * se ve— va en `kpy_cross_sell_auto`, junto a lo que dejó puesto el cálculo la última vez. Con eso
 * se sabe si alguien ha tocado un producto a mano: si lo que hay en `accessory` ya no es lo que dejó
 * el cálculo, **ese producto se respeta** y no se vuelve a calcular. Para devolvérselo al cálculo
 * basta con vaciar sus relacionados en el back office, o con `--forzar`.
 *
 * La ficha solo lee (`leer()`): una consulta, sin calcular nada. El cálculo (`generar()`) corre al dar
 * de alta un producto desde el back office y en `bin/generar.php`, que se puede pasar cada noche.
 */
class KcsGuardadas
{
    public const HECHO = 'hecho';
    public const RESPETADO = 'respetado';
    public const SIN_NADA = 'sin_nada';

    /** Cuántas veces las que se enseñan se guardan: con 2, se guardan seis para enseñar tres. */
    public const RESERVA = 2;

    /**
     * Las sugerencias guardadas de un producto, en su orden. Es lo que usa la ficha.
     *
     * @return array<int, array{id_product: int, origen: string}>
     */
    public static function leer(int $idProducto, int $cuantas = 3): array
    {
        if ($idProducto <= 0) {
            return [];
        }
        $p = _DB_PREFIX_;
        $idTienda = (int) Context::getContext()->shop->id;
        // Solo las que se pueden comprar ahora mismo: activas y con algún formato con stock que no
        // esté apagado. Las demás se ocultan y entra la siguiente de la reserva.
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT a.id_product_2 AS id_product, COALESCE(g.origen, 'mano') AS origen
             FROM {$p}accessory a
             INNER JOIN {$p}product s ON s.id_product = a.id_product_2 AND s.active = 1
             LEFT JOIN {$p}kpy_cross_sell_auto g ON g.id_product = a.id_product_1 AND g.id_sugerido = a.id_product_2
             WHERE a.id_product_1 = " . (int) $idProducto . "
               AND EXISTS (SELECT 1 FROM {$p}product_attribute pa
                           INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                                 AND sa.id_shop = {$idTienda} AND sa.quantity > 0
                           WHERE pa.id_product = a.id_product_2
                             AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute k
                                             WHERE k.id_product_attribute = pa.id_product_attribute AND k.active = 0))
             ORDER BY COALESCE(g.posicion, 99), a.id_product_2
             LIMIT " . max(1, $cuantas)
        ) ?: [];

        return array_map(static fn (array $f): array => [
            'id_product' => (int) $f['id_product'],
            'origen' => (string) $f['origen'],
        ], $filas);
    }

    /**
     * Calcula y guarda las sugerencias de un producto.
     *
     * @return string `hecho`, `respetado` (tocado a mano en el back office) o `sin_nada`
     */
    public static function generar(int $idProducto, array $ajustes, bool $forzar = false): string
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $idProducto = (int) $idProducto;

        $ahora = array_map('intval', array_column($db->executeS(
            "SELECT id_product_2 FROM {$p}accessory WHERE id_product_1 = {$idProducto}"
        ) ?: [], 'id_product_2'));
        $puestas = array_map('intval', array_column($db->executeS(
            "SELECT id_sugerido FROM {$p}kpy_cross_sell_auto WHERE id_product = {$idProducto}"
        ) ?: [], 'id_sugerido'));
        sort($ahora);
        sort($puestas);
        // Alguien lo ha cambiado en el back office: se respeta.
        if (!$forzar && $ahora !== [] && $ahora !== $puestas) {
            return self::RESPETADO;
        }

        $producto = $db->getRow(
            "SELECT id_product, id_manufacturer FROM {$p}product WHERE id_product = {$idProducto} AND active = 1"
        );
        $sugerencias = [];
        if ($producto) {
            // Se guardan el doble de las que se enseñan: las de más son la reserva. Si una se queda
            // sin stock, la ficha la oculta y entra la siguiente, y nunca queda un hueco vacío.
            $calculo = $ajustes;
            $calculo['limite'] = self::RESERVA * max(1, (int) ($ajustes['limite'] ?? 3));
            $sugerencias = KcsSugerencias::para([
                'id_product' => $idProducto,
                'id_manufacturer' => (int) $producto['id_manufacturer'],
                'precio' => (float) Product::getPriceStatic($idProducto, true, null),
            ], $calculo);
        }

        $db->delete('accessory', 'id_product_1 = ' . $idProducto);
        $db->delete('kpy_cross_sell_auto', 'id_product = ' . $idProducto);
        if ($sugerencias === []) {
            return self::SIN_NADA;
        }

        $posicion = 0;
        foreach ($sugerencias as $s) {
            ++$posicion;
            $db->insert('accessory', [
                'id_product_1' => $idProducto,
                'id_product_2' => (int) $s['id_product'],
            ], false, true, Db::INSERT_IGNORE);
            $db->insert('kpy_cross_sell_auto', [
                'id_product' => $idProducto,
                'id_sugerido' => (int) $s['id_product'],
                'posicion' => $posicion,
                'origen' => pSQL((string) $s['origen']),
                'fecha' => date('Y-m-d H:i:s'),
            ], false, true, Db::INSERT_IGNORE);
        }

        return self::HECHO;
    }
}
