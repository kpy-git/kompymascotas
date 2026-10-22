<?php
/**
 * Lee el texto de ingredientes de un producto (kpy_product_ingredients.text) y saca lo que la
 * ficha enseña además del texto: el titular del ingrediente principal («70 % pescado recién
 * preparado»), la barra con los ingredientes que llevan porcentaje, cuánto texto va en negrita
 * (los tres primeros) y los aditivos aparte.
 *
 * No depende de PrestaShop: se prueba suelto con `php tests/lectores.php`.
 *
 * Regla de oro: si algo no se entiende, se devuelve el texto tal cual y sin barra. La ficha nunca
 * enseña un dato inventado ni se rompe porque el texto venga raro.
 */
class KppIngredientes
{
    private const PCT = '(\d{1,3}(?:[.,]\d+)?)\s*%';

    /**
     * Las categorías de la etiqueta (Reglamento 767/2009), que se declaran sin porcentaje propio:
     * el de dentro es el que cuenta («Carnes y subproductos animales (pollo 43 %)»).
     */
    private const CATEGORIA = '(?:carnes? y subproductos animales|pescados? y subproductos de pescado|subproductos de origen vegetal|cereales|hortalizas|legumbres|extractos de prote[ií]nas vegetales|leche y productos l[aá]cteos|huevos? y (?:productos derivados|ovoproductos)|moluscos y crust[aá]ceos|aceites y grasas|verduras|frutas|semillas|levaduras)';

    /**
     * @return array{texto: string, negrita: string, resto: string, titular: string, barra: array<int, array{nombre: string, pct: float, pct_txt: string}>, aditivos: string}
     */
    public static function leer(string $bruto): array
    {
        $texto = self::limpiar($bruto);
        $vacio = ['texto' => $texto, 'negrita' => '', 'resto' => $texto, 'titular' => '', 'barra' => [], 'aditivos' => ''];
        if ($texto === '') {
            return $vacio;
        }

        [$principal, $aditivos] = self::separarAditivos($texto);
        $principal = preg_replace('/^\s*(composici[oó]n|ingredientes)\s*:\s*/iu', '', $principal);

        $trozos = self::trocear($principal);
        if ($trozos === []) {
            return ['aditivos' => $aditivos, 'resto' => $principal, 'texto' => $principal] + $vacio;
        }

        // Titular: «70 % de pescado recién preparado: …» al principio del texto.
        $titular = '';
        if (preg_match('/^\s*' . self::PCT . '\s*(?:de\s+)?([^:,(]{3,60}?)\s*:/u', $principal, $m)) {
            $titular = self::numero($m[1]) . ' % ' . self::minuscula(trim($m[2]));
        }

        // Barra: ingredientes de primer nivel con su porcentaje, de 5 % o más, hasta 4 y sin
        // pasar de 100 entre todos. Solo si el PRIMER ingrediente declara el suyo: si no, lo que
        // lleva porcentaje no es lo principal («Cebada, maíz, pollo 17 %…») y la barra engañaría.
        // Si el primero es una categoría de la etiqueta, vale lo de dentro («Carnes y subproductos
        // animales (pollo 43 %)» → 43 % pollo).
        // Si detrás de las categorías viene la lista detallada («… grasas. Ingredientes: con pollo:
        // hígado de cerdo, pollo (9 %)…»), la barra sale de esa, que dice cuál va primero de verdad.
        $lista = $trozos;
        $conTitular = $titular !== '';
        if (preg_match('/[.;]\s*ingredientes\s*:\s*(?:(?:con\s+)?[^:,.\d]{2,40}:\s*(?=\D))?(.+)$/iu', $principal, $m)) {
            $detalle = self::trocear($m[1]);
            if ($detalle !== []) {
                $lista = $detalle;
                $conTitular = false;
            }
        }
        $barra = [];
        $suma = 0.0;
        foreach (array_slice($lista, 0, 8) as $i => $t) {
            $texto = $t['texto'];
            if ($i === 0 && $conTitular) {
                // «70 % de pescado recién preparado: pescado blanco (52,5 %)» → lo de después de «:»
                $texto = preg_replace('/^[^:]*:\s*/u', '', $texto);
            }
            $pares = array_values(array_filter(self::paresDe($texto), fn ($p) => $p['pct'] >= 5));
            if ($i === 0 && $pares === []) {
                break;
            }
            foreach ($pares as $par) {
                if (in_array(mb_strtolower($par['nombre']), array_map(fn ($b) => mb_strtolower($b['nombre']), $barra), true)) {
                    continue;
                }
                if ($suma + $par['pct'] > 100.001 || count($barra) === 4) {
                    break 2;
                }
                $suma += $par['pct'];
                $barra[] = $par;
            }
        }
        if ($titular === '' && $barra !== []) {
            $titular = $barra[0]['pct_txt'] . ' ' . self::minuscula($barra[0]['nombre']);
        }

        // Negrita: hasta el final del tercer ingrediente de primer nivel.
        $corte = $trozos[min(2, count($trozos) - 1)]['fin'];

        return [
            'texto' => $principal,
            'negrita' => rtrim(mb_substr($principal, 0, $corte)),
            'resto' => mb_substr($principal, $corte),
            'titular' => $titular,
            'barra' => $barra,
            'aditivos' => $aditivos,
        ];
    }

