<?php
/**
 * El lector de listas de kpysupply: las listas que llegan de fuera (la de un proveedor con sus EAN
 * o sus referencias, una columna copiada de un Excel, unos ids sueltos, enlaces de la web…), en
 * filas y columnas, y qué producto o formato de la tienda es cada fila.
 *
 * **Es una copia de `KpcLista` de kpycoupons** (la 2.4.1, hecha el 23/10/2026), con el nombre
 * cambiado y con lo poco que aquella pedía a otras clases suyas (`KpcAlcance::sqlApagado()`,
 * `KpcAlcance::tablaPacks()` y `KpcFormatos::niveles()`, para saber qué formato es pack de cuál)
 * traído aquí como métodos privados. Por qué una copia y no una llamada: la regla de Julián de que
 * **ningún módulo llama a funciones de otro**; el código puro que dos módulos necesitan se copia,
 * no se comparte (docs/plan-desacoplar-kpycoupons.md, apartado 2). Las dos copias comparten
 * pruebas: `tests/lectura.php` es la misma batería que `kpycoupons/tests/lista.php`, y la prueba de
 * servidor (`tests/servidor/acuerdos-pruebas.php`) comprueba en pruebas que las dos dan el mismo
 * índice del catálogo y leen igual la misma lista. Si se arregla algo en una, se arregla en la otra.
 *
 * Qué se reconoce:
 *   - EAN (8, 12, 13 o 14 cifras; con o sin ceros delante, que Excel se los come);
 *   - referencias: la nuestra, la del proveedor y el MPN, del formato o del producto (sin mirar
 *     mayúsculas, espacios, puntos ni guiones);
 *   - ids de producto (`7214`) y «producto-formato» (`7214-90684`, el id del feed de Google);
 *   - enlaces de la web (`…/545-824-royal-canin-renal.html`).
 *
 * Con una lista de varias columnas (EAN, nombre, precio…) se queda con la columna que más
 * encuentra: el nombre y el precio no se confunden con nada.
 *
 * La parte que decide es pura (`celdas`, `pinta`, `buscar` con un índice dado) para poder probarla
 * sin tienda; `indice()` es lo único que pregunta a la base de datos, y solo a tablas del core y de
 * Julián (`product`, `product_shop`, `product_attribute`, `product_supplier`,
 * `kpy_product_attribute`, `kpy_packs`): ninguna de kpycoupons.
 */
class KsLectura
{
    /** Cómo leer la lista. `auto`: lo decide sola, columna a columna. */
    public const TIPOS = ['auto', 'codigo', 'id', 'combinacion'];

    /** Más filas que esto no es una lista de proveedor: es un error al elegir fichero. */
    public const MAX_FILAS = 20000;

    /** Los envases que dicen los nombres de los formatos (para saber qué es pack de qué). */
    private const ENVASES = ['saco', 'bolsa', 'caja', 'envase', 'bote', 'lata', 'tarrina', 'sobre', 'pouch', 'bandeja', 'barrita'];

