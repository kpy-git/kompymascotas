<?php

include_once '/var/www/html/panel.piensoymascotas.com/pymservices/includes/dbMssql.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$aqua = DbPymMssql::getInstance();

print_r($aqua->consulta("select top 1 * from DATOP01 where TIPOOPER='C' order by NUMERO desc"));
echo $aqua->getSqlError();
phpinfo();
exit;

require $_SERVER['DOCUMENT_ROOT'] . '/config/config.inc.php';
require $_SERVER['DOCUMENT_ROOT'] . '/init.php';

if(!isset($_GET['pedido'])) {
    exit('Falta el parámetro pedido');
}

print_r(obtienePedidosDivididos($_GET['pedido']));

function obtienePedidosDivididos($order)
{
    $pedidos = [];

    $productos = getProductosPedidoSinPacks($order);
    //print_r($productos);
    $id_reference=(int) Db::getInstance()->getValue("SELECT id_reference FROM ps_carrier WHERE id_carrier=(select id_carrier from ps_orders where id_order={$order})");
    $limitePeso = limitePesoParaEnvioPorIdRereference($id_reference, '07');
    echo "limite[$id_reference]: $limitePeso\n";
    $pedido = [
        'id' => $order,
        'peso' => 0,
        'productos' => []
    ];
    $productosRestantes = count($productos);

    foreach ($productos as $producto) {
        // suma el peso del producto que se metera en el pedido, si supera el limite se metera en un pedido nuevo
        $pedido['peso'] += $producto['peso'];
        $sku = $producto['product_id'] . '-' . $producto['product_attribute_id'];

        // si el peso del pedido superaría los 30kg al añadir el producto actual
        if ($pedido['peso'] >= $limitePeso) {
            // se le resta el peso que se sumó antes de comprobar si cabía o no
            $pedido['peso'] -= $producto['peso'];
            // añade el nuevo pedido a la lista
            $pedidos[] = $pedido;
            // crea uno nuevo para el producto
            $pedido = [
                'id' => (string) $order . str_pad(count($pedidos), 2, '0', STR_PAD_LEFT),
                'peso' => $producto['peso'], // el peso inicial del pedido es el del producto que se metera
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
        if ($productosRestantes == 0) {
            $pedidos[] = $pedido;
        }
    }

    return $pedidos;
}
function getProductosPedidoSinPacks($id_order)
{
    $sqlProductosPedido = "SELECT od.product_id, od.product_attribute_id, od.product_quantity, od.total_price_tax_excl,
                            od.total_price_tax_incl, ifnull(rg.alias,0) as tax_rate, od.reduction_percent, od.product_weight as peso
                           from ps_order_detail od
                           left join ps_tax_rules_group rg on rg.id_tax_rules_group = od.id_tax_rules_group
                           WHERE od.id_order=" . $id_order;

    $productos = Db::getInstance()->executeS($sqlProductosPedido);
    $productosSinPacks = [];

    foreach ($productos as $productoPedido) {
        if (($productosEnPack = Product::getProductsInPack($productoPedido['product_id'] . '-' . $productoPedido['product_attribute_id']))) {
            foreach ($productosEnPack as $productoEnPack) {
                $cantidadTotal = $productoEnPack['quantity'] * $productoPedido['product_quantity'];
                $pesoTotal = $productoPedido['product_quantity'] * $productoPedido['peso'];
                list($id, $attr) = explode('-', $productoEnPack['sku']);

                for($i=0; $i<$cantidadTotal; $i++) {
                    $productosSinPacks[] = [
                        'product_id' => $id,
                        'product_attribute_id' => $attr,
                        'product_quantity' => 1,
                        'total_price_tax_excl' => round($productoPedido['total_price_tax_excl'] / $cantidadTotal, 6),
                        'total_price_tax_incl' => round($productoPedido['total_price_tax_incl'] / $cantidadTotal, 6),
                        'reduction_percent' => $productoEnPack['is_gift'] == 0 ? $productoPedido['reduction_percent'] : 100,
                        'tax_rate' => $productoPedido['tax_rate'],
                        //'peso' => round($pesoTotal/ $cantidadTotal, 2),
                        'peso' => Product::getWeightForSku($id, $attr),
                    ];
                }
            }
        } else {
            $cantidadTotal = $productoPedido['product_quantity'];
            for($i=0; $i<$cantidadTotal; $i++) {
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

function limitePesoParaEnvioPorIdRereference($id_reference, $postcode)
{
    if ($postcode != null) {
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