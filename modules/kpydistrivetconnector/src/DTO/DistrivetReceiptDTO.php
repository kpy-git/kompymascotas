<?php

namespace PrestaShop\Module\KpyDistrivetConnector\DTO;

readonly class DistrivetReceiptDTO
{
    public function __construct(private \Address $deliveryAddress, private string $email)
    {
    }

    public function toArray(): array
    {
        $address = mb_strtoupper($this->deliveryAddress->address1 . " " . $this->deliveryAddress->address2);

        return [
            "ShipToAddress" => mb_substr($address, 0, 50),
            "ShipToAddress2" => mb_substr($address, 50, 50),
            "ShipToCity" => mb_strtoupper($this->deliveryAddress->city),
            "ShipToCountry" => "ES",
            "ShipToCounty" => mb_strtoupper(\State::getNameById($this->deliveryAddress->id_state)),
            "ShipToEmail" => $this->email,
            "ShipToName" => mb_strtoupper(mb_substr($this->deliveryAddress->firstname . " " . $this->deliveryAddress->lastname, 0, 50)),
            "ShipToPhoneNo_" => substr(preg_replace('/[\D]/', '', $this->deliveryAddress->phone ?: $this->deliveryAddress->phone_mobile), -9, 9),
            "ShipToPostCode" => $this->deliveryAddress->postcode,
        ];

        $extra = [
            "ShipToCode" => "",
            "ShippingAgentCode" => "",
            "ShippingServiceCode" => "",
        ];
    }
}