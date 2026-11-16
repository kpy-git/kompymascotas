<?php
/**
 * Kompy · SEO técnico (kpyseo)
 *
 * Lo que PrestaShop y el tema no hacen solos y Google necesita para entender la tienda, sin tocar el
 * core ni el tema:
 *
 *   - sitemaps XML siempre al día en `/sitemap.xml`: categorías con producto, fichas visibles, páginas
 *     de marca y páginas de contenido; se regeneran solos cada N horas (sin cron);
 *   - canónica, título y descripción de las páginas de marca (`/marcas/...`), que salían sin canónica y
 *     con la descripción igual al nombre; se leen de los campos SEO de la marca en el back office;
 *   - `noindex, follow` automático en las categorías sin ningún producto (y fuera del sitemap);
 *   - JSON-LD de producto completo: GTIN de la combinación, envío, devoluciones, marca y descripción;
 *   - Open Graph y Twitter Card completos (imagen, locale) y la ficha de Organización en la portada;
 *   - leyendas de imagen (alt) cuando están vacías: «Producto – Marca»;
 *   - un H1 de texto en la portada (el tema ponía el logo como H1);
 *   - IndexNow: aviso a Bing, Yandex y compañía cuando cambia una ficha, una categoría o una marca.
 *
 * No crea tablas. Guarda su configuración en `Configuration` (prefijo `KPYSEO_`) y la caché de los
 * sitemaps en `var/cache/` del módulo. Solo lee (nunca escribe) las tablas de kpymanufacturer para
 * saber qué marcas tienen landing. Reglas de la casa: sin llamadas a otros módulos, estilos por
 * actionFrontControllerSetMedia, posición de los hooks por la API, nada en el tema.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/KseoAjustes.php';
require_once __DIR__ . '/classes/KseoCategorias.php';
require_once __DIR__ . '/classes/KseoMarcas.php';
require_once __DIR__ . '/classes/KseoSoporte.php';
require_once __DIR__ . '/classes/KseoRedirecciones.php';
require_once __DIR__ . '/classes/KseoCabecera.php';
require_once __DIR__ . '/classes/KseoJsonLd.php';
require_once __DIR__ . '/classes/KseoPresentacion.php';
require_once __DIR__ . '/classes/KseoSitemap.php';
require_once __DIR__ . '/classes/KseoIndexNow.php';

class Kpyseo extends Module
{
    public const NOMBRE = 'Kompy · SEO técnico';

    private const HOOKS = [
        'displayHeader',
        'displayContentWrapperTop',
        'actionFrontControllerSetMedia',
        'actionPresentProduct',
        'actionPresentProductListing',
        'actionOutputHTMLBefore',
        'moduleRoutes',
        'actionDispatcherBefore',
        'actionObjectProductUpdateAfter',
        'actionObjectProductAddAfter',
        'actionObjectProductDeleteAfter',
        'actionObjectCategoryUpdateAfter',
        'actionObjectCategoryAddAfter',
        'actionObjectManufacturerUpdateAfter',
        'actionObjectCmsUpdateAfter',
    ];

    public function __construct()
    {
        $this->name = 'kpyseo';
        $this->tab = 'seo';
        $this->version = '1.1.0';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = self::NOMBRE;
        $this->description = 'Sitemaps al día, canónicas y metas de marcas, noindex de categorías vacías, JSON-LD completo, Open Graph, alt automático, H1 de portada, IndexNow y redirecciones (búsquedas de marca, URL no amigables, lista propia).';
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }
        foreach (self::HOOKS as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }
        KseoAjustes::instalar();
        KseoIndexNow::prepararClave();
        KseoSitemap::prepararCache();
        $this->ponerPrimeroEnPortada();

        return true;
    }

    public function uninstall(): bool
    {
        KseoSitemap::borrarCache();
        KseoAjustes::desinstalar();

        return parent::uninstall();
    }

    /**
     * El H1 de la portada tiene que salir antes que el resto de bloques de la home. Va en
     * displayContentWrapperTop (lo primero del contenido, antes del bloque de campaña de
     * kpypromolanding y de kpyhome) y en primera posición.
     */
    private function ponerPrimeroEnPortada(): void
    {
        $idHook = (int) Hook::getIdByName('displayContentWrapperTop');
        if ($idHook > 0) {
            $this->updatePosition($idHook, 0, 1);
        }
    }

    // ------------------------------------------------------------------ front

    public function hookDisplayHeader(array $params): string
    {
        try {
            return KseoCabecera::etiquetas($this->context);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpyseo displayHeader: ' . $e->getMessage(), 3);

            return '';
        }
    }

    public function hookDisplayContentWrapperTop(array $params): string
    {
        $controller = $this->context->controller;
        if (!$controller instanceof FrontController || $controller->php_self !== 'index') {
            return '';
        }
        $h1 = KseoAjustes::texto('KPYSEO_H1_HOME');
        if ($h1 === '') {
            return '';
        }
        $this->context->smarty->assign([
            'kseo_h1' => $h1,
            'kseo_sub' => KseoAjustes::texto('KPYSEO_H1_SUB'),
        ]);

        return $this->fetch('module:kpyseo/views/templates/hook/h1-home.tpl');
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        $controller = $this->context->controller;
        if (!$controller instanceof FrontController || $controller->php_self !== 'index') {
            return;
        }
        if (KseoAjustes::texto('KPYSEO_H1_HOME') === '') {
            return;
        }
        $controller->registerStylesheet(
            'modules-kpyseo-portada',
            'modules/' . $this->name . '/views/css/kpyseo.css',
            ['media' => 'all', 'priority' => 200, 'version' => $this->version]
        );
    }

    public function hookActionPresentProduct(array $params): void
    {
        if (isset($params['presentedProduct'])) {
            KseoPresentacion::completar($params['presentedProduct'], true);
        }
    }

    public function hookActionPresentProductListing(array $params): void
    {
        if (isset($params['presentedProduct'])) {
            KseoPresentacion::completar($params['presentedProduct'], false);
        }
    }

    public function hookActionOutputHTMLBefore(array $params): void
    {
        if (!isset($params['html']) || !is_string($params['html'])) {
            return;
        }
        try {
            $html = $params['html'];
            if ($this->context->controller instanceof ProductController) {
                $html = KseoJsonLd::enriquecerProducto($html, $this->context);
            } elseif (KseoMarcas::esLanding()) {
                $html = KseoMarcas::reescribirCabecera($html, $this->context);
            } elseif (KseoSoporte::esArticulo() || KseoSoporte::esIndice()) {
                $html = KseoSoporte::reescribirCabecera($html, $this->context);
            }
            if (KseoCabecera::noindexAplicado()) {
                // el tema imprime su propio «index, follow»; con dos etiquetas Google aplica la más restrictiva, pero mejor una sola
                $html = (string) preg_replace('~[ \t]*<meta name="robots" content="index[^"]*"\s*/?>\s*~', '', $html, 1);
            }
            $params['html'] = $html;
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpyseo actionOutputHTMLBefore: ' . $e->getMessage(), 3);
        }
    }

    /** Redirecciones, antes de resolver el controlador (solo front y solo GET; ver KseoRedirecciones). */
    public function hookActionDispatcherBefore(array $params): void
    {
        if ((int) ($params['controller_type'] ?? 0) !== Dispatcher::FC_FRONT) {
            return;
        }
        try {
            KseoRedirecciones::atender($this->context);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpyseo actionDispatcherBefore: ' . $e->getMessage(), 3);
        }
    }

    public function hookModuleRoutes(array $params): array
    {
        $params = ['fc' => 'module', 'module' => $this->name];

        return [
            'module-kpyseo-sitemap-indice' => [
                'rule' => 'sitemap.xml',
                'keywords' => [],
                'controller' => 'sitemap',
                'params' => $params + ['tipo' => 'indice'],
            ],
            'module-kpyseo-sitemap-productos' => [
                'rule' => 'sitemap-productos-{n}.xml',
                'keywords' => ['n' => ['regexp' => '[0-9]+', 'param' => 'n']],
                'controller' => 'sitemap',
                'params' => $params + ['tipo' => 'productos'],
            ],
            'module-kpyseo-sitemap-tipo' => [
                'rule' => 'sitemap-{tipo}.xml',
                'keywords' => ['tipo' => ['regexp' => 'paginas|categorias|marcas', 'param' => 'tipo']],
                'controller' => 'sitemap',
                'params' => $params,
            ],
            'module-kpyseo-indexnow' => [
                'rule' => '{clave}.txt',
                'keywords' => ['clave' => ['regexp' => '[a-f0-9]{32}', 'param' => 'clave']],
                'controller' => 'indexnow',
                'params' => $params,
            ],
        ];
    }

    // --------------------------------------------------------------- IndexNow

    public function hookActionObjectProductUpdateAfter(array $params): void
    {
        $this->avisarProducto($params);
    }

    public function hookActionObjectProductAddAfter(array $params): void
    {
        $this->avisarProducto($params);
    }

    public function hookActionObjectProductDeleteAfter(array $params): void
    {
        $this->avisarProducto($params);
    }

    public function hookActionObjectCategoryUpdateAfter(array $params): void
    {
        $this->avisarCategoria($params);
    }

    public function hookActionObjectCategoryAddAfter(array $params): void
    {
        $this->avisarCategoria($params);
    }

    public function hookActionObjectManufacturerUpdateAfter(array $params): void
    {
        $objeto = $params['object'] ?? null;
        if (!$objeto instanceof Manufacturer) {
            return;
        }
        $rewrite = KseoMarcas::rewritePorId((int) $objeto->id);
        if ($rewrite !== '') {
            KseoIndexNow::encolar($this->context->link->getModuleLink('kpymanufacturer', 'landing', ['brand' => $rewrite]));
        }
    }

    public function hookActionObjectCmsUpdateAfter(array $params): void
    {
        $objeto = $params['object'] ?? null;
        if ($objeto instanceof CMS && (int) $objeto->active === 1) {
            KseoIndexNow::encolar($this->context->link->getCMSLink($objeto));
        }
    }

    private function avisarProducto(array $params): void
    {
        $objeto = $params['object'] ?? null;
        if (!$objeto instanceof Product || !Validate::isLoadedObject($objeto)) {
            return;
        }
        try {
            KseoIndexNow::encolar($this->context->link->getProductLink($objeto));
        } catch (Throwable $e) {
            // un producto a medio crear puede no tener aún ruta; no pasa nada
        }
    }

    private function avisarCategoria(array $params): void
    {
        $objeto = $params['object'] ?? null;
        if (!$objeto instanceof Category || !Validate::isLoadedObject($objeto) || $objeto->is_root_category) {
            return;
        }
        try {
            KseoIndexNow::encolar($this->context->link->getCategoryLink($objeto));
        } catch (Throwable $e) {
        }
    }

    // ------------------------------------------------------------ back office

    public function getContent(): string
    {
        $avisos = [];

        if (Tools::isSubmit('submitKpyseoGuardar')) {
            KseoAjustes::guardarDesdeFormulario();
            KseoSitemap::borrarCache();
            KseoSitemap::prepararCache();
            $avisos[] = ['ok', 'Ajustes guardados. Los sitemaps se regenerarán en la próxima petición.'];
        }
        if (Tools::isSubmit('submitKpyseoSitemap')) {
            $datos = (new KseoSitemap($this->context))->regenerar();
            $avisos[] = ['ok', sprintf(
                'Sitemaps regenerados: %d páginas, %d categorías, %d marcas y %d productos en %d ficheros.',
                $datos['paginas'], $datos['categorias'], $datos['marcas'], $datos['productos'], $datos['ficheros_productos']
            )];
        }
        if (Tools::isSubmit('submitKpyseoIndexnow')) {
            $urls = (new KseoSitemap($this->context))->todasLasUrls();
            $resultado = KseoIndexNow::enviar($urls);
            $avisos[] = [$resultado['ok'] ? 'ok' : 'error', sprintf(
                'IndexNow: %d URL enviadas, respuesta %s.',
                $resultado['enviadas'], $resultado['codigo']
            )];
        }

        $estado = (new KseoSitemap($this->context))->estado();
        $this->context->smarty->assign([
            'kseo_avisos' => $avisos,
            'kseo_sitemap_url' => $this->context->link->getPageLink('index', true) . 'sitemap.xml',
            'kseo_estado' => $estado,
            'kseo_indexnow_url_clave' => KseoIndexNow::urlClave(),
            'kseo_indexnow_ultimo' => KseoAjustes::texto('KPYSEO_INDEXNOW_ULTIMO'),
        ]);

        return $this->fetch('module:kpyseo/views/templates/admin/estado.tpl') . $this->formulario();
    }

    private function formulario(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->title = self::NOMBRE;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitKpyseoGuardar';
        $helper->fields_value = KseoAjustes::valoresFormulario();

        return $helper->generateForm(KseoAjustes::definicionFormulario());
    }
}
