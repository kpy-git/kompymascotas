<?php
/**
 * Contenido de marca de algunas fichas: lo que una marca tenía hecho a mano, producto a producto,
 * y que no está en las tablas del catálogo. Hoy, Boske (desde el 30/09/2026): opinión de la
 * especialista, fotos con su texto, beneficios ilustrados, beneficios explicados, ingredientes
 * con foto y preguntas frecuentes. Antes vivía en plantillas de `kpyproductdescription`.
 *
 * Va en `marcas/<id_manufacturer>-<nombre>.json` y sus imágenes, ya optimizadas (WebP al tamaño
 * al que se ven), en `views/img/marcas/<nombre>/`. Solo se lee el fichero de la marca del
 * producto, y solo si existe: las demás fichas no leen nada.
 *
 * No depende de PrestaShop (se le da la dirección de la carpeta del módulo).
 */
class KppMarca
{
    /** @var array<int, array<int, array>> id_manufacturer => [id_product => contenido] */
    private static array $cache = [];

    /**
     * @param string $urlModulo la dirección de la carpeta del módulo, con barra al final
     *
     * @return array{marca: string, color: string, tinta: string, beneficios: array, opinion: ?array, fotos: array, cajas: array, claves: array, ingredientes: array, faq: array}|null
     */
    public static function de(int $idProducto, int $idMarca, string $urlModulo): ?array
    {
        if ($idProducto <= 0 || $idMarca <= 0) {
            return null;
        }
        if (!isset(self::$cache[$idMarca])) {
            self::$cache[$idMarca] = [];
            foreach (glob(dirname(__DIR__) . '/marcas/' . $idMarca . '-*.json') ?: [] as $fichero) {
                $datos = json_decode((string) file_get_contents($fichero), true);
                if (!is_array($datos)) {
                    continue;
                }
                $carpeta = substr(basename($fichero, '.json'), strlen($idMarca . '-'));
                foreach ((array) ($datos['productos'] ?? []) as $id => $c) {
                    self::$cache[$idMarca][(int) $id] = ['marca' => (string) ($datos['marca'] ?? ''), 'carpeta' => $carpeta, 'c' => (array) $c];
                }
            }
        }
        $fila = self::$cache[$idMarca][$idProducto] ?? null;
        if ($fila === null) {
            return null;
        }

        $img = $urlModulo . 'views/img/marcas/' . rawurlencode($fila['carpeta']) . '/';
        $foto = static function ($f) use ($img): ?array {
            if (!is_array($f) || empty($f['src'])) {
                return null;
            }

            return ['url' => $img . rawurlencode((string) $f['src']), 'ancho' => (int) ($f['ancho'] ?? 0), 'alto' => (int) ($f['alto'] ?? 0)];
        };
        $icono = static fn (string $src): string => $src === '' ? '' : $img . rawurlencode($src);
        $c = $fila['c'];

        $opinion = null;
        if (!empty($c['opinion']['texto'])) {
            $opinion = ['foto' => $foto($c['opinion']['foto'] ?? null), 'firma' => (string) ($c['opinion']['firma'] ?? ''), 'texto' => (string) $c['opinion']['texto']];
        }

        // El color de la marca para ese producto (fondo de sus tarjetas) y el de su texto.
        $hex = static fn ($v, string $por) => is_string($v) && preg_match('/^#[0-9a-f]{3,8}$/i', $v) ? $v : $por;

        return [
            'marca' => $fila['marca'],
            'color' => $hex($c['color'] ?? null, '#eef2ff'),
            'tinta' => $hex($c['tinta'] ?? null, '#373869'),
            // Con la forma de los beneficios de siempre, para el mismo bloque.
            'beneficios' => array_values(array_filter(array_map(
                static fn ($b) => ['nombre' => trim((string) ($b['nombre'] ?? '')), 'icono' => $icono((string) ($b['icono'] ?? '')), 'protege' => false],
                (array) ($c['beneficios'] ?? [])
            ), static fn ($b) => $b['nombre'] !== '')),
            'opinion' => $opinion,
            'fotos' => array_values(array_filter(array_map($foto, (array) ($c['fotos'] ?? [])))),
            'cajas' => array_values(array_filter(array_map(
                static fn ($k) => ['foto' => $foto($k['foto'] ?? null), 'html' => (string) ($k['html'] ?? '')],
                (array) ($c['cajas'] ?? [])
            ), static fn ($k) => trim(strip_tags($k['html'])) !== '')),
            'claves' => array_values(array_filter(array_map(
                static fn ($b) => ['icono' => $icono((string) ($b['icono'] ?? '')), 'html' => (string) ($b['html'] ?? '')],
                (array) ($c['claves'] ?? [])
            ), static fn ($b) => trim(strip_tags($b['html'])) !== '')),
            'ingredientes' => array_values(array_filter(array_map(
                static fn ($i) => ['foto' => $foto($i['foto'] ?? null), 'nombre' => trim((string) ($i['nombre'] ?? ''))],
                (array) ($c['ingredientes'] ?? [])
            ), static fn ($i) => $i['foto'] !== null && $i['nombre'] !== '')),
            'faq' => array_values(array_filter(array_map(
                static fn ($q) => ['pregunta' => trim((string) ($q['pregunta'] ?? '')), 'respuesta' => (string) ($q['respuesta'] ?? '')],
                (array) ($c['faq'] ?? [])
            ), static fn ($q) => $q['pregunta'] !== '' && trim(strip_tags($q['respuesta'])) !== '')),
        ];
    }
}
