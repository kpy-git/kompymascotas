<?php
/**
 * Kompy · Qué etiqueta lleva cada producto del catálogo.
 *
 * Se ejecuta EN LA TIENDA, desde su raíz:
 *
 *   docker exec -i -w /var/www/html/kompy php85 php modules/kpycoupons/bin/etiquetas.php
 *   … modules/kpycoupons/bin/etiquetas.php --lista     (además, los 40 primeros de cada etiqueta)
 *
 * Sirve para dos cosas: comprobar que el cálculo no revienta en ningún producto (se recorre el
 * catálogo entero) y saber a cuántos productos llega cada promoción antes de anunciarla.
 *
 * Solo lee.
 */
require dirname(__DIR__, 3) . '/config/config.inc.php';

$conLista = in_array('--lista', array_slice($argv, 1), true);
// El % sale de los precios de verdad, y PrestaShop no los calcula en consola sin nadie detrás.
Context::getContext()->employee = new Employee((int) Db::getInstance()->getValue('SELECT MIN(id_employee) FROM ' . _DB_PREFIX_ . 'employee'));
$modulo = Module::getInstanceByName('kpycoupons');
if (!$modulo) {
    exit("No está instalado kpycoupons.\n");
}

$p = _DB_PREFIX_;
$idTienda = (int) Context::getContext()->shop->id;
$productos = Db::getInstance()->executeS(
    "SELECT p.id_product, pl.name, IFNULL(pas.id_product_attribute, 0) AS ipa
     FROM {$p}product p
     INNER JOIN {$p}product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = " . (int) Configuration::get('PS_LANG_DEFAULT') . "
     LEFT JOIN {$p}product_attribute pas ON pas.id_product = p.id_product AND pas.default_on = 1
     WHERE p.active = 1
     GROUP BY p.id_product
     ORDER BY p.id_product"
) ?: [];

$cuenta = [];
$ejemplos = [];
$errores = [];
foreach ($productos as $fila) {
    try {
        $etiqueta = KpcFlags::delProducto([
            'id_product' => (int) $fila['id_product'],
            'id_product_attribute' => (int) $fila['ipa'],
        ], $modulo);
    } catch (Throwable $e) {
        $errores[] = $fila['id_product'] . ': ' . $e->getMessage();
        continue;
    }
    $clave = $etiqueta === null ? '(sin etiqueta)' : $etiqueta['label'];
    $cuenta[$clave] = ($cuenta[$clave] ?? 0) + 1;
    if ($etiqueta !== null && count($ejemplos[$clave] ?? []) < 40) {
        $ejemplos[$clave][] = '#' . $fila['id_product'] . ' ' . mb_substr((string) $fila['name'], 0, 60);
    }
}

arsort($cuenta);
echo 'Catálogo: ', count($productos), " productos activos\n";
echo str_repeat('-', 60), "\n";
foreach ($cuenta as $clave => $n) {
    printf("%-24s %6d  %5.1f %%\n", $clave, $n, $productos ? $n * 100 / count($productos) : 0);
}

if ($conLista) {
    foreach ($ejemplos as $clave => $lista) {
        echo "\n$clave\n";
        foreach ($lista as $linea) {
            echo '  ', $linea, "\n";
        }
    }
}

echo "\n", $errores === [] ? "Sin errores.\n" : count($errores) . " errores:\n  " . implode("\n  ", array_slice($errores, 0, 20)) . "\n";
exit($errores === [] ? 0 : 1);
