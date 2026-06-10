<?php

namespace PrestaShop\Module\KpyAquaOrders\Config;

enum AquaVendor: string
{
    case ESP = '1';
    case WECPT = '2';
    case WECIT = '10';

    case CLICK_CANARIAS = '4';

    case MASCOTEROS = '3';
    case CARREFOUR = '5';
    case EBAY = '6';
    case AMAZON = '7';
    case AMAZON_PRIME = '12';
    case MANO_MANO = '13';
    case BULEVIP = '14';
    case ALIEXPRESS = '15';

    public function getEmail(): string
    {
        return match ($this) {
            self::AMAZON, self::AMAZON_PRIME => 'pedidosamazon@piensoymascotas.com',
            self::MANO_MANO => 'pedidosmanomano@piensoymascotas.com',
            self::BULEVIP => 'pedidosbulevip@piensoymascotas.com',
            self::ALIEXPRESS => 'pedidosaliexpress@piensoymascotas.com',
            default => '',
        };
    }

    public function getPrefix(): string
    {
        return match ($this) {
            self::AMAZON, self::AMAZON_PRIME => 'AMA',
            self::MANO_MANO => 'MAN',
            self::BULEVIP => 'BV',
            self::ALIEXPRESS => 'ALI',
            default => '',
        };
    }

    public function getCustomerId(): int
    {
        return match ($this) {
            self::MASCOTEROS => 33023,
            self::EBAY => 40645,
            self::CARREFOUR => 36459,
            self::AMAZON, self::AMAZON_PRIME => 40373,
            self::MANO_MANO => 132578,
            self::BULEVIP => 179031,
            self::ALIEXPRESS => 188544,
            default => 0,
        };
    }

    public static function tryFromCustomerId(int $customerId): ?self
    {
        return array_find(self::cases(), static fn($vendor) => $vendor->getCustomerId() === $customerId);

    }

    public function hasFacturaSimplificada(): bool
    {
        return in_array($this, [self::AMAZON, self::AMAZON_PRIME, self::MANO_MANO, self::BULEVIP, self::ALIEXPRESS]);
    }

    public function getShippingTaxRate(): int
    {
        return match ($this) {
            self::WECPT => 23,
            self::WECIT => 22,
            default => 21,
        };
    }

    public function getAquaTaxes(): AquaTaxes
    {
        $builder = new AquaTaxesBuilder();
        return match ($this) {
            self::WECPT => $builder->buildPortugalTaxes(),
            self::WECIT => $builder->buildItalyTaxes(),
            default => $builder->buildDefaultTaxes(),
        };
    }

    public static function tryFromShop(int $shopId): self
    {
        return match ($shopId) {
            2 => self::WECPT,
            3 => self::WECIT,
            default => self::ESP,
        };
    }
}
