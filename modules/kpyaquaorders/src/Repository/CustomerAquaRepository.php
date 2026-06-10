<?php

namespace PrestaShop\Module\KpyAquaOrders\Repository;

use PrestaShop\Module\KpyAquaOrders\Config\AquaVendor;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Entity\CustomerAqua;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

readonly class CustomerAquaRepository
{
    protected DbMssql $aqua;

    public function __construct()
    {
        $this->aqua = DbMssql::getInstance();
    }

    public function existsCustomer(string $customerId): bool
    {
        return (int)$this->aqua->getValue(
                "SELECT CASE 
                        WHEN EXISTS(SELECT 1 FROM DATEN03 WITH(NOLOCK) WHERE NIF='{$customerId}') THEN 1 
                        ELSE 0 END 
                    AS EXISTS_CUSTOMER"
            ) > 0;
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function export(CustomerAqua $customer): bool
    {
        try {
            if ((int)$customer->getId() === AquaVendor::MASCOTEROS->getCustomerId()) {
                return true;
            }

            if ($this->existsCustomer($customer->getId())) {
                return $this->update($customer);
            }

            return $this->save($customer);

        } catch (\PDOException $e) {
            throw new KpyAquaSqlException(
                $e->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }

    private function update(CustomerAqua $customer): bool
    {
        $stmt = $this->aqua->prepare("UPDATE DATEN03 SET NOMBRE=?, COMERCIAL=?, ATENCION=?, CIF=? WHERE NIF=?");

        return $stmt->execute([
            $customer->getNombreCompleto(),
            $customer->getNombreCompleto(),
            $customer->getNombre(),
            $customer->getNif(),
            $customer->getId(),
        ]);
    }

    private function save(CustomerAqua $customer): bool
    {
        $stmt = $this->aqua->prepare(
            "INSERT INTO DATEN03 (
                NIF, EXENTO_IVA, FECHA, MERCADO,
                COMERCIAL, NOMBRE, NUMERO, CIF, DIRECCION, LOCALIDAD, PROVINCIA,
                CPOSTAL, TELEFONO1, TELEFONO2, FAX,
                CATEGORIA, OBSERVACIO, CONRECARGO, LUGARPAGO, TRANSPORTE, ENTFPAGO, AGENTE,
                DIAPAGO1, DIAPAGO2, DIAPAGO3, RIESGO, COD_EAN13, DTO_TT,
                TEXTO, EXCLUIRAUT, MONEDA, FCOSTE, PAIS, DESCASCADA,
                AGRUPACION, DEUDA, CONFIRMFAC, DEUDA_ALB, DESFAMILIA, EURO, CEE,
                INTERNET, FECHAREVIS, JUEGOIVA, CODPROVCLI, MESNOPAGO, CUSECOTAX,
                CONTAINER, CUSPERCEFORE, CARRIER, IRPF1, IRPF2, CARRIAGE, GROUPODER, DESCATALOGADO,
                CODECNAE, FACTURAECONF, PUNTOVENTA, PRESCRIPTOR, INTERMEDIARIO, CANALDEVENTA,
                PRESCRIPTORDEFE, GROUPCLASI, EVALUATION, MOD340IGIC, MOD347SERVICES,
                MOD347INCLUDE, MOD347EXCLUDE, TYPEPERSON,
                TYPERESIDENCE, CORPORATEWEB, TIPOVIA, DIRECCIONNUMERO,
                DIRECCIONPISO, DIRECCIONLETRA, PERSONANOMBRE,
                PERSONAPELLIDO1, PERSONAPELLIDO2,
                SEPAISO, SEPADC, SEPACODE, SEPAID, COMUNIDAD,
                SEPATYPEID, SEPATYPEDEBT, SEPAMANDATE,
                SEPACATPURPO, SEPAPURPOSE,
                ACCDISCBILLS, ACCMATUNPAID, IDCUSTOMER,
                SERVEMODE, MUELLESALIDA, ATENCION,
                AGRUPAREXPED, CLIENTEVIP
            )
            VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?)");

        $values = [
            $customer->getId(), 0, date('Ymd'), '',
            $customer->getNombreCompleto(),
            $customer->getNombreCompleto(),
            '', $customer->getNif(),
            $customer->getDireccion(),
            $customer->getCiudad(),
            $customer->getProvincia(),
            $customer->getCodigoPostal(),
            $customer->getTelefono1(), $customer->getTelefono2(), '',
            0, '', 0, '', '', '', '',
            0, 0, 0, 0, '', 0,
            '', 0, '', 0, $customer->getPais(), '',
            0, 0, 0, 0, '', 0, $customer->getUe(),
            $customer->getEmail(), '19000101', $customer->getJuegoIVA(), '', 0, 0,
            'CO', 0, '8', 0, 0, 1, 0, 0,
            '', '', '', 0, 0, '',
            '', '', 0, 0, 0, 0, 0, '',
            '', '', '', 0, 0, '', '', '',
            '', '', '', '', '', '', 0, '', '', '', '', '', '', $customer->getId(), 2, 'MUSA',
            $customer->getNombre(),
            0, $customer->isVip() ? '1' : '0',
        ];

        if ($stmt->execute($values)) {
            return $this->saveSIIData($customer);
        }

        return false;
    }

    private function saveSIIData(CustomerAqua $customer): bool
    {
        if ($customer->isCanarias()) {
            return $this->aqua->execute("INSERT INTO DATSIICUSTOMER03 (CUSCODE, CUSCUSTOMER, CUSDELIVTAI, CUSINVOICEF2, CUSINVOICTAI) 
                            VALUES(dbo.GETAUID(NEWID()), '" . $customer->getId() . "', 0, 0, 1)");
        }

        if ($customer->isFacturaSimplificada()) {
            return $this->aqua->execute("INSERT INTO DATSIICUSTOMER03 (CUSCODE, CUSCUSTOMER, CUSDELIVTAI, CUSINVOICEF2, CUSINVOICTAI) 
                            VALUES(dbo.GETAUID(NEWID()), '" . $customer->getId() . "', 0, 1, 0)");
        }

        return $this->aqua->execute("INSERT INTO DATSIICUSTOMER03 (CUSCODE, CUSCUSTOMER, CUSDELIVTAI, CUSINVOICEF2, CUSINVOICTAI) 
                VALUES(dbo.GETAUID(NEWID()), '" . $customer->getId() . "', 0, 0, 0)");
    }
}