<?php

namespace PrestaShop\Module\KpyManufacturer\LandingBuilder;

use Category;
use Db;
use Image;
use Manufacturer;
use PrestaShop\Module\KpyManufacturer\Search\KpyLandingSearchProvider;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use ProductAssembler;
use ProductPresenterFactory;

class GenericLandingBuilder extends AbstractLandingBuilder
{

    public function isRightManufacturer(int $manufacturerId): bool
    {
        return (int)Db::getInstance()->getValue(
                "SELECT COUNT(*) 
                FROM `" . _DB_PREFIX_ . "kpy_manufacturer_landing` 
                WHERE `id_manufacturer` = " . $manufacturerId) > 0;
    }

    public function getTemplate(): string
    {
        return 'landing.tpl';
    }

    public function getSmartyVars(): array
    {
        $data = Db::getInstance()->getRow(
            "select ml.id_landing, mll.title, m.name, mll.subtitle, ml.products,
                       GROUP_CONCAT(mlp.pill SEPARATOR '~') as pills
                from " . _DB_PREFIX_ . "kpy_manufacturer_landing ml
                         inner join " . _DB_PREFIX_ . "manufacturer m
                                    on m.id_manufacturer = ml.id_manufacturer
                         inner join " . _DB_PREFIX_ . "kpy_manufacturer_landing_lang mll
                                on ml.id_landing = mll.id_landing
                                and mll.id_lang = {$this->context->language->id}
                         left join " . _DB_PREFIX_ . "kpy_manufacturer_landing_pill mlp
                                   on mlp.id_landing = ml.id_landing 
                                        and mlp.id_lang = mll.id_lang
                where ml.id_manufacturer = {$this->manufacturerId}
                group by ml.id_landing"
        );

        $categoryRelated = Manufacturer::getCategoryRelatedByManufacturer($this->manufacturerId);

        return [
            'category_related_url' => $this->context->link->getCategoryLink((new Category($categoryRelated))),
            'banners_path' => $this->module->getPathUri() . 'views/img/landing/' . $categoryRelated . '/',
            'brand_name' => $data['name'],
            'title' => $data['title'] ?? $data['name'],
            'subtitle' => $data['subtitle'],
            'brand_image' => $this->context->link->getManufacturerImageLink($this->manufacturerId),
            'pills' => explode('~', $data['pills'] ?? ''),
            'categories' => $this->getLandingCategoriesByManufacturer($this->manufacturerId),
            'banners' => $this->getBannersByManufacturer($this->manufacturerId),
            'products' => $this->getProductsForTemplate(explode(',', $data['products'] ?? '')),
            'videos' => $this->getVideosByManufacturer($this->manufacturerId),
            'average_grade' => $this->getAverageGradeComments(),
            'comments' => $this->getTopComments(),
        ];
    }

    public function getLandingCategoriesByManufacturer(int $manufacturerId): array
    {
        $results = Db::getInstance()->executeS(
            "SELECT id_category, pet, title, subtitle
                FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing_category mlc
                where mlc.id_landing = (SELECT id_landing FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing ml WHERE ml.id_manufacturer = {$manufacturerId})
                ORDER BY mlc.id_landing_category"
        );

        $categories = [];

        foreach ($results as $result) {
            $category = new Category($result['id_category']);
            if (!$category->active) {
                continue;
            }

            $productId = $category->id_product_image_cover ?? Category::getCoverProductId($result['id_category']);

            $categories[$result['pet']][] = [
                'title' => $result['title'] ?? $category->getName($this->context->language->id),
                'subtitle' => $result['subtitle'] ?? substr(Category::getDescriptionClean($category->description[$this->context->language->id] ?? ''), 0, 100),
                'image' => $this->context->link->getImageLink('small-default', $productId . '-' . Image::getCover($productId)['id_image']),
                'url' => $this->context->link->getCategoryLink($category),
            ];
        }

        return array_change_key_case($categories);
    }

    public function getBannersByManufacturer(int $manufacturerId): array
    {
        return Db::getInstance()->executeS(
            "SELECT image, url, description
            FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing_banner
            WHERE id_landing = (SELECT id_landing FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing ml WHERE ml.id_manufacturer = {$manufacturerId})
                AND id_shop = {$this->context->shop->id}"
        ) ?: [];
    }

    private function getProductsForTemplate(array $id_products): array
    {
        $searchProvider = new KpyLandingSearchProvider();

        $result = $searchProvider->getProducts($id_products);

        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();

        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        // Now, we can present the products for the template.
        $products_for_template = [];
        $rawProducts = $result->getProducts();

        // Assemble & present in bulk or separately, depending on core version
        $assembleInBulk = method_exists($assembler, 'assembleProducts');
        if ($assembleInBulk) {
            $rawProducts = $assembler->assembleProducts($rawProducts);
        }
        foreach ($rawProducts as $rawProduct) {
            $productLazyArray = $presenter->present(
                $presentationSettings,
                ($assembleInBulk ? $rawProduct : $assembler->assembleProduct($rawProduct)),
                $this->context->language
            );

            $products_for_template[] = $productLazyArray;
        }

        return $products_for_template;
    }

    public function getVideosByManufacturer(int $manufacturerId): array
    {
        return Db::getInstance()->executeS(
            "SELECT IFNULL(title, '') as title, IFNULL(subtitle, '') as subtitle, name, url
                FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing_video
                WHERE id_landing = (SELECT id_landing FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing ml WHERE ml.id_manufacturer = {$manufacturerId})
                    AND id_shop = {$this->context->shop->id}"
        ) ?: [];
    }

    public function getStyleSheetsFiles(): array
    {
        return ['landing', 'header', 'gamas', 'slider', 'products', 'comments', 'videos', 'footer',];
    }

    public function getScriptsFiles(): array
    {
        return ['kpylanding'];
    }

    public function includeSliderScripts(): bool
    {
        return (int)Db::getInstance()->getValue(
                "SELECT COUNT(*) 
                    FROM `" . _DB_PREFIX_ . "kpy_manufacturer_landing_banner` 
                    WHERE id_shop = {$this->context->language->id} AND id_landing = (SELECT id_landing 
                        FROM " . _DB_PREFIX_ . "kpy_manufacturer_landing ml 
                        WHERE ml.id_manufacturer = {$this->manufacturerId})"
            ) > 1;
    }
}