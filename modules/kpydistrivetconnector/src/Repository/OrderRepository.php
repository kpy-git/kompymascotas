<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Repository;

use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetOrderDTO;

class OrderRepository
{
    public function save(DistrivetOrderDTO $order, string $distrivetOrderId): void
    {
        \Db::getInstance()->execute(
            "INSERT INTO `" . _DB_PREFIX_ . "kpy_distrivet_orders` (`id_order`, `uploaded_at`, `distrivet_order_id`) 
                VALUES ({$order->getOrderId()}, NOW(), '{$distrivetOrderId}') 
                ON DUPLICATE KEY UPDATE `uploaded_at` = NOW(), `distrivet_order_id` = '{$distrivetOrderId}'"
        );
    }

    public function getNotesByOrderId(int $orderId): string
    {
        return \Db::getInstance()->getValue(
            "SELECT message 
                    FROM " . _DB_PREFIX_ . "message 
                    WHERE private = 0 and id_order = {$orderId}") ?: "";
    }
}