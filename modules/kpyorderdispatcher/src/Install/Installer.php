<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyOrderDispatcher\Install;

use Db;
use Module;
use PrestaShop\Module\KpyOrderDispatcher\Config\Config;
use PrestaShopBundle\Install\SqlLoader;

class Installer
{
    private array $hooks = [
        'actionOrderStatusPostUpdate'
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

        $this->createHook();

        $this->createConfigurations();

        return true;
    }

    private function createHook(): void
    {
        $hook = new \Hook();
        $hook->name = 'actionKpyOrderWarehouseSelected';
        $hook->title = 'Compute destination warehouse for new orders';
        $hook->description = 'Hook triggered after compute destination warehouse for new orders';
        $hook->position = 1;
        $hook->add();
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

    public function createConfigurations(): void
    {
        if (!\Configuration::get(Config::KPY_ORDER_DISPATCHER_OS)) {
            \Configuration::updateValue(Config::KPY_ORDER_DISPATCHER_OS, json_encode([2, 3,]));
        }

        if (!\Configuration::get(Config::KPY_ORDER_DISPATCHER_SECRET)) {
            \Configuration::updateValue(Config::KPY_ORDER_DISPATCHER_SECRET, $this->generateToken());
        }

        if (!\Configuration::get(Config::KPY_ORDER_DISPATCHER_SECONDS_TTL)) {
            \Configuration::updateValue(Config::KPY_ORDER_DISPATCHER_SECONDS_TTL, 3600);
        }
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function deleteConfigurations(): void
    {
        \Configuration::deleteByName(Config::KPY_ORDER_DISPATCHER_OS);
        \Configuration::deleteByName(Config::KPY_ORDER_DISPATCHER_SECRET);
        \Configuration::deleteByName(Config::KPY_ORDER_DISPATCHER_SECONDS_TTL);
    }
}
