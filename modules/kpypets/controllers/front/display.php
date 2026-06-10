<?php

class KpyPetsDisplayModuleFrontController extends ModuleFrontController
{
    /* Redirecciona al login en el caso de que no esté registrado */
    public $auth = true;

    public $guestAllowed = false;

    public function initContent()
    {
        $this->context->smarty->assign([
            'pets' => $this->module->getCustomerPets(),
            'module_img' => $this->module->getPathUri() . 'views/img/',
            'csrf_token' => Tools::getToken(),
        ]);

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/pets.tpl');

        parent::initContent();
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            '/modules/' . $this->module->name . '/views/css/display.css',
            ['media' => 'all', 'priority' => 1000]
        );

        $this->registerJavascript(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-script',
            '/modules/' . $this->module->name . '/views/js/display.js',
            ['priority' => 1000]
        );
    }

    protected function getBreadcrumbLinks(): array
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();

        $breadcrumb['links'][] = [
            'title' => $this->trans('My pets', [], 'Modules.Kpypets.Shop'),
            'url' => $this->context->link->getModuleLink($this->module->name, 'display'),
        ];

        return $breadcrumb;
    }
}