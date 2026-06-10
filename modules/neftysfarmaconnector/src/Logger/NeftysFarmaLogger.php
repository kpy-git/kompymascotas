<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Logger;


use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\DTO\NeftysProduct;

class NeftysFarmaLogger
{
    public const string LOG_FILE = _PS_LOG_DIR_ . 'neftysfarmaconnector.log';

    public const bool LOG_UNSUPPORTED_ORDERS = true;

    public static function logOrder(\Order $order, array $products): void
    {
        if (!self::LOG_UNSUPPORTED_ORDERS) {
            return;
        }

        file_put_contents(
            NeftysFarmaConfig::ORDERS_PATH . $order->id . '.log',
            json_encode([
                "order_id" => $order->id,
                "error" => "Pedido no gestionable por Neftys Farma",
                "products" => array_map(static fn(NeftysProduct $product) => $product->toArray(), $products),
            ], JSON_PRETTY_PRINT)
        );

        self::log($order->id . ', pedido no gestionable por Neftys Farma');
    }

    public static function log(string $message): void
    {
        file_put_contents(self::LOG_FILE, date(DATE_ATOM) . ' - ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function clear(): void
    {
        if (file_exists(self::LOG_FILE)) {
            unlink(self::LOG_FILE);
        }
    }
}