<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class ANCFreshStrategy extends MessageStrategy
{
    public function isManufacturerSupported(int $manufacturer): bool
    {
        return 203 === $manufacturer;
    }

    public function computeRange(WorkingDaysManager $workingDaysManager): void
    {
        $this->start = $workingDaysManager->isWorkingDay(time()) && (int)date('H') < 11
            ? time()
            : $workingDaysManager->getNextWorkingDayTo(time());

        // + 3 días en venir la mercancía + 1 día de envío
        $this->start = $workingDaysManager->addWorkingDaysToTimestamp($this->start, 4);
        $this->end = $workingDaysManager->getNextWorkingDayTo($this->start);
    }
}