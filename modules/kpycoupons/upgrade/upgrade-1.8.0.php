<?php
/**
 * 1.8.0: `KpcReparto::rehacer()` avisa por el hook `actionKpyCouponsReparto` cuando termina (lo
 * escucha la landing de promociones, `kpypromolanding`). No cambia tablas ni hooks propios.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_0(Module $module): bool
{
    return true;
}
