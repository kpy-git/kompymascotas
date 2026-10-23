<?php
/**
 * 1.4.0 (23/10/2026): sin llamadas a otros módulos. La landing lee el estado de las promociones por
 * el hook de consulta `actionKpyCouponsPromociones` de kpycoupons 3.0.0 (ya no cruza sus tablas),
 * contesta `actionKpyCouponsCampana` en vez de `actionKpyProductBuyCapa` (kpyproductbuy se retira),
 * deja de llamar a kpycoupons (`elegido()`: ahora la cookie `kpy_cupon`) y a ps_emailsubscription
 * (el boletín sale de la landing), y guarda el % de cada categoría en `kpy_promo_categoria.pct`
 * para ordenar los listados sin cruzar nada en cada visita.
 *
 * Se puede ejecutar más de una vez: registra lo que falte, quita lo que sobre, añade la columna si
 * no está y deja la sincronización pendiente (las imágenes se completan en la primera visita).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0(Module $module): bool
{
    // La columna `pct` de kpy_promo_categoria (y cualquier otra que falte).
    if (method_exists($module, 'tablas') && !$module->tablas()) {
        return false;
    }
    foreach (Kpypromolanding::HOOKS as $hook) {
        if (!$module->isRegisteredInHook($hook) && !$module->registerHook($hook)) {
            return false;
        }
    }
    // El hook de kpyproductbuy, fuera. Sin `&&`: en una tienda nueva no existe y no es un error.
    if (Hook::getIdByName('actionKpyProductBuyCapa') && $module->isRegisteredInHook('actionKpyProductBuyCapa')) {
        $module->unregisterHook('actionKpyProductBuyCapa');
    }
    // Las categorías, el resumen y el % de cada categoría, ya, por el hook nuevo; las imágenes, en
    // la primera visita (en consola no hay dominio para sus direcciones).
    try {
        KplSincronizar::conCerrojo((int) Configuration::get('PS_SHOP_DEFAULT'), false);
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpypromolanding 1.4.0, al sincronizar: ' . $e->getMessage(), 3, null, 'Kpypromolanding', 0, true);
    }
    Configuration::updateGlobalValue(Kpypromolanding::CONF_PENDIENTE, 1);

    return true;
}
