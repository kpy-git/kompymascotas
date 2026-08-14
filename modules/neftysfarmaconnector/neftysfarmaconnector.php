<?php

declare(strict_types=1);

use PrestaShop\Module\NeftysFarmaConnector\Builder\NeftysOrderBuilder;
use PrestaShop\Module\NeftysFarmaConnector\Guard\OrderGuard;
use PrestaShop\Module\NeftysFarmaConnector\Logger\NeftysFarmaLogger;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use PrestaShop\Module\NeftysFarmaConnector\Install\Installer;
use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\Service\NeftysFarmaOrderUploader;
use PrestaShop\Module\NeftysFarmaConnector\Service\ProductFinder;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class NeftysFarmaConnector extends Module
{
    public function __construct()
    {
        $this->name = 'neftysfarmaconnector';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.5',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Neftys Farma Connector', [], 'Modules.Mymodule.Admin');
        $this->description = $this->trans('Sincronización de stock y pedidos con Neftys Farma', [], 'Modules.Mymodule.Admin');

        $this->confirmUninstall = $this->trans('¿Seguro que quieres desinstalar el módulo?', [], 'Modules.Mymodule.Admin');

    }

    /**
     * @return bool
     */
    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        return (new Installer())->install($this);
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        return (new Installer())->uninstall($this);
    }

    /**
     * @see https://devdocs.prestashop.com/8/modules/creation/module-translation/new-system/#translating-your-module
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function hookActionKpyOrderWarehouseSelected(array $params): void
    {
        if ($params['warehouse'] !== 'NEFTYS') {
            return;
        }

        $order = new Order((int)$params['id_order']);
        $productFinder = new ProductFinder();

        $productsWithoutPacks = $productFinder->getProductsOrderWithoutPacks($order);

        if (!OrderGuard::areAllProductsSupported($productsWithoutPacks)) {
            NeftysFarmaLogger::logOrder($order, $productsWithoutPacks);
            return;
        }

        try {
            foreach ($productsWithoutPacks as $product) {
                if ($product->getProductId() === 8299) {
                    $order->setCurrentStateWithDate(
                        37, // SIN STOCK EN NEFTYS - PENDIENTE DE REENVIAR
                        date('Y-m-d H:i:s')
                    );

                    return;
                }
            }

            $neftysOrder = NeftysOrderBuilder::from($order, $productsWithoutPacks);

            $uploader = new NeftysFarmaOrderUploader();
            $uploader->uploadNeftysOrder($neftysOrder, false);

            $order->setCurrentStateWithDate(
                (int)Configuration::get(NeftysFarmaConfig::NEFTYS_OS_TRANSMITTED),
                date('Y-m-d H:i:s')
            );

        } catch (NeftysFarmaException $ex) {
            NeftysFarmaLogger::log($ex->getMessage());
        }

    }
}
