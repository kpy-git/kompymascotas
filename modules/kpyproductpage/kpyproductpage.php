<?php
/**
 * Kompy · Ficha de producto
 *
 * Pinta la parte de abajo de la ficha (características, beneficios, ingredientes, composición,
 * ración diaria y descripción) con una sola plantilla para todos los productos, pensada primero
 * para móvil, rápida y legible para Google.
 *
 * Cómo entra en la ficha: el tema llama al hook `displayKpyProductPage` en el bloque
 * `product_tabs`. Si el módulo no devuelve nada (desactivado, producto excluido, sin datos
 * nuevos o cualquier error), el tema pinta lo de siempre. Ver README.md.
 *
 * Solo lee: no crea tablas ni cambia datos.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

foreach (['KppIngredientes', 'KppComposicion', 'KppFormatos', 'KppRacion', 'KppCaracteristicas', 'KppHtml', 'KppFicha', 'KppDatos', 'KppMarca'] as $clase) {
    require_once __DIR__ . '/classes/' . $clase . '.php';
}

class Kpyproductpage extends Module
{
    /** Marcas (id_manufacturer, separados por comas) que conservan su ficha de siempre. Boske. */
    public const CONF_EXCLUIR = 'KPP_EXCLUIR_MARCAS';

    /** 1 = plantilla nueva en todos los productos; 0 = solo en los que tienen datos nuevos. */
    public const CONF_TODOS = 'KPP_TODOS';

    /** Carpeta de los iconos de beneficios dentro de `modules/` (vacío = la de siempre). */
    public const CONF_ICONOS = 'KPP_ICONOS';

    /**
     * 1 = previsualización: con `?kpp=1` en la URL, la sección nueva sustituye a la de siempre
     * sin haber tocado el tema. Para probar en pruebas; en producción, 0.
     */
    public const CONF_PREVIA = 'KPP_PREVIA';

    /** Si ya se ha pintado en esta petición (para no repetirlo en la previsualización). */
    private static bool $pintado = false;

    public function __construct()
    {
        $this->name = 'kpyproductpage';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Kompy · Ficha de producto', [], 'Modules.Kpyproductpage.Admin');
        $this->description = $this->trans('Características, beneficios, ingredientes, composición, ración y descripción con una sola plantilla, pensada para móvil.', [], 'Modules.Kpyproductpage.Admin');
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('displayKpyProductPage')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayFooterProduct')
            && Configuration::updateValue(self::CONF_EXCLUIR, '')
            && Configuration::updateValue(self::CONF_TODOS, 0);
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName(self::CONF_EXCLUIR);
        Configuration::deleteByName(self::CONF_TODOS);
        Configuration::deleteByName(self::CONF_ICONOS);
        Configuration::deleteByName(self::CONF_PREVIA);

        return parent::uninstall();
    }

    /** CSS y JS solo en la ficha de producto, y el JS diferido. */
    public function hookActionFrontControllerSetMedia(): void
    {
        $controlador = $this->context->controller;
        if (!isset($controlador->php_self) || $controlador->php_self !== 'product') {
            return;
        }
        $controlador->registerStylesheet('module-kpyproductpage', 'modules/' . $this->name . '/views/css/ficha.css', ['media' => 'all', 'priority' => 200]);
        $controlador->registerJavascript('module-kpyproductpage', 'modules/' . $this->name . '/views/js/ficha.js', ['position' => 'bottom', 'priority' => 200, 'attributes' => 'defer']);
    }

    /**
     * @param array{product?: mixed} $params `product` es el producto que ya tiene la plantilla
     */
    public function hookDisplayKpyProductPage(array $params): string
    {
        $producto = $params['product'] ?? null;
        $idProducto = (int) ($producto['id_product'] ?? $producto['id'] ?? 0);
        if ($idProducto <= 0) {
            return '';
        }
        $idIdioma = (int) $this->context->language->id;
        $idMarca = (int) ($producto['id_manufacturer'] ?? 0);

        $excluidas = array_filter(array_map('intval', explode(',', (string) Configuration::get(self::CONF_EXCLUIR))));
        if ($idMarca > 0 && in_array($idMarca, $excluidas, true)) {
            return '';
        }

        try {
            return $this->pintar($producto, $idProducto, $idIdioma, $idMarca);
        } catch (Throwable $e) {
            // Pase lo que pase, la ficha no se rompe: vuelve la de siempre y queda en el log.
            PrestaShopLogger::addLog('kpyproductpage #' . $idProducto . ': ' . $e->getMessage(), 2, null, 'Product', $idProducto, true);

            return '';
        }
    }

    /**
     * Previsualización sin tocar el tema (ver CONF_PREVIA): se pinta al pie de la ficha y un
     * script de una línea la sube al sitio de la sección de siempre, que se oculta.
     */
    public function hookDisplayFooterProduct(array $params): string
    {
        // Si el tema ya lo ha pintado en su sitio, la previsualización sobra.
        if (self::$pintado || !$this->previa()) {
            return '';
        }
        $html = $this->hookDisplayKpyProductPage($params);
        if ($html === '') {
            return '';
        }

        return $html . '<script>(function(){var k=document.getElementById("kpp"),o=document.getElementById("product-infos-accordion");if(k&&o){o.parentNode.insertBefore(k,o);o.style.display="none";}})();</script>';
    }

    /**
     * `?kpp=1` enciende la previsualización y se recuerda en la cookie de la tienda mientras se
     * navega (también en fichas con `#/formato…` en la dirección); `?kpp=0` la apaga.
     */
    private function previa(): bool
    {
        if (!Configuration::get(self::CONF_PREVIA)) {
            return false;
        }
        $valor = (string) Tools::getValue('kpp');
        $cookie = $this->context->cookie;
        if ($valor === '1' || $valor === '0') {
            $cookie->kpp = $valor;
        }

        return isset($cookie->kpp) && (string) $cookie->kpp === '1';
    }

    private function pintar($producto, int $idProducto, int $idIdioma, int $idMarca): string
    {
        $iconos = trim((string) Configuration::get(self::CONF_ICONOS), '/ ');
        if ($iconos !== '' && strpos($iconos, '..') === false) {
            KppDatos::$iconos = $iconos . '/';
        }
        $beneficios = KppDatos::beneficios($idProducto, $idIdioma);
        $ingredientes = KppDatos::ingredientes($idProducto, $idIdioma);
        $composicion = KppDatos::composicion($idProducto, $idIdioma);
        $racion = KppDatos::racion($idProducto);
        // Contenido propio de la marca (hoy, Boske): sus beneficios ilustrados mandan sobre los de
        // la tabla, que en esas fichas nunca se enseñaron.
        $marcaPropia = KppMarca::de($idProducto, $idMarca, $this->getPathUri());
        if ($marcaPropia !== null && $marcaPropia['beneficios'] !== []) {
            $beneficios = $marcaPropia['beneficios'];
        }
        // Ficha completa = ingredientes y composición cargados: las que se dan de alta ahora. Casi
        // todo el catálogo tiene beneficios puestos (hasta juguetes), así que eso no distingue.
        // Una ficha con contenido de marca también sale entera, aunque le falte la analítica.
        $completa = (trim($ingredientes) !== '' && $composicion !== []) || $marcaPropia !== null;
        if (!$completa && !Configuration::get(self::CONF_TODOS)) {
            return '';
        }

        $link = $this->context->link;
        $marca = ['nombre' => '', 'url' => ''];
        if ($idMarca > 0) {
            $marca['nombre'] = (string) Manufacturer::getNameById($idMarca);
            $marca['url'] = $marca['nombre'] !== '' ? KppDatos::urlMarca($idMarca, $link) : '';
        }
        $idCategoria = (int) ($producto['id_category_default'] ?? 0);
        $categoria = ['nombre' => '', 'url' => ''];
        if ($idCategoria > 0) {
            $cat = new Category($idCategoria, $idIdioma);
            if (Validate::isLoadedObject($cat) && $cat->active) {
                $categoria = ['nombre' => (string) $cat->name, 'url' => $link->getCategoryLink($cat)];
            }
        }

        $kpp = KppFicha::montar([
            'nombre' => (string) ($producto['name'] ?? ''),
            'referencia' => (string) ($producto['reference_to_display'] ?? $producto['reference'] ?? ''),
            'ean13' => (string) ($producto['ean13'] ?? ''),
            'marca' => $marca,
            'categoria' => $categoria,
            'caracteristicas' => KppDatos::caracteristicas($idProducto, $idIdioma),
            'beneficios' => $beneficios,
            'ingredientes' => $ingredientes,
            'composicion' => $composicion,
            'racion' => $racion,
            'combinaciones' => KppDatos::combinaciones($idProducto, $idIdioma, (int) $this->context->shop->id),
            'descripcion' => (string) ($producto['description'] ?? ''),
            'movil' => (bool) $this->context->isMobile(),
        ]);

        $this->context->smarty->assign(['kpp' => $kpp, 'kpp_marca' => $marcaPropia]);
        $html = $this->fetch('module:' . $this->name . '/views/templates/hook/ficha.tpl');
        self::$pintado = $html !== '';

        return $html;
    }

    /** Configuración mínima: marcas excluidas y si se aplica a todos los productos. */
    public function getContent(): string
    {
        $salida = '';
        if (Tools::isSubmit('submitKpp')) {
            $ids = implode(',', array_filter(array_map('intval', preg_split('/[\s,;]+/', (string) Tools::getValue(self::CONF_EXCLUIR)))));
            Configuration::updateValue(self::CONF_EXCLUIR, $ids);
            Configuration::updateValue(self::CONF_TODOS, (int) Tools::getValue(self::CONF_TODOS));
            Configuration::updateValue(self::CONF_ICONOS, trim(str_replace('..', '', (string) Tools::getValue(self::CONF_ICONOS)), '/ '));
            $salida .= $this->displayConfirmation($this->trans('Guardado.', [], 'Modules.Kpyproductpage.Admin'));
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitKpp';
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->fields_value = [
            self::CONF_EXCLUIR => Configuration::get(self::CONF_EXCLUIR),
            self::CONF_TODOS => (int) Configuration::get(self::CONF_TODOS),
            self::CONF_ICONOS => Configuration::get(self::CONF_ICONOS),
        ];

        return $salida . $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->displayName],
                'input' => [
                    [
                        'type' => 'text',
                        'name' => self::CONF_EXCLUIR,
                        'label' => $this->trans('Marcas con su ficha de siempre', [], 'Modules.Kpyproductpage.Admin'),
                        'desc' => $this->trans('Id de fabricante separados por comas (p. ej. Boske).', [], 'Modules.Kpyproductpage.Admin'),
                    ],
                    [
                        'type' => 'switch',
                        'name' => self::CONF_TODOS,
                        'label' => $this->trans('Plantilla nueva en todos los productos', [], 'Modules.Kpyproductpage.Admin'),
                        'desc' => $this->trans('Apagado: solo en los que tienen ingredientes, composición, beneficios o ración cargados.', [], 'Modules.Kpyproductpage.Admin'),
                        'values' => [['id' => 'on', 'value' => 1, 'label' => 'Sí'], ['id' => 'off', 'value' => 0, 'label' => 'No']],
                    ],
                    [
                        'type' => 'text',
                        'name' => self::CONF_ICONOS,
                        'label' => $this->trans('Carpeta de los iconos de beneficios', [], 'Modules.Kpyproductpage.Admin'),
                        'desc' => $this->trans('Dentro de modules/. Vacío = kpyproductdescription/views/img/benefits', [], 'Modules.Kpyproductpage.Admin'),
                    ],
                ],
                'submit' => ['title' => $this->trans('Guardar', [], 'Modules.Kpyproductpage.Admin')],
            ],
        ]]);
    }
}
