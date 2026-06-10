<?php

namespace PrestaShop\Module\KpyAquaOrders\Config;

enum AquaCarrier: int
{
    case CARRIER_SACOS_ROTOS = 169;

    case SEUR = 139;
    case SEUR_INT = 187;
    case SEUR_PICKUP = 183;

    case MRW = 6;

    case DHL = 188;
    case DHL_SERVICE_POINT = 270;

    case GLS = 5;

    public static function fromPS(int $carrierId): self
    {
        $id_reference = (int)\Db::getInstance()->getValue(
            "SELECT id_reference FROM " . _DB_PREFIX_ . "carrier WHERE id_carrier={$carrierId}");

        return match ($id_reference) {
            238, 209 => self::SEUR, // SEUR PT, SEUR AMAZON PRIME
            221 => self::SEUR_INT, // SEUR IT
            259 => self::SEUR_PICKUP, // SEUR PICKUP
            256 => self::MRW, // MRW PT
            255, 262 => self::DHL, // DHL PT, DHL IT
            271, 272 => self::DHL_SERVICE_POINT, // DHL SERVICE POINT
            251, 5 => self::GLS, // GLS ES
            default => self::from($id_reference),
        };
    }
}
