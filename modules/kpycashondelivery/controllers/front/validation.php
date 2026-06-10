<?php

class KpyCashondeliveryValidationModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public $ssl = true;

    /**
     * @var PaymentModule
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            Tools::redirect($this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            ));
        }

        $this->module->validateOrder(
            (int) $this->context->cart->id,
            $this->module->getFinalOrderState(), // Preparación en curso
            (float) $this->context->cart->getOrderTotal(), // no usamos la comisión aquí, al validar el pedido Prestashop compara este importe con el total del carrito
            $this->module->displayName,
            null,
            [],
            (int) $this->context->currency->id,
            false,
            $customer->secure_key
        );

        Tools::redirect($this->context->link->getPageLink(
            'order-confirmation',
            true,
            (int) $this->context->language->id,
            [
                'id_cart' => (int) $this->context->cart->id,
                'id_module' => (int) $this->module->id,
                'id_order' => (int) $this->module->currentOrder,
                'key' => $customer->secure_key,
            ]
        ));
    }

    /**
     * Check if the context is valid
     * - Cart is loaded
     * - Cart has a Customer
     * - Cart has a delivery address
     * - Cart has an invoice address
     * - Cart doesn't contains virtual product
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice)
            && false === $this->context->cart->isVirtualCart();
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }
}
