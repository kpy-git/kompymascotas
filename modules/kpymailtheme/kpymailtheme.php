<?php

declare(strict_types=1);

use PrestaShop\Module\KpyMailTheme\Install\Installer;
use PrestaShop\PrestaShop\Core\MailTemplate\FolderThemeScanner;
use PrestaShop\PrestaShop\Core\MailTemplate\Layout\LayoutInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCatalogInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCollectionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyMailTheme extends Module
{
    public function __construct()
    {
        $this->name = 'kpymailtheme';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Mail Theme', [], 'Modules.Kpymailtheme.Admin');
        $this->description = $this->trans('Add the KpyTheme mails template', [], 'Modules.Kpymailtheme.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpymailtheme.Admin');

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

    public function enable($force_all = false): bool
    {
        return parent::enable()
            && $this->registerHook(ThemeCatalogInterface::LIST_MAIL_THEMES_HOOK);
    }

    public function disable($force_all = false): bool
    {
        return parent::disable()
            && $this->unregisterHook(ThemeCatalogInterface::LIST_MAIL_THEMES_HOOK);
    }

    public function hookActionListMailThemes(array $hookParams): void
    {
        if (!isset($hookParams['mailThemes'])) {
            return;
        }

        /** @var ThemeCollectionInterface $themes */
        $themes = $hookParams['mailThemes'];


        $scanner = new FolderThemeScanner();
        $kpytheme = $scanner->scan($this->getLocalPath() . 'mails/themes/kpytheme');
        if (null !== $kpytheme && $kpytheme->getLayouts()->count() > 0) {
            $themes->add($kpytheme);
        }
    }

    public function hookActionBuildMailLayoutVariables(array $hookParams): void
    {
        if (!isset($hookParams['mailLayout'])) {
            return;
        }

        /** @var LayoutInterface $mailLayout */
        $mailLayout = $hookParams['mailLayout'];

        if (str_contains($mailLayout->getHtmlPath(), 'kpytheme')) {
            $kpyParams = [
                'shop_email' => $this->trans('hola@kompymascotas.com', [], 'Modules.Kpymailtheme.Admin'),
                'shop_phone' => $this->trans('955 228 677', [], 'Modules.Kpymailtheme.Admin'),
                'shop_phone_time' => $this->trans('L-V de 09:00 a 18:00h', [], 'Modules.Kpymailtheme.Admin'),
                'copyright' => sprintf("©%d. %s", date('Y'), $this->context->shop->name),
                'contact_url' => $this->context->link->getPageLink('contact', true),
            ];

            $hookParams['mailLayoutVariables'] = array_merge($hookParams['mailLayoutVariables'], $kpyParams);
        }
    }

    public function hookActionGetExtraMailTemplateVars(array $params): void
    {
        // file_put_contents(_PS_LOG_DIR_ . 'mail_vars.json', json_encode($params, JSON_PRETTY_PRINT));

        $gamifications = Module::getInstanceByName('gamifications');
        if ($params['template'] === 'order_conf' && $gamifications && $gamifications->active) {
            $params['extra_template_vars']['{gamifications_url}'] = $this->context->link->getModuleLink('gamifications', 'exchangepoints');

            /** @var Cart $cart */
            $cart = $params['cart'];

            $params['extra_template_vars']['{order_points}'] = $cart->getOrderTotal() > 0 ? floor($cart->getOrderTotal(true, Cart::ONLY_PRODUCTS)) : 0;

        } else if ($params['template'] === 'ship_ready') {
            $params['extra_template_vars']['{followup}'] = str_replace(
                '~cp~',
                $this->getDeliveryPostCodeByOrder((int)$params['template_vars']['{id_order}']),
                $params['template_vars']['{followup}']
            );
        }
    }

    private function getDeliveryPostCodeByOrder(int $idOrder): string
    {
        return Db::getInstance()->getValue(
            "SELECT postcode
                    FROM " . _DB_PREFIX_ . "address a
                    WHERE a.id_address = (SELECT id_address_delivery FROM " . _DB_PREFIX_ . "orders o WHERE o.id_order=$idOrder)"
        ) ?: '';
    }

    public function hookActionPaymentModuleProductVarTplAfter(array $params): void
    {
        // file_put_contents(_PS_LOG_DIR_ . 'products.json', json_encode($params['product_var_tpl'], JSON_PRETTY_PRINT));

        $product = $params['product'];
        $params['product_var_tpl']['image'] = $this->context->link->getImageLink($product['link_rewrite'], $product['id_image'], 'cart_default');

        // file_put_contents(_PS_LOG_DIR_ . 'products.json', json_encode($params['product_var_tpl'], JSON_PRETTY_PRINT), FILE_APPEND);
    }
}
