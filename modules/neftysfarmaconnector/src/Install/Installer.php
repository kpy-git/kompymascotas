<?php

declare(strict_types=1);

namespace PrestaShop\Module\NeftysFarmaConnector\Install;

use Db;
use Module;
use Configuration;
use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShopBundle\Install\SqlLoader;


class Installer
{

    /**
     * Module's installation entry point.
     *
     * @param \Module $module
     *
     * @return bool
     */
    public function install(Module $module): bool
    {
        if (!$this->registerHooks($module)) {
            return false;
        }
        
        if (!$this->executeSqlFromFile($module->getLocalPath() . 'src/Install/install.sql')) {
            return false;
        }

        $this->createConfiguration();

        (new OrderStateInstaller($module))->install(
            NeftysFarmaConfig::NEFTYS_OS_TRANSMITTED,
            'Pedido transmitido a Neftys Farma',
            '#00d1a0'
        );

        return true;
    }

    /**
     * @param \Module $module
     *
     * @return bool
     */
    public function uninstall(Module $module): bool
    {
        $this->deleteConfiguration();

        return $this->executeSqlFromFile($module->getLocalPath() . 'src/Install/uninstall.sql');

    }

    /**
     * Register hooks for the module.
     *
     * @see https://devdocs.prestashop.com/8/modules/concepts/hooks/
     *
     * @param \Module $module
     *
     * @return bool
     */
    private function registerHooks(Module $module): bool
    {
        $hooks = [
            'actionOrderStatusPostUpdate'
        ];

        return $module->registerHook($hooks);
    }

    /**
     * @param string $filepath
     *
     * @return bool
     */
    private function executeSqlFromFile(string $filepath): bool
    {
        if (!is_readable($filepath)) {
            return true;
        }

        $allowedCollations = ['utf8mb4_general_ci', 'utf8mb4_unicode_ci'];
        $databaseCollation = Db::getInstance()->getValue('SELECT @@collation_database');
        $sqlLoader = new SqlLoader();
        $sqlLoader->setMetaData([
            'NEFTYS_ORDER_TABLE' => _DB_PREFIX_ . NeftysFarmaConfig::NEFTYS_FARMA_ORDERS_TABLE,
            'NEFTYS_STOCK_TABLE' => _DB_PREFIX_ . NeftysFarmaConfig::NEFTYS_FARMA_STOCK_TABLE,
            'ENGINE_TYPE' => _MYSQL_ENGINE_,
            'COLLATION' => (empty($databaseCollation) || !in_array($databaseCollation, $allowedCollations)) ? '' : 'COLLATE ' . $databaseCollation,
        ]);

        return $sqlLoader->parseFile($filepath);
    }

    private function createConfiguration(): void
    {
        if (!Configuration::get(NeftysFarmaConfig::ORDER_STATES)) {
            Configuration::updateValue(NeftysFarmaConfig::ORDER_STATES, json_encode([2]));
        }

        if (!Configuration::get(NeftysFarmaConfig::SFTP_USER)) {
            Configuration::updateValue(NeftysFarmaConfig::SFTP_USER, '');
        }

        if (!Configuration::get(NeftysFarmaConfig::SFTP_PASSWORD)) {
            Configuration::updateValue(NeftysFarmaConfig::SFTP_PASSWORD, '');
        }

        if (!Configuration::get(NeftysFarmaConfig::SFTP_SERVER)) {
            Configuration::updateValue(NeftysFarmaConfig::SFTP_SERVER, '');
        }

        if (!Configuration::get(NeftysFarmaConfig::SFTP_PORT)) {
            Configuration::updateValue(NeftysFarmaConfig::SFTP_PORT, '');
        }

        if (!Configuration::get(NeftysFarmaConfig::TOKEN)) {
            Configuration::updateValue(NeftysFarmaConfig::TOKEN, $this->generateToken());
        }

    }

    private function deleteConfiguration(): void
    {
        Configuration::deleteByName(NeftysFarmaConfig::ORDER_STATES);
        Configuration::deleteByName(NeftysFarmaConfig::SFTP_USER);
        Configuration::deleteByName(NeftysFarmaConfig::SFTP_PASSWORD);
        Configuration::deleteByName(NeftysFarmaConfig::SFTP_SERVER);
        Configuration::deleteByName(NeftysFarmaConfig::SFTP_PORT);
        Configuration::deleteByName(NeftysFarmaConfig::TOKEN);
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }
}