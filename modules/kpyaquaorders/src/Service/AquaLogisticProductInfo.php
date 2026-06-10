<?php

namespace PrestaShop\Module\KpyAquaOrders\Service;

class AquaLogisticProductInfo
{
    private float $weight;

    private float $volume;

    private string $description;

    /**
     * @param float $weight
     * @param float $volume
     * @param string $description
     */
    public function __construct(float $weight, float $volume, string $description)
    {
        $this->weight = $weight;
        $this->volume = $volume;
        $this->description = $description;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getVolume(): float
    {
        return $this->volume;
    }


    public function getDescription(): string
    {
        return $this->description;
    }


}