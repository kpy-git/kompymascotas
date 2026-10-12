<?php

namespace PrestaShop\Module\KpyProductSync\Query;

class SingleProductInfoQuery implements QueryInterface
{
    public function fetch(array $params = []): array
    {
        $productCode = $params['product_code'];

        if ($productCode->isCombinationProduct()) {
            return \Db::getInstance()->getRow(
                "select p.id_product,
                        pa.id_product_attribute,
                        p.weight + pa.weight as weight, 
                        p.id_manufacturer,
                        IF(exists(SELECT 1 FROM " . _DB_PREFIX_ . "kpy_packs WHERE id_product_pack=CONCAT_WS('-', p.id_product, pa.id_product_attribute)), 'si', 'no') as `pack`,
                        ifnull(kpa.active, 1) as `active`
                FROM " . _DB_PREFIX_ . "product p
                inner join " . _DB_PREFIX_ . "product_attribute pa
                    on pa.id_product = p.id_product
                left join " . _DB_PREFIX_ . "kpy_product_attribute kpa 
                    on pa.id_product_attribute = kpa.id_product_attribute
                where p.id_product = {$productCode->getProductId()}
                    and pa.id_product_attribute = {$productCode->getProductAttributeId()}"
            );
        }

        return \Db::getInstance()->getRow(
            "select p.weight, p.id_manufacturer, 'no' as `pack`, p.id_product, 0 as `id_product_attribute`, ps.active
            FROM " . _DB_PREFIX_ . "product p
            INNER JOIN `" . _DB_PREFIX_ . "product_shop` ps on p.id_product = ps.id_product and ps.id_shop = 1
            where p.id_product = {$productCode->getProductId()}"
        );
    }

    public function getName(): string
    {
        return 'kpyproductsync.query.single_product_info';
    }
}