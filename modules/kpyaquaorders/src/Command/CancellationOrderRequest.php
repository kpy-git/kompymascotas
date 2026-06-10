<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class CancellationOrderRequest extends AbstractCommand
{
    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::CANCELLATION_REQUEST);
    }

    /**
     * @inheritDoc
     */
    public function exec(Order $order): void
    {
        if (!$this->aquaOrderController->isModificable($order->id)) {
            throw new KpyAquaOrderException(__METHOD__ . ': El pedido ya no se puede modificar, esta en uso o ya ha sido procesado');
        }

        $this->aquaOrderController->orderBlock($order->id);
    }
}