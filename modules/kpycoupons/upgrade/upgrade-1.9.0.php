<?php
/**
 * 1.9.0 (14/10/2026): tres huecos para la landing de promociones (`kpypromolanding`), sin cambiar
 * tablas ni hooks propios:
 *   - `displayKpyCouponsFormulario`: lo que otro módulo pinta al final del formulario (el paso 6,
 *     «En la landing»);
 *   - `actionKpyCouponsGuardar`: al guardar una promoción, antes del reparto;
 *   - `actionKpyCouponsResumen`: repasar los textos de la cesta (durante una campaña, «Cupón Black
 *     −10 % · EUKA10»).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_9_0(Module $module): bool
{
    return true;
}
