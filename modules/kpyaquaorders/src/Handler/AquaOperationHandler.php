<?php

namespace PrestaShop\Module\KpyAquaOrders\Handler;

use Address;
use Db;
use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaVendor;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Entity\OrderAqua;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use PrestaShop\Module\KpyAquaOrders\Service\Checker;
use PrestaShop\Module\KpyAquaOrders\Service\Finder;
use PrestaShop\Module\KpyAquaOrders\Service\Mailer;
use PrestaShop\Module\KpyAquaOrders\Warehouse\GiftsWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Type\Discount;
use Product;

class AquaOperationHandler
{
    private AquaOrderController $aquaOrderController;

    private GiftsWarehouse $giftsWarehouse;

    private Finder $finder;

    public function __construct(private readonly DbMssql $aqua)
    {
        $this->aquaOrderController = new AquaOrderController($this->aqua);
        $this->giftsWarehouse = new GiftsWarehouse();
        $this->finder = new Finder($this->aqua);
    }

    /**
     * Crea el pedido en AQUA, los movimientos y la operación, y después se ejecuta la reserva de mercancía
     *
     * @throws KpyAquaOrderException|KpyAquaSqlException|\PrestaShopException
     */
    public function crearOperacion(Order $order, bool $new = false): bool
    {
        if ($this->esNecesarioDividir($order)) {
            $pedidos = $this->obtienePedidosDivididos($order);

            $resultado = true;
            $primerPedidoCreado = false;

            $idsPedidosRelacionados = [];

            foreach ($pedidos as $pedido) {
                // El primer pedido se crea normalmente (con dtos y portes si los tiene) pero sólo con los productos del primer pedido resultante de dividir el original
                if (!$primerPedidoCreado) {
                    $resultado &= $this->exportOrder($order, $new, $pedido['productos']);

                    $idsPedidosRelacionados[] = $order->id;

                    $primerPedidoCreado = true;
                    continue;
                }

                $orderAqua = new OrderAqua($order, $this->aqua);

                foreach ($pedido['productos'] as $producto) {
                    $orderAqua->addProduct(
                        $producto['product_id'],
                        $producto['product_attribute_id'],
                        $producto['product_quantity'],
                        $producto['total_price_tax_excl'],
                        $producto['total_price_tax_incl'],
                        $producto['reduction_percent'],
                        $producto['tax_rate']
                    );
                }

                $orderAqua->cambiarIdOrder($pedido['id']);

                $resultado &= $this->aquaOrderController->export($orderAqua, true);

                $idsPedidosRelacionados[] = $pedido['id'];
            }

            $this->aquaOrderController->bloqueaPedidosRelacionadosSiEsNecesario($idsPedidosRelacionados);

            if ($new && count($pedidos) < 2) {
                $mailer = new Mailer();
                $mailer->sendMailError(
                    'Nuevo pedido dividido, ' . $order->id,
                    'Se ha dividido en ' . count($pedidos) . ' pedidos<br /><a href="https://atc.piensoymascotas.com/order/' . $order->id . '">' . $order->id . '</a>',
                    $order->id
                );
            }

            return $resultado;
        }

        return $this->exportOrder($order, $new);
    }

    private function esNecesarioDividir(Order $order): bool
    {
        return false;

        $pesoPedido = $this->finder->getOrderWeight($order->id);

        $id_reference = $this->finder->getIdReferenceByCarrierId($order->id_carrier);

        $deliveryAddress = Address::initialize($order->id_address_delivery);

        $limiteDePeso = $this->limitePesoParaEnvioPorIdRereference($id_reference, $deliveryAddress->postcode);

        // GLS IT, SEUR IT
        return ($order->id_shop == 3 && in_array($id_reference, [207, 221, 262,]) && $pesoPedido >= $limiteDePeso)
            // SEUR internacional
            || (187 == $id_reference && $pesoPedido >= $limiteDePeso)
            // DHL
            || (188 == $id_reference && $pesoPedido >= $limiteDePeso)
            // DHL PT
            || (255 == $id_reference && $pesoPedido >= $limiteDePeso)
            // Correos
            || (268 == $id_reference && $pesoPedido >= $limiteDePeso)
            // MRW
            || (178 == $id_reference && $pesoPedido >= $limiteDePeso)
            // GLS IT
            || (207 == $id_reference && $pesoPedido >= $limiteDePeso)
            // GLS ESP
            || (251 == $id_reference && $pesoPedido >= $limiteDePeso)
            // OnTime Baleares
            || (280 == $id_reference && $pesoPedido > $limiteDePeso);
    }


