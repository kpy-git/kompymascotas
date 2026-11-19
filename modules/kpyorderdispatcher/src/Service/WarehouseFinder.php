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
        return array_keys($this->getProductsByWarehouse($products));
    }

    public function getProductsByWarehouse(array $products): array
    {
        $productsByWarehouse = [];

        /** @var CartProductLazyArray $product */
        foreach ($products as $product) {
            $warehouse = match (true) {
                StorePriorityProductsGuard::withStorePriorityProduct($product->id_product, $product->id_product_attribute, $product->quantity) => 'TIENDA',
                OrderGuard::isProductSupported($product->id_product, $product->id_product_attribute) => 'NEFTYS',
                DistrivetOrderGuard::isDistrivetProduct($product->id_product, $product->id_product_attribute) => 'DISTRIVET',
                EvolutionOrderGuard::isEvolutionProduct($product->id_manufacturer, $product->id_product) => 'EVOLUTION',
                default => 'TIENDA',
            };

            if (!isset($productsByWarehouse[$warehouse])) {
                $productsByWarehouse[$warehouse] = [];
            }

            $productsByWarehouse[$warehouse][] = $product;
        }

        return $productsByWarehouse;
    }

    public function hasMultiWarehouse(array $products): bool
    {
        $productsByWarehouse = $this->getProductsByWarehouse($products);

        if (count(array_keys($productsByWarehouse)) <= 1) {
            return false;
        }

        // si la suma de todos los productos que tiene cada almacén supera el límite para los gastos de envío gratis, se puede dividir el pedido y el cliente no se ve afectado por el multi-almacén
        if (array_all(array_values($this->getAmountByWarehouse($productsByWarehouse)), static fn(float $totalAmountWarehouse): bool => $totalAmountWarehouse > _KPY_LIMIT_SHIPPING_FREE_)) {
            return false;
        }

        return true;
    }

    public function getAmountByWarehouse(array $productsByWarehouse): array
    {
        $amountByWarehouse = [];

        foreach ($productsByWarehouse as $warehouse => $products) {
            $amountByWarehouse[$warehouse] = array_reduce($products, function (float $carry, CartProductLazyArray $product): float {
                return $carry + $product->total_wt;
            }, 0);
        }

        return $amountByWarehouse;
    }
}