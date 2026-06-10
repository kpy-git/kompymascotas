<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Config;

class NeftysFarmaConfig
{
    public const ORDER_STATES = 'NEFTYS_FARMA_ORDER_STATES';

    public const SFTP_USER = 'NEFTYS_FARMA_SFTP_USER';
    public const SFTP_PASSWORD = 'NEFTYS_FARMA_SFTP_PASSWORD';
    public const SFTP_SERVER = 'NEFTYS_FARMA_SFTP_SERVER';
    public const SFTP_PORT = 'NEFTYS_FARMA_SFTP_PORT';

    public const TOKEN = 'NEFTYS_FARMA_TOKEN';

    public const string NEFTYS_FARMA_ORDERS_TABLE = 'neftys_orders';

    public const string NEFTYS_FARMA_STOCK_TABLE = 'neftys_stock';

    public const string ORDERS_PATH = _PS_MODULE_DIR_ . 'neftysfarmaconnector/orders/';

    public const string NEFTYS_OS_TRANSMITTED = 'NEFTYS_OS_TRANSMITTED';
}