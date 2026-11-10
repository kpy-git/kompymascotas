<?php

namespace PrestaShop\Module\KpyOrderDispatcher\Service;


class OrderDispatcher
{
    public function dispatch(int $idOrder): void
    {
        $order = new \Order($idOrder);
        $warehouse = new WarehouseFinder()->getWarehouseByOrder($order);

        \Db::getInstance()->delete('kpy_orders_pending_dispatch', 'id_order = ' . $idOrder);
        \Db::getInstance()->insert('kpy_order_warehouse', [
            'id_order' => $idOrder,
            'warehouse' => $warehouse,
        ]);

        \Hook::exec('actionKpyOrderWarehouseSelected', [
            'id_order' => $idOrder,
            'warehouse' => $warehouse
        ]);

        \Hook::exec('actionKpyOrderDispatched', [
            'order' => $order,
        ]);
    }
}