<?php

class KpyAccountVerifyVerifyModuleFrontController extends ModuleFrontController
{

    public function initContent(): void
    {
        if (!Tools::getIsset('i') || !Tools::getIsset('v')) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }

        $customer = new Customer((int) Tools::getValue('i'));

        if (!$customer || ($this->module->getTokenByCustomer($customer->id) !== Tools::getValue('v'))) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }

        $this->module->verifyAccountByCustomer($this->context->customer->id);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/verify.tpl');

        parent::initContent();
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            '/modules/' . $this->module->name . '/views/css/fc-verify.css',
            ['media' => 'all', 'priority' => 1000]
        );
    }
}