<?php

use PrestaShop\Module\KpyAquaOrders\CommandDispatcher\CommandDispatcher;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\DTO\OutsyncOrder;
use PrestaShop\Module\KpyAquaOrders\Install\Installer;
use PrestaShop\Module\KpyAquaOrders\Service\Mailer;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyAquaOrders extends Module
{
    private string $logDirectory;

    public const string SECRET_KEY = 'KPY_AQUAORDERS_SECRET_KEY';

    public const string OUTSYNC_ORDERS_FILE = _PS_MODULE_DIR_ . 'kpyaquaorders/sin_sincronizar.csv';

    public function __construct()
    {
        $this->name = 'kpyaquaorders';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('KPY AQUA Orders', [], 'Modules.Kpyaquaorders.Admin');
        $this->description = $this->trans('AQUA orders integration', [], 'Modules.Kpyaquaorders.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyaquaorders.Admin');

        $this->ps_versions_compliancy = array('min' => '9.0', 'max' => _PS_VERSION_);

        $this->logDirectory = $this->getLocalPath() . 'log/';

    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        return (new Installer())->install($this);
    }

    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        return (new Installer())->uninstall($this);
    }

    public function hookActionOrderStatusPostUpdate($params): void
    {
        // evita hacer conexiones innecesarias para estados que no están contemplados
        if (!AquaOrderStateWarehouse::getInstance()->isSupported((int)$params['newOrderStatus']->id)) {
            return;
        }

        $aqua = DbMssql::getInstance();

        if ($aqua->withError()) {
            $this->addOrderToOutSyncFile(new OutsyncOrder((int)$params['id_order'], (int)$params['newOrderStatus']->id));
            return;
        }

        try {
            $commandDispatcher = new CommandDispatcher(new AquaOrderController($aqua));

            $commandDispatcher->dispatchAction(new Order((int)$params['id_order']), $this->context);

        } catch (KpyAquaOrderException $ex) {
            file_put_contents($this->logDirectory . 'orders_error.log',$ex->getFormatDate() . "\t" . $ex->getMessage() . PHP_EOL, FILE_APPEND);

        } catch (KpyAquaSqlException $exception) {
            $filename = 'sql_error_' . time() . '.log';
            $filePath = $this->logDirectory . $filename;

            $error = [
                'date' => date('d-m-Y H:i:s'),
                'method' => $exception->getMethod(),
                'error' => $exception->getMessage(),
                'sql' => $exception->getLastSql(),
                'sql_error' => $exception->getSqlError(),
            ];

            file_put_contents($this->logDirectory . $filename, json_encode($error, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

            $mailer = new Mailer();
            $mailer->sendMailError(
                (int)$params['id_order'],
                "Ha ocurrido un error en la base de datos al importar el pedido {$params['id_order']}\n\nLog: " . $filename,
                $exception->getMessage(),
                ['programacion@piensoymascotas.com'],
                [
                    'content' => file_get_contents($filePath),
                    'name' => $filename,
                    'mime' => 'text/plain',
                ]
            );
        }
    }

    private function addOrderToOutSyncFile(OutsyncOrder $order): void
    {
        if (($file = fopen(self::OUTSYNC_ORDERS_FILE, 'ab'))) {
            fwrite($file, $order->serialize() . "\n");
            fclose($file);
        }
    }

    public function getOutsyncOrdersFilename(): string
    {
        return self::OUTSYNC_ORDERS_FILE;
    }
}
