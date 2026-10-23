<?php
/**
 * Los formatos de un producto, leídos como los piensa quien hace una promoción: **de qué tamaño**
 * es («saco de 12 kg», «lata de 70 g») y **de qué nivel de pack** (suelto, pack de 2, pack de 4…).
 *
 *   «Saco de 12 Kg»            tamaño «saco 12 kg»   nivel 1 (suelto)
 *   «Pack 2 x Saco de 12 Kg»   tamaño «saco 12 kg»   nivel 2, su base es el saco de 12 kg
 *   «Pack 12 x Lata de 70 gr»  tamaño «lata 70 g»    nivel 1: en Schesir la caja de 12 es lo suelto
 *   «Pack 24 x Lata de 70 gr»  tamaño «lata 70 g»    nivel 2: dos cajas de 12
 *
 * El nivel sale de la tabla de packs de Kompy (`kpy_packs`: este pack son N del formato tal) y, si
 * el pack no está apuntado ahí, del nombre: «Pack 24 x Lata de 70 gr» es el doble de «Pack 12 x
 * Lata de 70 gr» del mismo producto. El «suelto» es siempre lo más pequeño que se vende de ese
 * tamaño: hay marcas que no venden latas sueltas, y su caja de 12 es la unidad.
 *
 * Sin PrestaShop dentro, para poder probarlo (`tests/formatos.php`).
 */
class KpcFormatos
{
    /** Los envases, en el orden en que se enseñan (primero lo seco, luego lo húmedo). */
    private const ENVASES = ['saco', 'bolsa', 'caja', 'envase', 'bote', 'lata', 'tarrina', 'sobre', 'pouch', 'bandeja', 'barrita'];

    private const PLURALES = [
        'saco' => 'Sacos', 'bolsa' => 'Bolsas', 'caja' => 'Cajas', 'envase' => 'Envases', 'bote' => 'Botes', 'lata' => 'Latas',
        'tarrina' => 'Tarrinas', 'sobre' => 'Sobres', 'pouch' => 'Pouches', 'bandeja' => 'Bandejas', 'barrita' => 'Barritas',
    ];

    /**
     * El tamaño de un formato, sin el pack ni el OUTLET: «Pack 2 x Saco de 12 Kg» → «saco 12 kg».
     * Es lo que se elige en la pantalla («sacos de 12 kg») y vale igual para el suelto y sus packs.
     */
    public static function tamano(string $nombre): string
    {
        $n = self::normalizar($nombre);
        $n = trim(preg_replace('/\s*-?\s*\boutlet\b\s*/u', ' ', $n) ?? $n);
        // Fuera el «Pack 2 x», «Pack 24 latas», «paquete de 4 bolsas», «2 x»… si detrás queda un
        // tamaño o un envase (en «Pack 16 uds» no queda nada que sea un tamaño: se deja tal cual).
        $sinPack = preg_replace('/^(?:(?:pack|paquete)\s+(?:de\s+)?)?\d{1,3}\s*(?:x\s*|(?=[a-z]))/u', '', $n) ?? $n;
        if ($sinPack !== $n && self::tieneTamano($sinPack)) {
            $n = trim($sinPack);
            // «Pack 2 x Pack 6 x Bolsa de 50 gr»: el de dentro también.
            $otro = preg_replace('/^(?:pack|paquete)\s+(?:de\s+)?\d{1,3}\s*x\s*/u', '', $n) ?? $n;
            if ($otro !== $n && self::tieneTamano($otro)) {
                $n = trim($otro);
            }
        }
        // Envases en singular y sin «de»: «latas de 85 gr» → «lata 85 gr».
        $n = preg_replace_callback('/\b(sacos|bolsas|cajas|envases|botes|latas|tarrinas|sobres|pouches|bandejas|barritas)\b/u', static function ($m) {
            return $m[1] === 'pouches' ? 'pouch' : substr($m[1], 0, -1);
        }, $n) ?? $n;
        $n = preg_replace('/\b(' . implode('|', self::ENVASES) . ')\s+(?:de\s+|x\s+)?/u', '$1 ', $n) ?? $n;
        // Unidades: «70gr», «70 grs», «60 g.» → «70 g»; «3kg.» → «3 kg»; «10 Litros» → «10 l».
        $n = preg_replace('/(\d)\s*(?:gramos|grs|gr|g)\.?(?=\s|$|\W)/u', '$1 g', $n) ?? $n;
        $n = preg_replace('/(\d)\s*(?:kilos|kilo|kgs|kg)\.?(?=\s|$|\W)/u', '$1 kg', $n) ?? $n;
        $n = preg_replace('/(\d)\s*(?:litros|litro|ltrs|ltr|lt|l)\.?(?=\s|$|\W)/u', '$1 l', $n) ?? $n;
        $n = preg_replace('/(\d)\s*ml\.?(?=\s|$|\W)/u', '$1 ml', $n) ?? $n;
        // Decimales siempre con coma: «1.5 kg» → «1,5 kg».
        $n = preg_replace('/(\d)\.(\d)/u', '$1,$2', $n) ?? $n;
        $n = trim(preg_replace('/\s+/u', ' ', $n) ?? $n, ' .-');
        // «12 kg» a secas es un saco (así lo llama Kompy en muchas fichas: «Pack 2 x 12 Kg»).
        if (preg_match('/^\d+(?:,\d+)?(?:\s*\+\s*\d+(?:,\d+)?)?\s*kg$/u', $n)) {
            $n = 'saco ' . $n;
        }

        return $n;
    }

