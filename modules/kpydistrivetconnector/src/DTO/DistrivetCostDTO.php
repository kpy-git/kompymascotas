<?php

namespace PrestaShop\Module\KpyDistrivetConnector\DTO;

readonly class DistrivetCostDTO
{
    public function __construct(
        private int $productId,
        private int $productAttributeId,
        private string $distrivetId,
        private float $cost
    )
    {
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductAttributeId(): int
    {
        return $this->productAttributeId;
    }

    public function getDistrivetId(): string
    {
        return $this->distrivetId;
    }

    public function getCost(): float
    {
        return $this->cost;
    }
}