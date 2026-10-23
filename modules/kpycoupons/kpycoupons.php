<?php
/**
 * Kompy · Cupones
 *
 * El motor de promociones de Kompy: un descuento en % (cupón con código, o «2ª unidad al X %»)
 * sobre unos artículos concretos, que se enseña en la ficha, se puede aplicar de un toque y llega
 * hasta la cesta y el pedido. Y, desde la 2.0.0, el **regalo con la compra** (`KpcRegalo`): una
 * tarjeta más en la ficha, «12 kg + 3 kg gratis» (o «+ Regalo», «+ Snacks»…). Si el cliente la
 * elige, el saco grande va a su precio de siempre, sin el Special Price, y el pequeño de la misma
 * ficha (o el artículo que se elija) entra a 0 €, como una línea normal con su EAN y su stock, y
 * queda apuntado en el pedido (2.1.0: antes entraba solo y encima del Special Price).
 *
 * Cómo funciona, en corto:
 *   1. La promoción se crea en Catálogo → Kompy · Promociones (o en bloque con SQL desde el
 *      registro de `catalog-ops`). Al guardarla se calcula **una vez** a qué artículos llega
 *      (`KpcReparto`) y se deja escrito, con su regla de carrito y sus etiquetas.
 *   2. La ficha la pinta **este módulo** en los huecos del tema (3.0.0, `KpcFicha`): el bloque del
 *      cupón, la 2ª unidad, la elección «12 kg» / «12 kg + 3 kg gratis», «Quedan N», el chip de cada
 *      formato y el precio presentado, por los hooks `displayProductPriceBlock`,
 *      `displayProductAttributeFlags` y `actionPresentProduct` que el tema ya llama. Lee el reparto
 *      ya guardado. Hasta la 2.4 lo pintaba `kpyproductbuy`, que no se instala en producción.
 *   3. Al pulsar el cupón, `ficha.js` llama a `/module/kpycoupons/aplicar`, que lo guarda para ese
 *      cliente y lo mete en la cesta si ya hay algo que descontar; PrestaShop repinta la ficha.
 *   4. El descuento lo hace **PrestaShop**, con una regla de carrito por cupón restringida a esos
 *      artículos (`reduction_product = -2`). Así sale bien en la cesta, en el pedido y en la
 *      factura sin repetir cuentas.
 *
 * Reglas de Kompy: un producto con Special Price no lleva cupón encima (salvo que el cupón diga lo
 * contrario); hay cupones que llegan ya aplicados según de dónde venga la visita (Google Shopping,
 * email, redes) o con un enlace (`?kpc=CODIGO`).
 *
 * Crea sus tablas (`kpy_coupon`, `kpy_coupon_scope`, `kpy_coupon_item`, `kpy_coupon_precio`,
 * `kpy_coupon_regalo`, `kpy_coupon_regalo_cesta`, `kpy_coupon_regalo_eleccion`,
 * `kpy_coupon_regalo_pedido`…) y no toca las de nadie más, salvo los Special Price que pone él
 * (`specific_price` por su API y `kpy_special_price`, apuntados en `kpy_coupon_precio`) y el
 * «(regalo)» en el nombre de la línea del pedido (`order_detail.product_name`). Las etiquetas se
 * calculan por hook (`KpcFlags`), sin filas en las tablas de `kpyproductflags` (3.0.0).
 *
 * **No depende de ningún otro módulo ni llama a ninguno** (regla de Julián, 23/10/2026): lo que
 * otros necesitan saber les llega por hooks propios con datos curados (`KpcContrato`; README,
 * «Contratos públicos»), y lo que este módulo necesita del tema lo pide por los hooks del core.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

// Cada clase del módulo se carga cuando hace falta, no todas en cada visita: la ficha y los
// listados solo leen lo ya guardado (unas pocas), y el cálculo y la pantalla son del back office.
spl_autoload_register(static function (string $clase): void {
    if (strncmp($clase, 'Kpc', 3) === 0 && is_file($fichero = __DIR__ . '/classes/' . $clase . '.php')) {
        require $fichero;
    }
});

class Kpycoupons extends Module
{
    /** Cupón elegido por el cliente, en su cookie. «!CODIGO» = lo ha quitado él. */
    public const COOKIE = 'kpy_cupon';

    /** De dónde llegó la visita (shopping, email, social), para los cupones que entran solos. */
    public const COOKIE_CANAL = 'kpy_canal';

    /** El parámetro de los enlaces que ponen un cupón: `https://kompymascotas.com/?kpc=EUKA10`. */
    public const PARAMETRO = 'kpc';

    /** El controlador de la entrada del menú (Catálogo → Kompy · Promociones). */
    public const PESTANA = 'AdminKpyPromociones';

    /** Interruptor del feed de Doofinder (1 = contesta `actionDoofinderExtendFeed`; 0 = no). */
    public const CONF_DOOFINDER = 'KPC_DOOFINDER';

    /** Los hooks del core y del tema que contesta este módulo (los propios de consulta, también). */
    public const HOOKS = [
        'actionCartSave', 'displayShoppingCartAfterDetailedTotals', 'actionFrontControllerSetMedia', 'actionFrontControllerInitAfter',
        'actionProductFlagsModifier', 'actionDoofinderExtendFeed', 'actionValidateOrder', 'actionPresentCartProduct',
        'actionPresentProductListing', 'actionPaymentModuleProductVarTplAfter', 'actionDuplicateCartData',
        'displayCartExtraProductActions', 'actionPresentCart', 'actionCartUpdateQuantityBefore', 'actionValidateOrderBefore',
        // 3.0.0: la ficha en los huecos del tema, la ventana «Añadido» y los hooks de consulta propios.
        'displayProductPriceBlock', 'displayProductAttributeFlags', 'actionPresentProduct', 'actionFrontControllerSetVariables',
        'displayCartModalFooter', 'actionKpyCouponsProductosConPromo', 'actionKpyCouponsAlcance', 'actionKpyCouponsPromociones',
    ];

    /**
     * Los hooks propios: su fila en `hook`, con título y descripción, para que salgan en Diseño →
     * Posiciones (quien dispara un hook lo nombra, lo crea y documenta sus parámetros). Los de aviso
     * los dispara este módulo; los de consulta los dispara quien pregunta y los contesta este módulo,
     * salvo `actionKpyCouponsCampana`, que dispara este y contesta la landing de promociones (por eso
     * ese no está en `HOOKS`: en modo desarrollo `registerHook` lanza si falta el método).
     */
    public const HOOKS_PROPIOS = [
        'displayKpyCouponsFormulario' => ['Kompy · pasos ajenos en el formulario de promociones', 'kpycoupons lo dispara al pintar el formulario de una promoción, módulo a módulo. Params: version, id (0 nueva), valores, duplicar, paso (6, 7…), url_editar, url_ver. Devuelve HTML.'],
        'actionKpyCouponsGuardar' => ['Kompy · promoción guardada', 'kpycoupons lo dispara tras guardar una promoción y sus ámbitos, antes del reparto. Params: version, id, valores, promocion (fila pública, KpcContrato).'],
        'actionKpyCouponsReparto' => ['Kompy · reparto de promociones rehecho', 'kpycoupons lo dispara al final de cada reparto. Params: version, id_shop, items, avisos, alcance, promociones (filas públicas), guardada. Puede correr tras contestar una visita: sin salida.'],
        'actionKpyCouponsResumen' => ['Kompy · resumen de promociones de la cesta', 'kpycoupons lo dispara al pintar los totales de la cesta. Params: cart, resumen (por referencia: vales, lineas, animos).'],
        'actionKpyCouponsCampana' => ['Kompy · capa de campaña (consulta)', 'kpycoupons lo dispara al pintar la ficha y la cesta; lo contesta la landing (kpypromolanding). Params: id_product, id_product_attribute, tipos. Respuesta: null o {nombre, clave, piel, tipos: {tipo: {label, background, color}}}.'],
        'actionKpyCouponsProductosConPromo' => ['Kompy · productos de la cesta con promoción (consulta)', 'Lo dispara quien pregunta (kpycrosssell); lo contesta kpycoupons. Params: cart, id_shop, productos (int[] por referencia): se añaden los id_product con cupón, 2ª unidad o regalo.'],
        'actionKpyCouponsAlcance' => ['Kompy · alcance de una promoción (consulta)', 'Lo dispara quien pregunta (kpysupply), con array_return; lo contesta kpycoupons. Params: id, id_shop. Respuesta: {version, entran, especial, promocion} o null.'],
        'actionKpyCouponsPromociones' => ['Kompy · las promociones y su estado (consulta)', 'Lo dispara quien pregunta (kpypromolanding), con array_return; lo contesta kpycoupons. Params: id_shop. Respuesta: por id, la fila pública con estado, cajon, regalo_clase, items, parejas.'],
    ];

    public function __construct()
    {
        $this->name = 'kpycoupons';
        $this->tab = 'pricing_promotion';
        $this->version = '3.0.0';
        $this->author = 'Kompy';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Kompy · Cupones', [], 'Modules.Kpycoupons.Admin');
        $this->description = $this->trans('Promociones de Kompy: cupones, 2ª unidad, Special Price y regalo con la compra, por marca, categoría, característica o lista de artículos, con su regla de carrito.', [], 'Modules.Kpycoupons.Admin');
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
        $this->asegurarHooks();
        $this->colocarHooks();
        if (Configuration::getGlobalValue(self::CONF_DOOFINDER) === false) {
            Configuration::updateGlobalValue(self::CONF_DOOFINDER, 1);
        }

        return $this->pestana();
    }

    /**
     * Hook de consulta (2.5.0): otro módulo pregunta qué productos de una cesta ya llevan una
     * promoción de este módulo (cupón puesto, 2ª unidad, línea con regalo o lo regalado), para no
     * poner encima otro descuento. Lo lanza quien pregunta (hoy `kpycrosssell`):
     *
     *   $ids = [];
     *   Hook::exec('actionKpyCouponsProductosConPromo', ['cart' => $cart, 'id_shop' => $idShop, 'productos' => &$ids]);
     *
     * y este módulo deja en `productos` los `id_product` (se suman a los que hubiera). Sin este
     * módulo instalado, el array se queda como estaba.
     *
     * @param array{cart?: Cart, id_shop?: int, productos?: int[]} $params `productos` por referencia
     */
    public function hookActionKpyCouponsProductosConPromo(array $params): void
    {
        $cart = $params['cart'] ?? null;
        if (!$cart instanceof Cart || !$cart->id || !isset($params['productos']) || !is_array($params['productos'])) {
            return;
        }
        try {
            $idTienda = (int) ($params['id_shop'] ?? $this->context->shop->id);
            $params['productos'] = array_values(array_unique(array_merge($params['productos'], KpcCarrito::productosConPromo($cart, $idTienda))));
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, productos con promoción de la cesta (actionKpyCouponsProductosConPromo): ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);
        }
    }

    /**
     * La entrada del menú: Catálogo → «Kompy · Promociones». Es la pantalla de las promociones, y
     * se usa a diario.
     */
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
        $tab->icon = 'local_offer';
        foreach (Language::getLanguages(false) as $idioma) {
            $tab->name[(int) $idioma['id_lang']] = 'Kompy · Promociones';
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

    public function uninstall(): bool
    {
        // Las reglas de carrito se borran con su cupón; las tablas se quedan (los datos son de Kompy).
        // Las filas que las versiones viejas escribieron en las tablas de `kpyproductflags` no se
        // tocan desde aquí (son tablas ajenas): las borra el SQL del paquete o `bin/limpiar-flags.php`.
        $this->quitarPestana();
        Configuration::deleteByName(self::CONF_DOOFINDER);

        return parent::uninstall();
    }

    public function tablas(): bool
    {
        $p = _DB_PREFIX_;
        $motor = _MYSQL_ENGINE_;
        $db = Db::getInstance();

        return (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon (
                id_kpy_coupon INT UNSIGNED NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(32) NOT NULL,
                nombre VARCHAR(128) NOT NULL DEFAULT '',
                tipo ENUM('cupon','segunda_unidad','special_price','regalo') NOT NULL DEFAULT 'cupon',
                modo_precio VARCHAR(16) NOT NULL DEFAULT 'pct',
                porcentaje DECIMAL(6,3) NOT NULL DEFAULT 0,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                fecha_desde DATETIME NULL,
                fecha_hasta DATETIME NULL,
                auto_web TINYINT(1) NOT NULL DEFAULT 0,
                auto_canal VARCHAR(64) NOT NULL DEFAULT '',
                en_ficha TINYINT(1) NOT NULL DEFAULT 1,
                excluir_especial TINYINT(1) NOT NULL DEFAULT 1,
                incluir_packs TINYINT(1) NOT NULL DEFAULT 1,
                tamanos TEXT NULL,
                packs VARCHAR(64) NOT NULL DEFAULT '',
                sin_outlet TINYINT(1) NOT NULL DEFAULT 1,
                bloquear_priceshape TINYINT(1) NOT NULL DEFAULT 0,
                en_merchant TINYINT(1) NOT NULL DEFAULT 1,
                id_cart_rule INT UNSIGNED NOT NULL DEFAULT 0,
                merchant_id VARCHAR(64) NOT NULL DEFAULT '',
                merchant_titulo VARCHAR(160) NOT NULL DEFAULT '',
                lista MEDIUMTEXT NULL,
                lista_tipo VARCHAR(16) NOT NULL DEFAULT 'auto',
                excluir MEDIUMTEXT NULL,
                formatos INT UNSIGNED NOT NULL DEFAULT 0,
                aviso VARCHAR(255) NOT NULL DEFAULT '',
                id_shop INT UNSIGNED NOT NULL DEFAULT 1,
                regalo_modo VARCHAR(16) NOT NULL DEFAULT 'pequeno',
                regalo_por VARCHAR(8) NOT NULL DEFAULT 'unidad',
                regalo_tope INT UNSIGNED NOT NULL DEFAULT 0,
                regalo_packs TINYINT(1) NOT NULL DEFAULT 0,
                regalo_mismo VARCHAR(24) NOT NULL DEFAULT '',
                regalo_reserva VARCHAR(24) NOT NULL DEFAULT '',
                regalo_parejas MEDIUMTEXT NULL,
                regalo_corto VARCHAR(24) NOT NULL DEFAULT '',
                cupo INT UNSIGNED NOT NULL DEFAULT 0,
                cupo_agotado DATETIME NULL,
                cupo_ficha TINYINT(1) NOT NULL DEFAULT 1,
                etiqueta VARCHAR(16) NOT NULL DEFAULT '',
                cupo_lista TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id_kpy_coupon),
                KEY codigo (codigo),
                KEY activo (activo, fecha_desde, fecha_hasta)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_scope (
                id_kpy_coupon_scope INT UNSIGNED NOT NULL AUTO_INCREMENT,
                id_kpy_coupon INT UNSIGNED NOT NULL,
                tipo ENUM('marca','categoria','caracteristica','producto','combinacion','excluir_producto','excluir_combinacion') NOT NULL,
                id_ref INT UNSIGNED NOT NULL,
                valor DECIMAL(20,6) NULL,
                unidades INT UNSIGNED NULL,
                agotado DATETIME NULL,
                PRIMARY KEY (id_kpy_coupon_scope),
                KEY cupon (id_kpy_coupon),
                KEY busqueda (tipo, id_ref)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // El reparto ya calculado: qué artículo le toca a cada promoción. `clave` es la
            // combinación, o el producto en negativo si no tiene combinaciones.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_item (
                id_kpy_coupon INT UNSIGNED NOT NULL,
                clave INT NOT NULL,
                id_product INT UNSIGNED NOT NULL,
                PRIMARY KEY (id_kpy_coupon, clave),
                KEY clave (clave),
                KEY producto (id_product)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Los Special Price que ha puesto cada promoción, para poder quitarlos sin tocar los de nadie.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_precio (
                id_kpy_coupon INT UNSIGNED NOT NULL,
                id_product INT UNSIGNED NOT NULL,
                id_product_attribute INT UNSIGNED NOT NULL,
                id_specific_price INT UNSIGNED NOT NULL,
                date_from DATETIME NOT NULL,
                expire DATETIME NOT NULL,
                porcentaje DECIMAL(8,3) NOT NULL DEFAULT 0,
                precio DECIMAL(20,6) NULL,
                old_discount DECIMAL(20,6) NOT NULL DEFAULT 0,
                reduccion DECIMAL(20,6) NOT NULL DEFAULT 0,
                firma VARCHAR(64) NOT NULL DEFAULT '',
                PRIMARY KEY (id_kpy_coupon, id_product, id_product_attribute),
                KEY precio (id_specific_price),
                KEY articulo (id_product, id_product_attribute)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Regalo con la compra: qué regala cada artículo disparador (r) y su reserva (s). `n`:
            // regalos por unidad (el nivel del pack). `origen`: pequeno, mismo, mano o reserva.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_regalo (
                id_kpy_coupon INT UNSIGNED NOT NULL,
                clave INT NOT NULL,
                id_product INT UNSIGNED NOT NULL,
                n INT UNSIGNED NOT NULL DEFAULT 1,
                r_id_product INT UNSIGNED NOT NULL,
                r_ipa INT UNSIGNED NOT NULL DEFAULT 0,
                s_id_product INT UNSIGNED NOT NULL DEFAULT 0,
                s_ipa INT UNSIGNED NOT NULL DEFAULT 0,
                origen VARCHAR(16) NOT NULL DEFAULT 'pequeno',
                PRIMARY KEY (id_kpy_coupon, clave),
                KEY producto (id_product),
                KEY regalo (r_id_product, r_ipa)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Qué parte de cada línea de una cesta es regalo (y de qué promoción y disparador, y
            // cuántas unidades del disparador lo llevan): se rehace en cada guardado de la cesta y
            // se borra al pedir.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_regalo_cesta (
                id_cart INT UNSIGNED NOT NULL,
                id_kpy_coupon INT UNSIGNED NOT NULL,
                disparador INT NOT NULL,
                r_id_product INT UNSIGNED NOT NULL,
                r_ipa INT UNSIGNED NOT NULL DEFAULT 0,
                cantidad INT UNSIGNED NOT NULL DEFAULT 0,
                unidades INT UNSIGNED NOT NULL DEFAULT 0,
                date_upd DATETIME NOT NULL,
                PRIMARY KEY (id_cart, id_kpy_coupon, disparador),
                KEY promo (id_kpy_coupon)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Cuántas unidades de cada línea de una cesta ha pedido el cliente con regalo (la
            // tarjeta «12 kg + 3 kg gratis» de la ficha): la única memoria del regalo. `clave` es el
            // formato, o el producto en negativo si no tiene formatos. Se borra al pedir y al limpiar
            // cestas muertas.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_regalo_eleccion (
                id_cart INT UNSIGNED NOT NULL,
                clave INT NOT NULL,
                id_specific_price INT UNSIGNED NOT NULL DEFAULT 0,
                unidades INT UNSIGNED NOT NULL DEFAULT 0,
                date_upd DATETIME NOT NULL,
                PRIMARY KEY (id_cart, clave)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Lo regalado en cada pedido, con su precio y su coste: el sell-out para la marca, el
            // tope de la campaña y la línea marcada «(regalo)». No se borra nunca.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_regalo_pedido (
                id_order INT UNSIGNED NOT NULL,
                id_kpy_coupon INT UNSIGNED NOT NULL,
                disparador INT NOT NULL,
                r_id_product INT UNSIGNED NOT NULL,
                r_ipa INT UNSIGNED NOT NULL DEFAULT 0,
                cantidad INT UNSIGNED NOT NULL DEFAULT 0,
                precio_unidad DECIMAL(20,6) NOT NULL DEFAULT 0,
                coste_unidad DECIMAL(20,6) NOT NULL DEFAULT 0,
                date_add DATETIME NOT NULL,
                PRIMARY KEY (id_order, id_kpy_coupon, disparador, r_id_product, r_ipa),
                KEY promo (id_kpy_coupon),
                KEY regalo (r_id_product, r_ipa)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // El cupo (2.3.0): lo reservado en cada cesta viva, por promoción y línea. Se reescribe en
            // cada repaso de la cesta y se borra al pedir (ver `KpcCupo`).
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_cupo_cesta (
                id_cart INT UNSIGNED NOT NULL,
                id_kpy_coupon INT UNSIGNED NOT NULL,
                clave INT NOT NULL,
                unidades INT UNSIGNED NOT NULL DEFAULT 0,
                date_upd DATETIME NOT NULL,
                PRIMARY KEY (id_cart, id_kpy_coupon, clave),
                KEY promo (id_kpy_coupon, date_upd)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // Lo vendido con cada promoción en cada pedido (las unidades que cuenta el cupo; los
            // regalos van en `kpy_coupon_regalo_pedido`). No se borra nunca.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_cupo_pedido (
                id_order INT UNSIGNED NOT NULL,
                id_kpy_coupon INT UNSIGNED NOT NULL,
                clave INT NOT NULL,
                unidades INT UNSIGNED NOT NULL DEFAULT 0,
                date_add DATETIME NOT NULL,
                PRIMARY KEY (id_order, id_kpy_coupon, clave),
                KEY promo (id_kpy_coupon)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        ) && (bool) $db->execute(
            // El precio habitual de una cesta por línea (`KpcPrecioCesta`): qué precio específico de
            // esa cesta ha puesto el módulo, para quitarlo sin tocar ningún otro.
            "CREATE TABLE IF NOT EXISTS {$p}kpy_coupon_precio_cesta (
                id_cart INT UNSIGNED NOT NULL,
                clave INT NOT NULL,
                id_specific_price INT UNSIGNED NOT NULL,
                date_upd DATETIME NOT NULL,
                PRIMARY KEY (id_cart, clave),
                KEY precio (id_specific_price)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        );
    }

    // ------------------------------------------------------------------------------------------
    // La ficha, en los huecos del tema (3.0.0; ver `KpcFicha` y docs/plan-desacoplar-kpycoupons.md)

    /**
     * El bloque de promociones bajo el precio: solo en la ficha (`type='weight'`,
     * `hook_origin='product_sheet'`, `ProductController`). El listado llama al mismo `type` sin
     * `hook_origin`, y la cesta y la ventana «Añadido» llaman a `unit_price` con una línea de cesta
     * (no con un `ProductLazyArray`): '' en todo eso, antes de tocar `product`.
     */
    public function hookDisplayProductPriceBlock(array $params): string
    {
        if (($params['type'] ?? '') !== 'weight' || ($params['hook_origin'] ?? '') !== 'product_sheet'
            || !$this->context->controller instanceof ProductControllerCore) {
            return '';
        }
        $producto = $params['product'] ?? null;
        $idProducto = (int) ($producto['id_product'] ?? 0);
        if ($idProducto <= 0) {
            return '';
        }
        try {
            $ficha = KpcFicha::de($this, $idProducto);
            $estado = $ficha->estado((int) ($producto['id_product_attribute'] ?? 0));
            if (!$estado['hay']) {
                return '';
            }
            $this->context->smarty->assign('kpc', $estado + ['textos' => $ficha->textos()]);

            return $this->fetch('module:' . $this->name . '/views/templates/hook/ficha.tpl');
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, el bloque de la ficha #' . $idProducto . ': ' . $e->getMessage(), 3, null, 'Product', $idProducto, true);

            return '';
        }
    }

    /**
     * El chip de la tarjeta de un formato (el tema pasa `product` y `attribute` = `id_attribute`):
     * «Cupón −10 %», «2ª ud. −30 %», «+ 3 kg gratis», «Liquidación»… Una franja por tarjeta
     * (`ficha.css` esconde el tag «Precio especial» del tema cuando hay chip nuestro).
     */
    public function hookDisplayProductAttributeFlags(array $params): string
    {
        if (!$this->context->controller instanceof ProductControllerCore) {
            return '';
        }
        $producto = $params['product'] ?? null;
        $idProducto = (int) ($producto['id_product'] ?? 0);
        $idAtributo = (int) ($params['attribute'] ?? 0);
        if ($idProducto <= 0 || $idAtributo <= 0) {
            return '';
        }
        try {
            $chip = KpcFicha::de($this, $idProducto)->chip($idAtributo);
            if ($chip === null) {
                return '';
            }
            $this->context->smarty->assign('chip', $chip);

            return $this->fetch('module:' . $this->name . '/views/templates/hook/ficha-chip.tpl');
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, el chip del formato en la ficha #' . $idProducto . ': ' . $e->getMessage(), 3, null, 'Product', $idProducto, true);

            return '';
        }
    }

    /**
     * El precio presentado del producto de la ficha, cuando no es el del core: la opción con regalo
     * elegida o el cupo agotado para esta cesta (el precio habitual, sin su Special Price) o el cupón
     * puesto por el cliente (el precio con el cupón). Se pisan las claves presentadas que lee el tema
     * (`KpcFicha::presentar`), nunca `price_amount` (JSON-LD y og:price = el precio del feed) ni la
     * fila cruda de `Product::getProductProperties` (su caché la comparte la cesta). Solo la ficha y
     * solo su producto: la vista rápida, las miniaturas y los de al lado se quedan como están.
     */
    public function hookActionPresentProduct(array $params): void
    {
        $p = $params['presentedProduct'] ?? null;
        if (!$this->context->controller instanceof ProductControllerCore || !is_object($p) || !method_exists($p, 'offsetExists')) {
            return;
        }
        $idProducto = (int) ($p['id_product'] ?? 0);
        if ($idProducto <= 0 || $idProducto !== (int) Tools::getValue('id_product')) {
            return;
        }
        try {
            KpcFicha::de($this, $idProducto)->presentar($p);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, el precio presentado de la ficha #' . $idProducto . ': ' . $e->getMessage(), 3, null, 'Product', $idProducto, true);
        }
    }

    /**
     * `$modules.kpycoupons` en la ficha, por si el tema quiere pintar algo él (hoy ninguna plantilla
     * lo lee: es una oferta a Julián): por `id_attribute`, el chip de su formato (`tipo`, `texto`,
     * `fondo`, `color`, `quedan`); y `estado` del formato elegido. Fuera de la ficha, nada (y ninguna
     * consulta).
     */
    public function hookActionFrontControllerSetVariables(): array
    {
        $controlador = $this->context->controller;
        if (!$controlador instanceof ProductControllerCore || !method_exists($controlador, 'getProduct')) {
            return [];
        }
        try {
            $producto = $controlador->getProduct();
            if (!$producto instanceof Product || !$producto->id) {
                return [];
            }

            return KpcFicha::de($this, (int) $producto->id)->paraTema();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * La ventana «Añadido a tu carrito» (hook `displayCartModalFooter`, el único vivo en el
     * `modal.tpl` del tema): la promoción de la línea que acaba de entrar, como la pinta la cesta
     * debajo del nombre (`KpcLineas::bloques`). `product` es la línea presentada de la cesta.
     */
    public function hookDisplayCartModalFooter(array $params): string
    {
        $producto = $params['product'] ?? null;
        $cart = $this->context->cart;
        if (!$producto || !$cart instanceof Cart || !$cart->id) {
            return '';
        }
        try {
            // Fuera de la cesta y el pago las líneas no van partidas en sus partes (`hookActionPresentCart`
            // solo parte en esas páginas): aquí se parten para decir cada parte («12 kg con regalo», «Regalo»).
            $idTienda = (int) $this->context->shop->id;
            $clave = KpcAlcance::clave((int) $producto['id_product'], (int) $producto['id_product_attribute']);
            $d = KpcRegalo::partesDe($cart)[$clave] ?? null;
            $lineas = $d !== null && is_object($producto) && !isset($producto['kpy_parte']) ? KpcLineas::trozos($producto, $d) : [$producto];
            $bloques = [];
            foreach ($lineas as $linea) {
                $bloques = array_merge($bloques, KpcLineas::bloques($linea, $cart, $idTienda, $this));
            }
            if ($bloques === []) {
                return '';
            }
            $this->context->smarty->assign(['kpc_bloques' => $bloques, 'kpc_titulo' => $this->t(KpcFicha::TEXTOS['modal_titulo'])]);

            return $this->fetch('module:' . $this->name . '/views/templates/hook/modal-promo.tpl');
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, la ventana «Añadido»: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);

            return '';
        }
    }

    // ------------------------------------------------------------------------------------------
    // Hooks de consulta que contesta este módulo (los dispara quien pregunta; ver `KpcContrato`)

    /**
     * `actionKpyCouponsAlcance` (lo dispara `kpysupply`): a qué artículos llega una promoción.
     *   $r = Hook::exec('actionKpyCouponsAlcance', ['id' => $id, 'id_shop' => $idShop], null, true);
     *   $alcance = $r['kpycoupons'] ?? null;   // null si no existe; {version, entran, especial, promocion}
     */
    public function hookActionKpyCouponsAlcance(array $params): ?array
    {
        try {
            return KpcContrato::alcance((int) ($params['id'] ?? 0), (int) ($params['id_shop'] ?? $this->context->shop->id));
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al contestar el alcance (actionKpyCouponsAlcance): ' . $e->getMessage(), 3, null, 'KpcContrato', (int) ($params['id'] ?? 0), true);

            return null;
        }
    }

    /**
     * `actionKpyCouponsPromociones` (lo dispara `kpypromolanding`): todas las promociones de la
     * tienda con su estado, su cajón, sus artículos del reparto y, en un regalo, sus parejas.
     *   $r = Hook::exec('actionKpyCouponsPromociones', ['id_shop' => $idShop], null, true);
     *   $promociones = $r['kpycoupons'] ?? [];
     */
    public function hookActionKpyCouponsPromociones(array $params): array
    {
        try {
            return KpcContrato::promociones((int) ($params['id_shop'] ?? $this->context->shop->id));
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al contestar las promociones (actionKpyCouponsPromociones): ' . $e->getMessage(), 3, null, 'KpcContrato', 0, true);

            return [];
        }
    }

    /**
     * «Pedir de nuevo»: PrestaShop acaba de copiar en una cesta nueva las líneas de un pedido. Las
     * unidades que fueron regalo no se cobran: se quitan, se vuelve a elegir el regalo en sus
     * disparadores y se repasa la cesta (ver `KpcRegalo::reordenar`).
     */
    public function hookActionDuplicateCartData(array $params): void
    {
        try {
            $nueva = new Cart((int) ($params['newCartId'] ?? 0));
            if (!Validate::isLoadedObject($nueva)) {
                return;
            }
            KpcRegalo::reordenar((int) ($params['oldCardId'] ?? $params['oldCartId'] ?? 0), $nueva);
            $this->sincronizarCesta($nueva);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al pedir de nuevo: ' . $e->getMessage(), 2, null, 'Cart', (int) ($params['newCartId'] ?? 0), true);
        }
    }

    /**
     * Justo antes de crear el pedido: la cesta se repasa una vez más, con las reservas del cupo al
     * día. Si el cliente dejó la cesta abierta más de una hora y otros se llevaron las últimas
     * unidades, el pedido se hace con los precios de ahora, no con los de cuando la vio.
     */
    public function hookActionValidateOrderBefore(array $params): void
    {
        $cart = $params['cart'] ?? null;
        if (!$cart instanceof Cart || !$cart->id || !KpcCupo::hayCupos((int) $this->context->shop->id)) {
            return;
        }
        try {
            $this->sincronizarCesta($cart);
            // PrestaShop guarda en estático la lista de paquetes y las opciones de envío de la cesta (las
            // calculó el módulo de pago antes de llamar aquí) y no las vacía al cambiar las líneas: si el
            // repaso ha tocado algo, que el pedido se monte con la cesta de verdad, no con la foto vieja.
            Cart::resetStaticCache();
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al repasar la cesta antes del pedido: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);
        }
    }

    /**
     * Al validar el pedido: lo regalado queda en `kpy_coupon_regalo_pedido` (el sell-out para la
     * marca y el cupo de la campaña), la línea del pedido dice «(regalo)» para quien lo prepare, y
     * lo reservado en el cupo pasa a vendido (`KpcCupo::alPedido`): si con este pedido se agota una
     * promoción, queda agotada y el reparto se rehace en la siguiente visita.
     */
    public function hookActionValidateOrder(array $params): void
    {
        $order = $params['order'] ?? null;
        if (!$order instanceof Order || !$order->id) {
            return;
        }
        try {
            KpcRegalo::alPedido($order);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al apuntar los regalos del pedido ' . (int) $order->id . ': ' . $e->getMessage(), 3, null, 'Order', (int) $order->id, true);
        }
        try {
            KpcCupo::alPedido($order);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al apuntar el cupo del pedido ' . (int) $order->id . ': ' . $e->getMessage(), 3, null, 'Order', (int) $order->id, true);
        }
    }

    /**
     * La línea de la cesta que es regalo entero se pinta como regalo: el tema ya lo sabe hacer con
     * `kpy_is_gift` (lo dejó `kpygifts`: sin precio, sin cantidad ni papelera, «1 Gift(s)»). Vale
     * también para la confirmación y el historial del pedido, que pintan las líneas de la cesta.
     * PrestaShop 9.1 avisa con `actionPresentCartProduct`; las versiones anteriores, con
     * `actionPresentProductListing` (que se dispara también en los listados: ahí no hay cesta).
     */
    public function hookActionPresentCartProduct(array $params): void
    {
        $this->marcarRegalo($params['presentedProduct'] ?? null);
    }

    public function hookActionPresentProductListing(array $params): void
    {
        $this->marcarRegalo($params['presentedProduct'] ?? null);
    }

    /** @param mixed $linea el `CartProductLazyArray` (o el que sea) de una línea de la cesta */
    /** @var array<int, array> las líneas con regalo de cada cesta, mientras se pinta */
    private array $elegidasCache = [];

    /** El % y el importe de descuento de una línea, los de su precio de verdad contra el PVPR. */
    private function rebajaDeLaLinea($linea): void
    {
        $precio = (float) ($linea['price_amount'] ?? 0);
        $regular = (float) ($linea['regular_price_amount'] ?? 0);
        if ($precio <= 0 || $regular <= $precio + 0.005 || !$linea->offsetExists('discount_percentage')) {
            return;
        }
        $pct = round((1 - $precio / $regular) * 100, 2);
        $locale = $this->context->getCurrentLocale();
        $numero = static fn (float $v): string => $locale ? $locale->formatNumber($v) : number_format($v, 2, ',', '.');
        $linea['discount_percentage'] = $numero(-$pct) . '%';
        $linea['discount_percentage_absolute'] = $numero($pct) . '%';
        if ($linea->offsetExists('discount_amount') && $locale) {
            $moneda = $this->context->currency->iso_code;
            $linea['discount_amount'] = $locale->formatPrice($regular - $precio, $moneda);
            $linea['discount_amount_to_display'] = '-' . $locale->formatPrice($regular - $precio, $moneda);
        }
    }

    private function marcarRegalo($linea): void
    {
        if (!is_object($linea) || !method_exists($linea, 'offsetExists') || !method_exists($linea, 'appendArray')
            || !$linea->offsetExists('cart_quantity')) {
            return;
        }
        try {
            $idCart = $linea->offsetExists('id_cart') ? (int) $linea['id_cart'] : 0;
            if ($idCart <= 0 && $this->context->cart instanceof Cart) {
                $idCart = (int) $this->context->cart->id;
            }
            if ($idCart <= 0) {
                return;
            }
            $clave = KpcAlcance::clave((int) $linea['id_product'], (int) $linea['id_product_attribute']);
            // Una línea al precio habitual de la cesta (por el regalo o por el cupo): el % de la línea,
            // el de ese precio, no el del Special Price que PrestaShop mira en el catálogo.
            $precios = $this->elegidasCache[$idCart] ??= KpcPrecioCesta::de($idCart);
            if (!empty($precios[$clave])) {
                $this->rebajaDeLaLinea($linea);
            }
            $regaladas = KpcRegalo::regaladasDe($idCart);
            $n = (int) ($regaladas[$clave] ?? 0);
            if ($n <= 0) {
                return;
            }
            // `appendArray` no pisa una clave que ya esté (la deja `kpygifts` si está en la línea):
            // entonces se escribe encima.
            $datos = ['kpy_regalo' => $n];
            if ($n >= (int) $linea['cart_quantity']) {
                $datos['kpy_is_gift'] = true;
            }
            foreach ($datos as $clave => $valor) {
                if ($linea->offsetExists($clave)) {
                    $linea[$clave] = $valor;
                } else {
                    $linea->appendArray([$clave => $valor]);
                }
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al marcar la línea de regalo: ' . $e->getMessage(), 2, null, 'Cart', 0, true);
        }
    }

    /**
     * «(regalo)» en la línea del email de confirmación del pedido (PrestaShop monta la lista del
     * email con las líneas de la cesta, antes de avisar del pedido).
     */
    public function hookActionPaymentModuleProductVarTplAfter(array $params): void
    {
        $producto = (array) ($params['product'] ?? []);
        $order = $params['order'] ?? null;
        $idCart = $order instanceof Order ? (int) $order->id_cart : (int) ($this->context->cart->id ?? 0);
        if ($idCart <= 0 || !isset($params['product_var_tpl']['name'])) {
            return;
        }
        try {
            $regaladas = KpcRegalo::regaladasDe($idCart);
            $clave = KpcAlcance::clave((int) ($producto['id_product'] ?? 0), (int) ($producto['id_product_attribute'] ?? 0));
            $n = (int) ($regaladas[$clave] ?? 0);
            if ($n <= 0) {
                return;
            }
            $cantidad = (int) ($producto['cart_quantity'] ?? $producto['quantity'] ?? 0);
            $params['product_var_tpl']['name'] .= ' ' . ($cantidad <= $n ? $this->t('(regalo)') : $this->t('(%n% de regalo)', ['%n%' => $n]));
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al marcar el regalo en el email: ' . $e->getMessage(), 2, null, 'Cart', $idCart, true);
        }
    }

    /**
     * Cada vez que cambia la cesta se revisa el cupón: se mete si hay algo que descontar y se quita
     * si ya no queda nada suyo. Así el cliente nunca ve un cupón que no descuenta.
     */
    public function hookActionCartSave(array $params): void
    {
        $cart = $params['cart'] ?? $this->context->cart;
        if (!$cart instanceof Cart || !$cart->id) {
            return;
        }
        // Lo que acaba de cambiar el cliente con una tarjeta («12 kg + 3 kg gratis» o la de siempre):
        // a esa parte de la línea. No en los guardados que provoca el propio repaso de la cesta.
        if (!KpcCarrito::ocupado()) {
            KpcRegalo::trasElCambio($cart);
        }
        $this->sincronizarCesta($cart);
    }

    /**
     * Antes de que cambie la cantidad de una línea: cuántas unidades pagaba, para saber luego a qué
     * parte va el cambio (ver `KpcRegalo::antesDelCambio`). Solo los cambios del cliente.
     */
    public function hookActionCartUpdateQuantityBefore(array $params): void
    {
        $cart = $params['cart'] ?? null;
        $producto = $params['product'] ?? null;
        if (KpcCarrito::ocupado() || !$cart instanceof Cart || !$producto instanceof Product) {
            return;
        }
        try {
            KpcRegalo::antesDelCambio($cart, (int) $producto->id, (int) ($params['id_product_attribute'] ?? 0), (int) ($params['id_customization'] ?? 0));
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, antes de cambiar la cesta: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);
        }
    }

    /**
     * La cesta y el pedido, con cada parte de una línea en su fila: «12 kg + 3 kg gratis», «12 kg» con
     * su Special Price, el regalo y lo que paga el cliente del mismo formato (ver `KpcLineas`), y cada
     * regalo justo debajo de su saco. En las demás páginas, las líneas como las da PrestaShop.
     */
    public function hookActionPresentCart(array $params): void
    {
        $presentada = $params['presentedCart'] ?? null;
        $cart = $this->context->cart;
        $pagina = (string) ($this->context->controller->php_self ?? '');
        if (!is_object($presentada) || !method_exists($presentada, 'offsetSet') || !$cart instanceof Cart || !$cart->id
            || !in_array($pagina, KpcLineas::PAGINAS, true)) {
            return;
        }
        try {
            $partes = KpcRegalo::partesDe($cart);
            if ($partes === []) {
                return;
            }
            $filas = [];
            foreach ($presentada['products'] as $linea) {
                $clave = KpcAlcance::clave((int) $linea['id_product'], (int) $linea['id_product_attribute']);
                array_push($filas, ...(empty($linea['id_customization']) && isset($partes[$clave]) ? KpcLineas::trozos($linea, $partes[$clave]) : [$linea]));
            }
            $presentada->offsetSet('products', KpcLineas::ordenar($filas), true);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al partir las líneas de la cesta: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);
        }
    }

    /** Pone en la cesta el cupón que toca: el elegido o, si no hay, el que entra solo. */
    public function sincronizarCesta(Cart $cart): void
    {
        KpcCarrito::sincronizar(
            $cart,
            $this->elegido(),
            (int) $this->context->shop->id,
            fn (array $cupon): bool => $this->entraSolo($cupon),
            fn (string $codigo): bool => $this->adoptar($codigo)
        );
    }

    /** Si ese vale es de un cupón con cupo de esta cesta, el cupón queda quitado (como con su papelera). */
    private function olvidarPorVale(Cart $cart, int $idRegla, int $idTienda): void
    {
        if ($idRegla <= 0) {
            return;
        }
        $regla = new CartRule($idRegla);
        if (!Validate::isLoadedObject($regla) || !KpcCupoCesta::esReglaCupon((string) $regla->code, (int) $cart->id)) {
            return;
        }
        $idPromo = KpcCupoCesta::promoDeRegla((string) $regla->code);
        foreach (KpcCupon::enVigor($idTienda) as $c) {
            if ((int) $c['id_kpy_coupon'] === $idPromo) {
                $this->olvidar((string) $c['codigo']);

                return;
            }
        }
    }

    /**
     * El cliente ha escrito un código de Kompy en la cesta: pasa a ser el suyo. Salvo que sea el que
     * acaba de quitar con la papelera (la regla aún puede estar puesta un instante).
     */
    private function adoptar(string $codigo): bool
    {
        if (mb_strtoupper($this->quitado()) === mb_strtoupper($codigo)) {
            return false;
        }
        $this->guardar($codigo);

        return true;
    }

    /**
     * En cada petición de la tienda (también las de la cesta por ajax), tres cosas baratas:
     *   - de dónde llega la visita (Shopping, email, redes): se recuerda, para los cupones que
     *     entran solos aunque la visita no aterrice en la ficha;
     *   - el enlace con cupón (`?kpc=EUKA10`): lo deja puesto;
     *   - el código escrito en «¿Tienes un cupón?»: si es de Kompy, pasa a ser el del cliente
     *     (PrestaShop lo mete en la cesta sin avisar a nadie).
     */
    public function hookActionFrontControllerInitAfter(): void
    {
        $idTienda = (int) $this->context->shop->id;
        $this->recordarCanal();
        $this->enlaceConCupon($idTienda);
        // La papelera del vale «Regalo: …» de la cesta: las líneas que lo regalaban dejan de ir con
        // regalo (vuelven a su precio de hoy) antes de que PrestaShop quite el vale, y se repasa la
        // cesta aquí mismo para que la respuesta (también por ajax) ya salga sin el regalo.
        $controlador = $this->context->controller;
        if (Tools::getIsset('deleteDiscount') && isset($controlador->php_self) && $controlador->php_self === 'cart'
            && (!Configuration::get('PS_TOKEN_ENABLE') || Tools::getValue('token') === Tools::getToken(false))
            && $this->context->cart instanceof Cart && $this->context->cart->id) {
            if (KpcRegalo::quitarPorRegla($this->context->cart, (int) Tools::getValue('deleteDiscount'))) {
                $this->sincronizarCesta($this->context->cart);
            }
            // La papelera de un vale de cupón con cupo (`KPYCP-…`) es la papelera del cupón: se olvida,
            // para que no vuelva a entrar solo, y el repaso de la cesta quita sus otros vales.
            $this->olvidarPorVale($this->context->cart, (int) Tools::getValue('deleteDiscount'), $idTienda);
        }
        if (Tools::getIsset('addDiscount')) {
            $escrito = KpcCupon::porCodigo(trim((string) Tools::getValue('discount_name')), $idTienda);
            if ($escrito !== null && ($escrito['tipo'] ?? 'cupon') === 'cupon') {
                $this->guardar((string) $escrito['codigo']);
            }
        }
    }

    /**
     * Si ha empezado o acabado una promoción desde la última vez, se rehace el reparto (después de
     * contestar, sin hacer esperar a nadie). Y la hoja de la cesta (solo en la cesta y el pago) y
     * la de las etiquetas (en todas).
     */
    public function hookActionFrontControllerSetMedia(): void
    {
        KpcReparto::siToca((int) $this->context->shop->id);
        KpcLimpieza::siToca();

        $controlador = $this->context->controller;
        if (isset($controlador->php_self) && in_array($controlador->php_self, ['cart', 'order'], true)) {
            // Al ver la cesta o el pago se repasa: si la regla de la 2ª unidad de esta cesta se borró
            // por vieja (cesta de hace días), vuelve a estar antes de pintar los totales.
            $cesta = $this->context->cart;
            if ($cesta instanceof Cart && $cesta->id && $cesta->nbProducts() > 0) {
                $this->sincronizarCesta($cesta);
            }
            $controlador->registerStylesheet('module-kpycoupons', 'modules/' . $this->name . '/views/css/cesta.css', ['media' => 'all', 'priority' => 200, 'version' => $this->sello('views/css/cesta.css')]);
            $controlador->registerJavascript('module-kpycoupons-cesta', 'modules/' . $this->name . '/views/js/cesta.js', ['position' => 'bottom', 'priority' => 200, 'version' => $this->sello('views/js/cesta.js')]);
        }
        $controlador->registerStylesheet('module-kpycoupons-flags', 'modules/' . $this->name . '/views/css/flags.css', ['media' => 'all', 'priority' => 210, 'version' => $this->sello('views/css/flags.css')]);
        if ($controlador instanceof ProductControllerCore) {
            // La ficha (3.0.0): la hoja detrás de las de los módulos de Julián (prioridad 800, como
            // las suyas) y el script diferido, abajo. Nada en el tema.
            $controlador->registerStylesheet('module-kpycoupons-ficha', 'modules/' . $this->name . '/views/css/ficha.css', ['media' => 'all', 'priority' => 800, 'version' => $this->sello('views/css/ficha.css')]);
            $controlador->registerJavascript('module-kpycoupons-ficha', 'modules/' . $this->name . '/views/js/ficha.js', ['position' => 'bottom', 'priority' => 200, 'attributes' => 'defer', 'version' => $this->sello('views/js/ficha.js')]);
        }
    }

    /**
     * La fecha del fichero como versión de la hoja o del script (`cesta.css?202610141530`): al
     * desplegar uno nuevo, el navegador no se queda con el que tenía guardado.
     */
    private function sello(string $ruta): string
    {
        $t = @filemtime(_PS_MODULE_DIR_ . $this->name . '/' . $ruta);

        return $t ? date('YmdHi', $t) : $this->version;
    }

    /**
     * Las etiquetas sobre la foto (ficha y listados): `KpcFlags::ajustar` calcula las de cupón, 2ª
     * unidad y regalo de las tablas propias (3.0.0: ninguna fila en las de `kpyproductflags`) y
     * repasa lo que llega: la «Special Price» de `kpyspecialprices` fuera si el precio no está
     * rebajado de verdad, con Special Price nada más que hable de precio; «Top ventas» y «Nuevo» sí
     * pueden ir con ella. En la ficha, además, si el precio presentado ya no es el Special Price
     * (opción con regalo elegida, cupo agotado para esta cesta), la «Special Price» se quita: la
     * etiqueta dice lo mismo que el precio (`KpcFicha`).
     */
    public function hookActionProductFlagsModifier(array $params): void
    {
        $producto = (array) ($params['product'] ?? []);
        KpcFlags::ajustar($params['flags'], $producto, $this);
        $idProducto = (int) ($producto['id_product'] ?? 0);
        if ($idProducto > 0 && isset($params['flags']['kpy_special_price'])
            && $this->context->controller instanceof ProductControllerCore && $idProducto === (int) Tools::getValue('id_product')) {
            try {
                if (KpcFicha::de($this, $idProducto)->estado((int) ($producto['id_product_attribute'] ?? 0))['especial_fuera']) {
                    unset($params['flags']['kpy_special_price']);
                }
            } catch (Throwable $e) {
                PrestaShopLogger::addLog('kpycoupons, las etiquetas de la ficha #' . $idProducto . ': ' . $e->getMessage(), 2, null, 'Product', $idProducto, true);
            }
        }
    }

    /**
     * Lo que la cesta cuenta de las promociones, **dentro de los totales**.
     *
     * Va en este hook y no en `displayShoppingCart` porque los totales los vuelve a pintar
     * PrestaShop cada vez que cambia la cesta (cantidad, vale, producto). Arriba, en
     * `displayShoppingCart`, el bloque se quedaba con los importes de al cargar: bajabas de 4 a 3
     * unidades y seguía diciendo el descuento de 4.
     *
     * Los vales aplicados los lista PrestaShop (con su importe y su papelera) justo encima de «¿Tienes
     * un cupón?», como dijo Julián: aquí solo se dice qué es cada uno para que `cesta.js` los dibuje
     * con su color, y se añade lo que no es un vale (Special Price) y el empujón («añade 1 ud.»).
     */
    public function hookDisplayShoppingCartAfterDetailedTotals(array $params): string
    {
        $cart = $params['cart'] ?? null;
        $cart = $cart instanceof Cart ? $cart : $this->context->cart;
        if (!$cart || !$cart->id) {
            return '';
        }
        $kpc = KpcResumen::deLaCesta($cart, (int) $this->context->shop->id, $this);
        if ($kpc['vales'] === [] && $kpc['lineas'] === [] && $kpc['animos'] === []) {
            return '';
        }
        // Lo que otro módulo añade por `actionKpyCouponsResumen` se imprime en `style=`: solo colores.
        foreach ($kpc['lineas'] as $i => $l) {
            foreach (['estilo', 'estilo_icono'] as $clave) {
                if (isset($l[$clave])) {
                    $kpc['lineas'][$i][$clave] = self::estiloSeguro((string) $l[$clave]);
                }
            }
        }
        foreach ($kpc['vales'] as $i => $v) {
            foreach (['fondo', 'color'] as $clave) {
                if (isset($v[$clave]) && !preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) $v[$clave])) {
                    unset($kpc['vales'][$i][$clave]);
                }
            }
        }
        $this->context->smarty->assign(['kpc' => $kpc + [
            'datos' => json_encode([
                'vales' => (object) $kpc['vales'],
                'quitar' => $this->context->link->getModuleLink($this->name, 'aplicar', [], true),
                'textos' => ['quitar_regalo' => $this->t('Quitar el regalo: el artículo vuelve a su precio de hoy')],
            ], JSON_UNESCAPED_UNICODE),
        ]]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/carrito.tpl');
    }

    /**
     * Debajo del nombre de cada fila de la cesta, su promoción: el regalo («Regalo · Saco de 3 Kg ·
     * 0,00 € · Con tu Saco de 12 Kg»), la versión con regalo («Kilos gratis · Lleva de regalo…»), el
     * Special Price, la 2ª unidad o el cupón (ver `KpcLineas::bloques`). El tema esconde formato y
     * precio en la fila del regalo: esto lo dice.
     */
    public function hookDisplayCartExtraProductActions(array $params): string
    {
        $producto = $params['product'] ?? null;
        $cart = $this->context->cart;
        if (!$producto || !$cart instanceof Cart || !$cart->id) {
            return '';
        }
        try {
            $bloques = KpcLineas::bloques($producto, $cart, (int) $this->context->shop->id, $this);
            if ($bloques === []) {
                return '';
            }
            $this->context->smarty->assign('kpc_bloques', $bloques);

            return $this->fetch('module:' . $this->name . '/views/templates/hook/linea-promo.tpl');
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, promoción en la línea de la cesta: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);

            return '';
        }
    }
    /**
     * Las filas de los hooks propios (`HOOKS_PROPIOS`) en `hook`, con título y descripción, para que
     * salgan en Diseño → Posiciones. `hook.name` es único: si ya existe, solo se actualiza el texto.
     * Se puede llamar siempre (instalación, `upgrade`).
     */
    public function asegurarHooks(): void
    {
        foreach (self::HOOKS_PROPIOS as $nombre => [$titulo, $descripcion]) {
            try {
                $id = (int) Hook::getIdByName($nombre, false);
                $hook = $id > 0 ? new Hook($id) : new Hook();
                if ($id > 0 && (string) $hook->title === $titulo && (string) $hook->description === $descripcion) {
                    continue;
                }
                $hook->name = $nombre;
                $hook->title = $titulo;
                $hook->description = $descripcion;
                $hook->position = true;
                if (property_exists($hook, 'active')) {
                    $hook->active = true;
                }
                $id > 0 ? $hook->update() : $hook->add();
            } catch (Throwable $e) {
                PrestaShopLogger::addLog('kpycoupons, al crear el hook ' . $nombre . ': ' . $e->getMessage(), 2, null, 'Hook', 0, true);
            }
        }
    }

    /**
     * Las posiciones que importan, por la API del core (`Module::updatePosition`: un movimiento
     * relativo que devuelve `false` si ya está donde toca o está solo en el hook, y eso no es un
     * error). Si se cambian en Diseño → Posiciones, la pantalla avisa (`KpcPantalla`).
     *   displayCartExtraProductActions   el primero: la línea de regalo antes del «Recíbelo entre…»;
     *   actionProductFlagsModifier       detrás de kpyspecialprices (o de kpyproductflags): repasa lo suyo;
     *   actionPresentProduct             detrás de kpyspecialprices: pisa `kpy_special_price` cuando toca;
     *   displayProductAttributeFlags     detrás de kpygifts: su chip, primero.
     */
    public function colocarHooks(): void
    {
        $this->colocar('displayCartExtraProductActions', null);
        if (!$this->colocar('actionProductFlagsModifier', 'kpyspecialprices')) {
            $this->colocar('actionProductFlagsModifier', 'kpyproductflags');
        }
        $this->colocar('actionPresentProduct', 'kpyspecialprices');
        $this->colocar('displayProductAttributeFlags', 'kpygifts');
    }

    /**
     * Coloca este módulo en un hook: el primero (`$detrasDe` null) o justo detrás de otro módulo.
     * Devuelve false si el otro módulo no está en ese hook (no hay detrás de quién ponerse).
     */
    public function colocar(string $hook, ?string $detrasDe): bool
    {
        $idHook = (int) Hook::getIdByName($hook);
        if ($idHook <= 0 || !$this->id) {
            return false;
        }
        $idTienda = (int) (($this->context->shop ? $this->context->shop->id : 0) ?: Configuration::get('PS_SHOP_DEFAULT'));
        $filas = Db::getInstance()->executeS(
            'SELECT hm.id_module, hm.position, m.name FROM ' . _DB_PREFIX_ . 'hook_module hm
             INNER JOIN ' . _DB_PREFIX_ . 'module m ON m.id_module = hm.id_module
             WHERE hm.id_hook = ' . $idHook . ' AND hm.id_shop = ' . $idTienda . '
             ORDER BY hm.position ASC, hm.id_module ASC'
        ) ?: [];
        $mia = null;
        $otra = null;
        foreach ($filas as $i => $f) {
            if ((int) $f['id_module'] === (int) $this->id) {
                $mia = $i;
            } elseif ($detrasDe !== null && (string) $f['name'] === $detrasDe) {
                $otra = $i;
            }
        }
        if ($mia === null || ($detrasDe !== null && $otra === null)) {
            return false;
        }
        $destino = $detrasDe === null ? 0 : ($otra < $mia ? $otra + 1 : $otra);
        if ($destino === $mia) {
            return true;
        }
        // way 1 = bajar (la lista en orden ASC), 0 = subir; `$position`, dónde quedarse.
        $this->updatePosition($idHook, $destino > $mia ? 1 : 0, (int) $filas[$destino]['position']);

        return true;
    }

    /**
     * Lo que este módulo espera de Diseño → Posiciones, dicho en claro, para la pantalla y para
     * `comprobar-despues.php`: hook => [módulos que deben ir antes, módulos que deben ir después].
     *
     * @return array<string, string> hook => aviso (vacío si todo está en su sitio)
     */
    public function posicionesMal(): array
    {
        $esperado = [
            'actionProductFlagsModifier' => [['kpygifts', 'kpyproductflags', 'kpyspecialprices'], ['kpypromolanding']],
            'actionPresentProduct' => [['kpyspecialprices'], []],
            'displayCartExtraProductActions' => [[], ['kpyproductavailabilitymessages']],
        ];
        $avisos = [];
        $idTienda = (int) (($this->context->shop ? $this->context->shop->id : 0) ?: Configuration::get('PS_SHOP_DEFAULT'));
        foreach ($esperado as $hook => [$antes, $despues]) {
            $idHook = (int) Hook::getIdByName($hook);
            if ($idHook <= 0) {
                continue;
            }
            // De la tabla, no de `Hook::getHookModuleExecList` (PrestaShop lo guarda en caché durante la petición).
            $orden = array_values(array_map(static fn (array $f): string => (string) $f['name'], Db::getInstance()->executeS(
                'SELECT m.name FROM ' . _DB_PREFIX_ . 'hook_module hm INNER JOIN ' . _DB_PREFIX_ . 'module m ON m.id_module = hm.id_module
                 WHERE hm.id_hook = ' . $idHook . ' AND hm.id_shop = ' . $idTienda . ' ORDER BY hm.position ASC, hm.id_module ASC'
            ) ?: []));
            $mia = array_search($this->name, $orden, true);
            if ($mia === false) {
                continue;
            }
            foreach ($antes as $m) {
                $pos = array_search($m, $orden, true);
                if ($pos !== false && $pos > $mia) {
                    $avisos[$hook] = $m . ' va detrás de kpycoupons en ' . $hook . ' y debería ir delante';
                }
            }
            foreach ($despues as $m) {
                $pos = array_search($m, $orden, true);
                if ($pos !== false && $pos < $mia) {
                    $avisos[$hook] = $m . ' va delante de kpycoupons en ' . $hook . ' y debería ir detrás';
                }
            }
        }

        return $avisos;
    }

    /** ¿La visita es de un robot? Para él ningún cupón se aplica ni se escribe en la cookie (`KpcFicha::esRobot`). */
    public function esRobot(): bool
    {
        static $es = null;

        return $es ??= KpcFicha::esRobot((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    }

    /** Un estilo en línea que viene de otro módulo (hook `actionKpyCouponsResumen`): solo colores. */
    public static function estiloSeguro(string $estilo): string
    {
        $salida = [];
        foreach (explode(';', $estilo) as $regla) {
            if (preg_match('/^\s*(background(?:-color)?|color|border-color)\s*:\s*(#[0-9A-Fa-f]{3,8}|[a-zA-Z]{3,20}|rgba?\([\d\s.,%]+\))\s*(!important)?\s*$/', $regla, $m)) {
                $salida[] = $m[1] . ':' . $m[2] . (isset($m[3]) && $m[3] !== '' ? ' !important' : '');
            }
        }

        return implode(';', $salida);
    }

    /**
     * Las promociones, también en el buscador de Doofinder.
     *
     * Doofinder no pinta las tarjetas de PrestaShop: las pinta él con lo que lleva en su feed. Así
     * que la promoción viaja con el producto, en cuatro columnas más al final del feed de siempre
     * (ver `KpcFlags::paraDoofinder`), y su plantilla decide con ellas la etiqueta y el precio
     * resaltado. Antes se ponía a mano por marca y salía «Precio especial» en productos sin
     * promoción.
     *
     * Es el enganche oficial del módulo de Doofinder para añadir columnas desde otro módulo
     * (`feeds/product.php`): no se toca su código, la dirección del feed es la misma y una
     * actualización de Doofinder no lo borra.
     *
     * Doofinder junta estas filas con las suyas por `id_product` y solo usa la primera de cada
     * producto: si otro módulo ya trae fila para uno, las columnas se añaden a esa. Y si el feed se
     * pide por páginas (`limit`/`offset`), van solo las de los productos de esa página: con una
     * página vacía no se añade nada, porque Doofinder daría las filas de aquí por productos
     * (`arrayMergeByIdProduct`). La página se calcula aquí con la misma consulta que Doofinder
     * (`paginaDoofinder`), sin llamar a su código. Se apaga con `KPC_DOOFINDER = 0`.
     */
    public function hookActionDoofinderExtendFeed(array $params): void
    {
        $encendido = Configuration::getGlobalValue(self::CONF_DOOFINDER);
        if ($encendido !== false && (int) $encendido === 0) {
            return;
        }
        $columnas = ['kpy_especial', 'kpy_precio_habitual', 'kpy_promo', 'kpy_promo_tipo'];
        $filas = (array) ($params['extra_rows'] ?? []);
        try {
            $datos = KpcFlags::paraDoofinder($this);
            if (!empty($params['limit'])) {
                $pagina = $this->paginaDoofinder((int) $params['limit'], (int) ($params['offset'] ?? 0));
                if ($pagina === []) {
                    return;
                }
                $datos = array_intersect_key($datos, array_flip($pagina));
            }
            $params['extra_header'] = array_merge((array) ($params['extra_header'] ?? []), $columnas);
            $yaEsta = [];
            foreach ($filas as $i => $fila) {
                $yaEsta[(int) ($fila['id_product'] ?? 0)] ??= $i;
            }
            foreach ($datos as $idProducto => $suyo) {
                if (isset($yaEsta[$idProducto])) {
                    $filas[$yaEsta[$idProducto]] += $suyo;
                } else {
                    $filas[] = ['id_product' => (int) $idProducto] + $suyo;
                }
            }
        } catch (Throwable $e) {
            // Sin promociones, pero con el feed entero: un fallo aquí no puede dejar el buscador vacío.
            PrestaShopLogger::addLog('kpycoupons, feed de Doofinder: ' . $e->getMessage(), 3, null, 'KpcFlags', 0, true);
        }
        $params['extra_rows'] = $filas;
    }

    /**
     * Los productos de una página del feed de Doofinder: la **misma consulta** que
     * `DfTools::getAvailableProductsIds` (Doofinder 8.x), copiada y no llamada (ningún módulo llama a
     * funciones de otro): activos, visibles en el buscador, de la tienda, por `id_product`. Si
     * Doofinder cambiara su página, las columnas de aquí no caerían en productos sueltos: se cruzan
     * por `id_product` y las que no coincidan se ignoran.
     *
     * @return int[]
     */
    private function paginaDoofinder(int $limite, int $desde): array
    {
        $q = new DbQuery();
        $q->select('product_shop.id_product');
        $q->from('product', 'p');
        $q->join(Shop::addSqlAssociation('product', 'p'));
        $q->where('product_shop.active = 1');
        $q->where("product_shop.visibility IN ('search', 'both')");
        $q->where('product_shop.id_shop IN (' . implode(', ', array_map('intval', Shop::getContextListShopID())) . ')');
        $q->orderBy('product_shop.id_product');
        $q->limit(max(1, $limite), max(0, $desde));

        return array_map('intval', array_column(Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($q) ?: [], 'id_product'));
    }

    /** Traducción de los textos de la tienda, para las clases del módulo. */
    public function t(string $texto, array $reemplazos = []): string
    {
        return $this->trans($texto, $reemplazos, 'Modules.Kpycoupons.Shop');
    }

    /** Traducción de los textos del back office, para la pantalla. */
    public function tAdmin(string $texto, array $reemplazos = []): string
    {
        return $this->trans($texto, $reemplazos, 'Modules.Kpycoupons.Admin');
    }

    /** El cupón que el cliente lleva puesto (vacío si no lleva, o si lo ha quitado). */
    public function elegido(): string
    {
        $valor = $this->valorCookie(self::COOKIE);

        return $valor !== '' && $valor[0] !== '!' ? $valor : '';
    }

    /** El cupón que el cliente ha quitado a mano: ese no vuelve a entrar solo mientras navega. */
    public function quitado(): string
    {
        $valor = $this->valorCookie(self::COOKIE);

        return $valor !== '' && $valor[0] === '!' ? substr($valor, 1) : '';
    }

    public function guardar(string $codigo): void
    {
        $this->escribirCookie(self::COOKIE, $codigo);
    }

    /** Quitar el cupón: se apunta cuál, para que no vuelva a entrar solo. */
    public function olvidar(string $codigo = ''): void
    {
        $this->escribirCookie(self::COOKIE, $codigo !== '' ? '!' . mb_strtoupper($codigo) : '');
    }

    /**
     * ¿Este cupón entra solo para esta visita? Por la web entera o por el canal del que llegó, y
     * siempre que el cliente no lo haya quitado.
     */
    public function entraSolo(array $cupon): bool
    {
        if (mb_strtoupper($this->quitado()) === mb_strtoupper((string) ($cupon['codigo'] ?? ''))) {
            return false;
        }
        $canal = KpcElegibles::canal($_GET) ?: $this->valorCookie(self::COOKIE_CANAL);

        return KpcElegibles::aplicadoDeEntrada($cupon, $canal);
    }

    /** Pantalla del módulo: la de Catálogo → Kompy · Promociones. */
    public function getContent(): string
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(self::PESTANA));

        return '';
    }

    /**
     * Si la visita llega de un canal, se recuerda. Y como es una visita nueva desde ese canal, el
     * cupón que el cliente hubiera quitado vuelve a poder entrar solo (es lo que se le ha prometido
     * en el anuncio o en el email).
     */
    private function recordarCanal(): void
    {
        if ($this->esRobot()) {
            return;
        }
        $canal = KpcElegibles::canal($_GET);
        if ($canal === '') {
            return;
        }
        if ($this->valorCookie(self::COOKIE_CANAL) !== $canal) {
            $this->escribirCookie(self::COOKIE_CANAL, $canal);
        }
        if ($this->quitado() !== '') {
            $this->escribirCookie(self::COOKIE, '');
        }
    }

    /** `?kpc=EUKA10`: el cupón queda puesto, y en la cesta si ya hay algo suyo. */
    private function enlaceConCupon(int $idTienda): void
    {
        $codigo = trim((string) Tools::getValue(self::PARAMETRO));
        if ($codigo === '' || !preg_match('/^[A-Za-z0-9_-]{2,32}$/', $codigo) || $this->esRobot()) {
            return;
        }
        $cupon = KpcCupon::porCodigo($codigo, $idTienda);
        if ($cupon === null || ($cupon['tipo'] ?? 'cupon') !== 'cupon') {
            return;
        }
        $this->guardar((string) $cupon['codigo']);
        if ($this->context->cart && $this->context->cart->id) {
            $this->sincronizarCesta($this->context->cart);
        }
    }

    private function valorCookie(string $nombre): string
    {
        $cookie = $this->context->cookie;

        return $cookie && isset($cookie->{$nombre}) ? (string) $cookie->{$nombre} : '';
    }

    private function escribirCookie(string $nombre, string $valor): void
    {
        $cookie = $this->context->cookie;
        if (!$cookie) {
            return;
        }
        $cookie->{$nombre} = $valor;
        $cookie->write();
    }
}
