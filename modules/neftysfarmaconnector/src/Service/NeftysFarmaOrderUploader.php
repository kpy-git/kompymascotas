<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Service;

use PrestaShop\Module\NeftysFarmaConnector\Entity\NeftysFarmaOrder;
use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use PrestaShop\Module\NeftysFarmaConnector\Repository\NeftysFarmaOrderRepository;

class NeftysFarmaOrderUploader
{
    /**
     * @throws NeftysFarmaException
     */
    public function uploadNeftysOrder(NeftysFarmaOrder $order, bool $deleteTempFile = true): void
    {
        $fileOrder = sprintf("%s/%d.csv", NeftysFarmaConfig::ORDERS_PATH, $order->getIdOrder());

        $fileWriter = new FileWriter();
        $fileWriter->writeOrder($order, $fileOrder);

        // FTPManager::getNeftysFarmaConnection()->uploadOrderFile($fileOrder);

        (new NeftysFarmaOrderRepository())->updateSynchronizationDate($order);

        if ($deleteTempFile) {
            unlink($fileOrder);
        }
    }

}