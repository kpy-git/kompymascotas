<?php

namespace PrestaShop\Module\KpyAquaOrders\Entity;

use Address;
use Country;
use Customer;
use PrestaShop\Module\KpyAquaOrders\Config\AquaConfig;
use PrestaShop\Module\KpyAquaOrders\Repository\AddressAquaRepository;
use PrestaShop\Module\KpyAquaOrders\Service\Tools;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use State;

class AddressAqua
{
    public string $id;

    protected string $cliente;

    protected string $nombre;

    protected string $nombre_completo;

    protected string $nif;

    protected string $direccion;

    protected string $ciudad;

    protected string $codigoPostal;

    protected string $provincia;

    protected string $pais;

    protected string $telefono;

    protected string $email;

    protected string $comunidadAutonoma;

    protected AddressAquaRepository $addressRepository;

    public function __construct(Address $address, Customer $customer, string $id_order = '')
    {
        $this->id = (string)$address->id;

        $this->cliente = Tools::getPrefixByCustomerId($customer->id) . $id_order;
        if (Tools::isCanarias($address->id_country)) {
            $this->cliente = AquaConfig::CANARIAS_CUSTOMER_CODE;
        }

        $this->nombre_completo = mb_substr($address->firstname . " " . $address->lastname, 0, 80);
        $this->nombre = mb_substr($address->firstname, 0, 50);
        $this->nif = str_replace('.', '', $address->dni);
        $this->direccion = mb_substr($address->address1 . " " . $address->address2, 0, 70);
        $this->ciudad = mb_substr($address->city, 0, 50);
        $this->codigoPostal = mb_substr($address->postcode, 0, 8);
        $this->provincia = mb_strtoupper(mb_substr(State::getNameById($address->id_state), 0, 50));
        $this->pais = mb_strtoupper(Country::getNameById(3, $address->id_country));
        $this->telefono = empty(trim($address->phone))
            ? substr(trim($address->phone_mobile), 0, 15)
            : substr(trim($address->phone), 0, 15);
        $this->email = $customer->email;

        $this->comunidadAutonoma = (new AquaStateWarehouse())->findCommunityByState($address->id_state);

        $this->addressRepository = new AddressAquaRepository();
    }

    public function isMascoteros(): bool
    {
        return $this->cliente === '33023';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getNombreCompleto(): string
    {
        return $this->nombre_completo;
    }

    public function getNif(): string
    {
        return $this->nif;
    }

    public function getDireccion(): string
    {
        return $this->direccion;
    }

    public function getCiudad(): string
    {
        return $this->ciudad;
    }

    public function getCodigoPostal(): string
    {
        return $this->codigoPostal;
    }

    public function getProvincia(): string
    {
        return $this->provincia;
    }

    public function getPais(): string
    {
        return $this->pais;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getComunidadAutonoma(): string
    {
        return $this->comunidadAutonoma;
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function export(): bool
    {
        return $this->addressRepository->export($this);
    }
}
