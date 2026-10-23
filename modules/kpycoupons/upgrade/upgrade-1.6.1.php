<?php
/**
 * 1.6.1: con Special Price, solo la etiqueta «Special Price» (Rafa, 30/09/2026).
 *
 *   - Ya no se escribe «Oferta −X %»: salía sobre la foto y en las tarjetas de los formatos que no
 *     tenían el Special Price. Al rehacer se borran las que había.
 *   - Un producto con Special Price no lleva tampoco la de un cupón o una 2ª unidad, ni las manuales.
 *   - Las de cupón y 2ª unidad se escriben con la fecha de fin de su promoción: se apagan a su hora.
 *   - Al terminar un Special Price se quita lo que puso (precio y fila de `kpyspecialprices`).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_1($modulo): bool
{
    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);
    try {
        KpcReparto::rehacer((int) Configuration::get('PS_SHOP_DEFAULT'));
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 1.6.1, rehacer: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }

    return true;
}
