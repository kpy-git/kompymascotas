<?php

namespace PrestaShop\Module\KpyAquaOrders\Config;

class AquaTaxes
{
    private int $iva1 = 4;
    private int $iva3 = 5;
    private int $iva2 = 6;

    private float $recargo1 = 0.5;
    private float $recargo2 = 5.2;
    private float $recargo3 = 1.4;

    private string $juegoIVA = '';

    public function getIva1(): int
    {
        return $this->iva1;
    }

    public function setIva1(int $iva1): self
    {
        $this->iva1 = $iva1;
        return $this;
    }

    public function getIva3(): int
    {
        return $this->iva3;
    }

    public function setIva3(int $iva3): self
    {
        $this->iva3 = $iva3;
        return $this;
    }

    public function getIva2(): int
    {
        return $this->iva2;
    }

    public function setIva2(int $iva2): self
    {
        $this->iva2 = $iva2;
        return $this;
    }

    public function getRecargo1(): float
    {
        return $this->recargo1;
    }

    public function setRecargo1(float $recargo1): self
    {
        $this->recargo1 = $recargo1;
        return $this;
    }

    public function getRecargo2(): float
    {
        return $this->recargo2;
    }

    public function setRecargo2(float $recargo2): self
    {
        $this->recargo2 = $recargo2;
        return $this;
    }

    public function getRecargo3(): float
    {
        return $this->recargo3;
    }

    public function setRecargo3(float $recargo3): self
    {
        $this->recargo3 = $recargo3;
        return $this;
    }

    public function getJuegoIVA(): string
    {
        return $this->juegoIVA;
    }

    public function setJuegoIVA(string $juegoIVA): self
    {
        $this->juegoIVA = $juegoIVA;
        return $this;
    }


}