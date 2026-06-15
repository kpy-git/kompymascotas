<?php

declare(strict_types=1);

use PrestaShop\Module\KpyProductFlags\Install\Installer;
use PrestaShop\Module\KpySpecialPrices\Checker\Checker;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpySpecialPrices extends Module
{
    public function __construct()
    {
        $this->name = 'kpyspecialprices';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Special Prices', [], 'Modules.Kpyspecialprices.Admin');
        $this->description = $this->trans('Add flags to product and category pages with special price promo', [], 'Modules.Kpyspecialprices.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyspecialprices.Admin');

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

    public function hookDisplayProductPriceBlock(array $params): string
    {
        if ($this->context->controller instanceof ProductControllerCore && $params['type'] === 'old_price') {
            return $this->fetch('module:' . $this->name . '/views/templates/hook/displayProductPriceBlock.tpl', [
                'module_img' => $this->getPathUri() . 'views/img/',
            ]);
        }

        return '';
    }

    public function hookActionPresentProduct(array $params): void
    {
        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray $presentedProduct */
        $presentedProduct = $params['presentedProduct'];

        $specialPrice = Db::getInstance()->getRow(
            "SELECT old_discount, expire 
                FROM " . _DB_PREFIX_ . "kpy_special_price 
                WHERE id_product = {$presentedProduct->id_product} 
                    AND id_product_attribute = {$presentedProduct->id_product_attribute}
                    AND id_shop = {$this->context->shop->id}",
        );

        $presentedProduct->appendArray(['kpy_special_price' => !empty($specialPrice)]);

        if (!empty($specialPrice)) {
            $dateEnd = new \DateTimeImmutable($specialPrice['expire']);
            $presentedProduct->appendArray([
                'kpy_special_price_regular_price' => round((float)$presentedProduct->regular_price_amount * (1 - (float)$specialPrice['old_discount'] / 100), 2),
                'kpy_special_price_old_discount' => (float)$specialPrice['old_discount'],
                'kpy_special_price_expire' => Tools::displayDate($specialPrice['expire']),
                'kpy_special_price_days_left' => $dateEnd->diff(new \DateTimeImmutable())->days,
            ]);
        }
    }

    private function hasSpecialPrice(int $id_product, int $id_product_attribute = 0): bool
    {
        return Checker::hasSpecialPrice($this->context->shop->id, $id_product, $id_product_attribute);
    }

    public function hookActionPresentProductListing(array $params): void
    {
        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray $presentedProduct */
        $presentedProduct = $params['presentedProduct'];

        $presentedProduct->appendArray([
            'kpy_special_price' => $this->hasSpecialPrice($presentedProduct->id_product, $presentedProduct->id_product_attribute),
        ]);

    }

    public function hookActionProductFlagsModifier(array $params): void
    {
        $product = $params['product'];

        $hasSpecialPrice = $this->context->controller instanceof ProductControllerCore
            ? $this->hasSpecialPrice($product['id_product'], $product['id_product_attribute'])
            // en las páginas de categorías que pasaría cuando la combinación por defecto no tenga precio especial...
            : $this->hasSpecialPrice($product['id_product']);

        if ($hasSpecialPrice) {
            $params['flags']['kpy_special_price'] = [
                'type' => 'kpy-special-price',
                'label' => $this->trans('Special price', [], 'Modules.Kpyspecialprices.Shop'),
                'icon' => $this->getPathUri() . 'views/img/special-price.svg',
            ];
        }
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller instanceof ProductControllerCore
            || $this->context->controller instanceof CategoryControllerCore
            || $this->context->controller instanceof IndexControllerCore
            || $this->context->controller->page_name === 'module-kpymanufacturer-landing'
            || $this->context->controller->page_name === 'module-kpyproductreviews-display'
        ) {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css',
                [
                    'media' => 'all',
                    'priority' => 800,
                ]
            );
        }
    }

    public function hookActionFrontControllerSetVariables(): array
    {
        if (!$this->context->controller instanceof ProductControllerCore) {
            return [];
        }

        /** @var ProductCore $product */
        $product = $this->context->controller->getProduct();

        if (!$product->hasCombinations()) {
            return [];
        }

        $sql = "SELECT pac.id_attribute
            FROM " . _DB_PREFIX_ . "product_attribute pa
            INNER JOIN " . _DB_PREFIX_ . "product_attribute_combination pac
                ON pac.id_product_attribute = pa.id_product_attribute
            WHERE pa.id_product = {$product->id_product}
                AND EXISTS (SELECT 1 
                    FROM " . _DB_PREFIX_ . "kpy_special_price sp 
                    WHERE sp.id_product_attribute = pa.id_product_attribute 
                        AND NOW() BETWEEN sp.date_from and sp.expire)
                and not exists (SELECT 1 
                    FROM " . _DB_PREFIX_ . "kpy_product_attribute kpa 
                    WHERE kpa.id_product_attribute = pa.id_product_attribute 
                        and kpa.active = 0)";

        return [
            'icon' => $this->getPathUri() . 'views/img/special-price.svg',
            'attributes_special_price' => array_map(static function (array $row) {
                return $row['id_attribute'];
            }, Db::getInstance()->executeS($sql)),
        ];
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        $product = $params['product'];

        if (!$this->hasSpecialPrice($product->id_product, $product->id_product_attribute)) {
            return '';
        }

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayKpyProductAdditionalInfo.tpl', [
            'date_end' => $this->getDateEndSpecialPrice($product->id_product, $product->id_product_attribute),
            'offer_img' => $this->getPathUri() . 'views/img/special-price-offer.svg',
        ]);
    }

    private function getDateEndSpecialPrice(int $productId, int $productAttributeId): string
    {
        return Tools::displayDate(Db::getInstance()->getValue(
            "SELECT `expire`
                FROM " . _DB_PREFIX_ . "kpy_special_price 
                WHERE id_product = {$productId}
                    AND id_product_attribute = {$productAttributeId}
                    AND NOW() BETWEEN `date_from` AND `expire`
                    AND id_shop = {$this->context->shop->id}"
        ));
    }
}