    /** «saco 12 kg» → «Saco 12 kg»; «xl» → «XL». */
    public static function etiqueta(string $tamano): string
    {
        if (preg_match('/^(?:x{0,3}s|s|m|l|x{0,3}l)(?:\s*-\s*(?:x{0,3}s|s|m|l|x{0,3}l))?$/u', $tamano)) {
            return mb_strtoupper($tamano);
        }

        return mb_strtoupper(mb_substr($tamano, 0, 1)) . mb_substr($tamano, 1);
    }

    /**
     * Para agrupar los tamaños en la pantalla: «saco 12 kg» → ['Sacos', '12 kg'].
     *
     * @return array{0: string, 1: string}
     */
    public static function grupo(string $tamano): array
    {
        foreach (self::ENVASES as $e) {
            if (strpos($tamano, $e . ' ') === 0) {
                return [self::PLURALES[$e], self::etiqueta(substr($tamano, strlen($e) + 1))];
            }
        }

        return ['Otros', self::etiqueta($tamano)];
    }

    /**
     * Para ordenar los tamaños: primero por envase (sacos, bolsas… latas, tarrinas…) y dentro, de
     * menor a mayor. «saco 1,5 kg» < «saco 12 kg» < «lata 70 g» < «lata 400 g».
     *
     * @return array{0: int, 1: float, 2: string}
     */
    public static function orden(string $tamano): array
    {
        $envase = count(self::ENVASES);
        foreach (self::ENVASES as $i => $e) {
            if (strpos($tamano, $e . ' ') === 0) {
                $envase = $i;
                break;
            }
        }
        $cantidad = PHP_FLOAT_MAX;
        // «saco 14 + 2 kg» son 16 kg.
        if (preg_match('/(\d+(?:,\d+)?)(?:\s*\+\s*(\d+(?:,\d+)?))?\s*(kg|g|ml|l)\b/u', $tamano, $m)) {
            $x = (float) str_replace(',', '.', $m[1]) + (float) str_replace(',', '.', $m[2] !== '' ? $m[2] : '0');
            $cantidad = $x * ['kg' => 1000, 'g' => 1, 'l' => 1000, 'ml' => 1][$m[3]];
        }

        return [$envase, $cantidad, $tamano];
    }

