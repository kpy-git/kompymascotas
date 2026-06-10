<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class CancelOrderRequestCommand extends AbstractCommand
{
    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $this->states = [107];
    }

    /**
     * @inheritDoc
     */
    public function exec(Order $order): void
    {
        if ($this->aquaOrderController->isModificable($order->id)) {
            throw new KpyAquaOrderException(__METHOD__ . ': intento de solicitud de cancelación bloqueado, el pedido no se puede modificar.');
        }

        $this->aquaOrderController->orderBlock($order->id);
    }
}