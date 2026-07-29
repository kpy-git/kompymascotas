<?php

namespace PrestaShop\Module\KpyProductSync\ValueObject;

class ProductCode
{
    private string $sku;

    private function __construct(private readonly int $productId, private readonly int $productAttributeId)
    {
        $this->setSku($productId, $productAttributeId);
    }

    public static function fromSKU(string $sku): static
    {
        if (!str_contains($sku, '-')) {
            throw new \PrestaShopException('Código de producto incorrecto: ' . $sku);
        }

        [$id, $attr] = explode('-', $sku);

        return new self((int)$id, (int)$attr);
    }

    public static function from(int $productId, int $productAttributeId): static
    {
        return new self($productId, $productAttributeId);
    }

    private function setSku(int $productId, int $productAttributeId): void
    {
        $this->sku = $productId . '-' . $productAttributeId;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductAttributeId(): int
    {
        return $this->productAttributeId;
    }

    public function isCombinationProduct(): bool
    {
        return $this->productAttributeId !== 0;
    }

    public function getCodeForUrl(): string
    {
        return $this->isCombinationProduct() ? $this->sku : (string)$this->productId;
    }

    public function equals(self $productCode): bool
    {
        return $this->sku === $productCode->getSku();
    }

    public function __toString(): string
    {
        return $this->getSku();
    }
}