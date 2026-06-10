<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyAquaOrders\Install;

use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Db\DbConfig;

class Installer
{
    private array $hooks = [
        'actionOrderStatusPostUpdate',
    ];
    private array $configurationCredentialsKeys = [
        DbConfig::AQUA_USER,
        DbConfig::AQUA_PASSWORD,
        DbConfig::AQUA_HOST,
        DbConfig::AQUA_DATABASE,
    ];

    /**
     * Module's installation entry point.
     *
     * @param \Module $module
     *
     * @return bool
     */
    public function install(\Module $module): bool
    {
        if (!$module->registerHook($this->hooks)) {
            return false;
        }

        $this->createOrderStates($module);

        // Generamos 32 bytes de datos aleatorios (256 bits)
        // bin2hex lo convierte en una cadena de 64 caracteres alfanuméricos
        $secretKey = bin2hex(random_bytes(32));

        if (!\Configuration::get(\KpyAquaOrders::SECRET_KEY)) {
            \Configuration::updateValue(\KpyAquaOrders::SECRET_KEY, $secretKey);
        }

        foreach ($this->configurationCredentialsKeys as $key) {
            if (!\Configuration::get($key)) {
                \Configuration::updateValue($key, '');
            }
        }

        return true;
    }

    /**
     * @param \Module $module
     *
     * @return bool
     */
    public function uninstall(\Module $module): bool
    {
        foreach ($this->hooks as $hook) {
            $module->unregisterHook($hook);
        }

        \Configuration::deleteByName(\KpyAquaOrders::SECRET_KEY);

        foreach ($this->configurationCredentialsKeys as $key) {
            \Configuration::deleteByName($key);
        }

        return true;
    }


    private function createOrderStates(\Module $module): void
    {
        $orderStateInstaller = new OrderStateInstaller($module);

        foreach (AquaOrderState::getInstallablesOrderState() as $orderState) {
            $orderStateInstaller->install($orderState);
        }
    }
}