    private function limitePesoParaEnvioPorIdRereference($id_reference, $postcode = null): float
    {
        if ($postcode != null && $this->context->shop->id == 1) {
            $limitsByPostcode = [
                // Baleares
                '07' => [
                    178 => 36, // MRW
                    280 => 50,
                ],
            ];

            $beginning = substr($postcode, 0, 2);
            if (isset($limitsByPostcode[$beginning][$id_reference])) {
                return $limitsByPostcode[$beginning][$id_reference];
            }
        }

        $limitsByCarrier = [
            187 => 30,
            221 => 30,
            207 => 30, // GLS IT
            251 => 30, // GLS ESP
            188 => 29, // DHL
            255 => 29, // DHL PT
            262 => 29, // DHL IT
            268 => 29, // correos
            178 => 78,
        ];

        return $limitsByCarrier[$id_reference] ?? 999;
    }

    private function obtienePedidosDivididos($order): array
    {
        $pedidos = [];

        $productos = $this->getProductosPedidoSinPacks($order->id, $order->id_shop);
        $deliveryAddress = Address::initialize($order->id_address_delivery);
        $limitePeso = $this->limitePesoParaEnvioPorIdRereference(
            $this->finder->getIdReferenceByCarrierId($order->id_carrier),
            $deliveryAddress->postcode
        );

        $pedido = [
            'id' => $order->id,
            'peso' => 0,
            'productos' => []
        ];
        $productosRestantes = count($productos);

        foreach ($productos as $producto) {
            // suma el peso del producto que se metera en el pedido, si supera el límite se meterá en un pedido nuevo
            $pedido['peso'] += $producto['peso'];
            $sku = $producto['product_id'] . '-' . $producto['product_attribute_id'];

            // si el peso del pedido superaría los 30 kg al añadir el producto actual
            if ($pedido['peso'] >= $limitePeso) {
                // se le resta el peso que se sumó antes de comprobar si cabía o no
                $pedido['peso'] -= $producto['peso'];
                // añade el nuevo pedido a la lista
                $pedidos[] = $pedido;
                // crea uno nuevo para el producto
                $pedido = [
                    'id' => (string)$order->id . str_pad(count($pedidos), 2, '0', STR_PAD_LEFT),
                    'peso' => $producto['peso'], // el peso inicial del pedido es el del producto que se meterá
                    'productos' => []
                ];
            }

            if (!array_key_exists($sku, $pedido['productos'])) {
                $pedido['productos'][$sku] = [
                    'product_id' => $producto['product_id'],
                    'product_attribute_id' => $producto['product_attribute_id'],
                    'product_quantity' => $producto['product_quantity'],
                    'total_price_tax_excl' => $producto['total_price_tax_excl'],
                    'total_price_tax_incl' => $producto['total_price_tax_incl'],
                    'reduction_percent' => $producto['reduction_percent'],
                    'tax_rate' => $producto['tax_rate']
                ];
            } else {
                $pedido['productos'][$sku]['product_quantity'] += $producto['product_quantity'];
                $pedido['productos'][$sku]['total_price_tax_excl'] += $producto['total_price_tax_excl'];
                $pedido['productos'][$sku]['total_price_tax_incl'] += $producto['total_price_tax_incl'];
            }

            $productosRestantes--;

            // si ya no quedan mas productos se mete el pedido en la lista tal y como esté
            if ($productosRestantes === 0) {
                $pedidos[] = $pedido;
            }
        }

        return $pedidos;
    }

