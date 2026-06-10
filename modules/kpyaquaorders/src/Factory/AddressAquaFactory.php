<?php

namespace PrestaShop\Module\KpyAquaOrders\Factory;

use PrestaShop\Module\KpyAquaOrders\Config\AquaCarrier;
use PrestaShop\Module\KpyAquaOrders\Entity\AddressAqua;
use PrestaShop\Module\KpyAquaOrders\Entity\AddressDHLServicePoint;
use PrestaShop\Module\KpyAquaOrders\Entity\AddressPickupAqua;

class AddressAquaFactory
{
    public function getAddressAquaByCarrier(\Order $order): AddressAqua
    {
        $address = \Address::initialize($order->id_address_delivery);
        $customer = new \Customer($order->id_customer);
        $carrier = AquaCarrier::fromPS($order->id_carrier);

        return match ($carrier) {
            AquaCarrier::SEUR_PICKUP => new AddressPickupAqua($address, $customer, $order->id_cart, $order->id),
            AquaCarrier::DHL_SERVICE_POINT => new AddressDHLServicePoint($address, $customer, $order->id_cart, $order->id),
            default => new AddressAqua($address, $customer, (string)$order->id),
        };
    }
}