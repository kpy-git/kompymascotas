<?php

namespace PrestaShop\Module\KpyProductSync\Service;

use PrestaShop\Module\KpyProductSync\Command\SynchronizeAllProductsCommand;
use PrestaShop\Module\KpyProductSync\Command\SynchronizeSingleProductCommand;
use PrestaShop\Module\KpyProductSync\ConsoleCommand\ContextInitializerTrait;
use PrestaShop\Module\KpyProductSync\Query\QueryBus;
use PrestaShop\Module\KpyProductSync\ValueObject\AppProduct;
use PrestaShop\Module\KpyProductSync\ValueObject\ProductCode;

class ProductSynchronizer
{
    private QueryBus $queryBus;
    private SynchronizeSingleProductCommand $synchronizeSingleProductCommand;
    use ContextInitializerTrait;

    public function __construct()
    {
        $this->queryBus = new QueryBus();
        $this->synchronizeSingleProductCommand = new SynchronizeSingleProductCommand();
    }

    public function syncProduct(ProductCode $productCode): void
    {
        $productRaw = $this->queryBus->fetch('kpyproductsync.query.single_product_info', [
            'product_code' => $productCode,
        ]);

        $this->initializeContext();

        $this->synchronizeSingleProductCommand->execute($this->createAppProductFromResult($productRaw));
    }

    private function createAppProductFromResult(array $result): AppProduct
    {
        $productCode = ProductCode::from($result['id_product'], $result['id_product_attribute']);

        return new AppProduct(
            $productCode,
            $result['id_manufacturer'],
            \Product::getPriceStatic(
                $productCode->getProductId(),
                true,
                ($productCode->isCombinationProduct() ? $productCode->getProductAttributeId() : null),
                2) ?? 0.0,
            $result['weight'],
            $result['pack'] === 'si'
        );
    }

    public function syncProductsInBatch(bool $reset = true): int
    {
        $this->initializeContext();

        $products = $this->queryBus->fetch('kpyproductsync.query.all_product_info');

        $appProducts = [];

        foreach ($products as $product) {
            $appProduct = $this->createAppProductFromResult($product);
            $appProducts[] = $appProduct;

            if (!$reset) {
                $this->synchronizeSingleProductCommand->execute($appProduct);
            }
        }

        if ($reset) {
            $synchronizeAllProductsCommand = new SynchronizeAllProductsCommand();
            $synchronizeAllProductsCommand->execute($appProducts);
        }

        return count($appProducts);
    }
}