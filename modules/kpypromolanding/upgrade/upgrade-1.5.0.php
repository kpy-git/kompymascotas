<?php
/**
 * 1.5.0 (26/10/2026): la campaña también en el buscador. El módulo se engancha a
 * `actionDoofinderExtendFeed` (el enganche oficial del módulo de Doofinder para añadir columnas al
 * feed), detrás de kpycoupons, y añade seis columnas con el nombre y los colores de campaña de cada
 * producto que entra en ella (`kpy_campana…`). En el panel de Doofinder hay que marcar esas columnas
 * en «Campos indexados adicionales» y pegar la plantilla v6 (docs/doofinder/LEEME.md).
 *
 * Se puede ejecutar más de una vez: registra lo que falte y, si en ese hook kpycoupons fuera detrás
 * de este módulo, lo vuelve a registrar para que quede el último (sus columnas tienen que estar ya
 * en las filas cuando llega el turno de este).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0(Module $module): bool
{
    foreach (Kpypromolanding::HOOKS as $hook) {
        if (!$module->isRegisteredInHook($hook) && !$module->registerHook($hook)) {
            return false;
        }
    }
    $idHook = (int) Hook::getIdByName('actionDoofinderExtendFeed');
    if ($idHook > 0) {
        $posiciones = [];
        foreach (Db::getInstance()->executeS(
            'SELECT m.name, hm.position FROM ' . _DB_PREFIX_ . 'hook_module hm
             INNER JOIN ' . _DB_PREFIX_ . 'module m ON m.id_module = hm.id_module
             WHERE hm.id_hook = ' . $idHook . ' AND hm.id_shop = ' . (int) Configuration::get('PS_SHOP_DEFAULT')
        ) ?: [] as $f) {
            $posiciones[(string) $f['name']] = (int) $f['position'];
        }
        if (isset($posiciones['kpycoupons'], $posiciones[$module->name]) && $posiciones[$module->name] < $posiciones['kpycoupons']) {
            // Quitar y volver a registrar lo deja el último del hook.
            $module->unregisterHook('actionDoofinderExtendFeed');
            if (!$module->registerHook('actionDoofinderExtendFeed')) {
                return false;
            }
        }
    }

    return true;
}
