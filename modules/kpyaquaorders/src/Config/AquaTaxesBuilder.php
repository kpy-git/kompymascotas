<?php

namespace PrestaShop\Module\KpyAquaOrders\Config;

class AquaTaxesBuilder
{
    public function buildDefaultTaxes(): AquaTaxes
    {
        return new AquaTaxes();
    }

    public function buildPortugalTaxes(): AquaTaxes
    {
        return (new AquaTaxes())
            ->setIva1(6)
            ->setIva2(23)
            ->setIva3(13)
            ->setRecargo1(0)
            ->setRecargo1(0)
            ->setRecargo1(0)
            ->setJuegoIVA('PT');
    }

    public function buildItalyTaxes(): AquaTaxes
    {
        return (new AquaTaxes())
            ->setIva1(5)
            ->setIva2(22)
            ->setIva3(10)
            ->setRecargo1(0)
            ->setRecargo1(0)
            ->setRecargo1(0)
            ->setJuegoIVA('IT');
    }
}