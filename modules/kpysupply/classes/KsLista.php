<?php
/**
 * La lista del proveedor, comprobada: qué artículo es cada fila y con qué valor, qué no se
 * encuentra, qué se repite, qué se cruza con otro acuerdo y qué precio no parece de la unidad de
 * venta. Todo puro (sin tienda), sobre lo que lee `KsLectura` (la copia propia del lector de kpycoupons); se prueba con
 * `tests/lista.php`.
 */
class KsLista
{
    /**
     * Las cabeceras que dicen dónde está el valor. Un precio de coste nunca sale de una columna de
     * PVP (los Excel de tarifa traen las dos).
     */
    public const PISTAS = [
        'pct' => ['si' => '/dto|desc|%|porc|rappel|redenc|bonif|abono/iu', 'no' => null],
        'precio' => ['si' => '/neto|coste|cost|compra|precio|tarifa|net\b/iu', 'no' => '/pvp|p\.v\.p|venta|recomend|retail|consumidor/iu'],
    ];

    /**
     * Comprueba una lista pegada (o subida) para un tipo de acuerdo.
     *
     * Dónde está el valor de cada fila (el % o el precio): en la columna que se elija; si no, en la
     * que su cabecera diga («Dto.», «%», «Neto», «Precio coste»…); y sin cabecera que lo diga, solo
     * si la lista es «código + valor» (dos columnas). Nunca a ojo entre varias columnas de números:
     * en un Excel de proveedor el formato en kg (9, 12, 7,5) parece un % y no lo es.
     *
     * @param array<int, string[]> $filas lo que devuelven `KsLectura::celdas()` o `deFichero()`
     * @param string $como 'pct' (redención, descuento en factura) o 'precio' (precio de coste)
     * @param float|null $general el % para las filas que no traen el suyo (null = no hay)
     * @param array $indice el de `KsLectura::indice()` (o uno de mentira, en las pruebas)
     * @param int|null $columna la columna del valor elegida a mano (desde 0; −1 = ninguna; null = sola)
     *
     * @return array{tipo: string, columna: int, columna_valor: int, motivo_valor: string, cabeceras: string[],
     *               filas: int, apagados: int, items: array<int, array{id_product: int, ipa: int, valor: float, codigo: string}>,
     *               no: string[], sin_valor: string[], repetidos: string[]}
     *         `columna` y `columna_valor` desde 1 (0 = ninguna); `motivo_valor`: `cabecera`, `dos`,
     *         `elegida`, `una` (código y valor en la misma celda) o `ninguna`; `cabeceras`: el nombre
     *         de cada columna (desde 0), para elegir; `items`: sin repetir (`ipa` 0 = el producto
     *         entero); `no`: lo que no se encuentra; `sin_valor`: encontrado pero sin un valor que se
     *         entienda (y sin general); `repetidos`: el mismo código dos veces con valores distintos
     */
    public static function comprobar(array $filas, string $como, ?float $general, array $indice, ?int $columna = null): array
    {
        $como = $como === 'precio' ? 'precio' : 'pct';
        $n = self::columnas($filas);
        $colCodigo = 0;
        $elegida = ['columna' => -1, 'motivo' => 'una'];
        $cabeceras = [];
        // Solo códigos (EAN, referencias, MPN): en una lista de proveedor no vienen nuestros ids, y
        // una columna de % (10, 15, 20) leída como ids podría ganarle a la de los EAN.
        if ($n >= 2) {
            $colCodigo = max(0, (int) KsLectura::buscar($filas, 'codigo', $indice, '')['columna'] - 1);
            $elegida = self::columnaValor($filas, $colCodigo, $como, $columna);
            $cabeceras = self::cabeceras($filas, $colCodigo);
            $reducidas = [];
            foreach ($filas as $fila) {
                $reducidas[] = $elegida['columna'] >= 0
                    ? [(string) ($fila[$colCodigo] ?? ''), (string) ($fila[$elegida['columna']] ?? '')]
                    : [(string) ($fila[$colCodigo] ?? '')];
            }
            $filas = $reducidas;
        }
        // Sin columna de valor (en una lista de varias), solo los códigos: todos con el general.
        $r = KsLectura::buscar($filas, 'codigo', $indice, $n >= 2 && $elegida['columna'] < 0 ? '' : $como);
        $salida = [
            'tipo' => (string) $r['tipo'],
            'columna' => $n >= 2 ? $colCodigo + 1 : (int) $r['columna'],
            'columna_valor' => $n >= 2 ? $elegida['columna'] + 1 : (int) $r['columna_valor'],
            'motivo_valor' => $n >= 2 ? $elegida['motivo'] : ((int) $r['columna_valor'] > 0 ? 'una' : 'ninguna'),
            'cabeceras' => $cabeceras,
            'filas' => (int) $r['filas'], 'apagados' => (int) $r['apagados'],
            'items' => [], 'no' => array_values((array) $r['no']), 'sin_valor' => [], 'repetidos' => [],
        ];
        $vistos = [];
        $valores = [];
        foreach ((array) $r['valores'] as $fila) {
            [$codigo, $crudo] = array_pad(explode(';', (string) $fila, 2), 2, '');
            $codigo = trim($codigo);
            $crudo = trim($crudo);
            if ($codigo === '') {
                continue;
            }
            $pares = KsLectura::uno($codigo, (string) $r['tipo'], $indice);
            if ($pares === []) {
                continue; // ya está en `no`
            }
            $propio = $crudo !== '' ? KsLectura::leerValor($crudo, $como) : null;
            $valor = $propio ?? $general;
            if ($valor === null) {
                $salida['sin_valor'][] = $codigo . ($crudo !== '' ? ' (' . $crudo . ')' : '');
                continue;
            }
            if (isset($valores[$codigo]) && abs($valores[$codigo] - $valor) > 0.000001) {
                $salida['repetidos'][] = $codigo . ' (' . self::pinta($valores[$codigo], $como) . ' y ' . self::pinta($valor, $como) . ': vale el último)';
            }
            $valores[$codigo] = $valor;
            foreach ($pares as [$idProducto, $ipa]) {
                $clave = $idProducto . '-' . $ipa;
                if (isset($vistos[$clave])) {
                    $salida['items'][$vistos[$clave]]['valor'] = $valor;
                    continue;
                }
                $vistos[$clave] = count($salida['items']);
                $salida['items'][] = ['id_product' => (int) $idProducto, 'ipa' => (int) $ipa, 'valor' => (float) $valor, 'codigo' => $codigo];
            }
        }

        return $salida;
    }

