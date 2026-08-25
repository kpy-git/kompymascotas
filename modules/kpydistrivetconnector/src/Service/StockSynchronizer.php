<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Service;

use PrestaShop\Module\KpyDistrivetConnector\Config\Config;
use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetStockProductDTO;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Repository\StockRepository;

class StockSynchronizer
{
    private StockRepository $stockRepository;

    private ProductFinder $productFinder;

    private int $productSynchronizedCount;

    public function __construct()
    {
        $this->stockRepository = new StockRepository();
        $this->productFinder = new ProductFinder();
        $this->productSynchronizedCount = 0;
    }

    public function synchronize(array $globalStock): void
    {
        try {
            $allowedManufacturers = json_decode(\Configuration::get(Config::KPY_DISTRIVET_MANUFACTURERS), true);

            $kpyProductsByEAN = $this->stockRepository->findAllProductsByEan();

            $distrivetStock = [];
            $skus = [];

            foreach ($globalStock as $stock) {
                if (!isset($stock['Barcode'], $kpyProductsByEAN[$stock['Barcode']])) {
                    continue;
                }

                $product = $kpyProductsByEAN[$stock['Barcode']];

                if (!in_array($product['manufacturer'], $allowedManufacturers)) {
                    continue;
                }

                // EANS duplicados en el catálogo de distrivet dan como resultado productos duplicados
                if (in_array($product['id_product'] . '-' . $product['id_product_attribute'], $skus)) {
                    continue;
                }

                $distrivetStock[] = new DistrivetStockProductDTO(
                    (int)$product['id_product'],
                    (int)$product['id_product_attribute'],
                    $stock['ProductId'],
                    $stock['Description'],
                    (int)$stock['Stock'],
                    new \DateTimeImmutable($stock['UpdateDatetime']),
                );
                $skus[] = $product['id_product'] . '-' . $product['id_product_attribute'];

                $this->productSynchronizedCount++;

                $productPacks = $this->productFinder->getMonoproductPacksByProduct($product['id_product'], $product['id_product_attribute']);

                foreach ($productPacks as $pack) {
                    [$idPack, $attrPack] = explode('-', $pack['id_product_pack']);

                    $distrivetStock[] = new DistrivetStockProductDTO(
                        (int)$idPack,
                        (int)$attrPack,
                        $stock['ProductId'],
                        sprintf("Pack %d x %s", $pack['quantity'], $stock['Description']),
                        floor((int)$stock['Stock'] / (int)$pack['quantity']),
                        new \DateTimeImmutable($stock['UpdateDatetime']),
                        true,
                    );

                    $skus[] = $idPack . '-' . $attrPack;
                    $this->productSynchronizedCount++;
                }
            }

            $this->stockRepository->save($distrivetStock);

        } catch (KpyDistrivetException $exception) {
            file_put_contents(_PS_LOG_DIR_ . '/distrivet.log', "\n" . date(DATE_ATOM) . ": " . $exception->getMessage(), FILE_APPEND | LOCK_EX);
        }
    }

    public function getCountProductsSynchronized(): int
    {
        return $this->productSynchronizedCount;
    }
}