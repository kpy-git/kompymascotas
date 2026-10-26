<?php
/**
 * Las etiquetas (flags) que salen sobre la foto del producto —en los listados, en la ficha y en el
 * buscador— y el chip de cada tarjeta de formato en la ficha.
 *
 * Son las mismas promociones que ya hay en la ficha, dichas en dos palabras y con el mismo número
 * que se le manda a Google, para que la tienda y Shopping no se contradigan:
 *
 *   Special Price → «Special Price»  (la de `kpyspecialprices`; en Google es `sale_price`: precio
 *                                     tachado y etiqueta OFERTA). Con nombre propio, «Liquidación»,
 *                                     «Outlet» o «Bajada de precio».
 *   Cupón         → «Código −10 %»   (en Google es una promoción con código: el precio de la ficha
 *                                     NO lleva el descuento, que es lo que exige la política)
 *   2ª unidad     → «2ª ud. −30 %»   (en Google, `buy_m_get_n_percent_off`)
 *   Regalo        → «+ 3 kg gratis»  (o «+ regalo» si regala otra cosa)
 *
 * **Se calculan en cada petición, de las tablas propias** (2.5.0, como pide Julián y como hace
 * `kpyspecialprices`): `kpy_coupon` (las promociones en vigor, en orden de mando), `kpy_coupon_item`
 * (el reparto: qué formato lleva cada una), `kpy_coupon_precio` (los Special Price propios, para el
 * nombre) y `kpy_coupon_regalo` (qué regala cada formato); y de `kpy_special_price` (la tabla de
 * datos del Special Price de Kompy, solo lectura) sale si el precio está rebajado de verdad. Una
 * consulta por tabla y por página, en caché estática. Hasta la 2.4 se escribían filas en las tablas
 * de `kpyproductflags`; `limpiarTablasAjenas()` las borra.
 *
 * Qué va con qué (Rafa, 30/09/2026):
 *
 *   - **Con Special Price no sale nada más que hable de precio**: ni «Oferta −X %», ni «Bajada de
 *     precio», ni «Ahorro XL», ni las rebajas de PrestaShop. Sí pueden ir con ella «Top ventas»,
 *     «Nuevo» y las de popularidad, y «agotado». Y el regalo, que no es de precio.
 *   - **Cada formato, su promoción.** Si el saco de 12 kg tiene Special Price y el de 3 kg una 2ª
 *     unidad, en la ficha la foto dice la del formato elegido (y cada tarjeta de formato, la suya);
 *     en los listados y en el buscador, que enseñan el producto entero, sale la que manda.
 *
 * Contrato con `kpypromolanding` (va detrás en el hook y recolorea por campaña): las claves
 * `kpy_special_price` y `kpc_0`, `kpc_1`…; los `type` `kpc-cupon`, `kpc-segunda`, `kpc-regalo`,
 * `kpc-especial`, `kpc-liquidacion`, `kpc-outlet`, `kpc-bajada`; los colores de `COLORES`; y el
 * campo `kpc` de cada etiqueta propia: `['tipo' => cupon|segunda_unidad|regalo|special_price,
 * 'id_kpy_coupon' => int (0 en un Special Price cargado por SQL), 'pct' => float]`.
 *
 * Lo que Google pone por su cuenta («Bajada de precio», «Popular», «Envío gratis») no se manda:
 * sale del historial de precios y del comportamiento. Aquí solo se dice lo que es verdad en la
 * tienda, que es justo lo que hace que Google no rechace las promociones.
 *
 * La regla (qué sale, en qué orden, qué chip lleva cada formato) es pura y se prueba sin tienda
 * (`tests/flags.php`): `elegir`, `componer`, `chip`, `deLaPromo`, `etiquetaDeEspecial`,
 * `vestirEspecial`, `corto`.
 */
class KpcFlags
{
    /**
     * A partir de qué descuento se anuncia algo, en %.
     *
     * PriceShape mueve los precios a diario y un 2 o un 3 % es ruido, no una oferta: anunciarlo
     * gasta la palabra «Oferta» y el cliente no ve la diferencia. El mismo listón vale para lo que
     * se le manda a Google.
     */
    public const MINIMO = 5;

    /** Los mismos colores que la ficha, para que el cliente reconozca la promoción (fondo). */
    public const COLORES = [
        'kpc-especial' => '#FFEAB7',
        'kpc-cupon' => '#7B5CB0',
        'kpc-segunda' => '#2F6B34',
        'kpc-regalo' => '#7F4AFF',
        // Un Special Price con nombre propio (2.3.0): «Liquidación» en rojo oscuro, «Bajada de precio»
        // en el rojo de siempre de la ficha.
        'kpc-liquidacion' => '#B3261E',
        'kpc-outlet' => '#3A3A3A',
        'kpc-bajada' => '#C23A2C',
    ];

    /** El chip de la tarjeta de formato es estrecho: como mucho estos caracteres. */
    public const CHIP_MAX = 14;

    /** El texto va en blanco, salvo en la crema del Special Price, que va en rojo como en la ficha. */
    private const TEXTO = ['kpc-especial' => '#B8322A'];

    /**
     * Lo que puede ir junto a «Special Price»: lo que no habla de precio. Por su nombre, en minúsculas
     * y sin tildes ni espacios, que es como llegan de `kpyproductflags` («topventas») y del core
     * («new», «out_of_stock»). `innovacin` es «Innovación» después de pasar por `kpyproductflags`,
     * que se come la tilde.
     */
    private const JUNTO_AL_ESPECIAL = [
        'outofstock', 'new', 'nuevo', 'topventas', 'popular', 'tendencia', 'innovacion', 'innovacin',
        'pack', 'onlineonly',
    ];