    /** Cuántas columnas tiene la lista (la fila más ancha, sin contar celdas vacías del final). */
    public static function columnas(array $filas): int
    {
        $n = 0;
        foreach ($filas as $fila) {
            $celdas = array_values((array) $fila);
            while ($celdas !== [] && trim((string) end($celdas)) === '') {
                array_pop($celdas);
            }
            $n = max($n, count($celdas));
        }

        return $n;
    }

    /**
     * El nombre de cada columna (desde 0): el de la cabecera si la primera fila lo es (en la
     * columna de los códigos no lleva cifras), o «columna N».
     *
     * @return string[]
     */
    public static function cabeceras(array $filas, int $colCodigo): array
    {
        $n = self::columnas($filas);
        $primera = array_values((array) ($filas[0] ?? []));
        $hay = $primera !== [] && !preg_match('/\d/', (string) ($primera[$colCodigo] ?? ''));
        $salida = [];
        for ($c = 0; $c < $n; ++$c) {
            $t = $hay ? trim((string) ($primera[$c] ?? '')) : '';
            $salida[$c] = $t !== '' ? $t : 'columna ' . ($c + 1);
        }

        return $salida;
    }

    /**
     * La columna del valor (desde 0; −1 = ninguna) y por qué: `elegida` a mano, `cabecera` (la que
     * su cabecera dice), `dos` (la lista es «código + valor») o `ninguna`.
     *
     * @return array{columna: int, motivo: string}
     */
    public static function columnaValor(array $filas, int $colCodigo, string $como, ?int $elegida): array
    {
        $n = self::columnas($filas);
        if ($elegida !== null) {
            return $elegida >= 0 && $elegida < $n && $elegida !== $colCodigo ? ['columna' => $elegida, 'motivo' => 'elegida'] : ['columna' => -1, 'motivo' => 'elegida'];
        }
        $pistas = self::PISTAS[$como === 'precio' ? 'precio' : 'pct'];
        $primera = array_values((array) ($filas[0] ?? []));
        $hayCabecera = $primera !== [] && !preg_match('/\d/', (string) ($primera[$colCodigo] ?? ''));
        $buenos = static function (int $c) use ($filas, $como, $hayCabecera): int {
            $n = 0;
            foreach ($filas as $i => $fila) {
                if ($i === 0 && $hayCabecera) {
                    continue;
                }
                if (KsLectura::leerValor((string) ($fila[$c] ?? ''), $como === 'precio' ? 'precio' : 'pct') !== null) {
                    ++$n;
                }
            }

            return $n;
        };
        if ($hayCabecera) {
            $mejor = [-1, -1];
            for ($c = 0; $c < $n; ++$c) {
                $h = trim((string) ($primera[$c] ?? ''));
                if ($c === $colCodigo || $h === '' || preg_match('/\d/', $h)) {
                    continue;
                }
                if ($pistas['no'] !== null && preg_match($pistas['no'], $h)) {
                    continue;
                }
                if (!preg_match($pistas['si'], $h)) {
                    continue;
                }
                $b = $buenos($c);
                if ($b > $mejor[1]) {
                    $mejor = [$c, $b];
                }
            }
            if ($mejor[0] >= 0) {
                return ['columna' => $mejor[0], 'motivo' => 'cabecera'];
            }
        }
        // Sin cabecera que lo diga: solo si, además del código, hay una sola columna con algo.
        $otras = [];
        for ($c = 0; $c < $n; ++$c) {
            if ($c === $colCodigo) {
                continue;
            }
            foreach ($filas as $i => $fila) {
                if (($i > 0 || !$hayCabecera) && trim((string) ($fila[$c] ?? '')) !== '') {
                    $otras[] = $c;
                    break;
                }
            }
        }
        if (count($otras) === 1 && $buenos($otras[0]) > 0) {
            return ['columna' => $otras[0], 'motivo' => 'dos'];
        }

        return ['columna' => -1, 'motivo' => 'ninguna'];
    }

