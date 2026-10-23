<?php
/**
 * Los Special Price de las promociones de esta pantalla: el precio rebajado que se ve en la ficha,
 * tachado, con «Precio habitual» y la etiqueta «Special Price» (sola: ninguna otra).
 *
 * Se guardan igual que los carga Julián, para que `kpyspecialprices`, la ficha, las etiquetas y el
 * fichero de Google los lean sin saber de dónde vienen:
 *
 *   - `specific_price`: el precio de la promoción, con sus fechas (`from`/`to`). Como lleva fecha
 *     de fin, PrestaShop lo prefiere a los de PriceShape (que no la llevan) mientras dura;
 *   - `kpy_special_price`: la fila de `kpyspecialprices`, con el descuento que había antes
 *     (`old_discount`, en tanto por uno) y el de la promoción (`special_discount`, en %).
 *
 * Tres maneras de rebajar (`modo_precio`):
 *
 *   - `pct`: el mismo % para todos. **El % va encima del precio de PriceShape**, como en la carga
 *     de Julián (Royal Canin, agosto: PriceShape 14,5 % + promo 12 % → 24,75 % sobre el PVPR):
 *         descuento del precio = 1 − (1 − PriceShape) × (1 − promo)
 *     Así «13 %» es un 13 % sobre el precio de hoy, que es lo que se ve y lo que se anuncia;
 *   - `pct_articulo`: cada artículo de la lista con su %, igual que arriba;
 *   - `precio_articulo`: cada artículo con su **precio final con IVA**. Se guarda como el descuento
 *     sobre el PVPR que da justo ese precio.
 *   Los que no traen el suyo llevan el % general, si lo hay; si no, se quedan fuera. Un pack que ha
 *   entrado solo lleva el mismo descuento que su saco suelto.
 *
 * Lo que se crea se apunta en `kpy_coupon_precio`, para poder quitarlo sin tocar nada de nadie:
 * al apagar la promoción, al quitar un producto a mano o al cambiar las fechas o el %.
 */
class KpcEspecial
{
    /** Por qué un artículo se queda sin Special Price. */
    public const MOTIVOS = [
        'sin_valor' => 'sin su % o su precio en la lista (y sin % general)',
        'caro' => 'su precio de promo no baja del de hoy',
        'sin_precio' => 'sin precio en la tienda',
    ];

    /**
     * Deja los Special Price de una promoción justo en estos artículos. Solo toca lo que cambia:
     * lo que ya estaba igual (mismas fechas y mismo % o precio) se queda como está.
     *
     * @param array $cupon fila de `kpy_coupon`
     * @param array<int, int> $items clave => id de producto (`KpcAlcance::clave`)
     * @param array<int, array{v: float, de: ?int}> $valores el % o precio propio de cada artículo
     *
     * @return array{puestos: int, quitados: int, iguales: int, fuera: array<string, int[]>}
     *         fuera: motivo => claves que se han quedado sin Special Price
     */
    public static function sincronizar(array $cupon, array $items, array $valores, int $idTienda): array
    {
        $id = (int) $cupon['id_kpy_coupon'];
        $desde = (string) $cupon['fecha_desde'];
        $hasta = (string) $cupon['fecha_hasta'];
        $cuenta = ['puestos' => 0, 'quitados' => 0, 'iguales' => 0, 'fuera' => []];

        $quiero = [];
        foreach ($items as $clave => $idProducto) {
            $meta = self::meta($cupon, $valores[$clave] ?? null);
            if ($meta === null) {
                $cuenta['fuera']['sin_valor'][] = (int) $clave;
                continue;
            }
            $ipa = max(0, (int) $clave);
            $quiero[(int) $idProducto . '-' . $ipa] = ['id_product' => (int) $idProducto, 'ipa' => $ipa, 'clave' => (int) $clave, 'meta' => $meta];
        }

        foreach (self::puestos($id) as $k => $h) {
            if (isset($quiero[$k]) && $h['date_from'] === $desde && $h['expire'] === $hasta && $h['firma'] === self::firma($quiero[$k]['meta'])) {
                unset($quiero[$k]);
                ++$cuenta['iguales'];
                continue;
            }
            self::quitarUno($h, $idTienda);
            ++$cuenta['quitados'];
        }
        foreach ($quiero as $q) {
            $objetivo = self::objetivo($q['meta'], $q['id_product'], $q['ipa'], $idTienda);
            if (!$objetivo['ok']) {
                $cuenta['fuera'][$objetivo['motivo']][] = $q['clave'];
                continue;
            }
            self::ponerUno($id, $q['id_product'], $q['ipa'], $desde, $hasta, $objetivo, self::firma($q['meta']), $idTienda);
            ++$cuenta['puestos'];
        }
        if ($cuenta['puestos'] || $cuenta['quitados']) {
            self::limpiarCache();
        }

        return $cuenta;
    }

