<?php

namespace PrestaShop\Module\KpySpecialPrices\Checker;

class Checker
{
    public static function hasSpecialPrice(int $shop, int $productId, int $productAttributeId = 0): bool
    {
        return (int)\Db::getInstance()->getValue(
                "SELECT EXISTS(SELECT 1
                FROM " . _DB_PREFIX_ . "kpy_special_price 
                WHERE id_product = {$productId} " .
                ($productAttributeId > 0 ? " AND id_product_attribute = $productAttributeId" : " ") ."
                    AND NOW() BETWEEN `date_from` AND `expire`
                    AND id_shop = $shop)"
            ) > 0;
    }
}