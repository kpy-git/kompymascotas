<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Context;
use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;

class OrderRenameCommand extends AbstractCommand
{
    public function __construct(private readonly AquaOrderController $aquaOrderController,
                                private readonly NewOperationCommand $newOperationCommand)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::RENAME_ORDER);
    }

    public function isSupported(Order $order, Context $context): bool
    {
        if ($context->employee && ($context->employee->id_profile === 1 || in_array($context->employee->id, [36, 37]))) {
            return parent::isSupported($order, $context);
        }

        return false;
    }

    public function exec(Order $order): void
    {
        // Añade una X al final del número de documento en AQUA y vuelve a meter el pedido
        $this->aquaOrderController->renameOrder($order->id);

        $this->newOperationCommand->exec($order);
    }
}