<?php
/**
 * Publica la clave de IndexNow en la raíz de la tienda: /<clave>.txt (ruta declarada en hookModuleRoutes).
 * Solo contesta si la clave pedida es la del módulo; si no, 404.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class KpyseoIndexnowModuleFrontController extends ModuleFrontController
{
    public function init(): void
    {
        $pedida = (string) Tools::getValue('clave', '');
        $clave = KseoIndexNow::clave();
        if ($clave === '' || !hash_equals($clave, $pedida)) {
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No existe.';
            exit;
        }
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=86400');
        echo $clave;
        exit;
    }
}
