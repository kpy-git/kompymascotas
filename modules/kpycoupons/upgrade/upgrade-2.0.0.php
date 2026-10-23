<?php
/**
 * 2.0.0 (16/10/2026): el cuarto tipo de promoción, «Regalo con la compra» (ver `KpcRegalo`):
 *   - `kpy_coupon`: el tipo `regalo` y sus columnas (`regalo_modo`, `regalo_por`, `regalo_tope`,
 *     `regalo_packs`, `regalo_mismo`, `regalo_reserva`, `regalo_parejas`);
 *   - tres tablas nuevas: `kpy_coupon_regalo` (qué regala cada artículo), `kpy_coupon_regalo_cesta`
 *     (qué parte de cada línea de una cesta es regalo) y `kpy_coupon_regalo_pedido` (lo regalado en
 *     cada pedido: el sell-out para la marca y el tope de la campaña);
 *   - hooks nuevos: `actionValidateOrder` (apuntar lo regalado y «(regalo)» en la línea),
 *     `actionPresentCartProduct` y `actionPresentProductListing` (la línea de regalo en la cesta),
 *     `actionPaymentModuleProductVarTplAfter` («(regalo)» en el email del pedido).
 * Se puede ejecutar más de una vez: solo añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_0_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $tabla = pSQL($p . 'kpy_coupon');

    $columna = static function (string $nombre) use ($db, $tabla): bool {
        return (bool) $db->getValue(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tabla}' AND COLUMN_NAME = '" . pSQL($nombre) . "'"
        );
    };

    $ok = (bool) $db->execute("ALTER TABLE {$p}kpy_coupon MODIFY tipo ENUM('cupon','segunda_unidad','special_price','regalo') NOT NULL DEFAULT 'cupon'");
    foreach ([
        'regalo_modo' => "VARCHAR(16) NOT NULL DEFAULT 'pequeno'",
        'regalo_por' => "VARCHAR(8) NOT NULL DEFAULT 'unidad'",
        'regalo_tope' => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'regalo_packs' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'regalo_mismo' => "VARCHAR(24) NOT NULL DEFAULT ''",
        'regalo_reserva' => "VARCHAR(24) NOT NULL DEFAULT ''",
        'regalo_parejas' => 'MEDIUMTEXT NULL',
    ] as $nombre => $definicion) {
        if (!$columna($nombre)) {
            $ok = $ok && (bool) $db->execute("ALTER TABLE {$p}kpy_coupon ADD COLUMN {$nombre} {$definicion}");
        }
    }

    // Las tablas nuevas (CREATE IF NOT EXISTS: las de siempre no se tocan).
    if ($module instanceof Kpycoupons) {
        $ok = $ok && $module->tablas();
    }

    foreach (['actionValidateOrder', 'actionPresentCartProduct', 'actionPresentProductListing', 'actionPaymentModuleProductVarTplAfter'] as $hook) {
        if (!$module->isRegisteredInHook($hook)) {
            $ok = $ok && $module->registerHook($hook);
        }
    }

    // Las etiquetas se rehacen con el próximo reparto; que sea en la primera visita.
    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);

    return $ok;
}
