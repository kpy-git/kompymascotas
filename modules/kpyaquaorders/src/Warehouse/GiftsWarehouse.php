<?php

namespace PrestaShop\Module\KpyAquaOrders\Warehouse;

class GiftsWarehouse
{
    private const string FILE_PATH  = __DIR__ . '/regalos.json';

    private array $gifts;

    public function __construct()
    {
        $this->gifts = json_decode(file_get_contents(self::FILE_PATH), true);
    }

    public function getAll(): array
    {
        return $this->gifts;
    }
}