    /**
     * Nivel de pack, formato suelto y tamaño de cada formato de un producto.
     *
     * El nivel es **cuántos sueltos lleva**: «Pack 2 x Saco de 12 Kg» es 2 (dos sacos); en Schesir,
     * «Pack 48 x Lata de 70 gr» es 4 (cuatro cajas de 12, que es lo suelto). `padre` es el formato
     * del que es pack directamente (la tabla encadena: el de 48 son dos del de 24) y `base`, el
     * suelto del que sale todo.
     *
     * Hay que pasarle **todos** los formatos del producto, también los apagados: si el saco suelto
     * está apagado, su pack sigue siendo un pack (y la 2ª unidad no puede entrar en él).
     *
     * @param array<int, array{ipa: int, nombre?: string}> $combinaciones los del producto
     * @param array<int, array{ipa: int, n: int}> $packs los apuntados en `kpy_packs` para este
     *        producto: id de la combinación pack => [el formato que lleva (del mismo producto), cuántos]
     *
     * @return array<int, array{nivel: int, base: ?int, padre: ?int, tam: string}> por id de combinación
     */
    public static function niveles(array $combinaciones, array $packs = []): array
    {
        $nombres = [];
        foreach ($combinaciones as $c) {
            $nombres[(int) ($c['ipa'] ?? 0)] = (string) ($c['nombre'] ?? '');
        }
        // De quién es pack cada uno y cuántos lleva: ipa => [ipa del padre (o null), cuántos].
        $padre = [];

        // 1. Lo que dice la tabla de packs: «este pack son 2 de aquel formato».
        foreach ($packs as $ipa => $p) {
            $base = (int) ($p['ipa'] ?? 0);
            $n = (int) ($p['n'] ?? 0);
            if (isset($nombres[$ipa], $nombres[$base]) && $base !== $ipa && $n >= 2) {
                $padre[$ipa] = [$base, $n];
            }
        }

        // 2. Lo que no está en la tabla, por el nombre: de los del mismo tamaño y envase, el más
        //    pequeño es el suelto y los demás son packs de él si son un múltiplo exacto.
        $familias = [];
        foreach ($nombres as $ipa => $nombre) {
            $familia = self::familia($nombre);
            if (!isset($padre[$ipa]) && !self::esOutlet($nombre) && $familia !== '') {
                $familias[$familia][$ipa] = self::unidades($nombre);
            }
        }
        foreach ($familias as $familia => $miembros) {
            asort($miembros);
            $suelto = (int) array_key_first($miembros);
            $minimo = $miembros[$suelto];
            foreach ($miembros as $ipa => $unidades) {
                if ($ipa !== $suelto && $unidades > $minimo && $unidades % $minimo === 0) {
                    $padre[$ipa] = [$suelto, intdiv($unidades, $minimo)];
                }
            }
            // Un «Pack 2 x Saco de 9 Kg» sin el saco suelto a la venta sigue siendo un pack de dos
            // sacos. (Las latas no: sin lata suelta, la caja de 12 es lo suelto.)
            if ($minimo > 1 && strpos($familia, 'saco ') === 0) {
                foreach ($miembros as $ipa => $unidades) {
                    if (!isset($padre[$ipa]) && $unidades === $minimo) {
                        $padre[$ipa] = [null, $minimo];
                    }
                }
            }
        }

        // 3. El OUTLET de un pack también es pack («Pack 2 x Saco de 12 Kg - OUTLET»).
        foreach ($nombres as $ipa => $nombre) {
            $unidades = self::unidades($nombre);
            $familia = self::familia($nombre);
            if (isset($padre[$ipa]) || !self::esOutlet($nombre) || $unidades < 2 || $familia === '') {
                continue;
            }
            foreach ($familias[$familia] ?? [] as $otro => $suyas) {
                if (!isset($padre[$otro]) && $unidades > $suyas && $unidades % $suyas === 0) {
                    $padre[$ipa] = [$otro, intdiv($unidades, $suyas)];
                    break;
                }
            }
            if (!isset($padre[$ipa]) && strpos($familia, 'saco ') === 0) {
                $padre[$ipa] = [null, $unidades];
            }
        }

        // 4. Se sigue la cadena hasta el suelto: el nivel se multiplica, y el tamaño es el del
        //    suelto (el pack «2 x 12 Kg» de un «Saco de 12 Kg» es «saco 12 kg»).
        $salida = [];
        foreach ($nombres as $ipa => $nombre) {
            $nivel = 1;
            $base = null;
            $actual = $ipa;
            $vistos = [];
            while (isset($padre[$actual]) && !isset($vistos[$actual])) {
                $vistos[$actual] = true;
                [$arriba, $n] = $padre[$actual];
                $nivel *= $n;
                if ($arriba === null) {
                    break;
                }
                $base = $arriba;
                $actual = $arriba;
            }
            $salida[$ipa] = [
                'nivel' => $nivel,
                'base' => $base,
                'padre' => $padre[$ipa][0] ?? null,
                'tam' => self::tamano($base !== null ? $nombres[$base] : $nombre),
            ];
        }

        return $salida;
    }

    /** Cuántas piezas lleva el nombre: 2 en «Pack 2 x Saco de 12 Kg», 24 en «Pack 24 Latas 85 gr». */
    public static function unidades(string $nombre): int
    {
        $n = self::normalizar($nombre);
        $total = 1;
        // «Pack 2 x Pack 6 x Bolsa de 50 gr» = 12.
        while (preg_match('/^(?:(?:pack|paquete)\s+(?:de\s+)?)?(\d{1,3})\s*(?:x\s*|(?=[a-z]))(.*)$/u', $n, $m) && self::tieneTamano($m[2])) {
            $total *= max(1, (int) $m[1]);
            $n = trim($m[2]);
        }

        return $total;
    }

    /** ¿Es un formato OUTLET? «Saco de 12 Kg - OUTLET». */
    public static function esOutlet(string $nombre): bool
    {
        return (bool) preg_match('/\boutlet\b/i', $nombre);
    }

    /**
     * Lo que tienen en común un formato y sus packs por el nombre: el tamaño, y el envase si lo
     * dice. «Pack 2 x 12 Kg» y «Saco de 12 Kg» son de la misma familia; «Bolsa de 70 gr» y «Pack 12
     * x Lata de 70 gr», no. Vacío si el nombre no dice ningún tamaño («Talla M», «Rojo»).
     */
    private static function familia(string $nombre): string
    {
        $tam = self::tamano($nombre);
        if (!self::tieneTamano($tam)) {
            return '';
        }

        return $tam;
    }

    /** ¿Dice un tamaño o un envase? «12 kg», «lata de 70 gr», «bolsa 7 uds». */
    private static function tieneTamano(string $texto): bool
    {
        return (bool) preg_match('/\d\s*(?:kg|kgs|kilos?|g|gr|grs|gramos|ml|l|litros?|ltrs?)\b\.?/u', $texto)
            || (bool) preg_match('/^(?:' . implode('|', self::ENVASES) . ')s?\b|^pouches\b/u', trim($texto));
    }

    /** Minúsculas, sin acentos y con los espacios justos. */
    public static function normalizar(string $texto): string
    {
        $t = mb_strtolower(trim($texto));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        return trim($t);
    }
}
