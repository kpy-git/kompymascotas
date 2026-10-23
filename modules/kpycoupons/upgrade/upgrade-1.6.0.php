<?php
/**
 * 1.6.0: promociones por marca, gama, tamaño y nivel de pack.
 *
 *   - `kpy_coupon`: `tamanos` (los tamaños a los que llega: «saco 12 kg», uno por línea) y `packs`
 *     (los niveles de pack que entran: «1» solo sueltos, «2» solo packs de 2; vacío = todos).
 *   - Los packs se leen de la tabla de Kompy (`kpy_packs`) y no solo del nombre: en Schesir la
 *     caja de 12 latas es lo suelto (antes se tomaba por pack y la 2ª unidad no le llegaba).
 *   - Se rehace todo el reparto con esa lectura.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_6_0($modulo): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;

    $columnas = [
        'tamanos' => 'TEXT NULL AFTER incluir_packs',
        'packs' => "VARCHAR(64) NOT NULL DEFAULT '' AFTER tamanos",
    ];
    foreach ($columnas as $columna => $definicion) {
        $existe = (int) $db->getValue(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '{$p}kpy_coupon' AND COLUMN_NAME = '" . pSQL($columna) . "'"
        );
        if (!$existe && !$db->execute("ALTER TABLE {$p}kpy_coupon ADD COLUMN {$columna} {$definicion}")) {
            return false;
        }
    }

    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);
    try {
        KpcReparto::rehacer((int) Configuration::get('PS_SHOP_DEFAULT'));
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 1.6.0, rehacer: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }

    return true;
}
