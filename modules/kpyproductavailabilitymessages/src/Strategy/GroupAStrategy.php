<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class GroupAStrategy extends MessageStrategy
{
    public function isManufacturerSupported(int $manufacturer): bool
    {
        // RC, Dingo
        return in_array($manufacturer, [3, 77, 78, 75,]);
    }

    public function computeRange(WorkingDaysManager $workingDaysManager): void
    {
        // si es antes de las 11 se puede hacer el pedido el mismo día, si no el siguiente laborable
        $this->start = $workingDaysManager->isWorkingDay(time()) && (int)date('H') < 11
            ? time()
            : $workingDaysManager->getNextWorkingDayTo(time());

        // + 2 días en venir la mercancía + 1 día de envío
        $this->start = $workingDaysManager->addWorkingDaysToTimestamp($this->start, 3);
        $this->end = $workingDaysManager->getNextWorkingDayTo($this->start);
    }
}