<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

interface CommandInterface
{
    /**
     * @throws KpyAquaOrderException
     * @throws KpyAquaSqlException
     */
    public function exec(Order $order): void;
}