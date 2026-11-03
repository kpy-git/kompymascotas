<?php
/**
 * Las cuentas del Special Price, sin tienda:
 *   php tests/especial.php
 */
require __DIR__ . '/../classes/KpcEspecial.php';

$fallos = 0;
function igual(string $que, $esperado, $real): void
{
    global $fallos;
    if ($esperado === $real) {
        echo "  ok  $que\n";

        return;
    }
    ++$fallos;
    echo "  MAL $que\n      esperado: " . json_encode($esperado, JSON_UNESCAPED_UNICODE) . "\n      real:     " . json_encode($real, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "El % va encima del de PriceShape (como la carga de Julián)\n";
igual('Royal Canin, agosto: 14,4964 % + 12 % → 24,7568 %', 0.247568, KpcEspecial::reduccion(0.144964, 12));
igual('sin PriceShape, el % tal cual', 0.13, KpcEspecial::reduccion(0.0, 13));
igual('Giant Adult: 71,87 € de hoy con 13 % → 62,53 €', 62.53, round(80.27 * (1 - KpcEspecial::reduccion(1 - 71.87 / 80.27, 13)), 2));

echo "Qué se quiere para cada artículo\n";
$general = ['modo_precio' => 'pct', 'porcentaje' => 13];
igual('el mismo % para todos', ['pct' => 13.0], KpcEspecial::meta($general, null));
igual('en «mismo %», el valor de la lista no cuenta', ['pct' => 13.0], KpcEspecial::meta($general, ['v' => 20.0, 'de' => null]));
$porArticulo = ['modo_precio' => 'pct_articulo', 'porcentaje' => 10];
igual('cada uno con su %', ['pct' => 18.0], KpcEspecial::meta($porArticulo, ['v' => 18.0, 'de' => null]));
igual('el que no trae el suyo, el general', ['pct' => 10.0], KpcEspecial::meta($porArticulo, null));
igual('sin general, fuera', null, KpcEspecial::meta(['modo_precio' => 'pct_articulo', 'porcentaje' => 0], null));
$porPrecio = ['modo_precio' => 'precio_articulo', 'porcentaje' => 0];
igual('su precio final', ['precio' => 42.5], KpcEspecial::meta($porPrecio, ['v' => 42.5, 'de' => null]));
igual('un pack, el precio de su saco para sacar el descuento', ['precio_de' => 42.5, 'de' => 90684], KpcEspecial::meta($porPrecio, ['v' => 42.5, 'de' => 90684]));

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
