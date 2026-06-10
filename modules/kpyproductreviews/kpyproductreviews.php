<?php

declare(strict_types=1);

use PrestaShop\Module\KpyProductReviews\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyProductReviews extends Module
{
	public function __construct()
	{
        $this->name = 'kpyproductreviews';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Product Reviews', [], 'Modules.Kpyproductreviews.Admin');
        $this->description = $this->trans('Add new page per product with customer reviews.', [], 'Modules.Kpyproductreviews.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyproductreviews.Admin');

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

    public function hookModuleRoutes(array $params): array
    {
        return [
            'module-' . $this->name . '-display' => [
                'rule' => 'opiniones/{product_rewrite}',
                'keywords' => [
                    'product_rewrite' => [
                        'regexp' => '.*',
                        'param' => 'product_rewrite',
                    ]
                ],
                'controller' => 'display',
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ]
            ],
        ];
    }
}
