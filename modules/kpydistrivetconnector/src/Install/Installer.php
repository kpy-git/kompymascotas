<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyDistrivetConnector\Install;

use Db;
use Module;
use PrestaShop\Module\KpyCancellationRequest\Install\OrderStateInstaller;
use PrestaShop\Module\KpyDistrivetConnector\Config\Config;
use PrestaShopBundle\Install\SqlLoader;

class Installer
{
    private array $hooks = [
        'actionKpyOrderDispatched',
    ];

    /**
     * Module's installation entry point.
     *
     * @param \Module $module
     *
     * @return bool
     */
    public function install(Module $module): bool
    {
        if (!$module->registerHook($this->hooks)) {
            return false;
        }

        if (!$this->executeSqlFromFile($module->getLocalPath() . 'src/Install/install.sql')) {
            return false;
        }

        $this->createConfigurations();

        $this->createOrderStates($module);

        return true;
    }

    /**
     * @param \Module $module
     *
     * @return bool
     */
    public function uninstall(Module $module): bool
    {
        foreach ($this->hooks as $hook) {
            $module->unregisterHook($hook);
        }

        $this->deleteConfigurations();

        return $this->executeSqlFromFile($module->getLocalPath() . 'src/Install/uninstall.sql');
    }

    /**
     * @param string $filepath
     *
     * @return bool
     */
    private function executeSqlFromFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return true;
        }

        $allowedCollations = ['utf8mb4_general_ci', 'utf8mb4_unicode_ci'];
        $databaseCollation = Db::getInstance()->getValue('SELECT @@collation_database');
        $sqlLoader = new SqlLoader();
        $sqlLoader->setMetaData([
            'PREFIX_' => _DB_PREFIX_,
            'ENGINE_TYPE' => _MYSQL_ENGINE_,
            'COLLATION' => (empty($databaseCollation) || !in_array($databaseCollation, $allowedCollations)) ? '' : 'COLLATE ' . $databaseCollation,
        ]);

        return $sqlLoader->parseFile($filepath);
    }

    private function createConfigurations(): void
    {
        if (!\Configuration::get(Config::KPY_DISTRIVET_CLIENT)) {
            \Configuration::updateValue(Config::KPY_DISTRIVET_CLIENT, '');
        }

        if (!\Configuration::get(Config::KPY_DISTRIVET_SECRET)) {
            \Configuration::updateValue(Config::KPY_DISTRIVET_SECRET, '');
        }

        if (!\Configuration::get(Config::KPY_DISTRIVET_MANUFACTURERS)) {
            \Configuration::updateValue(Config::KPY_DISTRIVET_MANUFACTURERS, json_encode([]));
        }

        if (!\Configuration::get(Config::KPY_DISTRIVET_API_DOMAIN)) {
            \Configuration::updateValue(Config::KPY_DISTRIVET_API_DOMAIN, '');
        }

        if (!\Configuration::get(Config::KPY_DISTRIVET_API_AUTH_PATH)) {
            \Configuration::updateValue(Config::KPY_DISTRIVET_API_AUTH_PATH, '');
        }
    }

    private function deleteConfigurations(): void
    {
        \Configuration::deleteByName(Config::KPY_DISTRIVET_CLIENT);
        \Configuration::deleteByName(Config::KPY_DISTRIVET_SECRET);
        \Configuration::deleteByName(Config::KPY_DISTRIVET_MANUFACTURERS);
        \Configuration::deleteByName(Config::KPY_DISTRIVET_API_AUTH_PATH);
        \Configuration::deleteByName(Config::KPY_DISTRIVET_API_DOMAIN);
    }

    private function createOrderStates(\Module $module): void
    {
        $orderStateInstaller = new OrderStateInstaller($module);

        $orderStateInstaller->install(
            Config::DISTRIVET_OS,
            "Pedido transmitido a Distrivet",
            "#b7d261"
        );
    }
}
