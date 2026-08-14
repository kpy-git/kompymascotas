<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Services;

class ProductAvailabilityMessageFormatter
{
    private array $weekDays = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'];
    private array $monthsName = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
        'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function convierteRangoTiempoADiasSemana(int $startTimestamp, int $finalTimestamp): string
    {
        $startMonthDay = date('j', $startTimestamp);
        $startWeekday = $this->weekDays[date('N', $startTimestamp) - 1];
        $finalMonthDay = date('j', $finalTimestamp);
        $finalWeekDay = $this->weekDays[date('N', $finalTimestamp) - 1];

        if (date('m', $startTimestamp) === date('m', $finalTimestamp)) {
            return sprintf('Recíbelo entre el %s %d y el %s %d de %s',
                $startWeekday,
                $startMonthDay,
                $finalWeekDay,
                $finalMonthDay,
                ucfirst($this->monthsName[date('m', $startTimestamp) - 1]));
        }

        $startMonthName = $this->monthsName[date('m', $startTimestamp) - 1];
        $endMonthName = $this->monthsName[date('m', $finalTimestamp) - 1];

        return sprintf('Recíbelo entre %s %d de %s y el %s %d de %s',
            $startWeekday,
            $startMonthDay,
            $startMonthName,
            $finalWeekDay,
            ucfirst($finalMonthDay),
            ucfirst($endMonthName)
        );
    }

    public function getWeekdayAndMonthInText(int $timestamp): string
    {
        return sprintf('%s %d de %s',
            $this->weekDays[date('N', $timestamp) - 1],
            date('j', $timestamp),
            ucfirst($this->monthsName[date('m', $timestamp) - 1])
        );
    }
}