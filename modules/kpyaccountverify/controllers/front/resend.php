<?php

class KpyAccountVerifyResendModuleFrontController extends ModuleFrontController
{
    public $auth = true;

    public function initContent(): void
    {
        $customer = $this->context->customer;

        if (!Tools::getIsset('t')
            || $this->module->getTokenForSignResendUrl($customer->id) !== Tools::getValue('t')) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }

        $newToken = $this->module->generateToken($customer->email);
        $this->module->saveToken($customer, $newToken);

        $this->module->sendWelcomeEmail(
            $customer,
            $newToken,
            $this->trans('Verify your email address', [], 'Modules.Kpyaccountverify.Emails')
        );

        $this->context->smarty->assign([
            'customer_email' => $customer->email,
        ]);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/resend.tpl');

        parent::initContent();
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            '/modules/' . $this->module->name . '/views/css/fc-resend.css',
            ['media' => 'all', 'priority' => 1000]
        );
    }
}