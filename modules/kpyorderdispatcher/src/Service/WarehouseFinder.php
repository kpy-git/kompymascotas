<?php

namespace PrestaShop\Module\KpyOrderDispatcher\Service;

use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetOrderGuard;
use PrestaShop\Module\KpyEvolutionPets\Config\Config;
use PrestaShop\Module\KpyEvolutionPets\Service\EvolutionOrderGuard;
use PrestaShop\Module\KpyStorePreparation\Guard\StorePriorityProductsGuard;
use PrestaShop\Module\NeftysFarmaConnector\Guard\OrderGuard;
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartProductLazyArray;

class WarehouseFinder
{
    public function getWarehouseByOrder(\Order $order): string
    {
        return match (true) {
            StorePriorityProductsGuard::withStorePriority($order) => 'TIENDA',
            OrderGuard::isNeftysFarmaOrder($order) => 'NEFTYS',
            DistrivetOrderGuard::isDistrivetOrder($order) => 'DISTRIVET',
            EvolutionOrderGuard::isEvolutionOrder($order) => 'EVOLUTION_PETS',
            default => 'TIENDA',
        };
    }

    public function getWarehousesByCart(int $cartId, array $products): array
    {
        if (StorePriorityProductsGuard::withStorePriorityByCartId($cartId)) {
            return ['TIENDA'];
        }

        $distrivetBrands = json_decode(\Configuration::get('KPY_DISTRIVET_MANUFACTURERS'), true);
        $evolutionBrands = json_decode(\Configuration::get(Config::KPY_EVOLUTION_BRANDS), true);

        $warehouses = [];

        /** @var CartProductLazyArray $product */
        foreach ($products as $product) {
            $warehouses[] = match (true) {
                in_array($product->id_manufacturer, $distrivetBrands) => 'DISTRIVET',
                in_array($product->id_manufacturer, $evolutionBrands) => 'EVOLUTION',
                OrderGuard::isProductSupported($product->id_product, $product->id_product_attribute) => 'NEFTYS',
                default => 'TIENDA',
            };
        }

        return array_unique($warehouses);
    }

    public function hasMultiWarehouse(int $idCart, array $products): bool
    {
        return count($this->getWarehousesByCart($idCart, $products)) > 1;
    }
}