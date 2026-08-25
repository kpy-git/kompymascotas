<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;

class DistrivetOrderGuard
{
    public static function isDistrivetOrder(\Order $order): bool
    {
        try {
            (new ProductFinder())->getProductsOrderWithoutPacks($order);
            return true;
        } catch (KpyDistrivetException $e) {
            return false;
        }
    }
}