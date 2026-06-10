<?php
declare(strict_types=1);

namespace PrestaShop\Module\KpyCancellationRequest\Install;

use Db;
use Module;
use PrestaShopBundle\Install\SqlLoader;

class Installer
{
    private array $hooks = [
        'actionOrderStatusPostUpdate',
        'actionFrontControllerSetMedia',
        'displayOrderDetailActions',
        'actionFrontControllerSetVariables',
        'displayOrderDetail',
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

    private function createOrderStates(\Module $module): void
    {
        $orderStateInstaller = new OrderStateInstaller($module);

        $orderStateInstaller->install(
            \KpyCancellationRequest::CANCELLATION_REQUEST,
            "Solicitud de cancelación",
            "#ff7472"
        );

        $orderStateInstaller->install(
            \KpyCancellationRequest::CANCEL_CANCELLATION_REQUEST,
            "Solicitud de cancelación anulada",
            "#870095"
        );
    }
}
