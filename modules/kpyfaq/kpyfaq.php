<?php

declare(strict_types=1);

use PrestaShop\Module\KpyFaq\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyFaq extends Module
{
	public function __construct()
	{
        $this->name = 'kpyfaq';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy FAQ', [], 'Modules.Kpyfaq.Admin');
        $this->description = $this->trans('Customer center and answers/questions.', [], 'Modules.Kpyfaq.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyfaq.Admin');

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

    public function hookModuleRoutes(): array
    {
        return [
            'module-' . $this->name . '-section' => [
                'rule' => 'soporte/{name}',
                'keywords' => [
                    'name' => [
                        'regexp' => '.*',
                        'param' => 'name',
                    ]
                ],
                'controller' => 'element',
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ]
            ]
        ];
    }

    public function hookDisplayCustomerAccountMenu(): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/accountmenu.tpl', [
            'url' => $this->context->link->getModuleLink($this->name, 'display'),
        ]);
    }

    public function hookDisplayKpyMenuMobileElementsAfter(): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayKpyMenuMobile.tpl', [
            'url' => $this->context->link->getModuleLink($this->name, 'display'),
        ]);
    }
}
