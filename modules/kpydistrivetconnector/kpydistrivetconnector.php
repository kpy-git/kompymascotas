<?php

declare(strict_types=1);

use PrestaShop\Module\KpyDistrivetConnector\Config\Config;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetProductNotFoundException;
use PrestaShop\Module\KpyDistrivetConnector\Install\Installer;
use PrestaShop\Module\KpyDistrivetConnector\Logger\DistrivetLogger;
use PrestaShop\Module\KpyDistrivetConnector\Repository\OrderRepository;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetClient;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetOrderBuilder;
use PrestaShop\Module\KpyDistrivetConnector\Service\ProductFinder;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyDistrivetConnector extends Module
{
	public function __construct()
	{
        $this->name = 'kpydistrivetconnector';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        
        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Distrivet Connector', [], 'Modules.Kpydistrivetconnector.Admin');
        $this->description = $this->trans('Handler of Distrivet API', [], 'Modules.Kpydistrivetconnector.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpydistrivetconnector.Admin');

	}

	/**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $installer = new Installer();

        return $installer->install($this);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $installer = new Installer();

        return $installer->uninstall($this);
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

    public function hookActionKpyOrderDispatched(array $params): void
    {
        if ($params['warehouse'] !== 'DISTRIVET') {
            return;
        }

        try {
            $order = new Order((int)$params['id_order']);
            $productFinder = new ProductFinder();

            $productsWithoutPacks = $productFinder->getProductsOrderWithoutPacks($order);

            $distrivetOrder = DistrivetOrderBuilder::from($order, $productsWithoutPacks);

            $distrivetClient = new DistrivetClient();
            $distrivetOrderId = $distrivetClient->createOrder($distrivetOrder);

            if (Config::DEBUG_MODE) {
                DistrivetLogger::logOrder($distrivetOrder, $this->getLocalPath());
            }

            $order->setCurrentState(\Configuration::get(Config::DISTRIVET_OS));

            $orderRepository = new OrderRepository();
            $orderRepository->save($distrivetOrder, $distrivetOrderId);

        } catch (KpyDistrivetProductNotFoundException $exception) {
            DistrivetLogger::log("Pedido no gestionable por Distrivet. Error: " . $exception->getMessage());

        } catch (KpyDistrivetException $e) {
            DistrivetLogger::log($e->getMessage());
        }
    }
}
