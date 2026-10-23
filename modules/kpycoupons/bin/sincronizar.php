<?php
/**
 * Kompy · Rehace el reparto de las promociones, las reglas de carrito y las etiquetas.
 *
 * Se ejecuta EN LA TIENDA, desde su raíz:
 *
 *   docker exec -i -w /var/www/html/kompy php85 php modules/kpycoupons/bin/sincronizar.php
 *   … php modules/kpycoupons/bin/sincronizar.php --ver     (solo enseña lo guardado, no toca nada)
 *
 * Desde la 1.5.0 casi nunca hace falta: se rehace solo al guardar una promoción en la pantalla y
 * cuando empieza o acaba una (la primera visita de después). Queda para después de cargar
 * promociones con SQL (`catalog-ops/bin/promos-tienda.php`) y, si se quiere, en un cron diario
 * como red de seguridad:
 *
 *   1 0 * * *  docker exec -w /var/www/html/kompy php85 php modules/kpycoupons/bin/sincronizar.php
 *
 * Las promociones de «2ª unidad» no llevan regla fija: se calculan en cada cesta, y las reglas de
 * las cestas que ya no las necesitan se barren aquí y solas cada 6 horas (`KpcLimpieza`).
 */
require dirname(__DIR__, 3) . '/config/config.inc.php';

if (!Module::getInstanceByName('kpycoupons') instanceof Kpycoupons) {
    exit("No está instalado kpycoupons.\n");
}
$soloVer = in_array('--ver', array_slice($argv, 1), true);
$idTienda = (int) Configuration::get('PS_SHOP_DEFAULT');
Context::getContext()->shop = new Shop($idTienda);
// En consola no hay nadie detrás, y el cálculo de precios de PrestaShop (etiquetas) lo pide.
Context::getContext()->employee = new Employee((int) Db::getInstance()->getValue('SELECT MIN(id_employee) FROM ' . _DB_PREFIX_ . 'employee'));

$errores = 0;
if (!$soloVer) {
    try {
        KpcReparto::rehacer($idTienda);
    } catch (Throwable $e) {
        ++$errores;
        echo 'ERROR: ', $e->getMessage(), "\n";
    }
    // Cada cesta que activa la «2ª unidad» deja una regla suya; las que ya no hacen falta, fuera
    // (lo hace también la tienda sola cada 6 horas).
    $borradas = KpcLimpieza::barrer();
    if ($borradas) {
        echo 'Reglas de «2ª unidad» borradas: ', array_sum($borradas), "\n";
        foreach ($borradas as $motivo => $n) {
            echo '  ', $n, ' · ', KpcLimpieza::MOTIVOS[$motivo] ?? $motivo, "\n";
        }
        echo "\n";
    }
}

$filas = Db::getInstance()->executeS(
    'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_shop = ' . $idTienda . ' ORDER BY activo DESC, fecha_desde, codigo'
) ?: [];
if ($filas === []) {
    exit("No hay promociones.\n");
}
printf("%-24s %-15s %9s  %-11s %9s  %-9s  %s\n", 'CÓDIGO', 'TIPO', '%', 'ESTADO', 'ARTÍCULOS', 'REGLA', 'AVISO');
echo str_repeat('-', 110), "\n";
$ahora = date('Y-m-d H:i:s');
foreach ($filas as $f) {
    $estado = !$f['activo'] ? 'apagada'
        : (KpcReparto::enMarcha($f, $ahora) ? 'en marcha' : ((string) $f['fecha_desde'] > $ahora ? 'programada' : 'terminada'));
    $porArticulo = ($f['tipo'] ?? '') === 'special_price' && ($f['modo_precio'] ?? 'pct') !== 'pct';
    printf(
        "%-24s %-15s %9s  %-11s %9d  %-9s  %s\n",
        $f['codigo'],
        $f['tipo'],
        $porArticulo ? ($f['modo_precio'] === 'precio_articulo' ? 'precios' : '% por art') : KpcElegibles::pct((float) $f['porcentaje']) . ' %',
        $estado,
        (int) $f['formatos'],
        ['segunda_unidad' => 'por cesta', 'special_price' => 'en precio'][$f['tipo']] ?? ((int) $f['id_cart_rule'] ?: '-'),
        $f['aviso']
    );
}

$proximo = (int) Configuration::getGlobalValue(KpcReparto::PROXIMO);
echo "\nPróximo cambio: ", $proximo > 0 ? date('d/m/Y H:i', $proximo) : 'en la próxima visita', "\n";
echo $soloVer ? "Solo enseñado, no se ha tocado nada.\n" : "Listo.\n";
exit($errores ? 1 : 0);
