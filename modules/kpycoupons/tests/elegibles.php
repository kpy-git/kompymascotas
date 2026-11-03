<?php
/**
 * Prueba la regla de negocio de los cupones sin tienda:
 *   php tests/elegibles.php
 */
require __DIR__ . '/../classes/KpcFormatos.php';
require __DIR__ . '/../classes/KpcElegibles.php';

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

$producto = ['id_product' => 7214, 'id_manufacturer' => 178, 'categorias' => [2, 1003, 2228]];
$combinaciones = [
    ['ipa' => 92895, 'nombre' => 'Saco de 3 Kg', 'especial' => false],
    ['ipa' => 90684, 'nombre' => 'Saco de 15 Kg', 'especial' => false],
    ['ipa' => 90685, 'nombre' => 'Pack 2 x Saco de 15 kg', 'especial' => true],
];

echo "A qué formatos llega\n";
$porMarca = ['excluir_especial' => true, 'ambitos' => [['tipo' => 'marca', 'id' => 178]]];
igual('marca entera, menos el que tiene Special Price', [92895, 90684], KpcElegibles::combinaciones($porMarca, $producto, $combinaciones));

$sinExcluir = ['excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178]]];
igual('si no respeta el Special Price, entran todos', [92895, 90684, 90685], KpcElegibles::combinaciones($sinExcluir, $producto, $combinaciones));

$porFormato = ['excluir_especial' => true, 'ambitos' => [['tipo' => 'combinacion', 'id' => 90684]]];
igual('solo un formato', [90684], KpcElegibles::combinaciones($porFormato, $producto, $combinaciones));

echo "Packs y 2ª unidad\n";
$conPack = ['excluir_especial' => false, 'incluir_packs' => true, 'ambitos' => [['tipo' => 'combinacion', 'id' => 90684]]];
igual('el pack entra solo con su formato', [90684, 90685], KpcElegibles::combinaciones($conPack, $producto, $combinaciones));
$sinPack = ['excluir_especial' => false, 'incluir_packs' => false, 'ambitos' => [['tipo' => 'combinacion', 'id' => 90684]]];
igual('si se apaga, no entra', [90684], KpcElegibles::combinaciones($sinPack, $producto, $combinaciones));
$segunda = ['tipo' => 'segunda_unidad', 'excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178]]];
igual('la 2ª unidad deja los packs fuera', [92895, 90684], KpcElegibles::combinaciones($segunda, $producto, $combinaciones));

$otraMarca = ['excluir_especial' => true, 'ambitos' => [['tipo' => 'marca', 'id' => 35]]];
igual('otra marca: no pinta nada', [], KpcElegibles::combinaciones($otraMarca, $producto, $combinaciones));

$porCategoria = ['excluir_especial' => true, 'ambitos' => [['tipo' => 'categoria', 'id' => 2228]]];
igual('por categoría', [92895, 90684], KpcElegibles::combinaciones($porCategoria, $producto, $combinaciones));

igual('sin ámbitos no vale para nada', [], KpcElegibles::combinaciones(['ambitos' => []], $producto, $combinaciones));

echo "Los filtros se cruzan; la lista suma; lo excluido se cae\n";
$gato = $producto + ['caracteristicas' => [1001, 1005]];          // Gato, Húmeda
$rasgos = [1000 => 14, 1001 => 14, 1004 => 15, 1005 => 15];         // Mascota: Perro, Gato; Tipo: Seca, Húmeda
$marcaYCategoria = ['excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178], ['tipo' => 'categoria', 'id' => 9999]]];
igual('marca Y categoría: si falta la categoría, no', [], KpcElegibles::combinaciones($marcaYCategoria, $producto, $combinaciones));
$marcaYGato = ['excluir_especial' => false, 'rasgos' => $rasgos, 'ambitos' => [['tipo' => 'marca', 'id' => 178], ['tipo' => 'caracteristica', 'id' => 1001]]];
igual('marca + Gato: el de gato entra', [92895, 90684, 90685], KpcElegibles::combinaciones($marcaYGato, $gato, $combinaciones));
igual('marca + Gato: el de perro no', [], KpcElegibles::combinaciones($marcaYGato, $producto + ['caracteristicas' => [1000, 1004]], $combinaciones));
$gatoSecaOHumeda = ['excluir_especial' => false, 'rasgos' => $rasgos, 'ambitos' => [
    ['tipo' => 'caracteristica', 'id' => 1001], ['tipo' => 'caracteristica', 'id' => 1004], ['tipo' => 'caracteristica', 'id' => 1005],
]];
igual('Gato y (Seca o Húmeda): uno de cada grupo', [92895, 90684, 90685], KpcElegibles::combinaciones($gatoSecaOHumeda, $gato, $combinaciones));
$listaSuma = ['excluir_especial' => false, 'incluir_packs' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 35], ['tipo' => 'combinacion', 'id' => 92895]]];
igual('la lista suma aunque la marca no sea', [92895], KpcElegibles::combinaciones($listaSuma, $producto, $combinaciones));
$productoEntero = ['excluir_especial' => false, 'ambitos' => [['tipo' => 'producto', 'id' => 7214]]];
igual('un producto de la lista: todos sus formatos', [92895, 90684, 90685], KpcElegibles::combinaciones($productoEntero, $producto, $combinaciones));
$sinUnFormato = ['excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178], ['tipo' => 'excluir_combinacion', 'id' => 90684]]];
igual('fuera un formato: su pack sigue (no es el mismo artículo)', [92895, 90685], KpcElegibles::combinaciones($sinUnFormato, $producto, $combinaciones));
$fueraProducto = ['excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178], ['tipo' => 'excluir_producto', 'id' => 7214]]];
igual('fuera el producto entero', [], KpcElegibles::combinaciones($fueraProducto, $producto, $combinaciones));
$accesorio = ['id_product' => 3647, 'id_manufacturer' => 90, 'categorias' => []];
igual('un producto sin combinaciones entra como 0', [0], KpcElegibles::combinaciones(['ambitos' => [['tipo' => 'marca', 'id' => 90]]], $accesorio, [['ipa' => 0, 'nombre' => '', 'especial' => false]]));

echo "OUTLET\n";
$conOutlet = array_merge($combinaciones, [['ipa' => 95001, 'nombre' => 'Saco de 12 Kg - OUTLET', 'especial' => false]]);
igual('los OUTLET se quedan fuera', [92895, 90684, 90685], KpcElegibles::combinaciones(['excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178]]], $producto, $conOutlet));
igual('salvo que se pida', [92895, 90684, 90685, 95001], KpcElegibles::combinaciones(['excluir_especial' => false, 'sin_outlet' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178]]], $producto, $conOutlet));
igual('o que el OUTLET venga en la lista con su propio formato', [92895, 90684, 90685, 95001], KpcElegibles::combinaciones(['excluir_especial' => false, 'ambitos' => [['tipo' => 'marca', 'id' => 178], ['tipo' => 'combinacion', 'id' => 95001]]], $producto, $conOutlet));
igual('«OUTLET» es palabra, no trozo', false, KpcElegibles::esOutlet('Saco de 12 Kg Outletero'));

echo "Quién manda\n";
$cupon12 = ['id_kpy_coupon' => 2, 'tipo' => 'cupon', 'porcentaje' => 12];
$segunda20 = ['id_kpy_coupon' => 1, 'tipo' => 'segunda_unidad', 'porcentaje' => 20];
igual('cupón 12 % manda sobre 2ª unidad 20 % (que es un 10 %)', -1, KpcElegibles::manda($cupon12, $segunda20));
igual('a igualdad, la más antigua', 1, KpcElegibles::manda(['id_kpy_coupon' => 5, 'porcentaje' => 10], ['id_kpy_coupon' => 3, 'porcentaje' => 10]));

echo "Cuándo entra ya aplicado\n";
$deCanal = ['auto_web' => false, 'auto_canal' => 'shopping,email'];
igual('visita normal: no', false, KpcElegibles::aplicadoDeEntrada($deCanal, ''));
igual('desde Shopping: sí', true, KpcElegibles::aplicadoDeEntrada($deCanal, 'shopping'));
igual('aplicado en la web: siempre', true, KpcElegibles::aplicadoDeEntrada(['auto_web' => true], ''));

echo "Dónde se ve (3.1.0)\n";
$soloGoogle = ['en_ficha' => 1, 'ver_canal' => 'shopping', 'auto_web' => 0];
igual('solo Google: navegando normal no lo ve', false, KpcElegibles::seVe($soloGoogle, ''));
igual('solo Google: desde Google sí', true, KpcElegibles::seVe($soloGoogle, 'shopping'));
igual('solo Google: desde un email no', false, KpcElegibles::seVe($soloGoogle, 'email'));
igual('en toda la tienda: cualquiera', true, KpcElegibles::seVe(['en_ficha' => 1, 'ver_canal' => ''], ''));
igual('sin la columna (promoción de antes): como siempre', true, KpcElegibles::seVe(['en_ficha' => 1], ''));
igual('oculto: nadie, ni desde Google', false, KpcElegibles::seVe(['en_ficha' => 0, 'ver_canal' => ''], 'shopping'));
igual('para todos manda: se ve aunque esté oculto', true, KpcElegibles::seVe(['en_ficha' => 0, 'auto_web' => 1], ''));
igual('dos canales, con espacios', true, KpcElegibles::seVe(['en_ficha' => 1, 'ver_canal' => 'shopping, email'], 'email'));
igual('dos canales: desde redes no', false, KpcElegibles::seVe(['en_ficha' => 1, 'ver_canal' => 'shopping, email'], 'social'));

echo "De dónde viene la visita\n";
igual('Google Shopping por utm', 'shopping', KpcElegibles::canal(['utm_source' => 'google', 'utm_medium' => 'cpc']));
igual('Google Shopping por srsltid', 'shopping', KpcElegibles::canal(['srsltid' => 'abc']));
igual('email', 'email', KpcElegibles::canal(['utm_medium' => 'email']));
igual('Instagram', 'social', KpcElegibles::canal(['utm_source' => 'instagram']));
igual('anuncio de Meta', 'social', KpcElegibles::canal(['fbclid' => 'x']));
igual('visita normal', '', KpcElegibles::canal([]));
// Revisión del 26/10/2026: la fuente manda sobre el medio, y todo el etiquetado automático de Google cuenta.
igual('anuncio de Meta con utm_medium=paid: redes, no Google', 'social', KpcElegibles::canal(['utm_source' => 'facebook', 'utm_medium' => 'paid']));
igual('Instagram con cpc: redes', 'social', KpcElegibles::canal(['utm_source' => 'instagram', 'utm_medium' => 'cpc']));
igual('un anuncio de Bing o de ChatGPT con cpc: ningún canal nuestro', ['', ''], [KpcElegibles::canal(['utm_source' => 'bing', 'utm_medium' => 'cpc']), KpcElegibles::canal(['utm_source' => 'chatgpt', 'utm_medium' => 'cpc'])]);
igual('solo el medio cpc: Google, como siempre', 'shopping', KpcElegibles::canal(['utm_medium' => 'cpc']));
igual('Google Ads desde iOS (gbraid/wbraid) y gad_source', ['shopping', 'shopping', 'shopping'], [KpcElegibles::canal(['gbraid' => 'x']), KpcElegibles::canal(['wbraid' => 'x']), KpcElegibles::canal(['gad_source' => '1'])]);
igual('google_ads con cpc', 'shopping', KpcElegibles::canal(['utm_source' => 'google_ads', 'utm_medium' => 'cpc']));
igual('un email con fbclid de propina sigue siendo email', 'email', KpcElegibles::canal(['utm_medium' => 'email', 'fbclid' => 'x']));

echo "Cuentas\n";
igual('descuento de 50,99 € al 10 %', 5.1, KpcElegibles::descuento(50.99, 10));
igual('descuento de 94,99 € al 10 %', 9.5, KpcElegibles::descuento(94.99, 10));

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
