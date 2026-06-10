<?php

declare(strict_types=1);

use PrestaShop\Module\KpyProductFlags\Install\Installer;
use PrestaShopBundle\Controller\Admin\Sell\Catalog\Product\ProductController;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyProductFlags extends Module
{
    public function __construct()
    {
        $this->name = 'kpyproductflags';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Product Flags', [], 'Modules.Kpyproductflags.Admin');
        $this->description = $this->trans('Handler for products flags', [], 'Modules.Kpyproductflags.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyproductflags.Admin');

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

    public function hookActionProductFlagsModifier(array $params): void
    {
        $product = $params['product'];

        $flags = $this->context->controller instanceof ProductControllerCore
            ? $this->getProductAttributeFlag((int)$product['id_product'], (int)$product['id_product_attribute'])
            : $this->getProductFlags((int)$product['id_product']);

        if (!empty($flags)) {
            foreach ($flags as $flag) {
                $typeSanitized = filter_var(mb_strtolower(trim($flag['type'])), FILTER_SANITIZE_URL);

                $params['flags'][$typeSanitized] = [
                    'type' => $typeSanitized,
                    'label' => $flag['name'],
                    'icon' => $this->getPathUri() . 'views/img/' . ($flag['icon'] ?? sprintf('flag_%d.svg', $flag['id_flag'])),
                    'color' => $flag['color'] ?? '',
                    'background' => $flag['bg_color'] ?? '',
                ];
            }
        }
    }

    private function getProductFlags(int $productId): array
    {
        $results = Db::getInstance()->executeS(
            "SELECT pf.id_flag, fl.name, f.color, f.bg_color, f.icon, f.type
                FROM " . _DB_PREFIX_ . "kpy_product_flag pf
                INNER JOIN " . _DB_PREFIX_ . "kpy_flag f
                    ON f.id_flag = pf.id_flag
                INNER JOIN " . _DB_PREFIX_ . "kpy_flag_lang fl ON fl.id_flag = pf.id_flag
                    AND fl.id_lang = {$this->context->language->id}
                WHERE id_product = $productId
                    and pf.active = 1
                    and ((pf.date_begin IS NULL AND pf.date_end IS NULL) OR
                        (pf.date_begin IS NULL AND NOW() < pf.date_end) OR
                        (pf.date_end IS NULL AND NOW() >pf.date_begin) OR
                        (NOW() BETWEEN pf.date_begin AND pf.date_end))
                ORDER BY f.id_flag"
        );

        // solo pondremos el primer flag que encuentre en el caso de un producto tenga varios flags (las combinaciones pueden tener diferentes)
        return !empty($results) ? [$results[0]] : [];
    }

    private function getProductAttributeFlag(int $productId, int $productAttributeId): array
    {
        return Db::getInstance()->executeS(
            "SELECT pf.id_flag, fl.name, f.color, f.bg_color, f.icon, f.type
                FROM " . _DB_PREFIX_ . "kpy_product_flag pf
                INNER JOIN " . _DB_PREFIX_ . "kpy_flag f
                    ON f.id_flag = pf.id_flag
                INNER JOIN " . _DB_PREFIX_ . "kpy_flag_lang fl ON fl.id_flag = pf.id_flag
                    AND fl.id_lang = {$this->context->language->id}
                WHERE pf.id_product = $productId AND pf.id_product_attribute = $productAttributeId
                    and pf.active = 1
                    and ((pf.date_begin IS NULL AND pf.date_end IS NULL) OR
                        (pf.date_begin IS NULL AND NOW() < pf.date_end) OR
                        (pf.date_end IS NULL AND NOW() >pf.date_begin) OR
                        (NOW() BETWEEN pf.date_begin AND pf.date_end))"
        ) ?: [];
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller instanceof ProductControllerCore
            || $this->context->controller instanceof CategoryControllerCore
            || $this->context->controller instanceof IndexControllerCore
            || $this->context->controller->page_name === 'module-kpymanufacturer-landing'
        ) {
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

    /** Para mostrar los flags en las combinaciones de la página de producto */
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

        return [
            'attributes_flag' => array_reduce($this->getAttributesFlag($product->id_product), function (array $flags, array $flag) {
                $flags[$flag['id_attribute']] = [
                    'name' => $flag['name'],
                    'background' => $flag['bg_color'] ?? '',
                    'icon' => $this->getPathUri() . 'views/img/' . ($flag['icon'] ?? sprintf('flag_%d.svg', $flag['id_flag'])),
                ];
                return $flags;
            }, []),
        ];
    }

    private function getAttributesFlag(int $productId): array
    {
        return Db::getInstance()->executeS(
            "SELECT pac.id_attribute, f.id_flag, f.bg_color, f.icon, f.color, f.type, fl.name
            FROM " . _DB_PREFIX_ . "product_attribute pa
            INNER JOIN " . _DB_PREFIX_ . "product_attribute_combination pac
                ON pac.id_product_attribute = pa.id_product_attribute
            INNER JOIN " . _DB_PREFIX_ . "kpy_product_flag pf
                ON pf.id_product = pa.id_product AND pf.id_product_attribute = pa.id_product_attribute
            INNER JOIN " . _DB_PREFIX_ . "kpy_flag f
                ON f.id_flag = pf.id_flag
            INNER JOIN " . _DB_PREFIX_ . "kpy_flag_lang fl ON fl.id_flag = pf.id_flag
                AND fl.id_lang = {$this->context->language->id}
            WHERE pa.id_product = {$productId}
                and pf.active = 1
                and ((pf.date_begin IS NULL AND pf.date_end IS NULL) OR
                    (pf.date_begin IS NULL AND NOW() < pf.date_end) OR
                    (pf.date_end IS NULL AND NOW() >pf.date_begin) OR
                    (NOW() BETWEEN pf.date_begin AND pf.date_end))
                and not exists (SELECT 1
                    FROM " . _DB_PREFIX_ . "kpy_product_attribute kpa
                    WHERE kpa.id_product_attribute = pa.id_product_attribute
                        and kpa.active = 0)"
        );
    }
}
