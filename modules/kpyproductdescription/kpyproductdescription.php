<?php

if (!defined('_PS_VERSION_')) {
    exit;
}


use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\Module\KpyProductDescription\Install\Installer;

require_once __DIR__ . '/vendor/autoload.php';

class KpyProductDescription extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'kpyproductdescription';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Product Description', [], 'Modules.Kpyproductdescription.Admin');
        $this->description = $this->trans('Personaliza las descripciones de producto', [], 'Modules.Kpyproductdescription.Admin');

        $this->confirmUninstall = $this->trans('¿Seguro que quieres desinstalar el módulo?', [], 'Modules.Kpyproductdescription.Admin');

    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        return (new Installer())->install($this);
    }

    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        return (new Installer())->uninstall($this);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function renderWidget($hookName, array $configuration): string
    {
        return match ($hookName) {
            'description', 'description_short' => $this->renderProductDescription($hookName, $configuration),
            'benefits' => $this->renderProductBenefits((int) $configuration['id_product']),
            'features' => $this->renderProductFeatures((int) $configuration['id_product']),
            'ingredients' => $this->renderProductIngredients((int) $configuration['id_product']),
            'components' => $this->renderProductComponents((int) $configuration['id_product']),
            default => '',
        };
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
    }

    public function renderProductDescription($hookName, array $configuration): string
    {
        $content = $this->getContentByProduct((int)$configuration['id_product']);

        if ($hookName === 'description_short') {
            return $this->fetch(
                'module:' . $this->name . '/views/templates/widget/' .  $content['tpl_short'],
                [
                    'module_img' => $this->_path . 'views/img/',
                    'faq' => $content['faq'],
                ]);
        }

        $idProduct = (int)$configuration['id_product'];

        $benefits = $this->getProductBenefits($idProduct);

        return $this->fetch(
            'module:' . $this->name . '/views/templates/widget/' .  $content['tpl'],
            [
                'module_img' => $this->_path . 'views/img/',
                'faq' => $content['faq'],
                'title_benefits' => $this->getBenefitsTitle($benefits),
                'benefits' => $benefits,
                'ingredients' => $this->getProductIngredients($idProduct),
                'components' => $this->getProductAnalyticalComponents($idProduct),
                'rations_table' => $this->getProductRationsTable($idProduct),
                'es_pienso' => Product::isPienso($idProduct),
                'formatos' => ''
            ]);
    }

    private function getContentByProduct(int $idProduct): array
    {
        $content = json_decode(file_get_contents($this->getLocalPath() . '/content.json'), true);

        foreach ($content['products'] as $product) {
            if ($idProduct === $product['id_product']) {
                return $product;
            }
        }

        return [
            'tpl' => 'default.tpl',
            'tpl_short' => 'default_short.tpl',
            'faq' => [],
        ];
    }

    private function renderProductBenefits(int $idProduct): string
    {
        $benefits = $this->getProductBenefits($idProduct);

        return $this->fetch('module:' . $this->name . '/views/templates/widget/benefits.tpl', [
            'title_benefits' => $this->getBenefitsTitle($benefits),
            'benefits' => $benefits,
        ]);
    }

    private function getProductBenefits(int $idProduct): array
    {
        $results = Db::getInstance()->executeS(
            "select  b.image, bl.name, b.protege
                from `" . _DB_PREFIX_ . "kpy_benefit_product` bp
                inner join `" . _DB_PREFIX_ . "kpy_benefits` b
                    on b.id_benefit = bp.id_benefit
                left join `" . _DB_PREFIX_ . "kpy_benefit_lang` bl
                    on bl.id_benefit = bp.id_benefit and bl.id_lang = {$this->context->language->id}
                where bp.id_product = $idProduct"
        );

        if (empty($results)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'image' => $this->_path . 'views/img/benefits/' . $item['image'],
                'name' => $item['name'],
                'protege' => (int)$item['protege'],
            ];
        }, $results);
    }

    private function getBenefitsTitle(array $benefits): string
    {
        foreach ($benefits as $benefit) {
            if ($benefit['protege']) {
                return $this->trans('Protects against', [], 'Modules.Kpyproductdescription.Shop');
            }
        }

        return $this->trans('Key Benefits', [], 'Modules.Kpyproductdescription.Shop');
    }

    private function renderProductFeatures(): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/widget/features.tpl');
    }

    private function renderProductIngredients(int $idProduct): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/widget/ingredients.tpl', [
            'ingredients' => $this->getProductIngredients($idProduct),
        ]);
    }

    private function getProductIngredients(int $idProduct): string
    {
        return Db::getInstance()->getValue(
            "SELECT text 
                FROM `" . _DB_PREFIX_ . "kpy_product_ingredients` 
                WHERE `id_product` = $idProduct and `id_lang` = " . $this->context->language->id
        ) ?: '';
    }

    private function renderProductComponents(int $idProduct): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/widget/components.tpl', []);
    }

    private function getProductAnalyticalComponents(int $idProduct): array
    {
        $results = Db::getInstance()->executeS(
            "select cl.name, pc.percentage
                    from " . _DB_PREFIX_ . "kpy_product_component pc
                    inner join " . _DB_PREFIX_ . "kpy_component_lang cl 
                        on cl.id_component = pc.id_component 
                               and cl.id_lang = {$this->context->language->id}
                    where pc.id_product = $idProduct"
        );

        if (empty($results)) {
            return [];
        }

        return array_map(function (array $row) {
            return ['name' => $row['name'], 'value' => $row['percentage']];
        }, $results);
    }

    private function getProductRationsTable(int $idProduct): string
    {
        return Db::getInstance()->getValue(
            "SELECT `table` FROM `" . _DB_PREFIX_ . "kpy_product_rations_table` WHERE id_product = $idProduct"
        ) ?: '';
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller->php_self === 'product') {
            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-css',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css'
            );

            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-benefit-css',
                'modules/' . $this->name . '/views/css/' . $this->name . '-benefits.css'
            );

            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-features-css',
                'modules/' . $this->name . '/views/css/' . $this->name . '-features.css'
            );

            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-ingredients-css',
                'modules/' . $this->name . '/views/css/' . $this->name . '-ingredients.css'
            );

            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-components-css',
                'modules/' . $this->name . '/views/css/' . $this->name . '-components.css'
            );

            $this->context->controller->registerStylesheet(
                'module-' . $this->name . '-ration-css',
                'modules/' . $this->name . '/views/css/' . $this->name . '-ration.css'
            );
        }
    }

}