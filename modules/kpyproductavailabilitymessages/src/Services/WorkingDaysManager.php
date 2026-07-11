<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Services;

use PrestaShop\Module\KpyProductAvailabilityMessages\Repository\NonWorkingDaysRepository;

class WorkingDaysManager
{
    private array $nonWorkingDays;

    public function __construct()
    {
        $this->nonWorkingDays = NonWorkingDaysRepository::getNonWorkingDaysFrom(time());
    }

    public function isWorkingDay(?int $timestamp = null): bool
    {
        return date('N', $timestamp) < 6 && !in_array(date('d-m-Y', $timestamp), $this->nonWorkingDays);
    }

    public function getNextWorkingDayTo(int $timestamp): int
    {
        return $this->addWorkingDaysToTimestamp($timestamp, 1);
    }

    public function addWorkingDaysToTimestamp(int $timestamp, int $days): int
    {
        if ($days < 1) {
            return $timestamp;
        }

        while($days > 0) {
            $timestamp = strtotime('+1 day', $timestamp);

            if ($this->isWorkingDay($timestamp)) {
                $days--;
            }
        }

        return $timestamp;
    }
}