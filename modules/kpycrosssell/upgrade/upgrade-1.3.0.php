<?php
/**
 * 1.3.0: sugerencias coherentes (ni enfermedades cruzadas, ni edades cruzadas, ni higiene al lado
 * de un pienso), el bloque a todo el ancho en el ordenador, y en la pantalla se puede **quitar**
 * lo que no pega, además de fijarlo. Se apunta también lo que la gente se lleva de verdad.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_3_0($modulo): bool
{
    $p = _DB_PREFIX_;
    $motor = _MYSQL_ENGINE_;

    // «Quitar»: una fila con veto = 1 dice que ese producto no se ofrezca ahí, ni aunque el
    // catálogo lo proponga.
    $columnas = array_column(Db::getInstance()->executeS("SHOW COLUMNS FROM {$p}kpy_cross_sell") ?: [], 'Field');
    if (!in_array('veto', $columnas, true)) {
        Db::getInstance()->execute("ALTER TABLE {$p}kpy_cross_sell ADD veto TINYINT(1) NOT NULL DEFAULT 0 AFTER activo");
    }

    // Lo que se lleva la gente, para ordenar por lo que funciona (los «clics» del panel antiguo).
    Db::getInstance()->execute(
        "CREATE TABLE IF NOT EXISTS {$p}kpy_cross_sell_clic (
            id_ref INT UNSIGNED NOT NULL,
            id_sugerido INT UNSIGNED NOT NULL,
            id_shop INT UNSIGNED NOT NULL DEFAULT 1,
            vistas INT UNSIGNED NOT NULL DEFAULT 0,
            clics INT UNSIGNED NOT NULL DEFAULT 0,
            ultimo DATETIME NULL,
            PRIMARY KEY (id_ref, id_sugerido, id_shop)
        ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
    );

    return true;
}
