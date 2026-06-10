<?php

namespace PrestaShop\Module\KpyAquaOrders\Entity;

use Address;
use Country;
use Customer;
use PrestaShop\Module\KpyAquaOrders\Repository\CustomerAquaRepository;
use PrestaShop\Module\KpyAquaOrders\Service\Tools;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use State;

class CustomerAqua
{
    protected string $id;

    private string $nombre;

    private string $nombre_completo;

    private string $nif;

    private string $direccion;

    private string $ciudad;

    private string $codigoPostal;

    private string $provincia;

    private string $pais;

    private string $telefono1;

    private string $telefono2;

    protected string $email;

    private int $ue;

    private bool $canarias;

    private bool $facturaSimplificada;

    private string $juegoIVA;

    private bool $vip;

    private CustomerAquaRepository $repository;

    public function __construct(Customer $customer, Address $address, string $id_order)
    {
        $this->id = (string) $customer->id;
        $this->repository = new CustomerAquaRepository();

        // los clientes de pedidos de los marketplaces tienen como id de cliente: prefijo + pedido
        if (!empty($prefix = Tools::getPrefixByCustomerId($customer->id))) {
            $this->id = $prefix . $id_order;
        }

        $this->facturaSimplificada = in_array($customer->id, [40373, 132578, 179031, 188544, 215598]);

        $this->nombre_completo = mb_substr($address->firstname . " " . $address->lastname, 0, 80);
        $this->nombre = mb_substr($address->firstname, 0, 50);
        $this->nif = $customer->id !== 133368 ? mb_substr($address->dni, 0, 15) : '';
        $this->direccion = mb_substr($address->address1 . " " . $address->address2, 0, 70);
        $this->ciudad = mb_substr($address->city, 0, 50);
        $this->codigoPostal = mb_substr($address->postcode, 0, 8);
        $this->provincia = mb_substr(State::getNameById($address->id_state), 0, 50);
        $this->pais = mb_strtoupper(Country::getNameById(3, $address->id_country));
        $this->telefono1 = mb_substr($address->phone, 0, 15);
        $this->telefono2 = mb_substr($address->phone_mobile, 0, 15);
        $this->email = $customer->email;
        $this->canarias = Tools::isCanarias($address->id_country);
        $this->ue = 0; // !in_array($customer->id_shop, [1, 245]) ? 1 : 0;
        $this->juegoIVA = $this->getJuegoIvaByShop($customer->id_shop);
        $this->vip = in_array(27, Customer::getGroupsStatic($customer->id)) ? 1 : 0;
    }

    private function getJuegoIvaByShop(int $shop): string
    {
        return match($shop) {
            2 => 'PT',
            3 => 'IT',
            default => '',
        };
    }

    public function getId(): string
    {
        return $this->id;
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

    public function getTelefono1(): string
    {
        return $this->telefono1;
    }

    public function getTelefono2(): string
    {
        return $this->telefono2;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUe(): int
    {
        return $this->ue;
    }

    public function isCanarias(): bool
    {
        return $this->canarias;
    }

    public function isFacturaSimplificada(): bool
    {
        return $this->facturaSimplificada;
    }

    public function getJuegoIVA(): string
    {
        return $this->juegoIVA;
    }

    public function isVip(): bool
    {
        return $this->vip;
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function export(): bool
    {
        return $this->repository->export($this);
    }
}
