<?php

namespace PrestaShop\Module\KpyStorePreparation\Repository;

use PrestaShop\Module\KpyStorePreparation\Config\StorePreparationConfig;

class PriorityProductsRepository
{
    public function getPriorityProducts(): array
    {
        $brands = json_decode(\Configuration::get(StorePreparationConfig::PRIORITY_BRANDS, "[]"), true);

        if (empty($brands)) {
            return [];
        }

        $products = \Db::getInstance()->executeS(
            "select ps.id_product, ifnull(pas.id_product_attribute, 0) as id_product_attribute
                from " . _DB_PREFIX_ . "product_shop ps
                inner join (
                        select id_product 
                        from " . _DB_PREFIX_ . "product p 
                        where p.id_manufacturer in (" . implode(',', $brands) . ") and p.active=1
                    ) as filter_products on filter_products.id_product = ps.id_product
                left join " . _DB_PREFIX_ . "product_attribute_shop pas
                    on pas.id_product=ps.id_product
                where ps.active = 1
                    and ps.visibility = 1
                    and not exists (select 1 from " . _DB_PREFIX_ . "kpy_product_attribute kpa where kpa.id_product_attribute=pas.id_product_attribute and kpa.active=0)"
        );

        return array_map(static fn(array $row): string => $row['id_product'] . '-' . $row['id_product_attribute'], $products);
    }

    public function saveProducts(array $products): void
    {
        \Db::getInstance()->execute("TRUNCATE TABLE " . _DB_PREFIX_ . "kpy_store_priority_product");

        \Db::getInstance()->insert("kpy_store_priority_product", $products);
    }
}