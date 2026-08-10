<?php

declare(strict_types=1);

use PrestaShop\Module\KpyStorePreparation\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyStorePreparation extends Module
{
    public function __construct()
    {
        $this->name = 'kpystorepreparation';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';

        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Store Preparation', [], 'Modules.Kpystorepreparation.Admin');
        $this->description = $this->trans('Store preparation orders dispatcher', [], 'Modules.Kpystorepreparation.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpystorepreparation.Admin');

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

    public function hookActionKpyOrderWarehouseSelected(array $params): void
    {
        if ($params['warehouse'] !== 'TIENDA') {
            return;
        }

        $order = new Order((int)$params['id_order']);

        $order->setCurrentStateWithDate(
            26, // Pendiente de preparación en tienda
            date('Y-m-d H:i:s')
        );
    }
}
