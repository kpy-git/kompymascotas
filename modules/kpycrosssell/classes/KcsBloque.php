<?php
/**
 * Lo que necesita la plantilla del bloque «Caprichitos»: para cada sugerencia, su foto, su nombre,
 * el formato más barato que hay con stock, su precio y lo que hay que mandar para meterlo en la
 * cesta de un toque.
 *
 * La regla de diseño va aquí porque es de negocio, no de CSS: **se ofrece el formato más barato con
 * stock**. Quien está comprando un saco de 50 € no quiere elegir entre seis latas: quiere decir que
 * sí a una y seguir.
 */
class KcsBloque
{
    /**
     * @param array<int, array{id_product: int, origen: string}> $sugerencias
     *
     * @return array<int, array{id_product: int, id_product_attribute: int, nombre: string, formato: string,
     *                          precio: float, precio_txt: string, antes_txt: string, foto: string, url: string, origen: string}>
     */
    public static function articulos(array $sugerencias, Kpycrosssell $modulo, int $idPrincipal = 0, int $ipaPrincipal = 0): array
    {
        $ids = array_map(static fn (array $s): int => (int) $s['id_product'], $sugerencias);
        if ($ids === []) {
            return [];
        }
        $origen = [];
        foreach ($sugerencias as $s) {
            $origen[(int) $s['id_product']] = (string) $s['origen'];
        }

        $contexto = Context::getContext();
        $idLang = (int) $contexto->language->id;
        $idTienda = (int) $contexto->shop->id;
        $p = _DB_PREFIX_;

        // La unidad mínima de venta de cada producto —el formato más barato que **se vende de
        // verdad**: con stock y sin apagar en `kpy_product_attribute`, que es donde se quitan las
        // unidades sueltas que el distribuidor no sirve— y su foto.
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pr.id_product, pr.id_manufacturer, pl.name, pl.link_rewrite, ma.name AS marca,
                    (SELECT pa.id_product_attribute
                       FROM {$p}product_attribute pa
                       INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                             AND sa.id_shop = {$idTienda} AND sa.quantity > 0
                      WHERE pa.id_product = pr.id_product
                        AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                                        WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)
                      ORDER BY (pa.price + pr.price) ASC, pa.id_product_attribute ASC LIMIT 1) AS ipa,
                    (SELECT im.id_image FROM {$p}image im
                      WHERE im.id_product = pr.id_product ORDER BY im.cover DESC, im.position ASC LIMIT 1) AS id_image
             FROM {$p}product pr
             INNER JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_lang = {$idLang} AND pl.id_shop = {$idTienda}
             LEFT JOIN {$p}manufacturer ma ON ma.id_manufacturer = pr.id_manufacturer
             WHERE pr.id_product IN (" . implode(',', array_map('intval', $ids)) . ')'
        ) ?: [];

        $porId = [];
        foreach ($filas as $f) {
            $porId[(int) $f['id_product']] = $f;
        }
        $rasgos = self::rasgosDe($ids, $idLang);

