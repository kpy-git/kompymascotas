<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Handler\AddressAquaHandler;
use PrestaShop\Module\KpyAquaOrders\Handler\CustomerAquaHandler;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class UpdateAddressCommand extends AbstractCommand
{
    public function __construct()
    {
        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::UPDATE_ADDRESS);

    }

    /**
     * @throws KpyAquaSqlException
     */
    public function exec(Order $order): void
    {
        // nuevos clientes de pedidos por transferencia no tendrían el cliente en AQUA al cambiar la dirección
        $customerHandler = new CustomerAquaHandler();
        $customerHandler->updateCustomerAqua($order);

        $addressHandler = new AddressAquaHandler(new AquaOrderController(DbMssql::getInstance()));
        $addressHandler->updateAddressAqua($order);
    }
}