<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyOrderMargin\Install;

use Db;
use KpyOrderMargin;
use Module;
use PrestaShopBundle\Install\SqlLoader;

class Installer
{
    private array $hooks = [
        'actionKpyOrderDispatched',
        'actionOrderStatusPostUpdate',
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

    private function createConfigurations(): void
    {
        if (!\Configuration::get(KpyOrderMargin::KPY_ORDER_MARGIN_HTTP_CLIENT)) {
            \Configuration::updateValue(KpyOrderMargin::KPY_ORDER_MARGIN_HTTP_CLIENT, '');
        }

        if (!\Configuration::get(KpyOrderMargin::KPY_ORDER_MARGIN_HTTP_PASSWORD)) {
            \Configuration::updateValue(KpyOrderMargin::KPY_ORDER_MARGIN_HTTP_PASSWORD, '');
        }

        if (!\Configuration::get(KpyOrderMargin::KPY_ORDER_MARGIN_URL)) {
            \Configuration::updateValue(KpyOrderMargin::KPY_ORDER_MARGIN_URL, '');
        }
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

    private function deleteConfigurations(): void
    {
        \Configuration::deleteByName(KpyOrderMargin::KPY_ORDER_MARGIN_HTTP_CLIENT);
        \Configuration::deleteByName(KpyOrderMargin::KPY_ORDER_MARGIN_HTTP_PASSWORD);
        \Configuration::deleteByName(KpyOrderMargin::KPY_ORDER_MARGIN_URL);
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
