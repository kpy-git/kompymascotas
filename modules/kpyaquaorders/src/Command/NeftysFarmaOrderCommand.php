<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;

class NeftysFarmaOrderCommand extends AbstractCommand
{

    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::NEFTYS_ORDER);
    }

    public function exec(Order $order): void
    {
        $this->aquaOrderController->moveToNeftysFarmaWarehouse($order->id);
    }
}