    /**
     * Un texto pegado, en filas y columnas.
     *
     * Con tabuladores (lo que da copiar de Excel) o punto y coma, cada uno separa columnas. Sin
     * ellos, cada línea puede traer varios valores separados por comas («7214, 7215»): cada uno pasa
     * a ser su propia fila. Los espacios no separan: «TX 32281» es una referencia; si así no
     * aparece, `uno()` prueba palabra a palabra («Boske 7214» → 7214). Lo que no lleva ni una
     * cifra se tira (son nombres, no códigos).
     *
     * @return array<int, string[]>
     */
    public static function celdas(string $texto): array
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto) ?? $texto;
        $filas = [];
        foreach (explode("\n", $texto) as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            if (strpos($linea, "\t") !== false || strpos($linea, ';') !== false) {
                $filas[] = array_map([self::class, 'limpiar'], preg_split('/[\t;]/', $linea) ?: []);
                continue;
            }
            // La coma separa, salvo la de un decimal («44,99 €», «12,5»): cifra, coma y una o dos cifras.
            foreach (preg_split('/(?<!\d),|,(?!\d{1,2}(?!\d))/', $linea) ?: [] as $valor) {
                $valor = self::limpiar($valor);
                if ($valor !== '' && preg_match('/\d/', $valor)) {
                    $filas[] = [$valor];
                }
            }
            if (count($filas) > self::MAX_FILAS) {
                break;
            }
        }

        return array_slice($filas, 0, self::MAX_FILAS);
    }

    /**
     * Un fichero subido (Excel, LibreOffice, CSV o texto), en filas y columnas. Solo la primera
     * hoja. Los números largos (EAN) se sacan enteros: Excel los guarda como 8,71E+12.
     *
     * @return array<int, string[]>
     */
    public static function deFichero(string $ruta, string $nombre): array
    {
        $extension = mb_strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (in_array($extension, ['txt', 'tsv', ''], true) || !class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            return self::celdas((string) file_get_contents($ruta));
        }
        if ($extension === 'csv') {
            $texto = (string) file_get_contents($ruta);
            // Un CSV con comas: se pasan a punto y coma para que cada coma separe una columna.
            if (strpos($texto, ';') === false && strpos($texto, "\t") === false) {
                $texto = str_replace(',', ';', $texto);
            }

            return self::celdas($texto);
        }

        $libro = \PhpOffice\PhpSpreadsheet\IOFactory::load($ruta);
        $filas = [];
        foreach ($libro->getActiveSheet()->toArray(null, true, false, false) as $fila) {
            $celdas = [];
            foreach ($fila as $valor) {
                if (is_float($valor) && floor($valor) === $valor && abs($valor) < 1e15) {
                    $valor = sprintf('%.0f', $valor);
                }
                $celdas[] = self::limpiar((string) $valor);
            }
            if (implode('', $celdas) !== '') {
                $filas[] = $celdas;
            }
            if (count($filas) >= self::MAX_FILAS) {
                break;
            }
        }

        return $filas;
    }

    /**
     * Qué parece un valor, solo por su forma.
     *
     * @return string `combinacion` (7214-90684), `url`, `ean`, `numero` (un id, o una referencia
     *                corta), `texto` (una referencia) o `nada`
     */
    public static function pinta(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '' || !preg_match('/\d/', $valor)) {
            return 'nada';
        }
        if (preg_match('~^https?://|\.html~i', $valor)) {
            return 'url';
        }
        if (preg_match('/^\d{1,7}-\d{1,7}$/', $valor)) {
            return 'combinacion';
        }
        if (preg_match('/^\d{8}$|^\d{12,14}$/', $valor)) {
            return 'ean';
        }
        if (preg_match('/^\d{1,6}$/', $valor)) {
            return 'numero';
        }

        return 'texto';
    }

    /** El EAN sin ceros delante: «0052742805702» y «052742805702» son el mismo. */
    public static function ean(string $valor): string
    {
        return ltrim(preg_replace('/\D/', '', $valor) ?? '', '0');
    }

    /** La referencia sin lo que cambia de un sitio a otro: mayúsculas, espacios, puntos, guiones. */
    public static function ref(string $valor): string
    {
        return mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $valor) ?? '');
    }

    /**
     * Busca la lista en la tienda.
     *
     * Con `$conValor` («pct» o «precio»), cada artículo trae además su descuento o su precio (el %
     * de un proveedor que da uno distinto a cada referencia, o un precio cerrado): se busca la
     * columna que lo lleva y se lee con él. Vale «13», «13 %», «0,13» (un porcentaje de Excel),
     * «12,99 €» o «1.234,56».
     *
     * @param array<int, string[]> $filas lo que devuelven `celdas()` o `deFichero()`
     * @param string $tipo uno de `TIPOS`
     * @param array $indice el de `indice()` (o uno de mentira, en las pruebas)
     * @param string $conValor '' (solo artículos), 'pct' (su %) o 'precio' (su precio)
     *
     * @return array{tipo: string, columna: int, columna_valor: int, filas: int, valores: string[],
     *               no: string[], sin_valor: string[], apagados: int,
     *               items: array<int, array{id_product: int, ipa: int, valor: ?float}>}
     *         `valores`: las filas tal cual, para guardarlas en la lista («EAN;13» si llevan valor);
     *         `no`: los que no se han encontrado; `sin_valor`: encontrados sin un valor que se entienda;
     *         `items`: sin repetir, con `ipa` 0 = el producto entero
     */
    public static function buscar(array $filas, string $tipo, array $indice, string $conValor = '', bool $conUnidades = false): array
    {
        $tipo = in_array($tipo, self::TIPOS, true) ? $tipo : 'auto';
        $conValor = in_array($conValor, ['pct', 'precio'], true) ? $conValor : '';
        $columnas = 0;
        foreach ($filas as $fila) {
            $columnas = max($columnas, count($fila));
        }
        // «8710255120850 13 %» (o «8710255120850 13 % 12», con sus unidades; o «8710255120850 12»)
        // en una sola columna: lo demás va detrás del código, separado por espacios.
        if (($conValor !== '' || $conUnidades) && $columnas === 1) {
            foreach ($filas as $i => $fila) {
                $trozos = preg_split('/\s+/u', trim((string) $fila[0])) ?: [];
                if (count($trozos) < 2) {
                    continue;
                }
                $codigo = (string) array_shift($trozos);
                $resto = implode(' ', $trozos);
                $nuevo = null;
                if ($conValor !== '' && $conUnidades && count($trozos) >= 2
                    && self::leerUnidades((string) end($trozos)) !== null
                    && self::leerValor(implode(' ', array_slice($trozos, 0, -1)), $conValor) !== null) {
                    $nuevo = [$codigo, implode(' ', array_slice($trozos, 0, -1)), (string) end($trozos)];
                } elseif ($conValor !== '' && self::leerValor($resto, $conValor) !== null) {
                    $nuevo = [$codigo, $resto];
                } elseif ($conUnidades && self::leerUnidades($resto) !== null) {
                    $nuevo = [$codigo, $resto];
                }
                if ($nuevo !== null) {
                    $filas[$i] = $nuevo;
                    $columnas = max($columnas, count($nuevo));
                }
            }
        }

        // La columna y la manera de leerla que más encuentran.
        $mejor = ['columna' => 0, 'tipo' => $tipo === 'auto' ? 'codigo' : $tipo, 'aciertos' => -1];
        for ($c = 0; $c < $columnas; ++$c) {
            $valores = array_map(static fn ($f) => (string) ($f[$c] ?? ''), $filas);
            // «producto-formato» y los enlaces se reconocen por su forma, se lean como se lean.
            foreach ($tipo === 'auto' ? ['codigo', 'id'] : [$tipo] as $como) {
                $aciertos = 0;
                foreach ($valores as $v) {
                    if ($v !== '' && self::uno($v, $como, $indice) !== []) {
                        ++$aciertos;
                    }
                }
                // A igualdad, gana «código»: un id de producto suelto es lo menos seguro.
                if ($aciertos > $mejor['aciertos']) {
                    $mejor = ['columna' => $c, 'tipo' => $como, 'aciertos' => $aciertos];
                }
            }
        }

        $colValor = $conValor !== '' ? self::columnaDelValor($filas, $mejor['columna'], $columnas, $conValor) : -1;
        $colUnidades = $conUnidades ? self::columnaDeUnidades($filas, $mejor['columna'], $colValor, $columnas) : -1;

        $salida = ['tipo' => $mejor['tipo'], 'columna' => $mejor['columna'] + 1, 'columna_valor' => $colValor + 1, 'columna_unidades' => $colUnidades + 1,
            'filas' => 0, 'valores' => [], 'no' => [], 'sin_valor' => [], 'sin_unidades' => [], 'apagados' => 0, 'items' => []];
        $vistos = [];
        foreach ($filas as $n => $fila) {
            $valor = trim((string) ($fila[$mejor['columna']] ?? ''));
            if ($valor === '') {
                continue;
            }
            $encontrados = self::uno($valor, $mejor['tipo'], $indice);
            // La cabecera («EAN», «Referencia»…) no es un artículo que falte.
            if ($n === 0 && $encontrados === [] && !preg_match('/\d/', $valor)) {
                continue;
            }
            ++$salida['filas'];
            $crudo = $colValor >= 0 ? trim((string) ($fila[$colValor] ?? '')) : '';
            $crudoUnidades = $colUnidades >= 0 ? trim((string) ($fila[$colUnidades] ?? '')) : '';
            $partes = [$valor];
            if ($conValor !== '') {
                $partes[] = $crudo;
            }
            if ($conUnidades) {
                $partes[] = $crudoUnidades;
            }
            $salida['valores'][] = implode(';', $partes);
            if ($encontrados === []) {
                $salida['no'][] = $valor;
                continue;
            }
            $leido = $conValor !== '' ? self::leerValor($crudo, $conValor) : null;
            if ($conValor !== '' && $leido === null) {
                $salida['sin_valor'][] = $valor . ($crudo !== '' ? ' (' . $crudo . ')' : '');
            }
            $unidades = $conUnidades ? self::leerUnidades($crudoUnidades) : null;
            if ($conUnidades && $unidades === null) {
                $salida['sin_unidades'][] = $valor . ($crudoUnidades !== '' ? ' (' . $crudoUnidades . ')' : '');
            }
            foreach ($encontrados as [$idProducto, $ipa]) {
                // Apagado, oculto, que no se puede pedir, o un formato apagado: se apunta, pero no entra.
                if (empty($indice['producto'][$idProducto]['activo']) || isset($indice['apagada'][$ipa])) {
                    ++$salida['apagados'];
                }
                $clave = $idProducto . '-' . $ipa;
                if (!isset($vistos[$clave])) {
                    $vistos[$clave] = count($salida['items']);
                    $salida['items'][] = ['id_product' => $idProducto, 'ipa' => $ipa, 'valor' => $leido, 'unidades' => $unidades];
                } else {
                    // Repetido: vale el último valor que se entienda; las unidades se suman (dos lotes).
                    if ($leido !== null) {
                        $salida['items'][$vistos[$clave]]['valor'] = $leido;
                    }
                    if ($unidades !== null) {
                        $salida['items'][$vistos[$clave]]['unidades'] = (int) ($salida['items'][$vistos[$clave]]['unidades'] ?? 0) + $unidades;
                    }
                }
            }
        }

        return $salida;
    }

    /**
     * Un descuento o un precio escrito a la española, a la inglesa o como lo deja Excel.
     *
     * @param string $como 'pct' (de 0 a 90, sin incluir el 0) o 'precio' (mayor que 0)
     */
    public static function leerValor(string $texto, string $como): ?float
    {
        $t = trim($texto);
        if ($t === '') {
            return null;
        }
        $conSigno = strpos($t, '%') !== false;
        $t = str_replace(['%', '€', 'EUR', 'eur', ' ', "\u{00A0}"], '', $t);
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $t)) {
            $t = str_replace('.', '', $t); // 1.234,56
        }
        $t = str_replace(',', '.', $t);
        if (!is_numeric($t)) {
            return null;
        }
        $v = (float) $t;
        if ($como === 'pct') {
            // Una celda de Excel con formato de porcentaje llega como 0,13.
            if ($v > 0 && $v < 1 && !$conSigno) {
                $v *= 100;
            }

            return $v > 0 && $v <= 90 ? round($v, 3) : null;
        }

        return $v > 0 ? round($v, 2) : null;
    }

    /** Unas unidades escritas como sea: «12», «12,0», «12 uds», «1.200»; nada si no se entiende. */
    public static function leerUnidades(string $texto): ?int
    {
        $t = mb_strtolower(trim($texto));
        $t = preg_replace('/\s*(unidades|unid\.?|uds?\.?|u\.?|pcs?\.?)$/u', '', $t) ?? $t;
        $t = str_replace([' ', "\u{00A0}", '.'], '', $t);
        if (preg_match('/^(\d{1,6})(,0+)?$/', $t, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * La columna de las unidades. Manda la cabecera («Unidades», «Uds.», «Cantidad», «Cupo»,
     * «Stock»); sin ella, la columna con más enteros de las que no son ni el código ni el valor (a
     * igualdad, la última: lo normal es EAN, descuento, unidades).
     *
     * @return int la columna (desde 0), o −1 si no hay ninguna
     */
    private static function columnaDeUnidades(array $filas, int $colId, int $colValor, int $columnas): int
    {
        $mejor = [-1, 0, 0];
        for ($c = 0; $c < $columnas; ++$c) {
            if ($c === $colId || $c === $colValor) {
                continue;
            }
            $buenos = 0;
            foreach ($filas as $fila) {
                if (self::leerUnidades((string) ($fila[$c] ?? '')) !== null) {
                    ++$buenos;
                }
            }
            $cabecera = (string) ($filas[0][$c] ?? '');
            $pista = !preg_match('/\d/', $cabecera) && preg_match('/unid|uds|cant|cupo|stock|qty/i', $cabecera) ? 1 : 0;
            if ($buenos > 0 && [$pista, $buenos] >= [$mejor[2], $mejor[1]]) {
                $mejor = [$c, $buenos, $pista];
            }
        }

        return $mejor[0];
    }

    /**
     * La columna del descuento o del precio. Manda la cabecera: «Dto.» o «%» para un descuento;
     * «Precio promo», «Oferta» o «Final» antes que «PVP» para un precio (un Excel de proveedor
     * suele traer los dos, y el bueno es el de la promo aunque alguna celda diga «consultar»). Sin
     * cabecera que lo diga, la que más valores buenos tiene.
     *
     * @return int la columna (desde 0), o −1 si no hay ninguna
     */
    private static function columnaDelValor(array $filas, int $colId, int $columnas, string $como): int
    {
        $pistas = $como === 'pct'
            ? ['/dto|desc|%|porc/i' => 2]
            : ['/promo|oferta|final|especial|nuevo/i' => 2, '/precio|pvp|importe/i' => 1];
        $mejor = [-1, 0, 0];
        for ($c = 0; $c < $columnas; ++$c) {
            if ($c === $colId) {
                continue;
            }
            $buenos = 0;
            foreach ($filas as $fila) {
                if (self::leerValor((string) ($fila[$c] ?? ''), $como) !== null) {
                    ++$buenos;
                }
            }
            $cabecera = (string) ($filas[0][$c] ?? '');
            $pista = 0;
            foreach ($pistas as $patron => $puntos) {
                if (!preg_match('/\d/', $cabecera) && preg_match($patron, $cabecera)) {
                    $pista = max($pista, $puntos);
                }
            }
            if ($buenos > 0 && [$pista, $buenos] > [$mejor[2], $mejor[1]]) {
                $mejor = [$c, $buenos, $pista];
            }
        }

        return $mejor[0];
    }

    /**
     * Lo que corresponde a un valor, leído de una manera.
     *
     * @return array<int, array{0: int, 1: int}> [id de producto, id de combinación (0 = todo el producto)]
     */
    public static function uno(string $valor, string $como, array $indice): array
    {
        $encontrados = self::leer($valor, $como, $indice);
        // «Boske 7214», «7214 2809»: si entero no es nada, palabra a palabra.
        if ($encontrados === [] && preg_match('/\s/u', trim($valor))) {
            foreach (preg_split('/\s+/u', trim($valor)) ?: [] as $trozo) {
                if (preg_match('/\d/', $trozo)) {
                    foreach (self::leer($trozo, $como, $indice) as $par) {
                        if (!in_array($par, $encontrados, true)) {
                            $encontrados[] = $par;
                        }
                    }
                }
            }
        }

        return $encontrados;
    }

    /** Un valor tal cual, leído de una manera. */
    private static function leer(string $valor, string $como, array $indice): array
    {
        $forma = self::pinta($valor);
        if ($forma === 'url') {
            // «…/545-824-royal-canin-renal.html»: producto y formato; «…/2809-eukanuba-….html»: producto.
            if (preg_match('~/(\d+)(?:-(\d+))?-[^/]*\.html~i', $valor, $m)) {
                return self::par((int) $m[1], (int) ($m[2] ?? 0), $indice);
            }

            return [];
        }
        if ($forma === 'combinacion') {
            [$idProducto, $ipa] = array_map('intval', explode('-', $valor));
            $par = self::par($idProducto, $ipa, $indice);

            return $par !== [] && $par[0][1] === $ipa ? $par : [];
        }

        switch ($como) {
            case 'id':
                return $forma === 'numero' && isset($indice['producto'][(int) $valor]) ? [[(int) $valor, 0]] : [];
            case 'combinacion':
                return [];
            default:
                // Un código: EAN o referencia, del formato primero y si no del producto.
                if ($forma === 'ean' || $forma === 'numero') {
                    $ean = self::ean($valor);
                    if ($ean !== '' && (isset($indice['ean'][$ean]) || isset($indice['eanP'][$ean]))) {
                        return self::sinPacksDeOtro($indice['ean'][$ean] ?? $indice['eanP'][$ean], $indice);
                    }
                }
                $ref = self::ref($valor);
                if ($ref !== '' && (isset($indice['ref'][$ref]) || isset($indice['refP'][$ref]))) {
                    return self::sinPacksDeOtro($indice['ref'][$ref] ?? $indice['refP'][$ref], $indice);
                }

                return [];
        }
    }

    /**
     * De los formatos con el mismo código, fuera los que son pack de otro de ellos. El «Pack 2 x
     * Saco de 10 Kg» lleva el EAN del saco: quien pasa ese EAN habla del saco, y el valor que trae
     * (un % o un precio de coste) es el del saco, no el del pack.
     *
     * @param array<int, array{0: int, 1: int}> $encontrados [id de producto, id de combinación]
     */
    private static function sinPacksDeOtro(array $encontrados, array $indice): array
    {
        if (count($encontrados) < 2) {
            return $encontrados;
        }
        $ipas = array_map(static fn ($par) => (int) $par[1], $encontrados);

        return array_values(array_filter($encontrados, static function ($par) use ($ipas, $indice) {
            $arriba = $indice['padre'][(int) $par[1]] ?? null;
            $vistos = [];
            while ($arriba !== null && !isset($vistos[$arriba])) {
                if (in_array($arriba, $ipas, true)) {
                    return false;
                }
                $vistos[$arriba] = true;
                $arriba = $indice['padre'][$arriba] ?? null;
            }

            return true;
        }));
    }

    /** Producto y formato, si existen y son del mismo producto; si el formato no, el producto entero. */
    private static function par(int $idProducto, int $ipa, array $indice): array
    {
        if (!isset($indice['producto'][$idProducto])) {
            return [];
        }
        if ($ipa > 0 && (int) ($indice['combinacion'][$ipa] ?? 0) === $idProducto) {
            return [[$idProducto, $ipa]];
        }

        return [[$idProducto, 0]];
    }

    /**
     * Todos los códigos de la tienda, de una vez: son unos pocos miles y así cada valor de la lista
     * se busca en memoria, sin una consulta por fila.
     *
     * @return array{ean: array, eanP: array, ref: array, refP: array, producto: array, combinacion: array,
     *               apagada: array, padre: array<int, int>} `padre`: de qué formato es pack cada pack
     */
    public static function indice(int $idTienda): array
    {
        static $hecho = [];
        if (isset($hecho[$idTienda])) {
            return $hecho[$idTienda];
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $indice = ['ean' => [], 'eanP' => [], 'ref' => [], 'refP' => [], 'producto' => [], 'combinacion' => [], 'apagada' => [], 'padre' => []];

        // «Activo» = se vende de verdad: encendido, visible y que se puede pedir (como lo mira kpycoupons).
        foreach ($db->executeS(
            "SELECT p.id_product, p.ean13, p.reference, p.supplier_reference, p.mpn,
                    IFNULL(ps.active = 1 AND ps.visibility IN ('both', 'catalog', 'search') AND ps.available_for_order = 1, 0) AS activo
             FROM {$p}product p
             LEFT JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = " . (int) $idTienda
        ) ?: [] as $f) {
            $id = (int) $f['id_product'];
            $indice['producto'][$id] = ['activo' => (bool) $f['activo']];
            self::apuntar($indice, $id, 0, $f);
        }
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $formatos = [];
        foreach ($db->executeS(
            "SELECT pa.id_product, pa.id_product_attribute AS ipa, pa.ean13, pa.reference, pa.supplier_reference, pa.mpn,
                    " . self::sqlApagado() . " AS apagado, GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre
             FROM {$p}product_attribute pa
             LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idioma}
             GROUP BY pa.id_product_attribute"
        ) ?: [] as $f) {
            $indice['combinacion'][(int) $f['ipa']] = (int) $f['id_product'];
            if ((int) $f['apagado']) {
                $indice['apagada'][(int) $f['ipa']] = true;
            }
            $formatos[(int) $f['id_product']][] = ['ipa' => (int) $f['ipa'], 'nombre' => (string) $f['nombre']];
            self::apuntar($indice, (int) $f['id_product'], (int) $f['ipa'], $f);
        }
        // Qué es pack de qué: el EAN del saco lo llevan también sus packs, y de la lista se queda el
        // saco (ver `sinPacksDeOtro()`).
        $packs = self::tablaPacks(array_keys($formatos));
        foreach ($formatos as $idProducto => $suyos) {
            if (count($suyos) < 2) {
                continue;
            }
            foreach (self::padres($suyos, $packs[$idProducto] ?? []) as $ipa => $padre) {
                $indice['padre'][$ipa] = $padre;
            }
        }
        foreach ($db->executeS(
            "SELECT id_product, id_product_attribute AS ipa, product_supplier_reference AS supplier_reference
             FROM {$p}product_supplier WHERE product_supplier_reference <> ''"
        ) ?: [] as $f) {
            self::apuntar($indice, (int) $f['id_product'], (int) $f['ipa'], $f);
        }

        return $hecho[$idTienda] = $indice;
    }

    /** Apunta los códigos de un producto o formato en el índice. */
    private static function apuntar(array &$indice, int $idProducto, int $ipa, array $f): void
    {
        $ean = self::ean((string) ($f['ean13'] ?? ''));
        if (strlen($ean) >= 6) {
            $indice[$ipa > 0 ? 'ean' : 'eanP'][$ean][] = [$idProducto, $ipa];
        }
        foreach (['reference', 'supplier_reference', 'mpn'] as $campo) {
            $ref = self::ref((string) ($f[$campo] ?? ''));
            if (strlen($ref) < 3) {
                continue;
            }
            $donde = $ipa > 0 ? 'ref' : 'refP';
            if (!in_array([$idProducto, $ipa], $indice[$donde][$ref] ?? [], true)) {
                $indice[$donde][$ref][] = [$idProducto, $ipa];
            }
        }
    }

    private static function limpiar(string $valor): string
    {
        return trim(trim($valor), "\"' \u{00A0}");
    }

    // ---------- lo que `indice()` pedía a otras clases de kpycoupons, traído aquí ----------

    /**
     * SQL: si el formato está apagado en la tabla de Julián (`kpy_product_attribute.active = 0`);
     * '0' si la tabla no existe. (Copia de `KpcAlcance::sqlApagado()`.)
     */
    private static function sqlApagado(): string
    {
        return KsVentas::hayTabla('kpy_product_attribute')
            ? 'EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'kpy_product_attribute k WHERE k.id_product_attribute = pa.id_product_attribute AND k.active = 0)'
            : '0';
    }

    /**
     * La tabla de packs de Kompy (`kpy_packs`): qué pack lleva N unidades de qué formato del mismo
     * producto. Vacío si la tabla no existe. (Copia de `KpcAlcance::tablaPacks()`.)
     *
     * @param int[] $productos
     *
     * @return array<int, array<int, array{ipa: int, n: int}>> id de producto => [id del pack => [formato que lleva, cuántos]]
     */
    private static function tablaPacks(array $productos): array
    {
        if ($productos === [] || !KsVentas::hayTabla('kpy_packs')) {
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
     * De qué formato es pack cada pack de un producto: por la tabla de packs y, si el pack no está
     * apuntado ahí, por el nombre («Pack 2 x Saco de 12 Kg» es dos del «Saco de 12 Kg» del mismo
     * producto; «Pack 24 x Lata de 70 gr» es el doble de «Pack 12 x Lata de 70 gr»). Hay que pasarle
     * **todos** los formatos del producto, también los apagados. (Copia de la parte del padre directo
     * de `KpcFormatos::niveles()`.)
     *
     * @param array<int, array{ipa: int, nombre?: string}> $combinaciones los del producto
     * @param array<int, array{ipa: int, n: int}> $packs los apuntados en `kpy_packs` para este
     *        producto: id de la combinación pack => [el formato que lleva, cuántos]
     *
     * @return array<int, int> id del pack => id del formato del que es pack
     */
    private static function padres(array $combinaciones, array $packs): array
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

        $salida = [];
        foreach ($padre as $ipa => [$arriba]) {
            if ($arriba !== null) {
                $salida[$ipa] = $arriba;
            }
        }

        return $salida;
    }

    /**
     * El tamaño de un formato, sin el pack ni el OUTLET: «Pack 2 x Saco de 12 Kg» → «saco 12 kg».
     * Vale igual para el suelto y sus packs. (Copia de `KpcFormatos::tamano()`.)
     */
    private static function tamano(string $nombre): string
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

    /** Cuántas unidades lleva un formato por su nombre: «Pack 2 x Pack 6 x Bolsa de 50 gr» = 12. (Copia de `KpcFormatos::unidades()`.) */
    private static function unidades(string $nombre): int
    {
        $n = self::normalizar($nombre);
        $total = 1;
        while (preg_match('/^(?:(?:pack|paquete)\s+(?:de\s+)?)?(\d{1,3})\s*(?:x\s*|(?=[a-z]))(.*)$/u', $n, $m) && self::tieneTamano($m[2])) {
            $total *= max(1, (int) $m[1]);
            $n = trim($m[2]);
        }

        return $total;
    }

    /** ¿Es un formato OUTLET? «Saco de 12 Kg - OUTLET». */
    private static function esOutlet(string $nombre): bool
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
    private static function normalizar(string $texto): string
    {
        $t = mb_strtolower(trim($texto));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;

        return trim($t);
    }
}
