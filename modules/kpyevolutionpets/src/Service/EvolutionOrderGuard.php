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
        }

        return true;
    }
}