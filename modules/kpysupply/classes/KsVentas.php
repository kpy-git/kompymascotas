<?php
/**
 * Las ventas de los artículos de un acuerdo en su periodo, sacadas de los pedidos de PrestaShop
 * al consultar (no se copian a ninguna tabla): por artículo, unidades y **valor sin IVA de
 * verdad**, que es lo que PrestaShop cobró por la línea menos los vales de la cesta que le tocan.
 * En una redención, además, el importe que toca cobrar al proveedor (ventas × %); en un precio de
 * coste actualizado, el coste de lo vendido a ese precio y el margen.
 *
 * A qué líneas toca cada vale (`elegibles`):
 *   - uno de «este producto» (los vales de kpycoupons): a las líneas de ese producto; el del
 *     regalo (`KPYRG`) a la regalada; el del Special Price recuperado (`KPYSP`) a la que llevaba el
 *     regalo o el cupo (lo apunta kpycoupons en el pedido); el del cupón con cupo (`KPYCP`) a la del
 *     cupo; los demás, a las pagadas;
 *   - uno de «los productos seleccionados» (los cupones de las promociones): a las líneas que
 *     cumplen sus restricciones (combinaciones, productos, marcas, categorías, atributos,
 *     proveedores), como hace PrestaShop;
 *   - uno del más barato: a la más barata de las que cumplen;
 *   - uno de todo el pedido: a todas, en proporción a lo que valen.
 * Si una regla «sin Special Price» no puede tocar las líneas rebajadas, no las toca. Lo que no se
 * puede saber (una regla borrada) va a todas en proporción. Los vales de solo portes no cuentan.
 *
 * Cuentan los pedidos **pagados** (`orders.valid`); con `$conPendientes`, también los pendientes de
 * pago. Los estados de `estadosFuera()` (cancelado, error de pago, reembolsado y los que se marquen
 * en la pantalla) nunca. Las unidades devueltas o reembolsadas se descuentan, con su parte de vales.
 *
 * El reparto (`elegibles` y `repartir`) es puro y se prueba con `tests/ventas.php`.
 */
class KsVentas
{
    /** Los estados de pedido que no cuentan como venta (ids separados por comas). */
    public const CONF_ESTADOS = 'KPS_ESTADOS_FUERA';

    /** Las ventas de un lote (sus artículos, su periodo; sin fin = hasta hoy). */
    public static function deLote(array $lote, bool $conPendientes = false): array
    {
        $articulos = [];
        foreach (KsAcuerdos::filasDeLote($lote) as $f) {
            $articulos[(int) $f['id_product'] . '-' . (int) $f['id_product_attribute']] = [
                'id_product' => (int) $f['id_product'],
                'ipa' => (int) $f['id_product_attribute'],
                'valor' => (float) $f['value'],
                'producto' => (string) ($f['producto'] ?? ''),
                'formato' => (string) ($f['formato'] ?? ''),
                'ean' => (string) ($f['ean'] ?? ''),
                'ref' => (string) ($f['ref'] ?? ''),
                'marca' => (string) ($f['marca'] ?? ''),
            ];
        }
        $hasta = $lote['hasta'] ?? null;
        $ahora = date('Y-m-d H:i:s');

        return self::ventas($articulos, (string) $lote['desde'], $hasta !== null && $hasta < $ahora ? (string) $hasta : $ahora, (string) $lote['tipo'], $conPendientes);
    }

