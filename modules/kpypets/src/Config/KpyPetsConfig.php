<?php

namespace PrestaShop\Module\KpyPets\Config;

class KpyPetsConfig 
{
	public const CART_RULE_PREFFIX = 'KPYPETS_CART_RULE_PREFFIX';

	public const CART_RULE_AMONT = 'KPYPETS_CART_RULE_AMOUNT';

    public const CART_RULE_NAME = 'KPYPETS_CART_RULE_NAME';

	public static function getDefaultConfiguration(): array 
	{
		return [
			self::CART_RULE_AMONT => 3.0,
			self::CART_RULE_PREFFIX => 'PET',
            self::CART_RULE_NAME => 'Vale descuento primera mascota',
		];
	}
}