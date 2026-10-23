<?php
/**
 * A qué combinaciones les vale un cupón. Es la regla de negocio de Kompy, sin PrestaShop dentro,
 * para poder probarla sin tienda (`tests/elegibles.php`). La consulta a la tienda está en
 * `KpcAlcance`, que le pasa aquí cada producto con sus datos.
 *
 * Cómo se lee una promoción:
 *
 *     (marcas con sus gamas  Y  categorías  Y  características  Y  tamaños)  +  la lista
 *     de eso, los niveles de pack que se pidan (sueltos, packs de 2…)  −  lo excluido
 *
 *   - los **filtros** se cruzan: «Royal Canin» + «Gato» es Royal Canin de gato, no todo Royal
 *     Canin más todo lo de gato. Dentro de cada uno vale cualquiera («Boske o Eukanuba»); en las
 *     características, uno por cada una que se pida («Gato» y «Húmeda o Snacks»);
 *   - las **gamas** afinan su marca y solo la suya: «Royal Canin (Razas gato) + Farmina» es Royal
 *     Canin de razas gato y toda Farmina. Una categoría que no es de una marca elegida se cruza con
 *     todo, como siempre;
 *   - el **tamaño** («saco 12 kg», «lata 70 g») es de cada formato, y vale igual para el saco y
 *     sus packs (ver `KpcFormatos`);
 *   - la **lista** (ids, EAN o referencias que pasa un proveedor) suma productos o formatos sueltos;
 *   - los **niveles de pack** («solo sueltos», «solo packs de 2») valen para todo;
 *   - lo **excluido** se cae siempre, venga de donde venga.
 *
 * Y las reglas de Kompy de siempre: los packs heredan la promoción de su formato suelto; la
 * «2ª unidad» no entra en los packs; un formato con Special Price no lleva cupón encima (salvo que
 * el cupón diga lo contrario); los formatos OUTLET se quedan fuera (ya van rebajados).
 *
 * Tres tipos: `cupon` (código), `segunda_unidad` y `special_price` (el precio rebajado en la ficha).
 * El Special Price no compite con los otros dos: manda siempre sobre ellos (un formato con Special
 * Price no lleva cupón), y entre dos Special Price, el que ya estaba.
 */
class KpcElegibles
{
    /** Ámbitos admitidos en `kpy_coupon_scope`. */
    public const AMBITOS = [
        'marca', 'categoria', 'caracteristica',          // filtros: se cruzan
        'producto', 'combinacion',                       // la lista: suma
        'excluir_producto', 'excluir_combinacion',       // fuera, siempre
    ];

    /** Los canales que puede tener un cupón para entrar ya puesto. */
    public const CANALES = ['shopping', 'email', 'social'];

    /** Los tipos de promoción. */
    public const TIPOS = ['cupon', 'segunda_unidad', 'special_price', 'regalo'];

    /** Los que descuentan en el precio (no se apilan entre sí en el mismo formato). */
    public const DE_PRECIO = ['cupon', 'segunda_unidad', 'special_price'];

    /** ¿Es un Special Price (precio rebajado en la ficha) y no una promo de cesta? */
    public static function esPrecio(array $cupon): bool
    {
        return ($cupon['tipo'] ?? 'cupon') === 'special_price';
    }

    /** ¿Es un «regalo con la compra»? (una línea más a 0 €, no un descuento de precio: se apila con todo). */
    public static function esRegalo(array $cupon): bool
    {
        return ($cupon['tipo'] ?? 'cupon') === 'regalo';
    }

