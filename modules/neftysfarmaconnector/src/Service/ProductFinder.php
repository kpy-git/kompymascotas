<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Service;

use PrestaShop\Module\NeftysFarmaConnector\DTO\NeftysProduct;

class ProductFinder
{
    public function getProductsOrderWithoutPacks(\Order $order): array
    {
        $products = [];

        foreach ($order->getProducts() as $productOrder) {
            // echo print_r($productOrder, true);
            $sku = $productOrder['id_product'] . '-' . $productOrder['product_attribute_id'];
            if (\Product::isPack($sku)) {
                foreach (\Product::getProductsInPack($sku) as $productInPack) {
                    if ($productInPack['is_gift']) {
                        continue;
                    }

                    $product = new NeftysProduct($productInPack['sku']);
                    $product
                        ->setEan(\Product::getEanBySku($productInPack['sku']))
                        ->setQuantity((int)$productOrder['product_quantity'] * (int)$productInPack['quantity']);

                    $products[] = $product;
                }

                continue;
            }

            $product = new NeftysProduct($sku);
            $product
                ->setEan($productOrder['product_ean13'])
                ->setQuantity((int)$productOrder['product_quantity']);

            $products[] = $product;
        }

        return $products;
    }

    public function getMonoproductPacksByProduct(int $productId, int $productAttributeId): array
    {
        $sql = "SELECT pp.id_product_pack, pp.quantity
            FROM " . _DB_PREFIX_ . "kpy_packs pp
            INNER JOIN (
                SELECT pp.id_product_pack
                FROM " . _DB_PREFIX_ . "kpy_packs pp
                WHERE pp.id_product_item = {$productId} and pp.id_product_attribute_item = {$productAttributeId}
            ) AS product_packs ON pp.id_product_pack=product_packs.id_product_pack
            GROUP BY id_product_pack
            HAVING COUNT(*) = 1";

        return  \Db::getInstance()->executeS($sql);
    }
}