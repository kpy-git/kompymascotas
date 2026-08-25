<?php

namespace PrestaShop\Module\KpyDistrivetConnector\DTO;

readonly class DistrivetProductOrderDTO
{
    public function __construct(
        private string $distrivetId,
        private string $productName,
        private int $quantity,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'ItemNo' => $this->distrivetId,
            'ItemDescription' => mb_strtoupper(mb_substr($this->productName, 0, 80)),
            'Quantity' => $this->quantity,
        ];
    }
}