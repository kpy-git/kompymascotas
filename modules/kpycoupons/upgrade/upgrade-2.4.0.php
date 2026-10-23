<?php
/**
 * 2.4.0 (19/10/2026): el cupo **por artículo** (Rafa: «un listado de EAN, referencias, con las
 * cantidades; multimarca; todos al mismo sitio») y la etiqueta «Outlet».
 *   - `kpy_coupon.cupo_lista`: las unidades van en la lista pegada, artículo a artículo;
 *   - `kpy_coupon_scope.unidades` (las de cada artículo de la lista) y `kpy_coupon_scope.agotado`
 *     (cuándo vendió las suyas: desde entonces el reparto lo deja fuera);
 *   - el reparto otra vez.
 * Va con `kpypromolanding` 1.3.0 (el cajón «Outlet y liquidación» de la landing) y
 * `kpyproductbuy` 1.5.1 (la etiqueta «Outlet» en la ficha).
 * Se puede ejecutar más de una vez: solo añade lo que falta.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_4_0(Module $module): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    $ok = true;

    if ($module instanceof Kpycoupons) {
        $ok = $module->tablas();
    }
    $hay = static function (string $tabla, string $columna) use ($db, $p): bool {
        return (bool) $db->getValue(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = '" . pSQL($p . $tabla) . "' AND COLUMN_NAME = '" . pSQL($columna) . "'"
        );
    };
    foreach ([
        ['kpy_coupon', 'cupo_lista', 'TINYINT(1) NOT NULL DEFAULT 0'],
        ['kpy_coupon_scope', 'unidades', 'INT UNSIGNED NULL'],
        ['kpy_coupon_scope', 'agotado', 'DATETIME NULL'],
    ] as [$tabla, $columna, $tipo]) {
        if (!$hay($tabla, $columna)) {
            $ok = $ok && (bool) $db->execute("ALTER TABLE {$p}{$tabla} ADD COLUMN {$columna} {$tipo}");
        }
    }

    try {
        KpcReparto::rehacer((int) Configuration::get('PS_SHOP_DEFAULT'));
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 2.4.0, al rehacer el reparto: ' . $e->getMessage(), 2, null, 'KpcReparto', 0, true);
    }

    return $ok;
}
