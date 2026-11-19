<?php
/**
 * 1.7.0 (Rafa, 16–18/11/2026): la cesta de un solo almacén.
 *
 *   - Cuando el reparto de Julián (`kpyorderdispatcher`, `hasMultiWarehouse()`) dice que la cesta implica
 *     más de un almacén, cada línea que no sale del almacén de referencia lleva su aviso, su (i) y el bloque
 *     «Cámbialo por…» con alternativas de ese almacén; un aviso al pie de la cesta; y lo mismo en la ventana
 *     «Añadido a tu cesta». Sin nombres de almacén y sin fechas.
 *   - Tablas nuevas `kpy_cross_sell_cesta` (lo que se enseña, se cambia o se deja) y `kpy_cross_sell_almacen`
 *     (la memoria de lo que responde el reparto de Julián por producto y formato, unas horas).
 *   - Cuatro hooks de la cesta y la hoja/guion propios en todas las páginas (la ventana sale en cualquiera).
 *   - Ajustes nuevos en la pantalla del módulo (encendido, bandas de precio, tarjetas, marcas).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_7_0($modulo): bool
{
    require_once dirname(__DIR__) . '/classes/KcsCestaLog.php';
    require_once dirname(__DIR__) . '/classes/KcsAlmacenes.php';
    require_once dirname(__DIR__) . '/classes/KcsCesta.php';

    $ok = KcsCestaLog::crearTabla() && KcsAlmacenes::crearTabla();
    foreach (['displayCartExtraProductInfo', 'displayCartExtraProductActions', 'displayShoppingCartFooter', 'displayCartModalFooter'] as $hook) {
        if (!$modulo->isRegisteredInHook($hook)) {
            $ok = $modulo->registerHook($hook) && $ok;
        }
    }
    foreach (KcsCesta::AJUSTES as $clave => $valor) {
        if (Configuration::get($clave) === false) {
            Configuration::updateValue($clave, $valor);
        }
    }
    try {
        $modulo->colocarHooks();
    } catch (Throwable $e) {
        PrestaShopLogger::addLog('kpycrosssell 1.7.0, posiciones: ' . $e->getMessage(), 2, null, 'Module', 0, true);
    }

    return $ok;
}