    /**
     * Lo que se quiere para un artículo, sin mirar precios todavía.
     *
     * @param array{v: float, de: ?int}|null $valor el suyo, de la lista
     *
     * @return array{pct?: float, precio?: float, precio_de?: float, de?: int}|null null = sin nada que ponerle
     */
    public static function meta(array $cupon, ?array $valor): ?array
    {
        $modo = (string) ($cupon['modo_precio'] ?? 'pct');
        if ($valor !== null && $modo === 'pct_articulo') {
            return ['pct' => (float) $valor['v']];
        }
        if ($valor !== null && $modo === 'precio_articulo') {
            return $valor['de'] === null
                ? ['precio' => (float) $valor['v']]
                : ['precio_de' => (float) $valor['v'], 'de' => (int) $valor['de']];
        }
        $general = (float) ($cupon['porcentaje'] ?? 0);

        return $general > 0 ? ['pct' => $general] : null;
    }

    /**
     * El Special Price de un artículo, con sus precios: el de hoy (sin promociones), el final y el
     * descuento que se guarda. Es lo que se pone y lo que enseña la vista previa.
     *
     * @return array{ok: bool, motivo: string, pct: float, precio: ?float, reduccion: float, old: float,
     *               pvpr: float, hoy: float, final: float}
     */
    public static function objetivo(array $meta, int $idProducto, int $ipa, int $idTienda): array
    {
        $old = self::descuentoDeHoy($idProducto, $ipa, $idTienda);
        $pvpr = self::pvpr($idProducto, $ipa);
        $hoy = round($pvpr * (1 - $old), 2);
        $salida = ['ok' => false, 'motivo' => '', 'pct' => 0.0, 'precio' => null, 'reduccion' => 0.0, 'old' => $old,
            'pvpr' => $pvpr, 'hoy' => $hoy, 'final' => $hoy];
        if ($pvpr <= 0) {
            return ['motivo' => 'sin_precio'] + $salida;
        }

        $pct = $meta['pct'] ?? null;
        if (isset($meta['precio_de'])) {
            // Pack que hereda el precio de su saco suelto: el mismo descuento sobre su precio de hoy.
            $ipaSuelto = max(0, (int) $meta['de']);
            $hoySuelto = round(self::pvpr($idProducto, $ipaSuelto) * (1 - self::descuentoDeHoy($idProducto, $ipaSuelto, $idTienda)), 2);
            if ($hoySuelto <= 0 || $meta['precio_de'] >= $hoySuelto) {
                return ['motivo' => 'caro'] + $salida;
            }
            $pct = (1 - $meta['precio_de'] / $hoySuelto) * 100;
        }

        if (isset($meta['precio'])) {
            $precio = round((float) $meta['precio'], 2);
            if ($precio >= $hoy - 0.004) {
                return ['motivo' => 'caro'] + $salida;
            }

            return ['ok' => true, 'pct' => round((1 - $precio / $hoy) * 100, 3), 'precio' => $precio,
                'reduccion' => round(1 - $precio / $pvpr, 6), 'final' => $precio] + $salida;
        }

        $pct = (float) $pct;
        if ($pct <= 0 || $pct >= 100) {
            return ['motivo' => 'sin_valor'] + $salida;
        }
        $reduccion = self::reduccion($old, $pct);

        return ['ok' => true, 'pct' => round($pct, 3), 'reduccion' => $reduccion,
            'final' => round($pvpr * (1 - $reduccion), 2)] + $salida;
    }

    /** Quita todos los Special Price que puso esta promoción (al apagarla o borrarla). */
    public static function quitarTodo(int $idCupon, int $idTienda): int
    {
        $n = 0;
        foreach (self::puestos($idCupon) as $h) {
            self::quitarUno($h, $idTienda);
            ++$n;
        }
        if ($n) {
            self::limpiarCache();
        }

        return $n;
    }

