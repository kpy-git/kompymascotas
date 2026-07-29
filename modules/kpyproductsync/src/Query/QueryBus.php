<?php

namespace PrestaShop\Module\KpyProductSync\Query;

class QueryBus
{
    /** @var QueryInterface[] */
    private array $queries;

    public function __construct()
    {
        $this->queries = [
            'kpyproductsync.query.single_product_info' => new SingleProductInfoQuery(),
            'kpyproductsync.query.all_product_info' => new AllProductsInfoQuery(),
        ];
    }

    /**
     * @throws \PrestaShopException
     */
    public function fetch(string $query, array $params = []): array
    {
        if (!isset($this->queries[$query])) {
            throw new \PrestaShopException('Unknown query: ' . $query);
        }

        return $this->queries[$query]->fetch($params);
    }
}