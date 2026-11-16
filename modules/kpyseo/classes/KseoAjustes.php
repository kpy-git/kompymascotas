<?php
/**
 * Los ajustes del módulo: valores por defecto, lectura cómoda y el formulario del back office.
 * Todo vive en `Configuration` con el prefijo KPYSEO_; no hay tabla propia.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoAjustes
{
    private const POR_DEFECTO = [
        'KPYSEO_MARCA_TEXTO' => 'kompymascotas',
        'KPYSEO_H1_HOME' => 'Piensos, dietas veterinarias y antiparasitarios para perros y gatos',
        'KPYSEO_H1_SUB' => '',
        'KPYSEO_OG_IMAGEN' => '',
        'KPYSEO_LOGO' => '',
        'KPYSEO_SAMEAS' => '',
        'KPYSEO_RAZON_SOCIAL' => 'Premier Pet Solutions, SL',
        'KPYSEO_NIF' => 'B24907297',
        'KPYSEO_NOINDEX_VACIAS' => '1',
        'KPYSEO_ALT_AUTO' => '1',
        'KPYSEO_MARCAS_META' => '1',
        'KPYSEO_SOPORTE_META' => '1',
        'KPYSEO_SOPORTE_DESC' => '',
        'KPYSEO_REDIR_BUSQUEDAS' => '1',
        'KPYSEO_REDIR_INDEXPHP' => '1',
        'KPYSEO_REDIR_LIMPIAR_QS' => '1',
        'KPYSEO_REDIRECCIONES' => '',
        'KPYSEO_JSONLD' => '1',
        'KPYSEO_PORTES' => '4.99',
        'KPYSEO_ENVIO_PREP_MIN' => '0',
        'KPYSEO_ENVIO_PREP_MAX' => '1',
        'KPYSEO_ENVIO_TRANSITO_MIN' => '1',
        'KPYSEO_ENVIO_TRANSITO_MAX' => '2',
        'KPYSEO_DEVOLUCION_DIAS' => '14',
        'KPYSEO_DEVOLUCION_COSTE' => '5.99',
        'KPYSEO_SITEMAP_HORAS' => '24',
        'KPYSEO_SITEMAP_POR_FICHERO' => '5000',
        'KPYSEO_INDEXNOW' => '1',
        'KPYSEO_INDEXNOW_CLAVE' => '',
        'KPYSEO_INDEXNOW_ULTIMO' => '',
    ];

    /** Los que se pueden tocar desde el formulario (los otros dos los gestiona el módulo). */
    private const EN_FORMULARIO = [
        'KPYSEO_MARCA_TEXTO', 'KPYSEO_H1_HOME', 'KPYSEO_H1_SUB', 'KPYSEO_OG_IMAGEN', 'KPYSEO_LOGO', 'KPYSEO_SAMEAS',
        'KPYSEO_RAZON_SOCIAL', 'KPYSEO_NIF', 'KPYSEO_NOINDEX_VACIAS', 'KPYSEO_ALT_AUTO', 'KPYSEO_MARCAS_META', 'KPYSEO_SOPORTE_META', 'KPYSEO_SOPORTE_DESC', 'KPYSEO_REDIR_BUSQUEDAS', 'KPYSEO_REDIR_INDEXPHP', 'KPYSEO_REDIR_LIMPIAR_QS', 'KPYSEO_REDIRECCIONES',
        'KPYSEO_JSONLD', 'KPYSEO_PORTES', 'KPYSEO_ENVIO_PREP_MIN', 'KPYSEO_ENVIO_PREP_MAX', 'KPYSEO_ENVIO_TRANSITO_MIN',
        'KPYSEO_ENVIO_TRANSITO_MAX', 'KPYSEO_DEVOLUCION_DIAS', 'KPYSEO_DEVOLUCION_COSTE', 'KPYSEO_SITEMAP_HORAS',
        'KPYSEO_SITEMAP_POR_FICHERO', 'KPYSEO_INDEXNOW',
    ];

    public static function instalar(): void
    {
        foreach (self::POR_DEFECTO as $clave => $valor) {
            if (Configuration::get($clave) === false) {
                Configuration::updateValue($clave, $valor);
            }
        }
    }

    public static function desinstalar(): void
    {
        foreach (array_keys(self::POR_DEFECTO) as $clave) {
            Configuration::deleteByName($clave);
        }
    }

    public static function texto(string $clave): string
    {
        $valor = Configuration::get($clave);
        if ($valor === false || $valor === null) {
            $valor = self::POR_DEFECTO[$clave] ?? '';
        }

        return trim((string) $valor);
    }

    public static function numero(string $clave): float
    {
        return (float) str_replace(',', '.', self::texto($clave));
    }

    public static function activo(string $clave): bool
    {
        return self::texto($clave) === '1';
    }

    /** Las URL de perfiles (Instagram, Facebook, Opiniones Verificadas…), una por línea. */
    public static function sameAs(): array
    {
        $urls = [];
        foreach (preg_split('~[\r\n]+~', self::texto('KPYSEO_SAMEAS')) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea !== '' && filter_var($linea, FILTER_VALIDATE_URL)) {
                $urls[] = $linea;
            }
        }

        return $urls;
    }

    public static function guardarDesdeFormulario(): void
    {
        foreach (self::EN_FORMULARIO as $clave) {
            $valor = Tools::getValue($clave, self::POR_DEFECTO[$clave]);
            if (is_array($valor)) {
                $valor = '';
            }
            $valor = trim((string) $valor);
            if (in_array($clave, ['KPYSEO_NOINDEX_VACIAS', 'KPYSEO_ALT_AUTO', 'KPYSEO_MARCAS_META', 'KPYSEO_SOPORTE_META', 'KPYSEO_JSONLD', 'KPYSEO_INDEXNOW', 'KPYSEO_REDIR_BUSQUEDAS', 'KPYSEO_REDIR_INDEXPHP', 'KPYSEO_REDIR_LIMPIAR_QS'], true)) {
                $valor = $valor === '1' ? '1' : '0';
            } elseif (in_array($clave, ['KPYSEO_PORTES', 'KPYSEO_DEVOLUCION_COSTE'], true)) {
                $valor = number_format((float) str_replace(',', '.', $valor), 2, '.', '');
            } elseif (in_array($clave, ['KPYSEO_ENVIO_PREP_MIN', 'KPYSEO_ENVIO_PREP_MAX', 'KPYSEO_ENVIO_TRANSITO_MIN', 'KPYSEO_ENVIO_TRANSITO_MAX', 'KPYSEO_DEVOLUCION_DIAS', 'KPYSEO_SITEMAP_HORAS', 'KPYSEO_SITEMAP_POR_FICHERO'], true)) {
                $valor = (string) max(0, (int) $valor);
            }
            Configuration::updateValue($clave, $valor);
        }
    }

    public static function valoresFormulario(): array
    {
        $valores = [];
        foreach (self::EN_FORMULARIO as $clave) {
            $valores[$clave] = Tools::getValue($clave, self::texto($clave));
        }

        return $valores;
    }

    public static function definicionFormulario(): array
    {
        $si_no = static fn (string $id): array => [
            ['id' => $id . '_on', 'value' => 1, 'label' => 'Sí'],
            ['id' => $id . '_off', 'value' => 0, 'label' => 'No'],
        ];

        return [
            [
                'form' => [
                    'legend' => ['title' => 'Portada y marca', 'icon' => 'icon-home'],
                    'input' => [
                        ['type' => 'text', 'label' => 'H1 de la portada', 'name' => 'KPYSEO_H1_HOME', 'desc' => 'La frase que encabeza la portada (el logo deja de ser el H1). Vacío = no se pinta nada.'],
                        ['type' => 'text', 'label' => 'Subtítulo de la portada', 'name' => 'KPYSEO_H1_SUB', 'desc' => 'Opcional, debajo del H1.'],
                        ['type' => 'text', 'label' => 'Nombre de la tienda en los textos', 'name' => 'KPYSEO_MARCA_TEXTO', 'desc' => 'Se usa en los títulos y descripciones que el módulo genera cuando faltan (por ejemplo, en las marcas).'],
                        ['type' => 'text', 'label' => 'Imagen para compartir (Open Graph)', 'name' => 'KPYSEO_OG_IMAGEN', 'desc' => 'URL completa de una imagen de 1200 × 630 (JPG o PNG). Se usa en las páginas que no tienen imagen propia.'],
                        ['type' => 'text', 'label' => 'Logo para la ficha de empresa', 'name' => 'KPYSEO_LOGO', 'desc' => 'URL completa. Vacío = el logo de la tienda.'],
                        ['type' => 'text', 'label' => 'Razón social', 'name' => 'KPYSEO_RAZON_SOCIAL'],
                        ['type' => 'text', 'label' => 'NIF', 'name' => 'KPYSEO_NIF', 'desc' => 'Sin «ES» delante; se añade solo.'],
                        ['type' => 'textarea', 'label' => 'Perfiles de la empresa (sameAs)', 'name' => 'KPYSEO_SAMEAS', 'rows' => 5, 'desc' => 'Una URL por línea: Instagram, Facebook, TikTok, Perfil de empresa de Google, Opiniones Verificadas…'],
                    ],
                    'submit' => ['title' => 'Guardar', 'class' => 'btn btn-default pull-right'],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => 'Qué hace el módulo', 'icon' => 'icon-cogs'],
                    'input' => [
                        ['type' => 'switch', 'label' => 'noindex en categorías sin productos', 'name' => 'KPYSEO_NOINDEX_VACIAS', 'is_bool' => true, 'values' => $si_no('noindex'), 'desc' => 'Si una categoría no tiene ningún producto visible (ni en sus subcategorías), se marca noindex, follow y no entra en el sitemap.'],
                        ['type' => 'switch', 'label' => 'Leyendas de imagen automáticas', 'name' => 'KPYSEO_ALT_AUTO', 'is_bool' => true, 'values' => $si_no('alt'), 'desc' => 'Cuando una imagen no tiene leyenda, el alt pasa a ser «Producto – Marca».'],
                        ['type' => 'switch', 'label' => 'Título y descripción de las marcas', 'name' => 'KPYSEO_MARCAS_META', 'is_bool' => true, 'values' => $si_no('marcas'), 'desc' => 'En /marcas/… se usan el meta título y la meta descripción de la marca (Catálogo › Marcas) y, si faltan, se generan. Una marca sin ningún producto visible lleva noindex, follow.'],
                        ['type' => 'switch', 'label' => 'Título y descripción del centro de soporte', 'name' => 'KPYSEO_SOPORTE_META', 'is_bool' => true, 'values' => $si_no('soporte'), 'desc' => 'En /soporte/… cada artículo lleva su pregunta como título y el principio de la respuesta como descripción (el módulo de soporte pone el mismo título en todos).'],
                        ['type' => 'text', 'label' => 'Descripción del índice del soporte', 'name' => 'KPYSEO_SOPORTE_DESC', 'desc' => 'Vacío = una frase con envíos, pagos, devoluciones y pedidos.'],
                        ['type' => 'switch', 'label' => 'Completar el JSON-LD del producto', 'name' => 'KPYSEO_JSONLD', 'is_bool' => true, 'values' => $si_no('jsonld'), 'desc' => 'GTIN de la combinación, envío, devoluciones, marca y descripción.'],
                        ['type' => 'switch', 'label' => 'IndexNow', 'name' => 'KPYSEO_INDEXNOW', 'is_bool' => true, 'values' => $si_no('indexnow'), 'desc' => 'Avisar a Bing y compañía cuando cambia una ficha, una categoría, una marca o una página.'],
                    ],
                    'submit' => ['title' => 'Guardar', 'class' => 'btn btn-default pull-right'],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => 'Envío y devoluciones (para el JSON-LD de producto)', 'icon' => 'icon-truck'],
                    'input' => [
                        ['type' => 'text', 'label' => 'Portes por debajo del envío gratis (€, con IVA)', 'name' => 'KPYSEO_PORTES', 'class' => 'fixed-width-sm', 'desc' => 'El umbral de envío gratis se lee de Transporte › Preferencias.'],
                        ['type' => 'text', 'label' => 'Preparación: días mínimo', 'name' => 'KPYSEO_ENVIO_PREP_MIN', 'class' => 'fixed-width-xs'],
                        ['type' => 'text', 'label' => 'Preparación: días máximo', 'name' => 'KPYSEO_ENVIO_PREP_MAX', 'class' => 'fixed-width-xs'],
                        ['type' => 'text', 'label' => 'Tránsito: días mínimo', 'name' => 'KPYSEO_ENVIO_TRANSITO_MIN', 'class' => 'fixed-width-xs'],
                        ['type' => 'text', 'label' => 'Tránsito: días máximo', 'name' => 'KPYSEO_ENVIO_TRANSITO_MAX', 'class' => 'fixed-width-xs'],
                        ['type' => 'text', 'label' => 'Días para devolver', 'name' => 'KPYSEO_DEVOLUCION_DIAS', 'class' => 'fixed-width-xs'],
                        ['type' => 'text', 'label' => 'Coste de la devolución para el cliente (€)', 'name' => 'KPYSEO_DEVOLUCION_COSTE', 'class' => 'fixed-width-sm', 'desc' => '0 = devolución gratuita.'],
                    ],
                    'submit' => ['title' => 'Guardar', 'class' => 'btn btn-default pull-right'],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => 'Redirecciones', 'icon' => 'icon-random'],
                    'input' => [
                        ['type' => 'switch', 'label' => 'Búsquedas de marca → página de marca', 'name' => 'KPYSEO_REDIR_BUSQUEDAS', 'is_bool' => true, 'values' => $si_no('redir_busq'), 'desc' => 'Una búsqueda cuyo término es exactamente una marca (/busqueda?s=beaphar) redirige con 301 a /marcas/beaphar. Son las URL del dominio antiguo que aún traen clics.'],
                        ['type' => 'switch', 'label' => 'URL no amigables → URL amigable', 'name' => 'KPYSEO_REDIR_INDEXPHP', 'is_bool' => true, 'values' => $si_no('redir_index'), 'desc' => 'index.php?controller=product&id_product=N (y category, cms, manufacturer) redirige con 301 a la URL amigable si el objeto está activo.'],
                        ['type' => 'switch', 'label' => 'Limpiar la query duplicada', 'name' => 'KPYSEO_REDIR_LIMPIAR_QS', 'is_bool' => true, 'values' => $si_no('redir_qs'), 'desc' => 'La redirección del dominio antiguo deja «?a=b?a=b&src=pym»; se limpia con una sola redirección más.'],
                        ['type' => 'textarea', 'label' => 'Redirecciones propias', 'name' => 'KPYSEO_REDIRECCIONES', 'rows' => 8, 'desc' => 'Una por línea: «/ruta-vieja | /destino | 301». Un «*» al final del origen casa por prefijo («/blog/* | / | 301»). Destino «-» o vacío = 410 (la página ya no existe). Las líneas que empiezan por # se ignoran. Solo rutas del front; nunca el back office.'],
                    ],
                    'submit' => ['title' => 'Guardar', 'class' => 'btn btn-default pull-right'],
                ],
            ],
            [
                'form' => [
                    'legend' => ['title' => 'Sitemaps', 'icon' => 'icon-sitemap'],
                    'input' => [
                        ['type' => 'text', 'label' => 'Regenerar cada (horas)', 'name' => 'KPYSEO_SITEMAP_HORAS', 'class' => 'fixed-width-xs', 'desc' => 'Se regeneran solos cuando alguien pide el sitemap y ha pasado este tiempo.'],
                        ['type' => 'text', 'label' => 'Productos por fichero', 'name' => 'KPYSEO_SITEMAP_POR_FICHERO', 'class' => 'fixed-width-sm', 'desc' => 'Google admite hasta 50.000; con 5.000 los ficheros se leen en un instante.'],
                    ],
                    'submit' => ['title' => 'Guardar', 'class' => 'btn btn-default pull-right'],
                ],
            ],
        ];
    }
}
