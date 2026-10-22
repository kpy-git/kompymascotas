<?php
/**
 * Junta lo leído de la base de datos y lo deja como lo pinta la plantilla (`$kpp`).
 *
 * No depende de PrestaShop, para poder pintar la plantilla en local con datos reales
 * (`tests/pintar.php`) sin tienda.
 */
class KppFicha
{
    /**
     * @param array{
     *   nombre: string, referencia: string, ean13: string,
     *   marca: array{nombre: string, url: string}, categoria: array{nombre: string, url: string},
     *   caracteristicas: array<int, array{nombre: string, valor: string}>,
     *   beneficios: array<int, array{nombre: string, icono: string}>,
     *   ingredientes: string, composicion: array<int, array{nombre: string, valor: string}>,
     *   racion: string, combinaciones: array<int, array{id: int, nombre: string}>,
     *   descripcion: string, movil: bool
     * } $d
     */
    public static function montar(array $d): array
    {
        $car = KppCaracteristicas::repartir($d['caracteristicas']);
        $formatos = KppFormatos::leer($d['combinaciones']);
        $ing = KppIngredientes::leer($d['ingredientes']);
        $ing['sellos'] = KppCaracteristicas::sellos($d['caracteristicas']);
        $comp = KppComposicion::ordenar($d['composicion']);
        $racion = self::racion($d['racion'], $formatos);

        $fichas = $car['fichas'];
        $rango = self::rangoFormatos($formatos);
        if ($rango !== '') {
            $fichas[] = ['nombre' => 'Formatos', 'valor' => $rango, 'url' => ''];
        }
        if ($d['marca']['nombre'] !== '') {
            $fichas[] = ['nombre' => 'Marca', 'valor' => $d['marca']['nombre'], 'url' => $d['marca']['url']];
        }
        $fichas = array_map(fn ($f) => $f + ['url' => ''], $fichas);

        $descripcion = KppHtml::descripcion($d['descripcion'], $d['nombre']);

        $hay = [
            'caracteristicas' => $car['para'] !== [] || $fichas !== [],
            'beneficios' => $d['beneficios'] !== [],
            'ingredientes' => $ing['texto'] !== '',
            'composicion' => $comp['principales'] !== [] || $comp['resto'] !== [],
            'racion' => $racion['tablas'] !== [] || $racion['html'] !== '',
            'descripcion' => trim(strip_tags($descripcion, '<img>')) !== '',
        ];

        // En móvil, cerradas de entrada las secciones largas; en PC, todo abierto.
        $movil = $d['movil'];
        $abierta = [
            'ingredientes' => true,
            'composicion' => true,
            'racion' => !$movil,
            'descripcion' => !$movil,
        ];

        return [
            'nombre' => $d['nombre'],
            'hay' => $hay,
            'abierta' => $abierta,
            'para' => $car['para'],
            'fichas' => $fichas,
            'referencia' => $d['referencia'],
            'ean13' => $d['ean13'],
            'categoria' => $d['categoria'],
            'beneficios' => $d['beneficios'],
            'protege' => in_array(true, array_column($d['beneficios'], 'protege'), true),
            'ingredientes' => $ing,
            'composicion' => $comp,
            'racion' => $racion,
            'descripcion' => $descripcion,
            'resumen' => [
                'ingredientes' => self::resumenIngredientes($ing),
                'composicion' => self::resumenComposicion($comp),
                'descripcion' => self::primeraFrase($descripcion),
            ],
        ];
    }

