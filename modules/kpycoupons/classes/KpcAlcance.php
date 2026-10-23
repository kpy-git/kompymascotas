<?php
/**
 * A qué productos y formatos de la tienda llega una promoción.
 *
 * Aquí solo se pregunta a la tienda: qué productos pueden entrar (por marca y sus gamas, categoría,
 * característica o porque están en la lista) y sus datos (formatos con su tamaño y su nivel de
 * pack, categorías, Special Price). Lo que decide, producto a producto, es
 * `KpcElegibles::combinaciones()`, la regla de negocio pura.
 *
 * Cada artículo se identifica con una **clave**: el id de la combinación, o el id del producto en
 * negativo si el producto no tiene combinaciones (los 485 accesorios de Trixie y compañía). Así un
 * «formato» es siempre un número y se puede repartir entre promociones igual que los demás.
 */
class KpcAlcance
{
    /** La clave de un artículo: su combinación, o el producto en negativo si no tiene. */
    public static function clave(int $idProducto, int $ipa): int
    {
        return $ipa > 0 ? $ipa : -$idProducto;
    }

    /**
     * Los artículos a los que llega una promoción.
     *
     * Qué es «tener ya Special Price» depende de la promoción:
     *   - un cupón o una 2ª unidad mira los Special Price **de ese momento** (su primer día, si es
     *     programada), incluidos los de las promociones de Special Price de esta pantalla;
     *   - un Special Price mira los **de otros** que se crucen con sus fechas: dos a la vez en el
     *     mismo formato se pisarían, y manda el que ya estaba.
     *
     * @param array $cupon una fila de `kpy_coupon` con sus `ambitos` (o lo que llega del formulario)
     * @param string|null $momento cuándo se mira el Special Price (`Y-m-d H:i:s`); por defecto, ahora.
     *
     * @return array{entran: array<int, int>, especial: array<int, int>, valores: array<int, array{v: float, de: ?int}>,
     *               niveles: array<int, int>, formatos: array<int, array<int, array>>}
     *         clave => id de producto. `especial`: los que se quedan fuera por tener Special Price.
     *         `valores`: el % o el precio propio de los artículos de la lista que lo traen (`de`: la
     *         clave del saco suelto, si es un pack que lo hereda). `niveles`: el nivel de pack de cada
     *         clave que entra. `formatos`: por producto, sus formatos a la venta (ipa => nombre, nivel,
     *         base), para emparejar regalos.
     */
    public static function articulos(array $cupon, int $idTienda, ?string $momento = null): array
    {
        $vacio = ['entran' => [], 'especial' => [], 'valores' => [], 'unidades' => [], 'niveles' => [], 'formatos' => []];
        $porTipo = KpcElegibles::porTipo((array) ($cupon['ambitos'] ?? []));
        [$gamas, $generales] = self::gamas($porTipo['marca'], $porTipo['categoria']);
        $rasgos = self::rasgos($porTipo['caracteristica']);

        $productos = self::candidatos($porTipo, $gamas, $generales, $rasgos, $idTienda);
        if ($productos === []) {
            return $vacio;
        }

        $momento = $momento ?: date('Y-m-d H:i:s');
        $ventana = null;
        if (KpcElegibles::esPrecio($cupon)) {
            $ventana = [
                'desde' => (string) ($cupon['fecha_desde'] ?: $momento),
                'hasta' => (string) ($cupon['fecha_hasta'] ?: '2099-12-31 23:59:59'),
                'propia' => (int) ($cupon['id_kpy_coupon'] ?? 0),
            ];
        }
        $categorias = $generales;
        foreach ($gamas as $deUnaMarca) {
            $categorias = array_merge($categorias, $deUnaMarca);
        }
        $datos = self::datos($productos, array_values(array_unique($categorias)), $porTipo['caracteristica'], $idTienda, $momento, $ventana);
        $definicion = [
            'ambitos' => (array) ($cupon['ambitos'] ?? []),
            'rasgos' => $rasgos,
            'gamas' => $gamas,
            'generales' => $generales,
            'tamanos' => KpcElegibles::tamanos($cupon),
            'packs' => (string) ($cupon['packs'] ?? ''),
            'tipo' => (string) ($cupon['tipo'] ?? 'cupon'),
            'incluir_packs' => (bool) ($cupon['incluir_packs'] ?? true),
            'sin_outlet' => (bool) ($cupon['sin_outlet'] ?? true),
            'excluir_especial' => false,
            // En un regalo, si los packs también disparan (si no, solo los sueltos: `KpcElegibles::niveles`).
            'regalo_packs' => (bool) ($cupon['regalo_packs'] ?? false),
        ];
        // Un Special Price nunca pisa otro; un cupón, salvo que se diga lo contrario, tampoco.
        $respetar = $ventana !== null || (bool) ($cupon['excluir_especial'] ?? true);

        // El valor propio de cada artículo de la lista (su % o su precio), si lo trae.
        $valorCombinacion = [];
        $valorProducto = [];
        foreach ((array) ($cupon['ambitos'] ?? []) as $a) {
            if (!isset($a['valor']) || $a['valor'] === null) {
                continue;
            }
            if (($a['tipo'] ?? '') === 'combinacion') {
                $valorCombinacion[(int) $a['id']] = (float) $a['valor'];
            } elseif (($a['tipo'] ?? '') === 'producto') {
                $valorProducto[(int) $a['id']] = (float) $a['valor'];
            }
        }
        // Y sus unidades (cupo por artículo): por formato, o por producto sin formatos.
        $unidades = [];
        foreach ((array) ($cupon['ambitos'] ?? []) as $a) {
            if (isset($a['unidades']) && $a['unidades'] !== null && in_array((string) ($a['tipo'] ?? ''), ['combinacion', 'producto'], true)) {
                $unidades[KpcCupo::claveDeAmbito((string) $a['tipo'], (int) ($a['id'] ?? 0))] = (int) $a['unidades'];
            }
        }

        $salida = $vacio;
        foreach ($datos as $idProducto => $producto) {
            $formatos = KpcElegibles::formatos($producto['combinaciones']);
            foreach (KpcElegibles::combinaciones($definicion, $producto, $producto['combinaciones']) as $ipa) {
                $clave = self::clave((int) $idProducto, (int) $ipa);
                if ($respetar && $formatos[$ipa]['especial']) {
                    $salida['especial'][$clave] = (int) $idProducto;
                    continue;
                }
                $salida['entran'][$clave] = (int) $idProducto;
                if (isset($unidades[$clave])) {
                    $salida['unidades'][$clave] = $unidades[$clave];
                }
                $salida['niveles'][$clave] = (int) $formatos[$ipa]['nivel'];
                if (!isset($salida['formatos'][(int) $idProducto])) {
                    foreach ($formatos as $otro => $f) {
                        $salida['formatos'][(int) $idProducto][(int) $otro] = ['nombre' => $f['nombre'], 'nivel' => $f['nivel'], 'base' => $f['base'], 'apagado' => false];
                    }
                }

                $propio = $valorCombinacion[(int) $ipa] ?? $valorProducto[(int) $idProducto] ?? null;
                if ($propio !== null) {
                    $salida['valores'][$clave] = ['v' => $propio, 'de' => null];
                    continue;
                }
                // Un pack que ha entrado solo lleva lo de lo que lleva dentro: el mismo descuento
                // que su saco (o su caja) de la lista. Se sube por la cadena: el de 48 es de 24,
                // que es de 12.
                $arriba = $producto['padres'][(int) $ipa] ?? null;
                $vistos = [];
                while ($arriba !== null && !isset($vistos[$arriba])) {
                    $vistos[$arriba] = true;
                    if (isset($valorCombinacion[$arriba])) {
                        $salida['valores'][$clave] = ['v' => $valorCombinacion[$arriba], 'de' => self::clave((int) $idProducto, $arriba)];
                        break;
                    }
                    $arriba = $producto['padres'][$arriba] ?? null;
                }
            }
        }

        return $salida;
    }

