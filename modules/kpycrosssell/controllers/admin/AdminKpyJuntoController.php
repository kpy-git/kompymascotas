<?php
/**
 * La entrada del menú: Catálogo → «Kompy · Caprichitos».
 *
 * Lleva a la pantalla del módulo, que es donde están los ajustes y las parejas. Una sola pantalla,
 * un solo sitio donde tocar.
 */
class AdminKpyJuntoController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent(): void
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => 'kpycrosssell',
            'module_name' => 'kpycrosssell',
        ]));
    }
}
