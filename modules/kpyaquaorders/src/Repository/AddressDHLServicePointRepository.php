<?php

namespace PrestaShop\Module\KpyAquaOrders\Repository;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;

class AddressDHLServicePointRepository
{
    public function saveServicePointCodes(string $addressId, string $harmonisedId, string $psfKey): bool
    {
        $stmt = DbMssql::getInstance()->prepare(
            "INSERT INTO DATPYMSPDHL03 (DELEGACION, HARMONISEDID, PSFKEY) VALUES (?, ?, ?)"
        );

        return $stmt->execute([$addressId, $harmonisedId, $psfKey]);
    }
}