    /**
     * Lo que solo sale en la ficha del producto, nunca en un listado (Rafa, 07/10/2026): «Ahorro XL»
     * habla del formato de más peso, y la tarjeta del listado enseña el precio de otro formato.
     */
    private const SOLO_EN_SU_FICHA = ['ahorroxl'];

    /** @var array<int, array<int, array{etiqueta: string, id: int}>>|null caché de `etiquetasEspeciales()` */
    private static ?array $etiquetasEspeciales = null;

    /** @var array<int, array>|null caché de `promos()`: id de producto => la promoción que manda */
    private static ?array $promos = null;

    /** @var array<int, array<int|string, array>>|null caché de `porFormato()`: producto => [clave => promoción] */
    private static ?array $porFormato = null;

    /** @var array<int, array<int, float>>|null caché de `especiales()`: producto => [formato => old_discount] */
    private static ?array $especiales = null;

    // ------------------------------------------------------------------------------------------
    // Lo que pintan los hooks
    // ------------------------------------------------------------------------------------------

    /**
     * Lo último que se hace con las etiquetas de un producto antes de pintarlas (este módulo va
     * detrás de `kpyproductflags` y `kpyspecialprices` en `actionProductFlagsModifier`, y delante de
     * `kpypromolanding`):
     *
     *   1. la «Special Price» de `kpyspecialprices` se quita si el precio no está rebajado de verdad
     *      (promoción terminada, o PriceShape por encima); si lo está, se llama «Special Price» o
     *      como se llame su promoción («Liquidación», «Outlet», «Bajada de precio»), con su color;
     *   2. las de cupón, 2ª unidad y regalo se **calculan** de las tablas propias: fuera de su ficha
     *      (listados, buscador, sugerencias) la promoción que manda en el producto; en su ficha, la
     *      del formato elegido y la de su regalo. Si todavía llega una fila vieja de la tabla de
     *      `kpyproductflags` con nuestro texto, se tira (saldría dos veces);
     *   3. «Ahorro XL» solo en su ficha: en los listados, fuera (ver `SOLO_EN_SU_FICHA`);
     *   4. con Special Price, fuera todo lo que habla de precio (ver `JUNTO_AL_ESPECIAL`);
     *   5. en orden: Special Price, la promoción, las demás y «agotado» al final.
     *
     * @param array $flags    las etiquetas, por referencia (así llega el hook del core)
     * @param array $producto el array crudo del producto (`id_product`, `id_product_attribute`)
     */
    public static function ajustar(array &$flags, array $producto, ?Kpycoupons $modulo = null): void
    {
        $idProducto = (int) ($producto['id_product'] ?? 0);
        if ($idProducto <= 0) {
            return;
        }
        $ipa = (int) ($producto['id_product_attribute'] ?? 0);
        $enSuFicha = self::enSuFicha($idProducto);

        // 1. Special Price, solo si rebaja de verdad; con el nombre de su promoción si lo tiene.
        $especial = $flags['kpy_special_price'] ?? null;
        if ($especial !== null) {
            $pct = self::pctEspecialDe($idProducto, $ipa);
            $especial = $pct >= self::MINIMO
                ? self::vestirEspecial((array) $especial, self::etiquetaEspecial($idProducto, $ipa, $modulo, (float) $pct))
                : null;
        }

        // 2. Las nuestras, de lo guardado en las tablas propias.
        $propias = [];
        foreach (self::elegir(self::porFormato()[$idProducto] ?? [], KpcAlcance::clave($idProducto, $ipa), $enSuFicha) as $promo) {
            $propias[] = self::deLaPromo($promo, $modulo);
        }

        // 3, 4 y 5.
        $flags = self::componer($flags, $especial, $propias, $enSuFicha);
    }

    /**
     * El chip de la tarjeta de un formato en la ficha (`displayProductAttributeFlags`): «Cupón −10 %»
     * (lila), «2ª ud. −30 %» (verde) o, si el formato solo tiene regalo, «+ 3 kg gratis» / «+ regalo»
     * (violeta). Un formato lleva una sola promoción de cesta; el regalo convive con el Special Price.
     * Con **solo Special Price**, `null`: el tema ya pinta «Precio especial» en la tarjeta desde
     * `$modules.kpyspecialprices` (el nombre «Liquidación» sale sobre la foto y bajo el precio).
     * Texto de `CHIP_MAX` caracteres como mucho.
     *
     * @param int $ipa el formato (0 si el producto no tiene combinaciones)
     *
     * @return array{type: string, label: string, color: string, background: string, kpc: array}|null
     */
    public static function deFormato(int $idProducto, int $ipa, Kpycoupons $modulo): ?array
    {
        if ($idProducto <= 0) {
            return null;
        }

        return self::chip(self::porFormato()[$idProducto] ?? [], KpcAlcance::clave($idProducto, $ipa), $modulo);
    }

    // ------------------------------------------------------------------------------------------
    // Listados, buscador y guiones
    // ------------------------------------------------------------------------------------------

    /**
     * La etiqueta de promoción de un producto, o `null` si no tiene ninguna. Es lo que cuenta
     * `bin/etiquetas.php`: si tiene Special Price, esa; si no, la de su cupón, su 2ª unidad o su regalo.
     *
     * @param array $producto el array crudo del producto (`actionProductFlagsModifier`)
     *
     * @return array{type: string, label: string, color: string, background: string, kpc: array}|null
     */
    public static function delProducto(array $producto, Kpycoupons $modulo): ?array
    {
        $todas = self::delListado($producto, $modulo);

        return $todas[0] ?? null;
    }

