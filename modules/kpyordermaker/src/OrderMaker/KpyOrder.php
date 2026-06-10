<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyOrderMaker\OrderMaker;

use Address;
use Carrier;
use Cart;
use CartRule;
use Context;
use Customer;
use Db;
use Order;
use OrderDetail;
use OrderHistory;
use PrestaShop\Module\KpyOrderMaker\Exception\KpyOrderMakerException;
use Shop;
use StockAvailable;
use Validate;

class KpyOrder
{
    public const FINAL_STATE = 2;

    private ?Customer $customer = null;

    private ?Carrier $carrier = null;

    private int $id_address_delivery;

    private array $products;

    private Cart $cart;

    private Order $newOrder;

    private bool $useTaxes;

    public function __construct($id_shop, $id_lang)
    {
        $this->id_address_delivery = 0;
        $this->products = [];
        $this->useTaxes = true;

        Shop::setContext(Shop::CONTEXT_SHOP, $id_shop);
        Context::getContext()->shop->id = $id_shop;
        Context::getContext()->language->id = $id_lang;

        $this->cart = new Cart();
        $this->cart->id_currency = 1;
        $this->cart->id_shop = $id_shop;
        $this->cart->id_shop_group = Shop::getGroupFromShop($id_shop);
        $this->cart->id_lang = $id_lang;
    }

    public function setCustomer(int $id_customer): void
    {
        $this->customer = new Customer($id_customer);

        if (!Validate::isLoadedObject($this->customer)) {
            throw new KpyOrderMakerException('Ha ocurrido un error al cargar el cliente, ¿es posible que no exista?');
        }

        $this->cart->id_customer = $this->customer->id;
        $this->useTaxes = !in_array(21, Customer::getGroupsStatic((int)$this->customer->id));
        Context::getContext()->customer = $this->customer;
    }

    public function setCarrier(int $carrier_reference): void
    {
        $this->carrier = Carrier::getCarrierByReference($carrier_reference);

        if (!Validate::isLoadedObject($this->carrier)) {
            throw new KpyOrderMakerException('Ha ocurrido un error al cargar el transportista, ¿es posible que no exista?');
        }

        $this->cart->id_carrier = $this->carrier->id;
    }

    public function setAddressDelivery($id_address_delivery): void
    {
        $this->id_address_delivery = (int)$id_address_delivery;
        $this->cart->id_address_delivery = (int)$id_address_delivery;
        $this->cart->id_address_invoice = (int)$id_address_delivery;
    }

    public function makeCart(): void
    {
        if (!$this->cart->add()) {
            throw new KpyOrderMakerException('Ha ocurrido un error al crear el nuevo carrito');
        }
    }

    public function addProduct($sku, $quantity, $totalPriceTaxIncl): void
    {
        if (!Validate::isLoadedObject($this->cart)) {
            throw new KpyOrderMakerException('No se pueden añadir productos, el carrito no existe (es posible que sea necesario una llamada al método makeCart antes de añadir productos)');
        }

        if (!$this->id_address_delivery) {
            throw new KpyOrderMakerException('No se pueden añadir productos, no se ha establecido la dirección de envío');
        }

        $product = new KpyOrderMakerProduct($sku, $quantity, $totalPriceTaxIncl, $this->useTaxes);

        if (StockAvailable::getQuantityAvailableByProduct($product->getIdProduct(), $product->getIdProductAttribute()) < (int)$quantity) {
            StockAvailable::setQuantity($product->getIdProduct(), $product->getIdProductAttribute(), (int)$quantity);
        }

        $this->cart->updateQty(
            $quantity,
            $product->getIdProduct(),
            $product->getIdProductAttribute(),
            false,
            'up',
            $this->id_address_delivery
        );

        $this->products[] = $product;
    }

