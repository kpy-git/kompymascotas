<?php
/**
 * Los antiparasitarios se ofrecen **entre ellos**: collar, pastilla y pipeta se combinan.
 *
 * Lo pidió Rafa el 29/09/2026: al collar Seresto, las pastillas AdTab sueltas y las pipetas
 * Advantix, y al revés; a las pastillas AdTab de gato, el Seresto de gato, las pipetas Frontline
 * Combo de gato y algo más. Siempre, sea del distribuidor que sea y cueste lo que cueste: la
 * protección completa se compra junta.
 *
 * Tres reglas:
 *   - **otra forma**: al collar no se le ofrece otro collar de la competencia, sino pastilla y pipeta;
 *   - **el mismo animal** y **pesos compatibles**: a unas pipetas de 10 a 25 kg no se le ofrece
 *     nada que solo sea para menos de 8;
 *   - el orden de Rafa (`PREFERIDOS`), y dentro de cada marca lo más barato: la pastilla suelta
 *     antes que la caja de tres.
 *
 * El peso, en pastillas y pipetas, **es la dosis**. Por eso el bloque (`KcsBloque`) no elige a
 * ciegas: si con el formato que se está mirando solo encaja un peso, lo pone; si encajan varios,
 * pide al cliente que elija el suyo.
 */
class KcsAntiparasitarios
{
    /** Lo que se ofrece primero, por animal. Se compara con el nombre del producto. */
    public const PREFERIDOS = [
        'Perro' => ['seresto', 'scalibor', 'adtab', 'advantix', 'frontline', 'vectra', 'effipro'],
        'Gato' => ['adtab', 'seresto', 'frontline combo', 'vectra felis', 'effipro'],
    ];

    /** Un peso «sin tope» se guarda con este máximo. */
    private const SIN_TOPE = 200.0;

    /**
     * Ordena los antiparasitarios que se le pueden ofrecer a otro y se queda con los que tocan.
     *
     * Los candidatos llegan ya filtrados por `KcsSugerencias::buscar()` —con stock, del mismo
     * animal también por nombre y categoría, sin patologías ni edades cruzadas, sin marcas
     * vetadas—, sin mirar distribuidor ni precio. Aquí se decide el orden: forma, peso y marca.
     *
     * @param int[] $candidatos
     *
     * @return int[]
     */
    public static function ordenar(int $idProducto, string $nombre, string $mascota, array $candidatos, int $cuantos): array
    {
        $candidatos = array_values(array_filter(array_map('intval', $candidatos)));
        if ($candidatos === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $idTienda = (int) Context::getContext()->shop->id;
        $idLang = (int) Context::getContext()->language->id;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pr.id_product, MAX(pl.name) AS nombre, MIN(pa.price + pr.price) AS desde
             FROM {$p}product pr
             INNER JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_shop = {$idTienda} AND pl.id_lang = {$idLang}
             INNER JOIN {$p}product_attribute pa ON pa.id_product = pr.id_product
             INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute AND sa.id_shop = {$idTienda} AND sa.quantity > 0
             WHERE pr.id_product IN (" . implode(',', $candidatos) . ")
               AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                               WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)
             GROUP BY pr.id_product"
        ) ?: [];
        if ($filas === []) {
            return [];
        }

        $forma = self::forma($nombre);
        $pesosSuyos = array_column(self::formatos([$idProducto], false)[$idProducto] ?? [], 'rango');
        $pesosSuyos = array_values(array_filter($pesosSuyos));
        $formatos = self::formatos(array_map('intval', array_column($filas, 'id_product')), true);

        $validos = [];
        foreach ($filas as $f) {
            $id = (int) $f['id_product'];
            $suForma = self::forma((string) $f['nombre']);
            // Otra forma: al collar, pastilla y pipeta; no el collar de la competencia.
            if ($forma !== 'otro' && $suForma === $forma) {
                continue;
            }
            // Pesos compatibles, si los dos los dicen.
            $pesos = array_values(array_filter(array_column($formatos[$id] ?? [], 'rango')));
            if (!self::compatibles($pesosSuyos, $pesos)) {
                continue;
            }
            $validos[] = ['id' => $id, 'forma' => $suForma, 'orden' => self::prioridad((string) $f['nombre'], $mascota), 'desde' => (float) $f['desde']];
        }
        usort($validos, static fn (array $a, array $b): int => [$a['orden'], $a['desde']] <=> [$b['orden'], $b['desde']]);

        // Primero una de cada forma de las tres que se combinan (collar, pastilla, pipeta), y después
        // el resto en el mismo orden: el spray es más para la casa, va detrás.
        $salida = [];
        $formasPuestas = [];
        foreach ($validos as $v) {
            if (in_array($v['forma'], ['collar', 'comprimido', 'pipeta'], true) && !isset($formasPuestas[$v['forma']])) {
                $salida[] = $v['id'];
                $formasPuestas[$v['forma']] = true;
            }
        }
        // Sin repetir marca (la caja de tres AdTab no aporta nada al lado del AdTab suelto), y solo
        // si aun así falta algo, lo que quede.
        $marcasPuestas = [];
        foreach ($validos as $v) {
            if (in_array($v['id'], $salida, true)) {
                $marcasPuestas[$v['orden']] = true;
            }
        }
        foreach ([true, false] as $sinRepetir) {
            foreach ($validos as $v) {
                if (in_array($v['id'], $salida, true) || ($sinRepetir && $v['orden'] < 99 && isset($marcasPuestas[$v['orden']]))) {
                    continue;
                }
                $salida[] = $v['id'];
                $marcasPuestas[$v['orden']] = true;
            }
        }

