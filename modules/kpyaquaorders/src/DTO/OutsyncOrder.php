<?php

namespace PrestaShop\Module\KpyAquaOrders\DTO;

use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class OutsyncOrder implements \JsonSerializable
{
    private int $id;

    private int $timestampOutsync;

    private int $orderStatus;

    public function __construct(int $id, int $orderStatus, ?int $dateOutsync = null)
    {
        $this->id = $id;
        $this->timestampOutsync = $dateOutsync ?? time();
        $this->orderStatus = $orderStatus;
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTimestampOutsync(): string
    {
        return $this->timestampOutsync;
    }

    public function setTimestampOutsync(int $timestampOutsync): self
    {
        $this->timestampOutsync = $timestampOutsync;
        return $this;
    }

    public function getOrderStatus(): int
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(int $orderStatus): self
    {
        $this->orderStatus = $orderStatus;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function serialize(): string
    {
        return sprintf("%s,%s,%s", date(DATE_ATOM, $this->timestampOutsync), $this->id, $this->orderStatus);
    }

    /**
     * @throws KpyAquaOrderException
     */
    public static function deserialize(string $data): self
    {
        $fields = explode(',', trim($data));

        if (count($fields) !== 3) {
            throw new KpyAquaOrderException('No se puede deserializar el pedido, número de campos incorrectos.');
        }

        [$date, $id, $orderStatus] = $fields;

        return new self((int)$id, (int)$orderStatus, strtotime($date));
    }
}