    /**
     * Crea el pedido en AQUA, los movimientos y la operación, y después se ejecuta la reserva de mercancía y todo lo demas que hacia el bot
     * $new, indica si un pedido es nuevo o no
     *
     * @throws KpyAquaSqlException|KpyAquaOrderException|\PrestaShopException
     */
    private function exportOrder(Order $order, $new = false, array $productos = []): bool
    {
        $orderAqua = new OrderAqua($order, $this->aqua);

        if (empty($productos)) {
            $productos = $order->getProducts();
        }

        $iva = [1 => 10, 2 => 23, 3 => 22];
        $muestrasAgregadas = [];
        $latasRegaloAgregadas = [];
        $flayersAgregados = [];

        foreach ($productos as $producto) {
            $multipack = Product::getProductsInMultipack($producto['product_id'], $producto['product_attribute_id'], $order->id_shop);

            if (empty($multipack)) {
                $orderAqua->addProduct(
                    $producto['product_id'],
                    $producto['product_attribute_id'],
                    $producto['product_quantity'],
                    $producto['total_price_tax_excl'],
                    $producto['total_price_tax_incl'],
                    $producto['reduction_percent'],
                    $producto['tax_rate']
                );

                /*if ($new) {
                    // el mensaje sólo se guarda cuando se hace el pedido, no se guarda cuando se modifica
                    $orderAqua->guardarFechaEstimadaDelMensajeSiExiste($producto['product_id'],
                        $producto['product_attribute_id'], $producto['product_quantity']);
                }*/

                /*$lataRegalo = $this->productoConLataDeRegalo($producto['product_id'] . '-' . $producto['product_attribute_id']);
                if ($lataRegalo != '' && !in_array($lataRegalo, $latasRegaloAgregadas)) {
                    list($idLata, $attrLata) = explode('-', $lataRegalo);
                    $orderAqua->insertaProducto(
                        $idLata,
                        $attrLata,
                        ($lataRegalo == '8429-92720' ? 2 : 1),
                        0,
                        0,
                        0,
                        $iva[(int) $order->id_shop]
                    );
                    $latasRegaloAgregadas[] = $lataRegalo;
                }*/

                if ($order->id_shop == 1) {
                    // los flayer solo se meten en pym
                    $skuFlayer = $this->productoConFlayers((int)$producto['product_id']);
                    if ($skuFlayer != ''
                        && !in_array($skuFlayer, $flayersAgregados)
                        && $this->aquaOrderController->numberOfTimesProductAppearsInCustomerOrders($skuFlayer, $order->id_customer) < 3
                    ) {
                        [$idFlayer, $attrFlayer] = explode('-', $skuFlayer);
                        $orderAqua->addProduct(
                            $idFlayer,
                            $attrFlayer,
                            1,
                            0,
                            0,
                            0,
                            $iva[(int)$order->id_shop]
                        );
                        $flayersAgregados[] = $skuFlayer;
                    }

                    /*if (!in_array('8756-93227', $flayersAgregados)
                        && $this->esPrimeraCompraDeProductosRCSeleccionadosParaCliente($order->id_customer)
                    ) {
                        $orderAqua->insertaProducto(
                            '8756',
                            '93227',
                            1,
                            0,
                            0,
                            0,
                            $iva[(int) $order->id_shop]
                        );
                        $flayersAgregados[] = '8756-93227';
                    }*/
                } else {
                    $skuMuestra = $this->productoConMuestraBoske((int)$producto['product_id']);
                    if ($skuMuestra != ''
                        && !in_array($skuMuestra, $muestrasAgregadas)
                        && $this->aquaOrderController->numberOfTimesProductAppearsInCustomerOrders($skuMuestra, $order->id_customer) < 3
                    ) {
                        list($idMuestra, $attrMuestra) = explode('-', $skuMuestra);
                        $orderAqua->addProduct(
                            $idMuestra,
                            $attrMuestra,
                            1,
                            0,
                            0,
                            0,
                            $iva[(int)$order->id_shop]
                        );
                        $muestrasAgregadas[] = $skuMuestra;
                    }
                }

            } else {
                foreach ($multipack as $item) {
                    $descuento = round(100 - ($item['price'] / $item['pvr'] * 100), 2);
                    $orderAqua->addProduct(
                        $item['id_product'],
                        $item['id_product_attribute'],
                        1 * $producto['product_quantity'],
                        round(($item['price'] * $producto['product_quantity']) / (1 + ($item['iva'] / 100)), 6),
                        $item['price'] * $producto['product_quantity'],
                        $descuento,
                        $item['iva']
                    );
                }
            }
        }

        if ((int)$order->id_shop == 1) {
            $skuPegatina = $this->obtieneSKUPegatinaSiCorresponde($order->id_customer, $order->id);
            if ($skuPegatina !== '') {
                [$idPegatina, $attrPegatina] = explode('-', $skuPegatina);
                $orderAqua->addProduct(
                    $idPegatina,
                    $attrPegatina,
                    1,
                    0,
                    0,
                    0,
                    $iva[(int)$order->id_shop]
                );
            }
        }

        if ($order->total_shipping > 0) {
            // para no perder decimales, si siempre se pasara el campo con los portes sin iva se perdería precision, sólo se pasa así cuando el pedido no lleva iva

            $iva = 1 + (AquaVendor::tryFromShop($order->id_shop)->getShippingTaxRate() / 100);

            $portes = (($order->total_paid_tax_incl !== $order->total_paid_tax_excl) || $order->module === 'free_order')
                ? round($order->total_shipping / $iva, 6)
                : $order->total_shipping_tax_excl;

            $orderAqua->insertaPortes($portes);
        }


        $orderDiscounts = $this->finder->getOrderDiscounts($order->id);

        if (!empty($orderDiscounts) && $order->module !== 'free_order') {
            foreach ($orderDiscounts as $discount) {
                if ($discount['free_shipping'] === 1 && $order->total_shipping > 0) { // descuento portes
                    $orderAqua->insertaDescuentoPortes($discount['descuento']);

                } else if ($discount['product_restriction'] === 1) {
                    // los descuentos pueden ser para determinados productos o para todos los productos de un fabricante
                    $tipoDeDescuento = $this->finder->getDiscountType($order->id);

                    if (Discount::NONE === $tipoDeDescuento) {
                        continue;
                    }

                    $ivaDescuento = Db::getInstance()->getValue($tipoDeDescuento->buildQuery($order->id, $order->id_shop));

                    if ($ivaDescuento === false) {
                        continue;
                    }

                    // si el cliente es exento de impuestos
                    if ($order->total_paid_tax_incl === $order->total_paid_tax_excl) {
                        // en la tabla de los descuentos, siempre el descuento va con iva...
                        $orderAqua->insertaDescuentoSinProratear($discount['descuento'] / (1 + ((int)$ivaDescuento / 100)), 0);
                    } else {
                        $orderAqua->insertaDescuentoSinProratear($discount['descuento'], (int)$ivaDescuento);
                    }

                } else if ($discount['gift_product'] !== 0) {
                    // vale descuento para un producto de regalo (al canjear los puntos por ejemplo)
                    $orderAqua->insertarDescuentoParaProducto($discount['gift_product'], $discount['descuento'], $order->id_shop);

                } else { // descuento productos
                    if ($order->total_paid_tax_incl == $order->total_paid_tax_excl) {
                        // para los clientes exentos de IVA el descuento se coje del pedido (que va sin iva), en la
                        // tabla de descuentos va con IVA....
                        $orderAqua->insertaDescuentos($order->total_discounts);
                    } else {
                        $orderAqua->insertaDescuentos($discount['descuento']);
                    }
                }
            }
        } else if ($order->module === 'free_order') {
            // si se crea pedido gratuito sin vales de descuento, creo descuentos con el total_discounts del pedido

            // si el pedido tiene portes, inserto descuento de portes
            if ($order->total_shipping > 0) {
                $orderAqua->insertaDescuentoPortes($order->total_shipping);
            }
            // inserto descuento de productos menos los portes
            $orderAqua->insertaDescuentos($order->total_discounts - $order->total_shipping);
        }

        $orderChecked = new Checker($this->aqua);

        $exporta = $this->aquaOrderController->export($orderAqua);

        // los pedidos que se dividen no se comprueban porque descuadrán siempre
        if ($exporta && $order->id_customer !== 4 && !$this->esNecesarioDividir($order)) {
            $orderChecked->checkOrder($order->id, $order->id_customer);
        }

        // $orderAqua->guardarEstadoStockPedido();

        // el trigger de Aqua no marca los productos al hacer un pedido, al hacerse al insertar en DATOP cuando se
        // lanza el trigger todavía no existe ningún movimiento porque las líneas se insertan después
        // $this->marcaProductosComoPendientesDeSincronizar($order->id);

        return $exporta;

    }

