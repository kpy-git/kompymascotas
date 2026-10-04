<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

interface DeliveryStrategyInterface
{
    public function isManufacturerSupported(int $manufacturer): bool;

    public function getPurchaseDayTimestamp(WorkingDaysManager $workingDaysManager): int;

    public function getFulfillmentDays(): int;
}