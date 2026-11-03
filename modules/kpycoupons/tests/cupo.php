<?php
/**
 * El cupo (promociones por cantidad), sin tienda: lo que queda, cómo se reparte entre las líneas
 * de una cesta, las parejas de la 2ª unidad que caben, el regalo con el cupo contando lo reservado
 * en otras cestas, y los nombres del Special Price («Liquidación», «Bajada de precio»).
 *   php tests/cupo.php
 */
require __DIR__ . '/../classes/KpcCupo.php';
require __DIR__ . '/../classes/KpcSegunda.php';
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

echo "Lo que queda\n";
igual('sin cupo, sin límite', null, KpcCupo::quedan(0, 50, 10));
igual('cupo 100, 60 vendidas y 10 en otras cestas: quedan 30', 30, KpcCupo::quedan(100, 60, 10));
igual('todo vendido: 0', 0, KpcCupo::quedan(100, 100, 0));
igual('vendido de más (dos pedidos a la vez): 0, nunca negativo', 0, KpcCupo::quedan(100, 101, 5));
igual('todo en otras cestas: 0 para esta', 0, KpcCupo::quedan(10, 0, 10));

echo "\nEl reparto entre las líneas de una cesta\n";
igual('sin límite, cada línea lo suyo', [92467 => 3, 92468 => 2], KpcCupo::repartir([92467 => 3, 92468 => 2], null));
igual('con 4: la primera entera, la segunda lo que queda', [92467 => 3, 92468 => 1], KpcCupo::repartir([92467 => 3, 92468 => 2], 4));
igual('con 2: la primera a medias, la segunda nada', [92467 => 2, 92468 => 0], KpcCupo::repartir([92467 => 3, 92468 => 2], 2));
igual('con 0: nada para nadie', [92467 => 0, 92468 => 0], KpcCupo::repartir([92467 => 3, 92468 => 2], 0));
igual('el orden manda (la que entró antes, primero)', [92468 => 2, 92467 => 2], KpcCupo::repartir([92468 => 2, 92467 => 3], 4));
igual('una línea que no pide nada', [92467 => 0, 92468 => 2], KpcCupo::repartir([92467 => 0, 92468 => 2], 5));
igual('lo que pide una línea nunca es negativo', [92467 => 0], KpcCupo::repartir([92467 => -3], 5));
igual('sin líneas', [], KpcCupo::repartir([], 5));
igual('sobra cupo: cada una lo suyo', [92467 => 3, 92468 => 2], KpcCupo::repartir([92467 => 3, 92468 => 2], 50));

echo "\nLa 2ª unidad: las parejas que caben\n";
igual('5 y 4 unidades: 2 y 2 rebajadas', [1 => 2, 2 => 2], KpcSegunda::reparto([1 => 5, 2 => 4]));
igual('con cupo para 3 rebajadas: 2 y 1', [1 => 2, 2 => 1], KpcSegunda::reparto([1 => 5, 2 => 4], 3));
igual('con cupo para 1: 1 y 0', [1 => 1, 2 => 0], KpcSegunda::reparto([1 => 5, 2 => 4], 1));
igual('sin cupo que quede: ninguna', [1 => 0, 2 => 0], KpcSegunda::reparto([1 => 5, 2 => 4], 0));
igual('una unidad sola no hace par', [1 => 0], KpcSegunda::reparto([1 => 1], 10));
igual('las regaladas no vienen aquí (ya descontadas): 3 pagadas, 1 par', [1 => 1], KpcSegunda::reparto([1 => 3]));

echo "\nEl regalo con el cupo: lo dado en pedidos más lo reservado en otras cestas\n";
$pareja = ['id_kpy_coupon' => 10, 'clave' => 92467, 'id_product' => 8299, 'n' => 1, 'r' => ['id' => 8299, 'ipa' => 92468], 's' => null,
    'origen' => 'pequeno', 'por' => 'unidad', 'tope' => 10, 'hasta' => '', 'nombre' => 'Boske 12 + 3', 'codigo' => 'REGALO-BOSKE'];
