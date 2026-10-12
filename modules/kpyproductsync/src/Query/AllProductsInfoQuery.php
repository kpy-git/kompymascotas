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
                   if(packs.id_product_pack is null, 'no', 'si') as `pack`,
                   if(pa.id_product_attribute is null or ps.active = 0, ps.active, ifnull(kpa.active, 1)) as `active`
            FROM " . _DB_PREFIX_ ."product p
            left join " . _DB_PREFIX_ ."product_attribute pa
                on pa.id_product = p.id_product
            inner join " . _DB_PREFIX_ ."product_shop ps
                on ps.id_product = p.id_product and ps.id_shop = 1
            left join " . _DB_PREFIX_ ."kpy_product_attribute kpa
                on kpa.id_product_attribute=pa.id_product_attribute
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