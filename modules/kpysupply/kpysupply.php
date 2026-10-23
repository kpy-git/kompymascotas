<?php
/**
 * Kompy · Acuerdos con proveedores (kpysupply).
 *
 * Lo que el proveedor nos da por una promoción (o sin promoción): una **redención** (al acabar
 * paga un % de lo vendido sin IVA), un **descuento en factura** mientras dura, o **precios de
 * coste nuevos** por EAN para un periodo (o hasta nueva orden). Se guarda por artículo (producto y
 * combinación) en las dos tablas que pidió Julián (`kpy_supplier_promo_type` y
 * `kpy_supplier_promo_products`), se sube pegando la lista del proveedor (EAN o referencia, con su
 * valor) con un verificador que dice qué se ha encontrado y qué no, y se consulta y se exporta a
 * Excel: la lista del acuerdo y las ventas del periodo (unidades y valor sin IVA de verdad, con lo
 * que toca cobrar al proveedor en una redención).
 *
 * Va con `kpycoupons` **sin llamar a nada suyo** (regla de Julián: ningún módulo llama a funciones
 * de otro). Lo que necesita de una promoción le llega por los hooks de kpycoupons (contrato 3.0.0,
 * `KpcContrato`): el paso del formulario de cada promoción (`displayKpyCouponsFormulario`, con el
 * número del paso), al guardarla (`actionKpyCouponsGuardar`), en cada reparto
 * (`actionKpyCouponsReparto`, que trae los artículos y la fila pública de cada promoción y cuál se
 * acaba de guardar) y, cuando hace falta saber los artículos de una promoción fuera del reparto
 * (al guardar, el botón «Poner al día»), el hook de consulta `actionKpyCouponsAlcance`, que
 * dispara este módulo y contesta kpycoupons. La lectura de listas es una copia propia
 * (`KsLectura`), y lo poco que lee de las tablas de kpycoupons está declarado en el README («Lo que
 * lee de kpycoupons»). Nada de esto cambia lo que ve el cliente.
 *
 * Plan y decisiones: docs/plan-acuerdos-proveedor.md del proyecto; el detalle, en README.md.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

spl_autoload_register(static function (string $clase): void {
    if (strncmp($clase, 'Ks', 2) === 0 && is_file($fichero = __DIR__ . '/classes/' . $clase . '.php')) {
        require $fichero;
    }
});

class Kpysupply extends Module
{
    public const PESTANA = 'AdminKpySupply';

    /** Los hooks que escucha (todos de kpycoupons). El de consulta `actionKpyCouponsAlcance` lo dispara, no lo escucha. */
    public const HOOKS = ['displayKpyCouponsFormulario', 'actionKpyCouponsGuardar', 'actionKpyCouponsReparto'];

    /** Avisos para el paso de una promoción (un % que no se entendió al guardar): JSON id => texto. */
    public const CONF_AVISOS = 'KPS_AVISOS_PASO';

    /** El número del paso si kpycoupons no lo dice: los cinco del formulario y este. */
    public const PASO_POR_DEFECTO = 6;

    /**
     * La versión de kpycoupons que hace falta: la del contrato (`KpcContrato`, 3.0.0): el hook de
     * consulta `actionKpyCouponsAlcance`; en el del reparto, `alcance`, `promociones` (filas
     * públicas) y `guardada`; en el del formulario, `paso`. (No hay 2.5.0: kpycoupons pasó de la
     * 2.4.1 a la 3.0.0.)
     */
    public const KPYCOUPONS_MINIMA = '3.0.0';

    public function __construct()
    {
        $this->name = 'kpysupply';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->dependencies = ['kpycoupons'];
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Kompy · Acuerdos con proveedores', [], 'Modules.Kpysupply.Admin');
        $this->description = $this->trans('Lo que el proveedor da en cada promoción (redención, descuento en factura o precios de coste nuevos), por artículo y con fechas; la lista pegada desde Excel con verificador; las ventas del periodo para cobrar la redención y contrastar facturas.', [], 'Modules.Kpysupply.Admin');
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        $error = self::faltaKpycoupons();
        if ($error !== '') {
            $this->_errors[] = $error;

            return false;
        }
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
        try {
            KsTipos::crear();
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpysupply, al instalar los tipos: ' . $e->getMessage(), 3, null, 'Kpysupply', 0, true);

            return false;
        }

        return true;
    }

    /**
     * Desinstalar quita la pestaña, los hooks y los ajustes. **Las tablas se quedan**: son datos de
     * administración (los acuerdos y lo que se cobró). Para quitarlas de verdad está el SQL de
     * `deshacer/` del paquete.
     */
    public function uninstall(): bool
    {
        Configuration::deleteByName(KsVentas::CONF_ESTADOS);
        Configuration::deleteByName(self::CONF_AVISOS);

        return $this->quitarPestana() && parent::uninstall();
    }

    /**
     * Las tablas. Las dos de Julián, tal cual salvo tres matices (`value` DECIMAL: un FLOAT pierde
     * céntimos; las fechas DATETIME como en PrestaShop, y «hasta nueva orden» = 2099-12-31 23:59:59
     * para que la clave única valga también ahí) y tres columnas más (`id_kpy_coupon`: de qué
     * promoción viene la fila; `origen`: del paso de la promoción o de una lista; `date_add` y
     * `date_upd`). Y una pequeña, `kpy_supplier_promo_coupon`: lo marcado en el paso de cada
     * promoción (tipo y %).
     */
    public function tablas(): bool
    {
        $p = _DB_PREFIX_;
        $motor = _MYSQL_ENGINE_;
        $db = Db::getInstance();
        $ok = (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_supplier_promo_type (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(255) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY nombre (nombre)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_supplier_promo_products (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_product INT UNSIGNED NOT NULL,
                id_product_attribute INT UNSIGNED NOT NULL DEFAULT 0,
                id_supplier_promo_type INT UNSIGNED NOT NULL,
                value DECIMAL(20,6) NOT NULL DEFAULT 0,
                date_from DATETIME NOT NULL,
                date_to DATETIME NOT NULL,
                id_kpy_coupon INT UNSIGNED NULL DEFAULT NULL,
                origen VARCHAR(8) NOT NULL DEFAULT 'lista',
                date_add DATETIME NOT NULL,
                date_upd DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY articulo_fechas (id_product, id_product_attribute, date_from, date_to),
                KEY tipo (id_supplier_promo_type),
                KEY fechas (date_from, date_to),
                KEY promo (id_kpy_coupon)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_supplier_promo_coupon (
                id_kpy_coupon INT UNSIGNED NOT NULL,
                id_supplier_promo_type INT UNSIGNED NOT NULL,
                value DECIMAL(20,6) NOT NULL DEFAULT 0,
                date_add DATETIME NOT NULL,
                date_upd DATETIME NOT NULL,
                PRIMARY KEY (id_kpy_coupon)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        );
        if (!$ok) {
            return false;
        }
        // Las claves foráneas que pidió Julián (al tipo). Si el motor no las admite, las tablas sirven igual.
        foreach (['kpy_supplier_promo_products', 'kpy_supplier_promo_coupon'] as $tabla) {
            try {
                $hay = (int) $db->getValue(
                    "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '{$p}{$tabla}' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
                );
                if ($hay === 0) {
                    $db->execute(
                        "ALTER TABLE {$p}{$tabla} ADD CONSTRAINT fk_{$p}{$tabla}_tipo
                         FOREIGN KEY (id_supplier_promo_type) REFERENCES {$p}kpy_supplier_promo_type (id)"
                    );
                }
            } catch (Throwable $e) {
                PrestaShopLogger::addLog('kpysupply, la clave foránea de ' . $tabla . ' no se ha podido crear (la tabla sirve igual): ' . $e->getMessage(), 2, null, 'Kpysupply', 0, true);
            }
        }

        return true;
    }

    /** La entrada del menú: Catálogo → «Kompy · Acuerdos con proveedores». */
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
        $tab->icon = 'receipt';
        foreach (Language::getLanguages(false) as $idioma) {
            $tab->name[(int) $idioma['id_lang']] = 'Kompy · Acuerdos con proveedores';
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

    /** Pantalla del módulo: la pestaña de Catálogo. */
    public function getContent(): string
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::PESTANA));

        return '';
    }

    /**
     * '' si kpycoupons está instalado y al día (el código y la base de datos en la versión mínima o
     * más); si no, qué falta. PrestaShop solo mira que la dependencia esté, no su versión. Sin
     * cargar el módulo: la versión de los ficheros la saca el core leyendo su fichero
     * (`Module::getModuleVersion`) y la de la base de datos, la tabla `module`.
     */
    public static function faltaKpycoupons(): string
    {
        if (!Module::isInstalled('kpycoupons')) {
            return 'Hace falta el módulo Kompy · Promociones (kpycoupons) ' . self::KPYCOUPONS_MINIMA . ' instalado antes que este.';
        }
        $codigo = self::versionEnFicheros('kpycoupons');
        // La versión de la base de datos (la del último `upgrade`): PrestaShop no la carga en el
        // objeto al instanciarlo (`database_version` solo se rellena al listar los módulos).
        $base = (string) (Db::getInstance()->getValue('SELECT version FROM ' . _DB_PREFIX_ . "module WHERE name = 'kpycoupons'") ?: '0');
        if (version_compare($codigo, self::KPYCOUPONS_MINIMA, '<') || version_compare($base, self::KPYCOUPONS_MINIMA, '<')) {
            return sprintf('Hace falta Kompy · Promociones (kpycoupons) %s o posterior; está la %s (ficheros) y la %s (base de datos). Primero se sube y se actualiza kpycoupons (prestashop:module upgrade kpycoupons) y después se instala este.', self::KPYCOUPONS_MINIMA, $codigo, $base);
        }

        return '';
    }

    /**
     * La versión que declara el fichero principal de un módulo, **sin cargarlo**: por la API del
     * core (`Module::getModuleVersion`, que analiza el fichero sin ejecutarlo) y, si no está o no la
     * saca, leyendo el `$this->version = '…'` del fichero. '0' si no se sabe.
     */
    public static function versionEnFicheros(string $modulo): string
    {
        try {
            if (method_exists(Module::class, 'getModuleVersion')) {
                $version = (string) Module::getModuleVersion($modulo);
                if ($version !== '') {
                    return $version;
                }
            }
        } catch (Throwable $e) {
            // Se lee del fichero.
        }
        $fichero = _PS_MODULE_DIR_ . $modulo . '/' . $modulo . '.php';
        if (is_file($fichero) && preg_match('/\$this->version\s*=\s*[\'"]([0-9][^\'"]*)[\'"]/', (string) file_get_contents($fichero), $m)) {
            return $m[1];
        }

        return '0';
    }

    /** La tienda del contexto (o la de por defecto), para preguntar a kpycoupons. */
    private function idTienda(): int
    {
        return (int) ($this->context->shop->id ?? 0) ?: (int) Configuration::get('PS_SHOP_DEFAULT');
    }

    // ---------- hooks: el paso del formulario de cada promoción (kpycoupons) ----------

    /**
     * El paso «Acuerdo con el proveedor» dentro del formulario de Kompy · Promociones: sin acuerdo
     * general, redención (su %) o descuento en factura (su %), para todos los artículos de la
     * promoción. Lo que vaya por artículo (un % distinto por EAN, precios de coste nuevos) se sube
     * con una lista en la pantalla de este módulo, eligiendo la promoción, y manda sobre esto. El
     * número del paso lo dice kpycoupons (`paso`: 6, 7…, según el orden de los módulos enganchados
     * en Diseño → Posiciones; el número solo se gasta si el paso pinta algo); si no llega, el 6.
     * Llegan también `version`, `url_editar` y `url_ver` (los enlaces a la pantalla de Promociones),
     * que este paso no necesita: enlaza a la pantalla de acuerdos, que es la suya.
     */
    public function hookDisplayKpyCouponsFormulario(array $params): string
    {
        $id = (int) ($params['id'] ?? 0);
        $duplicar = (int) ($params['duplicar'] ?? 0);
        try {
            $d = KsAcuerdos::dePromo($id);
            // Al duplicar una promoción, el paso sale como el de la original (las listas no se copian).
            $config = $d['config'] ?? ($id === 0 && $duplicar > 0 ? KsAcuerdos::configDe($duplicar) : null);
            $tipo = (string) ($config['tipo'] ?? '');
            $valor = $config !== null ? KsTipos::numero((float) $config['valor']) : '';
            // Si el formulario vuelve a pintarse tras un envío (kpycoupons no pudo guardar), lo que se escribió.
            if (Tools::getIsset('kps_paso')) {
                $tipo = (string) Tools::getValue('kps_tipo');
                $tipo = in_array($tipo, [KsTipos::REDENCION, KsTipos::FACTURA], true) ? $tipo : '';
                $valor = $tipo !== '' ? trim((string) Tools::getValue('kps_valor_' . $tipo)) : '';
            }
            $this->context->smarty->assign('kps', [
                'numero' => (int) ($params['paso'] ?? 0) > 0 ? (int) $params['paso'] : self::PASO_POR_DEFECTO,
                'id' => $id,
                'tipos' => KsTipos::TEXTOS,
                'tipo' => $tipo,
                'valor' => $valor,
                'actual_texto' => $id > 0 ? KsAcuerdos::textoDePromo($d) : '',
                'n_lista' => (int) $d['n_lista'],
                'aviso' => $id > 0 ? $this->sacarAviso($id) : '',
                'url_pantalla' => $this->context->link->getAdminLink(self::PESTANA),
                'url_ver' => $id > 0 ? $this->context->link->getAdminLink(self::PESTANA) . '&promo=' . $id . '#acuerdos' : '',
            ]);

            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . $this->name . '/views/templates/admin/paso.tpl');
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpysupply, el paso del formulario: ' . $e->getMessage(), 3, null, 'Kpysupply', $id, true);

            return '';
        }
    }

    /**
     * Al guardar una promoción en Kompy · Promociones (`version`, `id`, `valores`, `promocion`: la
     * fila pública): se guarda **lo marcado en el paso** (el tipo y su %) y, en el momento, se cuadra
     * el acuerdo con la promoción (sus artículos, sus fechas) pidiéndole a kpycoupons su alcance por
     * el hook de consulta `actionKpyCouponsAlcance` (`KsAcuerdos::ponerAlDia()`): así vale también
     * para una promoción apagada o terminada, que no entra en el reparto. Solo si el formulario
     * traía el paso (campo `kps_paso`); si no, no se toca nada. Si kpycoupons no contesta, lo marcado
     * queda guardado, el acuerdo no se toca y se apunta en el registro.
     *
     * El reparto que kpycoupons lanza justo después vuelve a pasar por aquí
     * (`actionKpyCouponsReparto` con `guardada` = esta promoción): esa segunda pasada no cambia nada
     * (el espejo es idempotente) y hace de red si kpycoupons no contestó al guardar.
     */
    public function hookActionKpyCouponsGuardar(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0 || !Tools::getIsset('kps_paso')) {
            return;
        }
        try {
            $tipo = (string) Tools::getValue('kps_tipo');
            if (!in_array($tipo, [KsTipos::REDENCION, KsTipos::FACTURA], true)) {
                $tipo = '';
            }
            $valor = null;
            if ($tipo !== '') {
                $escrito = trim((string) Tools::getValue('kps_valor_' . $tipo));
                $valor = KsLectura::leerValor($escrito, 'pct');
                if ($valor === null) {
                    // Sin un % que se entienda no se cambia nada; el paso lo dirá la próxima vez que se abra.
                    $this->ponerAviso($id, sprintf('El acuerdo no se ha cambiado: el %% escrito para «%s» («%s») no se entiende. Tiene que ser un número entre 0 y 90.', KsTipos::titulo($tipo), $escrito));
                    PrestaShopLogger::addLog('kpysupply, promoción ' . $id . ': el % del acuerdo (' . $tipo . ') no se entiende; no se ha tocado el acuerdo', 2, null, 'Kpysupply', $id, true);

                    return;
                }
            }
            KsAcuerdos::guardarConfig($id, $tipo, $valor);
            $r = KsAcuerdos::ponerAlDia($id, $this->idTienda());
            if (!$r['ok']) {
                PrestaShopLogger::addLog('kpysupply, promoción ' . $id . ', al guardar: ' . $r['texto'] . ' Lo marcado queda guardado; el acuerdo se cuadrará en el siguiente reparto si la promoción está en marcha.', 2, null, 'Kpysupply', $id, true);
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpysupply, al guardar el acuerdo de la promoción ' . $id . ': ' . $e->getMessage(), 3, null, 'Kpysupply', $id, true);
        }
    }

    /**
     * `kpycoupons` ha rehecho el reparto. Trae (`version`), por promoción en marcha (activa, sin
     * terminar y con cupo): `items` (lo que le toca de verdad), `alcance` (`entran` y `especial`: los
     * artículos de su paso 2 tal como los calcula kpycoupons, antes de quitar agotados y choques; la
     * clave es el id de la combinación, o −id_product si el producto no tiene combinaciones),
     * `promociones` (su fila pública, `KpcContrato::promocion`; de ella aquí se usan `fecha_desde` y
     * `fecha_hasta`) y `guardada` (la que se acaba de guardar en la pantalla; 0 en los repartos por
     * fecha o por consola).
     *
     * Para cada promoción con acuerdo que esté en marcha, el acuerdo se pone al día con ella: sus
     * fechas, el tipo y el % del paso, y una fila del paso para los artículos que no la tengan. Solo
     * en la recién guardada (`guardada`) se hace además el **espejo**: fuera las filas del paso de
     * artículos que ya no están en la promoción (salvo las que ya vendieron) y, sin nada marcado en
     * el paso, todas las del paso. En los demás repartos nunca se quita nada. La recién guardada ya
     * se cuadró al guardar (`hookActionKpyCouponsGuardar`): aquí se repite a propósito, porque es
     * idempotente y hace de red si entonces kpycoupons no contestó.
     *
     * Una promoción con acuerdo que no viene en `alcance` (kpycoupons no pudo calcular sus
     * artículos: sin fechas, un error) no se toca, y se apunta en el registro.
     */
    public function hookActionKpyCouponsReparto(array $params): void
    {
        try {
            $items = (array) ($params['items'] ?? []);
            $alcances = (array) ($params['alcance'] ?? []);
            $promociones = (array) ($params['promociones'] ?? []);
            $guardada = (int) ($params['guardada'] ?? 0);
            $conAcuerdo = KsAcuerdos::promosConAcuerdo();
            if ($guardada > 0 && in_array($guardada, $conAcuerdo, true) && !array_key_exists($guardada, $items)) {
                PrestaShopLogger::addLog('kpysupply, promoción ' . $guardada . ': se ha guardado pero no está en marcha (apagada, terminada o agotada); el reparto no la trae y su acuerdo se queda como lo dejó el guardar', 1, null, 'Kpysupply', $guardada, true);
            }
            foreach ($conAcuerdo as $idPromo) {
                if (!array_key_exists($idPromo, $items)) {
                    continue;
                }
                $alcance = $alcances[$idPromo] ?? null;
                $promocion = $promociones[$idPromo] ?? null;
                if (!is_array($alcance) || !is_array($promocion)) {
                    PrestaShopLogger::addLog('kpysupply, promoción ' . $idPromo . ': el reparto no trae su alcance (kpycoupons no ha podido calcular sus artículos); el acuerdo se deja como está', 2, null, 'Kpysupply', $idPromo, true);
                    continue;
                }
                // Un fallo en una promoción no para el repaso de las demás.
                try {
                    KsAcuerdos::sincronizarPromo($idPromo, $guardada === $idPromo, $alcance, $promocion);
                } catch (Throwable $e) {
                    PrestaShopLogger::addLog('kpysupply, al repasar el acuerdo de la promoción ' . $idPromo . ' tras el reparto: ' . $e->getMessage(), 3, null, 'Kpysupply', $idPromo, true);
                }
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpysupply, al repasar los acuerdos tras el reparto: ' . $e->getMessage(), 3, null, 'Kpysupply', 0, true);
        }
    }

    private function ponerAviso(int $id, string $texto): void
    {
        $avisos = json_decode((string) Configuration::getGlobalValue(self::CONF_AVISOS), true);
        $avisos = is_array($avisos) ? $avisos : [];
        $avisos[(string) $id] = $texto;
        Configuration::updateGlobalValue(self::CONF_AVISOS, json_encode(array_slice($avisos, -50, null, true), JSON_UNESCAPED_UNICODE));
    }

    /** El aviso pendiente de una promoción (y se borra: se enseña una vez). */
    private function sacarAviso(int $id): string
    {
        $avisos = json_decode((string) Configuration::getGlobalValue(self::CONF_AVISOS), true);
        if (!is_array($avisos) || !isset($avisos[(string) $id])) {
            return '';
        }
        $texto = (string) $avisos[(string) $id];
        unset($avisos[(string) $id]);
        Configuration::updateGlobalValue(self::CONF_AVISOS, json_encode($avisos, JSON_UNESCAPED_UNICODE));

        return $texto;
    }
}
