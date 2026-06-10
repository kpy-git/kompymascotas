<?php

declare(strict_types=1);

use PrestaShop\Module\KpyManufacturer\Form\Modifier\ManufacturerFormModifier;
use PrestaShop\Module\KpyManufacturer\Install\Installer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyManufacturer extends Module
{
    public function __construct()
    {
        $this->name = 'kpymanufacturer';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Manufacturers', [], 'Modules.Kpymanufacturer.Admin');
        $this->description = $this->trans('Handle landing of Brands', [], 'Modules.Kpymanufacturer.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpymanufacturer.Admin');

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

    public function hookActionFrontControllerSetVariables(): array
    {
        if ($this->context->controller->php_self !== 'product') {
            return [];
        }

        $product = $this->context->controller->getProduct();

        $manufacturer = new Manufacturer((int)$product->id_manufacturer);

        return [
            "category_manufacturer" => $manufacturer->getCategoryRelated(),
            "manufacturer_rewrite" => $this->context->link->getBaseLink() . 'marcas/' . $manufacturer->getLinkRewrite(),
        ];
    }

    public function hookModuleRoutes(array $params): array
    {
        // $routesManufacturers = $this->generateRoutesByManufacturers(Manufacturer::getAllManufacturersLinkRewriteById());
        // file_put_contents(_PS_LOG_DIR_ . '/manufacturers.json', json_encode($routesManufacturers, JSON_PRETTY_PRINT));

        return [
            'module-' . $this->name . '-landing' => [
                'rule' => 'marcas/{brand}',
                'keywords' => [
                    'brand' => [
                        'regexp' => '.*',
                        'param' => 'brand',
                    ]
                ],
                'controller' => 'landing',
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ]
            ],
            'module-' . $this->name . '-brands' => [
                'rule' => 'marcas',
                'keywords' => [],
                'controller' => 'brands',
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                ],
            ]
        ];
    }

    public function generateRoutesByManufacturers(array $manufacturers): array
    {
        $routes = [];
        foreach ($manufacturers as $manufacturer) {
            $routes[sprintf("module-%s-brand-%s", $this->name, Tools::str2url($manufacturer['name']))] = [
                'rule' => $manufacturer['link_rewrite'],
                'keywords' => [],
                'controller' => 'landing',
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                    'brand' => $manufacturer['link_rewrite'],
                ],
            ];
        }

        return $routes;
    }

    public function hookActionManufacturerFormBuilderModifier(array $params): void
    {
        /* De esta forma no podemos controlar el lugar en el que poner el nuevo campo, lo añade siempre al final

         $formBuilder = $params['form_builder'];

        $formBuilder->add('link_rewrite', TextType::class, [
            'required' => true,
            'label' => $this->trans('Link Rewrite', [], 'Modules.Kpymanufacturer.Admin'),
        ]);

        $manufacturerId = $params['id'];
        $params['data']['link_rewrite'] = Manufacturer::getLinkRewriteById($manufacturerId);

        $formBuilder->setData($params['data']);
        */

        /** @var ManufacturerFormModifier $manufacturerFormModifier */
        $manufacturerFormModifier = $this->get(ManufacturerFormModifier::class);


        $rewrite = '';
        $categoryRelated = 0;

        // cuando se crea una nueva marca todavía no se conoce el ID, se dejan los valores por defecto
        if (isset($params['id'])) {
            $rewrite = Manufacturer::getLinkRewriteById((int)$params['id']);
            $categoryRelated = Manufacturer::getCategoryRelatedByManufacturer((int)$params['id']);
        }

        $manufacturerFormModifier->addLinkRewriteField($rewrite, $params['form_builder']);
        $manufacturerFormModifier->addCategoryRelatedField($categoryRelated, $params['form_builder']);
    }

    public function hookActionAfterUpdateManufacturerFormHandler(array $params): void
    {
        // file_put_contents(_PS_LOG_DIR_ . 'manufacturer_form.json', json_encode($params, JSON_PRETTY_PRINT));
        $this->updateManufacturerRewrite((int)$params['id'], $params['form_data']['link_rewrite'] ?? '');

        if (isset($params['form_data']['category_related']) && ctype_digit($params['form_data']['category_related'])) {
            $this->updateManufacturerCategoryRelated((int)$params['id'], (int)$params['form_data']['category_related']);
        }
    }

    public function hookActionAfterCreateManufacturerFormHandler(array $params): void
    {
        $this->updateManufacturerRewrite((int)$params['id'], $params['form_data']['link_rewrite'] ?? '');

        // TODO - Comprobar que es una categoría válida.
        if (isset($params['form_data']['category_related']) && ctype_digit($params['form_data']['category_related'])) {
            $this->updateManufacturerCategoryRelated((int)$params['id'], (int)$params['form_data']['category_related']);
        }
    }

    public function updateManufacturerRewrite(int $manufacturerId, string $rewrite): bool
    {
        if ((int)Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "kpy_manufacturer WHERE id_manufacturer = {$manufacturerId}") === 0) {
            return Db::getInstance()->insert(
                'kpy_manufacturer',
                ['id_manufacturer' => $manufacturerId, 'link_rewrite' => $rewrite]
            );
        }

        return Db::getInstance()->update(
            'kpy_manufacturer',
            ['link_rewrite' => Tools::str2url($rewrite)],
            'id_manufacturer = ' . $manufacturerId
        );
    }

    public function updateManufacturerCategoryRelated(int $manufacturerId, int $categoryId): bool
    {
        if ((int)Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "kpy_manufacturer WHERE id_manufacturer = {$manufacturerId}") === 0) {
            return Db::getInstance()->insert(
                'kpy_manufacturer',
                ['id_manufacturer' => $manufacturerId, 'id_catetory_related' => $categoryId]
            );
        }

        return Db::getInstance()->update(
            'kpy_manufacturer',
            ['id_category_related' => $categoryId],
            'id_manufacturer = ' . $manufacturerId
        );
    }
}
