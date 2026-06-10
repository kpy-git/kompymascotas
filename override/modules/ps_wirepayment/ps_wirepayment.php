<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_WirepaymentOverride extends Ps_Wirepayment
{
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        if (!$this->checkCurrency($params['cart'])) {
            return [];
        }

        $this->smarty->assign(
            $this->getTemplateVarInfos()
        );

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Pay by bank wire', [], 'Modules.Wirepayment.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', [], true))
            ->setLogo($this->getPathUri() . 'views/img/logo.svg')
            ->setAdditionalInformation($this->fetch('module:ps_wirepayment/views/templates/hook/ps_wirepayment_intro.tpl'));

        return [
            $newOption,
        ];

    }
}