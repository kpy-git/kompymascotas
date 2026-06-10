<?php

declare(strict_types=1);

use PrestaShop\Module\KpyCartBlocks\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyCartBlocks extends Module
{
	public function __construct()
	{
        $this->name = 'kpycartblocks';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Cart Blocks', [], 'Modules.Kpycartblocks.Admin');
        $this->description = $this->trans('Agrega bloques en la página de carrito.', [], 'Modules.Kpycartblocks.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpycartblocks.Admin');

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

    public function hookActionFrontControllerSetMedia(): void
    {
        if (in_array($this->context->controller->getPageName(), ['cart', 'checkout'])) {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );
        }
    }

    public function hookDisplayShoppingCartAfterDetailedTotals(array $params): string
    {
        $totalProducts = (float)$this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $remaining = round(_KPY_LIMIT_SHIPPING_FREE_ - $totalProducts, 2);

        return $this->fetch('module:' . $this->name . '/views/templates/front/shopping-cart.tpl', [
            'total_products' => sprintf("%.2f €", $totalProducts),
            'remaining_amount' => $remaining,
            'limit_lower' => 0,
            'limit_upper' => _KPY_LIMIT_SHIPPING_FREE_,
            'width' => min($totalProducts * 100 / _KPY_LIMIT_SHIPPING_FREE_, 100),
        ]);
    }
}
