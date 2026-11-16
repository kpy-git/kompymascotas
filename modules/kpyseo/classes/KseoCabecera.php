<?php
/**
 * Lo que va en el <head> por displayHeader: canónica de las páginas de marca, noindex de las
 * categorías y marcas sin productos, Open Graph y Twitter Card completos y la ficha de Organización en la portada.
 * El tema ya imprime título, descripción, canónica (cuando la hay), og:title, og:description,
 * og:url y og:site_name; aquí solo se añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoCabecera
{
    /** Verdadero cuando en esta petición se ha añadido el noindex (para quitar el «index, follow» del tema). */
    private static bool $noindex = false;

    public static function noindexAplicado(): bool
    {
        return self::$noindex;
    }

    public static function etiquetas(Context $ctx): string
    {
        $lineas = [];
        $controller = $ctx->controller;
        $pagina = $ctx->smarty->getTemplateVars('page');
        $robots = is_array($pagina) ? (string) ($pagina['meta']['robots'] ?? 'index') : 'index';
        $imagen = '';
        $esProducto = $controller instanceof ProductController;

        if (KseoMarcas::esLanding()) {
            $rewrite = KseoMarcas::rewriteActual();
            if ($rewrite !== '') {
                $idMarca = KseoMarcas::idPorRewrite($rewrite);
                $lineas[] = '<link rel="canonical" href="' . self::esc($ctx->link->getModuleLink('kpymanufacturer', 'landing', ['brand' => $rewrite])) . '">';
                if (KseoAjustes::activo('KPYSEO_NOINDEX_VACIAS')
                    && stripos($robots, 'noindex') === false
                    && KseoMarcas::sinProductos($idMarca, (int) $ctx->shop->id)) {
                    $lineas[] = '<meta name="robots" content="noindex, follow">';
                    self::$noindex = true;
                }
                $imagen = KseoMarcas::ogImagen($idMarca, $ctx);
            }
        } elseif (KseoMarcas::esIndice()) {
            $lineas[] = '<link rel="canonical" href="' . self::esc($ctx->link->getModuleLink('kpymanufacturer', 'brands')) . '">';
        } elseif ($controller instanceof CategoryController) {
            $categoria = $controller->getCategory();
            if ($categoria instanceof Category && (int) $categoria->id > 0) {
                if (KseoAjustes::activo('KPYSEO_NOINDEX_VACIAS')
                    && stripos($robots, 'noindex') === false
                    && KseoCategorias::estaVacia((int) $categoria->id)) {
                    $lineas[] = '<meta name="robots" content="noindex, follow">';
                    self::$noindex = true;
                }
                $imagen = self::imagenCategoria($categoria, $ctx);
            }
        }

        if ($imagen === '' && !$esProducto) {
            $imagen = KseoAjustes::texto('KPYSEO_OG_IMAGEN');
        }
        if ($imagen !== '') {
            $lineas[] = '<meta property="og:image" content="' . self::esc($imagen) . '" />';
            $lineas[] = '<meta name="twitter:image" content="' . self::esc($imagen) . '" />';
        }
        $lineas[] = '<meta property="og:locale" content="es_ES" />';
        $lineas[] = '<meta name="twitter:card" content="summary_large_image" />';

        if ($controller instanceof FrontController && $controller->php_self === 'index') {
            $lineas[] = KseoJsonLd::organizacion($ctx);
        }

        return implode("\n", $lineas) . "\n";
    }

    private static function imagenCategoria(Category $categoria, Context $ctx): string
    {
        $id = (int) $categoria->id;
        if (!is_file(_PS_CAT_IMG_DIR_ . $id . '.jpg')) {
            return '';
        }
        $rewrite = is_array($categoria->link_rewrite) ? (string) reset($categoria->link_rewrite) : (string) $categoria->link_rewrite;

        return $ctx->link->getCatImageLink($rewrite, $id);
    }

    private static function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }
}