    /**
     * @param array{excluir_especial?: bool, incluir_packs?: bool, sin_outlet?: bool, tipo?: string,
     *              ambitos: array<int, array{tipo: string, id: int}>, rasgos?: array<int, int>,
     *              gamas?: array<int, int[]>, generales?: int[], tamanos?: string|string[], packs?: string} $cupon
     *              `rasgos`: id de valor de característica => id de característica, para agrupar las
     *              que se piden (Mascota, Tipo…). `gamas`: por marca, las categorías que la afinan;
     *              `generales`: las demás (si no vienen, todas las categorías son generales). Las dos
     *              ya con sus subcategorías. `tamanos`: los elegidos («saco 12 kg»); `packs`: los
     *              niveles que entran («1,2»; vacío = todos)
     * @param array{id_product: int, id_manufacturer: int, categorias: int[], caracteristicas?: int[]} $producto
     *              `categorias`: las suyas; `caracteristicas`: ids de valor
     * @param array<int, array{ipa: int, nombre?: string, especial: bool, nivel?: int, padre?: ?int,
     *              base?: ?int, tam?: string}> $combinaciones  un producto sin combinaciones va con
     *              una sola, `ipa` 0. Si no traen nivel y tamaño, se sacan de su nombre.
     *
     * @return int[] las combinaciones a las que se aplica (vacío = el cupón no pinta nada aquí)
     */
    public static function combinaciones(array $cupon, array $producto, array $combinaciones): array
    {
        $porTipo = self::porTipo($cupon['ambitos'] ?? []);
        $idProducto = (int) ($producto['id_product'] ?? 0);
        if ($idProducto <= 0 || in_array($idProducto, $porTipo['excluir_producto'], true)) {
            return [];
        }
        $formatos = self::formatos($combinaciones);

        $pasa = self::pasaFiltros($porTipo, (array) ($cupon['rasgos'] ?? []), $producto,
            $cupon['gamas'] ?? [], $cupon['generales'] ?? null);
        $tamanos = self::tamanos($cupon);
        $todoListado = in_array($idProducto, $porTipo['producto'], true);

        $salida = [];
        foreach ($formatos as $ipa => $f) {
            $porFiltro = $pasa && ($tamanos === [] || in_array($f['tam'], $tamanos, true));
            $porLista = $todoListado || ($ipa > 0 && in_array($ipa, $porTipo['combinacion'], true));
            if ($porFiltro || $porLista) {
                $salida[$ipa] = true;
            }
        }
        if ($salida === []) {
            return [];
        }

        // Los packs heredan la promoción de lo que llevan dentro, sin tener que decirlo: si entra el
        // saco de 12 kg (por la lista, por ejemplo), entra el pack de dos sacos de 12 kg.
        if ($cupon['incluir_packs'] ?? true) {
            do {
                $antes = count($salida);
                foreach ($formatos as $ipa => $f) {
                    if (!isset($salida[$ipa]) && (($f['padre'] !== null && isset($salida[$f['padre']]))
                        || ($f['base'] !== null && isset($salida[$f['base']])))) {
                        $salida[$ipa] = true;
                    }
                }
            } while (count($salida) > $antes);
        }

        // Los niveles que entran: los elegidos (solo sueltos, solo packs de 2…). La «2ª unidad», solo
        // sueltos: el pack ya es el descuento por cantidad.
        $niveles = self::niveles($cupon);
        foreach (array_keys($salida) as $ipa) {
            if (!self::nivelEntra($formatos[$ipa]['nivel'], $niveles)) {
                unset($salida[$ipa]);
            }
        }

        foreach (array_keys($salida) as $ipa) {
            $f = $formatos[$ipa];
            // Los formatos OUTLET ya van rebajados (saco dañado, fin de lote): fuera, salvo que se pida.
            // (Un OUTLET pegado en la lista con su propio formato sí entra: se ha pedido a propósito.)
            if ((($cupon['sin_outlet'] ?? true) && self::esOutlet($f['nombre']) && !in_array($ipa, $porTipo['combinacion'], true))
                // Lo excluido se cae aunque haya entrado como pack de otro.
                || in_array($ipa, $porTipo['excluir_combinacion'], true)
                // Un Special Price ya es la promoción de ese formato: no se le suma el cupón.
                || (!empty($cupon['excluir_especial']) && $f['especial'])) {
                unset($salida[$ipa]);
            }
        }

        return array_map('intval', array_keys($salida));
    }

    /**
     * Los formatos de un producto con su nivel de pack, su suelto y su tamaño. Si no vienen ya
     * calculados (con la tabla de packs, desde `KpcAlcance`), se sacan de los nombres.
     *
     * @return array<int, array{nombre: string, especial: bool, nivel: int, padre: ?int, base: ?int, tam: string}>
     */
    public static function formatos(array $combinaciones): array
    {
        $calculados = [];
        foreach ($combinaciones as $c) {
            if (!array_key_exists('nivel', $c)) {
                $calculados = KpcFormatos::niveles($combinaciones);
                break;
            }
        }
        $salida = [];
        foreach ($combinaciones as $c) {
            $ipa = (int) ($c['ipa'] ?? 0);
            $f = $calculados[$ipa] ?? $c;
            $salida[$ipa] = [
                'nombre' => (string) ($c['nombre'] ?? ''),
                'especial' => !empty($c['especial']),
                'nivel' => max(1, (int) ($f['nivel'] ?? 1)),
                'padre' => isset($f['padre']) ? (int) $f['padre'] : null,
                'base' => isset($f['base']) ? (int) $f['base'] : null,
                'tam' => (string) ($f['tam'] ?? KpcFormatos::tamano((string) ($c['nombre'] ?? ''))),
            ];
        }

        return $salida;
    }

