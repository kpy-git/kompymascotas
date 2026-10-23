<?php
/**
 * 1.1.1 (15/10/2026): el botón del menú con el nombre y los colores de la campaña (hook
 * `displayKpyPromoMenu`, que llama la plantilla parcheada de kpymenu) y la biblioteca con tipo y gama
 * (`tipo` y `gama` en `kpy_promo_imagen`): un logo o un saco subidos a mano se usan solos.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_1(Module $module): bool
{
    if (method_exists($module, 'tablas') && !$module->tablas()) {
        return false;
    }
    foreach (Kpypromolanding::HOOKS as $hook) {
        if (!$module->isRegisteredInHook($hook) && !$module->registerHook($hook)) {
            return false;
        }
    }
    // El factor de tamaño de los logos se escribe en la próxima sincronización.
    Configuration::updateGlobalValue(Kpypromolanding::CONF_PENDIENTE, 1);

    return true;
}
