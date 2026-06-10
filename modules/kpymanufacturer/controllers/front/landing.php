<?php

use PrestaShop\Module\KpyManufacturer\Exception\LandingBuilderException;
use PrestaShop\Module\KpyManufacturer\LandingBuilder\AbstractLandingBuilder;
use PrestaShop\Module\KpyManufacturer\LandingBuilder\LandingBuilderFactory;

class KpyManufacturerLandingModuleFrontController extends ModuleFrontController
{
    private int $manufacturerId = 0;

    private AbstractLandingBuilder $landingBuilder;

    public function init(): void
    {
        parent::init();

        if (!Tools::getIsset('brand')) {
            Tools::redirect($this->context->link->getPageLink('index'));
        }

        try {
            $this->manufacturerId = Manufacturer::getIDByRewrite(Tools::getValue('brand', 0));

            $builderFactory = new LandingBuilderFactory($this->module, $this->context, $this->translator);
            $this->landingBuilder = $builderFactory->getBuilderByManufacturerID($this->manufacturerId);


        } catch (LandingBuilderException $e) {
            // las que no tengan landing se mandan a su categoría relacionada
            $categoryRelated = new Category(Manufacturer::getCategoryRelatedByManufacturer($this->manufacturerId));

            if (!$categoryRelated->active) {
                Tools::redirect($this->context->link->getPageLink('index'));
            }

            $categoryRelatedLink = $this->context->link->getCategoryLink($categoryRelated);
            Tools::redirect($categoryRelatedLink);
        }

    }

    public function initContent(): void
    {
        parent::initContent();

        $this->context->controller->page_name = 'kpylanding';

        $this->context->smarty->assign($this->landingBuilder->getSmartyVars());

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/' . $this->landingBuilder->getTemplate());

    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();

        $brandName = Manufacturer::getNameByRewrite(Tools::getValue('brand', 0));

        $page['meta']['title'] = $brandName . ' - ' . Configuration::get('PS_SHOP_NAME');
        $page['meta']['keywords'] = implode(',', [
            "pienso $brandName",
            "comprar $brandName",
            "$brandName barato",
            "comida $brandName",
            "tienda >brandName",
            "tienda de $brandName",
            $brandName
        ]);
        $page['meta']['description'] = $brandName;
        $page['meta']['robots'] = 'index, follow';

        $page['page_name'] = 'kpylanding';

        return $page;
    }

    public function setMedia(): void
    {
        parent::setMedia();


        foreach ($this->landingBuilder->getStyleSheetsFiles() as $file) {
            $this->registerStylesheet(
                'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-' . $file . '-style',
                '/modules/' . $this->module->name . '/views/css/' . $file . '.css',
                ['media' => 'all', 'priority' => 1000]
            );
        }

        foreach ($this->landingBuilder->getScriptsFiles() as $file) {
            $this->registerJavascript(
                'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-' . $file . '-script',
                'modules/' . $this->module->name . '/views/js/' . $file . '.js',
                ['priority' => 1000, 'attributes' => 'async',]
            );
        }

        if ($this->landingBuilder->includeSliderScripts()) {
            $this->registerJavascript(
                'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-slider-script',
                'modules/' . $this->module->name . '/views/js/slider.js',
                [
                    'priority' => 1000,
                    'attributes' => 'async',
                ]
            );
        }
    }
}