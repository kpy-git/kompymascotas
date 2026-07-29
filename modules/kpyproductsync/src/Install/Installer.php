<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyProductSync\Install;

use Db;
use Module;
use PrestaShop\Module\KpyProductSync\Config\Config;
use PrestaShopBundle\Install\SqlLoader;

class Installer
{
    private array $hooks = [
        'actionProductUpdate',
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

        // $this->deleteConfigurations();

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
        if (!\Configuration::get(Config::APP_DB)) {
            \Configuration::updateValue(Config::APP_DB, 'db');
        }

        if (!\Configuration::get(Config::APP_DB_USER)) {
            \Configuration::updateValue(Config::APP_DB_USER, 'user');
        }

        if (!\Configuration::get(Config::APP_DB_PASSWORD)) {
            \Configuration::updateValue(Config::APP_DB_PASSWORD, 'passwd');
        }

        if (!\Configuration::get(Config::APP_DB_HOST)) {
            \Configuration::updateValue(Config::APP_DB_HOST, 'localhost');
        }

        if (!\Configuration::get(Config::APP_DB_PORT)) {
            \Configuration::updateValue(Config::APP_DB_PORT, '3306');
        }
    }

    private function deleteConfigurations(): void
    {
        \Configuration::deleteByName(Config::APP_DB);
        \Configuration::deleteByName(Config::APP_DB_USER);
        \Configuration::deleteByName(Config::APP_DB_PASSWORD);
        \Configuration::deleteByName(Config::APP_DB_HOST);
        \Configuration::deleteByName(Config::APP_DB_PORT);
    }
}