$siempre = static fn (int $id, int $ipa, int $n): bool => true;
$lineas = [['clave' => 92467, 'id_product' => 8299, 'ipa' => 92467, 'qty' => 3]];
$plan = KpcRegalo::plan($lineas, [], [92467 => [$pareja]], $siempre, [10 => 7], [92467 => true]);
igual('cupo 10, 7 comprometidos (dados + en otras cestas), pide 3: los 3', 3, $plan['regalos']['10|92467']['cantidad']);
$plan = KpcRegalo::plan($lineas, [], [92467 => [$pareja]], $siempre, [10 => 8], [92467 => true]);
igual('con 8 comprometidos, solo caben 2', 2, $plan['regalos']['10|92467']['cantidad']);
igual('y van con regalo 2 unidades (la tercera, a la tarjeta de siempre)', 2, $plan['unidades'][92467]);
$plan = KpcRegalo::plan($lineas, [], [92467 => [$pareja]], $siempre, [10 => 10], [92467 => true]);
igual('con 10 comprometidos, ninguno', [], $plan['regalos']);
igual('y la cesta lo dice', 'tope', $plan['avisos']['10|92467']['motivo']);
$plan = KpcRegalo::plan($lineas, [], [92467 => [$pareja]], $siempre, [], [92467 => true]);
igual('sin nada comprometido, todos', 3, $plan['regalos']['10|92467']['cantidad']);

echo "\nCómo se llama el Special Price\n";
igual('sin etiqueta', 'Special Price', KpcCupo::nombreEtiqueta(''));
igual('liquidación', 'Liquidación', KpcCupo::nombreEtiqueta('liquidacion'));
igual('bajada de precio', 'Bajada de precio', KpcCupo::nombreEtiqueta('bajada'));
igual('una que no existe, la de siempre', 'Special Price', KpcCupo::nombreEtiqueta('rara'));
igual('null, la de siempre', 'Special Price', KpcCupo::nombreEtiqueta(null));
igual('la clase de liquidación', 'liquidacion', KpcCupo::claseEtiqueta('liquidacion'));
igual('la clase de siempre', 'especial', KpcCupo::claseEtiqueta(''));
igual('en SQL, las que no están agotadas', '((c.cupo = 0 AND c.cupo_lista = 0) OR c.cupo_agotado IS NULL)', KpcCupo::sqlVivo('c'));
igual('lo que cuenta el cupo en un regalo', 'regalos dados', KpcCupo::unidadDe('regalo'));
igual('lo que cuenta en un tipo raro: lo del Special Price', KpcCupo::UNIDAD['special_price'], KpcCupo::unidadDe('otro'));

echo "\nEl cupo por artículo (las unidades de la lista)\n";
igual('cada línea hasta lo suyo: el 12 kg con 2, el 3 kg sin límite propio', [92467 => 2, 92468 => 2], KpcCupo::repartirConLimites([92467 => 3, 92468 => 2], null, [92467 => 2]));
igual('y además el cupo de la promoción (3 en total): 2 y 1', [92467 => 2, 92468 => 1], KpcCupo::repartirConLimites([92467 => 3, 92468 => 2], 3, [92467 => 2]));
igual('un artículo agotado (0): nada, y el resto lo suyo', [92467 => 0, 92468 => 2], KpcCupo::repartirConLimites([92467 => 3, 92468 => 2], null, [92467 => 0]));
igual('null por artículo es sin límite', [92467 => 3], KpcCupo::repartirConLimites([92467 => 3], null, [92467 => null]));
igual('sin límites, lo de siempre', [92467 => 3, 92468 => 2], KpcCupo::repartirConLimites([92467 => 3, 92468 => 2], null, []));
igual('la 2ª unidad con unidades por artículo: 5 y 4 uds., el primero solo 1 rebajada', [1 => 1, 2 => 2], KpcSegunda::reparto([1 => 5, 2 => 4], null, [1 => 1]));
igual('tiene cupo: por promoción', true, KpcCupo::tieneCupo(['cupo' => 5, 'cupo_lista' => 0]));
igual('tiene cupo: por artículo', true, KpcCupo::tieneCupo(['cupo' => 0, 'cupo_lista' => 1]));
igual('sin cupo', false, KpcCupo::tieneCupo(['cupo' => 0, 'cupo_lista' => 0]));
igual('la clave de un formato de la lista', 92467, KpcCupo::claveDeAmbito('combinacion', 92467));
igual('la de un producto sin formatos, en negativo', -8299, KpcCupo::claveDeAmbito('producto', 8299));
igual('una marca no es un artículo', 0, KpcCupo::claveDeAmbito('marca', 178));
igual('outlet se llama Outlet', 'Outlet', KpcCupo::nombreEtiqueta('outlet'));
igual('y su clase', 'outlet', KpcCupo::claseEtiqueta('outlet'));
igual('liquidación y outlet van al cajón; bajada y Special Price, no', [true, true, false, false], [KpcCupo::esCajon('liquidacion'), KpcCupo::esCajon('outlet'), KpcCupo::esCajon('bajada'), KpcCupo::esCajon('')]);

echo "\n" . ($fallos ? "$fallos fallos\n" : "Todo bien\n");
exit($fallos ? 1 : 0);
