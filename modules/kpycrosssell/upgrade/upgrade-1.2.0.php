<?php
/**
 * 1.2.0: los tres huecos con sus criterios (Boske primero, marcas de reserva, refuerzo de la
 * patología), el bloque a todo el ancho debajo de las dos columnas, y las parejas a mano por
 * producto, categoría o marca, con su posición.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_2_0($modulo): bool
{
    $p = _DB_PREFIX_;
    $motor = _MYSQL_ENGINE_;

    // La tabla de parejas cambia de forma: ahora valen para un producto, una categoría o una marca,
    // y llevan la posición en la que tienen que salir.
    $columnas = array_column(Db::getInstance()->executeS("SHOW COLUMNS FROM {$p}kpy_cross_sell") ?: [], 'Field');
    if (!in_array('ambito', $columnas, true)) {
        Db::getInstance()->execute("RENAME TABLE {$p}kpy_cross_sell TO {$p}kpy_cross_sell_viejo");
        Db::getInstance()->execute(
            "CREATE TABLE {$p}kpy_cross_sell (
                id_cross INT UNSIGNED NOT NULL AUTO_INCREMENT,
                ambito ENUM('producto','categoria','marca') NOT NULL DEFAULT 'producto',
                id_ref INT UNSIGNED NOT NULL,
                id_sugerido INT UNSIGNED NOT NULL,
                posicion TINYINT UNSIGNED NOT NULL DEFAULT 1,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id_cross),
                UNIQUE KEY pareja (ambito, id_ref, id_sugerido),
                KEY busqueda (ambito, id_ref, activo, posicion)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        );
        Db::getInstance()->execute(
            "INSERT IGNORE INTO {$p}kpy_cross_sell (ambito, id_ref, id_sugerido, posicion, activo)
             SELECT 'producto', id_product, id_sugerido, GREATEST(1, posicion), activo FROM {$p}kpy_cross_sell_viejo"
        );
        Db::getInstance()->execute("DROP TABLE {$p}kpy_cross_sell_viejo");
    }

    Configuration::updateValue('KCS_CUANTAS', 3);
    Configuration::updateValue('KCS_MARCA_PROPIA', (int) Db::getInstance()->getValue(
        'SELECT id_manufacturer FROM ' . $p . "manufacturer WHERE name = 'Boske'"
    ));

    // El bloque vuelve a ir debajo de las dos columnas, para poder ocupar todo el ancho.
    $modulo->unregisterHook((int) Hook::getIdByName('displayKpyCrossSell'));
    if (!$modulo->isRegisteredInHook('displayFooterProduct') && !$modulo->registerHook('displayFooterProduct')) {
        return false;
    }
    $id = (int) Hook::getIdByName('displayFooterProduct');

    return $id <= 0 || $modulo->updatePosition($id, false, 1);
}
