<?php

namespace PrestaShop\Module\KpyStorePreparation\Service;

use PrestaShop\Module\KpyStorePreparation\Repository\PriorityProductsRepository;

class PriorityProductsUpdater
{
    private PriorityProductsRepository $repository;

    private AquaStockFinder $aquaStockFinder;

    public function __construct()
    {
        $this->repository = new PriorityProductsRepository();
        $this->aquaStockFinder = new AquaStockFinder();
    }

    public function updateStock(): int
    {
        $skus = $this->repository->getPriorityProducts();

        $stockAqua = $this->aquaStockFinder->getStockByWarehouse($skus);

        $products = [];

        foreach ($stockAqua as $product) {
            [$id_product, $id_product_attribute] = explode('-', $product['SKU']);
            $products[] = [
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'stock' => (int)$product['STOCK'],
            ];

            $packs = \Product::getMonoproductPacksByProduct($id_product, $id_product_attribute);

            foreach($packs as $pack) {
                [$idPack, $attrPack] = explode('-', $pack['id_product_pack']);

                $products[] = [
                    'id_product' => (int)$idPack,
                    'id_product_attribute' => (int)$attrPack,
                    'stock' => floor((int)$product['STOCK'] / (int)$pack['quantity']),
                ];
            }
        }

        $this->repository->saveProducts($products);

        return count($products);
    }

}