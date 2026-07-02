<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Repository;

use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;

class NeftysFarmaStockRepository
{
    public function save(array $stockNeftys): void
    {
        if (empty($stockNeftys)) {
            return;
        }

        \Db::getInstance()->execute("TRUNCATE TABLE " . _DB_PREFIX_ . NeftysFarmaConfig::NEFTYS_FARMA_STOCK_TABLE);

        \Db::getInstance()->insert(NeftysFarmaConfig::NEFTYS_FARMA_STOCK_TABLE, $stockNeftys);

        \Db::getInstance()->execute(
            "UPDATE " . _DB_PREFIX_ . "stock_available sa
                    INNER JOIN " . _DB_PREFIX_ . "neftys_stock ns 
                        on ns.id_product=sa.id_product 
                            and ns.id_product_attribute=sa.id_product_attribute
                    SET sa.quantity = ns.stock"
        );

        \Db::getInstance()->execute(
            "UPDATE " . _DB_PREFIX_ . "stock_available sa
                INNER JOIN (
                    SELECT id_product, SUM(stock) AS total_stock
                    FROM " . _DB_PREFIX_ . "neftys_stock
                    GROUP BY id_product
                ) ns_total ON sa.id_product = ns_total.id_product
                SET sa.quantity = ns_total.total_stock
                WHERE sa.id_product_attribute = 0"
        );
    }

    public function findAllProductsByEan(): array
    {
        $results = \Db::getInstance()->executeS(
            "SELECT p.id_product, IFNULL(pa.id_product_attribute, 0) as attr, IFNULL(pa.ean13, p.ean13) as ean
				FROM " . _DB_PREFIX_ . "product p
				LEFT JOIN " . _DB_PREFIX_ . "product_attribute pa
					ON pa.id_product = p.id_product
				WHERE p.visibility = 'both'
	                AND p.active = 1 
	                AND NOT EXISTS (SELECT 1 
						FROM " . _DB_PREFIX_ . "kpy_packs kp 
						WHERE kp.id_product_pack = CONCAT_WS('-', p.id_product, pa.id_product_attribute))"
        );

        $productsByEan = [];

        foreach ($results as $product) {
            if ($product['ean'] === null || strlen($product['ean']) < 7) {
                continue;
            }

            $productsByEan[(int)$product['ean']] = [
                'id_product' => (int)$product['id_product'],
                'id_product_attribute' => (int)$product['attr']
            ];
        }

        return $productsByEan;
    }
}