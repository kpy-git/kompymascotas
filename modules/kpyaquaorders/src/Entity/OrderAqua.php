<?php

namespace PrestaShop\Module\KpyAquaOrders\Entity;

use Customer;
use Db;
use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaCarrier;
use PrestaShop\Module\KpyAquaOrders\Config\AquaVendor;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Service\Finder;
use Product;

class OrderAqua
{
    public const string PORTES = 'PORTES';
    public const string PORTES_NOMBRE = 'Portes';
    public const string DESCUENTO10_COD = 'DESCUENTO';
    public const string DESCUENTO10_NOMBRE = 'Descuentos';
    public const string DESCUENTO21_COD = 'DESCUENTO21';
    public const string DESCUENTO21_NOMBRE = 'Descuentos AL 21%';
    public const string DESCUENTO23_COD = 'DESCUENTO23';
    public const string DESCUENTO23_NOMBRE = 'Descuentos AL 23%';
    public const string DESCUENTO22_COD = 'DESCUENTO22';
    public const string DESCUENTO22_NOMBRE = 'Descuentos AL 22%';
    public const string DESCUENTO20_COD = 'DESCUENTO20';
    public const string DESCUENTO20_NOMBRE = 'Descuentos AL 20%';
    public const string DESCUENTO4_COD = 'DESCUENTO4';
    public const string DESCUENTO4_NOMBRE = 'Descuentos al 4%';
    public const string DESCUENTO6_COD = 'DESCUENTO6';
    public const string DESCUENTO6_NOMBRE = 'Descuentos al 6%';
    public const string DESCUENTO_PORTES_COD = 'DESCUENTOSPORTES';
    public const string DESCUENTO_PORTES_NOMBRE = 'Descuento de Portes';

    private int $id_order;

    private int $id_customer;

    private string $id_address;

    private float $recargo;

    private bool $exento_iva;

    private AquaCarrier $carrier;

    private AquaVendor $vendedor;

    private float $total_con_iva; // no es el total con iva del pedido, es el total de productos con iva que se utiliza para prorratear los descuentos al 10% y 21%

    private float $total_sin_iva;

    private float $total_iva; // cantidad de iva

    private array $productos;

    private string $forma_pago;

    private string $ncuenta;

    private string $nefecto;

    private float $total_articulos; // sin iva

    private float $total_servicios; // sin iva

    private float $total_tarifa;

    private float $total_recargo;

    private float $base_recargo_4;

    private float $base_recargo_10;

    private float $base_recargo_21;

    private float $base_iva_4;

    private float $base_iva_6;

    private float $base_iva_10;

    private float $base_iva_21;

    private float $base_iva_20;

    private float $base_iva_22;

    private float $base_iva_23;

    private float $total_iva_4;

    private float $total_iva_6;

    private float $total_iva_10; // total de los productos que tienen el iva al 10% para poder sacar la parte de descuento

    private float $total_iva_21; // total de productos que llevan el iva al 21% para luego sacar la parte de descuento

    private float $total_iva_20;

    private float $total_iva_22;

    private float $total_iva_23;

    private string $fecha_pedido;

    private string $hora_pedido;

    private string $fecha_facturacion;

    private string $fecha_pedido_historia;

    private string $observaciones;

    private int $lineas;

    private int $unidades;

    private float $peso;

    private float $volumen;

    private int $sinFactura;

    private Finder $aquafinder;

