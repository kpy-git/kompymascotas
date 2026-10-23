<?php
/**
 * Borra las etiquetas que kpycoupons (hasta la 2.4) escribió como filas en las tablas de
 * `kpyproductflags` (`kpy_flag`, `kpy_flag_lang`, `kpy_product_flag`). Desde la 3.0.0 se calculan
 * por hook y no hace falta ninguna fila; y como son tablas de otro módulo, el `upgrade` no las toca:
 * las borra este guion (o el SQL equivalente del paquete, `deshacer/limpiar-flags.sql`).
 *
 * Desde /var/www/html/kompy, dentro del contenedor:
 *   cat modules/kpycoupons/bin/limpiar-flags.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php -- --ensayo
 *   cat modules/kpycoupons/bin/limpiar-flags.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php -- --borrar
 *
 * Solo toca los tipos nuestros (`Oferta −%`, `Código −%`, `2ª ud. −%`, `+ %`, con guion U+2212 y
 * normal); las etiquetas manuales de Julián («Bajada de Precio», «kg Gratis», «Top Ventas»…) se
 * quedan. En una tienda sin esas tablas no hace nada.
 */
$borrar = in_array('--borrar', $argv ?? [], true);
require_once 'autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->usePutenv(false)->loadEnv(getcwd() . '/.env');
require 'config/config.inc.php';
$kernel = new FrontKernel('dev', false);
$kernel->boot();
Module::getInstanceByName('kpycoupons');

$antes = KpcFlags::limpiarTablasAjenas(true);
echo ($borrar ? 'Borrando' : 'Ensayo (sin borrar)') . ': filas nuestras en las tablas de kpyproductflags: ' . json_encode($antes) . "\n";
if ($borrar) {
    $hecho = KpcFlags::limpiarTablasAjenas(false);
    echo 'Borradas: ' . json_encode($hecho) . "\n";
    echo 'Quedan: ' . json_encode(KpcFlags::limpiarTablasAjenas(true)) . "\n";
}
