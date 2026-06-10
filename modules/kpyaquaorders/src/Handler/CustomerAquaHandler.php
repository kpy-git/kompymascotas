<?php

namespace PrestaShop\Module\KpyAquaOrders\Handler;

use Address;
use Customer;
use Order;
use PrestaShop\Module\KpyAquaOrders\Entity\CustomerAqua;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class CustomerAquaHandler
{
    /**
     * @throws KpyAquaSqlException
     */
    public function updateCustomerAqua(Order $order): void
    {
        $address = new Address($order->id_address_delivery);
        $customer = new Customer($order->id_customer);

        $customerAqua = new CustomerAqua($customer, $address, $order->id);
        $customerAqua->export();
    }
}