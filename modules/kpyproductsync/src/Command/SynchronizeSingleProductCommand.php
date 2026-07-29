<?php

namespace PrestaShop\Module\KpyProductSync\Command;

use PDO;
use PrestaShop\Module\KpyProductSync\Database\AppDb;
use PrestaShop\Module\KpyProductSync\ValueObject\AppProduct;

class SynchronizeSingleProductCommand
{
    public function execute(AppProduct $product): void
    {
        $appDb = AppDb::getInstance();

        $exists = $appDb->getValue("SELECT EXISTS (
            SELECT 1 FROM kpy_product 
                 WHERE id_product={$product->getProductCode()->getProductId()} 
                   and id_product_attribute={$product->getProductCode()->getProductAttributeId()})") > 0;

        $stmt =  $appDb->prepare(!$exists
            ? "INSERT INTO kpy_product (id_product, id_product_attribute, is_jirafa, is_pack, weight, brand_id, sales_price_es) 
                      VALUES (:id_product, :id_product_attribute, :is_jirafa, :is_pack, :weight, :brand_id, :sales_price_es)"
            : "UPDATE kpy_product
                SET is_jirafa = :is_jirafa,
                    is_pack = :is_pack,
                    weight = :weight,
                    sales_price_es = :sales_price_es,
                    brand_id = :brand_id
                WHERE id_product = :id_product
                    AND id_product_attribute = :id_product_attribute");

        $stmt->bindValue(':id_product', $product->getProductCode()->getProductId(), PDO::PARAM_INT);
        $stmt->bindValue(':id_product_attribute', $product->getProductCode()->getProductAttributeId(), PDO::PARAM_INT);
        $stmt->bindValue(':brand_id', $product->getBrand());
        $stmt->bindValue(':is_jirafa', $product->isJirafa(), PDO::PARAM_BOOL);
        $stmt->bindValue(':is_pack', $product->isPack(), PDO::PARAM_BOOL);
        $stmt->bindValue(':weight', $product->getWeight());
        $stmt->bindValue(':sales_price_es', $product->getSalesPrice());

        $stmt->execute();
    }
}