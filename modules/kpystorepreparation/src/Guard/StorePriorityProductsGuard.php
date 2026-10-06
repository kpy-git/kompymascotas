<?php

namespace PrestaShop\Module\KpyStorePreparation\Guard;

class StorePriorityProductsGuard
{
    // si todos los productos del pedido están en stock y son productos que tienen prioridad para prepararse en tienda
    public function withStorePriority(\Order $order): bool
    {
        $products = \Db::getInstance()->executeS(
            "select od.product_id, od.product_attribute_id, od.product_quantity, ifnull(spp.stock, 0) as stock_tienda
            from kir21_order_detail od
            left join kir21_kpy_store_priority_product spp
                on spp.id_product=od.product_id and spp.id_product_attribute=od.product_attribute_id
            where od.id_order=" . $order->id
        );

        return array_all($products, fn($product) => $product['stock_tienda'] >= $product['product_quantity']);

    }
}