<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class ANCFreshStrategy implements DeliveryStrategyInterface
{
    public function isManufacturerSupported(int $manufacturer): bool
    {
        return 203 === $manufacturer;
    }

    public function getPurchaseDayTimestamp(WorkingDaysManager $workingDaysManager): int
    {
        // pedido diario antes de las 11
        return (int)date('H') < 11 && $workingDaysManager->isWorkingDay(time())
            ? time()
            : $workingDaysManager->getNextWorkingDayTo(time());
    }

    public function getFulfillmentDays(): int
    {
        return 3;
    }
}