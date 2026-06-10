<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Repository;

use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\Entity\NeftysFarmaOrder;

class NeftysFarmaOrderRepository
{
    public function updateSynchronizationDate(NeftysFarmaOrder $order): void
    {
        \Db::getInstance()->insert(
            NeftysFarmaConfig::NEFTYS_FARMA_ORDERS_TABLE,
            ['id_order' => $order->getIdOrder(), 'date_sync' => date('Y-m-d H:i:s')],
            false,
            false,
            \Db::ON_DUPLICATE_KEY
        );
    }
}