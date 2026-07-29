<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Guard;

use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\DTO\NeftysProduct;
use PrestaShop\Module\NeftysFarmaConnector\Service\ProductFinder;

class OrderGuard
{

    public static function areAllProductsSupported(array $products): bool
    {
        /** @var NeftysProduct $product */
        foreach ($products as $product) {
            $sql = "SELECT EXISTS (SELECT 1 
                FROM " . _DB_PREFIX_ . NeftysFarmaConfig::NEFTYS_FARMA_STOCK_TABLE . " 
                WHERE id_product={$product->getProductId()} 
                    and id_product_attribute={$product->getProductAttributeId()})";

            if ((int)\Db::getInstance()->getValue($sql) === 0) {
                return false;
            }
        }

        return true;
    }

    public static function isNeftysFarmaOrder(\Order $order): bool
    {
        $productsWithoutPacks = (new ProductFinder())->getProductsOrderWithoutPacks($order);

        return self::areAllProductsSupported($productsWithoutPacks);
    }
}