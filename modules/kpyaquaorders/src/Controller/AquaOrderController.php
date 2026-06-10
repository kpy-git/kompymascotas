<?php

namespace PrestaShop\Module\KpyAquaOrders\Controller;

use PDOException;
use PrestaShop\Module\KpyAquaOrders\Config\AquaCarrier;
use PrestaShop\Module\KpyAquaOrders\Config\AquaConfig;
use PrestaShop\Module\KpyAquaOrders\Config\AquaVendor;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Entity\OrderAqua;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Service\OperationCounter;
use PrestaShop\Module\KpyAquaOrders\Service\Mailer;
use PrestaShop\Module\KpyAquaOrders\Service\Tools;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class AquaOrderController
{
    private OperationCounter $operationCounter;

    private Mailer $mailer;

    public function __construct(private readonly DbMssql $aqua)
    {
        $this->operationCounter = new OperationCounter($aqua);
        $this->mailer = new Mailer();
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function cancelAquaOrder(int $orderId): void
    {
        $this->cancelAquaOperation($orderId);

        $relatedOrders = $this->getRelatedOrders($orderId);

        if (!empty($relatedOrders)) {
            foreach ($relatedOrders as $relacionado) {
                $this->cancelAquaOperation($relacionado);
            }
        }
    }

    /**
     * @throws KpyAquaSqlException
     */
    private function cancelAquaOperation(int $orderId): void
    {
        $opeNumber = $this->getOperationNumberByOrderId($orderId);

        try {

            $this->aqua->beginTransaction();
            $procedure = "dbo.sr_STM_CANCELPEDID" . AquaConfig::EMPRESA . AquaConfig::EJERCICIO;

            $aquaStmt = $this->aqua->prepare("EXEC $procedure  ? ,'', 1, ?, ?, ?");
            $aquaStmt->bindValue(1, $opeNumber, \PDO::PARAM_INT); // @NUMEROOPERACION
            $aquaStmt->bindValue(2, AquaConfig::EMPRESA); // @SYSCOMPANY
            $aquaStmt->bindValue(3, AquaConfig::EJERCICIO); // @SYSEXERCISE
            $aquaStmt->bindValue(4, AquaConfig::USUARIO); // @SYSUSERCODE
            $aquaStmt->execute();

            $procedure = "dbo.sr_WMRSTM_CANCELPE" . AquaConfig::EMPRESA . AquaConfig::EJERCICIO;

            $aquaStmt2 = $this->aqua->prepare("EXEC $procedure  ? ,'C', 1, ?, ?, ?");
            $aquaStmt2->bindValue(1, $opeNumber, \PDO::PARAM_INT); // @NUMEROOPERACION
            $aquaStmt2->bindValue(2, AquaConfig::EMPRESA); // @SYSCOMPANY
            $aquaStmt2->bindValue(3, AquaConfig::EJERCICIO); // @SYSEXERCISE
            $aquaStmt2->bindValue(4, AquaConfig::USUARIO); // @SYSUSERCODE
            $aquaStmt2->execute();

            $this->aqua->commit();

        } catch (\PDOException $ex) {
            $this->aqua->rollBack();

            throw new KpyAquaSqlException(
                $ex->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }

    public function getOperationNumberByOrderId(int $orderId): int
    {
        return (int)$this->aqua->getValue("SELECT ISNULL(NUMERO, 0) AS OPERATION 
                FROM DATOP03 WITH(NOLOCK) 
                WHERE NUMERO_DOC='{$orderId}' AND TIPOOPER='C'");
    }

    public function getRelatedOrders(int $id_order): array
    {
        $results = $this->aqua->execute(
            "SELECT OP.NUMERO_DOC AS RELACIONADO
            FROM DATPYMORDERSRELATED03 R WITH(NOLOCK)
            INNER JOIN DATOP03 OP WITH(NOLOCK) 
                  ON OP.NUMERO = R.RELACIONADO
            WHERE ORIGINAL = (SELECT NUMERO FROM DATOP03 WITH(NOLOCK) WHERE NUMERO_DOC='{$id_order}' AND TIPOOPER='C')");

        if (empty($results)) {
            return [];
        }

        return array_map(static function (array $row) {
            return trim($row['RELACIONADO']);
        }, $results);
    }

    /**
     * Añade una X al final del pedido para que pueda volver a entrar con el mismo número de pedido
     * @throws KpyAquaSqlException
     */
    public function renameOrder(int $orderId): void
    {
        try {
            $this->aqua->beginTransaction();

            $this->aqua
                ->prepare("UPDATE DATOP03 SET NUMERO_DOC=? WHERE NUMERO_DOC=? AND TIPOOPER='C'")
                ->execute([$orderId . 'X', $orderId]);

            $this->aqua->execute("UPDATE DATMO03
            SET DATMO03.PROVIENE='{$orderId}X'
            FROM DATMO03
            INNER JOIN DATOP03 WITH(NOLOCK) 
               ON DATOP03.NUMERO=DATMO03.NUMERO AND DATOP03.TIPOOPER='S'
            WHERE DATMO03.PROVIENE='{$orderId}'");

            $this->aqua->commit();

        } catch (\PDOException $ex) {
            $this->aqua->rollBack();

            throw new KpyAquaSqlException(
                $ex->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }

    /**
     * Verifica que un pedido cumple todas estas condiciones para que se pueda moficar:
     * - NO está bloqueado por ningún usuario
     * - NO está seleccionado por ningún usuario para generar un OT o está siendo modificado en AQUA
     * - NO está en ninguna orden de trabajo (las empaquetadas no cuentan, un pedido que se echa para atrás siempre va a estar una OT empaquetada como minimo)
     * - NO tiene líneas incorporadas
     */
    public function isModificable(int $orderId, bool $isRelatedOrder = false): bool
    {
        // no me fío de los resultados que pueda dar la de abajo cuando un pedido no existe
        if (!$this->existsOrder($orderId)) {
            // si no existe es modificable
            return true;
        }

        $sql = "SELECT OP.USERQUERY, OP.ISREADYUSER, OP.SELECCION,
                    (SELECT COUNT(*) FROM DATMO03 M WITH(NOLOCK) WHERE M.NUMERO = OP.NUMERO AND M.INCORPORAD <> 0) as INCORPORADAS,
                    (SELECT COUNT(*) FROM DATWMRORDOPTRABAJO03 OOT WITH(NOLOCK)
                        LEFT JOIN DATWMRORDTRABAJO03 OT WITH(NOLOCK) ON OT.OT=OOT.OT
                        where OOT.NUMERO = OP.NUMERO AND OT.ESTADO <> 'EMPAQUETADO') AS OT
                FROM DATOP03 OP WITH(NOLOCK)
                WHERE OP.NUMERO_DOC='{$orderId}' AND OP.TIPOOPER='C'";

        $results = $this->aqua->getRow($sql);

        if (empty($results)) {
            return true;
        }

        $modificable = trim($results['USERQUERY']) === ''
            && trim($results['ISREADYUSER']) === ''
            && trim($results['SELECCION']) === ''
            && (int)$results['INCORPORADAS'] === 0
            && (int)$results['OT'] === 0;

        // si el primer pedido ya no es modificable no hace falta comprobar nada más
        if (!$modificable) {
            return false;
        }

        // si el pedido que estamos consultando es un relacionado no hace falta buscarle relacionados también
        if (!$isRelatedOrder) {
            $relatedOrders = $this->getRelatedOrders($orderId);

            if (!empty($relatedOrders)) {
                foreach ($relatedOrders as $relacionado) {
                    // si alguno de los relacionados no es modificable no hace falta comprobar más
                    if (!$this->isModificable($relacionado, true)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function existsOrder(int $orderId): bool
    {
        return (int)$this->aqua->getValue(
                "SELECT CASE 
                    WHEN EXISTS(SELECT 1 FROM DATOP03 WITH(NOLOCK) WHERE NUMERO_DOC='{$orderId}' AND TIPOOPER='C') THEN 1 
                    ELSE 0 
                END AS EXISTS_ORDER") > 0;
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function deleteAquaOrder(int $orderId): void
    {
        // si se hace después de eliminar el primer pedido, no encontrará ningún relacionado
        $relatedOrders = $this->getRelatedOrders($orderId);

        $this->deleteAquaOperation($orderId);
        if (!empty($relatedOrders)) {
            foreach ($relatedOrders as $relacionado) {
                $this->deleteAquaOperation($relacionado);
            }
        }
    }

    /**
     * @throws KpyAquaSqlException
     */
    private function deleteAquaOperation(int $orderId): void
    {
        $opeNumber = $this->getOperationNumberByOrderId($orderId);

        if (!$opeNumber) {
            return;
        }

        try {
            $this->aqua->beginTransaction();

            $this->aqua->execute("DELETE FROM DATOP03 WHERE NUMERO={$opeNumber}");
            $this->aqua->execute("DELETE FROM DATMO03 WHERE NUMERO={$opeNumber}");
            $this->aqua->execute("DELETE FROM DATCHAOTICMOVPALET03 WHERE NUMBER={$opeNumber}");
            $this->aqua->execute("DELETE FROM DATHI03 WHERE OPERACION={$opeNumber}");

            $this->aqua->commit();

        } catch (\PDOException $ex) {
            $this->aqua->rollBack();

            throw new KpyAquaSqlException(
                $ex->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }

    public function orderBlock(int $orderId): bool
    {
        return $this->aqua
            ->prepare("UPDATE DATOP03 SET BLOQUEADO=1 WHERE NUMERO_DOC = ? AND TIPOOPER='C'")
            ->execute([$orderId]);
    }

    public function orderUnblock(int $orderId): bool
    {
        return $this->aqua
            ->prepare("UPDATE DATOP03 SET BLOQUEADO=0 WHERE NUMERO_DOC = ? AND TIPOOPER='C'")
            ->execute([$orderId]);
    }

    public function isOrderBlocked(int $id_order): bool
    {
        return (int)$this->aqua->getValue(
                "SELECT COUNT(*)
            FROM DATOP03 WITH(NOLOCK)
            WHERE BLOQUEADO=1 AND TIPOOPER='C' AND NUMERO_DOC='{$id_order}'") > 0;
    }

    private function execProcedureReserva(int    $operacion,
                                          int    $posicion,
                                          string $sku,
                                          int    $cantidad,
                                          string $almacen,
                                          string $tipo_operacion = 'C',
                                          int    $urgente = 0,
                                          int    $syssign = 1): void
    {
        $procedureName = "dbo.sr_CHAORESERVATION" . AquaConfig::EMPRESA . AquaConfig::EJERCICIO;

        $aquaStmt = $this->aqua->prepare("EXEC {$procedureName}  ?,  ?,  ?,  ?,  ?,  ?,  ?,  ?,  ?,  ?,  ?");

        $aquaStmt->bindValue(1, $tipo_operacion); // @TYPEOPER
        $aquaStmt->bindValue(2, $operacion, \PDO::PARAM_INT); // @MOVNUMBER
        $aquaStmt->bindValue(3, $posicion, \PDO::PARAM_INT); // @MOVPOSITION
        $aquaStmt->bindValue(4, $sku); // @MOVPRODUCTCODE
        $aquaStmt->bindValue(5, $cantidad, \PDO::PARAM_INT); // @MOVUNITS
        $aquaStmt->bindValue(6, $almacen); // @OPEWAREHOUSE1
        $aquaStmt->bindValue(7, $urgente, \PDO::PARAM_INT); // @OPEURGENT
        $aquaStmt->bindValue(8, $syssign, \PDO::PARAM_INT); // @SYSSIGN
        $aquaStmt->bindValue(9, AquaConfig::EMPRESA); // @SYSCOMPANY
        $aquaStmt->bindValue(10, AquaConfig::EJERCICIO); // @SYSEXERCISE
        $aquaStmt->bindValue(11, AquaConfig::USUARIO); // @SYSUSERCODE

        $aquaStmt->execute();

    }

    public function updateAddress(int|string $orderId, string $addressId): void
    {
        $this->aqua
            ->prepare("UPDATE DATOP03 SET DELEGACION=? WHERE NUMERO_DOC=? AND TIPOOPER='C'")
            ->execute([$addressId, $orderId]);

        $pedidosRelacionados = $this->getRelatedOrders($orderId);
        if (!empty($pedidosRelacionados)) {
            foreach ($pedidosRelacionados as $relacionado) {
                $this->updateAddress($addressId, $relacionado);
            }
        }
    }

    private function execProcedureHistoria(string $fecha,
                                           int    $operacion,
                                           int    $posicion,
                                           string $sku,
                                           string $almacen1,
                                           string $almacen2 = '',
                                           string $movquality = '',
                                           string $tipo_operacion = 'C',
                                           int    $syssign = 1): void
    {
        $procedureName = "dbo.sr_HISTORY" . AquaConfig::EMPRESA . AquaConfig::EJERCICIO;

        $stmt = $this->aqua->prepare("Exec $procedureName ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?");

        $stmt->bindValue(1, $fecha); // @MOVDATE
        $stmt->bindValue(2, $operacion, \PDO::PARAM_INT); // @MOVNUMBER
        $stmt->bindValue(3, $posicion, \PDO::PARAM_INT); // @OPPOSITION
        $stmt->bindValue(4, $sku); // @MOVPRODUCTCODE
        $stmt->bindValue(5, $almacen1); // @OPEWAREHOUSE1
        $stmt->bindValue(6, $almacen2); // @OPEWAREHOUSE2
        $stmt->bindValue(7, $movquality); // @MOVQUALITY
        $stmt->bindValue(8, $tipo_operacion); // @TYPEOPER
        $stmt->bindValue(9, $syssign, \PDO::PARAM_INT); // @SYSSIGN
        $stmt->bindValue(10, AquaConfig::EMPRESA); // @SYSCOMPANY
        $stmt->bindValue(11, AquaConfig::EJERCICIO); // @SYSEXERCISE
        $stmt->bindValue(12, AquaConfig::USUARIO); // @SYSUSERCODE

        $stmt->execute();
    }

    /**
     * ejecuta el código del procedimiento almacenado sr_HISTORY directamente desde aquí
     *
     */
    private function saveHistory(string $fecha,
                                 int    $operacion,
                                 int    $posicion,
                                 string $sku,
                                 string $almacen1,
                                 string $movquality = ''): void
    {

        $resultsPRD = $this->aqua->execute("SELECT TOP 1 ISNULL( DATIN03.EXISTENCIA, 0 ) AS PRDSTOCK, ISNULL( DATIN03.PRECIOCMED, 0 ) AS PRDPCMP FROM DATIN03 WITH (NOLOCK) WHERE DATIN03.CODIGO = '{$sku}'");

        $resultsDTL = $this->aqua->execute("SELECT TOP 1
             ISNULL( DATAS03.EXISTENCIA, 0 ) as DTLSTOCK, ISNULL( DATAS03.PRECIOCMED, 0 ) as DTLPCMP,
             ISNULL( DATAS03.EXIST0, 0 ) as DTLSTOCK00, ISNULL( DATAS03.EXIST1, 0 ) as DTLSTOCK01, ISNULL( DATAS03.EXIST2, 0 ) as DTLSTOCK02,
             ISNULL( DATAS03.EXIST3, 0 ) as DTLSTOCK03, ISNULL( DATAS03.EXIST4, 0 ) as DTLSTOCK04, ISNULL( DATAS03.EXIST5, 0 ) as DTLSTOCK05,
             ISNULL( DATAS03.EXIST6, 0 ) as DTLSTOCK06, ISNULL( DATAS03.EXIST7, 0 ) as DTLSTOCK07, ISNULL( DATAS03.EXIST8, 0 ) as DTLSTOCK08,
             ISNULL( DATAS03.EXIST9, 0 ) as DTLSTOCK09, ISNULL( DATAS03.EXIST10, 0 ) as DTLSTOCK10, ISNULL( DATAS03.EXIST11, 0 ) as DTLSTOCK11,
             ISNULL( DATAS03.EXIST12, 0 ) as DTLSTOCK12, ISNULL( DATAS03.EXIST13, 0 ) as DTLSTOCK13, ISNULL( DATAS03.EXIST14, 0 ) as DTLSTOCK14,
             ISNULL( DATAS03.EXIST15, 0 ) as DTLSTOCK15, ISNULL( DATAS03.EXIST16, 0 ) as DTLSTOCK16, ISNULL( DATAS03.EXIST17, 0 ) as DTLSTOCK17,
             ISNULL( DATAS03.EXIST18, 0 ) as DTLSTOCK18, ISNULL( DATAS03.EXIST19, 0 ) as DTLSTOCK19
         FROM DATAS03 WITH (NOLOCK)
        WHERE DATAS03.CODIGO = '{$sku}' AND DATAS03.ALMACEN = '{$almacen1}' AND DATAS03.CUALIDAD = '{$movquality}'");

        $this->aqua->execute("DELETE FROM DATHI03 WHERE DATHI03.OPERACION = {$operacion} AND DATHI03.NUMERO = {$posicion} AND DATHI03.ALMACEN = '{$almacen1}'");

        $sqlInsert = "INSERT INTO DATHI03 ( DATHI03.FECHA, DATHI03.OPERACION, DATHI03.NUMERO, DATHI03.CODART   , DATHI03.ALMACEN           , DATHI03.EXISTENCIA         , DATHI03.PCMP         , DATHI03.PCMPCENTRO  , DATHI03.EXISCENTRO  , DATHI03.CUALIDAD, DATHI03.EXIST01         , DATHI03.EXIST02         , DATHI03.EXIST03         , DATHI03.EXIST04         , DATHI03.EXIST05         , DATHI03.EXIST06         , DATHI03.EXIST07         , DATHI03.EXIST08         , DATHI03.EXIST09         , DATHI03.EXIST10         , DATHI03.EXIST11         , DATHI03.EXIST12         , DATHI03.EXIST13         , DATHI03.EXIST14         , DATHI03.EXIST15         , DATHI03.EXIST16         , DATHI03.EXIST17         , DATHI03.EXIST18         , DATHI03.EXIST19         , DATHI03.EXIST20         , DATHI03.TIPOPROVIENE )
                  VALUES( '{$fecha}'   , {$operacion}       , {$posicion}  , '{$sku}', '{$almacen1}', {$resultsPRD[0]['PRDSTOCK']}, {$resultsPRD[0]['PRDPCMP']}, {$resultsDTL[0]['DTLPCMP']}, {$resultsDTL[0]['DTLSTOCK']}, '{$movquality}'    , {$resultsDTL[0]['DTLSTOCK00']}, {$resultsDTL[0]['DTLSTOCK01']}, {$resultsDTL[0]['DTLSTOCK02']}, {$resultsDTL[0]['DTLSTOCK03']}, {$resultsDTL[0]['DTLSTOCK04']}, {$resultsDTL[0]['DTLSTOCK05']}, {$resultsDTL[0]['DTLSTOCK06']}, {$resultsDTL[0]['DTLSTOCK07']}, {$resultsDTL[0]['DTLSTOCK08']}, {$resultsDTL[0]['DTLSTOCK09']}, {$resultsDTL[0]['DTLSTOCK10']}, {$resultsDTL[0]['DTLSTOCK11']}, {$resultsDTL[0]['DTLSTOCK12']}, {$resultsDTL[0]['DTLSTOCK13']}, {$resultsDTL[0]['DTLSTOCK14']}, {$resultsDTL[0]['DTLSTOCK15']}, {$resultsDTL[0]['DTLSTOCK16']}, {$resultsDTL[0]['DTLSTOCK17']}, {$resultsDTL[0]['DTLSTOCK18']}, {$resultsDTL[0]['DTLSTOCK19']}, 1 )";

        $this->aqua->execute($sqlInsert);

    }

    private function execProcedureIncorporados(int    $unidades,
                                               int    $operacion,
                                               int    $posicion,
                                               string $movprevious = '',
                                               string $serial = '',
                                               string $tipo_operacion = 'C',
                                               int    $syssign = 1): void
    {
        $procedureName = "dbo.sr_INCORPORATED" . AquaConfig::EMPRESA . AquaConfig::EJERCICIO;

        $aquaStmt = $this->aqua->prepare("EXEC {$procedureName} ?, ?, ?, ?, ?, ?, ?, ?, ?, ?");
        $aquaStmt->bindValue(1, $movprevious); // @MOVPREVIOUS
        $aquaStmt->bindValue(2, $unidades, \PDO::PARAM_INT); // @MOVUNITS
        $aquaStmt->bindValue(3, $operacion, \PDO::PARAM_INT); // @MOVNUMBER
        $aquaStmt->bindValue(4, $posicion, \PDO::PARAM_INT); // @OPPOSITION
        $aquaStmt->bindValue(5, $serial); // @PRDSERIAL
        $aquaStmt->bindValue(6, $tipo_operacion); //
        $aquaStmt->bindValue(7, $syssign, \PDO::PARAM_INT); // @SYSSIGN
        $aquaStmt->bindValue(8, AquaConfig::EMPRESA); // @SYSCOMPANY
        $aquaStmt->bindValue(9, AquaConfig::EJERCICIO); // @SYSEXERCISE
        $aquaStmt->bindValue(10, AquaConfig::USUARIO); // @SYSUSERCODE

        $aquaStmt->execute();
    }

    /**
     * Escribe la información del pedido en la base de datos de AQUA y ejecuta los procedimientos almacenados necesarios
     * que había en la meta-regla del bot
     *
     * En la meta-regla del bot también se ejecutaba el procedimiento sr_PCMP, revisando el código se ve que cuando es un
     * pedido de venta no se hace nada, nos ahorramos la ejecución
     *
     * @throws KpyAquaSqlException
     * @throws KpyAquaOrderException
     */
    public function export(OrderAqua $order, $relacionado = false): bool
    {
        // si el pedido ya existe se sale y se manda email
        if ($this->existsOrder($order->getIdOrder())) {
            $this->mailer->sendMailError(
                $order->getIdOrder(),
                "PYMAQUAORDERS: intento de inserción de un pedido duplicado: " . $order->getIdOrder(),
                "Intento de inserción de pedido duplicado en Aqua, " . date("d-m-Y H:i:s")
            );

            return false;
        }

        $usuario = AquaConfig::USUARIO;
        $almacen = $order->getCarrier() !== AquaCarrier::CARRIER_SACOS_ROTOS
            ? AquaConfig::ALMACEN
            : AquaConfig::ALMACEN_ROTOS;

        $precisionTotales = AquaConfig::PRECISION_TOTALES;
        $precisionLinea = AquaConfig::PRECISION_LINEA;

        $operacion = $this->operationCounter->getNewOperationNumber(AquaConfig::EMPRESA, AquaConfig::EJERCICIO);

        $productos = $order->getProductos();
        $positions = $this->operationCounter->getNewRangeOperationNumber(AquaConfig::EMPRESA, AquaConfig::EJERCICIO, count($productos));

        if (count($positions) !== count($productos)) {

            $this->mailer->sendMailError(
                $order->getIdOrder(),
                "PYMAQUAORDERS: error al obtener los números de posiciones: " . $order->getIdOrder(),
                "Se han obtenido " . count($positions) . " posiciones (" . implode(",", $positions) . "), son necesarias " . count($productos)
            );

            throw new KpyAquaOrderException("Se han obtenido " . count($positions) . " posiciones (" . implode(",", $positions) . "), son necesarias " . count($productos));

        }

        if ($order->getVendor() === AquaVendor::AMAZON_PRIME) {
            // para los pedidos de amazon prime se guarda en el campo observaciones el numero de pedido de amazon
            // será necesario en el momento se obtener la etiqueta
            $order->setObservaciones($this->aqua->getValue('SELECT id_order_amazon FROM ' . _DB_PREFIX_ . 'pym_amazon WHERE id_order_pym=' . $order->getIdOrder()));
        }

        // a partir de 2021 para los pedidos de los marketplaces se crea un nuevo cliente en AQUA
        // para cada pedido, en PrestaShop se seguirá usando el mismo
        $customer = Tools::getPrefixByCustomerId($order->getIdCustomer()) . $order->getIdCustomer();
        if ($order->getVendor() === AquaVendor::CLICK_CANARIAS) {
            $customer = AquaConfig::CANARIAS_CUSTOMER_CODE;
        }

        $aquaTaxes = $order->getVendor()->getAquaTaxes();

        $iva_1 = $aquaTaxes->getIva1();
        $iva_2 = $aquaTaxes->getIva2();
        $iva_3 = $aquaTaxes->getIva3();

        $recargo_1 = $aquaTaxes->getRecargo1();
        $recargo_2 = $aquaTaxes->getRecargo2();
        $recargo_3 = $aquaTaxes->getRecargo3();

        $juegoIVA = $aquaTaxes->getJuegoIVA();
        $base_iva_2 = $order->getBaseIva21();
        $base_iva_3 = $order->getBaseIva10();

        if ($order->getVendor() === AquaVendor::WECPT) {
            $base_iva_2 = $order->getBaseIva23();
        } else if ($order->getVendor() === AquaVendor::WECIT) {
            $base_iva_2 = $order->getBaseIva22();
        }

        if ($juegoIVA !== '' && $order->isExentoIva()) {
            $juegoIVA = '';
        }

        try {
            $this->aqua->beginTransaction();

            $sqlOperacion = "INSERT INTO DATOP03(
                        NUMERO, FECHA, NIF, DELEGACION, TIPOOPER, USUARIO, 
                        REVISADO, CENTRO, PCIVA1, PCIVA2, PCIVA3, NUMERO_DOC,
                        PCREC1, PCREC2, PCREC3, FORMATO,
                        NUMPRECISI, NUMPREC_LI, EUROTODIVI, EUROTOMONE, NCUENTA, NEFECTO,
                        FORMAP, CONTROL, F_PREVISTA,
                        JUEGOIVA, MONEDA, EXENTOIVA, IRPF1, IRPF2,
                        DES1, DES2, DES3, DES4,
                        DES5, DES6, DTOTOTAL, DTO_PP, LINEA_COM,
                        LINEA_DTO, AUTHORIZED, CARRIER, CARRIAGE, SERVEMODE, WHARF,
                        OBSERVACIO, COMENT_1, COMENT_3, HORA,
                        SERVICIOS, ARTICULOS, TOTALDEC, TOTAL, T_TARIFA,
                        UNIDADES, PENDIENTES, TOTALNETO, TOTALDECIRPF, TOTALIRPF,
                        TOTIVA, TOTRECARGO, TOTIVAIRPF, TOTRECARGOIRPF,
                        PESO, VOLUMEN, BULTOS,
                        BASEIVA1, BASEIVA2, BASEIVA3, BASEREC1, BASEREC2, BASEREC3,
                        TOTIVAIRPF1, TOTIVAIRPF2, TOTIVAIRPF3,
                        TOTRECARGOIRPF1, TOTRECARGOIRPF2, TOTRECARGOIRPF3,
                        NOFACTURA, PESOREAL
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, 
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, 
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, ?,
                        ?, ?
                    );";

            $stmtOperation = $this->aqua->prepare($sqlOperacion);

            $stmtOperation->execute([$operacion, $order->getFechaPedido(), $customer, $order->getIdAddress(), 'C', $usuario, $usuario,
                $almacen, $iva_1, $iva_2, $iva_3, (string)$order->getIdOrder(),
                $recargo_1, $recargo_2, $recargo_3, '',
                $precisionTotales, $precisionLinea, 1, 1, $order->getNcuenta(), $order->getNefecto(),
                $order->getFormaPago(), $order->getVendor()->value, date('d/m/Y'),
                $juegoIVA, '', $order->isExentoIva() ? 1 : 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0, 0,
                0, 1, $order->getCarrier()->value, 1, 2, 'MUSA', substr($order->getObservaciones(), 0, 100), '', '', $order->getHoraPedido(),
                $order->getTotalServicios(), $order->getTotalArticulos(), $order->getTotalSinIva(), $order->getTotalSinIva(), $order->getTotalTarifa(),
                $order->getUnidades(), $order->getLineas(), 0, $order->getTotalSinIva(), $order->getTotalSinIva(),
                $order->getTotalIva(), $order->getTotalRecargo(), $order->getTotalIva(), $order->getTotalRecargo(),
                $order->getPeso(), $order->getVolumen(), 0,
                $order->getBaseIva4(), $base_iva_2, $base_iva_3, $order->getBaseRecargo4(), $order->getBaseRecargo21(), $order->getBaseRecargo10(),
                $order->getBaseIva4(), $base_iva_2, $base_iva_3, $order->getBaseRecargo4(), $order->getBaseRecargo21(), $order->getBaseRecargo10(),
                $order->getSinFactura(), $order->getPeso(),
            ]);

            if ($relacionado !== false) {
                $sqlGuardaRelacion = "
                INSERT INTO DATPYMORDERSRELATED03 (ORIGINAL, RELACIONADO)
                VALUES ((SELECT NUMERO FROM DATOP03 WITH(NOLOCK) WHERE NUMERO_DOC='{$relacionado}' AND TIPOOPER='C'), {$operacion})";
                $this->aqua->execute($sqlGuardaRelacion);
            }

            $stmtProduct = $this->aqua->prepare("INSERT INTO DATMO03 (
                        FECHA, NUMERO, POSICION, CODART, UNIDADES, REC,
                        IMPORTE, TOTIMPORTE, TOTAL, IVA, DENOMINACI,
                        TARIFA, DESCUENTO, COM, PROYECTO, CUALIDAD,
                        H0UNIDADES, H1UNIDADES, H2UNIDADES, H3UNIDADES, H4UNIDADES,
                        H5UNIDADES, H6UNIDADES, H7UNIDADES, H8UNIDADES, H9UNIDADES,
                        H10UNIDADE, H11UNIDADE, H12UNIDADE, H13UNIDADE, H14UNIDADE,
                        H15UNIDADE, H16UNIDADE, H17UNIDADE, H18UNIDADE, H19UNIDADE,
                        HSERIE, NETO
                    )
                    VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?
                    );");


            foreach ($productos as $producto) {
                // $posicion = $this->operationCounter->getNewOperationNumber(AquaConfig::EMPRESA, AquaConfig::EJERCICIO);
                $posicion = array_shift($positions);

                $stmtProduct->execute([$order->getFechaPedido(), $operacion, $posicion, $producto['codigo'], $producto['cantidad'], $producto['recargo'],
                    $producto['importe'], $producto['totimporte'], $producto['totimporte'], $producto['iva'], $producto['nombre'],
                    $producto['tarifa'], $producto['descuento'], 0, '', '',
                    0, 0, 0, 0, 0,
                    0, 0, 0, 0, 0,
                    0, 0, 0, 0, 0,
                    0, 0, 0, 0, 0,
                    '', 0]);

                $this->execProcedureReserva($operacion, $posicion, $producto['codigo'], $producto['cantidad'], $almacen);

                $this->execProcedureHistoria($order->getFechaPedidoHistoria(), $operacion, $posicion, $producto['codigo'], AquaConfig::ALMACEN);

                $this->execProcedureIncorporados($producto['cantidad'], $operacion, $posicion);
            }

            $this->aqua->commit();

            return true;

        } catch (PDOException $e) {
            $this->aqua->rollback();

            //$this->mailer->sendMailError($order->getIdOrder(), "PYMAQUAORDERS: error al crear el pedido " . $order->getIdOrder(), $e->getMessage() . "\n\nSQL: " . $this->aqua->getLastSql());

            throw new KpyAquaSqlException(
                $e->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }

    public function orderWithStockCompletelyReserved(string $pedido): bool
    {
        // si todas las unidades del pedido tienen reserva
        return (int)$this->aqua->getValue("SELECT SUM(MO.UNIDADES-ISNULL(MPA.UNITS,0))
                FROM DATOP03 OP WITH(NOLOCK)
                INNER JOIN DATMO03 MO WITH(NOLOCK) ON MO.NUMERO = OP.NUMERO
                INNER JOIN DATIN03 P WITH(NOLOCK) ON P.CODIGO = MO.CODART AND P.CONTROLADO = 1
                LEFT JOIN DATCHAOTICMOVPALET03 MPA WITH(NOLOCK) ON OP.NUMERO = MPA.[NUMBER] AND MO.POSICION = MPA.[POSITION]
                WHERE OP.TIPOOPER = 'C' AND OP.NUMERO_DOC = '{$pedido}'") === 0;
    }

    public function numberOfTimesProductAppearsInCustomerOrders(string $sku, int $customerID): int
    {
        return (int)$this->aqua->getValue(
            "SELECT COUNT(DISTINCT OP.NUMERO)
            FROM DATOP03 OP WITH(NOLOCK)
            INNER JOIN DATMO03 MO WITH(NOLOCK) ON MO.NUMERO = OP.NUMERO AND MO.CODART='{$sku}'
            WHERE OP.TIPOOPER = 'C' AND OP.NIF='{$customerID}'"
        );
    }

    public function setProductsOrderAsPendingSync(int $idOrder): void
    {
        $this->aqua
            ->prepare(
                "UPDATE P
                    SET P.PENDING_SYNC = 1
                    FROM DATIN03 P
                    INNER JOIN DATMO03 MO WITH(NOLOCK)
                        ON MO.CODART = P.CODIGO
                    INNER JOIN DATOP03 OP WITH(NOLOCK)
                        ON OP.NUMERO = MO.NUMERO
                    WHERE P.CONTROLADO = 1
                      AND OP.NUMERO_DOC = ?
                      AND OP.TIPOOPER = 'C'
                      AND OP.CENTRO = ?")
            ->execute([
                $idOrder, AquaConfig::ALMACEN
            ]);
    }

    public function bloqueaPedidosRelacionadosSiEsNecesario(array $ordersRelatedIds): void
    {
        if (empty($ordersRelatedIds)) {
            return;
        }

        if (array_all($ordersRelatedIds, fn(string $pedido) => $this->orderWithStockCompletelyReserved($pedido))) {
            foreach ($ordersRelatedIds as $pedido) {
                $this->orderBlock($pedido);
            }
        }
    }

    /**
     * @throws KpyAquaOrderException
     * @throws KpyAquaSqlException
     */
    public function moveToNeftysFarmaWarehouse(int $idOrder): void
    {
        $operation = $this->getOperationNumberByOrderId($idOrder);

        if (!$operation) {
            throw new KpyAquaOrderException('No existe ningún pedido en AQUA: ' . $idOrder);
        }

        try {
            $this->aqua->beginTransaction();

            $stmtDeleteReserve = $this->aqua->prepare("DELETE FROM DATCHAOTICMOVPALET03 WHERE NUMBER = ?");
            $stmtDeleteReserve->execute([$operation]);

            $stmtUpdate = $this->aqua->prepare("UPDATE DATOP03 SET CENTRO=? WHERE NUMERO=?");
            $stmtUpdate->bindValue(1, AquaConfig::ALMACEN_NEFTYS);
            $stmtUpdate->bindValue(2, $operation, \PDO::PARAM_INT);

            $stmtUpdate->execute();

            $this->aqua->commit();

        } catch (PDOException $e) {
            $this->aqua->rollback();

            throw new KpyAquaSqlException(
                $e->getMessage(),
                __METHOD__,
                $this->aqua->getLastSql(),
                $this->aqua->getSqlError()
            );
        }
    }
}