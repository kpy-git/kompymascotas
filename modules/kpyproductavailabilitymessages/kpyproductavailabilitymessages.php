<?php

declare(strict_types=1);

use PrestaShop\Module\KpyProductAvailabilityMessages\Install\Installer;
use PrestaShop\Module\KpyProductAvailabilityMessages\Services\ProductAvailabilityMessageFormatter;
use PrestaShop\Module\KpyProductAvailabilityMessages\Services\ProductAvailabilityMessagesHandler;
use PrestaShop\Module\KpyProductAvailabilityMessages\Services\WorkingDaysManager;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyProductAvailabilityMessages extends Module implements WidgetInterface
{
    private WorkingDaysManager $workingDaysManager;

    public function __construct()
    {
        $this->name = 'kpyproductavailabilitymessages';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = false;

        parent::__construct();

        $this->displayName = $this->trans('Kpy Products Availability Messages', [], 'Modules.Kpyproductavailabilitymessages.Admin');
        $this->description = $this->trans('Show Products Availability Messages', [], 'Modules.Kpyproductavailabilitymessages.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyproductavailabilitymessages.Admin');

        $this->workingDaysManager = new WorkingDaysManager();
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
        if (!parent::uninstall()) {
            return false;
        }

        return (new Installer())->uninstall($this);
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
        if (in_array($this->context->controller->php_self, ['product', 'checkout', 'cart'])) {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );
        }
    }

    public function hookDisplayCartExtraProductActions(array $params): string
    {
        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartProductLazyArray $product */
        $product = $params['product'];
        $productAvailabilityMessageHandler = new ProductAvailabilityMessagesHandler();

        // en la clase CartProductLazyArray debería cargar correctamente el mensaje, pero no lo hace
        // getCombinationSpecificData sólo obtiene el nombre del atributo (manda webs)
        $availabilityMessage = $product['stock_quantity'] >= $product['quantity']
            ? $productAvailabilityMessageHandler->getProductMessageInStock($product['id_product'], $product['id_product_attribute'], $this->context->language->id)
            : $productAvailabilityMessageHandler->getProductMessageOutStock($product['id_product'], $product['id_product_attribute'], $this->context->language->id);

        $this->context->smarty->assign([
            'availability_message' => $product['stock_quantity'] >= $product['quantity'] ? $this->getMessageInStock() : $this->getMessageOutStock(),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/cartExtraProductActions.tpl');
    }

    public function renderWidget($hookName, array $configuration): string
    {
        //dump($configuration['product']);
        $this->context->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch('module:' . $this->name . '/views/templates/front/product-availability.tpl');
    }

    public function getWidgetVariables($hookName, array $configuration): array
    {
        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray $product */
        $product = $configuration['product'];

        return [
            'kpyproductavailabilitymessage' => $product->quantity >= $product->cart_quantity + $product->quantity_wanted
                ? $this->getMessageInStock()
                : $this->getMessageOutStock(),
        ];
    }

    public function getMessageInStock(): string
    {
        $messageFormatter = new ProductAvailabilityMessageFormatter();

        $start = (int)strtotime('H') < 12 ? time() : $this->workingDaysManager->getNextWorkingDayTo(time());

        $start = $this->workingDaysManager->getNextWorkingDayTo($start);
        $final = $this->workingDaysManager->getNextWorkingDayTo($start);

        return $messageFormatter->convierteRangoTiempoADiasSemana($start, $final);
    }

    public function getMessageOutStock(): string
    {
        return 'Disponible próximamente';
    }
}
