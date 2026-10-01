<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Strategy;

use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;

class MontillaStrategy extends MessageStrategy
{

    public function isManufacturerSupported(int $manufacturer): bool
    {
        // Advance, Libra, Natures Variety
        return in_array($manufacturer, [4, 27, 199], true);
    }

    public function computeRange(WorkingDaysManager $workingDaysManager): void
    {
        // si es antes de las 11 de un lunes o jueves laborable
        if ((int)date('H') < 11
            && $workingDaysManager->isWorkingDay(time())
            && ((int)date('N') === 1 || (int)date('N') === 4)
        ) {
            $this->start = time();

        } else {
            // el siguiente lunes o jueves laborable
            $this->start = (int)date('N') < 4 ? strtotime('next Thursday') : strtotime('next Monday');
            while (!$workingDaysManager->isWorkingDay($this->start)) {
                $this->start = (int)date('N', $this->start) < 4
                    ? strtotime('next Thursday', $this->start)
                    : strtotime('next Monday', $this->start);
            }
        }

        // + 2 días en venir la mercancía + 1 día de envío
        $this->start = $workingDaysManager->addWorkingDaysToTimestamp($this->start, 3);
        $this->end = $workingDaysManager->getNextWorkingDayTo($this->start);
    }
}