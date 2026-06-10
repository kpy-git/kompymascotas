<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Handler\CustomerAquaHandler;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class UpdateCustomerCommand extends AbstractCommand
{
    /**
     * @throws KpyAquaOrderException
     */
    public function exec(Order $order): void
    {
        $customerHandler = new CustomerAquaHandler();
        $customerHandler->updateCustomerAqua($order);
    }
}