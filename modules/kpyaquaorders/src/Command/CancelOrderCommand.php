<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class CancelOrderCommand extends AbstractCommand
{
    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::CANCEL_ORDER);
    }

    /**
     * @inheritDoc
     */
    public function exec(Order $order): void
    {
        if (!$this->aquaOrderController->isModificable($order->id)) {
            throw new KpyAquaOrderException(__METHOD__ . ': El pedido no se puede cancelar, está en uso o ya ha sido procesado.');
        }

        $this->aquaOrderController->cancelAquaOrder($order->id);
    }
}