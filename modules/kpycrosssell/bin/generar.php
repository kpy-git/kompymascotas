<?php
/**
 * Kompy · Calcula y guarda las sugerencias de «Caprichitos».
 *
 * Se ejecuta EN LA TIENDA, desde su raíz:
 *
 *   docker exec -i -w /var/www/html/kompy php85 php modules/kpycrosssell/bin/generar.php
 *   … generar.php --nuevos          solo los productos que todavía no tienen ninguna
 *   … generar.php --producto=7214   solo uno (y se ve qué le ha salido)
 *   … generar.php --forzar          también los que se han tocado a mano en el back office
 *
 * Deja tres sugerencias por producto en los **productos relacionados** de PrestaShop (`accessory`)
 * y su orden en `kpy_cross_sell_auto`. La ficha solo las lee: aquí es donde se hace todo el trabajo,
 * una vez, y no en cada visita.
 *
 * Un producto cuyos relacionados se hayan cambiado a mano en el back office se **respeta**: no se
 * vuelve a calcular salvo con `--forzar`.
 *
 * Se puede pasar cada noche (con `--nuevos` basta para los que entran por los paquetes de catálogo)
 * y de vez en cuando entero, para que las sugerencias mejoren con el stock y las altas nuevas.
 */
require dirname(__DIR__, 3) . '/config/config.inc.php';

$modulo = Module::getInstanceByName('kpycrosssell');
if (!$modulo) {
    exit("No está instalado kpycrosssell.\n");
}
// El precio de cada producto lo calcula PrestaShop, y en consola necesita a alguien detrás.
Context::getContext()->employee = new Employee((int) Db::getInstance()->getValue('SELECT MIN(id_employee) FROM ' . _DB_PREFIX_ . 'employee'));

$soloNuevos = false;
$forzar = false;
$uno = 0;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--nuevos') {
        $soloNuevos = true;
    } elseif ($arg === '--forzar') {
        $forzar = true;
    } elseif (strpos($arg, '--producto=') === 0) {
        $uno = (int) substr($arg, 11);
    }
}

$p = _DB_PREFIX_;
$donde = 'pr.active = 1';
if ($uno > 0) {
    $donde .= ' AND pr.id_product = ' . $uno;
} elseif ($soloNuevos) {
    $donde .= " AND NOT EXISTS (SELECT 1 FROM {$p}accessory a WHERE a.id_product_1 = pr.id_product)";
}
$ids = array_map('intval', array_column(Db::getInstance()->executeS(
    "SELECT pr.id_product FROM {$p}product pr WHERE {$donde} ORDER BY pr.id_product"
) ?: [], 'id_product'));

$ajustes = Kpycrosssell::comoBuscar();
$cuenta = [KcsGuardadas::HECHO => 0, KcsGuardadas::RESPETADO => 0, KcsGuardadas::SIN_NADA => 0];
$errores = [];
$inicio = microtime(true);

foreach ($ids as $i => $id) {
    try {
        $estado = KcsGuardadas::generar($id, $ajustes, $forzar);
        ++$cuenta[$estado];
    } catch (Throwable $e) {
        $errores[] = $id . ': ' . $e->getMessage();
    }
    if (($i + 1) % 250 === 0) {
        printf("  … %d de %d\n", $i + 1, count($ids));
    }
}

printf("Productos mirados: %d en %.1f s\n", count($ids), microtime(true) - $inicio);
printf("  con sus sugerencias:        %d\n", $cuenta[KcsGuardadas::HECHO]);
printf("  tocados a mano (se dejan):  %d\n", $cuenta[KcsGuardadas::RESPETADO]);
printf("  sin nada que ofrecer:       %d\n", $cuenta[KcsGuardadas::SIN_NADA]);

if ($uno > 0) {
    echo "\nLo que le ha salido a #{$uno}:\n";
    foreach (KcsGuardadas::leer($uno) as $i => $s) {
        $nombre = (string) Db::getInstance()->getValue(
            "SELECT name FROM {$p}product_lang WHERE id_product = " . (int) $s['id_product'] . ' AND id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT')
        );
        printf("  %d.ª  #%d  %-60s  (%s)\n", $i + 1, $s['id_product'], mb_substr($nombre, 0, 60), $s['origen']);
    }
}

echo $errores === [] ? "\nSin errores.\n" : "\n" . count($errores) . " errores:\n  " . implode("\n  ", array_slice($errores, 0, 20)) . "\n";
exit($errores === [] ? 0 : 1);
