<?php

namespace PrestaShop\Module\KpyOrderDispatcher\Service;

use Order;
use PrestaShop\Module\NeftysFarmaConnector\Guard\OrderGuard;

class OrderDispatcher
{
    public function dispatch(int $id_order): void
    {
        $warehouse = match (true) {
            OrderGuard::isNeftysFarmaOrder(new Order($id_order)) => 'NEFTYS',
            default => 'TIENDA',
        };

        \Hook::exec('actionKpyOrderWarehouseSelected', [
            'id_order' => $id_order,
            'warehouse' => $warehouse
        ]);

        \Db::getInstance()->delete('kpy_orders_pending_dispatch', 'id_order = ' . $id_order);
        \Db::getInstance()->insert('kpy_order_warehouse', [
            'id_order' => $id_order,
            'warehouse' => $warehouse,
        ]);
    }
}