        return array_slice($salida, 0, $cuantos);
    }

    /** Collar, comprimido, pipeta, spray u otro, por el nombre. */
    public static function forma(string $nombre): string
    {
        $n = mb_strtolower($nombre);
        if (preg_match('/\bcollar/u', $n)) {
            return 'collar';
        }
        if (preg_match('/\b(comprimid\w*|masticables?|pastillas?|adtab|tabletas?)\b/u', $n)) {
            return 'comprimido';
        }
        if (preg_match('/\b(pipetas?|spot[\s-]?on|monodosis)\b/u', $n)) {
            return 'pipeta';
        }
        if (preg_match('/\bspray\b/u', $n)) {
            return 'spray';
        }

        return 'otro';
    }

    /**
     * El peso para el que es un formato, en kg: «de 11 a 22 Kg», «Pipeta hasta 4 kg», «Seresto
     * más de 8 kg», «Perros 10-20 Kg», «+40 Kg». Lo que no lo dice (una talla de collar en
     * centímetros, «Talla única») devuelve null: vale para cualquiera.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function rango(string $texto): ?array
    {
        $t = str_replace(',', '.', mb_strtolower($texto));
        $num = '(\d+(?:\.\d+)?)';
        if (preg_match("/de\\s*{$num}\\s*(?:kg)?\\s*a\\s*{$num}\\s*kg/u", $t, $m)
            || preg_match("/{$num}\\s*-\\s*{$num}\\s*kg/u", $t, $m)) {
            return [(float) $m[1], (float) $m[2]];
        }
        if (preg_match("/(?:hasta|menos de)\\s*{$num}\\s*kg/u", $t, $m)) {
            return [0.0, (float) $m[1]];
        }
        if (preg_match("/(?:m[aá]s de|\\+)\\s*{$num}\\s*kg/u", $t, $m)) {
            return [(float) $m[1], self::SIN_TOPE];
        }

        return null;
    }

    /**
     * La talla de un formato que no dice el peso: «S/M (48 cm)» → «48 cm», «Talla única» →
     * «única». Las cantidades («3 pipetas», «Pack 2 x…») no son talla: devuelven null.
     */
    public static function talla(string $formato): ?string
    {
        $t = mb_strtolower($formato);
        if (preg_match('/(\d+)\s*cm\b/u', $t, $m)) {
            return $m[1] . ' cm';
        }
        if (preg_match('/\btalla\s+(\w+)/u', $t, $m)) {
            return $m[1];
        }

        return null;
    }

    /** ¿Se pisan dos pesos? «de 5,5 a 11» y «más de 8» sí; «hasta 4» y «de 4 a 10», no. */
    public static function solapan(?array $a, ?array $b): bool
    {
        if ($a === null || $b === null) {
            return true;
        }

        return min($a[1], $b[1]) > max($a[0], $b[0]);
    }

    /**
     * Los formatos de unos productos con su peso. El peso sale del formato y, si el formato no lo
     * dice, del nombre del producto (el Frontline Combo de perro lleva el peso en el nombre).
     *
     * @param int[] $ids
     *
     * @return array<int, array<int, array{nombre: string, rango: ?array}>> producto => formato => …
     */
    public static function formatos(array $ids, bool $soloVendibles): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $idTienda = (int) Context::getContext()->shop->id;
        $idLang = (int) Context::getContext()->language->id;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pa.id_product, pa.id_product_attribute, MAX(pl.name) AS producto,
                    GROUP_CONCAT(al.name ORDER BY al.id_attribute SEPARATOR ' ') AS formato
             FROM {$p}product_attribute pa
             INNER JOIN {$p}product_lang pl ON pl.id_product = pa.id_product AND pl.id_shop = {$idTienda} AND pl.id_lang = {$idLang}
             LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idLang}
             WHERE pa.id_product IN (" . implode(',', $ids) . ')'
            . ($soloVendibles ? " AND EXISTS (SELECT 1 FROM {$p}stock_available sa
                                   WHERE sa.id_product_attribute = pa.id_product_attribute AND sa.id_shop = {$idTienda} AND sa.quantity > 0)
                                 AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa
                                   WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)" : '') . '
             GROUP BY pa.id_product, pa.id_product_attribute'
        ) ?: [];

        $salida = [];
        foreach ($filas as $f) {
            $formato = (string) $f['formato'];
            $salida[(int) $f['id_product']][(int) $f['id_product_attribute']] = [
                'nombre' => $formato,
                'rango' => self::rango($formato) ?? self::rango((string) $f['producto']),
            ];
        }

        return $salida;
    }

    /** ¿Algún peso del uno encaja con alguno del otro? Si uno no dice pesos, vale. */
    private static function compatibles(array $unos, array $otros): bool
    {
        if ($unos === [] || $otros === []) {
            return true;
        }
        foreach ($unos as $a) {
            foreach ($otros as $b) {
                if (self::solapan($a, $b)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function prioridad(string $nombre, string $mascota): int
    {
        $n = mb_strtolower($nombre);
        foreach (self::PREFERIDOS[$mascota] ?? [] as $i => $clave) {
            if (strpos($n, $clave) !== false) {
                return $i;
            }
        }

        return 99;
    }
}
