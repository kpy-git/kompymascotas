<?php
/**
 * Kompy · Caprichitos
 *
 * El bloque de la ficha que ofrece lo que pega con lo que se está mirando: a un pienso, el húmedo de
 * su marca, unos snacks o un complemento. De un toque entra en la cesta, y si se lleva el par hay un
 * porcentaje extra en los dos. Tocar la foto o el nombre lleva a su ficha, y «Atrás» vuelve a esta
 * tal como estaba.
 *
 * Se llamó «Llévalo junto» hasta la 1.4 (Rafa, 30/09/2026: «Caprichitos»). El nombre técnico
 * (`kpycrosssell`) no cambia: con él están sus tablas, su configuración y el enganche de la ficha.
 *
 * Por qué hace falta: Kompy no tenía relaciones entre productos (la tabla de PrestaShop está vacía y
 * las del web antiguo se hicieron a mano y están viejas), pero el catálogo sí sabe de cada producto
 * su marca, su mascota y su tipo. Con eso se deduce, y lo que no cuadre se corrige a mano en la
 * pantalla del módulo.
 *
 * Cómo se reparte el trabajo:
 *   - `KcsSugerencias` decide qué ofrecer (a mano → de su marca → caprichos);
 *   - `KcsBloque` lo convierte en lo que pinta la plantilla (foto, formato más barato, precio);
 *   - `KcsJuntos` calcula el descuento por llevarlos juntos y lo mete en la cesta;
 *   - `controllers/front/anadir.php` los añade a la cesta sin recargar.
 *
 * Crea una tabla propia (`kpy_cross_sell`, las parejas a mano) y no toca ninguna otra.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/KcsDistribuidor.php';
require_once __DIR__ . '/classes/KcsPistas.php';
require_once __DIR__ . '/classes/KcsAntiparasitarios.php';
require_once __DIR__ . '/classes/KcsSugerencias.php';
require_once __DIR__ . '/classes/KcsBloque.php';
require_once __DIR__ . '/classes/KcsTitulo.php';
require_once __DIR__ . '/classes/KcsPromo.php';
require_once __DIR__ . '/classes/KcsJuntos.php';
require_once __DIR__ . '/classes/KcsClics.php';
require_once __DIR__ . '/classes/KcsGuardadas.php';

class Kpycrosssell extends Module
{
    /** La entrada del menú, al lado de la de promociones. */
    public const PESTANA = 'AdminKpyJunto';

    /** Cómo se llama en el menú y en la lista de módulos. */
    public const NOMBRE = 'Kompy · Caprichitos';

    public function __construct()
    {
        $this->name = 'kpycrosssell';
        $this->tab = 'front_office_features';
        $this->version = '1.6.0';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Kompy · Caprichitos', [], 'Modules.Kpycrosssell.Admin');
        $this->description = $this->trans('Sugerencias que pegan con el producto que se está viendo, con descuento por llevarlos juntos.', [], 'Modules.Kpycrosssell.Admin');
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        return parent::install()
            && $this->tabla()
            && $this->ajustes()
            && $this->registerHook('displayKpyCrossSell')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('actionCartSave')
            && $this->registerHook('displayShoppingCart')
            && $this->registerHook('actionProductAdd')
            && $this->pestana();
    }

    public function uninstall(): bool
    {
        // La tabla se queda: las parejas a mano son trabajo de catálogo, no del módulo.
        $this->quitarPestana();

        return parent::uninstall();
    }

    private function tabla(): bool
    {
        $p = _DB_PREFIX_;
        $motor = _MYSQL_ENGINE_;

        return (bool) Db::getInstance()->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell (
                id_cross INT UNSIGNED NOT NULL AUTO_INCREMENT,
                ambito ENUM('producto','categoria','marca') NOT NULL DEFAULT 'producto',
                id_ref INT UNSIGNED NOT NULL,
                id_sugerido INT UNSIGNED NOT NULL,
                posicion TINYINT UNSIGNED NOT NULL DEFAULT 1,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                veto TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id_cross),
                UNIQUE KEY pareja (ambito, id_ref, id_sugerido),
                KEY busqueda (ambito, id_ref, activo, posicion)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) Db::getInstance()->execute(
            // Lo que de verdad se lleva la gente. Es lo que en el panel antiguo eran las columnas
            // de clics: sirve para ordenar por lo que funciona y para quitar lo que no.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell_clic (
                id_ref INT UNSIGNED NOT NULL,
                id_sugerido INT UNSIGNED NOT NULL,
                id_shop INT UNSIGNED NOT NULL DEFAULT 1,
                vistas INT UNSIGNED NOT NULL DEFAULT 0,
                clics INT UNSIGNED NOT NULL DEFAULT 0,
                ultimo DATETIME NULL,
                PRIMARY KEY (id_ref, id_sugerido, id_shop)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) Db::getInstance()->execute(
            // El orden de las sugerencias guardadas en `accessory` (que no lo guarda), y la huella de
            // lo que dejó puesto el cálculo, para saber si alguien lo ha cambiado a mano.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell_auto (
                id_product INT UNSIGNED NOT NULL,
                id_sugerido INT UNSIGNED NOT NULL,
                posicion TINYINT UNSIGNED NOT NULL DEFAULT 1,
                origen VARCHAR(20) NOT NULL DEFAULT '',
                fecha DATETIME NULL,
                PRIMARY KEY (id_product, id_sugerido),
                KEY orden (id_product, posicion)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) Db::getInstance()->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell_promo (
                id_promo INT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_manufacturer INT UNSIGNED NOT NULL DEFAULT 0,
                nombre VARCHAR(128) NOT NULL DEFAULT '',
                porcentaje DECIMAL(6,3) NOT NULL DEFAULT 0,
                misma_marca TINYINT(1) NOT NULL DEFAULT 1,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                fecha_desde DATE NULL,
                fecha_hasta DATE NULL,
                PRIMARY KEY (id_promo),
                UNIQUE KEY marca (id_manufacturer)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        );
    }

    /**
     * Los valores de partida, pensados para que el bloque no moleste desde el primer día.
     *
     * Los descuentos por llevarlos juntos **no** se ponen aquí: se crean marca a marca en la
     * pantalla del módulo, y mientras no haya ninguno el bloque sugiere pero no descuenta.
     */
    private function ajustes(): bool
    {
        return Configuration::updateValue('KCS_CUANTAS', 3)
            && Configuration::updateValue('KCS_PARTE', 60)
            && Configuration::updateValue('KCS_TOPE', 40)
            // Boske: la marca de la casa, la que va primero cuando comparte patología.
            && Configuration::updateValue('KCS_MARCA_PROPIA', (int) Db::getInstance()->getValue(
                'SELECT id_manufacturer FROM ' . _DB_PREFIX_ . "manufacturer WHERE name = 'Boske'"
            ));
    }

    private function pestana(): bool
    {
        if (Tab::getIdFromClassName(self::PESTANA)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = self::PESTANA;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->active = 1;
        $tab->icon = 'add_shopping_cart';
        foreach (Language::getLanguages(false) as $idioma) {
            $tab->name[(int) $idioma['id_lang']] = self::NOMBRE;
        }

        return (bool) $tab->add();
    }

    private function quitarPestana(): bool
    {
        $id = (int) Tab::getIdFromClassName(self::PESTANA);
        if (!$id) {
            return true;
        }
        $tab = new Tab($id);

        return (bool) $tab->delete();
    }

    /** Los ajustes del motor, tal como los espera `KcsSugerencias`. */
    public static function comoBuscar(): array
    {
        return [
            'limite' => max(1, (int) Configuration::get('KCS_CUANTAS')),
            'parte' => max(0.05, min(1.0, ((float) Configuration::get('KCS_PARTE')) / 100)),
            'tope' => max(1.0, (float) Configuration::get('KCS_TOPE')),
        ];
    }

    /**
     * Un producto nuevo nace con sus tres sugerencias.
     *
     * Solo salta cuando el producto se crea desde el back office. Los que entran por los paquetes de
     * catálogo (SQL directo) no pasan por aquí: esos los recoge `bin/generar.php --nuevos`, que
     * calcula los que todavía no tienen nada.
     */
    public function hookActionProductAdd(array $params): void
    {
        $idProducto = (int) ($params['id_product'] ?? 0);
        if ($idProducto <= 0) {
            return;
        }
        try {
            KcsGuardadas::generar($idProducto, self::comoBuscar());
        } catch (Throwable $e) {
            // Que no se pueda calcular no puede impedir que se cree el producto.
            PrestaShopLogger::addLog('kpycrosssell: ' . $e->getMessage(), 2, null, 'Product', $idProducto);
        }
    }

    /**
     * El bloque, justo debajo de la caja de compra y antes de los beneficios. Es donde se decide la
     * compra, así que es donde tiene sentido ofrecer el acompañamiento; más abajo ya se ha pasado el
     * momento.
     *
     * Va a todo el ancho, en una sola línea, justo debajo de las dos columnas: aunque arriba haya
     * foto a la izquierda y caja a la derecha, aquí caben tres productos sin dejar medio bloque en
     * blanco. Si no hay nada que ofrecer, no se pinta nada.
     */
    public function hookDisplayKpyCrossSell(array $params): string
    {
        $producto = $params['product'] ?? null;
        // El producto llega de formas distintas según quién llame; si no, el de la dirección.
        $idProducto = 0;
        if (is_array($producto)) {
            $idProducto = (int) ($producto['id_product'] ?? $producto['id'] ?? 0);
        } elseif (is_object($producto)) {
            $idProducto = (int) ($producto->id_product ?? $producto->id ?? 0);
        }
        if ($idProducto <= 0) {
            $idProducto = (int) Tools::getValue('id_product');
        }
        if ($idProducto <= 0) {
            return '';
        }

        // El formato que está enseñando la ficha. El de la ficha manda: el «por defecto» del catálogo
        // puede ser uno agotado que la ficha se salta, y los antiparasitarios van por peso.
        $ipa = 0;
        if (is_array($producto) || $producto instanceof ArrayAccess) {
            $ipa = (int) ($producto['id_product_attribute'] ?? 0);
        }
        $ipa = $ipa ?: ((int) Tools::getValue('id_product_attribute', 0) ?: (int) Product::getDefaultAttribute($idProducto));
        // Las sugerencias ya están calculadas y guardadas en los productos relacionados: aquí solo
        // se leen. Nada de calcularlas en cada visita.
        // A un antiparasitario se le leen dos de más: si con el formato que se mira alguna no encaja
        // de peso, se oculta y entra la siguiente, y siguen viéndose tres.
        $limite = (int) self::comoBuscar()['limite'];
        $rasgos = KcsSugerencias::rasgos($idProducto);
        $sugerencias = KcsGuardadas::leer($idProducto, $limite + ($rasgos['tipo'] === 'Antiparasitario' ? 2 : 0));
        if ($sugerencias === []) {
            return '';
        }

        // Con el principal y su formato: los antiparasitarios van por peso (ver `KcsBloque::conPesos`).
        $articulos = KcsBloque::articulos($sugerencias, $this, $idProducto, $ipa);
        if ($articulos === []) {
            return '';
        }
        // Se enseñan las primeras que encajen; las demás esperan ocultas por si cambia el formato.
        $vistas = 0;
        foreach ($articulos as $i => $a) {
            $articulos[$i]['sobra'] = empty($a['oculto']) && $vistas >= $limite;
            if (empty($a['oculto']) && $vistas < $limite) {
                ++$vistas;
            }
        }

        // El descuento lo decide la marca de este producto, no la tienda entera.
        $idMarca = (int) (new Product($idProducto))->id_manufacturer;
        $regla = KcsPromo::deLaMarca($idMarca);
        if ($regla !== null) {
            foreach ($articulos as $i => $a) {
                $articulos[$i]['con_premio'] = KcsPromo::valeLaPareja($regla, $idMarca, (int) $a['id_manufacturer']);
            }
        }

        // Se apunta que se ha enseñado, para poder comparar después con lo que se llevan.
        $enPantalla = array_values(array_filter($articulos, static fn (array $a): bool => empty($a['oculto']) && empty($a['sobra'])));
        KcsClics::vistas($idProducto, array_column($enPantalla, 'id_product'));

        // El título dice qué gana el animal, no qué hace el botón.
        $texto = KcsTitulo::para($rasgos, $enPantalla ?: $articulos, $this);

        $this->context->smarty->assign(['kcs' => [
            'titulo' => $texto['titulo'],
            'sub' => $texto['sub'],
            'articulos' => $articulos,
            'limite' => $limite,
            'principal' => [
                'id_product' => $idProducto,
                'id_product_attribute' => $ipa,
            ],
            'juntos' => $regla !== null ? [
                'pct' => KcsJuntos::texto($regla['porcentaje']),
                'valor' => $regla['porcentaje'],
            ] : null,
            'url' => $this->context->link->getModuleLink($this->name, 'anadir', [], true),
            'moneda' => $this->context->currency->sign,
        ]]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/junto.tpl');
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        // Las reglas de «llevados juntos» de cestas abandonadas, fuera (cada 6 horas, sin cron).
        KcsJuntos::limpiarSiToca();

        $controlador = $this->context->controller;
        if (!isset($controlador->php_self)) {
            return;
        }
        if ($controlador->php_self === 'product') {
            $controlador->registerStylesheet('module-kpycrosssell', 'modules/' . $this->name . '/views/css/junto.css', ['media' => 'all', 'priority' => 220]);
            $controlador->registerJavascript('module-kpycrosssell', 'modules/' . $this->name . '/views/js/junto.js', ['position' => 'bottom', 'priority' => 220]);
        }
        if (in_array($controlador->php_self, ['cart', 'order'], true)) {
            // Si la regla de esta cesta se borró por vieja, vuelve antes de pintar los totales.
            $cesta = $this->context->cart;
            if ($cesta instanceof Cart && $cesta->id && KcsPromo::enVigor() !== []) {
                KcsJuntos::sincronizar($cesta, $this);
            }
            $controlador->registerStylesheet('module-kpycrosssell-cesta', 'modules/' . $this->name . '/views/css/junto.css', ['media' => 'all', 'priority' => 220]);
        }
    }

    /** El descuento por llevarlos juntos se revisa en cada cambio de la cesta. */
    public function hookActionCartSave(array $params): void
    {
        $cart = $params['cart'] ?? $this->context->cart;
        if ($cart instanceof Cart && $cart->id) {
            KcsJuntos::sincronizar($cart, $this);
        }
    }

    /** En la cesta, la línea que explica el descuento, con el mismo lenguaje que la ficha. */
    public function hookDisplayShoppingCart(): string
    {
        $cart = $this->context->cart;
        if (!$cart || !$cart->id) {
            return '';
        }
        $cuenta = KcsJuntos::calcular($cart);
        if ($cuenta['importe'] <= 0) {
            return '';
        }
        $locale = $this->context->getCurrentLocale();
        $this->context->smarty->assign(['kcsCesta' => [
            'texto' => $this->t('Llevados juntos: %pct% % en los dos', ['%pct%' => KcsJuntos::texto($cuenta['pct'])]),
            'importe' => '−' . ($locale ? $locale->formatPrice($cuenta['importe'], $this->context->currency->iso_code) : number_format($cuenta['importe'], 2, ',', '.') . ' €'),
        ]]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/cesta.tpl');
    }

    /** Traducción de los textos de la tienda, para las clases del módulo. */
    public function t(string $texto, array $reemplazos = []): string
    {
        return $this->trans($texto, $reemplazos, 'Modules.Kpycrosssell.Shop');
    }

    public function getContent(): string
    {
        require_once __DIR__ . '/classes/KcsPantalla.php';

        return (new KcsPantalla($this))->pintar();
    }
}
