<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Repository\StockRepository;

class DistrivetOrderGuard
{
    public static function isDistrivetOrder(\Order $order): bool
    {
        try {
            new ProductFinder()->getProductsOrderWithoutPacks($order);
            return true;

        } catch (KpyDistrivetException $e) {
            return false;
        }
    }

    public static function isDistrivetProduct(int $productId, int $productAttributeId): bool
    {
        try {
            new StockRepository()->findBySKUOrFail($productId . "-" . $productAttributeId);
            return true;

        } catch (KpyDistrivetException $e) {
            return false;
        }
    }
}