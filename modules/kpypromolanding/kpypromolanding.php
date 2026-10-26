<?php
/**
 * Kompy · Landing de promociones
 *
 * `/promociones` y sus páginas (por tipo, por mascota, la de cada promoción), las categorías
 * automáticas de las promociones, el bloque de promociones vivas en la landing de cada marca y las
 * campañas (Black Friday…) como capa por encima de todo, con fecha de inicio y de fin.
 *
 * No crea promociones: las de `kpycoupons` (Catálogo → Kompy · Promociones), que es donde se
 * crean, se las pregunta por su hook de consulta `actionKpyCouponsPromociones` (kpycoupons 3.0.0;
 * `KplPromociones`). Ningún módulo llama a funciones de otro ni lee sus tablas: todo lo que este
 * módulo sabe de otro le llega por hooks, y lo que otros saben de él, igual. Cuando `kpycoupons`
 * rehace el reparto (al guardar, y cuando una promoción empieza o termina) avisa por el hook
 * `actionKpyCouponsReparto`, y este módulo:
 *   - crea o reutiliza la categoría de cada promoción (`royal-canin-special-price-perro`) bajo una
 *     raíz «Promociones», mete y saca sus productos y la enciende o la apaga;
 *   - recorta el logo de la marca y el saco más vendido de cada promoción, sin fondo, para el banner;
 *   - decide qué foto de cada producto sale en las tarjetas (nunca una foto de transición);
 *   - deja escrito el resumen que pintan las páginas (nada se calcula en una visita).
 *
 * Y en el formulario de cada promoción de `kpycoupons` pone el paso 6, «En la landing» (hooks
 * `displayKpyCouponsFormulario` y `actionKpyCouponsGuardar`): dirección, título, foto, orden y
 * campañas, todo opcional.
 *
 * Mientras una campaña está viva, cambia lo que se ve sin escribir nada: etiquetas en toda la
 * tienda (`actionProductFlagsModifier`), nombres y colores en la ficha (`actionKpyCouponsCampana`,
 * lo pregunta `kpycoupons`) y en la cesta (`actionKpyCouponsResumen`), cinta arriba
 * (`displayBanner`), bloque en la portada de la tienda (`displayContentWrapperTop`) y piel en el
 * bloque de cada marca.
 *
 * Escribe en sus tablas (`kpy_promo_categoria`, `kpy_promo_promo`, `kpy_promo_imagen`,
 * `kpy_promo_foto`, `kpy_promo_dato`, `kpy_promo_campana`), en las categorías que son suyas (bajo
 * «Promociones») y en `img/kpypromo/`. En nada más.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

spl_autoload_register(static function (string $clase): void {
    if (strncmp($clase, 'Kpl', 3) === 0 && is_file($fichero = __DIR__ . '/classes/' . $clase . '.php')) {
        require $fichero;
    }
});

class Kpypromolanding extends Module
{
    public const PESTANA = 'AdminKpyPromoLanding';

    /** 1 = hay que sincronizar con imágenes en la próxima visita (tras instalar o sincronizar sin ellas). */
    public const CONF_PENDIENTE = 'KPL_PENDIENTE';

    /** Las marcas prioritarias (nombres separados por comas): primero en los productos de la portada durante una campaña. */
    public const CONF_MARCAS = 'KPL_MARCAS_PRIORIDAD';

    /** Cuántos productos en promoción salen en la portada de la tienda, debajo del bloque de campaña (0 = ninguno). */
    public const CONF_PORTADA_N = 'KPL_PORTADA_N';

    /**
     * Los hooks que contesta (los nuevos se registran al actualizar, en `upgrade/`). El de consulta
     * que **dispara** (`actionKpyCouponsPromociones`, lo contesta kpycoupons) no va aquí: quien solo
     * pregunta no se registra como oyente.
     */
    public const HOOKS = [
        'moduleRoutes', 'actionKpyCouponsReparto', 'actionFrontControllerSetMedia', 'displayKpyPromoMarca',
        'displayBanner', 'actionProductFlagsModifier', 'actionPresentProductListing', 'actionKpyCouponsCampana',
        'actionKpyCouponsResumen', 'displayKpyCouponsFormulario', 'actionKpyCouponsGuardar', 'displayContentWrapperTop',
        'displayKpyPromoMenu', 'actionDoofinderExtendFeed',
    ];

    /** Las claves de configuración nuestras (para dejarlo limpio al desinstalar). */
    private const CONFIGURACION = [
        self::CONF_PENDIENTE, KplCategorias::CONF_RAIZ, KplBiblioteca::CONF_REMOVEBG, KplBiblioteca::CONF_REMBG, KplBiblioteca::CONF_REMBG_MODELO,
        KplCampanas::CONF_CLAVES, KplHeroes::CONF_TIENDA, KplSincronizar::CONF_FEATURE, KplSincronizar::CONF_PERRO, KplSincronizar::CONF_GATO,
        self::CONF_MARCAS, self::CONF_PORTADA_N, 'KPL_ACCESO_1', 'KPL_ACCESO_2',
    ];

    /** Mientras se presentan los productos del bloque de la portada: la foto de las tarjetas también ahí. */
    private bool $heroesPortada = false;
    private ?bool $heroesCache = null;

    public function __construct()
    {
        $this->name = 'kpypromolanding';
        $this->tab = 'front_office_features';
        $this->version = '1.5.1';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->dependencies = ['kpycoupons'];
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Kompy · Landing de promociones', [], 'Modules.Kpypromolanding.Admin');
        $this->description = $this->trans('/promociones, las categorías automáticas de cada promoción, el bloque de promociones en la landing de cada marca y las campañas (Black Friday).', [], 'Modules.Kpypromolanding.Admin');
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        if (!parent::install() || !$this->tablas()) {
            return false;
        }
        foreach (self::HOOKS as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }
        if (!$this->pestana()) {
            return false;
        }
        $this->configuracionInicial();
        try {
            KplCampanas::crearPredefinidas();
            KplCategorias::raiz();
            KplBiblioteca::carpeta();
            // Las categorías y el resumen, ya; las imágenes, en la primera visita (en consola no hay dominio).
            KplSincronizar::todo((int) Configuration::get('PS_SHOP_DEFAULT'), false);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, al instalar: ' . $e->getMessage(), 3, null, 'Kpypromolanding', 0, true);
        }
        Configuration::updateGlobalValue(self::CONF_PENDIENTE, 1);

        return true;
    }

    /**
     * Desinstalar deja la tienda como estaba de cara al cliente: las categorías nuestras vacías y
     * apagadas (no se borran: son datos de Kompy y direcciones que están en emails), la pestaña y
     * los hooks fuera, la caché de la landing fuera. Las tablas de datos y las imágenes se quedan
     * por si se vuelve a instalar; para quitarlo todo de verdad está «Borrar todo» en la pantalla.
     */
    public function uninstall(): bool
    {
        try {
            KplCategorias::apagarTodas();
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, al desinstalar: ' . $e->getMessage(), 3, null, 'Kpypromolanding', 0, true);
        }
        $this->quitarPestana();
        Db::getInstance()->execute('DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'kpy_promo_dato');
        foreach (self::CONFIGURACION as $clave) {
            if ($clave !== KplCategorias::CONF_RAIZ) {
                Configuration::deleteByName($clave);
            }
        }

        return parent::uninstall();
    }

    public function tablas(): bool
    {
        $p = _DB_PREFIX_;
        $motor = _MYSQL_ENGINE_;
        $db = Db::getInstance();

        return (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_promo_categoria (
                id_kpy_promo_categoria INT UNSIGNED NOT NULL AUTO_INCREMENT,
                clave VARCHAR(128) NOT NULL,
                id_category INT UNSIGNED NOT NULL,
                id_manufacturer INT UNSIGNED NOT NULL DEFAULT 0,
                tipo VARCHAR(16) NOT NULL DEFAULT '',
                mascota VARCHAR(8) NOT NULL DEFAULT '',
                permanente TINYINT(1) NOT NULL DEFAULT 0,
                productos INT UNSIGNED NOT NULL DEFAULT 0,
                pct DECIMAL(6,3) NOT NULL DEFAULT 0,
                activa TINYINT(1) NOT NULL DEFAULT 0,
                date_add DATETIME NULL,
                date_upd DATETIME NULL,
                PRIMARY KEY (id_kpy_promo_categoria),
                UNIQUE KEY clave (clave),
                KEY categoria (id_category)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Lo nuestro de cada promoción de kpycoupons: la clave y lo del paso 6 (vacío = automático).
            "CREATE TABLE IF NOT EXISTS {$p}kpy_promo_promo (
                id_kpy_coupon INT UNSIGNED NOT NULL,
                clave VARCHAR(128) NOT NULL DEFAULT '',
                clave_manual TINYINT(1) NOT NULL DEFAULT 0,
                listar TINYINT(1) NOT NULL DEFAULT 1,
                titulo VARCHAR(160) NOT NULL DEFAULT '',
                texto VARCHAR(255) NOT NULL DEFAULT '',
                imagen VARCHAR(64) NOT NULL DEFAULT '',
                orden INT NOT NULL DEFAULT 0,
                campanas VARCHAR(64) NOT NULL DEFAULT '',
                date_upd DATETIME NULL,
                PRIMARY KEY (id_kpy_coupon),
                KEY clave (clave)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_promo_imagen (
                id_kpy_promo_imagen INT UNSIGNED NOT NULL AUTO_INCREMENT,
                origen VARCHAR(16) NOT NULL,
                id_ref INT UNSIGNED NOT NULL DEFAULT 0,
                id_product INT UNSIGNED NOT NULL DEFAULT 0,
                id_manufacturer INT UNSIGNED NOT NULL DEFAULT 0,
                mascota VARCHAR(8) NOT NULL DEFAULT '',
                tipo VARCHAR(8) NOT NULL DEFAULT '',
                gama VARCHAR(40) NOT NULL DEFAULT '',
                fichero VARCHAR(64) NOT NULL,
                firma VARCHAR(40) NOT NULL DEFAULT '',
                ancho SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                alto SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                bytes INT UNSIGNED NOT NULL DEFAULT 0,
                objetos TINYINT UNSIGNED NOT NULL DEFAULT 0,
                apta TINYINT(1) NOT NULL DEFAULT 1,
                motor VARCHAR(16) NOT NULL DEFAULT 'propio',
                nota VARCHAR(255) NOT NULL DEFAULT '',
                date_add DATETIME NULL,
                PRIMARY KEY (id_kpy_promo_imagen),
                UNIQUE KEY origen (origen, id_ref, fichero),
                KEY marca (id_manufacturer)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // La medida de cada foto del catálogo (¿un solo saco de pie?), para elegir la de las tarjetas.
            'CREATE TABLE IF NOT EXISTS ' . $p . KplHeroes::TABLA . " (
                id_image INT UNSIGNED NOT NULL,
                id_product INT UNSIGNED NOT NULL DEFAULT 0,
                firma VARCHAR(40) NOT NULL DEFAULT '',
                objetos TINYINT UNSIGNED NOT NULL DEFAULT 0,
                ancho SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                alto SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                apta TINYINT(1) NOT NULL DEFAULT 1,
                nota VARCHAR(64) NOT NULL DEFAULT '',
                date_upd DATETIME NULL,
                PRIMARY KEY (id_image),
                KEY producto (id_product)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // El resumen de la landing y otros datos calculados (JSON).
            "CREATE TABLE IF NOT EXISTS {$p}kpy_promo_dato (
                nombre VARCHAR(64) NOT NULL,
                valor MEDIUMTEXT NULL,
                date_upd DATETIME NULL,
                PRIMARY KEY (nombre)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Las campañas (Black Friday…): fechas, piel, textos, etiquetas de campaña y orden de marcas.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_promo_campana (
                id_kpy_promo_campana INT UNSIGNED NOT NULL AUTO_INCREMENT,
                clave VARCHAR(32) NOT NULL,
                nombre VARCHAR(64) NOT NULL DEFAULT '',
                activa TINYINT(1) NOT NULL DEFAULT 1,
                desde DATETIME NULL,
                hasta DATETIME NULL,
                piel VARCHAR(16) NOT NULL DEFAULT 'negra',
                titulo VARCHAR(120) NOT NULL DEFAULT '',
                texto VARCHAR(255) NOT NULL DEFAULT '',
                lema VARCHAR(120) NOT NULL DEFAULT '',
                texto_antes VARCHAR(255) NOT NULL DEFAULT '',
                texto_despues VARCHAR(255) NOT NULL DEFAULT '',
                cinta TINYINT(1) NOT NULL DEFAULT 1,
                cinta_texto VARCHAR(120) NOT NULL DEFAULT '',
                redirigir TINYINT(1) NOT NULL DEFAULT 1,
                sin_carrusel TINYINT(1) NOT NULL DEFAULT 1,
                etiquetas TEXT NULL,
                colores TEXT NULL,
                orden_marcas VARCHAR(255) NOT NULL DEFAULT '',
                date_add DATETIME NULL,
                date_upd DATETIME NULL,
                PRIMARY KEY (id_kpy_promo_campana),
                UNIQUE KEY clave (clave)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && $this->columnas();
    }

    /** Las columnas que se han añadido después de la primera versión de cada tabla (si faltan, se añaden). */
    private function columnas(): bool
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $nuevas = [
            'kpy_promo_imagen' => [
                'objetos' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER bytes',
                'apta' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER objetos',
                // 1.1.1: las subidas a mano con su tipo (logo, saco, lata) y su gama (Rafa, 15/10).
                'tipo' => "VARCHAR(8) NOT NULL DEFAULT '' AFTER mascota",
                'gama' => "VARCHAR(40) NOT NULL DEFAULT '' AFTER tipo",
            ],
            // 1.1.0: el bloque de campaña sustituye al carrusel de la portada (Rafa, 14/10) y la paleta de la campaña (15/10).
            'kpy_promo_campana' => [
                'sin_carrusel' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER redirigir',
                'colores' => 'TEXT NULL AFTER etiquetas',
            ],
            // 1.4.0: el % de cada categoría (el mayor de sus promociones vivas), para ordenar los
            // listados sin cruzar las tablas de kpycoupons en cada visita.
            'kpy_promo_categoria' => [
                'pct' => 'DECIMAL(6,3) NOT NULL DEFAULT 0 AFTER productos',
            ],
        ];
        foreach ($nuevas as $tabla => $columnas) {
            $hay = array_column($db->executeS("SHOW COLUMNS FROM {$p}{$tabla}") ?: [], 'Field');
            foreach ($columnas as $columna => $definicion) {
                if (!in_array($columna, $hay, true) && !$db->execute("ALTER TABLE {$p}{$tabla} ADD COLUMN {$columna} {$definicion}")) {
                    return false;
                }
            }
        }

        return true;
    }

    /** La entrada del menú: Catálogo → «Kompy · Landing de promociones». */
    public function pestana(): bool
    {
        if (Tab::getIdFromClassName(self::PESTANA)) {
            return true;
        }
        $tab = new Tab();
        $tab->class_name = self::PESTANA;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->active = 1;
        $tab->icon = 'storefront';
        foreach (Language::getLanguages(false) as $idioma) {
            $tab->name[(int) $idioma['id_lang']] = 'Kompy · Landing de promociones';
        }

        return (bool) $tab->add();
    }

    private function quitarPestana(): bool
    {
        $id = (int) Tab::getIdFromClassName(self::PESTANA);
        if (!$id) {
            return true;
        }

        return (bool) (new Tab($id))->delete();
    }

    /** Los accesos de la landing: «Especial antiparasitarios» y «Cachorro o gatito», por su nombre, si existen. */
    private function configuracionInicial(): void
    {
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $buscar = static function (string $nombre) use ($idioma): int {
            return (int) Db::getInstance()->getValue(
                'SELECT c.id_category FROM ' . _DB_PREFIX_ . 'category c INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON cl.id_category = c.id_category AND cl.id_lang = ' . $idioma
                . " WHERE c.active = 1 AND cl.name LIKE '" . pSQL($nombre) . "%' ORDER BY c.level_depth ASC, c.id_category ASC"
            );
        };
        if (!(int) Configuration::getGlobalValue('KPL_ACCESO_1')) {
            Configuration::updateGlobalValue('KPL_ACCESO_1', $buscar('Especial antiparasitarios'));
        }
        if (!(int) Configuration::getGlobalValue('KPL_ACCESO_2')) {
            Configuration::updateGlobalValue('KPL_ACCESO_2', $buscar('Cachorro o gatito'));
        }
        // La portada de la tienda durante una campaña: Boske primero (el buque de la web) y 8 productos.
        if (Configuration::getGlobalValue(self::CONF_MARCAS) === false) {
            Configuration::updateGlobalValue(self::CONF_MARCAS, 'Boske');
        }
        if (Configuration::getGlobalValue(self::CONF_PORTADA_N) === false) {
            Configuration::updateGlobalValue(self::CONF_PORTADA_N, 8);
        }
        KplSincronizar::caracteristicaMascota();
    }

    // ---------- hooks: la landing ----------

    /**
     * Las direcciones de la landing. El orden importa: la primera que encaja, manda; «todas» y los
     * tipos van antes que `{clave}`, que se queda con lo demás.
     */
    public function hookModuleRoutes(array $params): array
    {
        $base = ['fc' => 'module', 'module' => $this->name];
        $tipos = implode('|', array_values(KplClaves::TIPO_URL));
        $rutas = [];
        $campanas = KplCampanas::clavesParaRutas();
        if ($campanas !== '') {
            // `/blackfriday`: la landing de una campaña (solo las claves de las campañas activas).
            $rutas['module-kpypromolanding-campana'] = [
                'rule' => '{campana}',
                'keywords' => ['campana' => ['regexp' => $campanas, 'param' => 'campana']],
                'controller' => 'campana', 'params' => $base,
            ];
        }

        return $rutas + [
            'module-kpypromolanding-landing' => ['rule' => 'promociones', 'keywords' => [], 'controller' => 'landing', 'params' => $base],
            'module-kpypromolanding-todas' => ['rule' => 'promociones/todas', 'keywords' => [], 'controller' => 'todas', 'params' => $base],
            'module-kpypromolanding-tipo-mascota' => [
                'rule' => 'promociones/{tipo}/{mascota}',
                'keywords' => [
                    'tipo' => ['regexp' => $tipos, 'param' => 'tipo'],
                    'mascota' => ['regexp' => 'perros|gatos', 'param' => 'mascota'],
                ],
                'controller' => 'tipo', 'params' => $base,
            ],
            'module-kpypromolanding-tipo' => [
                'rule' => 'promociones/{tipo}',
                'keywords' => ['tipo' => ['regexp' => $tipos, 'param' => 'tipo']],
                'controller' => 'tipo', 'params' => $base,
            ],
            'module-kpypromolanding-promo' => [
                'rule' => 'promociones/{clave}',
                'keywords' => ['clave' => ['regexp' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'param' => 'clave']],
                'controller' => 'promo', 'params' => $base,
            ],
        ];
    }

    /** `kpycoupons` ha rehecho el reparto: se rehace todo lo nuestro. */
    public function hookActionKpyCouponsReparto(array $params): void
    {
        $idTienda = (int) ($params['id_shop'] ?? Configuration::get('PS_SHOP_DEFAULT'));
        $this->sincronizar($idTienda, true);
    }

    /**
     * En cada visita: si hay una sincronización pendiente (tras instalar), se hace después de
     * contestar. Y la hoja de estilos en la landing de una marca (el bloque de promociones).
     */
    public function hookActionFrontControllerSetMedia(): void
    {
        if ((int) Configuration::getGlobalValue(self::CONF_PENDIENTE) === 1) {
            static $apuntado = false;
            if (!$apuntado) {
                $apuntado = true;
                $idTienda = (int) $this->context->shop->id;
                register_shutdown_function(function () use ($idTienda): void {
                    if (function_exists('fastcgi_finish_request')) {
                        @fastcgi_finish_request();
                    }
                    if ((int) Configuration::getGlobalValue(self::CONF_PENDIENTE) === 1) {
                        $this->sincronizar($idTienda, true);
                    }
                });
            }
        }
        if (Tools::getValue('module') === 'kpymanufacturer' && Tools::getValue('controller') === 'landing') {
            $this->context->controller->registerStylesheet('module-kpypromolanding', 'modules/' . $this->name . '/views/css/promos.css', ['media' => 'all', 'priority' => 210, 'version' => $this->sello('views/css/promos.css')]);
        }
    }

    /**
     * La fecha del fichero como versión de una hoja de estilos (`promos.css?202610141530`): al
     * desplegar una nueva, el navegador no se queda con la que tenía guardada.
     */
    private function sello(string $ruta): string
    {
        $t = @filemtime(_PS_MODULE_DIR_ . $this->name . '/' . $ruta);

        return $t ? date('YmdHi', $t) : $this->version;
    }

    /**
     * El bloque de la landing de una marca: todas sus promociones vivas. Lo llama la plantilla de
     * `kpymanufacturer` con `{hook h='displayKpyPromoMarca' id_manufacturer=…}`; si no le pasan la
     * marca, la saca de la dirección (`marcas/{brand}`). Sin promoción viva devuelve vacío y la
     * plantilla pone lo de siempre. Con una campaña viva, el bloque lleva su piel y su lema.
     */
    public function hookDisplayKpyPromoMarca(array $params): string
    {
        $id = (int) ($params['id_manufacturer'] ?? 0);
        if ($id <= 0 && Tools::getValue('brand') && method_exists('Manufacturer', 'getIDByRewrite')) {
            $id = (int) Manufacturer::getIDByRewrite((string) Tools::getValue('brand'));
        }
        if ($id <= 0) {
            return '';
        }
        $c = KplCampanas::viva();
        $promos = KplPromos::conCampana(KplPromos::conUrls(KplPromos::deMarca($id)), $c);
        if ($promos === []) {
            return '';
        }
        $marca = (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'manufacturer WHERE id_manufacturer = ' . $id);
        $campana = null;
        if ($c !== null && KplPromos::deCampana($c, $promos) !== []) {
            $campana = $c + KplCampanas::textos($c) + ['url' => KplCampanas::url($c), 'estilo' => KplCampanas::estilo($c)];
        }
        $this->context->smarty->assign('kpl', ['marca' => $marca, 'promos' => array_values($promos), 'campana' => $campana]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/marca.tpl');
    }

    /**
     * La foto de cada tarjeta de producto en las páginas de la landing (y, si se pide en la
     * pantalla, en los listados de toda la tienda): la que decidió `KplHeroes` al sincronizar, un
     * solo saco de pie. Si la portada del catálogo ya vale, no cambia nada. No escribe nada: la
     * ficha y el catálogo siguen con su portada.
     */
    public function hookActionPresentProductListing(array $params): void
    {
        $p = $params['presentedProduct'] ?? null;
        if (!is_object($p) || !($p instanceof ArrayAccess) || !$this->heroesAqui()) {
            return;
        }
        try {
            $idImagen = KplPromos::heroe((int) ($p['id_product'] ?? 0));
            if ($idImagen <= 0) {
                return;
            }
            $portada = $p['cover'] ?? null;
            if (!method_exists($p, 'offsetSet') || (is_array($portada) && (int) ($portada['id_image'] ?? 0) === $idImagen)) {
                return;
            }
            // Las imágenes de la tarjeta son solo las del formato que enseña; si la foto buena es de
            // otro formato (o de ninguno), se piden todas las de la ficha.
            $imagenes = (array) ($p['images'] ?? []);
            $hallada = $this->imagenEntre($imagenes, $idImagen);
            if ($hallada === null) {
                $todas = (new PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($this->context->link))
                    ->getAllProductImages(['id_product' => (int) $p['id_product']], $this->context->language);
                $hallada = $this->imagenEntre($todas, $idImagen);
            }
            if ($hallada !== null) {
                $p->offsetSet('cover', $hallada, true);
            }
        } catch (Throwable $e) {
            // La tarjeta se queda con su portada.
        }
    }

    /** La imagen con ese id entre las de un producto (tal como las presenta PrestaShop), o null. */
    private function imagenEntre(array $imagenes, int $idImagen): ?array
    {
        foreach ($imagenes as $imagen) {
            if (is_array($imagen) && (int) ($imagen['id_image'] ?? 0) === $idImagen) {
                return $imagen;
            }
        }

        return null;
    }

    /** ¿Se aplica la foto héroe en esta página? En las de la landing y en el bloque de la portada siempre; en el resto, si se pidió. */
    private function heroesAqui(): bool
    {
        if ($this->heroesPortada) {
            return true;
        }
        if ($this->heroesCache === null) {
            $c = $this->context->controller ?? null;
            $this->heroesCache = $c instanceof KplListadoFrontController || $c instanceof KpypromolandingLandingModuleFrontController || KplHeroes::enTodaLaTienda();
        }

        return $this->heroesCache;
    }

    // ---------- hooks: la campaña, por encima de toda la tienda ----------

    /**
     * La cinta de campaña arriba de todas las páginas mientras la campaña está viva (y la tiene
     * puesta), con los estilos que la campaña pone en toda la tienda: el color del texto de sus
     * etiquetas (el tema pinta el fondo que le llega, pero no el texto) y el precio Special Price de
     * las tarjetas en sus colores. Lleva su propio estilo dentro para no cargar la hoja de la
     * landing en toda la tienda.
     */
    public function hookDisplayBanner(array $params): string
    {
        $c = KplCampanas::viva();
        if ($c === null) {
            return '';
        }
        $etiquetas = [];
        foreach (KplCampanas::TIPOS_ETIQUETA as $tipo) {
            $e = KplCampanas::etiqueta($c, $tipo, 0);
            if ($e !== null && KplPromos::tipoEnCampana($c, $tipo)) {
                $etiquetas[$tipo] = ['background' => $e['background'], 'color' => $e['color']];
            }
        }
        $especial = $etiquetas['special_price'] ?? null;
        // La cinta, con la paleta de la campaña: el fondo y el acento (en la piel clara, el acento sobre el fondo claro).
        $paleta = KplCampanas::coloresDe($c);
        $this->context->smarty->assign('kpl_cinta', [
            'con_cinta' => (int) $c['cinta'] === 1 && Tools::getValue('controller') !== 'campana',
            'url' => KplCampanas::url($c),
            'texto' => (string) ($c['cinta_texto'] !== '' ? $c['cinta_texto'] : $c['nombre']),
            'fondo' => $paleta['fondo'],
            'color' => $paleta['acento'],
            'etiquetas' => $etiquetas,
            // El precio Special Price de las tarjetas, en los colores de la etiqueta de campaña.
            'precio_fondo' => $especial !== null ? (string) $especial['background'] : '',
            'precio_color' => $especial !== null ? (string) $especial['color'] : '',
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/cinta.tpl');
    }

    /**
     * El botón «Ofertas y promociones» del menú mientras hay una campaña viva: con el nombre de la
     * campaña y sus colores. Lo llama la plantilla de `kpymenu` (parche) por el hook
     * `displayKpyPromoMenu`; sin campaña viva (o sin este módulo) devuelve vacío y la plantilla pinta
     * el botón de siempre. `movil` = la entrada del menú móvil.
     */
    public function hookDisplayKpyPromoMenu(array $params): string
    {
        $c = KplCampanas::viva();
        if ($c === null) {
            return '';
        }
        [$fondo, $color] = KplCampanas::coloresMenu($c);
        $this->context->smarty->assign('kpl_menu', [
            'nombre' => (string) $c['nombre'],
            'url' => KplCampanas::url($c),
            'fondo' => $fondo,
            'color' => $color,
            'movil' => !empty($params['movil']),
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/menu.tpl');
    }

    /**
     * El bloque de campaña en la portada de la tienda (arriba del todo, en el sitio del carrusel):
     * mientras dura, con su título, su lema, los días que quedan, las marcas y los accesos por
     * tipo, y el carrusel grande de la portada se esconde (si la campaña lo pide: hace de portada);
     * las dos semanas antes, una línea que avisa de cuándo empieza. En las demás páginas no pinta
     * nada.
     */
    public function hookDisplayContentWrapperTop(array $params): string
    {
        $controlador = $this->context->controller ?? null;
        if (!$controlador || (string) ($controlador->php_self ?? '') !== 'index') {
            return '';
        }
        $c = null;
        foreach (KplCampanas::todas() as $x) {
            if ($x['estado'] === 'viva' || ($x['estado'] === 'programada' && KplCampanas::textos($x)['faltan'] <= 15)) {
                $c = $x;
                break;
            }
        }
        if ($c === null) {
            return '';
        }
        $tipos = [];
        $productos = [];
        if ($c['estado'] === 'viva') {
            foreach (KplPromos::tiposConCampana(KplPromos::tipos(), $c) as $t) {
                $tipos[] = ['nombre' => $t['nombre'], 'n' => $t['n'], 'url' => $t['url'], 'tipo' => $t['tipo']];
            }
            // Los productos de la campaña, con las marcas prioritarias (Boske…) primero.
            $n = (int) Configuration::getGlobalValue(self::CONF_PORTADA_N);
            if ($n > 0) {
                try {
                    $this->heroesPortada = true;
                    $productos = KplPresentador::productos(KplPromos::portada($c, $n, KplPromos::marcasPrioritarias()), $n);
                } catch (Throwable $e) {
                    $productos = [];
                } finally {
                    $this->heroesPortada = false;
                }
            }
        }
        $this->context->smarty->assign('kpl_home', [
            'c' => $c + KplCampanas::textos($c),
            'url' => KplCampanas::url($c),
            'url_todas' => KplPromos::urlTodas(),
            'marcas' => $c['estado'] === 'viva' ? KplPromos::marcasDeCampana($c) : [],
            'tipos' => $tipos,
            'productos' => $productos,
            'negra' => $c['piel'] === 'negra',
            'estilo' => KplCampanas::estiloPortada($c),
            'sin_carrusel' => $c['estado'] === 'viva' && (int) ($c['sin_carrusel'] ?? 1) === 1,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/home.tpl');
    }

    /**
     * Las etiquetas de campaña, al pintar cada producto (listados, buscador, ficha): mientras una
     * campaña está viva, las etiquetas de las promociones que entran en ella cambian de nombre y de
     * color («Special Price» → «Black Friday −15 %»). Es el último que toca las etiquetas (va después
     * de `kpycoupons`, que las decide) y no escribe nada: al terminar la campaña vuelven solas.
     */
    public function hookActionProductFlagsModifier(array $params): void
    {
        $c = KplCampanas::viva();
        if ($c === null || empty($params['flags']) || !is_array($params['flags'])) {
            return;
        }
        $idProducto = (int) ($params['product']['id_product'] ?? 0);
        $suyas = KplPromos::enCampana($c, $idProducto);
        // Un Special Price puesto a mano en el catálogo (no es una promoción de Kompy · Promociones)
        // entra cuando entra todo (ninguna promoción marcada en el paso 6), con el % del precio.
        if (!isset($suyas['special_price']) && isset($params['flags']['kpy_special_price']) && KplPromos::promoEntra($c, 0)) {
            $suyas['special_price'] = $this->porcentajeDelPrecio((array) ($params['product'] ?? []));
        }
        if ($suyas === []) {
            return;
        }
        foreach ($params['flags'] as $k => $flag) {
            $tipo = $this->tipoDeEtiqueta((string) $k, (array) $flag, $idProducto, $suyas);
            if ($tipo === null || !isset($suyas[$tipo])) {
                continue;
            }
            $e = KplCampanas::etiqueta($c, $tipo, (float) $suyas[$tipo]);
            if ($e === null) {
                continue;
            }
            if ((float) $suyas[$tipo] <= 0 && $tipo !== 'kilos_gratis') {
                // Sin % conocido, el nombre a secas («Black Friday»), nunca «−0 %».
                $e['label'] = KplCampanas::etiquetaCorta($c, $tipo) ?: $e['label'];
            }
            $params['flags'][$k]['label'] = $e['label'];
            // El tema (`product-flags.tpl`) pinta el fondo que le llega en el `style` de la etiqueta
            // («background-color:X !important;border-color:X !important») y el color del texto lo
            // fija en blanco con !important dentro de una capa CSS, que gana a cualquier hoja
            // normal: solo el estilo en línea puede con ella. Por eso el color del texto va dentro
            // del valor del fondo. Y la clase `kpl-camp--{tipo}`, para las hojas de estilo.
            $params['flags'][$k]['background'] = $e['background'] . ' !important;color:' . $e['color'];
            $params['flags'][$k]['color'] = $e['color'];
            $params['flags'][$k]['type'] = trim((string) ($flag['type'] ?? '') . ' kpl-camp kpl-camp--' . $tipo);
        }
    }

    /**
     * El tipo (de la landing) de una etiqueta de producto, o null si no es de una promoción.
     *
     * Cómo llegan de kpycoupons (3.0.0): cada etiqueta suya lleva el campo `kpc` =
     * `{tipo: cupon|segunda_unidad|regalo|special_price, id_kpy_coupon, pct}` (también la
     * `kpy_special_price` de kpyspecialprices, que kpycoupons viste). Eso es lo que se mira primero.
     * De respaldo, la clave `kpy_special_price` y el `type` (`kpc-cupon`, `kpc-segunda`, `kpc-regalo`,
     * `kpc-liquidacion`, `kpc-outlet`, `kpc-bajada`, `kpc-especial`). Un regalo entra en la landing
     * como «Kilos gratis» o «Regalos» según lo que regala: lo dice el resumen por la promoción. La
     * de kilos gratis del catálogo («kg Gratis», de kpyproductflags) se reconoce por su texto.
     *
     * @param array<string, float> $suyas los tipos del producto que entran en la campaña
     */
    private function tipoDeEtiqueta(string $clave, array $flag, int $idProducto, array $suyas): ?string
    {
        $regalo = static fn (): string => isset($suyas['kilos_gratis']) || !isset($suyas['regalos']) ? 'kilos_gratis' : 'regalos';
        $kpc = is_array($flag['kpc'] ?? null) ? $flag['kpc'] : null;
        if ($kpc !== null) {
            $t = (string) ($kpc['tipo'] ?? '');
            if ($t === 'regalo') {
                return KplPromos::tipoDePromo($idProducto, (int) ($kpc['id_kpy_coupon'] ?? 0)) ?? $regalo();
            }
            if (in_array($t, ['special_price', 'cupon', 'segunda_unidad'], true)) {
                return $t;
            }
        }
        if ($clave === 'kpy_special_price') {
            return 'special_price';
        }
        $type = (string) ($flag['type'] ?? '');
        $porTipo = [
            'kpc-cupon' => 'cupon', 'kpc-segunda' => 'segunda_unidad', 'kpc-regalo' => 'regalo',
            'kpc-especial' => 'special_price', 'kpc-liquidacion' => 'special_price', 'kpc-outlet' => 'special_price', 'kpc-bajada' => 'special_price',
        ];
        foreach ($porTipo as $prefijo => $t) {
            if ($type === $prefijo || strncmp($type, $prefijo . ' ', strlen($prefijo) + 1) === 0) {
                return $t === 'regalo' ? $regalo() : $t;
            }
        }
        if (mb_strtolower(trim((string) ($flag['label'] ?? ''))) === 'kg gratis') {
            return 'kilos_gratis';
        }

        return null;
    }

    /** El % de rebaja del precio de un producto tal como lo presenta PrestaShop (0 si no es un %). */
    private function porcentajeDelPrecio(array $producto): float
    {
        $sp = $producto['specific_prices'] ?? null;
        if (is_array($sp) && (string) ($sp['reduction_type'] ?? '') === 'percentage') {
            return round((float) ($sp['reduction'] ?? 0) * 100, 1);
        }
        $pct = (float) ($producto['reduction_percentage'] ?? 0);
        if ($pct > 0) {
            return round($pct, 1);
        }
        $precio = (float) ($producto['price_amount'] ?? $producto['price'] ?? 0);
        $sin = (float) ($producto['regular_price_amount'] ?? $producto['price_without_reduction'] ?? 0);

        return $sin > $precio && $precio > 0 ? round((1 - $precio / $sin) * 100) : 0.0;
    }

    /**
     * `actionKpyCouponsCampana` (hook de consulta: lo dispara `kpycoupons` al pintar la ficha y la
     * cesta, con `array_return`): ¿hay una capa de campaña para este producto? Devuelve, por tipo de
     * promoción que entra en la campaña, el nombre y los colores de su etiqueta, y kpycoupons los
     * pone en el bloque del cupón o de la 2ª unidad, en el chip de cada formato y en la etiqueta del
     * precio. Null si no hay campaña o el producto no entra.
     *
     * Parámetros: `id_product`, `id_product_attribute` y `tipos` (los tipos que hay en pantalla:
     * cupon, segunda_unidad, special_price); si llegan tipos, solo se contestan esos.
     *
     * @return ?array{nombre: string, clave: string, piel: string, tipos: array<string, array{label: string, background: string, color: string}>}
     */
    public function hookActionKpyCouponsCampana(array $params): ?array
    {
        $c = KplCampanas::viva();
        if ($c === null) {
            return null;
        }
        $suyas = KplPromos::enCampana($c, (int) ($params['id_product'] ?? $params['product']['id_product'] ?? 0));
        $pedidos = array_values(array_filter(array_map('strval', (array) ($params['tipos'] ?? []))));
        $tipos = [];
        foreach ($suyas as $tipo => $pct) {
            if ($pedidos !== [] && !in_array((string) $tipo, $pedidos, true)) {
                continue;
            }
            $e = KplCampanas::etiqueta($c, (string) $tipo, (float) $pct);
            if ($e !== null) {
                $tipos[(string) $tipo] = $e;
            }
        }

        return $tipos === [] ? null : ['nombre' => (string) $c['nombre'], 'clave' => (string) $c['clave'], 'piel' => (string) $c['piel'], 'tipos' => $tipos];
    }

    /**
     * El feed de Doofinder durante una campaña (Black Friday…): a cada producto que entra en ella se
     * le añaden el nombre y los colores de campaña de sus etiquetas, para que el buscador diga «Black
     * Friday −25 %» en negro y oro, como la tienda. Seis columnas, siempre en la cabecera (vacías sin
     * campaña viva, para que el índice de Doofinder no las pierda):
     *
     *   kpy_campana, kpy_campana_fondo, kpy_campana_texto                    en lugar de «Precio especial»
     *   kpy_campana_promo, kpy_campana_promo_fondo, kpy_campana_promo_texto  en lugar de la etiqueta de promoción
     *
     * Va detrás de kpycoupons en el hook y trabaja sobre las filas que este ya ha añadido (sus columnas
     * `kpy_especial` y `kpy_promo_tipo`: datos del feed, no funciones de otro módulo). Un Special Price
     * del catálogo que no es promoción de Kompy · Promociones entra cuando entra todo (ninguna
     * promoción marcada en el paso 6), con el nombre a secas, como en los listados. Si falla algo, el
     * feed sigue como estaba y se apunta en el registro.
     */
    public function hookActionDoofinderExtendFeed(array $params): void
    {
        $columnas = ['kpy_campana', 'kpy_campana_fondo', 'kpy_campana_texto', 'kpy_campana_promo', 'kpy_campana_promo_fondo', 'kpy_campana_promo_texto'];
        $params['extra_header'] = array_values(array_unique(array_merge((array) ($params['extra_header'] ?? []), $columnas)));
        try {
            $c = KplCampanas::viva();
            if ($c === null) {
                return;
            }
            $filas = (array) ($params['extra_rows'] ?? []);
            foreach ($filas as $i => $fila) {
                $idProducto = (int) ($fila['id_product'] ?? 0);
                if ($idProducto <= 0) {
                    continue;
                }
                $campana = $this->campanaEnElFeed($c, $idProducto, (array) $fila);
                if ($campana !== []) {
                    $filas[$i] = $fila + $campana;
                }
            }
            $params['extra_rows'] = $filas;
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, feed de Doofinder: ' . $e->getMessage(), 3, null, 'Kpypromolanding', 0, true);
        }
    }

    /**
     * Las columnas de campaña de un producto del feed, a partir de lo que kpycoupons dice de él
     * (`kpy_especial`, `kpy_promo_tipo`) y de lo que entra en la campaña (`KplPromos::enCampana`).
     * Liquidación, outlet y bajada son el propio Special Price: van con las columnas de este, y la
     * hoja de estilos del buscador esconde su etiqueta genérica mientras hay campaña.
     *
     * @return array<string, string> vacío si la campaña no cambia nada en este producto
     */
    private function campanaEnElFeed(array $c, int $idProducto, array $fila): array
    {
        $suyas = KplPromos::enCampana($c, $idProducto);
        $salida = [];
        if ((string) ($fila['kpy_especial'] ?? '') === '1') {
            $pct = $suyas['special_price'] ?? (KplPromos::promoEntra($c, 0) ? 0.0 : null);
            $e = $pct !== null ? $this->etiquetaDeCampana($c, 'special_price', (float) $pct) : null;
            if ($e !== null) {
                $salida += ['kpy_campana' => $e['label'], 'kpy_campana_fondo' => $e['background'], 'kpy_campana_texto' => $e['color']];
            }
        }
        $tipo = [
            'cupon' => 'cupon',
            'segunda' => 'segunda_unidad',
            'regalo' => isset($suyas['kilos_gratis']) || !isset($suyas['regalos']) ? 'kilos_gratis' : 'regalos',
        ][(string) ($fila['kpy_promo_tipo'] ?? '')] ?? null;
        if ($tipo !== null && isset($suyas[$tipo])) {
            $e = $this->etiquetaDeCampana($c, $tipo, (float) $suyas[$tipo]);
            if ($e !== null) {
                $salida += ['kpy_campana_promo' => $e['label'], 'kpy_campana_promo_fondo' => $e['background'], 'kpy_campana_promo_texto' => $e['color']];
            }
        }

        return $salida;
    }

    /** La etiqueta de campaña de un tipo con su %; sin % conocido, el nombre a secas («Black Friday»), nunca «−0 %». */
    private function etiquetaDeCampana(array $c, string $tipo, float $pct): ?array
    {
        $e = KplCampanas::etiqueta($c, $tipo, $pct);
        if ($e !== null && $pct <= 0 && $tipo !== 'kilos_gratis') {
            $e['label'] = KplCampanas::etiquetaCorta($c, $tipo) ?: $e['label'];
        }

        return $e;
    }

    /**
     * La cesta (`kpycoupons`) deja repasar lo que cuenta de las promociones: durante la campaña, los
     * vales y la línea del Special Price se llaman como la campaña («Cupón Black −10 % · EUKA10»,
     * «Black Friday en 2 productos») y llevan sus colores.
     */
    public function hookActionKpyCouponsResumen(array $params): void
    {
        $c = KplCampanas::viva();
        if ($c === null || !isset($params['resumen']) || !is_array($params['resumen'])) {
            return;
        }
        $r = &$params['resumen'];
        foreach ((array) ($r['vales'] ?? []) as $k => $v) {
            $idPromo = (int) ($v['id_kpy_coupon'] ?? 0);
            $tipo = ($v['tipo'] ?? '') === 'segunda' ? 'segunda_unidad' : 'cupon';
            // Un cupón por canal no está en la landing ni lleva la piel de campaña en la ficha: tampoco en la cesta.
            if ($idPromo <= 0 || (string) ($v['ver_canal'] ?? '') !== '' || !KplPromos::promoEntra($c, $idPromo)) {
                continue;
            }
            $e = KplCampanas::etiqueta($c, $tipo, (float) ($v['pct'] ?? 0));
            if ($e !== null) {
                $r['vales'][$k]['texto'] = $e['label'] . ((string) ($v['codigo'] ?? '') !== '' ? ' · ' . $v['codigo'] : '');
                $r['vales'][$k]['fondo'] = $e['background'];
                $r['vales'][$k]['color'] = $e['color'];
            }
        }
        foreach ((array) ($r['lineas'] ?? []) as $k => $l) {
            if (($l['tipo'] ?? '') !== 'special_price' || !KplPromos::tipoEnCampana($c, 'special_price')) {
                continue;
            }
            $nombre = KplCampanas::etiquetaCorta($c, 'special_price');
            $e = KplCampanas::etiqueta($c, 'special_price', 0);
            if ($nombre === '' || $e === null) {
                continue;
            }
            $n = (int) ($l['n'] ?? 0);
            $r['lineas'][$k]['texto'] = $nombre . ($n === 1 ? ' en 1 producto' : ' en ' . $n . ' productos');
            $r['lineas'][$k]['estilo'] = 'background:' . $e['background'] . ';color:' . $e['color'];
            $r['lineas'][$k]['estilo_icono'] = 'background:' . $e['color'] . ';color:' . $e['background'];
        }
    }

    // ---------- hooks: el paso «En la landing» en el formulario de cada promoción (kpycoupons) ----------

    /**
     * El paso «En la landing» (el 6, normalmente) dentro del formulario de Kompy · Promociones: lo
     * que este módulo hace solo con la promoción (dirección, banner, foto) y lo que se puede cambiar
     * a mano. Todo opcional: vacío = automático. Parámetros del hook (kpycoupons 3.0.0): `version`,
     * `id` (0 = nueva), `valores`, `duplicar`, `paso` (el número que le toca: kpycoupons los cuenta
     * módulo a módulo), `url_editar`, `url_ver`.
     */
    public function hookDisplayKpyCouponsFormulario(array $params): string
    {
        $id = (int) ($params['id'] ?? 0);
        $valores = (array) ($params['valores'] ?? []);
        $paso = max(1, (int) ($params['paso'] ?? 6));
        try {
            $propia = $id > 0 ? Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_promo WHERE id_kpy_coupon = ' . $id) : null;
            $propia = is_array($propia) ? $propia : ['clave' => '', 'clave_manual' => 0, 'listar' => 1, 'titulo' => '', 'texto' => '', 'imagen' => '', 'orden' => 0, 'campanas' => ''];
            $clave = (string) $propia['clave'];
            $c = $clave !== '' ? KplPromos::clave($clave) : null;
            $imagen = (string) $propia['imagen'];
            $marcadas = array_filter(array_map('trim', explode(',', (string) $propia['campanas'])));
            $campanas = [];
            foreach (KplCampanas::todas() as $x) {
                if ((int) $x['activa'] === 1) {
                    $campanas[] = ['clave' => $x['clave'], 'nombre' => $x['nombre'], 'estado' => $x['estado'], 'fechas' => KplCampanas::textos($x)['fechas'], 'marcada' => in_array($x['clave'], $marcadas, true)];
                }
            }
            // Las imágenes subidas a mano, con su tipo, marca, mascota y gama; las de la marca de esta promoción, primero.
            $subidas = [];
            $idMarca = (int) ($c['id_manufacturer'] ?? 0);
            $nombresMarca = [];
            foreach (KplBiblioteca::subidas() as $s) {
                $url = KplBiblioteca::url((string) $s['fichero']);
                if ($url === '') {
                    continue;
                }
                $idM = (int) $s['id_manufacturer'];
                if ($idM > 0 && !isset($nombresMarca[$idM])) {
                    $nombresMarca[$idM] = (string) Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'manufacturer WHERE id_manufacturer = ' . $idM);
                }
                $partes = array_filter([
                    KplBiblioteca::TIPOS[(string) ($s['tipo'] ?? '')] ?? 'Otra foto',
                    (string) ($nombresMarca[$idM] ?? ''),
                    $s['mascota'] === 'perro' ? 'perros' : ($s['mascota'] === 'gato' ? 'gatos' : ''),
                    (string) ($s['gama'] ?? ''),
                ]);
                $subidas[] = ['id' => (int) $s['id_kpy_promo_imagen'], 'url' => $url, 'nota' => (string) $s['nota'], 'marca' => $idM, 'etiqueta' => implode(' · ', $partes), 'suya' => $idMarca > 0 && $idM === $idMarca];
            }
            usort($subidas, static fn (array $a, array $b): int => ((int) $b['suya'] <=> (int) $a['suya']) ?: ($b['id'] <=> $a['id']));
            $this->context->smarty->assign('kpl6', [
                'id' => $id,
                'paso' => $paso,
                'tipo' => (string) ($valores['tipo'] ?? ''),
                'clave' => $clave,
                'clave_manual' => (int) $propia['clave_manual'] === 1 ? $clave : '',
                'url' => $clave !== '' ? KplPromos::urlClave($clave) : '',
                'base' => rtrim(KplPromos::urlLanding(), '/') . '/',
                'listar' => (int) $propia['listar'] === 1,
                'titulo' => (string) $propia['titulo'],
                'texto' => (string) $propia['texto'],
                'titulo_auto' => (string) ($c['titulo'] ?? ''),
                'texto_auto' => (string) ($c['texto'] ?? ''),
                'imagen_modo' => preg_match('/^producto:(\d+)$/', $imagen, $m) ? 'producto' : (preg_match('/^biblioteca:(\d+)$/', $imagen, $m) ? 'biblioteca' : 'auto'),
                'imagen_id' => (int) ($m[1] ?? 0),
                'orden' => (int) $propia['orden'],
                'campanas' => $campanas,
                'subidas' => $subidas,
                'logo' => (string) ($c['logo'] ?? ''),
                'imagenes' => (array) ($c['imagenes'] ?? []),
                'estado' => (string) ($c['estado'] ?? ''),
                'url_pantalla' => $this->context->link->getAdminLink(self::PESTANA),
            ]);

            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/admin/paso6.tpl');
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, paso 6: ' . $e->getMessage(), 3, null, 'Kpypromolanding', $id, true);

            return '';
        }
    }

    /**
     * Al guardar una promoción en Kompy · Promociones (antes del reparto, que es el que avisa para
     * sincronizar): lo del paso 6 se escribe en `kpy_promo_promo`. Solo si el formulario traía el
     * paso (campo `kpl_paso6`); si no, no se toca nada. Parámetros del hook (kpycoupons 3.0.0):
     * `version`, `id`, `valores`, `promocion` (la fila pública; aquí solo hace falta el `id`).
     */
    public function hookActionKpyCouponsGuardar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0 || !Tools::getIsset('kpl_paso6')) {
            return;
        }
        try {
            $clave = KplClaves::slug(trim((string) Tools::getValue('kpl_clave')));
            $claveManual = $clave !== '' && KplClaves::claveValida($clave);
            $modo = (string) Tools::getValue('kpl_imagen_modo');
            $imagen = '';
            if ($modo === 'producto' && (int) Tools::getValue('kpl_imagen_producto') > 0) {
                $imagen = 'producto:' . (int) Tools::getValue('kpl_imagen_producto');
            } elseif ($modo === 'biblioteca' && (int) Tools::getValue('kpl_imagen_biblioteca') > 0) {
                $imagen = 'biblioteca:' . (int) Tools::getValue('kpl_imagen_biblioteca');
            }
            $campanas = [];
            foreach ((array) Tools::getValue('kpl_campanas', []) as $x) {
                $x = KplClaves::slug((string) $x);
                if ($x !== '') {
                    $campanas[] = str_replace('-', '', $x);
                }
            }
            $campos = [
                'clave_manual' => $claveManual ? 1 : 0,
                'listar' => Tools::getValue('kpl_listar') ? 1 : 0,
                'titulo' => pSQL(mb_substr(trim((string) Tools::getValue('kpl_titulo')), 0, 160)),
                'texto' => pSQL(mb_substr(trim((string) Tools::getValue('kpl_texto')), 0, 255)),
                'imagen' => pSQL($imagen),
                'orden' => (int) Tools::getValue('kpl_orden'),
                'campanas' => pSQL(mb_substr(implode(',', array_unique($campanas)), 0, 64)),
                'date_upd' => date('Y-m-d H:i:s'),
            ];
            if ($claveManual) {
                $campos['clave'] = pSQL($clave);
            }
            $db = Db::getInstance();
            if ($db->getValue('SELECT id_kpy_coupon FROM ' . _DB_PREFIX_ . 'kpy_promo_promo WHERE id_kpy_coupon = ' . $id)) {
                $db->update('kpy_promo_promo', $campos, 'id_kpy_coupon = ' . $id);
            } else {
                $db->insert('kpy_promo_promo', ['id_kpy_coupon' => $id, 'clave' => pSQL($clave)] + $campos);
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, al guardar el paso 6: ' . $e->getMessage(), 3, null, 'Kpypromolanding', $id, true);
        }
    }

    // ---------- lo demás ----------

    /**
     * La hoja de estilos de nuestras páginas. Lo poco que las tarjetas de los listados necesitan de
     * las hojas de `kpyspecialprices` y `kpyproductflags` (que esos módulos solo cargan en categoría,
     * ficha y portada) va copiado dentro de `promos.css`, con su origen: no se registra ninguna hoja
     * ajena.
     */
    public function hojasDeEstilo(FrontController $controlador): void
    {
        $controlador->registerStylesheet('module-kpypromolanding', 'modules/' . $this->name . '/views/css/promos.css', ['media' => 'all', 'priority' => 210, 'version' => $this->sello('views/css/promos.css')]);
    }

    /** Pantalla del módulo: la pestaña de Catálogo. */
    public function getContent(): string
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::PESTANA));

        return '';
    }

    /** Sincroniza con cerrojo y sin dejar que un fallo se propague a quien llama. */
    public function sincronizar(int $idTienda, bool $conImagenes): ?array
    {
        try {
            if ($conImagenes) {
                @set_time_limit(0); // una docena de recortes pueden pasar del medio minuto
            }
            $resultado = KplSincronizar::conCerrojo($idTienda, $conImagenes);
            if ($resultado !== null && $conImagenes) {
                Configuration::updateGlobalValue(self::CONF_PENDIENTE, 0);
            }
            KplPromos::olvidar();

            return $resultado;
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, al sincronizar: ' . $e->getMessage(), 3, null, 'Kpypromolanding', 0, true);

            return null;
        }
    }
}
