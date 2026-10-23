<?php
/**
 * 3.0.0 (23/10/2026): la generación **sin dependencias** (correo de Julián sobre el paquete del 19/10;
 * `docs/plan-desacoplar-kpycoupons.md`).
 *   - La ficha la pinta este módulo en los huecos del tema (`KpcFicha`): se registran
 *     `displayProductPriceBlock`, `displayProductAttributeFlags`, `actionPresentProduct`,
 *     `actionFrontControllerSetVariables` y `displayCartModalFooter`; se quita el hook de
 *     `kpyproductbuy` (`actionKpyProductBuyCoupon`), que ya no existe.
 *   - Los hooks propios con título y descripción (`Kpycoupons::HOOKS_PROPIOS`), y los de consulta que
 *     contesta este módulo (`actionKpyCouponsProductosConPromo`, `actionKpyCouponsAlcance`,
 *     `actionKpyCouponsPromociones`).
 *   - Las posiciones por la API del core (`colocarHooks`).
 *   - Las etiquetas se calculan por hook (`KpcFlags`, 2.5.0 → 3.0.0): las filas que las versiones
 *     anteriores escribieron en las tablas de `kpyproductflags` **no se borran desde aquí** (son
 *     tablas ajenas): se cuentan y se deja en el registro; las borra el SQL del paquete o
 *     `bin/limpiar-flags.php --borrar`.
 *   - El reparto otra vez (los parámetros del hook del reparto son ahora filas públicas).
 * Va con `kpypromolanding` 1.4.0, `kpysupply` 1.1.0 y `kpycrosssell` 1.6.0; `kpyproductbuy` se
 * desinstala. Se puede ejecutar más de una vez: solo añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0(Module $module): bool
{
    $ok = true;
    if (!$module instanceof Kpycoupons) {
        return $ok;
    }
    $ok = $module->tablas();

    // Los hooks que contesta (idempotente: `registerHook` ya comprueba si está).
    foreach (Kpycoupons::HOOKS as $hook) {
        try {
            if (!$module->isRegisteredInHook($hook)) {
                $ok = $module->registerHook($hook) && $ok;
            }
        } catch (Throwable $e) {
            $ok = false;
            PrestaShopLogger::addLog('kpycoupons 3.0.0, al registrar ' . $hook . ': ' . $e->getMessage(), 3, null, 'Hook', 0, true);
        }
    }
    // Los que ya no existen (sin `&&`: en una tienda que viene de cero no están).
    foreach (['actionKpyProductBuyCoupon', 'displayShoppingCart'] as $viejo) {
        try {
            if ($module->isRegisteredInHook($viejo)) {
                $module->unregisterHook($viejo);
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons 3.0.0, al quitar ' . $viejo . ': ' . $e->getMessage(), 2, null, 'Hook', 0, true);
        }
    }
    $module->asegurarHooks();
    $module->colocarHooks();
    if (Configuration::getGlobalValue(Kpycoupons::CONF_DOOFINDER) === false) {
        Configuration::updateGlobalValue(Kpycoupons::CONF_DOOFINDER, 1);
    }

    // Las filas viejas en las tablas de kpyproductflags: solo se cuentan (ver la cabecera).
    try {
        $cuenta = KpcFlags::limpiarTablasAjenas(true);
        $total = array_sum($cuenta);
        PrestaShopLogger::addLog(
            $total > 0
                ? 'kpycoupons 3.0.0: quedan ' . $total . ' filas de etiquetas viejas en las tablas de kpyproductflags (' . json_encode($cuenta) . '); se borran con deshacer/limpiar-flags.sql o bin/limpiar-flags.php --borrar'
                : 'kpycoupons 3.0.0: ninguna fila de etiquetas viejas en las tablas de kpyproductflags',
            1, null, 'KpcFlags', 0, true
        );
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 3.0.0, al contar las etiquetas viejas: ' . $e->getMessage(), 2, null, 'KpcFlags', 0, true);
    }

    try {
        KpcReparto::rehacer((int) Configuration::get('PS_SHOP_DEFAULT'));
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 3.0.0, al rehacer el reparto: ' . $e->getMessage(), 2, null, 'KpcReparto', 0, true);
    }

    return $ok;
}
