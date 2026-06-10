<?php

namespace PrestaShop\Module\KpyAquaOrders\Service;

use PrestaShop\Module\KpyAquaOrders\Config\AquaVendor;

class Tools
{
    public static function getPrefixByCustomerId(int $customerId): string
    {
        foreach (AquaVendor::cases() as $vendor) {
            if ($vendor->getCustomerId() === $customerId) {
                return $vendor->getPrefix();
            }
        }

        return '';
    }

    public static function isCanarias(int $countryId): bool
    {
        return $countryId === 242;
    }
}