<?php

namespace PrestaShop\Module\KpyAquaOrders\Entity;

use Address;
use Customer;
use Db;
use PrestaShop\Module\KpyAquaOrders\Repository\AddressDHLServicePointRepository;

class AddressDHLServicePoint extends AddressAqua
{

	private string $harmonisedId;

	private string $psfKey;

    private AddressDHLServicePointRepository $servicePointRepository;

	public function __construct(Address $address, Customer $customer, int $id_cart, int $id_order)
	{
        $this->servicePointRepository = new AddressDHLServicePointRepository();

		parent::__construct($address, $customer);

		$this->id = 'SP' . $id_order;
		$this->fillAddressWithServicePointData($id_cart);
	}

	private function fillAddressWithServicePointData(int $id_cart): void
	{
		$servicePointData = Db::getInstance()->getRow(
			"SELECT name, street, postcode, city, harmonisedId, psfKey
	            FROM " . _DB_PREFIX_ . "pym_dhl_service_point_cart
	            WHERE id_cart = {$id_cart}"
		);

		$this->direccion = mb_substr($servicePointData['street'], 0, 70);
		$this->ciudad = mb_substr($servicePointData['city'], 0, 50);
		$this->codigoPostal = $servicePointData['postcode'];
		$this->harmonisedId = $servicePointData['harmonisedId'];
		$this->psfKey = $servicePointData['psfKey'];
	}

	public function export(): bool
	{
		return $this->addressRepository->export($this)
            && $this->servicePointRepository->saveServicePointCodes($this->getId(), $this->harmonisedId, $this->psfKey);
	}
}