<?php

if (!defined('_PS_VERSION_')) {
    exit;
}


use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class KpyCashondelivery extends PaymentModule
{
    private const array HOOKS = [
        'displayOrderConfirmation',
        'paymentOptions',
    ];

    public const COD_FEE = 'KPYCOD_FEE';

    public const ORDER_STATE = 'KPYCOD_ORDER_STATE';

    public function __construct()
    {
        $this->name = 'kpycashondelivery';
        $this->tab = 'payments_gateways';
        $this->author = 'PyM Team';
        $this->version = '1.0';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = ['min' => '8.2', 'max' => _PS_VERSION_];
        $this->controllers = ['validation'];
        $this->currencies = false;

        parent::__construct();

        $this->displayName = $this->trans('Kpy Cash on delivery', [], 'Modules.Kpycashondelivery.Admin');
        $this->description = $this->trans('Accept cash payments on delivery to make it easy for customers to purchase on your store.', [], 'Modules.Kpycashondelivery.Admin');
    }

    public function install(): bool
    {
        $this->createConfiguration();

        return parent::install()
            && $this->registerHook(static::HOOKS);
    }

    public function createConfiguration(): void
    {
        if (!Configuration::get(self::COD_FEE)) {
            Configuration::updateValue(self::COD_FEE, 2);
        }

        if (!Configuration::get(self::ORDER_STATE)) {
            Configuration::updateValue(self::ORDER_STATE, 3);
        }
    }

    public function uninstall(): bool
    {
        $this->deleteConfiguracion();

        return parent::uninstall();
    }

    public function deleteConfiguracion(): void
    {
        Configuration::deleteByName(self::COD_FEE);
        Configuration::deleteByName(self::ORDER_STATE);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function hookPaymentOptions(array $params): array
    {
        if (empty($params['cart'])) {
            return [];
        }

        /** @var Cart $cart */
        $cart = $params['cart'];

        if ($cart->isVirtualCart()) {
            return [];
        }

        /** Condiciones de piensoymascotas.com */

        $postCode = Address::initialize((int)$params['cart']->id_address_delivery)->postcode;

        // deshabilitado para Baleares
        if (Context::getContext()->shop->id == 1 && str_starts_with($postCode, '07')) {
            return [];
        }

        $customerGroups = Context::getContext()->customer->getGroups();
        // si esta el grupo de restringidos para contra rembolso no se habilita la opción de contra reembolso
        if (in_array(26, $customerGroups)) {
            return [];
        }
        // Si el carrito es superior a 300 euros, y el cliente no es exento a limite contra reembolso (grupo 24)
        if ($params['cart']->getOrderTotal(true) > 300 && in_array(24, $customerGroups) === false){
            return [];
        }

        $this->context->smarty->assign([
            'kpycodfee' => [
                'fee' => $this->getFee(),
                'total_paid' => $this->context->cart->getOrderTotal(true, Cart::BOTH) + $this->getFee(),
            ]
        ]);

        $cashOnDeliveryOption = new PaymentOption();
        $cashOnDeliveryOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay by Cash on Delivery', [], 'Modules.Kpycashondelivery.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setAdditionalInformation($this->fetch('module:' . $this->name . '/views/templates/hook/paymentOptions-additionalInformation.tpl'))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo.svg'));

        return [$cashOnDeliveryOption];
    }

    /**
     * @param array{cookie: Cookie, cart: Cart, altern: int, order: Order, objOrder: Order} $params
     *
     * @return string
     */
    public function hookDisplayOrderConfirmation(array $params)
    {
        /** @var Order $order */
        $order = (isset($params['objOrder'])) ? $params['objOrder'] : $params['order'];

        if (!Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        $this->context->smarty->assign([
            'shop_name' => $this->context->shop->name,
            'total' => $this->context->getCurrentLocale()->formatPrice($params['order']->getOrdersTotalPaid(), (new Currency($params['order']->id_currency))->iso_code),
            'reference' => $order->id,
            'contact_url' => $this->context->link->getPageLink('contact', true),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayOrderConfirmation.tpl');
    }

    public function getFee(): float
    {
        return (float)Configuration::get(self::COD_FEE);
    }

    public function getFeeWithoutTaxes(): float
    {
        return round($this->getFee() / 1.21, 6);
    }

    public function getFinalOrderState(): int
    {
        return (int)Configuration::get(self::ORDER_STATE);
    }

    protected function createOrderFromCart(
        Cart $cart,
        Currency $currency,
        $productList,
        $addressId,
        $context,
        $reference,
        $secure_key,
        $payment_method,
        $name,
        $dont_touch_amount,
        $amount_paid,
        $warehouseId,
        $cart_total_paid,
        $debug,
        $order_status,
        $id_order_state,
        $carrierId = null
    ) {
        $order = new Order();
        $order->product_list = $productList;

        $computingPrecision = Context::getContext()->getComputingPrecision();

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $address = new Address((int) $addressId);
            $context->country = new Country((int) $address->id_country, (int) $cart->id_lang);
            if (!$context->country->active) {
                throw new PrestaShopException('The delivery address country is not active.');
            }
        }

        $carrier = null;
        if (!$cart->isVirtualCart() && isset($carrierId)) {
            $carrier = new Carrier((int) $carrierId, (int) $cart->id_lang);
            $order->id_carrier = (int) $carrier->id;
            $carrierId = (int) $carrier->id;
        } else {
            $order->id_carrier = 0;
            $carrierId = 0;
        }

        $order->id_customer = (int) $cart->id_customer;
        $order->id_address_invoice = (int) $cart->id_address_invoice;
        $order->id_address_delivery = (int) $addressId;
        $order->id_currency = $currency->id;
        $order->id_lang = (int) $cart->id_lang;
        $order->id_cart = (int) $cart->id;
        $order->reference = $reference;
        $order->id_shop = (int) $context->shop->id;
        $order->id_shop_group = (int) $context->shop->id_shop_group;

        $order->secure_key = ($secure_key ? pSQL($secure_key) : pSQL($context->customer->secure_key));
        $order->payment = $payment_method;
        if (isset($name)) {
            $order->module = $name;
        }
        $order->recyclable = $cart->recyclable;
        $order->gift = (bool) $cart->gift;
        $order->gift_message = $cart->gift_message;
        $order->mobile_theme = $cart->mobile_theme;
        $order->conversion_rate = $currency->conversion_rate;
        $amount_paid = !$dont_touch_amount ? Tools::ps_round((float) $amount_paid, $computingPrecision) : $amount_paid;
        $order->total_paid_real = 0;

        $order->total_products = Tools::ps_round(
            (float) $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS, $order->product_list, $carrierId),
            $computingPrecision
        );
        $order->total_products_wt = Tools::ps_round(
            (float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS, $order->product_list, $carrierId),
            $computingPrecision
        );
        $order->total_discounts_tax_excl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_discounts_tax_incl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_discounts = $order->total_discounts_tax_incl;

        $order->total_shipping_tax_excl = Tools::ps_round(
            (float) $cart->getPackageShippingCost($carrierId, false, null, $order->product_list) + $this->getFeeWithoutTaxes(),
            $computingPrecision
        );
        $order->total_shipping_tax_incl = Tools::ps_round(
            (float) $cart->getPackageShippingCost($carrierId, true, null, $order->product_list) + $this->getFee(),
            $computingPrecision
        );
        $order->total_shipping = $order->total_shipping_tax_incl;

        if (null !== $carrier && Validate::isLoadedObject($carrier)) {
            $order->carrier_tax_rate = $carrier->getTaxesRate(new Address((int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        }

        $order->total_wrapping_tax_excl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(false, Cart::ONLY_WRAPPING, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_wrapping_tax_incl = Tools::ps_round(
            (float) abs($cart->getOrderTotal(true, Cart::ONLY_WRAPPING, $order->product_list, $carrierId)),
            $computingPrecision
        );
        $order->total_wrapping = $order->total_wrapping_tax_incl;

        $order->total_paid_tax_excl = Tools::ps_round(
            (float) $cart->getOrderTotal(false, Cart::BOTH, $order->product_list, $carrierId) + $this->getFeeWithoutTaxes(),
            $computingPrecision
        );
        $order->total_paid_tax_incl = Tools::ps_round(
            (float) $cart->getOrderTotal(true, Cart::BOTH, $order->product_list, $carrierId) + $this->getFee(),
            $computingPrecision
        );
        $order->total_paid = $order->total_paid_tax_incl;
        $order->round_mode = (int) Configuration::get('PS_PRICE_ROUND_MODE');
        $order->round_type = (int) Configuration::get('PS_ROUND_TYPE');

        $order->invoice_date = '0000-00-00 00:00:00';
        $order->delivery_date = '0000-00-00 00:00:00';

        if ($debug) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order is about to be added', 1, null, 'Cart', (int) $cart->id, true);
        }

        // Creating order
        $result = $order->add();

        if (!$result) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - Order cannot be created', 3, null, 'Cart', (int) $cart->id, true);
            throw new PrestaShopException('Can\'t save Order');
        }

        // Amount paid by customer is not the right one -> Status = payment error
        // We don't use the following condition to avoid the float precision issues : https://www.php.net/manual/en/language.types.float.php
        // if ($order->total_paid != $order->total_paid_real)
        // We use number_format in order to compare two string
        if ($order_status->logable
            && number_format(
                $cart_total_paid,
                $computingPrecision
            ) != number_format(
                $amount_paid,
                $computingPrecision
            )
        ) {
            $id_order_state = Configuration::get('PS_OS_ERROR');
        }

        if ($debug) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderDetail is about to be added', 1, null, 'Cart', (int) $cart->id, true);
        }

        // Insert new Order detail list using cart for the current order
        $order_detail = new OrderDetail(null, null, $context);
        $order_detail->createList($order, $cart, $id_order_state, $order->product_list, 0, true, $warehouseId);

        if ($debug) {
            PrestaShopLogger::addLog('PaymentModule::validateOrder - OrderCarrier is about to be added', 1, null, 'Cart', (int) $cart->id, true);
        }

        // Adding an entry in order_carrier table
        if (null !== $carrier) {
            $order_carrier = new OrderCarrier();
            $order_carrier->id_order = (int) $order->id;
            $order_carrier->id_carrier = $carrierId;
            $order_carrier->weight = (float) $order->getTotalWeight();
            $order_carrier->shipping_cost_tax_excl = (float) $order->total_shipping_tax_excl;
            $order_carrier->shipping_cost_tax_incl = (float) $order->total_shipping_tax_incl;
            $order_carrier->add();
        }

        return ['order' => $order, 'orderDetail' => $order_detail];
    }
}
