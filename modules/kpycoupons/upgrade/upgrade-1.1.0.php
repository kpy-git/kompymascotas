<?php
/**
 * 1.1.0: tipos de promoción (cupón y «2ª unidad»), datos para Google Merchant y el interruptor de
 * los packs.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($modulo): bool
{
    $p = _DB_PREFIX_;
    $columnas = array_column(Db::getInstance()->executeS("SHOW COLUMNS FROM {$p}kpy_coupon") ?: [], 'Field');
    $nuevas = [
        'tipo' => "ALTER TABLE {$p}kpy_coupon ADD COLUMN tipo ENUM('cupon','segunda_unidad') NOT NULL DEFAULT 'cupon' AFTER nombre",
        'incluir_packs' => "ALTER TABLE {$p}kpy_coupon ADD COLUMN incluir_packs TINYINT(1) NOT NULL DEFAULT 1 AFTER excluir_especial",
        'merchant_id' => "ALTER TABLE {$p}kpy_coupon ADD COLUMN merchant_id VARCHAR(64) NOT NULL DEFAULT '' AFTER id_cart_rule",
        'merchant_titulo' => "ALTER TABLE {$p}kpy_coupon ADD COLUMN merchant_titulo VARCHAR(160) NOT NULL DEFAULT '' AFTER merchant_id",
    ];
    foreach ($nuevas as $columna => $sql) {
        if (!in_array($columna, $columnas, true) && !Db::getInstance()->execute($sql)) {
            return false;
        }
    }

    return true;
}