    /** «13 %» o «12,34 €». */
    public static function pinta(float $valor, string $como): string
    {
        $t = rtrim(rtrim(number_format($valor, 6, ',', '.'), '0'), ',');

        return ($t === '' ? '0' : $t) . ($como === 'precio' ? ' €' : ' %');
    }

    /**
     * ¿Es la misma fila con el mismo acuerdo (mismas fechas, tipo y valor)? Volver a subir la misma
     * lista no es un solape: no se pregunta nada y no se toca.
     *
     * @param array $fila una fila de la tabla con `date_to` ya leído (null = sin fin)
     */
    public static function igual(array $fila, int $idTipo, float $valor, string $desde, ?string $hasta): bool
    {
        $hastaF = isset($fila['date_to']) && $fila['date_to'] !== null && $fila['date_to'] !== '' ? (string) $fila['date_to'] : null;

        return (string) $fila['date_from'] === $desde && $hastaF === $hasta
            && (int) $fila['id_supplier_promo_type'] === $idTipo && abs((float) $fila['value'] - $valor) < 0.000001;
    }

    /** ¿Se cruzan dos periodos? `null` en un «hasta» = sin fin. Las fechas, `Y-m-d H:i:s`. */
    public static function seCruzan(string $desdeA, ?string $hastaA, string $desdeB, ?string $hastaB): bool
    {
        return ($hastaA === null || $hastaA >= $desdeB) && ($hastaB === null || $hastaB >= $desdeA);
    }

