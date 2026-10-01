<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class OwnatStrategy extends MessageStrategy
{
    public function isManufacturerSupported(int $manufacturer): bool
    {
        return 121 === $manufacturer;
    }

    public function computeRange(WorkingDaysManager $workingDaysManager): void
    {
        // OWNAT se hace pedido martes, jueves y viernes
        $this->start = (int)date('H') < 12 && in_array((int)date('N'), [2, 4, 5]) && $workingDaysManager->isWorkingDay(time())
            ? time()
            : $workingDaysManager->getNextWorkingDayTo(time());

        // entregan en 1 día + 1 dia de envío
        $this->start = $workingDaysManager->addWorkingDaysToTimestamp($this->start, 2);
        $this->end = $workingDaysManager->getNextWorkingDayTo($this->start);
    }
}