    public function makeFreeOrder(): int
    {
        $this->buildOrder();

        $this->newOrder->payment = 'Pedido gratuito';

        // todos los totales a 0, menos el descuento que es el equivalente al total de productos

        $this->newOrder->total_shipping = 0;
        $this->newOrder->total_shipping_tax_incl = 0;
        $this->newOrder->total_shipping_tax_excl = 0;

        $this->newOrder->total_paid_tax_excl = 0;
        $this->newOrder->total_paid = 0;
        $this->newOrder->total_paid_tax_incl = 0;

        $this->newOrder->total_discounts = $this->newOrder->total_products_wt;
        $this->newOrder->total_discounts_tax_incl = $this->newOrder->total_products_wt;
        $this->newOrder->total_discounts_tax_excl = $this->newOrder->total_products;

        $this->saveOrder();

        $this->addOrderCarrierWithAmount(0);

        // actualiza el precio de los productos para que mantengan el mismo que en la petición
        $this->updateProductsPrice();

        $this->newOrder->addCartRule(
            $this->createUsedCartRuleWithAmount($this->newOrder->total_discounts),
            'Vale dto nuevo envío',
            [
                'tax_incl' => $this->newOrder->total_discounts,
                'tax_excl' => $this->newOrder->total_discounts_tax_excl
            ],
            0,
            false
        );

        $this->changeOrderState(self::FINAL_STATE);

        return (int)$this->newOrder->id;
    }

    private function buildOrder(): void
    {
        if (!Validate::isLoadedObject($this->cart)) {
            throw new KpyOrderMakerException('No se puede generar el pedido, el carrito no existe');
        }

        if (!$this->id_address_delivery) {
            throw new KpyOrderMakerException('No se puede generar el pedido, no se ha establecido la dirección de envío');
        }

        if (empty($this->products)) {
            throw new KpyOrderMakerException('No se puede generar un pedido sin productos');
        }

        if (!Validate::isLoadedObject($this->customer)) {
            throw new KpyOrderMakerException('No se puede generar el pedido, no existe el cliente');
        }

        if (!Validate::isLoadedObject($this->carrier)) {
            throw new KpyOrderMakerException('No se puede generar el pedido, no existe el transportista');
        }

        $this->newOrder = new Order();
        $this->newOrder->module = 'kpyordermaker';
        $this->newOrder->id_address_invoice = $this->id_address_delivery;
        $this->newOrder->id_address_delivery = $this->id_address_delivery;
        $this->newOrder->id_shop = $this->cart->id_shop;
        $this->newOrder->id_shop_group = $this->cart->id_shop_group;
        $this->newOrder->id_lang = $this->cart->id_lang;
        $this->newOrder->id_currency = $this->cart->id_currency;
        $this->newOrder->id_cart = $this->cart->id;
        $this->newOrder->id_customer = $this->customer->id;
        $this->newOrder->id_carrier = $this->carrier->id;
        $this->newOrder->valid = 1;
        $this->newOrder->date_add = date("Y-m-d H:i:s");
        $this->newOrder->date_upd = date("Y-m-d H:i:s");
        $this->newOrder->reference = Order::generateReference();
        $this->newOrder->invoice_number = 0;
        $this->newOrder->delivery_number = 0;
        $this->newOrder->conversion_rate = 1;
        $this->newOrder->recyclable = 0;
        $this->newOrder->gift_message = " ";
        $this->newOrder->secure_key = $this->customer->secure_key;
        $this->newOrder->carrier_tax_rate = $this->carrier->getTaxesRate(Address::initialize($this->id_address_delivery)); // 10, 21, ...
        $this->newOrder->round_mode = 2;
        $this->newOrder->round_type = Order::ROUND_LINE;

        $totalProductsTaxExcl = 0;
        $totalProductsTaxIncl = 0;

        /** @var KpyOrderMakerProduct $product */
        foreach ($this->products as $product) {
            $totalProductsTaxExcl += $product->getTotalPriceTaxExcl();
            $totalProductsTaxIncl += $product->getTotalPriceTaxIncl();
        }

        $this->newOrder->total_products_wt = round($totalProductsTaxIncl, 2);
        $this->newOrder->total_products = round($totalProductsTaxExcl, 2);

        $this->newOrder->total_wrapping = 0;
        $this->newOrder->total_wrapping_tax_incl = 0;
        $this->newOrder->total_wrapping_tax_excl = 0;

        // el total pagado lo actualizará Prestashop al cambiar el estado del pedido a 'Pago aceptado'
        $this->newOrder->total_paid_real = 0;
    }

    private function saveOrder(): void
    {
        if (!$this->newOrder->add()) {
            $this->deleteCart();
            throw new KpyOrderMakerException('Ha ocurrido un error al crear el pedido');
        }

        $this->addOrderDetail();
    }

