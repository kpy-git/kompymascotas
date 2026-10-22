<?php
/**
 * Lee la ración diaria de un producto (kpy_product_rations_table.table, que es HTML: una o
 * varias tablas, a veces con un título encima y una nota debajo) y la deja lista para la
 * calculadora de la ficha: filas con su etiqueta («4 kg»), el texto de la ración («5 latas de
 * 50 g al día») y, si se puede, la cantidad en número (5 latas, 80 g) para calcular cuánto dura
 * cada formato.
 *
 * Además de las tablas sencillas («Peso del gato | Ración diaria»), entiende las de las tablas
 * oficiales de los fabricantes:
 *   - varias filas de cabecera: el modo arriba («Pérdida de peso») y la unidad debajo («Bolsitas
 *     al día»), que se añade a los números sueltos («3-4» → «3-4 bolsitas al día»);
 *   - celdas unidas (colspan y rowspan): si la primera fila de cabecera agrupa columnas («Gato
 *     esterilizado» sobre cuatro), cada grupo es una pestaña;
 *   - alimentación mixta: una columna «+» une la columna de su izquierda con cada una de las de su
 *     derecha («1 bolsita al día + 25-40 g al día»);
 *   - el título que va justo encima de una tabla («Solo alimentación húmeda») es el nombre de su
 *     pestaña, y lo que va entre paréntesis, su detalle.
 *
 * Si el HTML no trae tablas o no se entiende, `tablas` sale vacío y la ficha enseña el HTML
 * tal cual. No depende de PrestaShop (usa DOMDocument, que PrestaShop ya necesita).
 */
class KppRacion
{
    /** Un título de pestaña más largo que esto pasa su paréntesis al detalle. */
    private const PESTANA_LARGA = 30;

    /** Un número de ración: «3», «3,5», «1½», «½». */
    private const NUM = '(?:\d+(?:[.,]\d+)?\s*[½¼¾]?|[½¼¾])';

