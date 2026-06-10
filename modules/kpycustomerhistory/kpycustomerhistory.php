<?php

declare(strict_types=1);

use PrestaShop\Module\KpyCustomerHistory\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyCustomerHistory extends Module
{
	public function __construct()
	{
        $this->name = 'kpycustomerhistory';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Customer History', [], 'Modules.Kpycustomerhistory.Admin');
        $this->description = $this->trans('Add variables to HistoryController', [], 'Modules.Kpycustomerhistory.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpycustomerhistory.Admin');

	}

	/**
     * @return bool
     */
    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        return (new Installer())->install($this);
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        return parent::uninstall();
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
        if (in_array($this->context->controller->php_self, ['history', 'order-detail'], true)) {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );
        }

        if ('history' === $this->context->controller->php_self) {
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

    public function hookActionFrontControllerSetVariables(): array
    {
        if ('history' !== $this->context->controller->php_self) {
            return [];
        }

        $data = [
            'orders_history' => $this->getCountOrdersByCustomer(),
        ];

        if (Tools::getIsset('year') && Tools::getIsset('month')) {
            $data['selected_year'] = Tools::getValue('year');
            $data['selected_month'] = Tools::getValue('month');
        }

        return $data;
    }

    private function getCountOrdersByCustomer(): array
    {
        $sql = "select year(o.date_add) as `year`, month(o.date_add) as `month`, count(*) as `orders`
            from " . _DB_PREFIX_ . "orders o
            where o.id_customer = {$this->context->customer->id}
            group by year(o.date_add), month(o.date_add)
            order by year(o.date_add) desc, month(o.date_add) desc";

        $results = Db::getInstance()->executeS($sql);

        if (empty($results)) {
            return [];
        }

        $orders = [];

        foreach ($results as $result) {
            if (!isset($orders[$result['year']])) {
                $orders[$result['year']] = [];
            }

            $orders[$result['year']][$result['month']] = $this->getMonthName((int)$result['month']);
        }

        return $orders;
    }

    private function getMonthName(int $month): string
    {
        $monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre",
            "Octubre", "Noviembre", "Diciembre"];

        return $monthNames[$month - 1] ?? '';
    }
}
