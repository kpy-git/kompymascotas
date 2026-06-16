<?php

declare(strict_types=1);

use PrestaShop\Module\KpyGoogleTags\Configuration\KpyGoogleTagsConfiguration;
use PrestaShop\Module\KpyGoogleTags\Install\Installer;
use PrestaShop\Module\KpySpecialPrices\Checker\Checker;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyGoogleTags extends Module
{
    public function __construct()
    {
        $this->name = 'kpygoogletags';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';

        $this->ps_versions_compliancy = [
            'min' => '9.1',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Google Tags', [], 'Modules.Kpygoogletags.Admin');
        $this->description = $this->trans('Handle Google Tags.', [], 'Modules.Kpygoogletags.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpygoogletags.Admin');
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

    public function hookDisplayHeader(array $params): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayHeader.tpl');
    }

    public function hookDisplayAfterBodyOpeningTag(array $params): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayAfterBodyOpeningTag.tpl');
    }

    public function hookDisplayBeforeBodyClosingTag(array $params): string
    {
        return '';
    }

    public function hookDisplayOrderConfirmation(array $params): string
    {
        /** @var Order $order */
        $order = $params['order'];

        $items = $this->getProductsFromOrder($order->id);

        $gaPurchase = [
            'transaction_id' => $order->id,
            'currency' => 'EUR',
            'value' => round((float)$order->total_paid_tax_incl, 2),
            'items' => $items,
        ];

        $gaPaymentInfo = [
            'payment_type' => $this->getPaymentForGA($order->module),
        ];

        $coupon = $this->getCartRuleTypeForOrder($order->id);
        if ($coupon !== '') {
            $gaPurchase['coupon'] = $coupon;
        }

        $adsPurchase = [
            'event' => 'purchase',
            'transactionId' => $order->id,
            'transactionAffiliation' => $this->context->shop->domain,
            'transactionTotal' => round((float)$order->total_paid_tax_incl, 2),
            'transactionProducts' => array_map(static function ($product) {
                return [
                    'sku' => $product['item_id'],
                    'name' => $product['item_name'],
                    'price' => $product['price'],
                    'quantity' => $product['quantity'],
                    'benefit' => $product['benefit'],
                ];
            }, $items),
            'transactionBenefit' => round(array_reduce($items, static function ($carry, $product) {
                return $carry + $product['benefit'];
            }, 0), 2)
        ];

        if (KpyGoogleTagsConfiguration::LOG_ALL || KpyGoogleTagsConfiguration::LOG_PURCHASE) {
            file_put_contents(_PS_LOG_DIR_ . '/purchase.json', ",\n" . json_encode($gaPurchase, JSON_PRETTY_PRINT));
        }

        $this->context->smarty->assign([
			'gaPurchase' => json_encode($gaPurchase),
			'gaPaymentInfo' => json_encode($gaPaymentInfo),
			'adsPurchase' => json_encode($adsPurchase),
		]);

        return $this->fetch("module:" . $this->name . "/views/templates/hook/displayOrderConfirmation.tpl");
    }

    private function getProductsFromOrder(int $id_order): array
    {
        $products = Db::getInstance()->executeS(
            "select CONCAT_WS('-', od.product_id, od.product_attribute_id) as sku, od.product_quantity,
				od.unit_price_tax_incl, CONCAT_WS(' ', pl.name, al.name) as name, m.name as manufacturer,
				p.id_category_default, od.product_id, od.product_attribute_id, od.original_wholesale_price,
				od.unit_price_tax_excl
			from " . _DB_PREFIX_ . "order_detail od
			inner join " . _DB_PREFIX_ . "product p
				on p.id_product = od.product_id
			inner join " . _DB_PREFIX_ . "product_lang pl
				on pl.id_product = od.product_id and pl.id_shop=od.id_shop and pl.id_lang=1
			inner join " . _DB_PREFIX_ . "manufacturer m
				on m.id_manufacturer = p.id_manufacturer
			left join " . _DB_PREFIX_ . "product_attribute_shop pas
				on pas.id_product = od.product_id and pas.id_product_attribute=od.product_attribute_id and pas.id_shop = od.id_shop
			left join " . _DB_PREFIX_ . "product_attribute_combination pac
				on pac.id_product_attribute = pas.id_product_attribute
			left join " . _DB_PREFIX_ . "attribute_lang al
				on al.id_attribute = pac.id_attribute and al.id_lang = pl.id_lang
			where od.id_order = " . $id_order);

        if (empty($products)) {
            return [];
        }

        $index = 0;
        $gaItems = [];

        foreach ($products as $product) {
            $gaItems[] = array_merge([
                'item_id' => $product['sku'],
                'item_name' => trim(str_replace('"', "", $product['name'])),
                'index' => $index++,
                'quantity' => (int)$product['product_quantity'],
                'currency' => 'EUR',
                'item_brand' => $product['manufacturer'],
                'price' => round((float)$product['unit_price_tax_incl'], 2),
                'benefit' => max(0, round((float)$product['unit_price_tax_excl'] - (float)$product['original_wholesale_price'], 2)),
            ],
                $this->getCategoriesForGA($product['id_category_default']),
                $this->getProductFeatures($product['product_id']),
                $this->getSpecialPriceTagIfNeeded((int)$product['product_id'], (int)$product['product_attribute_id']),
                // $this->getProductCoupon($product['product_id'], $product['attr'])
            );
        }

        return $gaItems;
    }

    public function hookDisplayProductAdditionalInfo(array $params): string
    {
        /** @var \PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray $product */
        $product = $params['product'];

        $sku = $product['id'] . '-' . $product['id_product_attribute'];

        /** TODO - cuando un producto tenga un cupón aplicado por defecto el precio final hay que calcularlo */
        $price = $product->price_amount;

        $gaViewItem = [
            'currency' => 'EUR',
            'value' => $price,
            'items' => [
                array_merge([
                    'item_id' => $sku,
                    'item_name' => trim(str_replace('"', "", $product->title)),
                    'item_brand' => $product->manufacturer_name,
                    'currency' => 'EUR',
                    'price' => $price,
                    'quantity' => 1,
                ],
                    $this->getCategoriesForGA($product->id_category_default),
                    $this->transformProductFeatures($product->getFeatures()),
                    $this->getSpecialPriceTagIfNeeded($product->id, $product->id_product_attribute),
                    //$this->getProductCoupon($product->id, $product->id_product_attribute)
                ),
            ],
        ];

        if (KpyGoogleTagsConfiguration::LOG_ALL || KpyGoogleTagsConfiguration::LOG_VIEW_ITEM) {
            file_put_contents(_PS_LOG_DIR_ . 'ga_view_item.json', json_encode($gaViewItem, JSON_PRETTY_PRINT), FILE_APPEND | LOCK_EX);
        }

        $this->context->smarty->assign([
            'gaViewItem' => json_encode($gaViewItem),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayFooterProduct.tpl');
    }

    private function getCombinationName(int $id_product_attribute): string
    {
        return Db::getInstance()->getValue(
            "select GROUP_CONCAT(al.name SEPARATOR ' ') as name
				from " . _DB_PREFIX_ . "product_attribute pa
				left join " . _DB_PREFIX_ . "product_attribute_combination pac 
				    on pac.id_product_attribute = pa.id_product_attribute
				left join " . _DB_PREFIX_ . "attribute_lang al 
				    on al.id_attribute = pac.id_attribute and al.id_lang = 1
				where pa.id_product_attribute = {$id_product_attribute} 
				group by pa.id_product_attribute"
        );
    }

    private function getCategoriesForGA(int $id_category): array
    {
        $categoryTree = $this->getCategoryTree($id_category);
        $categories = array_slice($categoryTree, 0, 5);

        $index = 0;
        $categoriesGA = [];
        foreach ($categories as $category) {
            $key = 'item_category' . ((++$index > 1) ? $index : '');
            $categoriesGA[$key] = $category;
        }

        return $categoriesGA;
    }

    private function getCategoryTree(int $id_category): array
    {
        $categoryDefault = new Category($id_category, 1);

        if (!Validate::isLoadedObject($categoryDefault)) {
            return [];
        }

        $categories = Db::getInstance()->executeS(
            "select c.id_category, cl.name
				from " . _DB_PREFIX_ . "category c
				inner join " . _DB_PREFIX_ . "category_lang cl
					on cl.id_category = c.id_category and cl.id_shop=1 and cl.id_lang = 1
				where c.active=1 and c.id_category > 1000
					and c.nleft < {$categoryDefault->nleft} and nright > {$categoryDefault->nright}
				order by c.nleft"
        );

        return array_merge(
            array_map(function ($category) {
                return trim($category['name']);
            }, $categories),
            [trim($categoryDefault->name)]
        );
    }

    private function transformProductFeatures(array $productFeatures): array
    {
        $gaKeys = [
            'Mascota' => 'mascota',
            'Tipo de Producto' => 'tipo_producto',
            'Edad' => 'edad',
            'Textura de alimentos' => 'textura',
            'Tamaño' => 'size',
            'Ingrediente principal' => 'ingrediente_principal',
            'Raza' => 'raza',
            'Necesidades específicas' => 'necesidades_especificas',
            'Opción nutricional' => 'opcion_nutricional',
        ];

        $features = [];
        foreach ($productFeatures as $feature) {
            if (!array_key_exists($feature['name'], $gaKeys)) {
                continue;
            }

            $features[$gaKeys[$feature['name']]] = $feature['value'];
        }

        return $features;
    }

    private function getSpecialPriceTagIfNeeded(int $productId, int $attr): array
    {
        if (Checker::hasSpecialPrice($this->context->shop->id, $productId, $attr)) {
            return [
                'promotion_name' => 'Special Price'
            ];
        }

        return [];
    }

    private function getProductFeatures(int $productId): array
    {
        return $this->transformProductFeatures(Db::getInstance()->executeS(
            "SELECT fl.name, GROUP_CONCAT(fvl.value separator ', ') as value
				FROM " . _DB_PREFIX_ . "feature_product fp
				LEFT JOIN " . _DB_PREFIX_ . "feature_lang fl on fl.id_feature = fp.id_feature  AND fl.id_lang = 1
				LEFT JOIN " . _DB_PREFIX_ . "feature_value_lang fvl on fvl.id_feature_value = fp.id_feature_value ANd fvl.id_lang = 1
				WHERE fp.id_product = {$productId}
				GROUP BY fl.name"
        ));
    }

    public function hookDisplayShoppingCartFooter(array $params): string
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        $products = $cart->getProducts();

        if (empty($products)) {
            return '';
        }

        $gaViewCart = [
            'currency' => 'EUR',
            'value' => $cart->getOrderTotal(),
            'items' => $this->getItemArrayViewCartWithProducts($products),
        ];

        if (KpyGoogleTagsConfiguration::LOG_ALL || KpyGoogleTagsConfiguration::LOG_VIEW_CART) {
            file_put_contents(_PS_LOG_DIR_ . '/ga_view_cart.log', json_encode($gaViewCart, JSON_PRETTY_PRINT));
        }

        $this->smarty->assign([
			'gaViewCart' => json_encode($gaViewCart),
		]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayShoppingCartFooter.tpl');
    }

    private function getItemArrayViewCartWithProducts(array $products): array
    {
        $items = [];
        $index = 0;

        foreach ($products as $product) {
            $items[] = array_merge([
                'item_id' => $product['id_product'] . '-' . $product['id_product_attribute'],
                'item_name' => trim(str_replace('"', "", $product['name'] . ' ' . ($product['attributes_small'] ?? ''))),
                'index' => $index++,
                'quantity' => (int)$product['quantity'],
                'currency' => 'EUR',
                'item_brand' => $product['manufacturer_name'],
                'price' => round((float)$product['price_with_reduction'], 2),
            ],
                $this->getCategoriesForGA($product['id_category_default']),
                $this->getProductFeatures($product['id_product']),
                $this->getSpecialPriceTagIfNeeded((int)$product['id_product'], (int)$product['id_product_attribute']),
            //$this->getProductCoupon($product['id_product'], $product['attr'])
            );
        }

        return $items;
    }

    private function getPaymentForGA(string $module): string
    {
        $payments = [
            'tarjeta' => ['redsysoficial', 'redsys', 'bbva'],
            'paypal' => ['Paypal', 'PayPal', 'paypal', 'ps_checkout'],
            'crm' => ['kpycashondelivery', 'codfee', 'cashondeliveryplus'],
            'multibanco' => ['hipay', 'comprafacil', 'wfxcomprafacil'],
            'grt' => ['Pedido gratuito', 'Encomenda grátis'],
            'transferencia' => ['bankwire', 'ps_wirepayment'],
        ];

        foreach ($payments as $type => $modules) {
            if (in_array($module, $modules)) {
                return $type;
            }
        }

        return 'otro';
    }

    private function getCartRuleTypeForOrder(int $id_order): string
    {
        $cartRule = Db::getInstance()->getRow(
            "SELECT cr.code, crl.name, cr.reduction_amount,
				case when gv.id_cart_rule is not null then 'si' else 'no' end as puntos
			FROM " . _DB_PREFIX_ . "order_cart_rule ocr
			INNER JOIN " . _DB_PREFIX_ . "cart_rule cr ON cr.id_cart_rule = ocr.id_cart_rule
			INNER JOIN " . _DB_PREFIX_ . "cart_rule_lang crl on crl.id_cart_rule = cr.id_cart_rule and crl.id_lang=3
			LEFT JOIN " . _DB_PREFIX_ . "gamifications_voucher gv on gv.id_cart_rule = ocr.id_cart_rule
			WHERE ocr.id_order={$id_order}"
        );

        if (!is_array($cartRule) || empty($cartRule)) {
            return '';
        }

        if (str_starts_with($cartRule['code'], 'PET')) {
            return 'PET';
        }

        if (str_starts_with($cartRule['code'], 'HB20')) {
            return 'BIRTHDAY';
        }

        if ($cartRule['puntos'] === 'si') {
            return sprintf('POINTS %d', number_format($cartRule['reduction_amount']));
        }


        return '';
    }
}
