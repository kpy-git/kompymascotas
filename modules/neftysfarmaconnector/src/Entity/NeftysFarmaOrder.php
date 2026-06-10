<?php

declare(strict_types=1);

namespace PrestaShop\Module\NeftysFarmaConnector\Entity;


use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\DTO\NeftysProduct;

class NeftysFarmaOrder
{
	private Receiver $receiver;

	private int $createTimestamp;

	private int $idOrder;

	private array $products;

    private bool $isCRM;

    private float $totalPaid;

	public function __construct(int $id_order)
	{
		$this->idOrder = $id_order;
		$this->createTimestamp = time();
		$this->products = [];
        $this->isCRM = false;
        $this->totalPaid = 0;
	}

	public function getIdOrder(): int
	{
		return $this->idOrder;
	}

	public function addProduct(NeftysProduct $product): void
	{
		$this->products[$product->getEan()] = isset($this->products[$product->getEan()])
            ? $this->products[$product->getEan()] + $product->getQuantity()
            : $product->getQuantity();
	}

	public function getProductsQuantityByEan(): array
	{
		return $this->products;
	}

	public function setReceiver($receiver): void
	{
		$this->receiver = $receiver;
	}

	public function getReceiver(): Receiver
	{
		return $this->receiver;
	}

	public function getCreateTimestamp(): int
	{
		return $this->createTimestamp;
	}

    public function isCRM(): bool
    {
        return $this->isCRM;
    }

    public function setIsCRM(bool $isCRM): self
    {
        $this->isCRM = $isCRM;
        return $this;
    }

    public function getTotalPaid(): float
    {
        return $this->totalPaid;
    }

    public function setTotalPaid(float $totalPaid): self
    {
        $this->totalPaid = $totalPaid;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'NUMEROPEDIDO' => $this->getIdOrder(),
            'FECHAPEDIDO' => date('d/m/Y', $this->getCreateTimestamp()),
            'CRM' => $this->isCRM() ? 'SI' : 'NO',
            'TOTAL' => $this->isCRM() ? $this->getTotalPaid() : 0
        ];
    }

    public static function isNeftysFarmaOrder(array $products): bool
    {
        /** @var NeftysProduct $product */
        foreach ($products as $product) {
            $sql = "SELECT COUNT(*) 
                FROM " . _DB_PREFIX_ . NeftysFarmaConfig::NEFTYS_FARMA_STOCK_TABLE . " 
                WHERE id_product={$product->getProductId()} 
                    and id_product_attribute={$product->getProductAttributeId()}";

            if ((int)\Db::getInstance()->getValue($sql) === 0) {
                return false;
            }
        }

        return true;
    }
}