<?php

declare(strict_types=1);

use PrestaShop\Module\KpyOrderMargin\Exception\KpyOrderMarginException;
use PrestaShop\Module\KpyOrderMargin\Install\Installer;
use PrestaShop\Module\KpyOrderMargin\Service\Client;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyOrderMargin extends Module
{
    public const string KPY_ORDER_MARGIN_HTTP_CLIENT = 'KPY_ORDER_MARGIN_HTTP_CLIENT';
    public const string KPY_ORDER_MARGIN_HTTP_PASSWORD = 'KPY_ORDER_MARGIN_HTTP_PASSWORD';
    public const string KPY_ORDER_MARGIN_URL = 'KPY_ORDER_MARGIN_URL';

	public function __construct()
	{
        $this->name = 'kpyordermargin';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        
        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Order Margin', [], 'Modules.Kpyordermargin.Admin');
        $this->description = $this->trans('Handler order margin calculator.', [], 'Modules.Kpyordermargin.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyordermargin.Admin');
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

    public function hookActionKpyOrderDispatched(array $params): void
    {
        /** @var \Order $order */
        $order = $params['order'];

        $client = new Client();

        try {
            $client->calculateMargin($order->id);

        } catch (KpyOrderMarginException $e) {
            \Mail::Send(
                (int)\Configuration::get('PS_LANG_DEFAULT'),
                'generic',
                'Error al calcular el margen del pedido ' . $order->id,
                array(
                    '{message}' => $e->getMessage(),
                    '{id_order}' => $order->id,
                ),
                'desarrollo@kompymascotas.com',
            );
        }
    }
}
