<?php

declare(strict_types=1);

use PrestaShopBundle\Install\SqlLoader;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class KpyMenu extends Module implements WidgetInterface
{
    private string $template;

    private string $templateMobile;

    public function __construct()
    {
        $this->name = 'kpymenu';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('KpyMenu', [], 'Modules.Kpymenu.Admin');
        $this->description = $this->trans('Main menu', [], 'Modules.Kpymenu.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpymenu.Admin');

        $this->template = 'module:' . $this->name . '/views/templates/hook/displayTop.tpl';
        $this->templateMobile = 'module:' . $this->name . '/views/templates/hook/displayTopMobile.tpl';

    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->executeSqlFromFile($this->getLocalPath() . 'src/sql/install.sql')
            && $this->registerHook('displayTop')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    /**
     * @param string $filepath
     *
     * @return bool
     */
    private function executeSqlFromFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return true;
        }

        $sqlLoader = new SqlLoader();
        $sqlLoader->setMetaData([
            'PREFIX_' => _DB_PREFIX_,
            'ENGINE_TYPE' => _MYSQL_ENGINE_,
        ]);

        return $sqlLoader->parseFile($filepath);
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall()
            && $this->executeSqlFromFile($this->getLocalPath() . 'src/sql/uninstall.sql');
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

    public function hookDisplayTop(array $params): string
    {
        if ($this->context->mobile_detect->isMobile()) {
            return '';
        }

        $cacheId = $this->name;

        // $this->_clearCache($this->template, $this->getCacheId($cacheId));

        if (!$this->isCached($this->template, $this->getCacheId($cacheId))) {
            $this->smarty->assign($cacheId, $this->getMenuElements());
        }

        return $this->fetch($this->template);
    }

    private function getMenuElements(): array
    {
        return [
            'elements' => array_map(function ($category) {
                return [
                    'name' => $category['name'],
                    'template_icon' => $category['template_icon'] ?? '',
                    'link' => $category['link'],
                    'children' => array_map(function ($children) {
                        return [
                            'name' => $children['name'],
                            'link' => $children['link'],
                            'children' => $this->getChildrenCategories($children['id'], $children['nleft'], $children['nright'], $children['depth'] + 1, 6)
                        ];
                    }, $this->getChildrenCategories($category['id'], $category['nleft'], $category['nright'], $category['depth'] + 1))
                ];
            }, $this->getTopCategories()),
            'brands' => $this->getManufacturers(),
        ];
    }

    private function getTopCategories(): array
    {
        $topCategories = [];

        $results = Db::getInstance()->executeS(
            "select kc.id_category, kc.`position`, c.nleft, c.nright, c.level_depth, cl.name
            from " . _DB_PREFIX_ . "kpymenu_categories kc
            inner join " . _DB_PREFIX_ . "category c
                on c.id_category = kc.id_category and c.active = 1
            inner join " . _DB_PREFIX_ . "category_lang cl
                on cl.id_category = c.id_category and cl.id_shop = 1 and cl.id_lang = 1
            order by kc.`position`"
        );

        $templateIcon = [
            1001 => 'svg-dog.tpl',
            1170 => 'svg-cat.tpl',
            1278 => 'svg-rabbit.tpl',
        ];

        foreach ($results as $category) {
            $topCategories[] = [
                'id' => (int)$category['id_category'],
                'name' => $category['name'],
                'nleft' => (int)$category['nleft'],
                'nright' => (int)$category['nright'],
                'depth' => (int)$category['level_depth'],
                'template_icon' => $templateIcon[(int)$category['id_category']],
                'link' => $this->context->link->getCategoryLink((new Category((int)$category['id_category']))),

            ];
        }

        return $topCategories;
    }

    private function getChildrenCategories(int $idCategory, int $nleft, int $nright, int $depth, ?int $max = null): array
    {
        $children = [];

        $results = Db::getInstance()->executeS(
            "select c.id_category, cl.name, cl.link_rewrite, c.nleft, c.nright, c.level_depth
            from " . _DB_PREFIX_ . "category c
            inner join " . _DB_PREFIX_ . "category_lang cl
                on cl.id_category = c.id_category
            where c.active = 1
                and c.nleft > {$nleft}
                and c.nright < {$nright}
                and c.level_depth = {$depth}
            order by c.nleft"
        );

        foreach ($results as $category) {
            $children[] = [
                'id' => (int)$category['id_category'],
                'name' => $category['name'],
                'link' => $this->context->link->getCategoryLink((new Category((int)$category['id_category']))),
                'nleft' => (int)$category['nleft'],
                'nright' => (int)$category['nright'],
                'depth' => (int)$category['level_depth']
            ];
        }

        if ($max && count($children) > $max) {
            $children = array_slice($children, 0, $max);
            $children[] = [
                'id' => 0,
                'name' => 'Ver todo',
                'link' => $this->context->link->getCategoryLink((new Category($idCategory))),
                'nleft' => 0,
                'nright' => 0,
                'depth' => 0
            ];
        }

        return $children;
    }

    private function getManufacturers(): array
    {
        $manufacturers = Manufacturer::getAllManufacturersLinkRewriteById();

        $manufacturersByFirstLetter = [];
        $manufacturersByNumber = [];

        foreach ($manufacturers as $manufacturer) {
            $start = strtoupper($manufacturer['name'][0]);

            $link = $this->context->link->getBaseLink() . 'marcas/' . $manufacturer['link_rewrite'];

            if (ctype_digit($start)) {
                if (!isset($manufacturersByNumber[$start])) {
                    $manufacturersByNumber[$start] = [];
                }

                $manufacturersByNumber[$start][] = [
                    'name' => $manufacturer['name'],
                    'link' => $link,
                ];

            } else {
                if (!isset($manufacturersByFirstLetter[$start])) {
                    $manufacturersByFirstLetter[$start] = [];
                }

                $manufacturersByFirstLetter[$start][] = [
                    'name' => $manufacturer['name'],
                    'link' => $link,
                ];
            }


        }

        ksort($manufacturersByFirstLetter);
        ksort($manufacturersByNumber);

        $manufacturers = $manufacturersByFirstLetter + $manufacturersByNumber;

        return [
            'brands' => $manufacturers,
            'letters' => array_keys($manufacturers),
        ];
    }


    public function hookActionFrontControllerSetMedia(): void
    {
        $this->context->controller->registerStylesheet(
            $this->name . '-style',
            'modules/' . $this->name . '/views/css/' . $this->name . ($this->context->isMobile() ? '-mobile' : '') . '.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );

        $this->context->controller->registerJavascript(
            $this->name . '-javascript',
            'modules/' . $this->name . '/views/js/' . $this->name . ($this->context->isMobile() ? '-mobile' : '') . '.js',
            [
                'position' => 'bottom',
                'priority' => 1000,
            ]
        );
    }

    public function renderWidget($hookName, array $configuration): string
    {
        $cacheId = $this->name . 'Widget';
        if (!$this->isCached($this->templateMobile, $this->getCacheId($cacheId))) {
            $this->smarty->assign($cacheId, $this->getMobileMenuElements());
        }

        return $this->fetch($this->templateMobile);
    }

    private function getMobileMenuElements(): array
    {
        $topCategories = $this->getTopCategories();
        array_walk($topCategories, static function (&$category) {
            $category['hasChildren'] = true;
        });

        $secondLevel = array_map([$this, 'getArrayChildrenCategoriesForMobile'], $topCategories);

        $thirdLevel = [];
        foreach ($secondLevel as &$level) {
            foreach ($level['elements'] as &$category) {
                $children = $this->getArrayChildrenCategoriesForMobile($category);
                $category['hasChildren'] = false;

                if (!empty($children['elements'])) {
                    $category['hasChildren'] = true;
                    array_walk($children['elements'], static function (&$element) {
                        $element['hasChildren'] = false;
                    });
                }

                $thirdLevel[] = $children;
            }
        }

        unset($category);
        unset($level);

        return [
            'firstLevel' => $topCategories,
            'levels' => array_merge($secondLevel, $thirdLevel),
        ];
    }

    private function getArrayChildrenCategoriesForMobile(array $category): array
    {
        return [
            'parent' => ['id' => $category['id'], 'name' => $category['name']],
            'elements' => $this->getChildrenCategories($category['id'], $category['nleft'], $category['nright'], $category['depth'] + 1),
        ];
    }

    public function getWidgetVariables($hookName, array $configuration)
    {

    }


}
