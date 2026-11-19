<?php

namespace PrestaShop\Module\KpyOrderDispatcher\Service;

use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetOrderGuard;
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

    public function getWarehousesByCart(array $products): array
    {
        $warehouses = [];

        /** @var CartProductLazyArray $product */
        foreach ($products as $product) {
            $warehouses[] = match (true) {
                StorePriorityProductsGuard::withStorePriorityProduct($product->id_product, $product->id_product_attribute, $product->quantity) => 'TIENDA',
                OrderGuard::isProductSupported($product->id_product, $product->id_product_attribute) => 'NEFTYS',
                DistrivetOrderGuard::isDistrivetProduct($product->id_product, $product->id_product_attribute) => 'DISTRIVET',
                EvolutionOrderGuard::isEvolutionProduct($product->id_manufacturer, $product->id_product) => 'EVOLUTION',
                default => 'TIENDA',
            };
        }

        return array_unique($warehouses);
    }

    public function hasMultiWarehouse(array $products): bool
    {
        // si todos los productos del pedido superan el límite de gastos de envío gratis se divide el envío, para el cliente no tiene efecto de multi-almacén
        if (array_all($products, static fn (CartProductLazyArray $product): bool => $product->total_wt > _KPY_LIMIT_SHIPPING_FREE_)) {
            return false;
        }

        return count($this->getWarehousesByCart($products)) > 1;
    }
}