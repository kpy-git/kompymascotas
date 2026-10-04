<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class MontillaStrategy implements DeliveryStrategyInterface
{

    public function isManufacturerSupported(int $manufacturer): bool
    {
        // Advance, Libra, Natures Variety
        return in_array($manufacturer, [4, 27, 199], true);
    }

    public function getPurchaseDayTimestamp(WorkingDaysManager $workingDaysManager): int
    {
        $start = time();

        // si es antes de las 11 de un lunes o jueves laborable
        if ((int)date('H') < 11
            && $workingDaysManager->isWorkingDay($start)
            && ((int)date('N') === 1 || (int)date('N') === 4)
        ) {
            return $start;
        }

        do {
            // el siguiente lunes o jueves laborable
            $start = (int)date('N', $start) < 4
                ? strtotime('next Thursday', $start)
                : strtotime('next Monday', $start);
        } while (!$workingDaysManager->isWorkingDay($start));

        return $start;
    }

    public function getFulfillmentDays(): int
    {
        return 2;
    }
}