    /**
     * @param array<string, array{id_product: int, ipa: int, valor: float}> $articulos clave «producto-ipa» => artículo (ipa 0 = todas sus combinaciones)
     *
     * @return array{periodo: array{desde: string, hasta: string}, n_pedidos: int, pendientes_fuera: int,
     *               articulos: array<string, array>, detalle: array<int, array>, totales: array}
     */
    public static function ventas(array $articulos, string $desde, string $hasta, string $tipo, bool $conPendientes): array
    {
        $esRedencion = $tipo === KsTipos::REDENCION;
        $esCoste = $tipo === KsTipos::COSTE;
        $vacio = ['periodo' => ['desde' => $desde, 'hasta' => $hasta], 'n_pedidos' => 0, 'pendientes_fuera' => 0, 'articulos' => [], 'detalle' => [],
            'totales' => ['uds' => 0, 'regalo' => 0, 'bruto' => 0.0, 'vales' => 0.0, 'neto' => 0.0, 'importe' => $esRedencion ? 0.0 : null, 'coste' => $esCoste ? 0.0 : null, 'margen' => $esCoste ? 0.0 : null]];
        foreach ($articulos as $clave => $a) {
            $vacio['articulos'][$clave] = $a + ['uds' => 0, 'regalo' => 0, 'bruto' => 0.0, 'vales' => 0.0, 'neto' => 0.0, 'importe' => null, 'coste' => null, 'margen' => null, 'pedidos' => 0];
        }
        if ($articulos === [] || $desde > $hasta) {
            return $vacio;
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $periodo = "o.date_add BETWEEN '" . pSQL($desde) . "' AND '" . pSQL($hasta) . "'";
        $conArticulo = 'EXISTS (SELECT 1 FROM ' . $p . 'order_detail od WHERE od.id_order = o.id_order AND (' . self::sqlArticulos($articulos, 'od') . '))';
        $fuera = implode(',', self::estadosFuera()) ?: '0';
        $estados = 'o.current_state NOT IN (' . $fuera . ')' . ($conPendientes ? '' : ' AND o.valid = 1');

        // 1. Los pedidos del periodo con alguno de los artículos.
        $pedidos = [];
        foreach ($db->executeS(
            "SELECT o.id_order, o.reference, o.date_add, o.current_state, o.valid, o.total_shipping_tax_excl
             FROM {$p}orders o WHERE {$periodo} AND {$estados} AND {$conArticulo} ORDER BY o.id_order"
        ) ?: [] as $f) {
            $pedidos[(int) $f['id_order']] = $f;
        }
        $salida = $vacio;
        $salida['pendientes_fuera'] = $conPendientes ? 0 : (int) $db->getValue(
            "SELECT COUNT(*) FROM {$p}orders o WHERE {$periodo} AND o.valid = 0 AND o.current_state NOT IN ({$fuera}) AND {$conArticulo}"
        );
        if ($pedidos === []) {
            return $salida;
        }
        $ids = implode(',', array_keys($pedidos));

        // 2. Todas las líneas de esos pedidos (hacen falta todas para repartir los vales), sus vales, sus regalos y su cupo.
        $lineas = [];
        foreach ($db->executeS(
            "SELECT od.id_order_detail, od.id_order, od.product_id, od.product_attribute_id, od.product_quantity, od.product_quantity_refunded,
                    od.product_quantity_return, od.unit_price_tax_excl, od.product_name, od.reduction_percent, od.reduction_amount_tax_excl
             FROM {$p}order_detail od WHERE od.id_order IN ({$ids}) ORDER BY od.id_order, od.id_order_detail"
        ) ?: [] as $f) {
            $lineas[(int) $f['id_order']][] = [
                'id' => (int) $f['id_order_detail'],
                'id_product' => (int) $f['product_id'],
                'ipa' => (int) $f['product_attribute_id'],
                'cantidad' => (int) $f['product_quantity'],
                'devueltas' => max((int) $f['product_quantity_refunded'], (int) $f['product_quantity_return']),
                'unidad' => (float) $f['unit_price_tax_excl'],
                'especial' => (float) $f['reduction_percent'] > 0 || (float) $f['reduction_amount_tax_excl'] > 0,
                'nombre' => (string) $f['product_name'],
            ];
        }
        $vales = [];
        $idsRegla = [];
        foreach ($db->executeS(
            "SELECT ocr.id_order, ocr.id_cart_rule, ocr.value_tax_excl, IFNULL(cr.code, '') AS codigo, IFNULL(cr.reduction_product, 0) AS producto,
                    IFNULL(cr.product_restriction, 0) AS restringe, IFNULL(cr.reduction_exclude_special, 0) AS sin_especial,
                    IFNULL(cr.free_shipping, ocr.free_shipping) AS envio, IFNULL(cr.reduction_amount, 0) AS importe, IFNULL(cr.reduction_percent, 0) AS pct,
                    cr.id_cart_rule IS NOT NULL AS existe
             FROM {$p}order_cart_rule ocr LEFT JOIN {$p}cart_rule cr ON cr.id_cart_rule = ocr.id_cart_rule
             WHERE ocr.id_order IN ({$ids}) AND ocr.deleted = 0"
        ) ?: [] as $f) {
            $vales[(int) $f['id_order']][] = [
                'id_cart_rule' => (int) $f['id_cart_rule'],
                'valor' => (float) $f['value_tax_excl'],
                'codigo' => (string) $f['codigo'],
                'producto' => (int) $f['producto'],
                'restringe' => (int) $f['restringe'] === 1,
                'sin_especial' => (int) $f['sin_especial'] === 1,
                'envio' => (int) $f['envio'] === 1,
                'importe' => (float) $f['importe'],
                'pct' => (float) $f['pct'],
                'existe' => (int) $f['existe'] === 1,
            ];
            if ((int) $f['restringe'] === 1) {
                $idsRegla[(int) $f['id_cart_rule']] = true;
            }
        }
        $grupos = self::restricciones(array_keys($idsRegla));
        $regaladas = [];
        $disparadores = [];
        if (self::hayTabla('kpy_coupon_regalo_pedido')) {
            foreach ($db->executeS("SELECT id_order, disparador, r_ipa, cantidad FROM {$p}kpy_coupon_regalo_pedido WHERE id_order IN ({$ids})") ?: [] as $f) {
                if ((int) $f['r_ipa'] > 0) {
                    $regaladas[(int) $f['id_order']][(int) $f['r_ipa']] = (int) ($regaladas[(int) $f['id_order']][(int) $f['r_ipa']] ?? 0) + max(1, (int) $f['cantidad']);
                }
                if ((int) $f['disparador'] > 0) {
                    $disparadores[(int) $f['id_order']][(int) $f['disparador']] = true;
                }
            }
        }
        $conCupo = [];
        if (self::hayTabla('kpy_coupon_cupo_pedido')) {
            foreach ($db->executeS("SELECT id_order, clave FROM {$p}kpy_coupon_cupo_pedido WHERE id_order IN ({$ids}) AND clave > 0") ?: [] as $f) {
                $conCupo[(int) $f['id_order']][(int) $f['clave']] = true;
            }
        }
        $meta = self::meta($lineas, $grupos);

        // 3. Repartir los vales pedido a pedido y 4. sumar por artículo.
        $pedidosPorArticulo = [];
        foreach ($pedidos as $idPedido => $pedido) {
            $suyas = $lineas[$idPedido] ?? [];
            $suyos = [];
            foreach ($vales[$idPedido] ?? [] as $v) {
                $v['grupos'] = $grupos[$v['id_cart_rule']] ?? [];
                $v['elegibles'] = self::elegibles($v, $suyas, $meta, $regaladas[$idPedido] ?? [], $disparadores[$idPedido] ?? [], $conCupo[$idPedido] ?? []);
                $suyos[] = $v;
            }
            $reparto = self::repartir($suyas, $suyos, (float) $pedido['total_shipping_tax_excl']);
            foreach ($suyas as $l) {
                $clave = $l['id_product'] . '-' . $l['ipa'];
                if (!isset($salida['articulos'][$clave])) {
                    $clave = $l['id_product'] . '-0';
                    if (!isset($salida['articulos'][$clave])) {
                        continue;
                    }
                }
                $rep = $reparto[$l['id']];
                if ($rep['uds'] <= 0) {
                    continue;
                }
                $a = &$salida['articulos'][$clave];
                $a['uds'] += $rep['uds'];
                // Las que fueron de regalo (a 0 €): las apunta kpycoupons en el pedido.
                $deRegalo = min($rep['uds'], (int) ($regaladas[$idPedido][$l['ipa']] ?? 0));
                $a['regalo'] += $deRegalo;
                $a['bruto'] += $rep['bruto'];
                $a['vales'] += $rep['vales'];
                $a['neto'] += $rep['neto'];
                $valor = (float) $a['valor'];
                unset($a);
                $pedidosPorArticulo[$clave][$idPedido] = true;
                $salida['detalle'][] = [
                    'id_order' => $idPedido,
                    'pedido' => (string) $pedido['reference'],
                    'fecha' => (string) $pedido['date_add'],
                    'pagado' => (int) $pedido['valid'] === 1,
                    'clave' => $clave,
                    'producto' => (string) $l['nombre'],
                    'ean' => (string) ($salida['articulos'][$clave]['ean'] ?? ''),
                    'ref' => (string) ($salida['articulos'][$clave]['ref'] ?? ''),
                    'uds' => $rep['uds'],
                    'regalo' => $deRegalo,
                    'bruto' => $rep['bruto'],
                    'vales' => $rep['vales'],
                    'neto' => $rep['neto'],
                    'valor' => $valor,
                    'importe' => $esRedencion ? round($rep['neto'] * $valor / 100, 2) : null,
                ];
            }
        }
        $t = &$salida['totales'];
        foreach ($salida['articulos'] as $clave => &$a) {
            $a['bruto'] = round($a['bruto'], 2);
            $a['vales'] = round($a['vales'], 2);
            $a['neto'] = round($a['neto'], 2);
            $a['pedidos'] = count($pedidosPorArticulo[$clave] ?? []);
            if ($esRedencion) {
                $a['importe'] = round($a['neto'] * (float) $a['valor'] / 100, 2);
                $t['importe'] += $a['importe'];
            }
            if ($esCoste) {
                $a['coste'] = round($a['uds'] * (float) $a['valor'], 2);
                $a['margen'] = round($a['neto'] - $a['coste'], 2);
                $t['coste'] += $a['coste'];
                $t['margen'] += $a['margen'];
            }
            $t['uds'] += $a['uds'];
            $t['regalo'] += $a['regalo'];
            $t['bruto'] += $a['bruto'];
            $t['vales'] += $a['vales'];
            $t['neto'] += $a['neto'];
        }
        unset($a, $t);
        foreach (['bruto', 'vales', 'neto', 'importe', 'coste', 'margen'] as $k) {
            if ($salida['totales'][$k] !== null) {
                $salida['totales'][$k] = round($salida['totales'][$k], 2);
            }
        }
        $salida['n_pedidos'] = count($pedidos);

        return $salida;
    }

    /**
     * Qué artículos de unos pares han vendido en un periodo (en pedidos que no están en los estados
     * de fuera, pagados o no): para no quitar de un acuerdo lo que ya vendió con él.
     *
     * @param array<int, array{0: int, 1: int}> $pares [id de producto, id de combinación]
     *
     * @return array<string, true> clave «producto-ipa»
     */
    public static function conVentas(array $pares, string $desde, string $hasta): array
    {
        if ($pares === []) {
            return [];
        }
        $articulos = [];
        foreach ($pares as [$id, $ipa]) {
            $articulos[(int) $id . '-' . (int) $ipa] = ['id_product' => (int) $id, 'ipa' => (int) $ipa];
        }
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance()->executeS(
            "SELECT DISTINCT od.product_id, od.product_attribute_id FROM {$p}order_detail od
             INNER JOIN {$p}orders o ON o.id_order = od.id_order
             WHERE o.date_add BETWEEN '" . pSQL($desde) . "' AND '" . pSQL($hasta) . "'
               AND o.current_state NOT IN (" . (implode(',', self::estadosFuera()) ?: '0') . ')
               AND (' . self::sqlArticulos($articulos, 'od') . ')'
        ) ?: [] as $f) {
            $salida[(int) $f['product_id'] . '-' . (int) $f['product_attribute_id']] = true;
            $salida[(int) $f['product_id'] . '-0'] = true;
        }

        return $salida;
    }

    /** SQL: las líneas de unos artículos (por combinación, o el producto entero si ipa es 0). */
    private static function sqlArticulos(array $articulos, string $alias): string
    {
        $ipas = [];
        $productos = [];
        foreach ($articulos as $a) {
            if ((int) $a['ipa'] > 0) {
                $ipas[(int) $a['ipa']] = true;
            } else {
                $productos[(int) $a['id_product']] = true;
            }
        }
        $partes = [];
        if ($ipas !== []) {
            $partes[] = $alias . '.product_attribute_id IN (' . implode(',', array_keys($ipas)) . ')';
        }
        if ($productos !== []) {
            $partes[] = $alias . '.product_id IN (' . implode(',', array_keys($productos)) . ')';
        }

        return $partes !== [] ? implode(' OR ', $partes) : '0';
    }

    /**
     * Las restricciones de producto de unas reglas de carrito, como las guarda PrestaShop.
     *
     * @return array<int, array<int, array{todas: bool, reglas: array<int, array{tipo: string, valores: int[]}>}>> por regla, sus grupos
     */
    private static function restricciones(array $idsRegla): array
    {
        $idsRegla = array_values(array_filter(array_map('intval', $idsRegla)));
        if ($idsRegla === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $salida = [];
        $tipoGrupo = self::hayColumna('cart_rule_product_rule_group', 'type');
        foreach (Db::getInstance()->executeS(
            'SELECT g.id_cart_rule, g.id_product_rule_group AS grupo, ' . ($tipoGrupo ? 'g.type' : "''") . " AS tipo_grupo,
                    r.id_product_rule AS regla, r.type AS tipo, v.id_item
             FROM {$p}cart_rule_product_rule_group g
             INNER JOIN {$p}cart_rule_product_rule r ON r.id_product_rule_group = g.id_product_rule_group
             LEFT JOIN {$p}cart_rule_product_rule_value v ON v.id_product_rule = r.id_product_rule
             WHERE g.id_cart_rule IN (" . implode(',', $idsRegla) . ')'
        ) ?: [] as $f) {
            $idRegla = (int) $f['id_cart_rule'];
            $grupo = (int) $f['grupo'];
            $regla = (int) $f['regla'];
            if (!isset($salida[$idRegla][$grupo])) {
                $salida[$idRegla][$grupo] = ['todas' => (string) $f['tipo_grupo'] === 'all_product_rules', 'reglas' => []];
            }
            if (!isset($salida[$idRegla][$grupo]['reglas'][$regla])) {
                $salida[$idRegla][$grupo]['reglas'][$regla] = ['tipo' => (string) $f['tipo'], 'valores' => []];
            }
            if ($f['id_item'] !== null) {
                $salida[$idRegla][$grupo]['reglas'][$regla]['valores'][] = (int) $f['id_item'];
            }
        }
        foreach ($salida as $idRegla => $grupos) {
            foreach ($grupos as $g => $grupo) {
                $salida[$idRegla][$g]['reglas'] = array_values($grupo['reglas']);
            }
            $salida[$idRegla] = array_values($salida[$idRegla]);
        }

        return $salida;
    }

    /**
     * Lo que hace falta saber de los productos de las líneas para las restricciones que usan los
     * vales (marca, proveedores, categorías, atributos): solo lo que se usa.
     */
    private static function meta(array $lineasPorPedido, array $grupos): array
    {
        $tipos = [];
        foreach ($grupos as $gs) {
            foreach ($gs as $g) {
                foreach ($g['reglas'] as $r) {
                    $tipos[$r['tipo']] = true;
                }
            }
        }
        $meta = ['producto' => [], 'ipa' => []];
        $productos = [];
        $ipas = [];
        foreach ($lineasPorPedido as $lineas) {
            foreach ($lineas as $l) {
                $productos[$l['id_product']] = true;
                if ($l['ipa'] > 0) {
                    $ipas[$l['ipa']] = true;
                }
            }
        }
        if ($productos === []) {
            return $meta;
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $en = implode(',', array_keys($productos));
        if (isset($tipos['manufacturers']) || isset($tipos['suppliers'])) {
            foreach ($db->executeS("SELECT id_product, id_manufacturer, id_supplier FROM {$p}product WHERE id_product IN ({$en})") ?: [] as $f) {
                $meta['producto'][(int) $f['id_product']]['marca'] = (int) $f['id_manufacturer'];
                $meta['producto'][(int) $f['id_product']]['proveedores'][] = (int) $f['id_supplier'];
            }
            if (isset($tipos['suppliers'])) {
                foreach ($db->executeS("SELECT DISTINCT id_product, id_supplier FROM {$p}product_supplier WHERE id_product IN ({$en})") ?: [] as $f) {
                    $meta['producto'][(int) $f['id_product']]['proveedores'][] = (int) $f['id_supplier'];
                }
            }
        }
        if (isset($tipos['categories'])) {
            foreach ($db->executeS("SELECT id_product, id_category FROM {$p}category_product WHERE id_product IN ({$en})") ?: [] as $f) {
                $meta['producto'][(int) $f['id_product']]['categorias'][] = (int) $f['id_category'];
            }
        }
        if (isset($tipos['attributes']) && $ipas !== []) {
            foreach ($db->executeS('SELECT id_product_attribute, id_attribute FROM ' . $p . 'product_attribute_combination WHERE id_product_attribute IN (' . implode(',', array_keys($ipas)) . ')') ?: [] as $f) {
                $meta['ipa'][(int) $f['id_product_attribute']][] = (int) $f['id_attribute'];
            }
        }

        return $meta;
    }

    /**
     * Las líneas a las que toca un vale. Puro.
     *
     * @param array $vale `producto` (reduction_product: id, 0 = todo el pedido, -1 = el más barato,
     *                    -2 = los seleccionados), `codigo`, `restringe`, `sin_especial`, `grupos`
     *                    (los de `restricciones()`), `existe` (la regla sigue en la tienda)
     * @param array<int, array{id: int, id_product: int, ipa: int, unidad: float, especial?: bool}> $lineas
     * @param array $meta lo de `meta()`: por producto `marca`, `proveedores`, `categorias`; por combinación sus atributos
     * @param array<int, int|true> $regaladas combinaciones regaladas en el pedido (con cuántas)
     * @param array<int, true> $disparadores combinaciones que llevaban un regalo
     * @param array<int, true> $conCupo combinaciones con unidades de un cupo
     *
     * @return int[] ids de línea
     */
    public static function elegibles(array $vale, array $lineas, array $meta = [], array $regaladas = [], array $disparadores = [], array $conCupo = []): array
    {
        $todas = array_map(static fn (array $l): int => (int) $l['id'], $lineas);
        $esRegalada = static fn (array $l): bool => isset($regaladas[(int) $l['ipa']]) && (int) $l['ipa'] > 0;
        $ids = static fn (array $ls): array => array_values(array_map(static fn (array $l): int => (int) $l['id'], $ls));
        $producto = (int) ($vale['producto'] ?? 0);
        $codigo = (string) ($vale['codigo'] ?? '');

        if ($producto > 0) {
            $suyas = array_filter($lineas, static fn (array $l): bool => (int) $l['id_product'] === $producto);
            if ($suyas === []) {
                return $todas;
            }
            $pagadas = array_filter($suyas, static fn (array $l): bool => !$esRegalada($l));
            $preferidas = [];
            if (strncmp($codigo, 'KPYRG', 5) === 0) {
                $preferidas = array_filter($suyas, $esRegalada);
            } elseif (strncmp($codigo, 'KPYSP', 5) === 0) {
                $preferidas = array_filter($pagadas, static fn (array $l): bool => isset($disparadores[(int) $l['ipa']]) || isset($conCupo[(int) $l['ipa']]));
            } elseif (strncmp($codigo, 'KPYCP', 5) === 0) {
                $preferidas = array_filter($pagadas, static fn (array $l): bool => isset($conCupo[(int) $l['ipa']]));
            }

            return $ids($preferidas ?: ($pagadas ?: $suyas));
        }

        $candidatas = array_filter($lineas, static fn (array $l): bool => !$esRegalada($l)) ?: $lineas;
        if (!empty($vale['sin_especial'])) {
            $candidatas = array_filter($candidatas, static fn (array $l): bool => empty($l['especial']));
        }
        if (($producto === -2 || $producto === -1) && !empty($vale['restringe'])) {
            if (empty($vale['grupos'])) {
                // La regla ya no está (o no tiene restricciones a la vista): no se sabe a qué tocó.
                return $ids($candidatas) ?: $todas;
            }
            $candidatas = array_filter($candidatas, static fn (array $l): bool => self::cumple($l, (array) $vale['grupos'], $meta));
        }
        if ($producto === -1 && $candidatas !== []) {
            usort($candidatas, static fn (array $a, array $b): int => ((float) $a['unidad'] <=> (float) $b['unidad']) ?: ((int) $a['id'] <=> (int) $b['id']));
            $candidatas = [reset($candidatas)];
        }

        return $ids($candidatas);
    }

    /** ¿Cumple una línea las restricciones de una regla? Cada grupo tiene que cumplirse; dentro, todas sus reglas o una, según el grupo. */
    public static function cumple(array $linea, array $grupos, array $meta = []): bool
    {
        foreach ($grupos as $g) {
            $resultados = array_map(static fn (array $r): bool => self::cumpleRegla($linea, $r, $meta), (array) ($g['reglas'] ?? []));
            if ($resultados === []) {
                continue;
            }
            $ok = !empty($g['todas']) ? !in_array(false, $resultados, true) : in_array(true, $resultados, true);
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    private static function cumpleRegla(array $l, array $regla, array $meta): bool
    {
        $valores = array_map('intval', (array) ($regla['valores'] ?? []));
        $idProducto = (int) $l['id_product'];
        $ipa = (int) $l['ipa'];
        switch ((string) ($regla['tipo'] ?? '')) {
            case 'products':
                return in_array($idProducto, $valores, true);
            case 'combinations':
                return in_array($ipa, $valores, true);
            case 'manufacturers':
                return in_array((int) ($meta['producto'][$idProducto]['marca'] ?? 0), $valores, true);
            case 'suppliers':
                return array_intersect(array_map('intval', (array) ($meta['producto'][$idProducto]['proveedores'] ?? [])), $valores) !== [];
            case 'categories':
                return array_intersect(array_map('intval', (array) ($meta['producto'][$idProducto]['categorias'] ?? [])), $valores) !== [];
            case 'attributes':
                return array_intersect(array_map('intval', (array) ($meta['ipa'][$ipa] ?? [])), $valores) !== [];
            default:
                return true; // un tipo que no se conoce no deja fuera a nadie
        }
    }

    /**
     * Reparte los vales de un pedido entre sus líneas. Puro.
     *
     * Primero los de «este producto», luego los de los seleccionados o el más barato, y al final
     * los de todo el pedido: así un vale de todo el pedido no le toca a un regalo, que ya está a 0.
     * Cada vale va a sus `elegibles` en proporción a lo que les queda por pagar, sin pasar de ello.
     * Se reparte con las unidades pedidas y al final se descuentan las devueltas con su parte.
     *
     * @param array<int, array{id: int, id_product: int, ipa: int, cantidad: int, devueltas?: int, unidad: float}> $lineas
     * @param array<int, array{valor: float, producto?: int, envio?: bool, importe?: float, pct?: float, elegibles?: int[]}> $vales
     *                                                                                                                          `valor`: lo que descontó, sin IVA; `elegibles`: las líneas a las que toca (sin ellas, a todas)
     * @param float $envio los portes del pedido sin IVA (lo que vale un vale de portes gratis)
     *
     * @return array<int, array{uds: int, bruto: float, vales: float, neto: float}> por id de línea
     */
    public static function repartir(array $lineas, array $vales, float $envio = 0.0): array
    {
        $estado = [];
        foreach ($lineas as $l) {
            $estado[(int) $l['id']] = ['cantidad' => max(0, (int) $l['cantidad']), 'devueltas' => (int) ($l['devueltas'] ?? 0), 'bruto' => (float) $l['unidad'] * max(0, (int) $l['cantidad']), 'vales' => 0.0];
        }
        $orden = static function (array $v): int {
            $producto = (int) ($v['producto'] ?? 0);

            return $producto > 0 ? 0 : ($producto < 0 ? 1 : 2);
        };
        usort($vales, static fn (array $a, array $b): int => $orden($a) <=> $orden($b));
        foreach ($vales as $v) {
            $valor = (float) $v['valor'];
            if (!empty($v['envio'])) {
                if ((float) ($v['importe'] ?? 0) <= 0 && (float) ($v['pct'] ?? 0) <= 0) {
                    continue; // solo portes
                }
                $valor = max(0.0, $valor - $envio);
            }
            if ($valor <= 0.000001) {
                continue;
            }
            $elegibles = array_values(array_filter(array_map('intval', (array) ($v['elegibles'] ?? [])), static fn (int $id): bool => isset($estado[$id])));
            self::repartirEntre($estado, $elegibles ?: array_keys($estado), $valor);
        }
        $salida = [];
        foreach ($estado as $id => $s) {
            $parte = $s['cantidad'] > 0 ? max(0, $s['cantidad'] - $s['devueltas']) / $s['cantidad'] : 0.0;
            // Se redondea al final y a partir del neto: así un regalo (vale = línea, salvo milésimas de
            // PrestaShop) queda a 0,00, y bruto − vales = neto siempre.
            $bruto = round($s['bruto'] * $parte, 2);
            $neto = max(0.0, round(($s['bruto'] - min($s['vales'], $s['bruto'])) * $parte, 2));
            $salida[$id] = ['uds' => max(0, $s['cantidad'] - $s['devueltas']), 'bruto' => $bruto, 'vales' => round($bruto - $neto, 2), 'neto' => $neto];
        }

        return $salida;
    }

    /** Reparte un importe entre unas líneas en proporción a lo que les queda, sin pasar de ello. */
    private static function repartirEntre(array &$estado, array $ids, float $importe): void
    {
        $pendientes = array_values(array_unique($ids));
        for ($vuelta = 0; $vuelta < 20 && $importe > 0.000001 && $pendientes !== []; ++$vuelta) {
            $restos = [];
            $total = 0.0;
            foreach ($pendientes as $id) {
                $resto = $estado[$id]['bruto'] - $estado[$id]['vales'];
                if ($resto > 0.000001) {
                    $restos[$id] = $resto;
                    $total += $resto;
                }
            }
            if ($total <= 0.000001) {
                return;
            }
            $sobra = 0.0;
            $llenas = [];
            foreach ($restos as $id => $resto) {
                $parte = $importe * $resto / $total;
                if ($parte >= $resto - 0.000001) {
                    $estado[$id]['vales'] += $resto;
                    $sobra += $parte - $resto;
                    $llenas[] = $id;
                } else {
                    $estado[$id]['vales'] += $parte;
                }
            }
            if ($llenas === []) {
                return;
            }
            $importe = $sobra;
            $pendientes = array_values(array_diff(array_keys($restos), $llenas));
        }
    }

    /**
     * Los estados de pedido que no cuentan como venta: los marcados en la pantalla o, si no hay
     * nada marcado, cancelado, error de pago y reembolsado.
     *
     * @return int[]
     */
    public static function estadosFuera(): array
    {
        $guardados = trim((string) Configuration::getGlobalValue(self::CONF_ESTADOS));
        if ($guardados !== '') {
            return array_values(array_unique(array_filter(array_map('intval', explode(',', $guardados)))));
        }

        return self::estadosFueraPorDefecto();
    }

    /** @return int[] cancelado, error de pago y reembolsado */
    public static function estadosFueraPorDefecto(): array
    {
        return array_values(array_unique(array_filter([
            (int) Configuration::get('PS_OS_CANCELED'),
            (int) Configuration::get('PS_OS_ERROR'),
            (int) Configuration::get('PS_OS_REFUND'),
        ])));
    }

    public static function hayTabla(string $tabla): bool
    {
        static $hay = [];
        if (!isset($hay[$tabla])) {
            $hay[$tabla] = (bool) Db::getInstance()->executeS("SHOW TABLES LIKE '" . pSQL(_DB_PREFIX_ . $tabla) . "'");
        }

        return $hay[$tabla];
    }

    private static function hayColumna(string $tabla, string $columna): bool
    {
        static $hay = [];
        $k = $tabla . '.' . $columna;
        if (!isset($hay[$k])) {
            $hay[$k] = (bool) Db::getInstance()->executeS('SHOW COLUMNS FROM ' . _DB_PREFIX_ . bqSQL($tabla) . " LIKE '" . pSQL($columna) . "'");
        }

        return $hay[$k];
    }
}
