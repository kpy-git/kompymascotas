<?php

namespace PrestaShop\Module\KpyEvolutionPets\Service;

use PrestaShop\Module\KpyEvolutionPets\Config\Config;

class EvolutionOrderGuard
{
    public static function isEvolutionOrder(\Order $order): bool
    {
        $products = $order->getProducts();
        $evolutionBrands = json_decode(\Configuration::get(Config::KPY_EVOLUTION_BRANDS), true);

        foreach ($products as $product) {
            if (!in_array($product['id_manufacturer'], $evolutionBrands)) {
                return false;
            }

            // los snacks no se pueden enviar sueltos desde Evolution Pets
            if (\Db::getInstance()->getValue(
                "select exists (select 1
                from " . _DB_PREFIX_ . "order_detail od
                where id_order = {$order->id}
                and exists (select 1 
                            from " . _DB_PREFIX_ . "category_product cp 
                            where cp.id_product = od.product_id 
                              and cp.id_category 
                              and cp.id_category=5576)) as `snacks`"
            )) {
                return false;
            }
        }

        return true;
    }
}