<?php

namespace PrestaShop\Module\KpyAquaOrders\Entity;

use Address;
use Customer;
use Db;
use PrestaShop\Module\KpyAquaOrders\Repository\AddressPickupAquaRepository;

class AddressPickupAqua extends AddressAqua
{
	private int $id_cart;

	private string $pudoid;

    private AddressPickupAquaRepository $pickupRepository;

	public function __construct(Address $address, Customer $customer, int $id_cart, int $id_order)
	{
        parent::__construct($address, $customer, $id_order);

		$this->id_cart = $id_cart;
		$this->id = 'PK' . $id_order;

		$this->fillAddressWithPickupData();
        $this->pickupRepository = new AddressPickupAquaRepository();
	}

	private function fillAddressWithPickupData(): void
	{
		$pickup = Db::getInstance()->getRow(
            "SELECT CONCAT(direccion, ' (', centre, ')') as direccion, codigo_postal, ciudad, pudoid 
                    from " . _DB_PREFIX_ . "kpy_seur_pickup 
                    where id_cart=" . $this->id_cart);

        $this->direccion         = mb_substr($pickup['direccion'], 0, 70);
        $this->ciudad            = mb_substr($pickup['ciudad'], 0, 50);
        $this->codigoPostal      = mb_substr($pickup['codigo_postal'], 0, 8);

        // Se guardará en el campo CUE_CODIGO. Será necesario en el momento de generar la expedición
	    $this->pudoid = $pickup['pudoid'];
    }

    public function export(): bool
    {
        return $this->addressRepository->export($this)
            && $this->pickupRepository->savePudoId($this->id, $this->pudoid);
    }
}