    /**
     * Las etiquetas de promoción de un producto tal como salen en un listado: «Special Price» si
     * algún formato la tiene de verdad, y la promoción que manda en el producto (de otro formato) detrás.
     *
     * @return array<int, array{type: string, label: string, color: string, background: string, kpc: array}>
     */
    public static function delListado(array $producto, Kpycoupons $modulo): array
    {
        $idProducto = (int) ($producto['id_product'] ?? 0);
        if ($idProducto <= 0) {
            return [];
        }
        $ipa = (int) ($producto['id_product_attribute'] ?? 0);
        $salida = [];
        $pct = self::pctEspecialDe($idProducto, $ipa);
        if ($pct >= self::MINIMO) {
            $salida[] = self::etiquetaEspecial($idProducto, $ipa, $modulo, (float) $pct);
        }
        foreach (self::elegir(self::porFormato()[$idProducto] ?? [], KpcAlcance::clave($idProducto, $ipa), false) as $promo) {
            $salida[] = self::deLaPromo($promo, $modulo);
        }

        return $salida;
    }

    /**
     * La etiqueta del Special Price de un formato (o del producto, con 0): «Special Price» en crema o,
     * si su promoción tiene nombre propio, «Liquidación», «Outlet» o «Bajada de precio» con su color.
     *
     * @param float $pct el % que rebaja de verdad (va en `kpc.pct`)
     *
     * @return array{type: string, label: string, color: string, background: string, kpc: array}
     */
    public static function etiquetaEspecial(int $idProducto, int $ipa, ?Kpycoupons $modulo = null, float $pct = 0.0): array
    {
        $propia = self::etiquetasEspeciales()[$idProducto] ?? [];
        $e = $propia[$ipa] ?? null;
        if ($e === null && $ipa === 0 && $propia !== []) {
            // Sin formato (el buscador): la primera con nombre propio, o la primera que haya.
            foreach ($propia as $otra) {
                if ($otra['etiqueta'] !== '') {
                    $e = $otra;
                    break;
                }
            }
            $e ??= reset($propia);
        }

        return self::etiquetaDeEspecial((string) ($e['etiqueta'] ?? ''), (int) ($e['id'] ?? 0), $pct, $modulo);
    }

    /**
     * Lo que el buscador de Doofinder necesita para pintar las promociones igual que la tienda.
     *
     * El feed de Doofinder va **por producto**, con el precio de su formato por defecto, así que:
     *
     *   kpy_especial         «1» si algún formato tiene un Special Price que rebaja de verdad: la
     *                        etiqueta «Precio especial».
     *   kpy_precio_habitual  el precio habitual («60,19 €»), solo si el formato por defecto (el que
     *                        enseña la tarjeta) es uno de los del Special Price. Es el que va tachado
     *                        junto al precio en amarillo, igual que en la ficha; si el Special Price
     *                        es de otro formato, la tarjeta lleva la etiqueta y su precio normal.
     *   kpy_promo            «2ª ud. −30 %», «Código −10 %» o «+ 3 kg gratis», si tiene cupón, 2ª
     *                        unidad o regalo.
     *   kpy_promo_tipo       «segunda», «cupon» o «regalo», para el color.
     *
     * Solo salen los productos que tienen algo: el resto se quedan con esas columnas vacías.
     *
     * @return array<int, array{kpy_especial: string, kpy_precio_habitual: string, kpy_promo: string, kpy_promo_tipo: string}>
     */
    public static function paraDoofinder(Kpycoupons $modulo): array
    {
        $vacio = ['kpy_especial' => '', 'kpy_precio_habitual' => '', 'kpy_promo' => '', 'kpy_promo_tipo' => ''];
        $salida = [];
        foreach (self::promos() as $idProducto => $promo) {
            $salida[$idProducto] = ['kpy_promo' => self::deLaPromo($promo, $modulo)['label'],
                'kpy_promo_tipo' => ['segunda_unidad' => 'segunda', 'regalo' => 'regalo'][$promo['tipo']] ?? 'cupon'] + $vacio;
        }
        foreach (self::especiales() as $idProducto => $formatos) {
            if (self::pctEspecialDe((int) $idProducto, 0) < self::MINIMO) {
                continue;
            }
            $fila = ($salida[$idProducto] ?? $vacio);
            $fila['kpy_especial'] = '1';
            // Con nombre propio («Liquidación»), y sin otra promoción que decir, el buscador lo dice.
            $propia = self::etiquetaEspecial((int) $idProducto, 0, $modulo);
            if ($propia['type'] !== 'kpc-especial' && $fila['kpy_promo'] === '') {
                $fila['kpy_promo'] = $propia['label'];
                $fila['kpy_promo_tipo'] = substr($propia['type'], 4);
            }
            $defecto = (int) Product::getDefaultAttribute((int) $idProducto);
            if ($defecto > 0 && isset($formatos[$defecto]) && self::pctDeFormato((int) $idProducto, $defecto, $formatos[$defecto]) >= self::MINIMO) {
                $fila['kpy_precio_habitual'] = self::euros(self::habitual((int) $idProducto, $defecto, $formatos[$defecto]));
            }
            $salida[(int) $idProducto] = $fila;
        }

        return $salida;
    }

    // ------------------------------------------------------------------------------------------
    // La regla, pura (se prueba sin tienda)
    // ------------------------------------------------------------------------------------------

