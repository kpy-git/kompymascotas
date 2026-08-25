<?php

namespace PrestaShop\Module\KpyDistrivetConnector\DTO;

readonly class DistrivetStockProductDTO
{
    public function __construct(
        private int                $productId,
        private int                $productAttributeId,
        private string             $distrivetId,
        private string             $name,
        private int                $stock,
        private \DateTimeImmutable $updatedAt,
        private bool               $isPack = false
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

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isPack(): bool
    {
        return $this->isPack;
    }

    public function getSku(): string
    {
        return $this->productId . '-' . $this->productAttributeId;
    }

    public function getDistrivetName(): string
    {
        return $this->name;
    }
}