    /**
     * Los productos que pueden entrar: los que cumplen los filtros y los de la lista, menos los
     * excluidos. Solo los que están encendidos en la tienda.
     *
     * @param array<int, int[]> $gamas por marca, las categorías que la afinan
     * @param int[] $generales las categorías que se cruzan con todo
     *
     * @return int[]
     */
    private static function candidatos(array $porTipo, array $gamas, array $generales, array $rasgos, int $idTienda): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $ids = [];

        $condiciones = [];
        if ($porTipo['marca'] !== []) {
            // Cada marca con sus gamas, si las tiene: «Royal Canin de Razas gato, o Farmina entera».
            $partes = [];
            $enteras = [];
            foreach ($porTipo['marca'] as $marca) {
                if (!empty($gamas[$marca])) {
                    $partes[] = '(p.id_manufacturer = ' . (int) $marca . " AND EXISTS (SELECT 1 FROM {$p}category_product cp
                        WHERE cp.id_product = p.id_product AND cp.id_category IN (" . implode(',', array_map('intval', $gamas[$marca])) . ')))';
                } else {
                    $enteras[] = (int) $marca;
                }
            }
            if ($enteras !== []) {
                $partes[] = 'p.id_manufacturer IN (' . implode(',', $enteras) . ')';
            }
            $condiciones[] = '(' . implode(' OR ', $partes) . ')';
        }
        if ($generales !== []) {
            $condiciones[] = "EXISTS (SELECT 1 FROM {$p}category_product cp WHERE cp.id_product = p.id_product
                              AND cp.id_category IN (" . implode(',', array_map('intval', $generales)) . '))';
        }
        foreach (KpcElegibles::grupos($porTipo['caracteristica'], $rasgos) as $valores) {
            $condiciones[] = "EXISTS (SELECT 1 FROM {$p}feature_product fp WHERE fp.id_product = p.id_product
                              AND fp.id_feature_value IN (" . implode(',', $valores) . '))';
        }
        if ($condiciones !== []) {
            $ids = array_map('intval', array_column($db->executeS(
                "SELECT p.id_product FROM {$p}product p WHERE " . implode(' AND ', $condiciones)
            ) ?: [], 'id_product'));
        }

        $ids = array_merge($ids, $porTipo['producto']);
        if ($porTipo['combinacion'] !== []) {
            $ids = array_merge($ids, array_map('intval', array_column($db->executeS(
                "SELECT DISTINCT id_product FROM {$p}product_attribute
                 WHERE id_product_attribute IN (" . implode(',', $porTipo['combinacion']) . ')'
            ) ?: [], 'id_product')));
        }
        $ids = array_values(array_diff(array_unique($ids), $porTipo['excluir_producto']));
        if ($ids === []) {
            return [];
        }

        // Solo los que se venden de verdad en esta tienda: encendidos, visibles (no «en ningún
        // sitio», que es como se apagan sin borrarlos) y que se pueden pedir.
        $activos = [];
        foreach (array_chunk($ids, 2000) as $trozo) {
            $activos = array_merge($activos, array_map('intval', array_column($db->executeS(
                "SELECT id_product FROM {$p}product_shop
                 WHERE active = 1 AND visibility IN ('both', 'catalog', 'search') AND available_for_order = 1
                   AND id_shop = " . (int) $idTienda . ' AND id_product IN (' . implode(',', $trozo) . ')'
            ) ?: [], 'id_product')));
        }

        return $activos;
    }

    /** ¿Existe esta tabla de Kompy en la tienda? (`kpy_product_attribute`, `kpy_packs`…) */
    public static function hayTabla(string $tabla): bool
    {
        static $hay = [];
        if (!isset($hay[$tabla])) {
            $hay[$tabla] = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = '" . pSQL(_DB_PREFIX_ . $tabla) . "'"
            );
        }

        return $hay[$tabla];
    }

    /** ¿Hay tabla de formatos apagados (`kpy_product_attribute`, la que usa la ficha)? */
    public static function hayFormatosApagados(): bool
    {
        return self::hayTabla('kpy_product_attribute');
    }

    /** La condición SQL «esta combinación (`pa`) está apagada». */
    public static function sqlApagado(): string
    {
        return self::hayFormatosApagados()
            ? 'EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'kpy_product_attribute k WHERE k.id_product_attribute = pa.id_product_attribute AND k.active = 0)'
            : '0';
    }

    /**
     * Los packs apuntados en `kpy_packs` de estos productos: los que son N de otro formato del
     * mismo producto (los de varios productos o con regalo se leen por el nombre).
     *
     * @param int[] $productos
     *
     * @return array<int, array<int, array{ipa: int, n: int}>> id de producto => id del pack => lo que lleva
     */
    public static function tablaPacks(array $productos): array
    {
        if ($productos === [] || !self::hayTabla('kpy_packs')) {
            return [];
        }
        $filas = [];
        foreach (array_chunk(array_map('intval', $productos), 2000) as $trozo) {
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_product_pack, id_product_item, id_product_attribute_item, quantity FROM ' . _DB_PREFIX_ . 'kpy_packs
                 WHERE is_gift = 0 AND id_product_item IN (' . implode(',', $trozo) . ')'
            ) ?: [] as $f) {
                $filas[(string) $f['id_product_pack']][] = $f;
            }
        }
        $salida = [];
        foreach ($filas as $pack => $dentro) {
            $partes = explode('-', $pack);
            $idProducto = (int) ($partes[0] ?? 0);
            $ipa = (int) ($partes[1] ?? 0);
            if (count($dentro) !== 1 || $ipa <= 0 || (int) $dentro[0]['id_product_item'] !== $idProducto) {
                continue;
            }
            $salida[$idProducto][$ipa] = ['ipa' => (int) $dentro[0]['id_product_attribute_item'], 'n' => (int) $dentro[0]['quantity']];
        }

