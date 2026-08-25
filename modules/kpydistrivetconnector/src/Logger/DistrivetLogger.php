<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Logger;

use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetOrderDTO;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DistrivetLogger
{
    private const string LOG_FILE = _PS_LOG_DIR_ . '/distrivetconnector.log';

    public static function logOrder(DistrivetOrderDTO $order, string $path): void
    {
        file_put_contents($path . 'orders/' . $order->getOrderId() . '.json' , json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

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

    public static function logResponse(ResponseInterface $response): void
    {
        file_put_contents(_PS_LOG_DIR_ . '/distrivetconnector_response.json', json_encode([
            'code' => $response->getStatusCode(),
            'time' => date(DATE_ATOM),
            'content' => $response->toArray(false),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function logRequest(DistrivetOrderDTO $order): void
    {
        file_put_contents(_PS_LOG_DIR_ . '/distrivetconnector_request.json', json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}