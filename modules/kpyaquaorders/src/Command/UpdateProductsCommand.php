<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class UpdateProductsCommand extends AbstractCommand
{

    public function __construct(private readonly AquaOrderController $aquaOrderController,
                                private readonly NewOperationCommand $newOperationCommand)
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::UPDATE_PRODUCTS);

    }

    /**
     * @throws KpyAquaOrderException
     * @throws KpyAquaSqlException
     */
    public function exec(Order $order): void
    {
        if ($this->aquaOrderController->isModificable($order->id)) {
            throw new KpyAquaOrderException(__METHOD__ . ': El pedido no se puede modificar, está en uso o ya ha sido procesado.');
        }

        $this->aquaOrderController->deleteAquaOrder($order->id);
        $this->newOperationCommand->exec($order);
    }
}