    /**
     * @return array{tablas: array<int, array{titulo: string, pestana: string, detalle: string, columnas: array<int, string>, filas: array<int, array{etiqueta: string, valores: array<int, string>, cantidades: array<int, ?array>}>}>, nota: string, html: string, html_tablas: string}
     */
    public static function leer(string $html): array
    {
        $html = trim($html);
        $salida = ['tablas' => [], 'nota' => '', 'html' => $html, 'html_tablas' => ''];
        if ($html === '' || stripos($html, '<table') === false) {
            return $salida;
        }

        $dom = new DOMDocument();
        $antes = libxml_use_internal_errors(true);
        $ok = $dom->loadHTML('<?xml encoding="utf-8"?><div id="kpp-r">' . $html . '</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($antes);
        $raiz = $ok ? $dom->getElementById('kpp-r') : null;
        if ($raiz === null) {
            return $salida;
        }

        // En orden de lectura: el texto corto justo encima de una tabla es su título; el resto,
        // la nota de debajo («Como máximo, 2 sobres al día.»).
        $piezas = [];
        self::recorrer($raiz, $piezas);
        $nota = [];
        $html = [];
        $encima = '';
        foreach ($piezas as $p) {
            if (is_string($p)) {
                if ($encima !== '') {
                    $nota[] = $encima;
                }
                $encima = $p;
                continue;
            }
            $titulo = self::esTitulo($encima) ? $encima : '';
            if ($titulo === '' && $encima !== '') {
                $nota[] = $encima;
            }
            $encima = '';
            $leidas = self::tabla($p, $titulo);
            if ($leidas !== []) {
                array_push($salida['tablas'], ...$leidas);
                $html[] = ($titulo !== '' ? '<p class="kpp-rac__tit">' . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</p>' : '')
                    . $dom->saveHTML($p);
            } elseif ($titulo !== '') {
                $nota[] = $titulo;
            }
        }
        if ($encima !== '') {
            $nota[] = $encima;
        }
        // Solo las tablas (la nota va aparte, debajo, y no se repite).
        $salida['html_tablas'] = implode('', $html);
        $salida['nota'] = implode(' ', $nota);

        return $salida;
    }

    /** Tablas y textos, en orden, aunque vengan dentro de otra etiqueta. */
    private static function recorrer(DOMNode $nodo, array &$piezas): void
    {
        foreach ($nodo->childNodes as $hijo) {
            if ($hijo->nodeName === 'table') {
                $piezas[] = $hijo;
            } elseif ($hijo instanceof DOMElement && $hijo->getElementsByTagName('table')->length > 0) {
                self::recorrer($hijo, $piezas);
            } else {
                $t = self::limpio($hijo->textContent);
                if ($t !== '') {
                    $piezas[] = $t;
                }
            }
        }
    }

    private static function esTitulo(string $t): bool
    {
        return $t !== '' && mb_strlen($t) <= 90 && !preg_match('/[.:;]$/u', $t);
    }

    /** @return array<int, array> Una tabla de la calculadora por cada grupo de columnas. */
    private static function tabla(DOMElement $tabla, string $titulo): array
    {
        $rejilla = self::rejilla($tabla);
        if (count($rejilla) < 2) {
            return [];
        }
        $ancho = max(array_map('count', $rejilla));
        if ($ancho < 2) {
            return [];
        }

        // Cabecera: las filas de arriba sin cantidades («Pérdida de peso», «Bolsitas al día»). Si la
        // primera ya las trae, se toma igual como cabecera (como siempre).
        $nc = 0;
        while ($nc < count($rejilla) - 1 && !self::conCantidades($rejilla[$nc], $ancho)) {
            ++$nc;
        }
        $nc = max(1, $nc);
        $cabecera = array_slice($rejilla, 0, $nc);
        $datos = array_slice($rejilla, $nc);

        // Una tabla de composición metida con la ración («Proteína bruta | 26 %») no es ración.
        $valores = 0;
        $analitica = 0;
        foreach ($rejilla as $f) {
            for ($c = 1; $c < $ancho; ++$c) {
                if ($f[$c]['t'] !== '') {
                    ++$valores;
                    $analitica += preg_match('/%|kcal|mg\s*\/\s*kg/iu', $f[$c]['t']) ? 1 : 0;
                }
            }
        }
        if ($valores > 0 && $analitica * 2 >= $valores) {
            return [];
        }

        // Qué pregunta la tabla: lo de la primera columna de la cabecera («Peso adulto»).
        $pregunta = '';
        foreach ($cabecera as $f) {
            if ($f[0]['t'] !== '') {
                $pregunta = rtrim($f[0]['t'], ' *');
                break;
            }
        }
        if ($pregunta === '') {
            $pregunta = 'Peso';
        }

        // Grupos: si la primera fila de cabecera reparte las columnas en bloques de dos o más con
        // nombre distinto («Gato esterilizado | Gato no esterilizado»), cada uno es una pestaña.
        $grupos = [['nombre' => '', 'columnas' => range(1, $ancho - 1)]];
        $desde = 0;
        if ($nc >= 2) {
            $bloques = [];
            for ($c = 1; $c < $ancho; ++$c) {
                $id = $cabecera[0][$c]['id'];
                $n = count($bloques);
                if ($n > 0 && $bloques[$n - 1]['id'] === $id) {
                    $bloques[$n - 1]['columnas'][] = $c;
                } else {
                    $bloques[] = ['id' => $id, 'nombre' => $cabecera[0][$c]['t'], 'columnas' => [$c]];
                }
            }
            $nombres = array_column($bloques, 'nombre');
            $agrupa = count($bloques) >= 2 && !in_array('', $nombres, true)
                && count(array_unique($nombres)) === count($nombres);
            foreach ($bloques as $b) {
                $agrupa = $agrupa && count($b['columnas']) >= 2;
            }
            if ($agrupa) {
                $grupos = array_map(fn ($b) => ['nombre' => $b['nombre'], 'columnas' => $b['columnas']], $bloques);
                $desde = 1;
            }
        }

        // Columnas «+» (sin contar la fila de los grupos): unen la de su izquierda con las de su
        // derecha.
        $mas = [];
        for ($c = 1; $c < $ancho; ++$c) {
            $hay = false;
            $solo = true;
            foreach ($datos as $f) {
                $t = $f[$c]['t'];
                $hay = $hay || $t === '+';
                $solo = $solo && ($t === '+' || $t === '');
            }
            for ($h = $desde; $h < $nc; ++$h) {
                $solo = $solo && in_array($cabecera[$h][$c]['t'], ['+', ''], true);
            }
            $mas[$c] = $hay && $solo;
        }

        // Nombre de la pestaña: el título de encima, o el del grupo, o la pregunta (como antes).
        [$pestanaTitulo, $detalleTitulo] = self::partirTitulo($titulo);

        $salida = [];
        foreach ($grupos as $g) {
            $modos = self::modos($g['columnas'], $mas);
            if ($modos === []) {
                continue;
            }
            $etiquetas = [];
            $unidades = [];
            foreach ($g['columnas'] as $c) {
                [$etiquetas[$c], $unidades[$c]] = self::rotulo($cabecera, $c, $desde);
            }
            // «Hembra: peso actual | Hembra: gramos al día»: una sola opción, la ración con el
            // peso entre paréntesis.
            $pesoDe = [];
            $juntos = [];
            for ($i = 0, $n = count($modos); $i < $n; ++$i) {
                $m = $modos[$i];
                $sig = $modos[$i + 1] ?? null;
                if ($sig !== null && count($m) === 1 && count($sig) === 1
                    && preg_match('/\bpeso\b/iu', $unidades[$m[0]])
                    && implode('', $etiquetas[$m[0]]) !== ''
                    && $etiquetas[$m[0]] === $etiquetas[$sig[0]]) {
                    $pesoDe[count($juntos)] = $m[0];
                    $juntos[] = $sig;
                    ++$i;
                    continue;
                }
                $juntos[] = $m;
            }
            $modos = $juntos;
            $columnas = [];
            foreach ($modos as $m) {
                $columnas[] = self::nombreModo($m, $modos, $etiquetas, $cabecera, $desde);
            }
            $filas = [];
            foreach ($datos as $f) {
                $etiqueta = $f[0]['t'];
                if ($etiqueta === '') {
                    continue;
                }
                $valores = [];
                foreach ($modos as $i => $m) {
                    $partes = [];
                    foreach ($m as $c) {
                        $v = self::conUnidad($f[$c]['t'], $unidades[$c]);
                        if ($v !== '') {
                            $partes[] = $v;
                        }
                    }
                    $valor = implode(' + ', $partes);
                    if (isset($pesoDe[$i]) && $valor !== '' && $f[$pesoDe[$i]]['t'] !== '') {
                        $valor .= ' (' . $f[$pesoDe[$i]]['t'] . ')';
                    }
                    $valores[] = $valor;
                }
                if (implode('', $valores) === '') {
                    continue;
                }
                $filas[] = [
                    'etiqueta' => $etiqueta,
                    'valores' => $valores,
                    'cantidades' => array_map([self::class, 'cantidad'], $valores),
                ];
            }
            if ($filas === []) {
                continue;
            }
            if ($g['nombre'] !== '') {
                $pestana = $g['nombre'];
                $detalle = $titulo;
            } else {
                $pestana = $pestanaTitulo !== '' ? $pestanaTitulo : $pregunta;
                $detalle = $detalleTitulo;
            }
            $salida[] = [
                'titulo' => $pregunta,
                'pestana' => $pestana,
                'detalle' => $detalle,
                'columnas' => $columnas,
                'filas' => $filas,
            ];
        }

        return $salida;
    }

    /**
     * La tabla como rejilla: cada celda en su columna, también las unidas (colspan, rowspan).
     *
     * @return array<int, array<int, array{t: string, id: string}>>
     */
    private static function rejilla(DOMElement $tabla): array
    {
        $filas = [];
        $bajan = [];
        $r = 0;
        foreach ($tabla->getElementsByTagName('tr') as $tr) {
            $fila = [];
            $c = 0;
            foreach ($tr->childNodes as $n) {
                if ($n->nodeName !== 'td' && $n->nodeName !== 'th') {
                    continue;
                }
                while (isset($bajan[$c])) {
                    $fila[$c] = $bajan[$c]['celda'];
                    if (--$bajan[$c]['quedan'] <= 0) {
                        unset($bajan[$c]);
                    }
                    ++$c;
                }
                $celda = ['t' => self::limpio($n->textContent), 'id' => $r . ':' . $c];
                $ancho = max(1, min(20, (int) $n->getAttribute('colspan')));
                $alto = max(1, min(50, (int) $n->getAttribute('rowspan')));
                for ($k = 0; $k < $ancho; ++$k) {
                    $fila[$c] = $celda;
                    if ($alto > 1) {
                        $bajan[$c] = ['celda' => $celda, 'quedan' => $alto - 1];
                    }
                    ++$c;
                }
            }
            // Las que bajan de arriba a la derecha de la última celda de esta fila.
            foreach ($bajan as $col => $b) {
                if ($col >= $c && !isset($fila[$col])) {
                    $fila[$col] = $b['celda'];
                    if (--$bajan[$col]['quedan'] <= 0) {
                        unset($bajan[$col]);
                    }
                }
            }
            ksort($fila);
            $vacia = true;
            foreach ($fila as $celdaFila) {
                $vacia = $vacia && $celdaFila['t'] === '';
            }
            if (!$vacia) {
                $filas[] = $fila;
            }
            ++$r;
        }
        $ancho = $filas === [] ? 0 : max(array_map(fn ($f) => max(array_keys($f)) + 1, $filas));
        foreach ($filas as $i => $f) {
            for ($c = 0; $c < $ancho; ++$c) {
                $filas[$i][$c] ??= ['t' => '', 'id' => 'v' . $i . ':' . $c];
            }
            ksort($filas[$i]);
        }

        return $filas;
    }

    private static function conCantidades(array $fila, int $ancho): bool
    {
        for ($c = 1; $c < $ancho; ++$c) {
            $t = $fila[$c]['t'] ?? '';
            if ((preg_match('/\d/u', $t) && !preg_match('/\b(mes|meses|semanas?|años?|edad)\b/iu', $t))
                || preg_match('/^a voluntad$/iu', $t)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Las columnas que se enseñan como una opción: sola, o «izquierda + derecha» en la mixta.
     *
     * @return array<int, array<int, int>>
     */
    private static function modos(array $columnas, array $mas): array
    {
        $modos = [];
        $n = count($columnas);
        for ($k = 0; $k < $n; ++$k) {
            $c = $columnas[$k];
            if ($mas[$c]) {
                continue;
            }
            if ($k + 1 < $n && $mas[$columnas[$k + 1]]) {
                $derecha = [];
                $j = $k + 2;
                while ($j < $n && !$mas[$columnas[$j]]) {
                    $derecha[] = $columnas[$j];
                    ++$j;
                }
                foreach ($derecha as $d) {
                    $modos[] = [$c, $d];
                }
                if ($derecha === []) {
                    $modos[] = [$c];
                }
                $k = $j - 1;
                continue;
            }
            $modos[] = [$c];
        }

        return $modos;
    }

    /**
     * Rótulo y unidad de una columna, de sus filas de cabecera: «Solo húmedo: sobres al día» →
     * [«Solo húmedo», «sobres al día»]; «Pérdida de peso» sobre «Bolsitas al día» → [«Pérdida de
     * peso», «Bolsitas al día»].
     *
     * @return array{0: array<int, string>, 1: string} rótulos por fila de cabecera y unidad
     */
    private static function rotulo(array $cabecera, int $c, int $desde): array
    {
        $rotulos = [];
        $unidad = '';
        $vistos = [];
        for ($h = $desde, $n = count($cabecera); $h < $n; ++$h) {
            $celda = $cabecera[$h][$c];
            $t = $celda['t'];
            if ($t === '' || $t === '+' || isset($vistos[$celda['id']])) {
                $rotulos[$h] = '';
                continue;
            }
            $vistos[$celda['id']] = true;
            if (preg_match('/^(.+?):\s*(.+)$/u', $t, $m)) {
                $rotulos[$h] = rtrim($m[1], ' *');
                $unidad = $m[2];
            } elseif (self::esUnidad($t)) {
                $rotulos[$h] = '';
                $unidad = $t;
            } else {
                $rotulos[$h] = rtrim($t, ' *');
            }
        }

        return [$rotulos, $unidad];
    }

    private static function nombreModo(array $modo, array $modos, array $etiquetas, array $cabecera, int $desde): string
    {
        $unir = static fn (array $r): string => implode(' · ', array_values(array_unique(array_filter($r, fn ($x) => $x !== ''))));
        $c = $modo[count($modo) - 1];
        // Varias opciones que comparten la izquierda («húmeda +»): se nombran por lo que las
        // distingue («Pérdida de peso», «Mantenimiento»).
        if (count($modo) > 1) {
            $hermanas = array_values(array_filter($modos, fn ($m) => count($m) > 1 && $m[0] === $modo[0]));
            if (count($hermanas) > 1) {
                $distinto = [];
                foreach ($etiquetas[$c] as $h => $t) {
                    $otros = array_map(fn ($m) => $etiquetas[$m[count($m) - 1]][$h] ?? '', $hermanas);
                    if ($t !== '' && count(array_unique($otros)) > 1) {
                        $distinto[] = $t;
                    }
                }
                if ($distinto !== []) {
                    return implode(' · ', $distinto);
                }
            }
            $izq = $unir($etiquetas[$modo[0]]);
            $der = $unir($etiquetas[$c]);
            if ($izq === $der || $der === '') {
                return $izq !== '' ? $izq : self::textoCabecera($cabecera, $c, $desde);
            }

            return $izq !== '' ? $izq . ' + ' . $der : $der;
        }
        $nombre = $unir($etiquetas[$c]);

        return $nombre !== '' ? $nombre : self::textoCabecera($cabecera, $c, $desde);
    }

    /** La cabecera tal cual, para cuando no hay otro nombre (una columna solo con la unidad). */
    private static function textoCabecera(array $cabecera, int $c, int $desde): string
    {
        $t = [];
        for ($h = $desde, $n = count($cabecera); $h < $n; ++$h) {
            if (!in_array($cabecera[$h][$c]['t'], ['', '+'], true)) {
                $t[] = $cabecera[$h][$c]['t'];
            }
        }

        return implode(' · ', array_values(array_unique($t)));
    }

    private static function esUnidad(string $t): bool
    {
        return (bool) preg_match('/(al d[ií]a|por d[ií]a|\/\s*d[ií]a|^(g|gr|gramos|sobres?|bolsitas?|latas?|tarrinas?)$)/iu', $t);
    }

    /** «3-4» con «Bolsitas al día» → «3-4 bolsitas al día»; «1» → «1 bolsita al día». */
    private static function conUnidad(string $v, string $unidad): string
    {
        if ($v === '' || $v === '+' || $unidad === '') {
            return $v === '+' ? '' : $v;
        }
        $u = mb_strtolower($unidad);
        $u = preg_replace('/\bgramos\b/u', 'g', $u);
        if (preg_match('/^' . self::NUM . '(?:\s*(?:-|–|a)\s*' . self::NUM . ')?$/u', $v)) {
            if ($v === '1') {
                $u = preg_replace('/^(\p{L}+?)s\b/u', '$1', $u);
            }

            return $v . ' ' . $u;
        }
        if (preg_match('/\d/u', $v) && !preg_match('/d[ií]a/u', $v) && preg_match('/al d[ií]a$/u', $u)) {
            return $v . ' al día';
        }

        return $v;
    }

    /** «Alimentación mixta (comida húmeda y pienso)» → [«Alimentación mixta», «comida húmeda y pienso»]. */
    private static function partirTitulo(string $t): array
    {
        if (mb_strlen($t) > self::PESTANA_LARGA && preg_match('/^(.+?)\s*\((.+)\)\s*$/u', $t, $m)) {
            return [$m[1], $m[2]];
        }

        return [$t, ''];
    }

    private static function limpio(string $t): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $t)));
    }

    /**
     * «5 latas de 50 g al día» → 5 unidades; «3 ½ latas» → 3,5; «3-4 bolsitas» → 3,5; «80g» →
     * 80 g; «50-60 g» → 55 g. En la mixta («1 sobre + 20-45 g al día») lleva también la otra
     * (`alt`), para que el pienso diga cuánto le dura con los gramos.
     *
     * @return array{tipo: string, n: float, alt?: array{tipo: string, n: float}}|null
     */
    public static function cantidad(string $v): ?array
    {
        $v = mb_strtolower($v);
        $unidades = self::unidades($v);
        $gramos = self::gramos($v);
        if ($unidades !== null && $gramos !== null && str_contains($v, ' + ')) {
            return $unidades + ['alt' => $gramos];
        }

        return $unidades ?? $gramos;
    }

    private static function unidades(string $v): ?array
    {
        $envase = '(latas?|sobres?|tarrinas?|bolsitas?|barritas?|bandejas?)';
        if (preg_match('/(' . self::NUM . ')\s*(?:-|–|a)\s*(' . self::NUM . ')\s*' . $envase . '\b/u', $v, $m)) {
            $n = (self::numero($m[1]) + self::numero($m[2])) / 2;

            return $n > 0 ? ['tipo' => 'unidades', 'n' => $n] : null;
        }
        if (preg_match('/(' . self::NUM . '|1\/2)\s*' . $envase . '\b/u', $v, $m)) {
            $n = $m[1] === '1/2' ? 0.5 : self::numero($m[1]);

            return $n > 0 ? ['tipo' => 'unidades', 'n' => $n] : null;
        }

        return null;
    }

    /** «3» → 3; «3,5» → 3,5; «1½» o «1 ½» → 1,5; «¾» → 0,75. */
    private static function numero(string $s): float
    {
        $fracciones = ['½' => 0.5, '¼' => 0.25, '¾' => 0.75];
        $n = 0.0;
        foreach ($fracciones as $f => $valor) {
            if (str_contains($s, $f)) {
                $n += $valor;
                $s = str_replace($f, '', $s);
            }
        }
        $s = trim($s);

        return $n + ($s !== '' ? (float) str_replace(',', '.', $s) : 0.0);
    }

    private static function gramos(string $v): ?array
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:-|–|a)\s*(\d+(?:[.,]\d+)?)\s*(?:g|gr|grs|gramos)\b/u', $v, $m)) {
            $n = ((float) str_replace(',', '.', $m[1]) + (float) str_replace(',', '.', $m[2])) / 2;

            return $n > 0 ? ['tipo' => 'gramos', 'n' => $n] : null;
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:g|gr|grs|gramos)\b/u', $v, $m)) {
            $n = (float) str_replace(',', '.', $m[1]);

            return $n > 0 ? ['tipo' => 'gramos', 'n' => $n] : null;
        }

        return null;
    }

    /**
     * Cuántos días dura un formato con una ración. null si no se puede saber.
     *
     * @param array{tipo: string, n: float, alt?: array}|null $cantidad
     * @param array{gramos: ?float, unidades: ?int} $formato
     */
    public static function dias(?array $cantidad, array $formato): ?int
    {
        if ($cantidad === null || $cantidad['n'] <= 0) {
            return null;
        }
        if ($cantidad['tipo'] === 'unidades' && !empty($formato['unidades'])) {
            return (int) floor($formato['unidades'] / $cantidad['n']);
        }
        if ($cantidad['tipo'] === 'gramos' && !empty($formato['gramos'])) {
            return (int) floor($formato['gramos'] / $cantidad['n']);
        }

        return isset($cantidad['alt']) ? self::dias($cantidad['alt'], $formato) : null;
    }
}
