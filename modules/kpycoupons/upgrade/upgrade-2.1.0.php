<?php
/**
 * 2.1.0 (16/10/2026): el regalo con la compra lo elige el cliente en la ficha (la tarjeta «12 kg +
 * 3 kg gratis») y el saco grande va a su precio de siempre, sin el Special Price (ver `KpcRegalo`):
 *   - `kpy_coupon`: `regalo_corto`, lo que va detrás del «+» en la tarjeta si el regalo es otra cosa;
 *   - tabla nueva `kpy_coupon_regalo_eleccion`: las líneas de cada cesta que van con regalo, con el
 *     precio de cesta que se les pone (`id_specific_price`);
 *   - hook `actionDuplicateCartData` («pedir de nuevo»: las unidades regaladas no se cobran);
 *   - el reparto se rehace (un regalo ya no se apila con un cupón ni una 2ª unidad en el mismo
 *     artículo: un artículo, una promoción).
 * Se puede ejecutar más de una vez: solo añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_1_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $tabla = pSQL($p . 'kpy_coupon');

    $ok = true;
    $hay = (bool) $db->getValue(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tabla}' AND COLUMN_NAME = 'regalo_corto'"
    );
    if (!$hay) {
        $ok = (bool) $db->execute("ALTER TABLE {$p}kpy_coupon ADD COLUMN regalo_corto VARCHAR(24) NOT NULL DEFAULT ''");
    }

    // La tabla nueva (CREATE IF NOT EXISTS: las de siempre no se tocan).
    if ($module instanceof Kpycoupons) {
        $ok = $ok && $module->tablas();
    }
    // Si la tabla se creó con una versión anterior de esta actualización (pruebas), sin el precio.
    $eleccion = pSQL($p . 'kpy_coupon_regalo_eleccion');
    $conPrecio = (bool) $db->getValue(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$eleccion}' AND COLUMN_NAME = 'id_specific_price'"
    );
    if (!$conPrecio) {
        $ok = $ok && (bool) $db->execute("ALTER TABLE {$p}kpy_coupon_regalo_eleccion ADD COLUMN id_specific_price INT UNSIGNED NOT NULL DEFAULT 0 AFTER clave");
    }
    if (!$module->isRegisteredInHook('actionDuplicateCartData')) {
        $ok = $ok && $module->registerHook('actionDuplicateCartData');
    }
    // En la cesta, la línea del regalo dice qué es, que va a 0 € y con qué saco va (el tema la esconde).
    if (!$module->isRegisteredInHook('displayCartExtraProductActions')) {
        $ok = $ok && $module->registerHook('displayCartExtraProductActions');
    }
    if ($module instanceof Kpycoupons) {
        // 3.0.0: `primeroEn` (UPDATE crudo) pasó a `colocar` (Module::updatePosition); los upgrades corren con el código final.
        if (method_exists($module, 'colocar')) {
            $module->colocar('displayCartExtraProductActions', null);
        } elseif (method_exists($module, 'primeroEn')) {
            $module->primeroEn('displayCartExtraProductActions');
        }
    }

    // El reparto y las etiquetas se rehacen con la próxima visita.
    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);

    return $ok;
}