    /**
     * Devuelve un array con todos los productos del pedido descompuestos uno por uno
     */
    private function getProductosPedidoSinPacks(int $id_order, int $id_shop = 1): array
    {
        $sqlProductosPedido = "SELECT od.product_id, od.product_attribute_id, od.product_quantity, od.total_price_tax_excl,
                                od.total_price_tax_incl, ifnull(rg.alias,0) as tax_rate, od.reduction_percent, od.product_weight as peso
                               from " . _DB_PREFIX_ . "order_detail od
                               left join " . _DB_PREFIX_ . "tax_rules_group rg on rg.id_tax_rules_group = od.id_tax_rules_group
                               WHERE od.id_order=" . $id_order;

        $productos = Db::getInstance()->executeS($sqlProductosPedido);
        $productosSinPacks = [];

        foreach ($productos as $productoPedido) {
            if (($productosEnPack = Product::getProductsInPack($productoPedido['product_id'] . '-' . $productoPedido['product_attribute_id']))) {
                foreach ($productosEnPack as $productoEnPack) {
                    $cantidadTotal = $productoEnPack['quantity'] * $productoPedido['product_quantity'];
                    $pesoTotal = $productoPedido['product_quantity'] * $productoPedido['peso'];
                    [$id, $attr] = explode('-', $productoEnPack['sku']);

                    for ($i = 0; $i < $cantidadTotal; $i++) {
                        $productosSinPacks[] = [
                            'product_id' => $id,
                            'product_attribute_id' => $attr,
                            'product_quantity' => 1,
                            'total_price_tax_excl' => $productoEnPack['is_gift'] == 0 ? round($productoPedido['total_price_tax_excl'] / $cantidadTotal, 6) : 0,
                            'total_price_tax_incl' => $productoEnPack['is_gift'] == 0 ? round($productoPedido['total_price_tax_incl'] / $cantidadTotal, 6) : 0,
                            'reduction_percent' => $productoPedido['reduction_percent'],
                            'tax_rate' => $productoPedido['tax_rate'],
                            'peso' => Product::getWeight($id, $attr),
                            // los packs que están formados por más de un producto informaría a todos los productos del pack con el mismo peso
                            // 'peso' => round($pesoTotal/ $cantidadTotal, 2),
                        ];
                    }
                }
            } else if (!empty(($multipack = Product::getProductsInMultipack($productoPedido['product_id'], $productoPedido['product_attribute_id'], $id_shop)))) {
                foreach ($multipack as $item) {
                    $descuento = round(100 - ($item['price'] / $item['pvr'] * 100), 2);
                    $productosSinPacks[] = [
                        'product_id' => $item['id_product'],
                        'product_attribute_id' => $item['id_product_attribute'],
                        'product_quantity' => 1 * $productoPedido['product_quantity'],
                        'total_price_tax_excl' => round(($item['price'] * $productoPedido['product_quantity']) / (1 + ($item['iva'] / 100)), 6),
                        'total_price_tax_incl' => $item['price'] * $productoPedido['product_quantity'],
                        'reduction_percent' => $descuento,
                        'tax_rate' => $item['iva'],
                        'peso' => Product::getWeight($item['id_product'], $item['id_product_attribute']),
                    ];
                }

            } else {
                $cantidadTotal = $productoPedido['product_quantity'];
                for ($i = 0; $i < $cantidadTotal; $i++) {
                    $productosSinPacks[] = [
                        'product_id' => $productoPedido['product_id'],
                        'product_attribute_id' => $productoPedido['product_attribute_id'],
                        'product_quantity' => 1,
                        'total_price_tax_excl' => round($productoPedido['total_price_tax_excl'] / $cantidadTotal, 6),
                        'total_price_tax_incl' => round($productoPedido['total_price_tax_incl'] / $cantidadTotal, 6),
                        'reduction_percent' => $productoPedido['reduction_percent'],
                        'tax_rate' => $productoPedido['tax_rate'],
                        'peso' => (float)$productoPedido['peso'],
                    ];
                }
            }
        }

        return $productosSinPacks;
    }