    public function __construct(Order $order, private readonly DbMssql $aqua)
    {
        $this->aquafinder = new Finder($aqua);

        $this->id_order = $order->id;
        $this->id_customer = $order->id_customer;
        $this->id_address = (string)$order->id_address_delivery;
        $this->recargo = in_array(23, Customer::getGroupsStatic((int)$order->id_customer));
        if ($order->total_paid_tax_incl === $order->total_paid_tax_excl && $order->total_paid_tax_incl > 0) {
            // para los gratuitos es igual el total con iva y sin iva (0) pero no son exentos de iva
            $this->exento_iva = true;
        } else {
            $this->exento_iva = in_array(21, Customer::getGroupsStatic((int)$order->id_customer));
        }

        $this->carrier = AquaCarrier::fromPS($order->id_carrier);

        if ($this->carrier === AquaCarrier::DHL_SERVICE_POINT) {
            $this->id_address = 'SP' . $this->id_order;
        } else if ($this->carrier === AquaCarrier::SEUR_PICKUP) {
            $this->id_address = 'PK' . $this->id_order;
        }

        $this->vendedor = $this->loadVendor($order->id_shop, $order->id_customer, $order->module, $this->carrier);

        $this->forma_pago = $this->getFormaPagoBy($order->module, $order->payment, $order->id_shop);

        $this->sinFactura = $this->forma_pago === 'GRT' ? 1 : 0;

        $info_pago = $this->getArrayWithAquaInfoPayment($this->forma_pago);
        $this->nefecto = $info_pago['nefecto'];
        $this->ncuenta = $info_pago['ncuenta'];

        $this->observaciones = $this->getMessagesByOrderId($order->id);

        $this->productos = array();

        $timestampDateAdd = strtotime($order->date_add);
        $this->fecha_pedido = date("d/m/Y", $timestampDateAdd);
        $this->hora_pedido = date("H:i:s", $timestampDateAdd);

        $timestampInvoiceDate = strtotime($order->invoice_date);
        $this->fecha_facturacion = date("Ymd", $timestampInvoiceDate);

        // para los pedidos por transferencia y multibanco la fecha y hora de pedido se convierte a la fecha y hora cuando se realiza el pago
        if (in_array($order->module, array('ps_bankwire', 'bankwire', 'comprafacil', 'ps_wirepayment'))) {
            $this->fecha_pedido = $this->fecha_facturacion;
            $this->hora_pedido = date("H:i:s", $timestampInvoiceDate);
        }

        $this->fecha_pedido_historia = $this->fecha_pedido;

        $this->total_con_iva = $this->total_sin_iva = $this->total_articulos = $this->total_servicios = $this->total_tarifa = $this->total_iva = 0;
        $this->base_iva_21 = $this->base_iva_10 = $this->base_iva_4 = 0; // las bases se irán calculando conforme se vayan añadiendo productos, como los totales
        $this->base_iva_20 = $this->base_iva_22 = $this->base_iva_23 = 0;
        $this->total_iva_4 = $this->total_iva_10 = $this->total_iva_21 = 0; // totales para sacar después el porcentaje de cada iva del total
        $this->total_iva_20 = $this->total_iva_22 = $this->total_iva_23 = 0;
        $this->total_recargo = $this->base_recargo_21 = $this->base_recargo_10 = $this->base_recargo_4 = 0;
        $this->total_iva_6 = 0;
        $this->base_iva_6 = 0;

        $this->lineas = $this->unidades = $this->peso = $this->volumen = 0;
    }

