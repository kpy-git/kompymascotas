<?php
/**
 * Kompy · Ampliar fotos con los dedos
 *
 * En móvil no se podían ampliar las fotos del producto: el tema abre una galería grande con un
 * botón pequeño de lupa, y dentro no se puede hacer zoom. Quien compra pienso mira el saco, la
 * etiqueta y la tabla de raciones: si no puede acercarse, se va.
 *
 * Este módulo añade, solo en la ficha:
 *   - tocar la foto abre la galería grande (antes había que acertar con la lupa);
 *   - dentro se amplía pellizcando con dos dedos o con dos toques, y se arrastra para moverse.
 *
 * No toca el tema ni la galería: se cuelga de lo que ya hay (`#product-modal`). Si se desactiva,
 * la ficha se queda como estaba.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Kpyproductzoom extends Module
{
    /** Hasta cuánto se puede ampliar. */
    public const CONF_MAX = 'KPZ_MAX';

    public function __construct()
    {
        $this->name = 'kpyproductzoom';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Kompy · Ampliar fotos con los dedos', [], 'Modules.Kpyproductzoom.Admin');
        $this->description = $this->trans('En la ficha: tocar la foto abre la galería y dentro se amplía pellizcando o con dos toques.', [], 'Modules.Kpyproductzoom.Admin');
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('actionFrontControllerSetMedia')
            && Configuration::updateValue(self::CONF_MAX, 4);
    }

    public function uninstall(): bool
    {
        Configuration::deleteByName(self::CONF_MAX);

        return parent::uninstall();
    }

    /** Solo en la ficha de producto, y el JavaScript diferido. */
    public function hookActionFrontControllerSetMedia(): void
    {
        $controlador = $this->context->controller;
        if (!isset($controlador->php_self) || $controlador->php_self !== 'product') {
            return;
        }
        $controlador->registerStylesheet('module-kpyproductzoom', 'modules/' . $this->name . '/views/css/zoom.css', ['media' => 'all', 'priority' => 210]);
        $controlador->registerJavascript('module-kpyproductzoom', 'modules/' . $this->name . '/views/js/zoom.js', ['position' => 'bottom', 'priority' => 210, 'attributes' => 'defer']);
        Media::addJsDef([
            'kpzMax' => (float) Configuration::get(self::CONF_MAX) ?: 4,
            'kpzPista' => $this->trans('Pellizca para ampliar', [], 'Modules.Kpyproductzoom.Shop'),
        ]);
    }

    public function getContent(): string
    {
        $salida = '';
        if (Tools::isSubmit('submitKpz')) {
            Configuration::updateValue(self::CONF_MAX, max(2, min(8, (float) str_replace(',', '.', (string) Tools::getValue(self::CONF_MAX)))));
            $salida .= $this->displayConfirmation($this->trans('Guardado.', [], 'Modules.Kpyproductzoom.Admin'));
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitKpz';
        $helper->fields_value = [self::CONF_MAX => Configuration::get(self::CONF_MAX)];
        $d = 'Modules.Kpyproductzoom.Admin';

        return $salida . $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->displayName],
                'input' => [
                    ['type' => 'text', 'name' => self::CONF_MAX, 'suffix' => 'x', 'label' => $this->trans('Ampliar como mucho', [], $d),
                        'desc' => $this->trans('Cuántas veces se puede agrandar la foto (entre 2 y 8).', [], $d)],
                ],
                'submit' => ['title' => $this->trans('Guardar', [], $d)],
            ],
        ]]);
    }
}
