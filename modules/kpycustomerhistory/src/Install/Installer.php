<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyCustomerHistory\Install;

use Module;

class Installer
{
    /**
     * Module's installation entry point.
     *
     * @param Module $module
     *
     * @return bool
     */
    public function install(Module $module): bool
    {
        if (!$this->registerHooks($module)) {
            return false;
        }

        return true;
    }


    /**
     * Register hooks for the module.
     *
     * @see https://devdocs.prestashop.com/8/modules/concepts/hooks/
     *
     * @param Module $module
     *
     * @return bool
     */
    private function registerHooks(Module $module): bool
    {
        $hooks = [
            'actionFrontControllerSetMedia',
            'actionFrontControllerSetVariables',
        ];

        return $module->registerHook($hooks);
    }

}