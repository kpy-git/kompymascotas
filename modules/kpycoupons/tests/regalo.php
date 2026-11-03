<?php
/**
 * El regalo con la compra, sin tienda: qué formato pequeño toca, qué regala cada artículo y qué
 * regalos tiene que haber en una cesta en todas las direcciones (el cliente añade, sube, baja,
 * quita el saco o el regalo, compra unidades sueltas del regalo, dos sacos, packs, uno por pedido,
 * tope de campaña, sin stock con y sin reserva), siempre con la versión con regalo elegida por el
 * cliente (sin elegirla, nada: la tarjeta «12 kg» de siempre no regala). Y los textos de la tarjeta.
 *   php tests/regalo.php
 */
require __DIR__ . '/../classes/KpcFormatos.php';
require __DIR__ . '/../classes/KpcAlcance.php';
require __DIR__ . '/../classes/KpcRegalo.php';

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

// Boske Adult Grain Free Salmón (8299), como lo ve `KpcAlcance::articulos()` (sin los apagados).
$salmon = [
    92467 => ['nombre' => 'Saco de 12 Kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
    92468 => ['nombre' => 'Saco de 3 Kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
    92469 => ['nombre' => 'Pack 2 x Saco de 12 Kg', 'nivel' => 2, 'base' => 92467, 'apagado' => false],
    92618 => ['nombre' => 'Saco de 12 kg + 3 kg GRATIS', 'nivel' => 1, 'base' => null, 'apagado' => false],
    92619 => ['nombre' => 'Pack 2 x Saco de 12 kg + 3 kg GRATIS', 'nivel' => 2, 'base' => 92618, 'apagado' => false],
    99001 => ['nombre' => 'Saco de 1 Kg - OUTLET', 'nivel' => 1, 'base' => null, 'apagado' => false],
];
$gato = [
    94015 => ['nombre' => 'Saco de 2.5 Kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
    94016 => ['nombre' => 'Saco de 10 Kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
    94017 => ['nombre' => 'Pack 2 x saco de 10 kg', 'nivel' => 2, 'base' => 94016, 'apagado' => false],
    94641 => ['nombre' => 'Saco de 10kg + 2.5 kg Gratis', 'nivel' => 1, 'base' => null, 'apagado' => false],
];
$mini = [
    93380 => ['nombre' => 'Saco de 2 Kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
    93381 => ['nombre' => 'Saco de 8 Kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
    93415 => ['nombre' => 'Saco de 8kg + 2kg Gratis', 'nivel' => 1, 'base' => null, 'apagado' => false],
    94642 => ['nombre' => 'Saco de 8kg + 4kg', 'nivel' => 1, 'base' => null, 'apagado' => false],
];
$latas = [
    92790 => ['nombre' => 'Lata de 400 gr', 'nivel' => 1, 'base' => null, 'apagado' => false],
    92791 => ['nombre' => 'Pack 6 x Lata de 400 gr', 'nivel' => 6, 'base' => 92790, 'apagado' => false],
];

echo "El formato pequeño de la misma ficha\n";
igual('el 12 kg regala el 3 kg', 92468, KpcRegalo::pequenoDe(92467, $salmon));
igual('el 3 kg no tiene nada más pequeño (el OUTLET de 1 kg no cuenta)', null, KpcRegalo::pequenoDe(92468, $salmon));
igual('el pack de 2 × 12 kg también apunta al 3 kg', 92468, KpcRegalo::pequenoDe(92469, $salmon));
igual('el saco «+ 3 kg gratis» del fabricante nunca es el regalo', 92468, KpcRegalo::pequenoDe(92618, $salmon));
igual('gato: el 10 kg regala el 2,5 kg', 94015, KpcRegalo::pequenoDe(94016, $gato));
igual('mini: el 8 kg regala el 2 kg, no el «8 + 4» ni el «8 + 2 gratis»', 93380, KpcRegalo::pequenoDe(93381, $mini));
igual('latas: la lata suelta no tiene nada más pequeño (el pack de 6 es más grande)', null, KpcRegalo::pequenoDe(92790, $latas));
igual('una lata no es el pequeño de un saco (otro envase)', 92468, KpcRegalo::pequenoDe(92467, $salmon + $latas));
igual('un formato apagado no se regala', null, KpcRegalo::pequenoDe(92467, [92467 => $salmon[92467], 92468 => array_merge($salmon[92468], ['apagado' => true])]));
igual('sin peso en el nombre no hay «más pequeño»', null, KpcRegalo::pequenoDe(1, [1 => ['nombre' => 'Talla M'], 2 => ['nombre' => 'Talla S']]));
igual('un formato que no está en la ficha', null, KpcRegalo::pequenoDe(123, $salmon));

echo "\nLeer y escribir un artículo\n";
igual('«8299-92468»', ['id' => 8299, 'ipa' => 92468], KpcRegalo::leerArticulo('8299-92468'));
igual('«7123» es el producto sin formato', ['id' => 7123, 'ipa' => 0], KpcRegalo::leerArticulo(' 7123 '));
igual('como array', ['id' => 8299, 'ipa' => 92468], KpcRegalo::leerArticulo(['id' => 8299, 'ipa' => 92468]));
igual('vacío', null, KpcRegalo::leerArticulo(''));
igual('raro', null, KpcRegalo::leerArticulo('boske'));
igual('escribir', '8299-92468', KpcRegalo::escribirArticulo(['id' => 8299, 'ipa' => 92468]));
igual('escribir nada', '', KpcRegalo::escribirArticulo(null));

echo "\nQué regala cada artículo (las parejas)\n";
$formatos = [8299 => $salmon, 9208 => $gato];
$items = [92467 => 8299, 92469 => 8299, 94016 => 9208, 92468 => 8299];
$niveles = [92467 => 1, 92469 => 2, 94016 => 1, 92468 => 1];
$p = KpcRegalo::parejas(['regalo_modo' => 'pequeno'], $items, $niveles, $formatos);
igual('12 kg → 3 kg de su ficha', ['id' => 8299, 'ipa' => 92468], $p[92467]['r']);
igual('un regalo por unidad', 1, $p[92467]['n']);
igual('el pack de 2 regala dos', 2, $p[92469]['n']);
igual('gato 10 kg → 2,5 kg', ['id' => 9208, 'ipa' => 94015], $p[94016]['r']);
igual('el 3 kg no tiene pequeño: sin regalo', null, $p[92468]['r']);
igual('y lo dice', 'sin_pequeno', $p[92468]['aviso']);
igual('origen', 'pequeno', $p[92467]['origen']);

$conReserva = KpcRegalo::parejas(['regalo_modo' => 'pequeno', 'regalo_reserva' => '8473-92791'], $items, $niveles, $formatos);
igual('con reserva: el 3 kg regala la reserva', ['id' => 8473, 'ipa' => 92791], $conReserva[92468]['r']);
igual('y queda dicho que es la reserva', 'reserva', $conReserva[92468]['origen']);
igual('el 12 kg sigue con su pequeño y la reserva detrás', ['id' => 8473, 'ipa' => 92791], $conReserva[92467]['s']);

$mismo = KpcRegalo::parejas(['regalo_modo' => 'mismo', 'regalo_mismo' => '8473-92791', 'regalo_reserva' => '8473-92790'], $items, $niveles, $formatos);
igual('el mismo para todos', ['id' => 8473, 'ipa' => 92791], $mismo[92467]['r']);
igual('también para el que no tiene pequeño', ['id' => 8473, 'ipa' => 92791], $mismo[92468]['r']);
igual('con su reserva', ['id' => 8473, 'ipa' => 92790], $mismo[94016]['s']);
$mismoQueCompra = KpcRegalo::parejas(['regalo_modo' => 'mismo', 'regalo_mismo' => '8299-92467'], [92467 => 8299], [92467 => 1], $formatos);
igual('regalar lo mismo que se compra se avisa', 'mismo_articulo', $mismoQueCompra[92467]['aviso']);
$reservaIgual = KpcRegalo::parejas(['regalo_modo' => 'mismo', 'regalo_mismo' => '8473-92791', 'regalo_reserva' => '8473-92791'], [92467 => 8299], [], $formatos);
igual('una reserva igual al regalo no sirve de nada', null, $reservaIgual[92467]['s']);

$aMano = KpcRegalo::parejas(['regalo_modo' => 'pequeno', 'regalo_parejas' => json_encode([92467 => ['r' => '7777-0']])], $items, $niveles, $formatos);
igual('la pareja a mano manda', ['id' => 7777, 'ipa' => 0], $aMano[92467]['r']);
igual('y se sabe que es a mano', 'mano', $aMano[92467]['origen']);
igual('las demás siguen con su pequeño', ['id' => 8299, 'ipa' => 92468], $aMano[92469]['r']);
igual('sin regalo elegido en modo «mismo»', 'sin_regalo', KpcRegalo::parejas(['regalo_modo' => 'mismo'], [92467 => 8299], [], $formatos)[92467]['aviso']);

echo "\nLa cesta, en todas las direcciones\n";
// Dos promociones: A (id 10) regala el 3 kg con el 12 kg (y dos con el pack); B (id 11) regala una
// caja de latas con el 10 kg de gato, uno por pedido y con tope 10.
$parejaA = ['id_kpy_coupon' => 10, 'clave' => 92467, 'id_product' => 8299, 'n' => 1, 'r' => ['id' => 8299, 'ipa' => 92468], 's' => ['id' => 8473, 'ipa' => 92791], 'origen' => 'pequeno', 'por' => 'unidad', 'tope' => 0, 'hasta' => '', 'nombre' => 'Boske 12 + 3', 'codigo' => 'REGALO-BOSKE'];
$parejaPack = $parejaA + [];
$parejaPack['clave'] = 92469;
$parejaPack['n'] = 2;
$parejaB = ['id_kpy_coupon' => 11, 'clave' => 94016, 'id_product' => 9208, 'n' => 1, 'r' => ['id' => 8473, 'ipa' => 92791], 's' => null, 'origen' => 'mismo', 'por' => 'pedido', 'tope' => 10, 'hasta' => '', 'nombre' => 'Latas de regalo', 'codigo' => 'REGALO-LATAS'];
$parejas = [92467 => [$parejaA], 92469 => [$parejaPack], 94016 => [$parejaB]];
$siempre = static fn (int $id, int $ipa, int $n): bool => true;
// En casi todas las pruebas el cliente ha elegido la versión con regalo de todos los disparadores.
$todas = [92467 => true, 92469 => true, 94016 => true, 92468 => true];
function planT(...$a): array
{
    global $todas;
    $a = array_pad($a, 5, []);
    $a[5] = $todas;

    return KpcRegalo::plan(...$a);
}
$linea = static fn (int $id, int $ipa, int $qty): array => ['clave' => KpcAlcance::clave($id, $ipa), 'id_product' => $id, 'ipa' => $ipa, 'qty' => $qty];
$regalos = static fn (array $plan): array => array_map(static fn (array $r): array => [$r['id_product'], $r['ipa'], $r['cantidad']], array_values($plan['regalos']));

$plan = planT([$linea(8299, 92467, 1)], [], $parejas, $siempre);
igual('añado un 12 kg: entra un 3 kg', [[8299, 92468, 1]], $regalos($plan));
igual('el 3 kg es todo regalo', [92468 => 1], $plan['totales']);
igual('el 12 kg se paga', 1, $plan['pagadas'][92467]);
igual('sin avisos', [], $plan['avisos']);

$plan = planT([$linea(8299, 92467, 2), $linea(8299, 92468, 1)], [92468 => 1], $parejas, $siempre);
igual('subo a dos sacos: dos regalos (la línea del 3 kg tiene que pasar a 2)', [[8299, 92468, 2]], $regalos($plan));
igual('y el 3 kg que había era regalo: no se paga', 0, $plan['pagadas'][92468]);

$plan = planT([$linea(8299, 92467, 1), $linea(8299, 92468, 2)], [92468 => 2], $parejas, $siempre);
igual('bajo a un saco con dos regalos en la línea: queda uno', [[8299, 92468, 1]], $regalos($plan));

$plan = planT([$linea(8299, 92468, 1)], [92468 => 1], $parejas, $siempre);
igual('quito el saco: el regalo se va (nada que regalar)', [], $regalos($plan));
igual('la línea del 3 kg tiene que quedar a 0', 0, ($plan['pagadas'][92468] ?? 0) + ($plan['totales'][92468] ?? 0));

$plan = planT([$linea(8299, 92467, 1)], [92468 => 1], $parejas, $siempre);
igual('el cliente borra la línea del regalo: vuelve (el saco sigue)', [[8299, 92468, 1]], $regalos($plan));

$plan = planT([$linea(8299, 92467, 1), $linea(8299, 92468, 2)], [92468 => 1], $parejas, $siempre);
igual('compra además un 3 kg suelto: paga uno y le regalan otro', 1, $plan['pagadas'][92468]);
igual('la línea tiene que quedar en 2 (1 pagado + 1 regalo)', 2, $plan['pagadas'][92468] + $plan['totales'][92468]);

$plan = planT([$linea(8299, 92468, 2)], [92468 => 1], $parejas, $siempre);
igual('quita el saco pero se queda su 3 kg comprado: la línea baja a 1', 1, ($plan['pagadas'][92468] ?? 0) + ($plan['totales'][92468] ?? 0));

$plan = planT([$linea(8299, 92469, 1)], [], $parejas, $siempre);
igual('un pack de 2 × 12 kg regala dos', [[8299, 92468, 2]], $regalos($plan));

$plan = planT([$linea(8299, 92467, 1), $linea(8299, 92469, 1)], [], $parejas, $siempre);
igual('saco y pack a la vez: 1 + 2 = 3 regalos en la misma línea', [92468 => 3], $plan['totales']);

$plan = planT([$linea(9208, 94016, 3)], [], $parejas, $siempre);
igual('uno por pedido: con tres sacos de gato, una caja', [[8473, 92791, 1]], $regalos($plan));

$plan = planT([$linea(9208, 94016, 1)], [], $parejas, $siempre, [11 => 10]);
igual('tope alcanzado: sin regalo y se dice', [], $regalos($plan));
igual('el aviso', 'tope', $plan['avisos']['11|94016']['motivo'] ?? '');

$parejaConTope = $parejaA + [];
$parejaConTope['tope'] = 5;
$plan = planT([$linea(8299, 92467, 4)], [], [92467 => [$parejaConTope]], $siempre, [10 => 3]);
igual('quedan 2 del tope: 4 sacos, 2 regalos', [[8299, 92468, 2]], $regalos($plan));

$sinStock3kg = static fn (int $id, int $ipa, int $n): bool => $ipa !== 92468;
$plan = planT([$linea(8299, 92467, 1)], [], $parejas, $sinStock3kg);
igual('sin stock del 3 kg: entra la reserva (la caja de latas)', [[8473, 92791, 1]], $regalos($plan));
$sinNada = static fn (int $id, int $ipa, int $n): bool => false;
$plan = planT([$linea(8299, 92467, 1)], [], $parejas, $sinNada);
igual('sin stock de nada: sin regalo', [], $regalos($plan));
igual('y se avisa de que está agotado', 'sin_stock', $plan['avisos']['10|92467']['motivo'] ?? '');
igual('con el regalo que faltaba', ['id' => 8299, 'ipa' => 92468], $plan['avisos']['10|92467']['regalo'] ?? null);

$pocoStock = static fn (int $id, int $ipa, int $n): bool => $ipa !== 92468 || $n <= 2;
$plan = planT([$linea(8299, 92467, 3)], [], $parejas, $pocoStock);
igual('tres sacos pero solo dos 3 kg en stock: los tres regalos pasan a la reserva', [[8473, 92791, 3]], $regalos($plan));

// Lo regalado no dispara nada: si el 3 kg regalado disparase otra promoción, se encadenarían regalos.
$parejaC = ['id_kpy_coupon' => 12, 'clave' => 92468, 'id_product' => 8299, 'n' => 1, 'r' => ['id' => 8473, 'ipa' => 92790], 's' => null, 'origen' => 'mismo', 'por' => 'unidad', 'tope' => 0, 'hasta' => '', 'nombre' => '', 'codigo' => ''];
$plan = planT([$linea(8299, 92467, 1), $linea(8299, 92468, 1)], [92468 => 1], $parejas + [92468 => [$parejaC]], $siempre);
igual('el 3 kg regalado no dispara su propia promoción', [[8299, 92468, 1]], $regalos($plan));
$plan = planT([$linea(8299, 92467, 1), $linea(8299, 92468, 2)], [92468 => 1], $parejas + [92468 => [$parejaC]], $siempre);
igual('pero el 3 kg que paga el cliente, sí', [[8299, 92468, 1], [8473, 92790, 1]], $regalos($plan));

// Dos promociones de regalo sobre el mismo disparador: no pasa (el reparto deja una), pero si
// pasara, cada una regala lo suyo.
$plan = planT([$linea(8299, 92467, 1)], [], [92467 => [$parejaA, $parejaC + ['clave' => 92467]]], $siempre);
igual('dos promociones en el mismo saco: dos regalos', 2, count($plan['regalos']));

// Dos sacos distintos que regalan el mismo artículo: la línea del regalo suma.
$parejaD = $parejaA + [];
$parejaD['clave'] = 94016;
$parejaD['id_product'] = 9208;
$plan = planT([$linea(8299, 92467, 1), $linea(9208, 94016, 2)], [], [92467 => [$parejaA], 94016 => [$parejaD]], $siempre);
igual('dos disparadores, el mismo regalo: 1 + 2 = 3', [92468 => 3], $plan['totales']);
igual('con dos filas en la cesta de regalos (una por disparador)', 2, count($plan['regalos']));

// La elección del cliente: sin ella no hay regalo (la tarjeta «12 kg» de siempre, con su Special
// Price, no regala); con ella, solo regalan las líneas elegidas.
$plan = KpcRegalo::plan([$linea(8299, 92467, 1)], [], $parejas, $siempre);
igual('sin elegir la versión con regalo, nada', [], $regalos($plan));
$plan = KpcRegalo::plan([$linea(8299, 92467, 1), $linea(9208, 94016, 1)], [], $parejas, $siempre, [], [92467 => true]);
igual('elegida solo la del 12 kg: regala el 12 kg, el de gato no', [[8299, 92468, 1]], $regalos($plan));
$plan = KpcRegalo::plan([$linea(8299, 92467, 1), $linea(8299, 92468, 1)], [92468 => 1], $parejas, $siempre, [], []);
igual('quita el regalo con la papelera (ya no está elegida): el 3 kg regalado se va', [], $regalos($plan));
igual('y su línea queda a 0', 0, ($plan['pagadas'][92468] ?? 0) + ($plan['totales'][92468] ?? 0));
$plan = KpcRegalo::plan([$linea(8299, 92468, 1)], [], $parejas, $siempre, [], [92467 => true]);
igual('una elección de una línea que no está en la cesta no hace nada', [], $regalos($plan));

// Por unidades (2.2.0): cada tarjeta añade lo suyo. «12 kg + 3 kg gratis» y «12 kg» con su Special
// Price en la misma línea: solo regalan las unidades elegidas con regalo.
$elegida = static fn (int $u): array => ['precio' => 0, 'unidades' => $u, 'desde' => ''];
$plan = KpcRegalo::plan([$linea(8299, 92467, 2)], [], $parejas, $siempre, [], [92467 => $elegida(1)]);
igual('dos 12 kg, uno con regalo y otro de la tarjeta de siempre: un 3 kg', [[8299, 92468, 1]], $regalos($plan));
igual('y una unidad va con regalo', [92467 => 1], $plan['unidades']);
$plan = KpcRegalo::plan([$linea(8299, 92467, 3), $linea(8299, 92468, 3)], [92468 => 1], $parejas, $siempre, [], [92467 => $elegida(2)]);
igual('tres 12 kg (dos con regalo) y la línea del 3 kg con uno regalado y dos pagados: dos regalos', [[8299, 92468, 2]], $regalos($plan));
igual('los 3 kg pagados siguen siendo dos', 2, $plan['pagadas'][92468]);
$plan = KpcRegalo::plan([$linea(8299, 92467, 1)], [], $parejas, $siempre, [], [92467 => $elegida(5)]);
igual('más unidades elegidas que las que hay: las que hay', [92467 => 1], $plan['unidades']);
$plan = KpcRegalo::plan([$linea(8299, 92467, 2)], [], $parejas, $siempre, [], [92467 => $elegida(0)]);
igual('una elección de antes de la 2.2.0 (sin unidades): toda la línea', [92467 => 2], $plan['unidades']);
$plan = KpcRegalo::plan([$linea(9208, 94016, 3)], [], [94016 => [$parejaB]], $siempre, [], [94016 => $elegida(3)]);
igual('uno por pedido: una unidad con regalo, aunque se elijan tres', [94016 => 1], $plan['unidades']);
igual('y un regalo', [[8473, 92791, 1]], $regalos($plan));
$parejaN = ['n' => 2, 'tope' => 3] + $parejaA;
$plan = KpcRegalo::plan([$linea(8299, 92467, 2)], [], [92467 => [$parejaN]], $siempre, [], [92467 => $elegida(2)]);
igual('dos regalos por unidad y quedan 3 en el tope: una unidad entera (dos regalos), no tres sueltos', [[8299, 92468, 2]], $regalos($plan));
igual('y una unidad con regalo; la otra, a la tarjeta de siempre', [92467 => 1], $plan['unidades']);
$plan = KpcRegalo::plan([$linea(8299, 92467, 1)], [], [92467 => [$parejaN]], $siempre, [1 => 0, 10 => 2], [92467 => $elegida(1)]);
igual('quedan 1 en el tope y cada unidad lleva 2: ninguna, con aviso', [[], 'tope'], [$regalos($plan), $plan['avisos']['10|92467']['motivo'] ?? '']);
igual('unidadesDe: true son todas las pagadas', 3, KpcRegalo::unidadesDe(true, 3));
igual('unidadesDe: nada, ninguna', 0, KpcRegalo::unidadesDe(null, 3));
igual('unidadesDe: sin unidades pagadas, ninguna', 0, KpcRegalo::unidadesDe($elegida(2), 0));

// Una línea personalizada o rara no rompe nada.
igual('las líneas se leen de la cesta', [['clave' => 92467, 'id_product' => 8299, 'ipa' => 92467, 'qty' => 2], ['clave' => -555, 'id_product' => 555, 'ipa' => 0, 'qty' => 1]],
    KpcRegalo::lineasDe([['id_product' => 8299, 'id_product_attribute' => 92467, 'cart_quantity' => 2], ['id_product' => 555, 'id_product_attribute' => 0, 'cart_quantity' => 1], ['id_product' => 7, 'id_product_attribute' => 8, 'cart_quantity' => 1, 'id_customization' => 3]]));

echo "\nNombres y etiquetas\n";
igual('nombre corto con formato (46 letras y puntos suspensivos)', 'Boske Adult Grain Free Salmón Pienso Natural p… · Saco de 3 Kg', KpcRegalo::nombreCorto('Boske Adult Grain Free Salmón Pienso Natural para Perro', 'Saco de 3 Kg'));
igual('el formato sin «Grupo:»', 'Carda · Talla M', KpcRegalo::nombreCorto('Carda', 'Talla: Talla M'));
igual('sin formato', 'Carda', KpcRegalo::nombreCorto('  Carda '));
igual('+ 3 kg gratis', '+ 3 kg gratis', KpcRegalo::etiqueta('Saco de 3 Kg', true));
igual('+ 2,5 kg gratis', '+ 2,5 kg gratis', KpcRegalo::etiqueta('Saco de 2.5 Kg', true));
igual('+ 1 lata gratis (una lata de su ficha, se cuenta)', '+ 1 lata gratis', KpcRegalo::etiqueta('Lata de 400 gr', true));
igual('+ 6 latas gratis (un pack de latas de su ficha)', '+ 6 latas gratis', KpcRegalo::etiqueta('Pack 6 x Lata de 400 gr', true));
igual('+ 2 pouches gratis', '+ 2 pouches gratis', KpcRegalo::etiqueta('Pack 2 x Pouch de 85 gr', true));
igual('otro artículo: + regalo', '+ regalo', KpcRegalo::etiqueta('Saco de 3 Kg', false));
igual('sin peso: + regalo', '+ regalo', KpcRegalo::etiqueta('Talla M', true));

echo "\nLa tarjeta de la ficha («12 kg + ___») y la clase\n";
igual('más de lo mismo: «3 kg gratis»', '3 kg gratis', KpcRegalo::corto('Saco de 3 Kg', 'Boske Adult Salmón', true));
igual('una lata de su ficha: «1 lata gratis»', '1 lata gratis', KpcRegalo::corto('Lata de 400 gr', 'Boske lata', true));
igual('un pack de latas de otro producto: «Pack latas»', 'Pack latas', KpcRegalo::corto('Pack 6 x Lata de 400 gr', 'Boske Latas Pollo', false));
igual('una lata suelta de otro producto: «Lata»', 'Lata', KpcRegalo::corto('Lata de 400 gr', 'Boske Lata Pollo', false));
igual('un pack de sobres: «Pack sobres»', 'Pack sobres', KpcRegalo::corto('Pack 12 x Sobre de 85 gr', 'Boske Gato húmedo', false));
igual('un snack, por el nombre', 'Snacks', KpcRegalo::corto('Bolsa de 100 gr', 'Boske Snacks de pollo', false));
igual('una bolsita pequeña sin decir snack', 'Snacks', KpcRegalo::corto('Bolsa de 150 gr', 'Boske Bocaditos', false));
igual('una bolsa grande de otro pienso no es un snack', 'Regalo', KpcRegalo::corto('Bolsa de 2 Kg', 'Otro pienso', false));
igual('un accesorio: «Regalo»', 'Regalo', KpcRegalo::corto('Talla M', 'Carda Kompy', false));
igual('lo que diga la promoción manda', 'Carda', KpcRegalo::corto('Talla M', 'Carda Kompy', false, ' Carda '));
igual('pero no sobre «más de lo mismo»', '3 kg gratis', KpcRegalo::corto('Saco de 3 Kg', 'Boske', true, 'Regalo'));
igual('clase kilos', 'kilos', KpcRegalo::clase('+ 3 kg gratis', true));
igual('clase kilos con latas de su ficha', 'kilos', KpcRegalo::clase('+ 1 lata gratis', true));
igual('clase regalo: otra cosa', 'regalo', KpcRegalo::clase('+ regalo', true));
igual('clase regalo: otro producto aunque sea un saco', 'regalo', KpcRegalo::clase('+ 3 kg gratis', false));

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
