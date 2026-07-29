<?php

namespace PrestaShop\Module\KpyProductSync\Query;

class AllProductsInfoQuery implements QueryInterface
{
    public function fetch(array $params = []): array
    {
        return \Db::getInstance()->executeS(
            "SELECT p.id_product,
                   ifnull(pa.id_product_attribute, 0) as id_product_attribute,
                   (p.weight + ifnull(pa.weight, 0)) as weight,
                   p.id_manufacturer,
                   if(packs.id_product_pack is null, 'no', 'si') as `pack`
            FROM " . _DB_PREFIX_ ."product p
            left join " . _DB_PREFIX_ ."product_attribute pa
                on pa.id_product = p.id_product
            left join (
                select distinct id_product_pack from " . _DB_PREFIX_ ."kpy_packs
            ) as packs
                on packs.id_product_pack = CONCAT_WS('-', p.id_product, ifnull(pa.id_product_attribute, 0))"
        );
    }

    public function getName(): string
    {
        return 'kpyproductsync.query.all_product_info';
    }
}