    private static function limpiar(string $bruto): string
    {
        $t = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', $bruto)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_replace("\u{00A0}", ' ', $t);

        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    /** @return array{0: string, 1: string} */
    private static function separarAditivos(string $texto): array
    {
        if (preg_match('/\baditivos\b/iu', $texto, $m, PREG_OFFSET_CAPTURE) && $m[0][1] > 20) {
            $pos = mb_strlen(substr($texto, 0, $m[0][1]));

            return [rtrim(mb_substr($texto, 0, $pos), " .;"), trim(mb_substr($texto, $pos))];
        }

        return [$texto, ''];
    }

    /**
     * Parte el texto en ingredientes de primer nivel: corta por coma, punto y coma o punto, pero
     * no dentro de paréntesis ni en la coma decimal («52,5 %»).
     *
     * @return array<int, array{texto: string, fin: int}> `fin` es la posición (en caracteres)
     *         donde acaba el trozo en el texto original.
     */
    private static function trocear(string $texto): array
    {
        $letras = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY);
        $trozos = [];
        $nivel = 0;
        $actual = '';
        $n = count($letras);
        for ($i = 0; $i < $n; $i++) {
            $c = $letras[$i];
            if ($c === '(' || $c === '[') {
                $nivel++;
            } elseif (($c === ')' || $c === ']') && $nivel > 0) {
                $nivel--;
            }
            $decimal = ($c === ',' || $c === '.') && $i > 0 && $i < $n - 1
                && ctype_digit($letras[$i - 1]) && ctype_digit($letras[$i + 1]);
            if ($nivel === 0 && !$decimal && ($c === ',' || $c === ';' || $c === '.')) {
                if (trim($actual) !== '') {
                    $trozos[] = ['texto' => trim($actual), 'fin' => $i];
                }
                $actual = '';
                continue;
            }
            $actual .= $c;
        }
        if (trim($actual) !== '') {
            $trozos[] = ['texto' => trim($actual), 'fin' => $n];
        }

        return $trozos;
    }

    /**
     * Los ingredientes con porcentaje de un trozo de primer nivel: el suyo («salmón (17,5 %)») o, si
     * es una categoría de la etiqueta sin porcentaje, los de dentro («Carnes y subproductos animales
     * (pollo 19 %, cerdo 14 %)» → pollo 19 %, cerdo 14 %).
     *
     * @return array<int, array{nombre: string, pct: float, pct_txt: string}>
     */
    private static function paresDe(string $t): array
    {
        $par = self::ingredienteConPct($t);
        if ($par !== null) {
            return [$par];
        }
        if (!preg_match('/^' . self::CATEGORIA . '\s*\((.+)\)$/iu', trim($t), $m)) {
            return [];
        }
        $pares = [];
        foreach (self::trocear($m[1]) as $dentro) {
            // «incluido un 7 % de proteínas de pollo», «incluido pollo 4 %», «4 % de bacalao»
            $par = self::ingredienteConPct((string) preg_replace('/^(?:incluid[oa]s?|con)\s+(?:un\s+)?/iu', '', $dentro['texto']));
            if ($par !== null) {
                $pares[] = $par;
            }
        }

        return $pares;
    }

    /** @return array{nombre: string, pct: float, pct_txt: string}|null */
    private static function ingredienteConPct(string $t): ?array
    {
        $t = trim($t);
        // «salmón (17,5 %)», «harina de pescado (28 %, fuente de omega 3)»,
        // «Proteína de ave 35 % (pollo 22 %, pavo 13 %)» o «Pollo y pavo deshidratado: 35 %»
        if (preg_match('/^([^(\d][^(:]*?)\s*\(\s*' . self::PCT . '[^)]*\)/u', $t, $m)
            || preg_match('/^([^(\d][^(:]*?)\s*:?\s+' . self::PCT . '/u', $t, $m)) {
            return self::par($m[1], $m[2]);
        }
        // «25 % de arroz integral»
        if (preg_match('/^' . self::PCT . '\s*(?:de\s+)?([^(]+)/u', $t, $m)) {
            return self::par($m[2], $m[1]);
        }

        return null;
    }

    /** @return array{nombre: string, pct: float, pct_txt: string}|null */
    private static function par(string $nombre, string $pct): ?array
    {
        // El asterisco marca lo ecológico en la etiqueta: en la barra sobra.
        $nombre = trim($nombre, " \t:-*");
        if ($nombre === '' || mb_strlen($nombre) > 60) {
            return null;
        }

        return [
            'nombre' => mb_strtoupper(mb_substr($nombre, 0, 1)) . mb_substr($nombre, 1),
            'pct' => (float) str_replace(',', '.', $pct),
            'pct_txt' => self::numero($pct) . ' %',
        ];
    }

    private static function numero(string $n): string
    {
        return str_replace('.', ',', $n);
    }

    private static function minuscula(string $t): string
    {
        return mb_strtolower(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }
}