    /** @param array<int, array{id: int, nombre: string, corto: string, gramos: ?float, unidades: ?int, envase: string}> $formatos */
    private static function racion(string $html, array $formatos): array
    {
        $r = KppRacion::leer($html);
        $r['fila'] = 0;
        $r['dias'] = [];
        $r['datos'] = '';
        if ($r['tablas'] === []) {
            return $r;
        }
        // De entrada, la fila del medio: el peso más típico, no el más pequeño.
        $filas = $r['tablas'][0]['filas'];
        $r['fila'] = intdiv(count($filas) - 1, 2);
        $r['dias'] = self::dias($filas[$r['fila']]['cantidades'][0] ?? null, $formatos);
        $r['datos'] = json_encode([
            'tablas' => $r['tablas'],
            'formatos' => array_map(fn ($f) => ['corto' => $f['corto'], 'gramos' => $f['gramos'], 'unidades' => $f['unidades']], $formatos),
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

        return $r;
    }

    /** @return array<int, array{formato: string, dias: int}> */
    public static function dias(?array $cantidad, array $formatos): array
    {
        $salida = [];
        foreach ($formatos as $f) {
            $d = KppRacion::dias($cantidad, $f);
            if ($d !== null && $d >= 1) {
                $salida[] = ['formato' => $f['corto'], 'dias' => $d, 'texto' => $f['corto'] . ': ' . $d . ($d === 1 ? ' día' : ' días')];
            }
        }

        return $salida;
    }

    private static function resumenIngredientes(array $ing): string
    {
        $partes = [];
        if ($ing['titular'] !== '') {
            $partes[] = $ing['titular'];
        }
        foreach ($ing['sellos'] as $s) {
            if (mb_stripos($s, 'sin ') === 0) {
                $partes[] = mb_strtolower($s);
                break;
            }
        }
        if ($partes === [] && $ing['texto'] !== '') {
            return self::cortar($ing['texto'], 48);
        }

        return implode(' · ', $partes);
    }

    private static function resumenComposicion(array $comp): string
    {
        $filas = $comp['principales'] !== []
            ? array_map(fn ($p) => ['nombre' => KppHtml::nombreCorto($p['clave']), 'valor' => $p['valor']], array_slice($comp['principales'], 0, 3))
            : array_slice($comp['resto'], 0, 2);

        return implode(' · ', array_map(fn ($f) => $f['nombre'] . ' ' . $f['valor'], $filas));
    }

    private static function primeraFrase(string $html): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if ($t === '') {
            return '';
        }
        if (preg_match('/^(.{20,}?[.!?])\s/u', $t, $m)) {
            $t = $m[1];
        }

        return self::cortar($t, 60);
    }

    /** Corta en palabra entera, nunca a mitad. */
    private static function cortar(string $t, int $max): string
    {
        if (mb_strlen($t) <= $max) {
            return $t;
        }
        $corte = mb_substr($t, 0, $max);
        $espacio = mb_strrpos($corte, ' ');
        if ($espacio !== false && $espacio > $max / 2) {
            $corte = mb_substr($corte, 0, $espacio);
        }

        return rtrim($corte, " ,.;:(") . '…';
    }

    /**
     * Hasta tres formatos se nombran todos («Saco 2 kg y Pack 2 x 2 kg»). Con más, el más pequeño
     * y el más grande: «De Pack 12 x 85 g a Pack 48 x 85 g (4 formatos)».
     */
    public static function rangoFormatos(array $formatos): string
    {
        $cortos = array_values(array_unique(array_filter(array_column($formatos, 'corto'))));

        // Packs del mismo envase y tamaño: «6, 12 y 24 latas de 50 g» / «De 6 a 48 latas de 85 g».
        $mismo = self::mismoEnvase($formatos);
        if ($mismo !== null) {
            [$unidades, $envase, $tamano] = $mismo;
            sort($unidades);
            $cola = ' ' . $envase . ' de ' . $tamano;

            return count($unidades) > 3
                ? 'De ' . $unidades[0] . ' a ' . $unidades[count($unidades) - 1] . $cola
                : self::lista(array_map('strval', $unidades)) . $cola;
        }

        if (count($cortos) <= 2) {
            return self::lista($cortos);
        }
        $conPeso = array_values(array_filter($formatos, fn ($f) => $f['gramos'] !== null));
        if (count($conPeso) === count($formatos)) {
            usort($conPeso, fn ($a, $b) => $a['gramos'] <=> $b['gramos']);
            $primero = $conPeso[0]['corto'];
            $ultimo = $conPeso[count($conPeso) - 1]['corto'];
        } else {
            $primero = $cortos[0];
            $ultimo = $cortos[count($cortos) - 1];
        }

        return 'De ' . $primero . ' a ' . $ultimo . ' (' . count($cortos) . ' formatos)';
    }

    /**
     * Si todos los formatos son packs del mismo envase y del mismo tamaño por unidad, devuelve
     * [unidades de cada uno, envase en plural, tamaño de la unidad]. Si no, null.
     */
    private static function mismoEnvase(array $formatos): ?array
    {
        if (count($formatos) < 2) {
            return null;
        }
        $plural = ['lata' => 'latas', 'sobre' => 'sobres', 'bolsa' => 'sobres', 'tarrina' => 'tarrinas', 'bote' => 'botes',
            'barrita' => 'barritas', 'stick' => 'sticks', 'bandeja' => 'bandejas'];
        $unidades = [];
        $clave = null;
        foreach ($formatos as $f) {
            if (empty($f['unidades']) || empty($f['gramos']) || !isset($plural[$f['envase']])) {
                return null;
            }
            $gUno = round($f['gramos'] / $f['unidades'], 1);
            $esta = $f['envase'] . '|' . $gUno;
            if ($clave !== null && $esta !== $clave) {
                return null;
            }
            $clave = $esta;
            $unidades[] = (int) $f['unidades'];
        }
        [$envase, $gUno] = explode('|', $clave);
        $tamano = (float) $gUno >= 1000
            ? str_replace('.', ',', rtrim(rtrim(number_format($gUno / 1000, 2, '.', ''), '0'), '.')) . ' kg'
            : str_replace('.', ',', rtrim(rtrim(number_format((float) $gUno, 1, '.', ''), '0'), '.')) . ' g';

        return [array_values(array_unique($unidades)), $plural[$envase], $tamano];
    }

    /** «a», «a y b», «a, b y c» */
    private static function lista(array $cosas): string
    {
        if (count($cosas) < 2) {
            return (string) ($cosas[0] ?? '');
        }
        $ultima = array_pop($cosas);

        return implode(', ', $cosas) . ' y ' . $ultima;
    }
}
