<?php

namespace PrestaShop\Module\KpyManufacturer\Search;

use Context;
use Db;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use Product;
use Shop;

class KpyLandingSearchProvider
{
    private ProductSearchContext $context;

    public function __construct(?ProductSearchContext $context = null)
    {
        $this->context = $context ?? new ProductSearchContext(Context::getContext());
    }

    public function getProducts(array $products): ProductSearchResult
    {
        $productsRaw = $this->getProductsProperties($products);

        $productsPet = $this->getPetTypeByProduct($products);

        foreach ($productsRaw as &$productRaw) {
            $productRaw['pet'] = $productsPet[$productRaw['id_product']] ?? 'all';
        }

        $result = new ProductSearchResult();
        $result
            ->setProducts($productsRaw)
            ->setTotalProductsCount(count($productsRaw));

        return $result;
    }

    private function getProductsProperties(array $products): array
    {
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
					product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity,IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute,
					pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
					pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
					m.`name` AS manufacturer_name, p.`id_manufacturer` as id_manufacturer,
					image_shop.`id_image` id_image, il.`legend`,
					t.`rate`'
            . ' FROM `' . _DB_PREFIX_ . 'product` p 
				' . Shop::addSqlAssociation('product', 'p', false)
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
							ON (p.`id_product` = product_attribute_shop.`id_product` 
							    AND product_attribute_shop.`default_on` = 1 
							    AND product_attribute_shop.id_shop=' . $this->context->getIdShop() . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . $this->context->getIdLang() . Shop::addSqlRestrictionOnLang('pl') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . $this->context->getIdShop() . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . $this->context->getIdLang() . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON (product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group`)
					AND tr.`id_country` = ' . Context::getContext()->country->id . '
					AND tr.`id_state` = 0
				LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (t.`id_tax` = tr.`id_tax`)
				' . Product::sqlStock('p', 0)
            . ' WHERE p.`id_product`  IN (' . implode(',', $products) . ') 
            ORDER BY FIELD(p.`id_product`, ' . implode(',', $products) . ')';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return [];
        }

        return Product::getProductsProperties($this->context->getIdLang(), $result);
    }

    private function getPetTypeByProduct(array $productsId): array
    {
        if (empty($productsId)) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            "SELECT fp.id_product, fvl.value
                    FROM `" . _DB_PREFIX_ . "feature_product` fp
                    INNER JOIN `" . _DB_PREFIX_ . "feature_value_lang` fvl
                        ON fvl.id_feature_value = fp.id_feature_value
                            AND fvl.id_lang = {$this->context->getIdLang()}
                    WHERE fp.id_product IN (" . implode(',', $productsId) . ")
                        AND fp.id_feature = 14"
        );

        $productsPets = [];

        foreach ($rows as $row) {
            $productsPets[$row['id_product']] = mb_strtolower(trim($row['value']));
        }

        return $productsPets;
    }
}