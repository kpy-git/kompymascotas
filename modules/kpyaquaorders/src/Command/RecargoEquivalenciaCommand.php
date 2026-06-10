<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;

class RecargoEquivalenciaCommand extends AbstractCommand
{

    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::RECARGO);

    }

    /**
     * @inheritDoc
     */
    public function exec(Order $order): void
    {
        // pedidos de clientes con recargo de equivalencia y no están pagados aún, entrará en AQUA, pero bloqueado
        // (para poder darle al cliente el detalle de pedido con el recargo incluido para que se pague)
        if (!$this->aquaOrderController->existsOrder($order->id)) {
            (new NewOrderCommand($this->aquaOrderController))->exec($order);
            $this->aquaOrderController->orderBlock($order->id);
        }
    }
}