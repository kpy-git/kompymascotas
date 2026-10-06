<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyStorePreparation\Install;

use Db;
use Configuration;
use Module;
use PrestaShop\Module\KpyStorePreparation\Config\StorePreparationConfig;
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

        if (!Configuration::get(StorePreparationConfig::PRIORITY_BRANDS)) {
            Configuration::updateValue(StorePreparationConfig::PRIORITY_BRANDS, json_encode([]));
        }

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

        Configuration::deleteByName(StorePreparationConfig::PRIORITY_BRANDS);

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
}
