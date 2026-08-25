<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetProductOrderDTO;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetProductNotFoundException;
use PrestaShop\Module\KpyDistrivetConnector\Repository\StockRepository;

class ProductFinder
{
    /**
     * @throws KpyDistrivetProductNotFoundException
     */
    public function getProductsOrderWithoutPacks(\Order $order): array
    {
        $products = [];
        $stockRepository = new StockRepository();

        foreach ($order->getProducts() as $productOrder) {
            //echo print_r($productOrder, true);
            $sku = $productOrder['id_product'] . '-' . $productOrder['product_attribute_id'];
            if (\Product::isPack($sku)) {
                foreach (\Product::getProductsInPack($sku) as $productInPack) {
                    if ($productInPack['is_gift']) {
                        continue;
                    }

                    $distrivetProduct = $stockRepository->findBySKUOrFail($productInPack['sku']);

                    $products[] = new DistrivetProductOrderDTO(
                        $distrivetProduct->getDistrivetId(),
                        $distrivetProduct->getDistrivetName(),
                        (int)$productOrder['product_quantity'] * (int)$productInPack['quantity']
                    );
                }

                continue;
            }

            $distrivetProduct = $stockRepository->findBySKUOrFail($sku);

            $products[] = new DistrivetProductOrderDTO(
                $distrivetProduct->getDistrivetId(),
                $distrivetProduct->getDistrivetName(),
                (int)$productOrder['product_quantity']
            );
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

        return \Db::getInstance()->executeS($sql);
    }
}