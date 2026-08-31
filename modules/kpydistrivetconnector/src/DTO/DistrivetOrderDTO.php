<?php

namespace PrestaShop\Module\KpyDistrivetConnector\DTO;

class DistrivetOrderDTO implements \JsonSerializable
{
    private string $status = "Closed";

    private array $products = [];

    private string $notes = "";

    private float $crm = .0;

    public function __construct(
        private readonly int                 $orderId,
        private readonly DistrivetReceiptDTO $receipt
    )
    {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setCRM(float $total): void
    {
        $this->crm = $total;
    }

    public function isCRM(): bool
    {
        return $this->crm > 0;
    }

    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    public function addProduct(DistrivetProductOrderDTO $product): void
    {
        $this->products[] = $product;
    }

    public function jsonSerialize(): array
    {
        $data = [
            "Status" => $this->status,
            "YourReference" => (string)$this->orderId,
            ...$this->receipt->toArray(),
            'Items' => array_map(static fn (DistrivetProductOrderDTO $product): array => $product->toArray(), $this->products),
            "ShippingAgentCode" => \Configuration::get('KPY_DISTRIVET_AGENT_CODE') ?: '',
            "ShippingServiceCode" => \Configuration::get('KPY_DISTRIVET_SERVICE_CODE') ?: '',
        ];

        if (!empty($this->notes)) {
            $data["LabelText"] = mb_strtoupper(mb_substr($this->notes, 0, 30));
        }

        if ($this->isCRM()) {
            $data['ReimbursementAmount'] = $this->crm;
        }

        return $data;
    }
}