        return $salida;
    }

    /**
     * Lo que la regla necesita de cada producto: marca, categorías, características y formatos,
     * con su nombre, su tamaño, su nivel de pack y si tienen Special Price en ese momento.
     *
     * @param int[] $productos
     * @param int[] $categorias las que hay que mirar (gamas y generales, con sus subcategorías)
     * @param int[] $caracteristicas los valores que hay que mirar
     * @param array{desde: string, hasta: string, propia: int}|null $ventana solo para un Special Price:
     *        sus fechas y su id (sus propios precios no cuentan como «ya tiene»)
     *
     * @return array<int, array{id_product: int, id_manufacturer: int, categorias: int[], caracteristicas: int[],
     *                          combinaciones: array<int, array>, padres: array<int, ?int>}>
     */
    private static function datos(array $productos, array $categorias, array $caracteristicas, int $idTienda, string $momento, ?array $ventana = null): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $salida = [];

        foreach (array_chunk($productos, 1000) as $trozo) {
            $lista = implode(',', array_map('intval', $trozo));
            foreach ($db->executeS("SELECT id_product, id_manufacturer FROM {$p}product WHERE id_product IN ({$lista})") ?: [] as $f) {
                $salida[(int) $f['id_product']] = [
                    'id_product' => (int) $f['id_product'],
                    'id_manufacturer' => (int) $f['id_manufacturer'],
                    'categorias' => [],
                    'caracteristicas' => [],
                    'combinaciones' => [],
                    'padres' => [],
                ];
            }
            if ($categorias !== []) {
                foreach ($db->executeS(
                    "SELECT id_product, id_category FROM {$p}category_product
                     WHERE id_product IN ({$lista}) AND id_category IN (" . implode(',', array_map('intval', $categorias)) . ')'
                ) ?: [] as $f) {
                    $salida[(int) $f['id_product']]['categorias'][] = (int) $f['id_category'];
                }
            }
            if ($caracteristicas !== []) {
                foreach ($db->executeS(
                    "SELECT id_product, id_feature_value FROM {$p}feature_product
                     WHERE id_product IN ({$lista}) AND id_feature_value IN (" . implode(',', array_map('intval', $caracteristicas)) . ')'
                ) ?: [] as $f) {
                    $salida[(int) $f['id_product']]['caracteristicas'][] = (int) $f['id_feature_value'];
                }
            }

            // Todos los formatos, también los apagados: hacen falta para saber qué es pack de qué
            // (si el saco suelto está apagado, su pack sigue siendo un pack). Luego los apagados, los
            // que la ficha no enseña, no entran; y un producto con todos apagados no entra entero.
            $todos = [];
            $apagados = [];
            foreach ($db->executeS(
                "SELECT pa.id_product, pa.id_product_attribute AS ipa, " . self::sqlApagado() . " AS apagado,
                        GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre
                 FROM {$p}product_attribute pa
                 LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                 LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idioma}
                 WHERE pa.id_product IN ({$lista})
                 GROUP BY pa.id_product_attribute"
            ) ?: [] as $f) {
                $todos[(int) $f['id_product']][] = ['ipa' => (int) $f['ipa'], 'nombre' => (string) $f['nombre']];
                if ((int) $f['apagado']) {
                    $apagados[(int) $f['ipa']] = true;
                }
            }
            $packs = self::tablaPacks($trozo);
            foreach ($todos as $idProducto => $suyos) {
                if (!isset($salida[$idProducto])) {
                    continue;
                }
                $salida[$idProducto]['con_formatos'] = true;
                $niveles = KpcFormatos::niveles($suyos, $packs[$idProducto] ?? []);
                foreach ($suyos as $c) {
                    $salida[$idProducto]['padres'][$c['ipa']] = $niveles[$c['ipa']]['padre'];
                    if (!isset($apagados[$c['ipa']])) {
                        $salida[$idProducto]['combinaciones'][$c['ipa']] = $c + ['especial' => false] + $niveles[$c['ipa']];
                    }
                }
            }

            if (KpcCupon::hayTablaEspeciales()) {
                $cuando = $ventana === null
                    ? "'" . pSQL($momento) . "' BETWEEN sp.date_from AND sp.expire"
                    : "sp.date_from <= '" . pSQL($ventana['hasta']) . "' AND sp.expire >= '" . pSQL($ventana['desde']) . "'
                       AND NOT EXISTS (SELECT 1 FROM {$p}kpy_coupon_precio cp
                                       WHERE cp.id_kpy_coupon = " . (int) $ventana['propia'] . '
                                         AND cp.id_product = sp.id_product AND cp.id_product_attribute = sp.id_product_attribute
                                         AND cp.date_from = sp.date_from)';
                foreach ($db->executeS(
                    "SELECT sp.id_product, sp.id_product_attribute AS ipa FROM {$p}kpy_special_price sp
                     WHERE sp.id_product IN ({$lista}) AND sp.id_shop = " . (int) $idTienda . " AND {$cuando}"
                ) ?: [] as $f) {
                    $idProducto = (int) $f['id_product'];
                    $ipa = (int) $f['ipa'];
                    if (isset($salida[$idProducto]['combinaciones'][$ipa])) {
                        $salida[$idProducto]['combinaciones'][$ipa]['especial'] = true;
                    } elseif ($ipa === 0) {
                        $salida[$idProducto]['sin_formatos_especial'] = true;
                    }
                }
            }
        }

        // Un producto sin combinaciones es un solo artículo, con `ipa` 0. Uno con todas apagadas,
        // ninguno.
        foreach ($salida as $id => $producto) {
            if ($producto['combinaciones'] === [] && empty($producto['con_formatos'])) {
                $salida[$id]['combinaciones'] = [0 => [
                    'ipa' => 0, 'nombre' => '', 'especial' => !empty($producto['sin_formatos_especial']),
                    'nivel' => 1, 'padre' => null, 'base' => null, 'tam' => '',
                ]];
            }
            $salida[$id]['combinaciones'] = array_values($salida[$id]['combinaciones']);
        }

        return $salida;
    }

    /**
     * Separa las categorías elegidas en **gamas** (las que cuelgan de la categoría de una marca
     * elegida: afinan solo esa marca) y **generales** (las demás: se cruzan con todo). Las dos, con
     * todas sus subcategorías.
     *
     * @param int[] $marcas
     * @param int[] $categorias
     * @param bool $conHijas con las subcategorías (para buscar) o solo las elegidas (para nombrarlas)
     *
     * @return array{0: array<int, int[]>, 1: int[]} gamas por marca, y generales
     */
    public static function gamas(array $marcas, array $categorias, bool $conHijas = true): array
    {
        if ($categorias === []) {
            return [[], []];
        }
        $raices = self::raices($marcas);
        $posicion = [];
        if ($raices !== []) {
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_category, nleft, nright FROM ' . _DB_PREFIX_ . 'category
                 WHERE id_category IN (' . implode(',', array_map('intval', $categorias)) . ')'
            ) ?: [] as $f) {
                $posicion[(int) $f['id_category']] = [(int) $f['nleft'], (int) $f['nright']];
            }
        }
        $gamas = [];
        $generales = [];
        foreach ($categorias as $categoria) {
            $categoria = (int) $categoria;
            $deMarca = false;
            foreach ($raices as $marca => [$izquierda, $derecha]) {
                if (isset($posicion[$categoria]) && $posicion[$categoria][0] >= $izquierda && $posicion[$categoria][1] <= $derecha) {
                    $gamas[$marca][] = $categoria;
                    $deMarca = true;
                }
            }
            if (!$deMarca) {
                $generales[] = $categoria;
            }
        }
        if (!$conHijas) {
            return [$gamas, $generales];
        }
        foreach ($gamas as $marca => $deLaMarca) {
            $gamas[$marca] = self::conSubcategorias($deLaMarca);
        }

        return [$gamas, self::conSubcategorias($generales)];
    }

    /**
     * La categoría de cada marca (la de su página de marca, `kpy_manufacturer`), si la tiene.
     *
     * @param int[] $marcas
     *
     * @return array<int, array{0: int, 1: int}> id de marca => [nleft, nright] de su categoría
     */
    public static function raices(array $marcas): array
    {
        if ($marcas === [] || !self::hayTabla('kpy_manufacturer')) {
            return [];
        }
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT km.id_manufacturer, c.nleft, c.nright FROM {$p}kpy_manufacturer km
             INNER JOIN {$p}category c ON c.id_category = km.id_category_related
             WHERE km.id_category_related > 0 AND km.id_manufacturer IN (" . implode(',', array_map('intval', $marcas)) . ')'
        ) ?: [] as $f) {
            $salida[(int) $f['id_manufacturer']] = [(int) $f['nleft'], (int) $f['nright']];
        }

        return $salida;
    }

    /**
     * Las categorías con todas las que cuelgan de ellas: pedir «Pienso para gatos» es pedir
     * también «Pienso gato Royal Canin», aunque el producto solo esté apuntado en la de abajo.
     *
     * @param int[] $categorias
     *
     * @return int[]
     */
    public static function conSubcategorias(array $categorias): array
    {
        if ($categorias === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT DISTINCT hija.id_category FROM {$p}category padre
             INNER JOIN {$p}category hija ON hija.nleft >= padre.nleft AND hija.nright <= padre.nright
             WHERE padre.id_category IN (" . implode(',', array_map('intval', $categorias)) . ')'
        ) ?: [];

        return array_values(array_unique(array_merge(
            array_map('intval', $categorias),
            array_map('intval', array_column($filas, 'id_category'))
        )));
    }

    /**
     * De qué característica es cada valor pedido (para cruzar «Gato» con «Húmeda» y no mezclarlos).
     *
     * @param int[] $valores
     *
     * @return array<int, int> id de valor => id de característica
     */
    public static function rasgos(array $valores): array
    {
        if ($valores === []) {
            return [];
        }
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_feature_value, id_feature FROM ' . _DB_PREFIX_ . 'feature_value
             WHERE id_feature_value IN (' . implode(',', array_map('intval', $valores)) . ')'
        ) ?: [] as $f) {
            $salida[(int) $f['id_feature_value']] = (int) $f['id_feature'];
        }

        return $salida;
    }
}
