<?php
/**
 * 1.4.0: la cesta.
 *
 *   - Las reglas de carrito de los cupones pasan a restringir **por combinación exacta**. Con
 *     «productos + atributos», PrestaShop 9 se conformaba con una de las dos y el cupón EUKA10
 *     descontaba el Boske de 15 kg. Aquí se rehacen todas.
 *   - Lo que la cesta cuenta de las promociones pasa de `displayShoppingCart` (se pintaba al
 *     cargar y no se movía al cambiar la cantidad) a `displayShoppingCartAfterDetailedTotals`,
 *     dentro de los totales que PrestaShop vuelve a pintar en cada cambio.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($modulo): bool
{
    if (!$modulo->isRegisteredInHook('displayShoppingCartAfterDetailedTotals')
        && !$modulo->registerHook('displayShoppingCartAfterDetailedTotals')) {
        return false;
    }
    if ($modulo->isRegisteredInHook('displayShoppingCart')) {
        $modulo->unregisterHook('displayShoppingCart');
    }

    // Las reglas, rehechas por combinación: las rehace la 1.5.0 (`KpcReparto::rehacer`), que va
    // justo detrás y ya sabe hacerlo también para los productos sin combinaciones.

    return true;
}
