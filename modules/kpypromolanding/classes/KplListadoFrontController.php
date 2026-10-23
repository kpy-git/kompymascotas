<?php

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

/**
 * Lo común de las páginas con listado de productos (la de una promoción, la de un tipo y «todas»):
 * es un listado de PrestaShop de verdad (la misma tarjeta, el mismo orden, la misma paginación y
 * el mismo ajax que una categoría), con una cabecera nuestra y el filtro de mascota.
 *
 * Extiende el controlador de listados del núcleo, no `ModuleFrontController`, porque el de listados
 * es el que sabe presentar productos; lo que `ModuleFrontController` hace en su constructor (el
 * módulo, el nombre de página) se hace aquí a mano.
 */
abstract class KplListadoFrontController extends ProductListingFrontController
{
    /** @var Module */
    public $module;

    /** Lo que pinta la cabecera; cada página lo llena en `preparar()`. */
    protected array $kpl = [];

    /** @var int[] las categorías nuestras que se listan */
    protected array $categorias = [];

    protected string $mascota = '';

    /** La campaña viva (Black Friday…), o null: sus nombres en las etiquetas y su piel en la cabecera. */
    protected ?array $campana = null;

    public function __construct()
    {
        $this->module = Module::getInstanceByName('kpypromolanding');
        $this->page_name = 'module-kpypromolanding-' . $this->nombre();
        parent::__construct();
        $this->controller_type = 'modulefront';
    }

    /** El nombre del controlador (`tipo`, `promo`, `todas`). */
    abstract protected function nombre(): string;

    /** Llena `$this->kpl` y `$this->categorias`; para una dirección que no existe, `$this->noEsta()`. */
    abstract protected function preparar(): void;

    public function init(): void
    {
        parent::init();
        if (!$this->module || !$this->module->active) {
            Tools::redirect('index');
        }
        $this->mascota = KplPromos::mascotaPedida();
        $this->campana = KplCampanas::viva();
        $this->preparar();
    }

    public function initContent(): void
    {
        parent::initContent();
        $this->context->smarty->assign('kpl', $this->kpl + [
            'mascota' => $this->mascota,
            'campana' => $this->campanaParaPlantilla(),
            'url_landing' => KplPromos::urlLanding(),
            'url_todas' => KplPromos::urlTodas(),
        ]);
        $this->doProductSearch('module:kpypromolanding/views/templates/front/listado.tpl');
    }

    public function setMedia(): void
    {
        parent::setMedia();
        if ($this->module instanceof Kpypromolanding) {
            $this->module->hojasDeEstilo($this);
        }
    }

    /** Las plantillas `module:` se resuelven al pintar (como hace `ModuleFrontController`), no con el buscador del tema. */
    public function setTemplate($template, $params = [], $locale = null): void
    {
        if (strpos($template, 'module:') === 0) {
            $this->template = $template;
        } else {
            parent::setTemplate($template, $params, $locale);
        }
    }

    protected function getProductSearchQuery(): ProductSearchQuery
    {
        $query = new ProductSearchQuery();
        $query
            ->setQueryType('kpypromo')
            ->setIdCategory((int) ($this->categorias[0] ?? 0))
            ->setSortOrder(new SortOrder('product', 'position', 'desc'));

        return $query;
    }

    protected function getDefaultProductSearchProvider(): KplBuscador
    {
        return new KplBuscador($this->categorias, $this->mascota);
    }

    public function getListingLabel(): string
    {
        return (string) ($this->kpl['titulo_plano'] ?? $this->kpl['titulo'] ?? 'Promociones');
    }

    public function getCanonicalURL(): string
    {
        $base = (string) ($this->kpl['canonical'] ?? '');

        return $base !== '' ? $this->buildPaginatedUrl($base) : parent::getCanonicalURL();
    }

    public function getTemplateVarPage(): array
    {
        $page = parent::getTemplateVarPage();
        $page['page_name'] = $this->page_name;
        $page['body_classes']['page-kpypromo'] = true;
        $page['body_classes']['page-kpypromo-' . $this->nombre()] = true;
        $page['meta']['title'] = (string) ($this->kpl['meta_titulo'] ?? $this->getListingLabel()) . ' · ' . Configuration::get('PS_SHOP_NAME');
        $page['meta']['description'] = (string) ($this->kpl['meta_texto'] ?? '');
        $page['meta']['keywords'] = '';
        if (!empty($this->kpl['noindex']) || $this->mascota !== '' || Tools::getIsset('page')) {
            $page['meta']['robots'] = 'noindex,follow';
        }

        return $page;
    }

    public function getBreadcrumbLinks(): array
    {
        $migas = parent::getBreadcrumbLinks();
        $migas['links'][] = ['title' => 'Ofertas y promociones', 'url' => KplPromos::urlLanding()];
        foreach ((array) ($this->kpl['migas'] ?? []) as $m) {
            $migas['links'][] = $m;
        }

        return $migas;
    }

    /** Las direcciones del selector Todos / Perros / Gatos para una dirección base. */
    protected function quien(string $base): array
    {
        return [
            'todos' => $base,
            'perro' => KplPromos::conMascota($base, 'perro'),
            'gato' => KplPromos::conMascota($base, 'gato'),
        ];
    }

    /** Una dirección que no existe: a la página de «no encontrado» de la tienda (como hace una categoría que no existe). */
    protected function noEsta(): void
    {
        $this->redirect_after = '404';
        $this->redirect();
        exit;
    }

    /**
     * Las claves con sus direcciones (la de la página y la de «aplicar cupón») y, con una campaña
     * viva, el nombre de campaña en su etiqueta, para las plantillas.
     */
    protected function conUrls(array $claves): array
    {
        return KplPromos::conCampana(KplPromos::conUrls($claves, $this->mascota), $this->campana);
    }

    /** La campaña viva con sus textos, su dirección y sus colores, para la cabecera; null si no hay. */
    protected function campanaParaPlantilla(): ?array
    {
        if ($this->campana === null) {
            return null;
        }
        $c = $this->campana;

        return $c + KplCampanas::textos($c) + ['url' => KplCampanas::url($c), 'estilo' => KplCampanas::estilo($c)];
    }

    /** El nombre de campaña de un tipo («Black Friday» por «Special Price») si la campaña viva lo cambia; si no, vacío. */
    protected function etiquetaCampana(string $tipo): string
    {
        if ($this->campana === null || !KplPromos::tipoEnCampana($this->campana, $tipo)) {
            return '';
        }

        return KplCampanas::etiquetaCorta($this->campana, $tipo);
    }
}
