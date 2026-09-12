<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyEvolutionPets\Install;

use Db;
use Module;
use PrestaShop\Module\KpyEvolutionPets\Config\Config;
use PrestaShopBundle\Install\SqlLoader;

class Installer
{
    private array $hooks = [
        'actionKpyOrderWarehouseSelected',
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

        $this->createConfiguration();

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

        $this->deleteConfiguration();

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

    private function createConfiguration(): void
    {
        if (!\Configuration::get(Config::KPY_EVOLUTION_BRANDS)) {
            \Configuration::updateValue(Config::KPY_EVOLUTION_BRANDS, json_encode([]));
        }

        if (!\Configuration::get(Config::KPY_EVOLUTION_WAREHOUSE)) {
            \Configuration::updateValue(Config::KPY_EVOLUTION_WAREHOUSE, 'EVOLUTION_PETS');
        }
    }

    private function deleteConfiguration(): void
    {
        \Configuration::deleteByName(Config::KPY_EVOLUTION_BRANDS);
        \Configuration::deleteByName(Config::KPY_EVOLUTION_WAREHOUSE);
    }

    private function createOrderStates(\Module $module): void
    {
        $orderStateInstaller = new OrderStateInstaller($module);

        $orderStateInstaller->install(
            Config::KPY_EVOLUTION_OS,
            "Pendiente de preparación en Evolution Pets",
            "#D746BB"
        );
    }
}