    private function addOrderCarrierWithAmount($totalShipping): void
    {
        Db::getInstance()->insert('order_carrier', [
            'id_order' => $this->newOrder->id,
            'id_carrier' => $this->carrier->id,
            'date_add' => date('Y-m-d H:i:s'),
            'id_order_invoice' => 0,
            'shipping_cost_tax_incl' => $totalShipping,
            'shipping_cost_tax_excl' => $this->useTaxes
                ? $totalShipping / (1 + ($this->carrier->getTaxesRate(Address::initialize($this->id_address_delivery))/100))
                : $totalShipping
        ]);
    }

    private function addOrderDetail(): void
    {
        $orderDetail = new OrderDetail();
        $orderDetail->createList($this->newOrder, $this->cart, self::FINAL_STATE, $this->cart->getProducts(), 0, $this->useTaxes);

        if (!Validate::isLoadedObject($orderDetail)) {
            Db::getInstance()->delete('orders', 'id_order=' . $this->newOrder->id);
            $this->deleteCart();

            throw new KpyOrderMakerException('Ha ocurrido un error al guardar las líneas del pedido');
        }

    }

    private function updateProductsPrice(): void
    {
        foreach ($this->products as $product) {
            Db::getInstance()->update('order_detail', [
                'reduction_percent' => 0,
                'product_price' => $product->getUnitPriceTaxIncl(),
                'unit_price_tax_incl' => $product->getUnitPriceTaxIncl(),
                'unit_price_tax_excl' => $product->getUnitPriceTaxExcl(),
                'total_price_tax_incl' => $product->getTotalPriceTaxIncl(),
                'total_price_tax_excl' => $product->getTotalPriceTaxExcl(),
            ], "id_order={$this->newOrder->id} and product_id={$product->getIdProduct()} and product_attribute_id=" . ($product->getIdProductAttribute() ?? 0));
        }
    }

    private function changeOrderState(int $orderStateId): void
    {
        $history = new OrderHistory();
        $history->id_order = (int)$this->newOrder->id;
        $history->changeIdOrderState($orderStateId, $this->newOrder, true);
        $history->add();

    }


    private function createUsedCartRuleWithAmount($amount): int
    {
        $code = 'KPY' . random_int(10000000, 99999999);

        Db::getInstance()->insert('cart_rule', array(
            'id_customer' => $this->customer->id,
            'description' => 'Vale descuento nuevo envío',
            'date_from' => date("Y-m-d H:i:s"),
            'date_to' => date("Y-m-d H:i:s"),
            'quantity' => 0,
            'quantity_per_user' => 1,
            'priority' => 1,
            'partial_use' => 0,
            'code' => $code,
            'minimum_amount' => 0,
            'minimum_amount_tax' => 1,
            'minimum_amount_currency' => 1,
            'minimum_amount_shipping' => 0,
            'country_restriction' => 0,
            'carrier_restriction' => 0,
            'group_restriction' => 0,
            'cart_rule_restriction' => 0,
            'product_restriction' => 0,
            'shop_restriction' => 0,
            'free_shipping' => 0,
            'reduction_percent' => 0,
            'reduction_amount' => $amount,
            'reduction_tax' => (int)$this->useTaxes,
            'reduction_currency' => 1,
            'reduction_product' => 0,
            'gift_product' => 0,
            'gift_product_attribute' => 0,
            'highlight' => 0,
            'active' => 0,
            'date_add' => date("Y-m-d H:i:s"),
            'date_upd' => date("Y-m-d H:i:s"),

        ));

        $id_cart_rule = CartRule::getIdByCode($code);

        Db::getInstance()->insert('cart_rule_lang', [
            [
                'id_cart_rule' => $id_cart_rule,
                'id_lang' => 1,
                'name' => 'Vale descuento nuevo envío',
            ],
        ]);

        return $id_cart_rule;
    }

    public function deleteCart(): void
    {
        if (Validate::isLoadedObject($this->cart)) {
            Db::getInstance()->delete('cart', 'id_cart=' . $this->cart->id);
            Db::getInstance()->delete('cart_product', 'id_cart=' . $this->cart->id);
        }
    }
}