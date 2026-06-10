<?php

class KpyManufacturerBrandsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $manufacturers = Manufacturer::getAllManufacturersLinkRewriteById();

        $manufacturersWithNumber = [];
        $manufacturersByFirstLetter = [];

        foreach ($manufacturers as $manufacturer) {
            $manufacturer['image'] = $this->context->link->getManufacturerImageLink($manufacturer['id']);
            $manufacturer['url'] = $this->context->link->getBaseLink() . 'marcas/' . $manufacturer['link_rewrite'];

            $start = mb_strtoupper(mb_substr($manufacturer['name'], 0, 1));

            if (ctype_digit($start)) {
                if (!isset($manufacturersWithNumber[$start])) {
                    $manufacturersWithNumber[$start] = [];
                }
                $manufacturersWithNumber[$start][] = $manufacturer;

            } else {
                if (!isset($manufacturersByFirstLetter[$start])) {
                    $manufacturersByFirstLetter[$start] = [];
                }
                $manufacturersByFirstLetter[$start][] = $manufacturer;
            }
        }

        ksort($manufacturersWithNumber);
        ksort($manufacturersByFirstLetter);
        $manufacturers = $manufacturersByFirstLetter + $manufacturersWithNumber;

        $this->context->smarty->assign([
            'brandsByLetter' => $manufacturers,
            'letters' => array_keys($manufacturers),
        ]);

        $this->context->controller->page_name = 'kpybrands';

        $this->setTemplate('module:' . $this->module->name . '/views/templates/front/brands.tpl');

        parent::initContent();
    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();

        $page['meta']['title'] = $this->trans('Brands on %shop_name%', ['%shop_name%' => Configuration::get('PS_SHOP_NAME')], 'Modules.Kpymanufacturer.Shop');
        $page['meta']['robots'] = 'index, follow';

        return $page;
    }

    public function setMedia(): void
    {
        parent::setMedia();

        $this->registerStylesheet(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-style',
            'modules/' . $this->module->name . '/views/css/brands.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );

        $this->registerJavascript(
            'module-' . $this->module->name . '-fc-' . basename(__FILE__) . '-script',
            'modules/' . $this->module->name . '/views/js/brands.js',
            [
                'priority' => 1000,
                'attributes' => 'async',
            ]
        );
    }
}