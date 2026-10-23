<?php
/**
 * 1.1.0 (14/10/2026): la foto de cada tarjeta (nunca una de transición: `kpy_promo_foto` y el hook
 * `actionPresentProductListing`), el saco de cada mascota en los banners, la capa de campaña en la
 * ficha (`actionKpyProductBuyCapa`), en la cesta (`actionKpyCouponsResumen`), en la portada de la
 * tienda (`displayContentWrapperTop`) y en el bloque de cada marca, y el paso 6 en el formulario de
 * Kompy · Promociones (`displayKpyCouponsFormulario`, `actionKpyCouponsGuardar`).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0(Module $module): bool
{
    if (method_exists($module, 'tablas') && !$module->tablas()) {
        return false;
    }
    foreach (Kpypromolanding::HOOKS as $hook) {
        if (!$module->isRegisteredInHook($hook) && !$module->registerHook($hook)) {
            return false;
        }
    }
    // La foto de las tarjetas y el saco por mascota se calculan en la próxima sincronización.
    Configuration::updateGlobalValue(Kpypromolanding::CONF_PENDIENTE, 1);
    // La portada de la tienda durante una campaña: Boske primero y 8 productos; y las campañas
    // predefinidas que falten (Cyber Monday, Reyes Magos), con su paleta.
    if (Configuration::getGlobalValue(Kpypromolanding::CONF_MARCAS) === false) {
        Configuration::updateGlobalValue(Kpypromolanding::CONF_MARCAS, 'Boske');
    }
    if (Configuration::getGlobalValue(Kpypromolanding::CONF_PORTADA_N) === false) {
        Configuration::updateGlobalValue(Kpypromolanding::CONF_PORTADA_N, 8);
    }
    try {
        KplCampanas::crearPredefinidas();
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpypromolanding 1.1.0, campañas predefinidas: ' . $e->getMessage(), 3, null, 'Kpypromolanding', 0, true);
    }

    return true;
}
