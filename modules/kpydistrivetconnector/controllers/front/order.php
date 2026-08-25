<?php

use PrestaShop\Module\KpyDistrivetConnector\Logger\DistrivetLogger;
use PrestaShop\Module\KpyDistrivetConnector\Repository\OrderRepository;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetClient;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetOrderBuilder;
use PrestaShop\Module\KpyDistrivetConnector\Service\ProductFinder;

class KpyDistrivetConnectorOrderModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->ajax = true;

        $distrivetClient = new DistrivetClient();

        $order = new Order(866292);
        $productFinder = new ProductFinder();

        $productsWithoutPacks = $productFinder->getProductsOrderWithoutPacks($order);

        $distrivetOrder = DistrivetOrderBuilder::from($order, $productsWithoutPacks);

        $distrivetOrderid = $distrivetClient->createOrder($distrivetOrder);
        DistrivetLogger::logOrder($distrivetOrder);

        $orderRepository = new OrderRepository();
        $orderRepository->save($distrivetOrder, $distrivetOrderid);
    }
}