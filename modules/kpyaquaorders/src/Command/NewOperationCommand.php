<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use PrestaShop\Module\KpyAquaOrders\Handler\AquaOperationHandler;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class NewOperationCommand extends AbstractCommand
{

    /**
     * @throws \PrestaShopException
     * @throws KpyAquaOrderException
     * @throws KpyAquaSqlException
     */
    public function exec(Order $order): void
    {
        $operationHandler = new AquaOperationHandler(DbMssql::getInstance());
        $operationHandler->crearOperacion($order);
    }
}