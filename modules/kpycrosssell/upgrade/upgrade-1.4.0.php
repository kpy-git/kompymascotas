<?php
/**
 * 1.4.0: las sugerencias se calculan una vez y se guardan en los productos relacionados de
 * PrestaShop (`accessory`), con su orden en `kpy_cross_sell_auto`. La ficha ya no calcula nada: lee.
 * Y un producto nuevo creado desde el back office nace con las suyas.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($modulo): bool
{
    $p = _DB_PREFIX_;
    $motor = _MYSQL_ENGINE_;

    Db::getInstance()->execute(
        "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell_auto (
            id_product INT UNSIGNED NOT NULL,
            id_sugerido INT UNSIGNED NOT NULL,
            posicion TINYINT UNSIGNED NOT NULL DEFAULT 1,
            origen VARCHAR(20) NOT NULL DEFAULT '',
            fecha DATETIME NULL,
            PRIMARY KEY (id_product, id_sugerido),
            KEY orden (id_product, posicion)
        ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
    );

    return $modulo->registerHook('actionProductAdd');
}
