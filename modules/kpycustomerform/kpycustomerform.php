<?php

declare(strict_types=1);

use PrestaShop\Module\KpyCustomerForm\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyCustomerForm extends Module
{
	public function __construct()
	{
        $this->name = 'kpycustomerform';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Customer Form', [], 'Modules.Kpycustomerform.Admin');
        $this->description = $this->trans('Modify customer form.', [], 'Modules.Kpycustomerform.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpycustomerform.Admin');
	}

	/**
     * @return bool
     */
    public function install(): bool
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
    public function uninstall(): bool
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
        if ($this->context->controller instanceof IdentityControllerCore) {
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

}
