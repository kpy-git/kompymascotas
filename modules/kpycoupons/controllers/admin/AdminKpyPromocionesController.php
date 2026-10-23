<?php
/**
 * La entrada del menú: Catálogo → «Kompy · Promociones». Aquí está la pantalla entera
 * (`KpcPantalla`): el formulario, la vista previa, la lista y los ficheros de Google.
 *
 * Es un controlador de administración propio y no la página «Configurar» del módulo, porque esa en
 * PrestaShop 9 va dentro de Symfony y no deja subir ficheros ni cargar el JS de la pantalla con
 * normalidad. «Configurar» en la lista de módulos trae aquí.
 */
class AdminKpyPromocionesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = 'Kompy · Promociones';
    }

    public function setMedia($isNewTheme = false): void
    {
        parent::setMedia($isNewTheme);
        $version = '?v=' . $this->module->version;
        $this->addCSS(_MODULE_DIR_ . 'kpycoupons/views/css/admin.css' . $version);
        $this->addJS(_MODULE_DIR_ . 'kpycoupons/views/js/admin.js' . $version);
    }

    public function initContent(): void
    {
        // Lo que se pide sin recargar (los productos de una promoción) no pinta la pantalla entera:
        // lo contesta displayAjaxProductos().
        if ($this->ajax) {
            return;
        }
        $this->content = $this->pantalla()->pintar();
        parent::initContent();
    }

    /**
     * Los productos de una promoción, para desplegarlos en la lista (el ojo de «Productos»):
     * `…&ajax=1&action=productos&id=5`. Pasa por las mismas comprobaciones de acceso y token que
     * la pantalla.
     */
    public function displayAjaxProductos(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $this->pantalla()->productosDe((int) Tools::getValue('id'));
    }

    /**
     * Buscar un artículo para el regalo con la compra (el mismo para todos, el de reserva o el de una
     * fila): `…&ajax=1&action=buscar&q=boske 3 kg`. Devuelve JSON, un formato por fila.
     */
    public function displayAjaxBuscar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            echo json_encode($this->pantalla()->buscarArticulos((string) Tools::getValue('q')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, buscar artículos: ' . $e->getMessage(), 3, null, 'KpcPantalla', 0, true);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function pantalla(): KpcPantalla
    {
        return new KpcPantalla($this->module, $this->context->link->getAdminLink('AdminKpyPromociones'));
    }
}