    public function getMessagesByOrderId(int $orderId): string
    {
        $results = Db::getInstance()->getRow(
            "SELECT REPLACE(a.other, '\r\n', ' ') as message1, IFNULL(REPLACE(m.message, '\r\n', ' '),'') as message2 
                FROM " . _DB_PREFIX_ . "orders o 
                INNER JOIN " . _DB_PREFIX_ . "address a 
                    ON a.id_address = o.id_address_delivery 
                LEFT JOIN " . _DB_PREFIX_ . "message m 
                    ON m.id_order=o.id_order 
                WHERE m.message NOT LIKE '%payment%' 
                    AND m.message NOT LIKE '%transaccion%' 
                    AND o.id_order =" . $orderId);

        return empty($results) ? '' : mb_strtoupper(trim($results['message1'] . " " . $results['message2']));
    }

    public function cambiarIdOrder($id_order): void
    {
        $this->id_order = $id_order;
    }

    /**
     * Añade un nuevo producto para el pedido de AQUA e incrementa el total de la operación
     * controla que el producto que viene es un pack o no y lo descompone en caso de que lo sea
     *
     * Recibe el pvp total de la línea, y se saca a partir de ahí el precio de la unidad y el precio de tarifa
     * si se hace al revés, se puede perder algún decimal al comprar varias unidades
     */
    public function addProduct($id, $attr, $unidades, $pvp_sin_iva, $pvp_con_iva, $descuento, $tipo_iva): void
    {
        $tipo_recargo = 0;
        if ($this->recargo) {
            $tipo_recargo = match ($tipo_iva) {
                10 => 1.4,
                21 => 5.2,
                4 => 0.5,
                default => 0,
            };
        }

        // los de click canarias y los intracomunitarios van sin iva en los productos y descuentos??
        if ($pvp_sin_iva === $pvp_con_iva || $this->exento_iva) {
            $tipo_iva = 0;
        }

        if (($productos = Product::getProductsInPack($id . '-' . $attr))) {
            foreach ($productos as $producto) {
                if ($producto['is_gift'] == 1) {
                    $precio_sin_iva = $tarifa = 0;
                } else {
                    // el precio unitario del producto que va dentro del pack
                    $precio_sin_iva = round((($pvp_sin_iva / $unidades) / (int)$producto['quantity']), 6);
                    $tarifa = round((100 * $precio_sin_iva) / (100 - $descuento), 6);
                    //$precio_con_iva = ($pvp_con_iva / (int) producto['quantity']);
                }

                $logisticInfo = $this->aquafinder->getLogisticProductInfoBySku($producto['sku']);

                $this->productos[] = array(
                    'codigo' => $producto['sku'],
                    'nombre' => $logisticInfo->getDescription(),
                    'cantidad' => (int)$producto['quantity'] * $unidades,
                    'descuento' => $producto['is_gift'] != 1 ? $descuento : 0,
                    'tarifa' => $tarifa, // precio sin iva del producto antes de aplicar el dto
                    'importe' => $precio_sin_iva,
                    'totimporte' => $producto['is_gift'] != 1 ? $pvp_sin_iva : 0,
                    'iva' => $tipo_iva, // porcentaje de iva
                    'recargo' => $tipo_recargo,
                );
                $this->total_tarifa += $tarifa * (int)$producto['quantity'] * $unidades;

                $this->peso += $logisticInfo->getWeight() * $unidades * (int)$producto['quantity'];
                $this->volumen += $logisticInfo->getVolume() * $unidades * (int)$producto['quantity'];
                $this->unidades += $unidades * (int)$producto['quantity'];
            }

        } else {
            $tarifa = round((100 * ($pvp_sin_iva / $unidades)) / (100 - $descuento), 6);
            $this->total_tarifa += $tarifa * $unidades;

            $logisticInfo = $this->aquafinder->getLogisticProductInfoBySku($id . "-" . $attr);

            $this->productos[] = array(
                'codigo' => $id . '-' . $attr,
                'nombre' => $logisticInfo->getDescription(),
                'cantidad' => $unidades,
                'descuento' => $descuento,
                'tarifa' => $tarifa,
                'importe' => $pvp_sin_iva / $unidades,
                'totimporte' => $pvp_sin_iva,
                'iva' => $tipo_iva,
                'recargo' => $tipo_recargo,
            );

            $this->peso += $logisticInfo->getWeight() * $unidades;
            $this->volumen += $logisticInfo->getVolume() * $unidades;
            $this->unidades += $unidades;
        }

        $this->total_sin_iva += $pvp_sin_iva;
        $this->total_articulos += $pvp_sin_iva;
        $this->total_iva += $pvp_con_iva - $pvp_sin_iva;

        if ($this->recargo) {
            $this->total_recargo += $pvp_sin_iva * ($tipo_recargo / 100);
            $this->acumulaRecargos($pvp_sin_iva, $tipo_recargo);
        }
        //$this->total_con_iva += $pvp_con_iva;
        $this->acumulaTotales($pvp_con_iva, $tipo_iva);
        $this->acumulaBase($pvp_sin_iva, $tipo_iva);

        $this->lineas++;

    }

    /**
     * Añade el producto portes al pedido para simular los portes de PrestaShop
     */
    public function insertaPortes(float $portes_sin_iva): void
    {
        $pc_recargo = $this->recargo ? 5.2 : 0;

        $iva = $this->exento_iva ? 0 : $this->vendedor->getShippingTaxRate();

        $this->productos[] = array(
            'codigo' => self::PORTES,
            'nombre' => self::PORTES_NOMBRE,
            'cantidad' => 1,
            'descuento' => 0,
            'tarifa' => $portes_sin_iva,
            'importe' => $portes_sin_iva,
            'totimporte' => $portes_sin_iva,
            'iva' => $iva,
            'recargo' => $pc_recargo,
        );
        $this->total_servicios += $portes_sin_iva;
        $this->total_sin_iva += $portes_sin_iva;
        if (!$this->exento_iva) {
            $this->total_iva += ($portes_sin_iva * (1 + ($iva / 100))) - $portes_sin_iva;
        }
        $this->total_tarifa += $portes_sin_iva;

        $this->acumulaBase($portes_sin_iva, $iva);

        if ($this->recargo) {
            $this->total_recargo += $portes_sin_iva * (5.2 / 100);
            $this->acumulaRecargos($portes_sin_iva, 5.2);
        }

        // no se acumulan los portes para el total de iva, los descuentos de prestashop no tienen en cuenta el transporte
        // en el caso de que haya un descuento de portes gratis este siempre sera al 21%
        //$this->acumulaTotales($portes_sin_iva * 1.21, 21);
        $this->lineas++;
        $this->unidades++;
    }

    public function insertaDescuentos($total_descuentos_con_iva): void
    {
        $taxRates = array(10, 21, 4, 20, 22, 23, 6);

        if (!$this->exento_iva && $this->vendedor !== AquaVendor::CLICK_CANARIAS) {
            foreach ($taxRates as $iva) {
                if ($this->forma_pago !== 'GRT') {
                    $this->insertaDescuento($total_descuentos_con_iva, $iva);
                } else {
                    $this->insertaDescuentoParaGratuito($iva);
                }
            }
        } else {
            $this->insertaDescuento($total_descuentos_con_iva, 0);
        }
    }

    /**
     * Incorpora los productos de descuento en AQUA para simular los descuentos de PrestaShop
     */
    private function insertaDescuento($total_descuentos_con_iva, $tipo): void
    {
        switch ($tipo) {
            // para cada tipo de IVA se calcula el porcentaje que tiene con respecto al total de pedido
            // y así se saca el descuento ed cada IVA
            case 10:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_10 / $this->total_con_iva)) / 1.10, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_10 / $this->total_con_iva));
                $tipo_recargo = $this->recargo ? 1.4 : 0;
                break;
            case 21:
                $codigo = self::DESCUENTO21_COD;
                $nombre = self::DESCUENTO21_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_21 / $this->total_con_iva)) / 1.21, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_21 / $this->total_con_iva));
                $tipo_recargo = $this->recargo ? 5.2 : 0;
                break;
            case 20:
                $codigo = self::DESCUENTO20_COD;
                $nombre = self::DESCUENTO20_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_20 / $this->total_con_iva)) / 1.20, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_20 / $this->total_con_iva));
                $tipo_recargo = 0;
                break;
            case 22:
                $codigo = self::DESCUENTO22_COD;
                $nombre = self::DESCUENTO22_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_22 / $this->total_con_iva)) / 1.22, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_22 / $this->total_con_iva));
                $tipo_recargo = 0;
                break;
            case 23:
                $codigo = self::DESCUENTO23_COD;
                $nombre = self::DESCUENTO23_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_23 / $this->total_con_iva)) / 1.23, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_23 / $this->total_con_iva));
                $tipo_recargo = 0;
                break;
            case 4:
                $codigo = self::DESCUENTO4_COD;
                $nombre = self::DESCUENTO4_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_4 / $this->total_con_iva)) / 1.04, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_4 / $this->total_con_iva));
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 6:
                $codigo = self::DESCUENTO6_COD;
                $nombre = self::DESCUENTO6_NOMBRE;
                $descuento = round(($total_descuentos_con_iva * ($this->total_iva_6 / $this->total_con_iva)) / 1.06, 6);
                $descuento_con_iva = ($total_descuentos_con_iva * ($this->total_iva_6 / $this->total_con_iva));
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 0:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $descuento_con_iva = $descuento = $total_descuentos_con_iva;
                $tipo_recargo = 0;
                break;
        }

        if ($descuento > 0) {
            // ahora los vales dto que meten un producto de regalo, ya meten un descuento (del iva correspondiente al del producto que se regala) antes de ejecutar este método, por lo tanto en el array de productos ya existe un descuento con el codigo que corresponda, si existe se acumula
            $descuentoIntroducidoAnteriormente = false;
            foreach ($this->productos as &$producto) {
                if ($producto['codigo'] == $codigo) {
                    $producto['importe'] -= $descuento;
                    $producto['totimporte'] -= $descuento;
                    $producto['tarifa'] -= $descuento;
                    $descuentoIntroducidoAnteriormente = true;
                    break;
                }
            }

            // si no se ha encontrado ningún descuento en el array se mete
            if (!$descuentoIntroducidoAnteriormente) {
                $this->productos[] = array(
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'cantidad' => 1,
                    'descuento' => 0,
                    'importe' => -1 * $descuento,
                    'totimporte' => -1 * $descuento,
                    'tarifa' => -1 * $descuento,
                    'iva' => $tipo,
                    'recargo' => $tipo_recargo,
                );
            }

            $this->total_sin_iva -= $descuento;
            $this->total_iva -= $descuento_con_iva - $descuento;
            $this->total_servicios -= $descuento;
            $this->total_tarifa -= $descuento;
            $this->acumulaBase(-1 * $descuento, $tipo);

            if ($this->recargo) {
                $this->total_recargo -= $descuento * ($tipo_recargo / 100);
                $this->acumulaRecargos(-1 * $descuento, $tipo_recargo);
            }

            $this->lineas++;
            $this->unidades++;
        }
    }

    /**
     * Incorpora los productos de descuento en AQUA para simular los descuentos de PrestaShop
     */
    public function insertaDescuentoSinProratear($descuento_con_iva, $tipo): void
    {
        switch ($tipo) {
            // para cada tipo de IVA se calcula el porcentaje que tiene con respecto al total de pedido
            // y así se saca el descuento ed cada IVA
            case 10:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $tipo_recargo = $this->recargo ? 1.4 : 0;
                break;
            case 21:
                $codigo = self::DESCUENTO21_COD;
                $nombre = self::DESCUENTO21_NOMBRE;
                $tipo_recargo = $this->recargo ? 5.2 : 0;
                break;
            case 20:
                $codigo = self::DESCUENTO20_COD;
                $nombre = self::DESCUENTO20_NOMBRE;
                $tipo_recargo = 0;
                break;
            case 22:
                $codigo = self::DESCUENTO22_COD;
                $nombre = self::DESCUENTO22_NOMBRE;
                $tipo_recargo = 0;
                break;
            case 23:
                $codigo = self::DESCUENTO23_COD;
                $nombre = self::DESCUENTO23_NOMBRE;
                $tipo_recargo = 0;
                break;
            case 4:
                $codigo = self::DESCUENTO4_COD;
                $nombre = self::DESCUENTO4_NOMBRE;
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 6:
                $codigo = self::DESCUENTO6_COD;
                $nombre = self::DESCUENTO6_NOMBRE;
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 0:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $tipo_recargo = 0;
                break;
        }

        $descuento = round($descuento_con_iva / (1 + ($tipo / 100)), 6);

        if ($descuento > 0) {
            // ahora los vales dto que meten un producto de regalo, ya meten un descuento (del iva correspondiente al del producto que se regala) antes de ejecutar este método, por lo tanto en el array de productos ya existe un descuento con el codigo que corresponda, si existe se acumula
            $descuentoIntroducidoAnteriormente = false;
            foreach ($this->productos as &$producto) {
                if ($producto['codigo'] == $codigo) {
                    $producto['importe'] -= $descuento;
                    $producto['totimporte'] -= $descuento;
                    $producto['tarifa'] -= $descuento;
                    $descuentoIntroducidoAnteriormente = true;
                    break;
                }
            }

            // si no se ha encontrado ningún descuento en el array se mete
            if (!$descuentoIntroducidoAnteriormente) {
                $this->productos[] = array(
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'cantidad' => 1,
                    'descuento' => 0,
                    'importe' => -1 * $descuento,
                    'totimporte' => -1 * $descuento,
                    'tarifa' => -1 * $descuento,
                    'iva' => $tipo,
                    'recargo' => $tipo_recargo,
                );
            }

            $this->total_sin_iva -= $descuento;
            $this->total_iva -= $descuento_con_iva - $descuento;
            $this->total_servicios -= $descuento;
            $this->total_tarifa -= $descuento;
            $this->acumulaBase(-1 * $descuento, $tipo);

            if ($this->recargo) {
                $this->total_recargo -= $descuento * ($tipo_recargo / 100);
                $this->acumulaRecargos(-1 * $descuento, $tipo_recargo);
            }

            $this->lineas++;
            $this->unidades++;
        }
    }

    public function insertaDescuentoPortes($total_con_iva): void
    {
        $iva = $this->exento_iva ? 0 : $this->vendedor->getShippingTaxRate();

        $descuento = -1 * round($total_con_iva / (1 + ($iva / 100)), 6);
        $pc_recargo = $this->recargo ? 5.2 : 0;
        $this->productos[] = array(
            'codigo' => self::DESCUENTO_PORTES_COD,
            'nombre' => self::DESCUENTO_PORTES_NOMBRE,
            'cantidad' => 1,
            'descuento' => 0,
            'importe' => $descuento,
            'totimporte' => $descuento,
            'tarifa' => $descuento,
            'iva' => $iva,
            'recargo' => $pc_recargo,
        );

        $this->total_sin_iva += $descuento;
        $this->total_servicios += $descuento;
        $this->total_tarifa += $descuento;
        $this->total_iva -= $total_con_iva - ($descuento * -1);
        $this->acumulaBase($descuento, $iva);

        if ($this->recargo) {
            $this->total_recargo += ($descuento) * (5.2 / 100);
            $this->acumulaRecargos($descuento, 5.2);
        }

        $this->lineas++;
        $this->unidades++;
    }

    /**
     * Cuando es un pedido gratuito, para cuadrar el resultado a 0 exacto, los descuentos tiene que ser exactamente igual que la base de cada iva
     * si no se hace así, al sacar el descuento en proporción al total con muchos decimales se da el caso de que sea 0.0003 en lugar de 0
     */
    private function insertaDescuentoParaGratuito($tipo_iva)
    {
        switch ($tipo_iva) {
            case 10:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $descuento = $this->base_iva_10;
                $tipo_recargo = $this->recargo ? 1.4 : 0;
                break;
            case 21:
                $codigo = self::DESCUENTO21_COD;
                $nombre = self::DESCUENTO21_NOMBRE;
                // el descuento no puede ser igual que la base al 21% completa, hay también van los portes, y los portes ya se meten como otro descuento,
                // por lo tanto, el descuento al 21% será la base al 21% menos la base del transporte
                /**
                 * fix 03/11/2021 -jbajo
                 *
                 * al insertar el descuento de portes antes de ejecutar este método, en la base2 ya está restado el descuento, se resta 2 veces
                 * $descuento    = $this->base_iva_21 - ($this->total_portes_con_iva / 1.21);
                 */
                $descuento = $this->base_iva_21;
                $tipo_recargo = $this->recargo ? 5.2 : 0;
                break;
            case 20:
                $codigo = self::DESCUENTO20_COD;
                $nombre = self::DESCUENTO20_NOMBRE;
                // el descuento no puede ser igual que la base al 21% completa, hay también van los portes, y los portes ya se meten como otro descuento
                // por lo tanto el descuento al 21% será la base al 21% menos la base del transporte
                $descuento = $this->base_iva_20;
                $tipo_recargo = 0;
                break;
            case 22:
                $codigo = self::DESCUENTO22_COD;
                $nombre = self::DESCUENTO22_NOMBRE;
                // el descuento no puede ser igual que la base al 21% completa, hay también van los portes, y los portes ya se meten como otro descuento
                // por lo tanto el descuento al 21% será la base al 21% menos la base del transporte
                $descuento = $this->base_iva_22;
                $tipo_recargo = 0;
                break;
            case 23:
                $codigo = self::DESCUENTO23_COD;
                $nombre = self::DESCUENTO23_NOMBRE;
                // el descuento no puede ser igual que la base al 21% completa, hay también van los portes, y los portes ya se meten como otro descuento
                // por lo tanto el descuento al 21% será la base al 21% menos la base del transporte
                $descuento = $this->base_iva_23;
                $tipo_recargo = 0;
                break;
            case 4:
                $codigo = self::DESCUENTO4_COD;
                $nombre = self::DESCUENTO4_NOMBRE;
                $descuento = $this->base_iva_4;
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 6:
                $codigo = self::DESCUENTO6_COD;
                $nombre = self::DESCUENTO6_NOMBRE;
                $descuento = $this->base_iva_6;
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 0:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $descuento_con_iva = $descuento = $this->total_sin_iva;
                $tipo_recargo = 0;
                break;
        }

        if (round($descuento, 6) > 0) {
            $this->productos[] = array(
                'codigo' => $codigo,
                'nombre' => $nombre,
                'cantidad' => 1,
                'descuento' => 0,
                'importe' => -1 * $descuento,
                'totimporte' => -1 * $descuento,
                'tarifa' => -1 * $descuento,
                'iva' => $tipo_iva,
                'recargo' => $tipo_recargo,
            );

            $this->total_sin_iva -= $descuento;
            // en los gratuitos como el descuento es igual que la base, la cantidad de iva también será igual que el iva de la base, el iva final 0
            $this->total_iva = 0;
            $this->total_servicios -= $descuento;
            $this->total_tarifa -= $descuento;
            $this->acumulaBase(-1 * $descuento, $tipo_iva);

            $this->lineas++;
            $this->unidades++;
        }
    }

    // mete el total del producto como descuentos (en funcion del iva usará lo acumulará en un descuento u otro)
    public function insertarDescuentoParaProducto($id_product, $precioProductoConIva, $tienda)
    {
        $tipo = (float)Db::getInstance()->getValue(
            "SELECT trg.alias
                from ps_product_shop ps
                inner join ps_tax_rules_group trg on trg.id_tax_rules_group = ps.id_tax_rules_group
                where ps.id_shop = {$tienda} and ps.id_product = {$id_product}");

        switch ($tipo) {
            // para cada tipo de IVA se calcula el porcentaje que tiene con respecto al total de pedido
            // y así se saca el descuento ed cada IVA
            case 10:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $descuento = round($precioProductoConIva / 1.10, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = $this->recargo ? 1.4 : 0;
                break;
            case 21:
                $codigo = self::DESCUENTO21_COD;
                $nombre = self::DESCUENTO21_NOMBRE;
                $descuento = round($precioProductoConIva / 1.21, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = $this->recargo ? 5.2 : 0;
                break;
            case 20:
                $codigo = self::DESCUENTO20_COD;
                $nombre = self::DESCUENTO20_NOMBRE;
                $descuento = round($precioProductoConIva / 1.20, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = 0;
                break;
            case 22:
                $codigo = self::DESCUENTO22_COD;
                $nombre = self::DESCUENTO22_NOMBRE;
                $descuento = round($precioProductoConIva / 1.22, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = 0;
                break;
            case 23:
                $codigo = self::DESCUENTO23_COD;
                $nombre = self::DESCUENTO23_NOMBRE;
                $descuento = round($precioProductoConIva / 1.23, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = 0;
                break;
            case 4:
                $codigo = self::DESCUENTO4_COD;
                $nombre = self::DESCUENTO4_NOMBRE;
                $descuento = round($precioProductoConIva / 1.04, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 6:
                $codigo = self::DESCUENTO6_COD;
                $nombre = self::DESCUENTO6_NOMBRE;
                $descuento = round($precioProductoConIva / 1.06, 6);
                $descuento_con_iva = $precioProductoConIva;
                $tipo_recargo = $this->recargo ? 0.5 : 0;
                break;
            case 0:
                $codigo = self::DESCUENTO10_COD;
                $nombre = self::DESCUENTO10_NOMBRE;
                $descuento_con_iva = $descuento = $precioProductoConIva;
                $tipo_recargo = 0;
                break;
        }

        $this->productos[] = array(
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => 1,
            'descuento' => 0,
            'importe' => -1 * $descuento,
            'totimporte' => -1 * $descuento,
            'tarifa' => -1 * $descuento,
            'iva' => $tipo,
            'recargo' => $tipo_recargo,
        );


        $this->total_sin_iva -= $descuento;
        $this->total_iva -= $descuento_con_iva - $descuento;
        $this->total_servicios -= $descuento;
        $this->total_tarifa -= $descuento;
        $this->acumulaBase(-1 * $descuento, $tipo);

        if ($this->recargo) {
            $this->total_recargo -= $descuento * ($tipo_recargo / 100);
            $this->acumulaRecargos(-1 * $descuento, $tipo_recargo);
        }

        $this->lineas++;
        $this->unidades++;
    }

    private function acumulaBase($base, $iva): void
    {
        switch ($iva) {
            case 4:
                $this->base_iva_4 += $base;
                break;
            case 6:
                $this->base_iva_6 += $base;
                break;
            case 10:
                $this->base_iva_10 += $base;
                break;
            case 21:
                $this->base_iva_21 += $base;
                break;
            case 20:
                $this->base_iva_20 += $base;
                break;
            case 22:
                $this->base_iva_22 += $base;
                break;
            case 23:
                $this->base_iva_23 += $base;
                break;
        }
    }

    private function acumulaRecargos($base, $recargo): void
    {
        switch ($recargo) {
            case 0.5:
                $this->base_recargo_4 += $base;
                break;
            case 5.2:
                $this->base_recargo_21 += $base;
                break;
            case 1.4:
                $this->base_recargo_10 += $base;
                break;
        }
    }

    private function acumulaTotales($precio, $iva): void
    {
        switch ($iva) {
            case 10:
                $this->total_iva_10 += $precio;
                break;
            case 21:
                $this->total_iva_21 += $precio;
                break;
            case 4:
                $this->total_iva_4 += $precio;
                break;
            case 6:
                $this->total_iva_6 += $precio;
                break;
            case 20:
                $this->total_iva_20 += $precio;
                break;
            case 22:
                $this->total_iva_22 += $precio;
                break;
            case 23:
                $this->total_iva_23 += $precio;
                break;
        }

        $this->total_con_iva += $precio;
    }

    /**
     * Obtiene el vendedor en función de la tienda, el cliente y el módulo
     */
    public function loadVendor(int $shop, int $customer, string $module, AquaCarrier $carrier): AquaVendor
    {
        return match (true) {
            $shop === 2 => AquaVendor::WECPT,
            $shop === 3 => AquaVendor::WECIT,
            $module === 'clickcanarias' => AquaVendor::CLICK_CANARIAS,
            $carrier->value === 209 => AquaVendor::AMAZON_PRIME,
            AquaVendor::tryFromCustomerId($customer) !== null => AquaVendor::tryFromCustomerId($customer),
            default => AquaVendor::ESP,
        };
    }

    /**
     * Obtiene la forma de pago equivalente en aqua a la del pedido
     */
    public function getFormaPagoBy(string $module, string $pago, int $tienda): string
    {
        $AMA = array('AMAZON');
        $CAR = array('CARREFOUR');
        $MAS = array('MASCOTEROS');
        $CON = array('ps_checkpayment', 'cheque');
        $CRM = array('cashondelivery', 'codfee', 'cashondeliveryplus', 'kpycashondelivery');
        $GRT = array('Pedido gratuito', 'Encomenda grátis');
        $HPY = array('hipay', 'comprafacil', 'wfxcomprafacil');
        $PAY = array('Paypal', 'PayPal', 'paypal', 'ps_checkout');
        $TPV = array('redsysoficial', 'redsys', 'bbva', 'stripe_official');
        $TRA = array('bankwire', 'ps_wirepayment');
        $MAN = array('MANOMANO');
        $BV = ['BULEVIP'];
        $ALI = ['ALIEXPRESS'];

        return match (true) {
            in_array($module, $TPV) => 'TPV',
            in_array($module, $CRM) => 'CRM',
            in_array($module, $TRA) && $tienda === 1 => 'TRA',
            in_array($module, $TRA) && $tienda === 2 => 'TRT',
            in_array($module, $PAY) || $pago === 'EBAY' => 'PAY',
            $module === 'free_order' || in_array($pago, $GRT) => 'GRT',
            in_array($module, $CON) => 'KC1', //se pone la forma de pago al contado de kompy que será donde se pague
            in_array($module, $HPY) => 'HPY',
            $module === 'clickcanarias' => 'CKC',
            in_array($pago, $AMA) => 'AMA',
            in_array($pago, $CAR) => 'CAR',
            in_array($pago, $MAN) => 'MAN',
            in_array($pago, $BV) => 'BV',
            in_array($pago, $ALI) => 'ALI',
            in_array($pago, $MAS) => 'MAS',
            default => 'CON', // si no se encuentra se pone como contado
        };
    }

    private function getArrayWithAquaInfoPayment(string $formaPago): array
    {
        $sql = "SELECT Isnull(DATFP01.CUENTA,'') AS CUENTA, IsNull(DATFP01.TIPOEFECTO,'') AS EFECTO
                FROM DATFP01 WITH (NOLOCK)
                WHERE DATFP01.NUMERO='{$formaPago}'";

        $result = $this->aqua->getRow($sql);

        return array('ncuenta' => $result['CUENTA'], 'nefecto' => $result['EFECTO']);
    }

    public function getIdOrder(): int
    {
        return $this->id_order;
    }

    public function getIdCustomer(): int
    {
        return $this->id_customer;
    }

    public function getIdAddress(): string
    {
        return $this->id_address;
    }

    public function getRecargo(): float
    {
        return $this->recargo;
    }

    public function isExentoIva(): bool
    {
        return $this->exento_iva;
    }

    public function getCarrier(): AquaCarrier
    {
        return $this->carrier;
    }

    public function getTotalConIva(): float
    {
        return $this->total_con_iva;
    }

    public function getTotalSinIva(): float
    {
        return $this->total_sin_iva;
    }

    public function getTotalIva(): float
    {
        return $this->total_iva;
    }

    public function getProductos(): array
    {
        return $this->productos;
    }

    public function getFormaPago(): string
    {
        return $this->forma_pago;
    }

    public function getNcuenta(): string
    {
        return $this->ncuenta;
    }

    public function getNefecto(): string
    {
        return $this->nefecto;
    }

    public function getTotalArticulos(): float
    {
        return $this->total_articulos;
    }

    public function getTotalServicios(): float
    {
        return $this->total_servicios;
    }

    public function getTotalTarifa(): float
    {
        return $this->total_tarifa;
    }

    public function getTotalRecargo(): float
    {
        return $this->total_recargo;
    }

    public function getBaseRecargo4(): float
    {
        return $this->base_recargo_4;
    }

    public function getBaseRecargo10(): float
    {
        return $this->base_recargo_10;
    }

    public function getBaseRecargo21(): float
    {
        return $this->base_recargo_21;
    }

    public function getBaseIva4(): float
    {
        return $this->base_iva_4;
    }

    public function getBaseIva6(): float
    {
        return $this->base_iva_6;
    }

    public function getBaseIva10(): float
    {
        return $this->base_iva_10;
    }

    public function getBaseIva21(): float
    {
        return $this->base_iva_21;
    }

    public function getBaseIva20(): float
    {
        return $this->base_iva_20;
    }

    public function getBaseIva22(): float
    {
        return $this->base_iva_22;
    }

    public function getBaseIva23(): float
    {
        return $this->base_iva_23;
    }

    public function getTotalIva4(): float
    {
        return $this->total_iva_4;
    }

    public function getTotalIva6(): float
    {
        return $this->total_iva_6;
    }

    public function getTotalIva10(): float
    {
        return $this->total_iva_10;
    }

    public function getTotalIva21(): float
    {
        return $this->total_iva_21;
    }

    public function getTotalIva20(): float
    {
        return $this->total_iva_20;
    }

    public function getTotalIva22(): float
    {
        return $this->total_iva_22;
    }

    public function getTotalIva23(): float
    {
        return $this->total_iva_23;
    }

    public function getFechaPedido(): string
    {
        return $this->fecha_pedido;
    }

    public function getHoraPedido(): string
    {
        return $this->hora_pedido;
    }

    public function getFechaFacturacion(): string
    {
        return $this->fecha_facturacion;
    }

    public function getFechaPedidoHistoria(): string
    {
        return $this->fecha_pedido_historia;
    }

    public function getObservaciones(): string
    {
        return $this->observaciones;
    }

    public function getLineas(): int
    {
        return $this->lineas;
    }

    public function getUnidades(): int
    {
        return $this->unidades;
    }

    public function getPeso(): float
    {
        return $this->peso;
    }

    public function getVolumen(): float
    {
        return $this->volumen;
    }

    public function getSinFactura(): int
    {
        return $this->sinFactura;
    }

    public function getVendor(): AquaVendor
    {
        return $this->vendedor;
    }

    public function setObservaciones(string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }
}
