<?php
/**
 * 1.5.0: la pantalla de promociones nueva y el reparto guardado.
 *
 *   - `kpy_coupon`: el tipo «Special Price» y cómo se rebaja (`modo_precio`), «fuera OUTLET», la
 *     lista de artículos tal como se pegó (EAN, referencias, ids…), lo excluido, «anunciar en
 *     Google» y el resumen que deja el reparto (a cuántos artículos llega, qué suelta).
 *   - `kpy_coupon_scope`: características (Mascota, Tipo…), excluidos y el valor de cada artículo
 *     de la lista (su % o su precio, para los Special Price por artículo).
 *   - `kpy_coupon_item` (nueva): qué artículo le toca a cada promoción, calculado una vez.
 *   - `kpy_coupon_precio` (nueva): los Special Price que pone cada promoción, para quitarlos sin
 *     tocar los de nadie.
 *   - Hook `actionFrontControllerInitAfter`: el enlace `?kpc=CODIGO`, el canal de la visita y el
 *     código escrito en la cesta.
 *   - Se rehace todo: reparto, reglas de carrito (ahora también con productos sin combinaciones)
 *     y etiquetas.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_0($modulo): bool
{
    $db = Db::getInstance();
    $p = _DB_PREFIX_;

    $columnas = [
        'kpy_coupon' => [
            'modo_precio' => "VARCHAR(16) NOT NULL DEFAULT 'pct' AFTER tipo",
            'sin_outlet' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER incluir_packs',
            'en_merchant' => 'TINYINT(1) NOT NULL DEFAULT 1 AFTER bloquear_priceshape',
            'lista' => 'MEDIUMTEXT NULL AFTER merchant_titulo',
            'lista_tipo' => "VARCHAR(16) NOT NULL DEFAULT 'auto' AFTER lista",
            'excluir' => 'MEDIUMTEXT NULL AFTER lista_tipo',
            'formatos' => 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER excluir',
            'aviso' => "VARCHAR(255) NOT NULL DEFAULT '' AFTER formatos",
        ],
        'kpy_coupon_scope' => [
            'valor' => 'DECIMAL(20,6) NULL AFTER id_ref',
        ],
    ];
    foreach ($columnas as $tabla => $suyas) {
        foreach ($suyas as $columna => $definicion) {
            $existe = (int) $db->getValue(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = '{$p}{$tabla}' AND COLUMN_NAME = '" . pSQL($columna) . "'"
            );
            if (!$existe && !$db->execute("ALTER TABLE {$p}{$tabla} ADD COLUMN {$columna} {$definicion}")) {
                return false;
            }
        }
    }

    if (!$db->execute("ALTER TABLE {$p}kpy_coupon MODIFY tipo ENUM('cupon','segunda_unidad','special_price') NOT NULL DEFAULT 'cupon'")
        || !$db->execute(
            "ALTER TABLE {$p}kpy_coupon_scope MODIFY tipo
             ENUM('marca','categoria','caracteristica','producto','combinacion','excluir_producto','excluir_combinacion') NOT NULL"
        )) {
        return false;
    }

    // La tabla nueva (las demás ya están: CREATE TABLE IF NOT EXISTS no las toca).
    if (!$modulo->tablas()) {
        return false;
    }
    if (!$modulo->isRegisteredInHook('actionFrontControllerInitAfter')
        && !$modulo->registerHook('actionFrontControllerInitAfter')) {
        return false;
    }
    $modulo->pestana();

    // Todo rehecho de una vez. Si fallara, la primera visita lo vuelve a intentar.
    Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);
    try {
        KpcReparto::rehacer((int) Configuration::get('PS_SHOP_DEFAULT'));
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycoupons 1.5.0, rehacer: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }

    return true;
}
