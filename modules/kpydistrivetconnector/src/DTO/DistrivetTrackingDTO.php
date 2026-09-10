<?php

namespace PrestaShop\Module\KpyDistrivetConnector\DTO;

class DistrivetTrackingDTO
{
    public function __construct(private string $trackingNumber, private string $shipmentId)
    {
    }

    public function getShipmentId(): string
    {
        return $this->shipmentId;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

}