<?php

namespace PrestaShop\Module\KpySpecialPrices\Service;

use Db;

class SpecialPriceCleaner
{
    public function deleteExpired(bool $saveHistory = true): int
    {
        if ($saveHistory) {
            Db::getInstance()->execute(
                "insert into " . _DB_PREFIX_ . "kpy_special_price_history (id_product, id_product_attribute, id_shop, special_price, special_discount, units_sold, date_to, date_from)
                    select spc.id_product,
                           sp.id_product_attribute,
                           spc.id_shop,
                           round(ps.price+ifnull(pas.price, 0) * if(ps.id_tax_rules_group=1, 1.1, 1.21) * (1-ifnull(sp.reduction, 0)), 2) as `special_price`,
                           special_discount,
                           0 as `units_sold`, 
                           date_from, 
                           `expire` as date_to
                    from " . _DB_PREFIX_ . "kpy_special_price spc
                    inner join " . _DB_PREFIX_ . "product_shop ps
                        on ps.id_product = spc.id_product and ps.id_shop = spc.id_shop
                    left join " . _DB_PREFIX_ . "product_attribute_shop pas
                        on pas.id_product_attribute=spc.id_product_attribute and pas.id_shop=ps.id_shop
                    left join " . _DB_PREFIX_ . "specific_price sp
                        on sp.id_product=spc.id_product and sp.id_product_attribute=spc.id_product_attribute
                        and sp.id_shop = pas.id_shop
                        and sp.`to` = spc.expire
                    where now() > `expire`"
            );

            Db::getInstance()->execute(
                "update " . _DB_PREFIX_ . "kpy_special_price_history sph
                inner join (
                    with products_expired as (
                        select id_product, id_product_attribute, date_from, `expire`, id_shop
                        from " . _DB_PREFIX_ . "kpy_special_price
                        where now() > `expire`)
                    select od.product_id,
                           od.product_attribute_id,
                           sum(od.product_quantity) as units_sold,
                           o.id_shop,
                           p.date_from,
                           p.expire
                    from " . _DB_PREFIX_ . "order_detail od
                    inner join products_expired p on
                        od.product_id = p.id_product and od.product_attribute_id = p.id_product_attribute
                    inner join " . _DB_PREFIX_ . "orders o
                        on o.id_order = od.id_order and o.id_shop = p.id_shop
                    where o.date_add between p.date_from and p.expire
                    group by od.product_id, od.product_attribute_id, o.id_shop, p.date_from, p.expire
                ) as products
                    on sph.id_product = products.product_id
                        and sph.id_product_attribute=products.product_attribute_id
                        and sph.id_shop=products.id_shop
                        and sph.date_from=products.date_from
                        and sph.date_to=products.expire
                set sph.units_sold=products.units_sold;"
            );
        }

        $count = Db::getInstance()->getValue("
            select count(*)
            from " . _DB_PREFIX_ . "kpy_special_price
            where now() > `expire`
        ");

        \Db::getInstance()->delete('kpy_special_price', 'now() > `expire`');

        return $count;
    }
}