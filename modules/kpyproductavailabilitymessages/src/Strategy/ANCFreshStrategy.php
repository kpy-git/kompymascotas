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
        // pedido los lunes antes de las 11
        $start = (int)date('N') === 1 && (int)date('H') < 11
            ? time()
            : strtotime('next Monday');

        while (!$workingDaysManager->isWorkingDay($start)) {
            $start = strtotime('next Monday', $start);
        }

        return $start;
    }

    public function getFulfillmentDays(): int
    {
        return 1;
    }
}