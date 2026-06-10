<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;

class CancelCancellationOrderRequest extends AbstractCommand
{
    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::CANCEL_CANCELLATION_REQUEST);
    }

    /**
     * @inheritDoc
     */
    public function exec(Order $order): void
    {
        $this->aquaOrderController->orderUnblock($order->id);
    }
}