    /**
     * Los tamaños elegidos («saco 12 kg»), de la columna `tamanos` (uno por línea) o de una lista.
     *
     * @return string[]
     */
    public static function tamanos(array $cupon): array
    {
        $tamanos = $cupon['tamanos'] ?? [];
        if (!is_array($tamanos)) {
            $tamanos = preg_split('/\R/u', (string) $tamanos) ?: [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($t) => KpcFormatos::normalizar((string) $t),
            $tamanos
        ), static fn ($t) => $t !== '')));
    }

    /**
     * Los niveles de pack que entran, como tramos [desde, hasta]; `null` = todos. En `packs`: «1»
     * son los sueltos, «2» los packs de 2, «2+» cualquier pack. La 2ª unidad, siempre solo sueltos.
     *
     * @return array<int, array{0: int, 1: int}>|null
     */
    public static function niveles(array $cupon): ?array
    {
        if (($cupon['tipo'] ?? 'cupon') === 'segunda_unidad') {
            return [[1, 1]];
        }
        // Un regalo lo disparan los sueltos; los packs solo si se pide («los packs también regalan»).
        if (self::esRegalo($cupon) && empty($cupon['regalo_packs'])) {
            return [[1, 1]];
        }
        $tramos = [];
        foreach (explode(',', (string) ($cupon['packs'] ?? '')) as $trozo) {
            if (preg_match('/^\s*([1-9]\d?)(\+?)\s*$/', $trozo, $m)) {
                $tramos[] = [(int) $m[1], $m[2] === '+' ? PHP_INT_MAX : (int) $m[1]];
            }
        }

        return $tramos === [] ? null : $tramos;
    }

    /** ¿Entra un formato de este nivel de pack? (`$tramos` de `niveles()`) */
    public static function nivelEntra(int $nivel, ?array $tramos): bool
    {
        if ($tramos === null) {
            return true;
        }
        foreach ($tramos as [$desde, $hasta]) {
            if ($nivel >= $desde && $nivel <= $hasta) {
                return true;
            }
        }

        return false;
    }

    /** ¿Es un formato OUTLET? «Saco de 12 Kg - OUTLET». */
    public static function esOutlet(string $nombre): bool
    {
        return KpcFormatos::esOutlet($nombre);
    }

    /**
     * ¿El producto cumple los filtros? Sin ningún filtro, no: una promoción sin marca, categoría ni
     * característica solo llega a lo que diga su lista (nunca a toda la tienda por descuido).
     *
     * @param array<string, int[]> $porTipo los ámbitos por tipo (`porTipo()`)
     * @param array<int, int> $rasgos id de valor de característica => id de característica
     * @param array<int, int[]> $gamas por marca, las categorías que la afinan (con subcategorías)
     * @param int[]|null $generales las categorías que se cruzan con todo (con subcategorías); si no
     *        vienen, todas las de la promoción
     */
    public static function pasaFiltros(array $porTipo, array $rasgos, array $producto, array $gamas = [], ?array $generales = null): bool
    {
        $generales = $generales ?? $porTipo['categoria'];
        if ($porTipo['marca'] === [] && $generales === [] && $porTipo['caracteristica'] === []) {
            return false;
        }
        $suyas = array_map('intval', (array) ($producto['categorias'] ?? []));
        if ($porTipo['marca'] !== []) {
            $marca = (int) ($producto['id_manufacturer'] ?? 0);
            if (!in_array($marca, $porTipo['marca'], true)) {
                return false;
            }
            // Las gamas de su marca, si se eligió alguna: «Royal Canin, solo Razas gato».
            $deSuMarca = array_map('intval', (array) ($gamas[$marca] ?? []));
            if ($deSuMarca !== [] && array_intersect($suyas, $deSuMarca) === []) {
                return false;
            }
        }
        if ($generales !== [] && array_intersect($suyas, array_map('intval', $generales)) === []) {
            return false;
        }
        // Características: una de cada grupo que se pida (Mascota: Gato; Tipo: Húmeda o Snacks).
        $suyas = array_map('intval', (array) ($producto['caracteristicas'] ?? []));
        foreach (self::grupos($porTipo['caracteristica'], $rasgos) as $valores) {
            if (array_intersect($suyas, $valores) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * Los valores de característica pedidos, agrupados por característica.
     *
     * @param int[] $valores
     * @param array<int, int> $rasgos id de valor => id de característica
     *
     * @return array<int, int[]>
     */
    public static function grupos(array $valores, array $rasgos): array
    {
        $grupos = [];
        foreach ($valores as $v) {
            $grupos[(int) ($rasgos[$v] ?? -$v)][] = (int) $v;
        }

        return $grupos;
    }

    /**
     * Los ámbitos de un cupón, por tipo: `['marca' => [178], 'categoria' => [], …]`.
     *
     * @return array<string, int[]>
     */
    public static function porTipo(array $ambitos): array
    {
        $salida = array_fill_keys(self::AMBITOS, []);
        foreach ($ambitos as $a) {
            $tipo = (string) ($a['tipo'] ?? '');
            $id = (int) ($a['id'] ?? 0);
            if ($id > 0 && isset($salida[$tipo]) && !in_array($id, $salida[$tipo], true)) {
                $salida[$tipo][] = $id;
            }
        }

        return $salida;
    }

    /** ¿Llega ya aplicado? Por canal (Google Shopping, email…) o porque el cupón lo diga. */
    public static function aplicadoDeEntrada(array $cupon, string $canal): bool
    {
        if (!empty($cupon['auto_web'])) {
            return true;
        }
        $canales = array_filter(array_map('trim', explode(',', (string) ($cupon['auto_canal'] ?? ''))));

        return $canal !== '' && in_array($canal, $canales, true);
    }

    /**
     * El canal de la visita, a partir de los parámetros de la URL: lo que usa Kompy para decidir si
     * el cupón entra solo («viene de Shopping», «viene de un email», «viene de Instagram»).
     *
     * @param array<string, mixed> $parametros normalmente `$_GET`
     */
    public static function canal(array $parametros): string
    {
        $fuente = mb_strtolower(trim((string) ($parametros['utm_source'] ?? '')));
        $medio = mb_strtolower(trim((string) ($parametros['utm_medium'] ?? '')));
        if (isset($parametros['gclid']) || isset($parametros['srsltid'])
            || in_array($fuente, ['google', 'google_shopping', 'googleshopping', 'shopping'], true)
            || in_array($medio, ['cpc', 'shopping', 'paid'], true)) {
            return 'shopping';
        }
        if (in_array($fuente, ['email', 'mail', 'newsletter', 'connectif', 'klaviyo', 'mailchimp', 'brevo', 'sendinblue'], true)
            || in_array($medio, ['email', 'mail', 'newsletter'], true)) {
            return 'email';
        }
        if (isset($parametros['fbclid']) || isset($parametros['ttclid'])
            || in_array($fuente, ['facebook', 'instagram', 'meta', 'fb', 'ig', 'tiktok', 'pinterest'], true)
            || in_array($medio, ['social', 'paid_social', 'social_paid'], true)) {
            return 'social';
        }

        return '';
    }

    /** «10» o «12,5»: el porcentaje como se escribe. */
    public static function pct(float $porcentaje): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($porcentaje, 1, '.', ''), '0'), '.'));
    }

    /** El descuento en euros de un precio, redondeado a céntimos. */
    public static function descuento(float $precio, float $porcentaje): float
    {
        return round($precio * $porcentaje / 100, 2);
    }

    /**
     * Cuánto descuenta de verdad una promoción, para poder compararlas entre sí.
     *
     * Un cupón del 10 % descuenta el 10 % de todo lo que se lleve. Una «2ª unidad al 30 %» descuenta
     * como mucho el 15 % (media unidad de cada par), y solo si el cliente lleva dos. Sin esto, un
     * cupón del 12 % parecería peor que una 2ª unidad del 20 %, y no lo es.
     */
    public static function efectivo(array $cupon): float
    {
        $pct = (float) ($cupon['porcentaje'] ?? 0);
        if (self::esRegalo($cupon)) {
            return 0.0; // No descuenta en el precio: nunca manda sobre un cupón ni una 2ª unidad.
        }

        return ($cupon['tipo'] ?? 'cupon') === 'segunda_unidad' ? $pct / 2 : $pct;
    }

    /** Orden de mando entre promociones: la que más descuenta de verdad; a igualdad, la más antigua. */
    public static function manda(array $a, array $b): int
    {
        $orden = self::efectivo($b) <=> self::efectivo($a);

        return $orden !== 0 ? $orden : ((int) $a['id_kpy_coupon'] <=> (int) $b['id_kpy_coupon']);
    }
}
