<?php

namespace PrestaShop\Module\KpyStorePreparation\Repository;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;

class OrdersPendingConfirmationRepository
{
    public function addOrder(int $orderId): void
    {
        try {
            $stmt = DbMssql::getInstance()->prepare(
                "INSERT INTO DATKPYORDERPENDSTOCK03 (PEDIDO) VALUES (?)"
            );

            $stmt->bindValue(1, $orderId);
            $stmt->execute();

        } catch (\PDOException $e) {
            file_put_contents(_PS_LOG_DIR_ . 'kpyastorepreparation.log', date(DATE_ATOM) . ": " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}