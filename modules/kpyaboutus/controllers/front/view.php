<?php

class KpyAboutUsViewModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->context->smarty->assign([
            'module_img' => $this->module->getPathUri() . 'views/img/',
        ]);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/view.tpl');

        parent::initContent();
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            '/modules/' . $this->module->name . '/views/css/view.css',
            ['media' => 'all', 'priority' => 1000]
        );
    }
}