<?php
/**
 * 2.2.0 (16/10/2026, noche): el regalo con la compra, por unidades (Rafa: «tiene que estar todo bien
 * correcto, para que no haya jaleos, sobre todo si en algún producto tenemos varias promos»).
 *   - `kpy_coupon_regalo_eleccion.unidades`: cuántas unidades de cada línea van con regalo. Cada
 *     tarjeta de la ficha añade lo suyo («12 kg + 3 kg gratis» y «12 kg» con su Special Price
 *     conviven en la cesta); antes la línea iba entera con regalo o entera sin él. Las elecciones
 *     que ya hubiera pasan a tener todas las unidades de su línea (lo que eran);
 *   - `kpy_coupon_regalo_cesta.unidades`: las unidades del disparador que llevan cada regalo;
 *   - hook `actionCartUpdateQuantityBefore`: a qué parte de la línea va cada cambio de cantidad;
 *   - hook `actionPresentCart`: la cesta pinta cada parte de una línea en su fila.
 * Se puede ejecutar más de una vez: solo añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_2_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $ok = true;

    // Las tablas, por si se viene de una versión sin regalo (CREATE IF NOT EXISTS: las de siempre no se tocan).
    if ($module instanceof Kpycoupons) {
        $ok = $module->tablas();
    }
    $hay = static function (string $tabla, string $columna) use ($db, $p): bool {
        return (bool) $db->getValue(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($p . $tabla) . "' AND COLUMN_NAME = '" . pSQL($columna) . "'"
        );
    };
    if (!$hay('kpy_coupon_regalo_eleccion', 'unidades')) {
        $ok = $ok && (bool) $db->execute("ALTER TABLE {$p}kpy_coupon_regalo_eleccion ADD COLUMN unidades INT UNSIGNED NOT NULL DEFAULT 0 AFTER id_specific_price");
    }
    if (!$hay('kpy_coupon_regalo_cesta', 'unidades')) {
        $ok = $ok && (bool) $db->execute("ALTER TABLE {$p}kpy_coupon_regalo_cesta ADD COLUMN unidades INT UNSIGNED NOT NULL DEFAULT 0 AFTER cantidad");
    }
    // Las elecciones de antes: todas las unidades de su línea (la clave es el formato, o el producto
    // en negativo si no tiene). Las que no encuentran su línea las quita el próximo repaso de la cesta.
    $ok = $ok && (bool) $db->execute(
        "UPDATE {$p}kpy_coupon_regalo_eleccion e
         INNER JOIN (
             SELECT id_cart, IF(id_product_attribute > 0, id_product_attribute, -id_product) AS clave, SUM(quantity) AS uds
             FROM {$p}cart_product WHERE id_customization = 0 GROUP BY id_cart, clave
         ) l ON l.id_cart = e.id_cart AND l.clave = e.clave
         SET e.unidades = l.uds
         WHERE e.unidades = 0"
    );

    foreach (['actionCartUpdateQuantityBefore', 'actionPresentCart'] as $hook) {
        if (!$module->isRegisteredInHook($hook)) {
            $ok = $ok && $module->registerHook($hook);
        }
    }

    return $ok;
}
