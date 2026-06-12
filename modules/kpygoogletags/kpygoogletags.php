<?php

declare(strict_types=1);

use PrestaShop\Module\KpyGoogleTags\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyGoogleTags extends Module
{
	public function __construct()
	{
        $this->name = 'kpygoogletags';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        
        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Google Tags', [], 'Modules.Kpygoogletags.Admin');
        $this->description = $this->trans('Handle Google Tags.', [], 'Modules.Kpygoogletags.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpygoogletags.Admin');

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

    public function hookDisplayHeader(array $params): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayHeader.tpl');
    }

    public function hookDisplayAfterBodyOpeningTag(array $params): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayAfterBodyOpeningTag.tpl');
    }

    public function hookDisplayBeforeBodyClosingTag(array $params): string
    {
        return '';
    }
}
