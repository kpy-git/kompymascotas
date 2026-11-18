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

        $warehouses = [];

        /** @var CartProductLazyArray $product */
        foreach ($products as $product) {
            $warehouses[] = match (true) {
                OrderGuard::isProductSupported($product->id_product, $product->id_product_attribute) => 'NEFTYS',
                DistrivetOrderGuard::isDistrivetProduct($product->id_product, $product->id_product_attribute) => 'DISTRIVET',
                EvolutionOrderGuard::isEvolutionProduct($product->id_manufacturer, $product->id_product) => 'EVOLUTION',
                default => 'TIENDA',
            };
        }

        return array_unique($warehouses);
    }

    public function hasMultiWarehouse(int $idCart, array $products): bool
    {
        // todo - si todos los productos del pedido superan el limite de gastos de envío se divide el envío, para el cliente no tiene efecto de multi-almacén
        return count($this->getWarehousesByCart($idCart, $products)) > 1;
    }
}