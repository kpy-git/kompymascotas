<?php

namespace PrestaShop\Module\KpyProductSync\Command;

use PrestaShop\Module\KpyProductSync\Database\AppDb;
use PrestaShop\Module\KpyProductSync\ValueObject\AppProduct;

readonly class SynchronizeAllProductsCommand
{

    public function execute(array $products): void
    {
        AppDb::getInstance()->execute("TRUNCATE TABLE kpy_product");

        $values = array_map(static function (AppProduct $product): array {
            return [
                'id_product' => $product->getProductCode()->getProductId(),
                'id_product_attribute' => $product->getProductCode()->getProductAttributeId(),
                'weight' => $product->getWeight(),
                'brand_id' => $product->getBrand(),
                'sales_price_es' => $product->getSalesPrice(),
                'is_pack' => (int)$product->isPack(),
                'is_jirafa' => (int)$product->isJirafa(),
            ];
        }, $products);

        AppDb::getInstance()->insertMany('kpy_product', $values);
    }
}