        // Lo que ya está en la cesta sale marcado en verde desde el principio: el «+» añade directo,
        // y al volver a la ficha tiene que verse que ya se añadió.
        $enCesta = [];
        $idCesta = (int) ($contexto->cart->id ?? 0);
        if ($idCesta > 0) {
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                "SELECT id_product, id_product_attribute FROM {$p}cart_product
                 WHERE id_cart = {$idCesta} AND id_product IN (" . implode(',', array_map('intval', $ids)) . ')'
            ) ?: [] as $c) {
                $enCesta[(int) $c['id_product'] . '-' . (int) $c['id_product_attribute']] = true;
            }
        }

        // En la tienda siempre hay moneda; en consola (las pruebas del catálogo) no, y no es motivo
        // para reventar: se escribe el importe a mano.
        $locale = $contexto->getCurrentLocale();
        $moneda = $contexto->currency->iso_code ?? '';
        $euros = static fn (float $v): string => $locale && $moneda !== ''
            ? $locale->formatPrice($v, $moneda)
            : number_format($v, 2, ',', '.') . ' €';

        $salida = [];
        foreach ($ids as $idProducto) {
            $f = $porId[$idProducto] ?? null;
            $ipa = (int) ($f['ipa'] ?? 0);
            if ($f === null || $ipa <= 0) {
                continue;
            }
            // De las marcas que lo permiten se ofrece el **pack de 6**, no la lata suelta: es la
            // unidad mínima con la que interesa que entre en la cesta (Boske y Natural Greatness,
            // por ahora). Del resto, lo que tengan activo.
            if (KcsDistribuidor::enPackDeSeis((int) ($f['id_manufacturer'] ?? 0))) {
                $ipa = self::packDeSeis($idProducto, $idTienda) ?: $ipa;
            }
            $precio = (float) Product::getPriceStatic($idProducto, true, $ipa);
            if ($precio <= 0) {
                continue;
            }
            $antes = (float) Product::getPriceStatic($idProducto, true, $ipa, 6, null, false, false);
            $salida[] = [
                'id_product' => $idProducto,
                'id_product_attribute' => $ipa,
                'nombre' => self::corto(self::sinMarca((string) $f['name'], (string) ($f['marca'] ?? ''))),
                'formato' => self::formato($ipa, $idLang),
                'precio' => round($precio, 2),
                'precio_txt' => $euros(round($precio, 2)),
                'antes_txt' => $antes > $precio + 0.01 ? $euros(round($antes, 2)) : '',
                'foto' => (string) $contexto->link->getImageLink((string) $f['link_rewrite'], (int) $f['id_image'], 'small_default'),
                'url' => (string) $contexto->link->getProductLink($idProducto, null, null, null, null, null, $ipa),
                'id_manufacturer' => (int) ($f['id_manufacturer'] ?? 0),
                'tipo' => $rasgos[$idProducto]['tipo'] ?? '',
                'necesidad' => $rasgos[$idProducto]['necesidad'] ?? '',
                'natural' => !empty($rasgos[$idProducto]['natural']),
                'con_premio' => false,
                'en_cesta' => isset($enCesta[$idProducto . '-' . $ipa]),
                'origen' => $origen[$idProducto] ?? '',
            ];
        }

        return $idPrincipal > 0 ? self::conPesos($salida, $idPrincipal, $ipaPrincipal, $euros, $idLang, $enCesta) : $salida;
    }

    /**
     * Los antiparasitarios que van por peso (pastillas, pipetas, collares por tamaño): el formato que
     * se añade depende del que se está mirando. Si con ese solo encaja un peso, se pone ese (y de
     * ese peso, el más barato: la unidad antes que el pack). Si encajan varios, **se pide el peso**:
     * en pastillas y pipetas el peso es la dosis, y no se elige a ciegas.
     *
     * Se da el mapa entero (formato del principal → formatos que encajan) para que, al cambiar de
     * formato arriba, el bloque se ajuste sin volver a preguntar al servidor.
     *
     * @param array<string, bool> $enCesta «producto-formato» que ya están en la cesta
     */
    private static function conPesos(array $articulos, int $idPrincipal, int $ipaPrincipal, callable $euros, int $idLang, array $enCesta): array
    {
        $ids = [];
        foreach ($articulos as $a) {
            if ($a['tipo'] === 'Antiparasitario') {
                $ids[] = (int) $a['id_product'];
            }
        }
        if ($ids === []) {
            return $articulos;
        }
        $formatos = KcsAntiparasitarios::formatos($ids, true);
        $principales = KcsAntiparasitarios::formatos([$idPrincipal], false)[$idPrincipal] ?? [];
        if ($principales === []) {
            $principales = [0 => ['rango' => null]];
        }

        foreach ($articulos as $i => $a) {
            $suyos = $formatos[(int) $a['id_product']] ?? [];
            $conPeso = array_filter($suyos, static fn (array $f): bool => $f['rango'] !== null);
            // Lo que distingue un formato de otro para el animal: su peso o, si no lo dice, su talla
            // (el Scalibor va por centímetros). Las cantidades (1, 3 o 6 pipetas) no cuentan.
            $clave = static fn (array $f): ?string => $f['rango'] !== null
                ? json_encode($f['rango'])
                : KcsAntiparasitarios::talla((string) $f['nombre']);
            $claves = array_unique(array_filter(array_map($clave, $suyos)));
            if ($a['tipo'] !== 'Antiparasitario' || count($suyos) < 2 || ($conPeso === [] && count($claves) < 2)) {
                continue;
            }

            $datos = [];
            foreach ($suyos as $ipa => $f) {
                $precio = round((float) Product::getPriceStatic((int) $a['id_product'], true, $ipa), 2);
                if ($precio <= 0) {
                    continue;
                }
                $antes = round((float) Product::getPriceStatic((int) $a['id_product'], true, $ipa, 6, null, false, false), 2);
                $datos[$ipa] = [
                    'etiqueta' => self::formato($ipa, $idLang) ?: (string) $f['nombre'],
                    'precio' => $precio,
                    'precio_txt' => $euros($precio),
                    'antes_txt' => $antes > $precio + 0.01 ? $euros($antes) : '',
                    'rango' => $f['rango'],
                    'clave' => (string) $clave($f),
                    // La foto y el nombre llevan a la ficha con el peso elegido.
                    'url' => (string) Context::getContext()->link->getProductLink((int) $a['id_product'], null, null, null, null, null, $ipa),
                ];
            }

            $mapa = [];
            foreach ($principales as $ipaP => $fp) {
                $opciones = array_keys(array_filter($datos, static fn (array $d): bool => KcsAntiparasitarios::solapan($fp['rango'], $d['rango'])));
                // Por peso de menor a mayor y, en el mismo peso, lo más barato primero.
                usort($opciones, static fn (int $x, int $y): int => [$datos[$x]['rango'][0] ?? 0, $datos[$x]['precio']] <=> [$datos[$y]['rango'][0] ?? 0, $datos[$y]['precio']]);
                $pesos = array_unique(array_map(static fn (int $o): string => $datos[$o]['clave'], $opciones));
                // Lo que ya está en la cesta cuenta como elegido.
                $puesto = 0;
                foreach ($opciones as $o) {
                    if (isset($enCesta[(int) $a['id_product'] . '-' . $o])) {
                        $puesto = $o;
                        break;
                    }
                }
                $mapa[$ipaP] = [
                    'elegido' => $puesto ?: (count($pesos) === 1 ? (int) $opciones[0] : 0),
                    'opciones' => array_values($opciones),
                    'puesto' => $puesto > 0,
                ];
            }

            $actual = $mapa[$ipaPrincipal] ?? reset($mapa);
            $elegido = (int) $actual['elegido'];
            $articulos[$i]['pesos'] = [
                // Para el navegador: con esto se ajusta la tarjeta al cambiar de formato arriba.
                'json' => json_encode([
                    'mapa' => (object) $mapa,
                    'datos' => (object) $datos,
                    // Su ficha sin formato, para cuando aún no hay peso elegido.
                    'base' => (string) Context::getContext()->link->getProductLink((int) $a['id_product']),
                ], JSON_UNESCAPED_UNICODE),
                // Qué se pide elegir: el peso o, si el producto va por centímetros, la talla.
                'por' => $conPeso !== [] ? 'peso' : 'talla',
                'opciones' => array_map(static fn (int $o): array => ['ipa' => $o] + $datos[$o], $actual['opciones']),
            ];
            $articulos[$i]['oculto'] = $actual['opciones'] === [];
            $articulos[$i]['en_cesta'] = (bool) $actual['puesto'];
            $articulos[$i]['id_product_attribute'] = $elegido;
            // Sin peso elegido, a la ficha sin formato: allí se elige.
            $articulos[$i]['url'] = $elegido > 0 ? $datos[$elegido]['url'] : (string) Context::getContext()->link->getProductLink((int) $a['id_product']);
            if ($elegido > 0) {
                $articulos[$i]['formato'] = $datos[$elegido]['etiqueta'];
                $articulos[$i]['precio'] = $datos[$elegido]['precio'];
                $articulos[$i]['precio_txt'] = $datos[$elegido]['precio_txt'];
                $articulos[$i]['antes_txt'] = $datos[$elegido]['antes_txt'];
            } elseif ($actual['opciones'] !== []) {
                // Sin peso elegido, el precio del más barato de los que encajan, con «desde».
                $baratos = array_map(static fn (int $o): float => $datos[$o]['precio'], $actual['opciones']);
                $articulos[$i]['precio_txt'] = $euros(min($baratos));
                $articulos[$i]['antes_txt'] = '';
                $articulos[$i]['desde'] = true;
            }
        }

        return $articulos;
    }

    /**
     * El pack de 6 de un producto, si lo tiene con stock.
     *
     * Se busca por el nombre del formato («Pack 6 x Lata de 400 gr»), que es como está puesto en el
     * catálogo. Si no hay, se devuelve 0 y manda el formato de siempre.
     */
    private static function packDeSeis(int $idProducto, int $idTienda): int
    {
        $p = _DB_PREFIX_;
        $idLang = (int) Context::getContext()->language->id;

        return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT pa.id_product_attribute
             FROM {$p}product_attribute pa
             INNER JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idLang}
             INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                   AND sa.id_shop = {$idTienda} AND sa.quantity > 0
             WHERE pa.id_product = " . (int) $idProducto . "
               AND al.name REGEXP '(^|[^0-9])6[[:space:]]*x'
               AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                               WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)
             ORDER BY pa.id_product_attribute"
        );
    }

    /**
     * Qué es cada sugerencia y para qué sirve: lo necesita el título del bloque, que promete lo que
     * la sugerencia cumple y no otra cosa.
     *
     * @param int[] $ids
     *
     * @return array<int, array{tipo: string, necesidad: string, natural: bool}>
     */
    private static function rasgosDe(array $ids, int $idLang): array
    {
        $p = _DB_PREFIX_;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT fp.id_product, fl.name AS caracteristica, fvl.value
             FROM {$p}feature_product fp
             INNER JOIN {$p}feature_lang fl ON fl.id_feature = fp.id_feature AND fl.id_lang = {$idLang}
             INNER JOIN {$p}feature_value_lang fvl ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = {$idLang}
             WHERE fp.id_product IN (" . implode(',', array_map('intval', $ids)) . ")
               AND fl.name IN ('Tipo de Producto', 'Necesidades específicas', 'Opción nutricional')"
        ) ?: [];

        $salida = [];
        foreach ($filas as $f) {
            $id = (int) $f['id_product'];
            $valor = (string) $f['value'];
            $salida[$id] = $salida[$id] ?? ['tipo' => '', 'necesidad' => '', 'natural' => false];
            if ($f['caracteristica'] === 'Tipo de Producto' && $salida[$id]['tipo'] === '') {
                $salida[$id]['tipo'] = $valor;
            } elseif ($f['caracteristica'] === 'Necesidades específicas' && $salida[$id]['necesidad'] === '') {
                $salida[$id]['necesidad'] = $valor;
            } elseif ($f['caracteristica'] === 'Opción nutricional' && $valor === 'Natural') {
                $salida[$id]['natural'] = true;
            }
        }

        return $salida;
    }

    /** El nombre del formato («Pack 12 x 400 g»), que es lo que decide si merece la pena. */
    private static function formato(int $ipa, int $idLang): string
    {
        $p = _DB_PREFIX_;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT al.name
             FROM {$p}product_attribute_combination pac
             INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = " . (int) $idLang . '
             WHERE pac.id_product_attribute = ' . (int) $ipa . '
             ORDER BY al.name'
        ) ?: [];

        return implode(' · ', array_column($filas, 'name'));
    }

    /**
     * Quita la marca del principio del nombre.
     *
     * Casi todas las sugerencias son de la misma marca que el producto que se está mirando, así que
     * repetirla tres veces solo gasta las dos líneas que hay: «Eukanuba Special Care Adulto…» se
     * queda en lo que de verdad distingue, «Special Care Adulto Digestión Sensible».
     */
    private static function sinMarca(string $nombre, string $marca): string
    {
        $marca = trim($marca);
        if ($marca === '' || mb_stripos($nombre, $marca) !== 0) {
            return $nombre;
        }
        $resto = trim(mb_substr($nombre, mb_strlen($marca)), " \t-–—·:");

        // Si al quitarla no queda nada que leer, se deja el nombre entero.
        return mb_strlen($resto) >= 8 ? $resto : $nombre;
    }

    /** En una fila de dos líneas no cabe un título entero. */
    private static function corto(string $nombre): string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? $nombre);

        return mb_strlen($nombre) > 64 ? mb_substr($nombre, 0, 62) . '…' : $nombre;
    }
}
