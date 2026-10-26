<?php
/**
 * 3.1.0 (26/10/2026): «¿Dónde se ve?». Un cupón puede verse solo para quien llega de un canal (Google,
 * email, redes): columna `kpy_coupon.ver_canal` (vacía = en toda la tienda, como hasta ahora). Nada
 * más cambia en la base de datos; los hooks son los mismos. Va con `kpypromolanding` 1.5.1 (que deja
 * fuera de la landing lo que no se ve). Se puede ejecutar más de una vez.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_1_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $existe = (int) $db->getValue(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = '{$p}kpy_coupon' AND COLUMN_NAME = 'ver_canal'"
    );
    if (!$existe && !$db->execute("ALTER TABLE {$p}kpy_coupon ADD COLUMN ver_canal VARCHAR(64) NOT NULL DEFAULT '' AFTER auto_canal")) {
        return false;
    }

    return true;
}