    private function productoConMuestraBoske($idProducto): string
    {
        foreach ($this->giftsWarehouse->getAll() as $regalo) {
            if ($regalo['activo'] && in_array($idProducto, $regalo['productos'])) {
                return $regalo['muestra'];
            }
        }

        return '';
    }

    private function productoConLataDeRegalo($sku)
    {
        $latas = [
            // lenda
            '8429-92720' => ['4015-7686', '4015-7733', '4017-7690', '4017-7735', '4019-7692', '4019-7737', '4020-92138',
                '4020-92139', '4023-7696', '4023-7741', '4816-9169', '4816-9170', '4016-7688', '4016-7734', '4029-7711',
                '4029-7743', '4018-7697', '4018-7736'],
        ];

        $lata = '';
        foreach ($latas as $skuLata => $productos) {
            if (in_array($sku, $productos)) {
                $lata = $skuLata;
                break;
            }
        }

        return $lata;
    }

    private function productoConFlayers($idProd): string
    {
        foreach ($this->giftsWarehouse->getAll() as $regalo) {
            if ($regalo['activo'] && in_array($idProd, $regalo['productos'])) {
                return $regalo['flayer'];
            }
        }

        return '';
    }

    private function obtieneSKUPegatinaSiCorresponde($cliente, $pedido): string
    {
        return '';
        $configuracion = [
            //'8642-93128' => [77, 44, 107, 121, 173], // Digno, Picart, Ownat, Optima Nova, Natural Greatness
            //'8641-93127' => [78, 92],  // Natura Diet, Lenda
        ];

        foreach ($configuracion as $sku => $fabricantes) {
            foreach ($fabricantes as $fabricante) {
                // si el pedido actual no tiene un producto del fabricante se lo salta
                if (!$this->finder->pedidoConProductosDeFabricante($pedido, $fabricante)) {
                    continue;
                }

                $numeroDePedidos = $this->finder->numeroDePedidosDeFabricante($fabricante, $cliente);

                if ($numeroDePedidos > 0 && $numeroDePedidos <= 2) {
                    return $sku;
                }
            }
        }

        return '';
    }
}