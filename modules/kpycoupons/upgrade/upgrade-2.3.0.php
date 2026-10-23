<?php
/**
 * 2.3.0 (19/10/2026): promociones **por cantidad** (Rafa: «no podamos vender más de X unidades de
 * una promoción… una vez se agote el cupo, la promoción debe desactivarse y quedarse limpia 100 %»)
 * y el Special Price con nombre propio («Liquidación», «Bajada de precio»).
 *   - `kpy_coupon`: `cupo` (unidades; 0 = sin cupo), `cupo_agotado` (cuándo se agotó), `cupo_ficha`
 *     (si la ficha dice cuántas quedan) y `etiqueta` (el nombre del Special Price). El «tope de
 *     regalos» de antes (`regalo_tope`) pasa a `cupo`;
 *   - `kpy_coupon_cupo_cesta` (lo reservado en cada cesta) y `kpy_coupon_cupo_pedido` (lo vendido
 *     en cada pedido; los regalos siguen en `kpy_coupon_regalo_pedido`);
 *   - `kpy_coupon_precio_cesta`: el precio habitual de una cesta por línea (hasta ahora iba en la
 *     elección del regalo, `kpy_coupon_regalo_eleccion.id_specific_price`): se copian los que haya;
 *   - el hook `actionValidateOrderBefore` (la cesta se repasa justo antes de crear el pedido);
 *   - el reparto otra vez (las promociones con cupo que ya estuvieran agotadas, fuera).
 * Se puede ejecutar más de una vez: solo añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_3_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $ok = true;

    // Las tablas nuevas (CREATE IF NOT EXISTS: las de siempre no se tocan).
    if ($module instanceof Kpycoupons) {
        $ok = $module->tablas();
    }
    $hay = static function (string $tabla, string $columna) use ($db, $p): bool {
        return (bool) $db->getValue(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($p . $tabla) . "' AND COLUMN_NAME = '" . pSQL($columna) . "'"
        );
    };
    foreach ([
        'cupo' => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'cupo_agotado' => 'DATETIME NULL',
        'cupo_ficha' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'etiqueta' => "VARCHAR(16) NOT NULL DEFAULT ''",
    ] as $columna => $tipo) {
        if (!$hay('kpy_coupon', $columna)) {
            $ok = $ok && (bool) $db->execute("ALTER TABLE {$p}kpy_coupon ADD COLUMN {$columna} {$tipo}");
        }
    }
    // El tope de regalos de antes es ahora el cupo.
    $ok = $ok && (bool) $db->execute("UPDATE {$p}kpy_coupon SET cupo = regalo_tope WHERE tipo = 'regalo' AND cupo = 0 AND regalo_tope > 0");
    // Los precios de cesta que ya hubiera (de las elecciones de regalo), a su tabla.
    if ($hay('kpy_coupon_regalo_eleccion', 'id_specific_price')) {
        $ok = $ok && (bool) $db->execute(
            "INSERT IGNORE INTO {$p}kpy_coupon_precio_cesta (id_cart, clave, id_specific_price, date_upd)
             SELECT e.id_cart, e.clave, e.id_specific_price, e.date_upd FROM {$p}kpy_coupon_regalo_eleccion e
             INNER JOIN {$p}specific_price sp ON sp.id_specific_price = e.id_specific_price AND sp.id_cart = e.id_cart
             WHERE e.id_specific_price > 0"
        );
        $ok = $ok && (bool) $db->execute("UPDATE {$p}kpy_coupon_regalo_eleccion SET id_specific_price = 0");
    }

    foreach (['actionValidateOrderBefore'] as $hook) {
        if (!$module->isRegisteredInHook($hook)) {
            $ok = $ok && $module->registerHook($hook);
        }
    }

    // El reparto, en la próxima visita (o ahora, con el 2.4.0 que viene detrás): el código nuevo ya lee
    // columnas que añade el 2.4.0 (`cupo_lista`, `unidades`, `agotado`), así que rehacerlo aquí, en
    // mitad de la cadena de upgrades, daría un aviso («Unknown column c.cupo_lista»). Una promoción con
    // el cupo agotado deja de estar en vigor en cuanto se rehace.
    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);

    return $ok;
}