    /**
     * El descuento de hoy de un formato **sin promociones**: el de PriceShape. Es el precio que
     * PrestaShop usaría si no hubiera ninguna promoción con fecha de fin: de los precios
     * específicos sin fecha de fin, primero el del formato y luego el del producto, el más nuevo.
     */
    public static function descuentoDeHoy(int $idProducto, int $ipa, int $idTienda): float
    {
        return (float) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT reduction FROM ' . _DB_PREFIX_ . 'specific_price
             WHERE id_product = ' . (int) $idProducto . ' AND id_product_attribute IN (0, ' . (int) $ipa . ')
               AND id_shop IN (0, ' . (int) $idTienda . ') AND id_cart = 0 AND id_customer = 0 AND id_group = 0
               AND id_country = 0 AND id_currency = 0 AND id_specific_price_rule = 0 AND from_quantity = 1
               AND price < 0 AND reduction_type = "percentage"
               AND `to` = "0000-00-00 00:00:00" AND `from` <= NOW()
             ORDER BY id_product_attribute DESC, `from` DESC, id_specific_price DESC'
        );
    }

    /** El descuento que lleva el precio de la promoción: el % encima del de PriceShape. */
    public static function reduccion(float $deHoy, float $pct): float
    {
        return round(1 - (1 - $deHoy) * (1 - $pct / 100), 6);
    }

    /**
     * Lo que puso cada promoción, por artículo.
     *
     * @return array<string, array> «producto-combinación» => fila de `kpy_coupon_precio`
     */
    public static function puestos(int $idCupon): array
    {
        $salida = [];
        foreach (Db::getInstance()->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon_precio WHERE id_kpy_coupon = ' . (int) $idCupon
        ) ?: [] as $f) {
            $salida[(int) $f['id_product'] . '-' . (int) $f['id_product_attribute']] = $f;
        }

        return $salida;
    }

    /** Para saber si lo que hay puesto es lo que se quiere, sin volver a calcular precios. */
    private static function firma(array $meta): string
    {
        if (isset($meta['precio'])) {
            return 'precio:' . number_format((float) $meta['precio'], 2, '.', '');
        }
        if (isset($meta['precio_de'])) {
            return 'de:' . (int) $meta['de'] . ':' . number_format((float) $meta['precio_de'], 2, '.', '');
        }

        return 'pct:' . rtrim(rtrim(number_format((float) ($meta['pct'] ?? 0), 3, '.', ''), '0'), '.');
    }

    /** PVPR con IVA, sin ninguna rebaja. */
    private static function pvpr(int $idProducto, int $ipa): float
    {
        return (float) Product::getPriceStatic($idProducto, true, $ipa ?: null, 6, null, false, false);
    }

    private static function ponerUno(int $idCupon, int $idProducto, int $ipa, string $desde, string $hasta, array $objetivo, string $firma, int $idTienda): void
    {
        $precio = new SpecificPrice();
        $precio->id_specific_price_rule = 0;
        $precio->id_cart = 0;
        $precio->id_product = $idProducto;
        $precio->id_product_attribute = $ipa;
        $precio->id_shop = $idTienda;
        $precio->id_shop_group = 0;
        $precio->id_currency = 0;
        $precio->id_country = 0;
        $precio->id_group = 0;
        $precio->id_customer = 0;
        $precio->price = -1;
        $precio->from_quantity = 1;
        $precio->reduction = $objetivo['reduccion'];
        $precio->reduction_tax = 1;
        $precio->reduction_type = 'percentage';
        $precio->from = $desde;
        $precio->to = $hasta;
        if (!$precio->add()) {
            throw new RuntimeException(sprintf('no se ha podido poner el Special Price de %d-%d', $idProducto, $ipa));
        }

        $db = Db::getInstance();
        $db->execute(
            'INSERT INTO ' . _DB_PREFIX_ . 'kpy_special_price
                (id_product, id_product_attribute, id_shop, old_discount, expire, special_discount, date_from)
             VALUES (' . $idProducto . ', ' . $ipa . ', ' . $idTienda . ', ' . (float) $objetivo['old'] . ", '" . pSQL($hasta) . "', "
                . (float) $objetivo['pct'] . ", '" . pSQL($desde) . "')
             ON DUPLICATE KEY UPDATE old_discount = VALUES(old_discount), expire = VALUES(expire),
                special_discount = VALUES(special_discount)"
        );
        $db->execute(
            'REPLACE INTO ' . _DB_PREFIX_ . 'kpy_coupon_precio
                (id_kpy_coupon, id_product, id_product_attribute, id_specific_price, date_from, expire, porcentaje, precio,
                 old_discount, reduccion, firma)
             VALUES (' . $idCupon . ', ' . $idProducto . ', ' . $ipa . ', ' . (int) $precio->id . ", '" . pSQL($desde) . "', '"
                . pSQL($hasta) . "', " . (float) $objetivo['pct'] . ', ' . ($objetivo['precio'] !== null ? (float) $objetivo['precio'] : 'NULL') . ', '
                . (float) $objetivo['old'] . ', ' . (float) $objetivo['reduccion'] . ", '" . pSQL($firma) . "')"
        );
    }

    /** Quita un Special Price puesto por una promoción: su precio y su fila, y nada más. */
    private static function quitarUno(array $h, int $idTienda): void
    {
        $precio = new SpecificPrice((int) $h['id_specific_price']);
        if (Validate::isLoadedObject($precio)) {
            $precio->delete();
        }
        $db = Db::getInstance();
        $db->delete(
            'kpy_special_price',
            'id_product = ' . (int) $h['id_product'] . ' AND id_product_attribute = ' . (int) $h['id_product_attribute']
            . ' AND id_shop = ' . (int) $idTienda . " AND date_from = '" . pSQL((string) $h['date_from']) . "'"
        );
        $db->delete(
            'kpy_coupon_precio',
            'id_kpy_coupon = ' . (int) $h['id_kpy_coupon'] . ' AND id_product = ' . (int) $h['id_product']
            . ' AND id_product_attribute = ' . (int) $h['id_product_attribute']
        );
    }

    private static function limpiarCache(): void
    {
        if (method_exists('SpecificPrice', 'flushCache')) {
            SpecificPrice::flushCache();
        }
        Cache::clean('SpecificPrice::*');
        Cache::clean('Product::getPriceStatic_*');
    }
}
