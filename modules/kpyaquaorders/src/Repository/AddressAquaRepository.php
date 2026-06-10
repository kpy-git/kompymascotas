<?php

namespace PrestaShop\Module\KpyAquaOrders\Repository;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Entity\AddressAqua;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

readonly class AddressAquaRepository
{
    protected DbMssql $aqua;

    public function __construct()
    {
        $this->aqua = DbMssql::getInstance();
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function export(AddressAqua $address): bool
    {
        try {
            if ($address->isMascoteros()) {
                return true;
            }

            if ($this->existsAddress($address->getId(), $address->getCliente())) {
                return $this->update($address);
            }

            return $this->save($address);

        } catch (\PDOException $e) {
            throw new KpyAquaSqlException(
                $e->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }

    private function update(AddressAqua $address): bool
    {
        $stmt = $this->aqua->prepare(
            "UPDATE DATAE03 SET DOMICILIO=?, LOCALIDAD=?, PROVINCIA=?, CPOSTAL=?, TELEFONO=?, COMERCIAL=?, 
                    NOMBRE=?, ATENCION=?, PAIS=?, EMAIL=?, PYM_NIF=?, COMUNIDAD=? 
                WHERE CODIGO=? AND DELEGACION=?");

        return $stmt->execute([
            $address->getDireccion(),
            $address->getCiudad(),
            $address->getProvincia(),
            $address->getCodigoPostal(),
            $address->getTelefono(),
            $address->getNombreCompleto(),
            $address->getNombreCompleto(),
            $address->getNombre(),
            $address->getPais(),
            $address->getEmail(),
            $address->getNif(),
            $address->getComunidadAutonoma(),
            $address->getCliente(),
            $address->getId(),
        ]);
    }

    public function existsAddress(string $addressId, string $customerId): bool
    {
        return (int)$this->aqua->getValue(
                "SELECT COUNT(*) FROM DATAE01 WITH(NOLOCK) WHERE DELEGACION='{$addressId}' AND CODIGO='{$customerId}'"
            ) > 0;
    }

    protected function save(AddressAqua $address): bool
    {
        $stmt = $this->aqua->prepare(
            "INSERT INTO DATAE03 (
                    CODIGO, DELEGACION, DOMICILIO, LOCALIDAD, PROVINCIA, COMUNIDAD,
                    CPOSTAL, CONTACTO, B_NOMBRE, B_DOMICILI, B_LOCALIDA, B_PROVINCI, B_CPOSTAL,
                    B_NUMERO, CUE_CODIGO, CUE_NUMERO, CLI_CODIGO, CUE_CLAVE, ZONA, SUBZONA,
                    RUTA, PUERTA, TELEFONO, FAX, AGENTE, DEPARTMENT, COMERCIAL,
                    CARRIER, CUE_IBAN, CUE_IBAN_INTER, NOMBRE,
                    IDCUSTOMER, IDADDRESS, ATENCION, PAIS, EMAIL, PYM_NIF
                )
                VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?
                )"
        );

        return $stmt->execute([
            $address->getCliente(),
            $address->getId(),
            $address->getDireccion(),
            $address->getCiudad(),
            $address->getProvincia(),
            $address->getComunidadAutonoma(),
            $address->getCodigoPostal(),
            '', '', '', '', '', '',
            '', '', '', '', '', '', '',
            '', '',
            $address->getTelefono(),
            '', '', '',
            $address->getNombreCompleto(),
            '', '', '',
            $address->getNombreCompleto(),
            $address->getCliente(),
            $address->getId(),
            $address->getNombre(),
            $address->getPais(),
            $address->getEmail(),
            $address->getNif(),
        ]);
    }
}