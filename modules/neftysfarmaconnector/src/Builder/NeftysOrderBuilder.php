<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Builder;

use PrestaShop\Module\NeftysFarmaConnector\Entity\NeftysFarmaOrder;
use PrestaShop\Module\NeftysFarmaConnector\Entity\Receiver;

class NeftysOrderBuilder
{

    public static function from(\Order $order, array $products): NeftysFarmaOrder
    {
        $neftysOrder = new NeftysFarmaOrder($order->id);

        $receiver = new Receiver();
        $receiver->setEmail($order->getCustomer()->email);

        $deliveryAddress = \Address::initialize($order->id_address_delivery);

        $receiver
            ->setName($deliveryAddress->firstname . ' ' . $deliveryAddress->lastname)
            ->setAddress($deliveryAddress->address1 . ' ' . $deliveryAddress->address2)
            ->setPostCode($deliveryAddress->postcode)
            ->setCity($deliveryAddress->city)
            ->setPhoneNumber($deliveryAddress->phone ?: $deliveryAddress->phone_mobile)
            ->setState(\State::getNameById($deliveryAddress->id_state));

        $neftysOrder->setReceiver($receiver);

        foreach ($products as $product) {
            $neftysOrder->addProduct($product);
        }

        if (in_array($order->module, ['cashondelivery', 'codfee', 'cashondeliveryplus', 'kpycashondelivery'])) {
            $neftysOrder
                ->setIsCRM(true)
                ->setTotalPaid($order->total_paid);
        }

        return $neftysOrder;
    }
}