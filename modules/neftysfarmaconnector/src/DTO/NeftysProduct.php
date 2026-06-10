<?php

namespace PrestaShop\Module\NeftysFarmaConnector\DTO;

class NeftysProduct
{
    private int $productId;

    private int $productAttributeId;

    private string $sku;

    private string $ean;

    private int $quantity;

    public function __construct(string $sku)
    {
        $this->setSku($sku);

        $this->ean = '';
        $this->quantity = 0;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductAttributeId(): int
    {
        return $this->productAttributeId;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;
        [$this->productId, $this->productAttributeId] = explode('-', $sku);
        return $this;
    }

    public function getEan(): string
    {
        return $this->ean;
    }

    public function setEan(string $ean): self
    {
        $this->ean = $ean;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

}