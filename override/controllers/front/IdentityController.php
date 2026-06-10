<?php
class IdentityController extends IdentityControllerCore
{
    /*
    * module: kpycustomerform
    * date: 2026-06-03 13:42:55
    * version: 1.0.0
    */
    public function initContent(): void
    {
        /**
         * Hace que la contraseña no sea obligatoria siempre y cuando no se intente cambiar
         * En el formulario los campos de contraseña están ocultos, hay un botón para mostrarlos
         * Si en alguno de los dos se introduce texto, la contraseña se comprobará
         */
        $this->passwordRequired = !Tools::isSubmit('submitCreate')
            || !empty(Tools::getValue('new_password'));
        parent::initContent();
    }
}