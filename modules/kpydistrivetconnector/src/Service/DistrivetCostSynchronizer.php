<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\Config\Config;
use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetCostDTO;
use PrestaShop\Module\KpyDistrivetConnector\Repository\StockRepository;

class DistrivetCostSynchronizer
{
    private StockRepository $stockRepository;

    private ProductFinder $productFinder;

    private DistrivetClient $distrivetClient;

    public int $countProducts = 0;

    public function __construct()
    {
        $this->stockRepository = new StockRepository();
        $this->productFinder = new ProductFinder();
        $this->distrivetClient = new DistrivetClient();
    }

    public function getAllProductsCost(int $limit): array
    {
        $distrivetProducts = $this->stockRepository->findAllProductsIndexByDistrivet($limit);

        return $this->getDistrivetCosts($distrivetProducts);
    }

    public function getProductsCostByManufacturer(int $manufacturerId, int $limit): array
    {
        $distrivetProducts = $this->stockRepository->findManufacturerProductsIndexByDistrivet($manufacturerId, $limit);

        return $this->getDistrivetCosts($distrivetProducts);
    }

    private function getDistrivetCosts(array $distrivetProducts, bool $packsIncluded = true): array
    {

        $productsCosts = [];

        foreach ($distrivetProducts as $distrivetId => $sku) {
            $costPrice = $this->distrivetClient->getProductCost($distrivetId);

            [$productId, $productAttributeId] = explode('-', $sku);

            $productsCosts[] = new DistrivetCostDTO(
                $productId,
                $productAttributeId,
                $distrivetId,
                $costPrice
            );

            $this->countProducts++;

            if ($packsIncluded) {
                $productPacks = $this->productFinder->getMonoproductPacksByProduct($productId, $productAttributeId);

                foreach ($productPacks as $pack) {
                    [$idPack, $attrPack] = explode('-', $pack['id_product_pack']);

                    $productsCosts[] = new DistrivetCostDTO(
                        $idPack,
                        $attrPack,
                        '',
                        round($costPrice * (int)$pack['quantity'], 6)
                    );
                }
            }
        }

        return $productsCosts;
    }

    public function getCountSynchronizedProducts(): int
    {
        return $this->countProducts;
    }
}