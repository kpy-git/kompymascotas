<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Exception\KpyMessageStrategyNotFound;

class MessageStrategyManager
{
    /** @var DeliveryStrategyInterface[] */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            new GroupAStrategy(),
            new MontillaStrategy(),
            new OwnatStrategy(),
            new ANCFreshStrategy(),
        ];
    }

    /**
     * @throws KpyMessageStrategyNotFound
     */
    public function getAvailableStrategyByManufacturer(int $manufacturer): DeliveryStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->isManufacturerSupported($manufacturer)) {
                return $strategy;
            }
        }

        throw new KpyMessageStrategyNotFound('Message strategy not found by manufacturer: ' . $manufacturer);
    }
}