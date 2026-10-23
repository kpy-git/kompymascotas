<?php
/**
 * 1.7.0 (Rafa, 30/09/2026):
 *
 *   - Etiquetas **por formato**: la foto de la ficha dice la promoción del formato elegido; los
 *     listados y el buscador, todas las del producto. «Top ventas» y «Nuevo» pueden ir junto a
 *     «Special Price»; lo que habla de precio, no.
 *   - Doofinder: `kpy_especial`, `kpy_precio_habitual`, `kpy_promo` y `kpy_promo_tipo`.
 *   - La regla de carrito de la 2ª unidad de cada cesta dura hasta el fin de su promoción (no un
 *     mes) y las que sobran se barren solas cada 6 horas (`KpcLimpieza`).
 *
 * Aquí se rehace todo (las etiquetas se reescriben por formato) y se hace el primer barrido.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_0($modulo): bool
{
    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);
    try {
        KpcReparto::rehacer((int) Configuration::get('PS_SHOP_DEFAULT'));
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 1.7.0, rehacer: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }
    try {
        KpcLimpieza::barrer();
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 1.7.0, barrido: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }
    Configuration::updateGlobalValue(KpcLimpieza::CUANDO, time() + KpcLimpieza::CADA);

    return true;
}