    /**
     * Qué promociones propias se anuncian sobre la foto de un producto, con lo que `porFormato()`
     * sabe de él (clave del formato => promoción; el regalo en la clave «formato-regalo»):
     *
     *   - **en su ficha**, las del formato elegido: su cupón o su 2ª unidad y, detrás, su regalo
     *     (y nada de los otros formatos, que ya tienen su tarjeta);
     *   - **fuera de ella** (listados, buscador, sugerencias), **una**: la que manda en el producto,
     *     la primera en orden de mando de las de sus formatos; un cupón o una 2ª unidad antes que un
     *     regalo (el regalo solo manda si es lo único que hay).
     *
     * @param array<int|string, array> $formatos lo de `porFormato()` para ese producto
     * @param int                      $clave    el formato elegido (la combinación, o el producto en
     *                                           negativo si no tiene: `KpcAlcance::clave`)
     *
     * @return array<int, array> las promociones, en el orden en que se pintan
     */
    public static function elegir(array $formatos, int $clave, bool $enSuFicha): array
    {
        if ($enSuFicha) {
            $salida = [];
            if (isset($formatos[$clave])) {
                $salida[] = $formatos[$clave];
            }
            if (isset($formatos[$clave . '-regalo'])) {
                $salida[] = $formatos[$clave . '-regalo'];
            }

            return $salida;
        }
        $manda = null;
        foreach ($formatos as $promo) {
            if (!empty($promo['solo_ficha'])) {
                continue; // Un cupón por canal solo se anuncia en su ficha.
            }
            if ($manda === null || (($manda['tipo'] ?? '') === 'regalo' && ($promo['tipo'] ?? '') !== 'regalo')) {
                $manda = $promo;
            }
        }

        return $manda !== null ? [$manda] : [];
    }

    /**
     * Los pasos 2 a 5 de `ajustar()`, con los datos ya decididos: las etiquetas que llegan de los
     * demás módulos y del core, el Special Price ya vestido (o `null` si no rebaja), las nuestras ya
     * hechas (`deLaPromo`) y si se está pintando el producto en su propia ficha.
     *
     * @param array<string, array> $flags    lo que llega al hook
     * @param array|null           $especial la etiqueta del Special Price, lista para pintar, o `null`
     * @param array<int, array>    $promos   las nuestras, en orden (van como `kpc_0`, `kpc_1`…)
     *
     * @return array<string, array> las etiquetas, en el orden en que se pintan
     */
    public static function componer(array $flags, ?array $especial, array $promos, bool $enSuFicha): array
    {
        unset($flags['kpy_special_price']);

        // 2. Las nuestras van calculadas; si todavía llega una fila vieja de la tabla de
        //    `kpyproductflags` con nuestro texto («Código −10 %», «+ 3 kg gratis»), fuera: saldría dos
        //    veces. (Red de seguridad hasta que el upgrade 2.5.0 las borre; ver `limpiarTablasAjenas`.)
        foreach ($flags as $clave => $flag) {
            if (self::tipoPromo((string) ($flag['label'] ?? '')) !== null) {
                unset($flags[$clave]);
            }
        }
        $nuestras = [];
        foreach (array_values($promos) as $i => $promo) {
            $nuestras['kpc_' . $i] = $promo;
        }

        // 3. «Ahorro XL» solo en su ficha.
        if (!$enSuFicha) {
            foreach ($flags as $clave => $flag) {
                if (self::soloEnSuFicha((string) $clave, (array) $flag)) {
                    unset($flags[$clave]);
                }
            }
        }

        // 4. Con Special Price, nada más que hable de precio.
        if ($especial !== null) {
            foreach ($flags as $clave => $flag) {
                if (!self::vaConElEspecial((string) $clave, (array) $flag)) {
                    unset($flags[$clave]);
                }
            }
        }

        // 5. En orden.
        $agotado = array_intersect_key($flags, ['out_of_stock' => true]);

        return ($especial !== null ? ['kpy_special_price' => $especial] : [])
            + $nuestras
            + array_diff_key($flags, $agotado)
            + $agotado;
    }

    /**
     * El chip de un formato (ver `deFormato`), con lo que `porFormato()` sabe del producto: su cupón
     * o su 2ª unidad; si no tiene, su regalo; si no, `null`.
     *
     * @param array<int|string, array> $formatos lo de `porFormato()` para ese producto
     * @param int                      $clave    el formato (`KpcAlcance::clave`)
     */
    public static function chip(array $formatos, int $clave, ?Kpycoupons $modulo = null): ?array
    {
        $promo = $formatos[$clave] ?? $formatos[$clave . '-regalo'] ?? null;
        if ($promo === null) {
            return null;
        }
        $e = self::deLaPromo($promo, $modulo, true);
        $e['label'] = self::corto($e['label']);

        return $e;
    }

    /**
     * La etiqueta de una promoción de cesta: «Código −10 %» (en el chip de la tarjeta, «Cupón −10 %»),
     * «2ª ud. −30 %» o, en un regalo, «+ 3 kg gratis» / «+ regalo». Con su `kpc`.
     *
     * @param array{tipo: string, porcentaje?: float, id?: int, etiqueta?: string} $promo una de `porFormato()`
     * @param bool                                                                 $corta la forma corta del chip
     *
     * @return array{type: string, label: string, color: string, background: string, kpc: array}
     */
    public static function deLaPromo(array $promo, ?Kpycoupons $modulo = null, bool $corta = false): array
    {
        $tipo = (string) ($promo['tipo'] ?? 'cupon');
        $kpc = [
            'tipo' => $tipo,
            'id_kpy_coupon' => (int) ($promo['id'] ?? 0),
            'pct' => $tipo === 'regalo' ? 0.0 : (float) ($promo['porcentaje'] ?? 0),
        ];
        if ($tipo === 'regalo') {
            return self::etiqueta('kpc-regalo', (string) ($promo['etiqueta'] ?? '+ regalo'), $kpc);
        }
        $pct = KpcElegibles::pct((float) ($promo['porcentaje'] ?? 0));
        if ($tipo === 'segunda_unidad') {
            return self::etiqueta('kpc-segunda', self::t($modulo, '2ª ud. −%pct% %', ['%pct%' => $pct]), $kpc);
        }

        return self::etiqueta('kpc-cupon', self::t($modulo, $corta ? 'Cupón −%pct% %' : 'Código −%pct% %', ['%pct%' => $pct]), $kpc);
    }

