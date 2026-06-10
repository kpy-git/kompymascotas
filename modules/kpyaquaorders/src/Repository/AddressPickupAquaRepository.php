<?php

namespace PrestaShop\Module\KpyAquaOrders\Repository;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;

class AddressPickupAquaRepository
{
    public function savePudoId(string $addressId, string $pudoId): bool
    {
        $stmt = DbMssql::getInstance()->prepare("UPDATE DATAE03 SET CUE_CODIGO=? WHERE DELEGAGION=?");

        return $stmt->execute([$pudoId, $addressId]);
    }
}