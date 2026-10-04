<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class OwnatStrategy implements DeliveryStrategyInterface
{
    public function isManufacturerSupported(int $manufacturer): bool
    {
        return 121 === $manufacturer;
    }

    public function getPurchaseDayTimestamp(WorkingDaysManager $workingDaysManager): int
    {
        // OWNAT se hace pedido martes, jueves y viernes
        return (int)date('H') < 12 && in_array((int)date('N'), [2, 4, 5]) && $workingDaysManager->isWorkingDay(time())
            ? time()
            : $workingDaysManager->getNextWorkingDayTo(time());
    }

    public function getFulfillmentDays(): int
    {
        return 1;
    }
}