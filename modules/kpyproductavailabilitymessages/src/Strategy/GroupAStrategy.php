<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class GroupAStrategy implements DeliveryStrategyInterface
{
    public function isManufacturerSupported(int $manufacturer): bool
    {
        // RC, Dingo
        return in_array($manufacturer, [3, 77, 78, 75,]);
    }

    public function getPurchaseDayTimestamp(WorkingDaysManager $workingDaysManager): int
    {
        // si es antes de las 11 se puede hacer el pedido el mismo día, si no el siguiente laborable
        return $workingDaysManager->isWorkingDay(time()) && (int)date('H') < 11
            ? time()
            : $workingDaysManager->getNextWorkingDayTo(time());
    }

    public function getFulfillmentDays(): int
    {
        return 2;
    }
}