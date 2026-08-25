<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetOrderDTO;
use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetReceiptDTO;
use PrestaShop\Module\KpyDistrivetConnector\Repository\OrderRepository;

class DistrivetOrderBuilder
{
    public static function from(\Order $order, array $products): DistrivetOrderDTO
    {
        $deliveryAddress = \Address::initialize($order->id_address_delivery);

        $receipt = new DistrivetReceiptDTO($deliveryAddress, $order->getCustomer()->email);

        $distrivetOrder = new DistrivetOrderDTO($order->id, $receipt);

        foreach ($products as $product) {
            $distrivetOrder->addProduct($product);
        }

        if (in_array($order->module, ['cashondelivery', 'codfee', 'cashondeliveryplus', 'kpycashondelivery'])) {
            $distrivetOrder->setCRM($order->total_paid);
        }

        $orderRepository = new OrderRepository();

        if (($notes = $orderRepository->getNotesByOrderId($order->id))) {
            $distrivetOrder->setNotes($notes);
        }

        return $distrivetOrder;
    }
}