    /**
     * La etiqueta de un Special Price según cómo se llame su promoción (`kpy_coupon.etiqueta`: '' es
     * «Special Price», `liquidacion`, `outlet`, `bajada`), con su color y su `kpc`.
     *
     * @param int   $idPromo la promoción (0 si el Special Price está cargado por SQL, sin promoción)
     * @param float $pct     el % que rebaja de verdad
     *
     * @return array{type: string, label: string, color: string, background: string, kpc: array}
     */
    public static function etiquetaDeEspecial(string $nombre, int $idPromo, float $pct, ?Kpycoupons $modulo = null): array
    {
        return self::etiqueta(
            'kpc-' . KpcCupo::claseEtiqueta($nombre),
            self::t($modulo, KpcCupo::nombreEtiqueta($nombre)),
            ['tipo' => 'special_price', 'id_kpy_coupon' => $idPromo, 'pct' => $pct]
        );
    }

    /**
     * Viste la «Special price» que manda `kpyspecialprices` con la nuestra: siempre el nombre y el
     * `kpc`; y si la promoción tiene nombre propio («Liquidación»), también su `type` y sus colores
     * (el icono de `kpyspecialprices` se queda). Sin nombre propio, el `type` de `kpyspecialprices`
     * sigue (`kpy-special-price`): su hoja pinta la crema.
     *
     * @param array $especial lo que llega en `kpy_special_price`
     * @param array $propia   lo de `etiquetaEspecial()`
     */
    public static function vestirEspecial(array $especial, array $propia): array
    {
        $especial['label'] = $propia['label'];
        $especial['kpc'] = $propia['kpc'];

        return $propia['type'] !== 'kpc-especial' ? $propia + $especial : $especial;
    }

    /**
     * Un texto que quepa en el chip de la tarjeta (`CHIP_MAX`): «+ 6 latas gratis» se queda en
     * «+ 6 latas», y si ni así cabe, «+ regalo». Los de cupón y 2ª unidad ya caben.
     */
    public static function corto(string $texto, int $max = self::CHIP_MAX): string
    {
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        $sin = trim((string) preg_replace('/\s+gratis$/u', '', $texto));
        if (mb_strlen($sin) <= $max) {
            return $sin;
        }

        return strncmp($texto, '+ ', 2) === 0 ? '+ regalo' : rtrim(mb_substr($texto, 0, $max));
    }

