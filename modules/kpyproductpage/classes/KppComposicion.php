<?php
/**
 * Ordena la composición analítica (kpy_product_component + kpy_component_lang) para la ficha:
 * las cuatro cifras que mira todo el mundo (proteína, grasa, fibra y ceniza) en grande, y el
 * resto en filas.
 *
 * Cada marca llama a lo mismo de una manera («Grasa bruta», «Grasas brutas», «Materias grasas»,
 * «Aceites y grasas brutos»…). Aquí se reconocen todas; el nombre que se enseña en grande es el
 * corto y el de la etiqueta se conserva en `nombre_original` (va en el `title`).
 *
 * No depende de PrestaShop.
 */
class KppComposicion
{
    /** clave => expresión que reconoce el nombre, sin tildes y en minúsculas */
    private const PRINCIPALES = [
        'proteina' => '/^(proteinas?( brutas?)?|contenido de proteinas?)$/',
        'grasa' => '/^(grasas?( brutas?)?|materias? grasas?( brutas?)?|aceites y grasas( brutos?)?|contenido de grasas?|lipidos( brutos)?)$/',
        'fibra' => '/^(fibras?( brutas?)?|celulosa bruta)$/',
        'ceniza' => '/^(cenizas?( brutas?)?|materias? (inorganicas?|minerales?)|materia mineral)$/',
    ];

    /**
     * @param array<int, array{nombre: string, valor: string}> $filas en el orden de la base de datos
     *
     * @return array{principales: array<int, array{clave: string, nombre_original: string, valor: string}>, resto: array<int, array{nombre: string, valor: string}>}
     */
    public static function ordenar(array $filas): array
    {
        $principales = [];
        $resto = [];
        foreach ($filas as $f) {
            $nombre = trim((string) $f['nombre']);
            $valor = self::valor((string) $f['valor']);
            if ($nombre === '' || $valor === '') {
                continue;
            }
            $clave = self::clave($nombre);
            if ($clave !== null && !isset($principales[$clave])) {
                $principales[$clave] = ['clave' => $clave, 'nombre_original' => $nombre, 'valor' => $valor];
            } else {
                $resto[] = ['nombre' => $nombre, 'valor' => $valor];
            }
        }

        // Con menos de tres de las cuatro, las tarjetas grandes quedan cojas: todo en filas.
        if (count($principales) < 3) {
            $todas = [];
            foreach ($filas as $f) {
                $v = self::valor((string) $f['valor']);
                if (trim((string) $f['nombre']) !== '' && $v !== '') {
                    $todas[] = ['nombre' => trim((string) $f['nombre']), 'valor' => $v];
                }
            }

            return ['principales' => [], 'resto' => $todas];
        }

        $ordenadas = [];
        foreach (array_keys(self::PRINCIPALES) as $clave) {
            if (isset($principales[$clave])) {
                $ordenadas[] = $principales[$clave];
            }
        }

        return ['principales' => $ordenadas, 'resto' => $resto];
    }

    public static function clave(string $nombre): ?string
    {
        $n = self::normalizar($nombre);
        foreach (self::PRINCIPALES as $clave => $re) {
            if (preg_match($re, $n)) {
                return $clave;
            }
        }

        return null;
    }

    /** «27%» → «27 %», «2.5 %» → «2,5 %». Lo demás («377 kcal/100 g») se deja. */
    private static function valor(string $v): string
    {
        $v = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $v)));
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*%$/', $v, $m)) {
            return str_replace('.', ',', $m[1]) . ' %';
        }

        return $v;
    }

    private static function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z ]/', ' ', $t)));
    }
}
