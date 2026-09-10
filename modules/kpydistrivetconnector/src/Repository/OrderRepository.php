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

    public function getDistrivetOrderId(int $orderId): string
    {
        return \Db::getInstance()->getValue(
            "SELECT distrivet_order_id FROM " . _DB_PREFIX_ . "kpy_distrivet_orders WHERE id_order = {$orderId}"
        ) ?: '';
    }

    public function getDistrivetShipmentId(int $orderId): string
    {
        return \Db::getInstance()->getValue(
            "SELECT distrivet_shipment_id FROM " . _DB_PREFIX_ . "kpy_distrivet_orders WHERE id_order = {$orderId}"
        ) ?: '';
    }

    public function saveTrackingNumber(int $orderId, string $trackingNumber): void
    {
        \Db::getInstance()->update('order_carrier', [
            'tracking_number' => $trackingNumber,
        ], 'id_order = ' . $orderId);
    }

    public function saveShipmentId(int $orderId, string $shipmentId): void
    {
        \Db::getInstance()->update('kpy_distrivet_orders', [
            'distrivet_shipment_id' => $shipmentId,
        ], 'id_order = ' . $orderId);
    }
}