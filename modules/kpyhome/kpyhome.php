<?php

declare(strict_types=1);

use PrestaShop\Module\KpyHome\Entity\KpyHomeSliderBanner;
use PrestaShop\Module\KpyHome\Install\Installer;
use PrestaShop\Module\KpyHome\Repository\KpyHomeCategoryRepository;
use PrestaShop\Module\KpyHome\Repository\KpyHomeSliderBannerRepository;
use PrestaShop\Module\KpyHome\SearchProvider\KpyHomeSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyHome extends Module
{
    private string $template;

    private string $templateMobile;

    public function __construct()
    {
        $this->name = 'kpyhome';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PyM Team';

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Home', [], 'Modules.Kpyhome.Admin');
        $this->description = $this->trans('Add content to homepage', [], 'Modules.Kpyhome.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyhome.Admin');

        $this->template = 'module:' . $this->name . '/views/templates/hook/home.tpl';
        $this->templateMobile = 'module:' . $this->name . '/views/templates/hook/home-mobile.tpl';
    }

    /**
     * @return bool
     */
    public function install(): bool
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
    public function uninstall(): bool
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

    public function hookDisplayHome(): string
    {
        // $this->_clearCache($this->template);

        if (!$this->isCached($this->template, $this->getCacheId($this->name))) {
            $variables = [
                'module_img' => $this->getPathUri() . 'views/img/',
                'best_products' => $this->getProductsForTemplate([7214, 649, 3312, 7103, 661, 3223, 9208]),
                'alternative_products' => $this->getProductsForTemplate([9026, 545, 2702, 7043, 8307, 661]),
                'comments' => [
                    'comments' => $this->getComments(),
                    'grade' => 4.7,
                    'nb' => 1250,
                ],
                'limit_free_shipping' => _KPY_LIMIT_SHIPPING_FREE_,
                'categories' => $this->getCategories(),
                'slider_banners' => $this->getHomeSliderBanners()
            ];

            $this->smarty->assign($variables);
        }

        return $this->fetch(
            $this->context->isMobile() ? $this->templateMobile : $this->template,
            $this->getCacheId($this->name)
        );
    }

    public function getProductsForTemplate(array $id_products): array
    {
        $searchProvider = new KpyHomeSearchProvider();

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
            $products_for_template[] = $presenter->present(
                $presentationSettings,
                ($assembleInBulk ? $rawProduct : $assembler->assembleProduct($rawProduct)),
                $this->context->language
            );
        }

        return $products_for_template;
    }

    public function getComments(int $nbComments = 3): array
    {
        return [
            [
                'text' => 'Llevo 6 meses comprando aquí el pienso renal de mi gato. Siempre llega en 24h y cuando una vez hubo un problema con Seur me lo solucionaron en el mismo día sin hacerme esperar.',
                'author' => 'Laura M., Madrid',
            ],
            [
                'text' => 'He probado zooplus, Tiendanimal y Miscota. Aquí es donde menos líos he tenido con las entregas, y eso para mí es oro.',
                'author' => 'Javier R., Valencia',
            ],
            [
                'text' => 'El stock es real, no como en otras webs que te cancelan el pedido a los 4 días. Aquí si pone \'disponible\', es que lo tienen.',
                'author' => 'Ana P., Sevilla',
            ],
        ];
    }

    public function getCategories(): array
    {
        $repository = new KpyHomeCategoryRepository();

        return array_map(function(array $category) {
            return [
                'id' => $category['id_category'],
                'title' => $category['title'],
                'subtitle' => $category['subtitle'],
                'image' => $this->getPathUri() . 'views/img/categories/' . $category['image'],
            ];
        },  $repository->getHomeCategories($this->context->isMobile()));
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller->php_self === 'index') {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . ($this->context->isMobile() ? 'mobile' : '') . '.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );

            $this->context->controller->registerStylesheet(
                $this->name . '-slider-style',
                'assets/css/slider.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );

            $this->context->controller->registerJavascript(
                $this->name . '-slider-script',
                'assets/js/slider.js',
                [
                    'position' => 'bottom',
                    'priority' => 1000,
                ]
            );

            if (!$this->context->isMobile()) {
                $this->context->controller->registerStylesheet(
                    $this->name . '-carousel-style',
                    'assets/css/carousel.css',
                    [
                        'media' => 'all',
                        'priority' => 1000,
                    ]
                );

                $this->context->controller->registerJavascript(
                    $this->name . '-carousel-script',
                    'assets/js/carousel.js',
                    [
                        'position' => 'bottom',
                        'priority' => 1000,
                    ]
                );
            }
        }
    }

    public function getHomeSliderBanners(): array
    {
        $repository = new KpyHomeSliderBannerRepository();

        return array_map(function (KpyHomeSliderBanner $slider) {
            return [
                'image' => $this->getPathUri() . 'views/img/banners/' . ($this->context->isMobile() ? 'mobile/' : '') . $slider->getImage(),
                'description' => $slider->getDescription(),
                'url' => $slider->getUrl(),
            ];
        }, $repository->getBannersActive($this->context->isMobile()));
    }
}
