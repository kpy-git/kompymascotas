<?php

class IdentityController extends IdentityControllerCore
{
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