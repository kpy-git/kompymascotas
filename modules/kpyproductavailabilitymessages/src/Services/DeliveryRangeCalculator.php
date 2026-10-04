<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Services;

use PrestaShop\Module\KpyProductAvailabilityMessages\Strategy\DeliveryStrategyInterface;

class DeliveryRangeCalculator
{
    private int $firstDeliveryDay;

    private int $endDeliveryDay;

    private const int SHIPPING_DAYS = 1;

    public function __construct(
        WorkingDaysManager        $workingDaysManager,
        DeliveryStrategyInterface $deliveryStrategy,
    )
    {
        $this->firstDeliveryDay = $workingDaysManager->addWorkingDaysToTimestamp(
            $deliveryStrategy->getPurchaseDayTimestamp($workingDaysManager),
            $deliveryStrategy->getFulfillmentDays() + self::SHIPPING_DAYS
        );

        $this->endDeliveryDay = $workingDaysManager->getNextWorkingDayTo($this->firstDeliveryDay);
    }

    public function getFirstDeliveryDay(): int
    {
        return $this->firstDeliveryDay;
    }

    public function getEndDeliveryDay(): int
    {
        return $this->endDeliveryDay;
    }
}