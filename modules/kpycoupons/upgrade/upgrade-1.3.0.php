<?php
/**
 * 1.3.0: las etiquetas también en el feed de Doofinder, y los ficheros de Google Merchant servidos
 * por su dirección para que Merchant los recoja solo.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($modulo): bool
{
    if (!$modulo->isRegisteredInHook('actionDoofinderExtendFeed')
        && !$modulo->registerHook('actionDoofinderExtendFeed')) {
        return false;
    }

    // La clave de las direcciones de Merchant se crea aquí, para que la pantalla ya la tenga.
    require_once _PS_MODULE_DIR_ . 'kpycoupons/classes/KpcMerchant.php';
    KpcMerchant::clave();

    return true;
}
