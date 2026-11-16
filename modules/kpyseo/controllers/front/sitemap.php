<?php
/**
 * Sirve los sitemaps: /sitemap.xml (índice), /sitemap-paginas.xml, /sitemap-categorias.xml,
 * /sitemap-marcas.xml y /sitemap-productos-N.xml. Las rutas las declara el módulo en hookModuleRoutes.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class KpyseoSitemapModuleFrontController extends ModuleFrontController
{
    public function init(): void
    {
        parent::init();

        $tipo = (string) preg_replace('~[^a-z]~', '', (string) Tools::getValue('tipo', 'indice'));
        $n = max(1, (int) Tools::getValue('n', 1));

        $sitemap = new KseoSitemap($this->context);
        $xml = $tipo === 'indice' || $tipo === '' ? $sitemap->indice() : $sitemap->fichero($tipo, $n);

        if ($xml === null || $xml === '') {
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No existe ese sitemap.';
            exit;
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        header('X-Robots-Tag: noindex');
        echo $xml;
        exit;
    }
}