    /**
     * ¿Puede esta etiqueta ir junto a «Special Price»? Solo las que no hablan de precio: «Top
     * ventas», «Nuevo», las de popularidad y «agotado».
     *
     * @param string $clave la clave con la que llega en el array de etiquetas
     */
    public static function vaConElEspecial(string $clave, array $flag): bool
    {
        foreach ([$clave, (string) ($flag['type'] ?? ''), (string) ($flag['label'] ?? '')] as $nombre) {
            if ($nombre !== '' && in_array(self::normal($nombre), self::JUNTO_AL_ESPECIAL, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ¿Es una etiqueta que solo sale en la ficha de su producto? (Ver `SOLO_EN_SU_FICHA`.)
     *
     * @param string $clave la clave con la que llega en el array de etiquetas
     */
    public static function soloEnSuFicha(string $clave, array $flag): bool
    {
        foreach ([$clave, (string) ($flag['type'] ?? ''), (string) ($flag['label'] ?? '')] as $nombre) {
            if ($nombre !== '' && in_array(self::normal($nombre), self::SOLO_EN_SU_FICHA, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * La clase de una etiqueta de promoción de este módulo, por su texto («Código −10 %» → cupón,
     * «2ª ud. −30 %» → 2ª unidad, «+ 3 kg gratis» → regalo), o `null` si no es nuestra. Red de
     * seguridad de `componer()`: así se reconoce una fila vieja de la tabla de `kpyproductflags`.
     */
    public static function tipoPromo(string $texto): ?string
    {
        if (preg_match('/^2ª ud\. −\d/u', $texto)) {
            return 'kpc-segunda';
        }
        if (preg_match('/^Código −\d/u', $texto)) {
            return 'kpc-cupon';
        }
        if (preg_match('/^\+ (regalo|\d)/u', $texto)) {
            return 'kpc-regalo';
        }

        return null;
    }

    /**
     * El descuento que anuncia la etiqueta, en %: del **precio habitual** al precio de hoy.
     *
     * Con los precios de verdad, no con los descuentos apuntados. Parece que daría igual (el precio
     * es `PVPR × (1 − descuento)`), pero no: cuando una combinación tiene varias filas de precio
     * vivas, PrestaShop aplica una concreta y no la mayor, así que echar la cuenta con los
     * descuentos anuncia rebajas que en la ficha no están. Se vio con el Pastor Alemán: por los
     * descuentos salía −33 % y el precio real estaba **por encima** del habitual.
     *
     * Es el mismo número que va en el feed de Google (`price` → `sale_price`) y el que pide la
     * normativa europea, que no deja anunciar un descuento contra el PVPR. Contra el PVPR el número
     * sería mayor, pero entonces la tienda y Shopping dirían cosas distintas.
     *
     * @param float $habitual PVPR menos el descuento de siempre (PriceShape)
     * @param float $hoy      lo que se paga hoy
     */
    public static function pct(float $habitual, float $hoy): int
    {
        if ($habitual <= 0 || $hoy <= 0 || $habitual <= $hoy + 0.01) {
            return 0;
        }

        return (int) round((1 - $hoy / $habitual) * 100);
    }

    /** El % de un formato concreto, preguntando sus precios a la tienda. */
    public static function pctDeFormato(int $idProducto, int $ipa, float $descuentoViejo): int
    {
        try {
            $hoy = (float) Product::getPriceStatic($idProducto, true, $ipa);
        } catch (Throwable $e) {
            return 0; // Sin contexto de precios (por ejemplo en consola) no se anuncia nada.
        }

        return self::pct(self::habitual($idProducto, $ipa, $descuentoViejo), $hoy);
    }

    /**
     * El precio habitual de un formato: su PVPR menos el descuento que tenía antes de la promoción
     * (`old_discount`, en tanto por uno). Es el «Precio habitual» de la ficha y el `price` de Google.
     */
    public static function habitual(int $idProducto, int $ipa, float $descuentoViejo): float
    {
        try {
            $pvpr = (float) Product::getPriceStatic($idProducto, true, $ipa, 6, null, false, false);
        } catch (Throwable $e) {
            return 0.0;
        }

        return round($pvpr * (1 - $descuentoViejo), 2);
    }

    // ------------------------------------------------------------------------------------------
    // Limpieza y caché
    // ------------------------------------------------------------------------------------------

    /**
     * Borra lo que este módulo escribió hasta la 2.4 en las tablas de `kpyproductflags` (los tipos
     * «Oferta −X %», «Código −X %», «2ª ud. −X %» y «+ …», con el menos tipográfico y con el guion
     * normal), en el orden que manda su clave foránea: `kpy_product_flag` → `kpy_flag_lang` →
     * `kpy_flag`. Las etiquetas manuales de Julián («Bajada de Precio», «kg Gratis», «Popular»,
     * «Tendencia», «Top Ventas», «Envío gratis», «Innovación», «Ahorro XL») no se tocan.
     *
     * Lo llaman el `upgrade` 2.5.0 y `uninstall()`. Se puede repetir: la segunda vez no hay nada que
     * borrar. Si `kpyproductflags` no está (faltan sus tablas), no hace nada.
     *
     * @param bool $ensayo solo contar, sin borrar
     *
     * @return array{kpy_product_flag: int, kpy_flag_lang: int, kpy_flag: int} filas borradas (o por borrar) de cada tabla
     *
     * @throws RuntimeException si la base de datos no acepta un borrado (fuera del modo desarrollo
     *                          PrestaShop no lanza nada por su cuenta)
     */
    public static function limpiarTablasAjenas(bool $ensayo = false): array
    {
        $salida = ['kpy_product_flag' => 0, 'kpy_flag_lang' => 0, 'kpy_flag' => 0];
        foreach (array_keys($salida) as $tabla) {
            if (!KpcAlcance::hayTabla($tabla)) {
                return $salida;
            }
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $ids = array_map('intval', array_column(
            $db->executeS("SELECT f.id_flag FROM {$p}kpy_flag f WHERE " . self::sqlTiposPropios('f')) ?: [],
            'id_flag'
        ));
        if ($ids === []) {
            return $salida;
        }
        $lista = implode(',', $ids);
        if ($ensayo) {
            $salida['kpy_product_flag'] = (int) $db->getValue("SELECT COUNT(*) FROM {$p}kpy_product_flag WHERE id_flag IN ({$lista})");
            $salida['kpy_flag_lang'] = (int) $db->getValue("SELECT COUNT(*) FROM {$p}kpy_flag_lang WHERE id_flag IN ({$lista})");
            $salida['kpy_flag'] = count($ids);

            return $salida;
        }
        foreach (array_keys($salida) as $tabla) {
            if ($db->execute("DELETE FROM {$p}{$tabla} WHERE id_flag IN ({$lista})") === false) {
                throw new RuntimeException('kpycoupons: no se ha podido limpiar ' . $p . $tabla . ': ' . ($db->getMsgError() ?: 'la base de datos no lo ha aceptado'));
            }
            $salida[$tabla] = (int) $db->Affected_Rows();
        }

        return $salida;
    }

    /**
     * Olvida lo leído en esta petición. Lo llama `KpcReparto::rehacer()`: lo que se acaba de guardar
     * (reparto, Special Price, regalos) es lo que tiene que salir si en la misma petición se pinta algo.
     */
    public static function olvidarCache(): void
    {
        self::$promos = null;
        self::$porFormato = null;
        self::$etiquetasEspeciales = null;
        self::$especiales = null;
    }

    // ------------------------------------------------------------------------------------------
    // Lo privado
    // ------------------------------------------------------------------------------------------

    /** @return array{type: string, label: string, color: string, background: string, kpc: array} */
    private static function etiqueta(string $tipo, string $texto, array $kpc): array
    {
        return [
            'type' => $tipo,
            'label' => $texto,
            'color' => self::TEXTO[$tipo] ?? '#ffffff',
            'background' => self::COLORES[$tipo] ?? '#C23A2C',
            'kpc' => $kpc,
        ];
    }

    /** Traduce por el módulo; sin él (pruebas puras), el texto tal cual con sus reemplazos. */
    private static function t(?Kpycoupons $modulo, string $texto, array $reemplazos = []): string
    {
        return $modulo !== null ? $modulo->t($texto, $reemplazos) : strtr($texto, $reemplazos);
    }

    /** «60,19 €», como lo escribe la tienda. */
    private static function euros(float $valor): string
    {
        return number_format($valor, 2, ',', '.') . ' €';
    }

    /** En minúsculas, sin tildes y sin nada que no sea letra o número: «Top Ventas» → «topventas». */
    private static function normal(string $texto): string
    {
        $texto = strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return (string) preg_replace('/[^a-z0-9]+/', '', $texto);
    }

    /** Los tipos de `kpy_flag` que son nuestros, en SQL (para `limpiarTablasAjenas`). */
    private static function sqlTiposPropios(string $alias): string
    {
        $partes = [];
        foreach (['Oferta ', 'Código ', '2ª ud. '] as $prefijo) {
            foreach (['−', '-'] as $guion) {
                $partes[] = $alias . ".type LIKE '" . pSQL($prefijo . $guion) . "%'";
            }
        }
        $partes[] = $alias . ".type LIKE '+ %'";

        return '(' . implode(' OR ', $partes) . ')';
    }

    /**
     * ¿Se está pintando este producto en su propia ficha? (Y no en los accesorios o las sugerencias
     * de la ficha de otro, que son un listado.)
     */
    private static function enSuFicha(int $idProducto): bool
    {
        $controlador = Context::getContext()->controller ?? null;

        return $controlador instanceof ProductControllerCore && (int) Tools::getValue('id_product') === $idProducto;
    }

    /**
     * El descuento del Special Price de un producto, mirando el formato que enseña la tarjeta.
     *
     * Si ese formato tiene promoción apuntada, manda él (aunque haya terminado: entonces no hay
     * etiqueta, que es lo honesto). Si no la tiene, vale la mejor de las de sus otros formatos: la
     * etiqueta dice que en este producto hay una oferta, y el cliente la encuentra al entrar.
     */
    private static function pctEspecialDe(int $idProducto, int $ipa): int
    {
        $filas = self::especiales()[$idProducto] ?? [];
        if ($filas === []) {
            return 0;
        }
        if ($ipa > 0 && isset($filas[$ipa])) {
            return self::pctDeFormato($idProducto, $ipa, $filas[$ipa]);
        }

        $mejor = 0;
        foreach ($filas as $otro => $viejo) {
            $mejor = max($mejor, self::pctDeFormato($idProducto, (int) $otro, $viejo));
        }

        return $mejor;
    }

    /**
     * Qué formatos tienen Special Price vivo y con qué descuento de siempre, de una sola consulta
     * por página a `kpy_special_price` (la tabla de datos del Special Price de Kompy, de
     * `kpyspecialprices`; solo lectura: `id_product`, `id_product_attribute`, `id_shop`,
     * `old_discount`, `date_from`, `expire`). Son pocas filas y así un listado de 24 productos no
     * hace 24 consultas; si hay promoción de verdad se decide después, con los precios de cada formato.
     *
     * @return array<int, array<int, float>> id_product => [id_product_attribute => old_discount]
     */
    private static function especiales(): array
    {
        if (self::$especiales !== null) {
            return self::$especiales;
        }
        self::$especiales = [];
        if (!KpcCupon::hayTablaEspeciales()) {
            return self::$especiales;
        }
        $p = _DB_PREFIX_;
        // Sin los formatos apagados (`kpy_product_attribute.active = 0`, los que la ficha no enseña): el mismo
        // filtro que `KpcMerchant::especiales()`, para que la tienda, Doofinder y Merchant cuenten los mismos
        // formatos (un producto rebajado solo en un formato apagado salía «Precio especial», 26/10/2026).
        $apagado = KpcAlcance::hayFormatosApagados()
            ? " AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute k WHERE k.id_product_attribute = sp.id_product_attribute AND k.active = 0)"
            : '';
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT sp.id_product, sp.id_product_attribute AS ipa, sp.old_discount FROM {$p}kpy_special_price sp
             WHERE sp.id_shop = " . (int) Context::getContext()->shop->id . " AND NOW() BETWEEN sp.date_from AND sp.expire{$apagado}"
        ) ?: [];
        foreach ($filas as $f) {
            self::$especiales[(int) $f['id_product']][(int) $f['ipa']] = (float) $f['old_discount'];
        }

        return self::$especiales;
    }

    /**
     * Los formatos cuyo Special Price es de una promoción de esta pantalla (`kpy_coupon_precio`), con
     * cómo se llama (`kpy_coupon.etiqueta`: '' es «Special Price») y de qué promoción es. Una consulta
     * por página. Los cargados por SQL no están aquí: son «Special Price» sin promoción (`id` 0).
     *
     * @return array<int, array<int, array{etiqueta: string, id: int}>> producto => [formato => nombre y promoción]
     */
    private static function etiquetasEspeciales(): array
    {
        if (self::$etiquetasEspeciales !== null) {
            return self::$etiquetasEspeciales;
        }
        self::$etiquetasEspeciales = [];
        $p = _DB_PREFIX_;
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT cp.id_product, cp.id_product_attribute AS ipa, c.id_kpy_coupon, c.etiqueta FROM {$p}kpy_coupon_precio cp
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = cp.id_kpy_coupon
             WHERE c.activo = 1 AND c.tipo = 'special_price' AND c.id_shop = " . (int) Context::getContext()->shop->id . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c') . '
             ORDER BY c.id_kpy_coupon'
        ) ?: [] as $f) {
            self::$etiquetasEspeciales[(int) $f['id_product']][(int) $f['ipa']] ??= [
                'etiqueta' => (string) $f['etiqueta'],
                'id' => (int) $f['id_kpy_coupon'],
            ];
        }

        return self::$etiquetasEspeciales;
    }

    /**
     * Qué promoción de cesta (cupón, 2ª unidad o regalo) le toca a cada formato, del reparto ya
     * guardado (`KpcReparto::vigentes`, de `kpy_coupon_item`) y de las promociones en vigor
     * (`KpcCupon::enVigor`, de `kpy_coupon`). Una consulta por tabla y por página.
     *
     * Un regalo no quita el sitio a un cupón ni a una 2ª unidad del mismo formato: se apila, en la
     * clave «formato-regalo», con lo que dice su etiqueta («+ 3 kg gratis», «+ regalo»).
     *
     * @return array<int, array<int|string, array{tipo: string, porcentaje: float, codigo: string, hasta: ?string, id: int, etiqueta?: string, clave?: int}>>
     *         id de producto => [clave del formato => promoción]
     */
    private static function porFormato(): array
    {
        if (self::$porFormato !== null) {
            return self::$porFormato;
        }
        self::$porFormato = [];
        $idTienda = (int) Context::getContext()->shop->id;
        $reparto = KpcReparto::vigentes($idTienda);
        // Un cupón que solo se ve desde un canal, solo para esa visita y solo en su ficha (en los listados, la
        // portada y el feed de Doofinder, nunca: un bloque cacheado por Smarty, como el de la portada, se
        // pintaría para todos con lo que viera el primer visitante). Y quien ya lo lleva puesto, lo ve.
        $canal = Kpycoupons::canalDeLaVisita();
        $puesto = mb_strtoupper(Kpycoupons::cuponPuesto());
        // `enVigor` las devuelve en orden de mando: la primera que toca un formato, manda.
        $cupones = [];
        $regalos = [];
        foreach (KpcCupon::enVigor($idTienda) as $c) {
            $porCanal = (string) ($c['ver_canal'] ?? '') !== '';
            if (KpcElegibles::esPrecio($c) || (!KpcElegibles::seVe($c, $canal) && !($porCanal && $puesto !== '' && mb_strtoupper((string) $c['codigo']) === $puesto))) {
                // Un cupón que no se enseña (o solo a quien llega de un canal, y esta visita no) tampoco se
                // anuncia; el Special Price lleva la suya.
                continue;
            }
            $cupones[] = $c;
            if (KpcElegibles::esRegalo($c)) {
                $regalos[] = (int) $c['id_kpy_coupon'];
            }
        }
        $etiquetas = self::etiquetasDeRegalo($regalos);
        foreach ($cupones as $c) {
            $id = (int) $c['id_kpy_coupon'];
            $hasta = (string) ($c['fecha_hasta'] ?? '');
            $promo = [
                'tipo' => (string) ($c['tipo'] ?? 'cupon'),
                'porcentaje' => (float) $c['porcentaje'],
                'codigo' => (string) $c['codigo'],
                'hasta' => $hasta !== '' && strpos($hasta, '0000') !== 0 ? $hasta : null,
                'id' => $id,
                // Solo en su ficha (`elegir()`): el cupón por canal no se anuncia en ningún listado.
                'solo_ficha' => (string) ($c['ver_canal'] ?? '') !== '',
            ];
            foreach ($reparto[$id] ?? [] as $clave => $idProducto) {
                if ($promo['tipo'] === 'regalo') {
                    self::$porFormato[(int) $idProducto][(int) $clave . '-regalo'] ??= $promo + [
                        'etiqueta' => $etiquetas[$id][(int) $clave] ?? '+ regalo',
                        'clave' => (int) $clave,
                    ];
                } else {
                    self::$porFormato[(int) $idProducto][(int) $clave] ??= $promo;
                }
            }
        }

        return self::$porFormato;
    }

    /**
     * Lo que dice la etiqueta de cada disparador de unos regalos (`kpy_coupon_regalo`): «+ 3 kg
     * gratis» si regala el saco pequeño de su ficha (o la lata…), «+ regalo» si es otra cosa. Dos
     * consultas para todos los regalos de la página (las parejas y los nombres de los formatos).
     *
     * @param int[] $ids las promociones de regalo en vigor
     *
     * @return array<int, array<int, string>> promoción => [clave del disparador => etiqueta]
     */
    private static function etiquetasDeRegalo(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_kpy_coupon, clave, id_product, r_id_product, r_ipa FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo
             WHERE id_kpy_coupon IN (' . implode(',', $ids) . ')'
        ) ?: [];
        $ipas = [];
        foreach ($filas as $f) {
            if ((int) $f['r_ipa'] > 0) {
                $ipas[(int) $f['r_ipa']] = true;
            }
        }
        $nombres = KpcRegalo::nombresFormato(array_keys($ipas));
        $salida = [];
        foreach ($filas as $f) {
            $salida[(int) $f['id_kpy_coupon']][(int) $f['clave']] = (int) $f['r_ipa'] > 0
                ? KpcRegalo::etiqueta((string) ($nombres[(int) $f['r_ipa']] ?? ''), (int) $f['r_id_product'] === (int) $f['id_product'])
                : '+ regalo';
        }

        return $salida;
    }

    /**
     * La promoción de cesta que manda en cada producto (para el buscador, que enseña el producto
     * entero): lo de `elegir()` fuera de la ficha, para todo el catálogo con promoción.
     *
     * @return array<int, array{tipo: string, porcentaje: float, codigo: string, hasta: ?string, id: int}>
     */
    private static function promos(): array
    {
        if (self::$promos !== null) {
            return self::$promos;
        }
        self::$promos = [];
        foreach (self::porFormato() as $idProducto => $formatos) {
            $manda = self::elegir($formatos, 0, false);
            if ($manda !== []) {
                self::$promos[$idProducto] = $manda[0];
            }
        }

        return self::$promos;
    }
}
