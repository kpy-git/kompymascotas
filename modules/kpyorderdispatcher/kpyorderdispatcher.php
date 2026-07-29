<?php

declare(strict_types=1);

use PrestaShop\Module\KpyOrderDispatcher\Install\Installer;
use PrestaShop\Module\KpyOrderDispatcher\Config\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyOrderDispatcher extends Module
{
	public function __construct()
	{
        $this->name = 'kpyorderdispatcher';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        
        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Order Dispatcher', [], 'Modules.Kpyorderdispatcher.Admin');
        $this->description = $this->trans('Handler the warehouse where orders are shipped.', [], 'Modules.Kpyorderdispatcher.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyorderdispatcher.Admin');

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

    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        $newOrderStatus = (int)$params['newOrderStatus']->id;

        if (!in_array($newOrderStatus, $this->getOrderStates())) {
            return;
        }

        Db::getInstance()->insert('kpy_orders_pending_dispatch', [
            'id_order' => (int)$params['id_order'],
        ]);
    }

    private function getOrderStates(): array
    {
        return json_decode(\Configuration::get(Config::KPY_ORDER_DISPATCHER_OS), true, 512, JSON_THROW_ON_ERROR);
    }
}
