<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Repository;

class NonWorkingDaysRepository
{
    private static ?NonWorkingDaysRepository $instance = null;

    private const NON_WORKING_DAYS_FILE = __DIR__ . '/festivos.json';

    private array $nonWorkingDaysByYear = [];

    private array $nonWorkingDays = [];

    public static function getNonWorkingDays(): array
    {
        self::createInstanceIfNeeded();

        return self::$instance->nonWorkingDays;
    }

    private static function createInstanceIfNeeded(): void
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
    }

    public static function getNonWorkingDaysByYear(int $year): array
    {
        self::createInstanceIfNeeded();

        return self::$instance->nonWorkingDaysByYear[$year] ?? [];
    }

    public static function getNonWorkingDaysFrom(int $timestamp): array
    {
        self::createInstanceIfNeeded();

        return array_filter(self::$instance->nonWorkingDays, static fn(string $day) => strtotime($day) >= $timestamp);
    }

    private function __construct()
    {
        if (is_readable(self::NON_WORKING_DAYS_FILE)) {
            $rawData = json_decode(file_get_contents(self::NON_WORKING_DAYS_FILE), true);

            foreach ($rawData as $data) {
                $holidays = explode(',', $data['holidays']);

                $this->nonWorkingDaysByYear[$data['year']] = array_map(static fn(string $day) => $data['year']. '-' . $day, $holidays);

                foreach ($holidays as $holiday) {
                    $this->nonWorkingDays[] = $data['year'] . '-' . $holiday;
                }
            }
        }
    }

}