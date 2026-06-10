<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyOrderMaker\OrderMaker;

use Context;
use PrestaShop\Module\KpyOrderMaker\Exception\KpyOrderMakerException;
use PrestaShop\Module\KpyOrderMaker\Service\TaxRateFinder;

class KpyOrderMakerProduct
{
    private int $id_product;

    private int $id_product_attribute;

    private int $quantity;

    private float $unit_price_tax_incl;

    private float $unit_price_tax_excl;

    private float $total_price_tax_incl;

    private float $total_price_tax_excl;

    private float $taxRate;

    public function __construct($sku, $quantity, $totalPriceTaxIncl, $useTaxes = true)
    {
        if (!str_contains($sku, '-')) {
            throw new KpyOrderMakerException("SKU erróneo");
        }

        if ($quantity < 1) {
            throw new KpyOrderMakerException('La cantidad debe ser mayor que 1');
        }

        [$this->id_product, $this->id_product_attribute] = array_map(static fn (string $x) => (int)$x , explode('-', $sku));

        $this->quantity = $quantity;

        $this->total_price_tax_incl = $totalPriceTaxIncl;

        // TODO - si en algún momento se envía a más países habrá que sacar la relación de países y tiendas
        $taxRateFinder = new TaxRateFinder(6, Context::getContext());

        $this->taxRate = $useTaxes ? $taxRateFinder->getProductTaxRate($this->id_product) : 1;

        $this->updateTotals();
    }

    private function updateTotals(): void
    {
        $this->total_price_tax_excl = round($this->total_price_tax_incl / $this->taxRate, 2);

        $this->unit_price_tax_incl = round($this->total_price_tax_incl / $this->quantity, 6);
        $this->unit_price_tax_excl = round($this->unit_price_tax_incl / $this->taxRate, 6);
    }

    public function getIdProduct(): int
    {
        return $this->id_product;
    }

    public function getIdProductAttribute(): ?int
    {
        return $this->id_product_attribute ?: null;
    }

    public function setQuantity($quantity): void
    {
        if ($quantity < 1) {
            throw new KpyOrderMakerException('La cantidad debe ser mayor que 1');
        }

        $this->quantity = $quantity;
        $this->updateTotals();
    }

    public function setTotalPriceTaxIncl($totalPriceTaxIncl): void
    {
        $this->total_price_tax_incl = $totalPriceTaxIncl;
        $this->updateTotals();
    }

    public function getTotalPriceTaxIncl(): float
    {
        return $this->total_price_tax_incl;
    }

    public function getTotalPriceTaxExcl(): float
    {
        return $this->total_price_tax_excl;
    }

    public function getUnitPriceTaxIncl(): float
    {
        return $this->unit_price_tax_incl;
    }

    public function getUnitPriceTaxExcl(): float
    {
        return $this->unit_price_tax_excl;
    }
}