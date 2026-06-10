<?php

declare(strict_types=1);

use PrestaShop\Module\KpyProductBlocks\Install\Installer;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyProductBlocks extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'kpyproductblocks';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Kpy ProductBlocks', [], 'Modules.Kpyproductblocks.Admin');
        $this->description = $this->trans('Agrega bloques en la paǵina de producto', [], 'Modules.Kpyproductblocks.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyproductblocks.Admin');

    }

    /**
     * @return bool
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $installer = new Installer();

        return $installer->install($this);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        $installer = new Installer();

        return $installer->uninstall($this);
    }

    /**
     * @see https://devdocs.prestashop.com/8/modules/creation/module-translation/new-system/#translating-your-module
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function hookActionFrontControllerSetMedia()
    {
        if ($this->context->controller->php_self === 'product') {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );

            $this->context->controller->registerJavascript(
                $this->name . '-script',
                'modules/' . $this->name . '/views/js/' . $this->name . '.js',
                [
                    'position' => 'bottom',
                    'priority' => 1000,
                ]
            );
        }
    }

    public function renderWidget($hookName, array $configuration)
    {
        return match($hookName) {
            'product_shipping' => $this->renderShippingPrice($configuration),
            'unit_price' => $this->renderUnitPrice($configuration),
            default => ''
        };
    }

    public function renderShippingPrice(array $configuration): string
    {
        $product = $configuration['product'];

        $productWanted = (int)Tools::getValue('quantity_wanted', 1);

        if (round((float)$product['price'] * $productWanted, 2) < _KPY_LIMIT_SHIPPING_FREE_) {
            return $this->fetch('module:' . $this->name . '/views/templates/hook/shipping.tpl', [
                'img_path' => '/modules/' . $this->name . '/views/img/shipping.svg',
                'tag' => $this->trans('Shipping: %price%€', ['%price%' => 4.99], 'Modules.Kpyproductblocks.Shop'),
            ]);
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/shipping.tpl', [
            'img_path' => '/modules/' . $this->name . '/views/img/shipping_free.svg',
            'tag' => $this->trans('Shipping: free!', [], 'Modules.Kpyproductblocks.Shop')
        ]);
    }

    public function renderUnitPrice(array $configuration): string
    {
        $product = $configuration['product'];

        if (!Product::isPienso((int)$product['id_product'])) {
            return '';
        }

        $price = (float)Product::getPriceStatic($product['id_product'], true, $product['id_product_attribute'], 2);

        $weight = Product::getWeight((int)$product['id_product'], (int)$product['id_product_attribute']);

        if ($weight < 1) {
            return '';
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/unit-price.tpl', [
            'unit_price' => round($price / $weight, 2),
        ]);

    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        // TODO: Implement getWidgetVariables() method.
    }
}
