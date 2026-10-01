<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

abstract class MessageStrategy
{
    protected int $start;

    protected int $end;

    abstract public function isManufacturerSupported(int $manufacturer): bool;

    abstract public function computeRange(WorkingDaysManager $workingDaysManager): void;

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }
}