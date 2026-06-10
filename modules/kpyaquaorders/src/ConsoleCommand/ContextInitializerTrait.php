<?php

namespace PrestaShop\Module\KpyAquaOrders\ConsoleCommand;

trait ContextInitializerTrait
{
    public function initializeContext(): \Context
    {
        $context = \Context::getContext();
        if (!$context->currency) {
            $context->currency = \Currency::getCurrencyInstance((int)\Configuration::get('PS_CURRENCY_DEFAULT'));
        }

        if (!$context->language) {
            $context->language = new \Language((int)\Configuration::get('PS_LANG_DEFAULT'));
        }

        if (!$context->employee) {
            $context->employee = new \Employee(0);
            $context->employee->id_profile = 1;
        }

        if (!$context->shop) {
            $context->shop = new \Shop((int)\Configuration::get('PS_SHOP_DEFAULT'));
        }

        return $context;
    }
}