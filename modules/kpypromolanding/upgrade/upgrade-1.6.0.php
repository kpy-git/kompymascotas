<?php
/**
 * 1.6.0 (23/11/2026): los productos elegidos a mano de cada campaña (Rafa, 19/11: «que se puedan
 * elegir, para poner los más llamativos, más vendidos»). Columna nueva `productos_elegidos` en
 * `kpy_promo_campana` (ids en orden, separados por comas; vacía = como hasta ahora). En lo
 * automático de la portada, dentro de cada marca, por ventas de los últimos 90 días y sin agotados.
 *
 * Se puede ejecutar más de una vez: solo añade la columna si no está.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_0(Module $module): bool
{
    if (!method_exists($module, 'tablas') || !$module->tablas()) {
        return false;
    }
    try {
        KplPromos::calcularVentas90();
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpypromolanding 1.6.0, ventas de 90 días: ' . $e->getMessage(), 2, null, 'Kpypromolanding', 0, true);
    }

    return true;
}