    /**
     * Los solapes: para cada artículo de la lista, las filas que ya hay y se cruzan con el periodo.
     * Una fila del producto entero (ipa 0) se cruza con cualquiera de sus formatos, y al revés.
     *
     * @param array<int, array{id_product: int, ipa: int}> $items
     * @param array<int, array> $existentes filas de `kpy_supplier_promo_products` (con `id_product`,
     *                                      `id_product_attribute`, `date_from`, `date_to`)
     *
     * @return array<string, array<int, array>> clave «producto-ipa» => sus filas cruzadas
     */
    public static function solapes(array $items, string $desde, ?string $hasta, array $existentes): array
    {
        $porProducto = [];
        foreach ($existentes as $f) {
            $porProducto[(int) $f['id_product']][] = $f;
        }
        $salida = [];
        foreach ($items as $it) {
            $ipa = (int) $it['ipa'];
            foreach ($porProducto[(int) $it['id_product']] ?? [] as $f) {
                $ipaF = (int) $f['id_product_attribute'];
                if ($ipaF !== 0 && $ipa !== 0 && $ipaF !== $ipa) {
                    continue;
                }
                $hastaF = isset($f['date_to']) && $f['date_to'] !== null && $f['date_to'] !== '' ? (string) $f['date_to'] : null;
                if (!self::seCruzan($desde, $hasta, (string) $f['date_from'], $hastaF)) {
                    continue;
                }
                $salida[(int) $it['id_product'] . '-' . $ipa][] = $f;
            }
        }

        return $salida;
    }

    /**
     * Cómo queda una fila que ya existe cuando la nueva se pone encima («sustituir»): se borra si
     * la nueva la cubre entera; se recorta por delante, por detrás, o por los dos lados (y entonces
     * queda un trozo nuevo detrás).
     *
     * @return array{accion: string, date_from?: string, date_to?: string, nueva?: array{date_from: string, date_to: ?string}}
     */
    public static function recorte(array $fila, string $desde, ?string $hasta): array
    {
        $fDesde = (string) $fila['date_from'];
        $fHasta = isset($fila['date_to']) && $fila['date_to'] !== null && $fila['date_to'] !== '' ? (string) $fila['date_to'] : null;
        $antes = $fDesde < $desde;
        $despues = $hasta !== null && ($fHasta === null || $fHasta > $hasta);
        if (!$antes && !$despues) {
            return ['accion' => 'borrar'];
        }
        $salida = ['accion' => 'recortar'];
        if ($antes) {
            $salida['date_to'] = self::unSegundoAntes($desde);
        }
        if ($despues) {
            $trozo = ['date_from' => self::unSegundoDespues((string) $hasta), 'date_to' => $fHasta];
            if ($antes) {
                $salida['nueva'] = $trozo;
            } else {
                $salida['date_from'] = $trozo['date_from'];
            }
        }

        return $salida;
    }

    public static function unSegundoAntes(string $fecha): string
    {
        return date('Y-m-d H:i:s', strtotime($fecha) - 1);
    }

    public static function unSegundoDespues(string $fecha): string
    {
        return date('Y-m-d H:i:s', strtotime($fecha) + 1);
    }

    /**
     * Un aviso cuando un precio de coste nuevo no parece de la unidad de venta (comparado con el
     * coste que tiene PrestaShop para ese formato). Vacío si no hay nada que decir.
     */
    public static function avisoCoste(float $nuevo, float $actual): string
    {
        if ($actual <= 0 || $nuevo <= 0) {
            return '';
        }
        if ($nuevo > $actual * 1.5) {
            return sprintf('%s frente a %s de coste actual en PrestaShop: ¿es el precio de la caja?', self::pinta($nuevo, 'precio'), self::pinta($actual, 'precio'));
        }
        if ($nuevo > $actual) {
            return sprintf('sube: %s frente a %s de coste actual', self::pinta($nuevo, 'precio'), self::pinta($actual, 'precio'));
        }

        return '';
    }

    /**
     * Un texto pegado en filas y columnas, como lo deja Excel (tabuladores), para guardarlo en el
     * formulario entre «Comprobar» y «Guardar» sin volver a pedir el fichero.
     *
     * @param array<int, string[]> $filas
     */
    public static function aTexto(array $filas): string
    {
        $lineas = [];
        foreach ($filas as $fila) {
            $lineas[] = implode("\t", array_map(static fn ($c): string => str_replace(["\t", "\r", "\n"], ' ', (string) $c), $fila));
        }

        return implode("\n", $lineas);
    }
}
