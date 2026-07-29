<?php

namespace PrestaShop\Module\KpyProductSync\ValueObject;

readonly class AppProduct
{
    public function __construct(
        private ProductCode $productCode,
        private int $brand,
        private float $salesPrice,
        private float $weight,
        private bool $isPack = false,
        private bool $isJirafa= false
    )
    {
    }

    public function getProductCode(): ProductCode
    {
        return $this->productCode;
    }

    public function getBrand(): int
    {
        return $this->brand;
    }

    public function getSalesPrice(): float
    {
        return $this->salesPrice;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function isPack(): bool
    {
        return $this->isPack;
    }

    public function isJirafa(): bool
    {
        return $this->isJirafa;
    }


}