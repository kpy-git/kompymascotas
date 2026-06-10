<?php

declare(strict_types=1);

use PrestaShop\Module\KpyCancellationRequest\Guard\Guard;
use PrestaShop\Module\KpyCancellationRequest\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyCancellationRequest extends Module
{
    public const string CANCELLATION_REQUEST = 'KPY_CANCELLATION_REQUEST_OS';

    public const string CANCEL_CANCELLATION_REQUEST = 'KPY_CANCEL_CANCELLATION_REQUEST_OS';

	public function __construct()
	{
        $this->name = 'kpycancellationrequest';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';
        
        $this->ps_versions_compliancy = [
            'min' => '9.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Cancellation Request', [], 'Modules.Kpycancellationrequest.Admin');
        $this->description = $this->trans('Handler for customer cancellation order requests.', [], 'Modules.Kpycancellationrequest.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpycancellationrequest.Admin');
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

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        if ((int)$params['newOrderStatus']->id === (int)\Configuration::get(self::CANCELLATION_REQUEST)) {
            // todo - enviar un email ATC??
        }

    }

    public function hookDisplayOrderDetailActions(array $params): string
    {
        $data = [
            'order' => $params['order']->getDetails()->getId(),
            'customer' => $this->context->customer->id,
        ];

        if (Guard::isOrderCancelable($params['order'])) {
            return $this->fetch('module:' . $this->name . '/views/templates/front/hook/displayOrderDetailActions.tpl', [
                'class' => 'order-cancellation-request',
                'message' => $this->trans('Cancellation request', [], 'Modules.Kpycancellationrequest.Shop'),
                'status' => Configuration::get(self::CANCELLATION_REQUEST),
                ...$data,
            ]);
        }

        if (Guard::canBeCancelledCancellationRequest($params['order'])) {
            return $this->fetch('module:' . $this->name . '/views/templates/front/hook/displayOrderDetailActions.tpl', [
                'class' => 'cancel-order-cancellation-request',
                'message' => $this->trans('Cancel cancellation request', [], 'Modules.Kpycancellationrequest.Shop'),
                'status' => Configuration::get(self::CANCEL_CANCELLATION_REQUEST),
                ...$data,
            ]);
        }

        return '';
    }

    public function hookActionFrontControllerSetVariables(): array
    {
        if (!$this->context->controller instanceof OrderDetailController) {
            return [];
        }

        return [
            'url_handler' => $this->context->link->getModuleLink($this->name, 'handler'),
        ];
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if ('order-detail' === $this->context->controller->php_self) {
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

    public function hookDisplayOrderDetail(array $params): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/front/hook/displayOrderDetail.tpl');
    }
}
