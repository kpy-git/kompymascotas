<?php

declare(strict_types=1);

use PrestaShop\Module\KpyOrderMaker\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyOrderMaker extends Module
{
	public function __construct()
	{
        $this->name = 'kpyordermaker';
        $this->version = '1.0.0';
        $this->author = 'PyM';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->trans('Kpy Order Maker', [], 'Modules.Kpyordermaker.Admin');
        $this->description = $this->trans('Allow creates order with AJAX request', [], 'Modules.Kpyordermaker.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyordermaker.Admin');

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];
	}

	public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

		return (new Installer())->install($this);
	}

	public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

		return (new Installer())->uninstall($this);
	}

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }
}