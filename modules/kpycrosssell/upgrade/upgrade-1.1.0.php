<?php
/**
 * 1.1.0: el descuento por llevarlos juntos deja de ser uno para toda la tienda y pasa a decidirse
 * **marca a marca**, con sus fechas y su interruptor. Y el bloque cambia de sitio: de debajo de la
 * ficha a dentro de la caja de compra, justo bajo el precio.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($modulo): bool
{
    $p = _DB_PREFIX_;
    $motor = _MYSQL_ENGINE_;

    if (!Db::getInstance()->execute(
        "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell_promo (
            id_promo INT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_manufacturer INT UNSIGNED NOT NULL DEFAULT 0,
            nombre VARCHAR(128) NOT NULL DEFAULT '',
            porcentaje DECIMAL(6,3) NOT NULL DEFAULT 0,
            misma_marca TINYINT(1) NOT NULL DEFAULT 1,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            fecha_desde DATE NULL,
            fecha_hasta DATE NULL,
            PRIMARY KEY (id_promo),
            UNIQUE KEY marca (id_manufacturer)
        ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
    )) {
        return false;
    }

    // Si había un descuento general puesto, se convierte en la regla «todas las marcas».
    $viejo = (float) Configuration::get('KCS_JUNTOS_PCT');
    if ($viejo > 0 && !Db::getInstance()->getValue("SELECT id_promo FROM {$p}kpy_cross_sell_promo WHERE id_manufacturer = 0")) {
        Db::getInstance()->insert('kpy_cross_sell_promo', [
            'id_manufacturer' => 0,
            'nombre' => 'Todas las marcas (venía de los ajustes)',
            'porcentaje' => $viejo,
            'misma_marca' => 0,
            'activo' => 1,
            'fecha_desde' => Configuration::get('KCS_JUNTOS_DESDE') ?: null,
            'fecha_hasta' => Configuration::get('KCS_JUNTOS_HASTA') ?: null,
        ]);
    }
    Configuration::deleteByName('KCS_JUNTOS_PCT');
    Configuration::deleteByName('KCS_JUNTOS_DESDE');
    Configuration::deleteByName('KCS_JUNTOS_HASTA');

    // El bloque ahora lo coloca `kpyproductbuy` dentro de la caja de compra.
    $modulo->unregisterHook((int) Hook::getIdByName('displayFooterProduct'));

    return $modulo->isRegisteredInHook('displayKpyCrossSell') || $modulo->registerHook('displayKpyCrossSell');
}
