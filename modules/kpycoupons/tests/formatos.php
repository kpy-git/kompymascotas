<?php
/**
 * Prueba cómo se leen los formatos (tamaño y nivel de pack), sin tienda:
 *   php tests/formatos.php
 *
 * Los nombres son de verdad, de la tienda de pruebas (30/09/2026).
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

/** Nivel de cada formato de un producto, por id. */
function niveles(array $nombres, array $tabla = []): array
{
    $combinaciones = [];
    foreach ($nombres as $ipa => $nombre) {
        $combinaciones[] = ['ipa' => $ipa, 'nombre' => $nombre];
    }

    return array_map(static fn ($n) => $n['nivel'], KpcFormatos::niveles($combinaciones, $tabla));
}

echo "El tamaño, igual para el saco y sus packs\n";
igual('saco', 'saco 12 kg', KpcFormatos::tamano('Saco de 12 Kg'));
igual('su pack', 'saco 12 kg', KpcFormatos::tamano('Pack 2 x Saco de 12 Kg'));
igual('pack con el nombre corto', 'saco 12 kg', KpcFormatos::tamano('Pack 2 x 12 kg.'));
igual('decimales con punto o coma', 'saco 1,5 kg', KpcFormatos::tamano('1.5 Kg'));
igual('OUTLET fuera del tamaño', 'saco 10 kg', KpcFormatos::tamano('Saco de 10 Kg - OUTLET'));
igual('latas en caja', 'lata 70 g', KpcFormatos::tamano('Pack 24 x Lata de 70 gr'));
igual('«Pack 24 Latas 85 gr»', 'lata 85 g', KpcFormatos::tamano('Pack 24 Latas 85 gr'));
igual('«Pack 12 latas x 185g»', 'lata 185 g', KpcFormatos::tamano('Pack 12 latas x 185g'));
igual('«paquete de 4 bolsas de 85 gr»', 'bolsa 85 g', KpcFormatos::tamano('paquete de 4 bolsas de 85 gr'));
igual('pack de pack', 'bolsa 50 g', KpcFormatos::tamano('Pack 2 x Pack 6 x Bolsa de 50 gr'));
igual('el saco con regalo es otro tamaño', 'saco 15 kg + 3 kg gratis', KpcFormatos::tamano('Pack 2 x Saco de 15 kg + 3 kg Gratis'));
igual('«Pack 16 uds» se queda como está', 'pack 16 uds', KpcFormatos::tamano('Pack 16 uds'));
igual('litros', '10 l', KpcFormatos::tamano('10 Litros'));
igual('etiqueta', 'Saco 12 kg', KpcFormatos::etiqueta('saco 12 kg'));
igual('etiqueta de talla', 'XS-S', KpcFormatos::etiqueta('xs-s'));
igual('orden: primero sacos, de menor a mayor', ['saco 1,5 kg', 'saco 12 kg', 'saco 14 + 2 kg', 'lata 70 g', 'lata 400 g'],
    (static function () {
        $t = ['lata 400 g', 'saco 12 kg', 'lata 70 g', 'saco 14 + 2 kg', 'saco 1,5 kg'];
        usort($t, static fn ($a, $b) => KpcFormatos::orden($a) <=> KpcFormatos::orden($b));

        return $t;
    })());

echo "\nCuántas piezas dice el nombre\n";
igual('«Pack 2 x Saco de 15 Kg»', 2, KpcFormatos::unidades('Pack 2 x Saco de 15 Kg'));
igual('«Saco de 15 Kg»', 1, KpcFormatos::unidades('Saco de 15 Kg'));
igual('«Pack 24 Latas 85 gr»', 24, KpcFormatos::unidades('Pack 24 Latas 85 gr'));
igual('«Pack 2 x Pack 6 x Bolsa de 50 gr»', 12, KpcFormatos::unidades('Pack 2 x Pack 6 x Bolsa de 50 gr'));
igual('«12 kg» no son 12 piezas', 1, KpcFormatos::unidades('12 Kg'));
igual('«Pack 16 uds»: sin tamaño, una pieza', 1, KpcFormatos::unidades('Pack 16 uds'));

echo "\nNivel de pack: cuántos sueltos lleva\n";
igual('Royal Canin: saco, pack de 2 y el de regalo aparte', [1853 => 1, 1854 => 2, 4855 => 1, 4856 => 2], niveles([
    1853 => 'Saco de 10 Kg', 1854 => 'Pack 2 x 10 Kg', 4855 => 'Saco de 10 + 2 Kg', 4856 => 'Pack 2 x 10 + 2 Kg',
]));
igual('el saco con regalo no es pack del saco', [90684 => 1, 90685 => 2, 93147 => 1, 93148 => 2], niveles([
    90684 => 'Saco de 15 Kg', 90685 => 'Pack 2 x Saco de 15 kg', 93147 => 'Saco de 15 kg + 3 kg Gratis', 93148 => 'Pack 2 x Saco de 15 kg + 3 kg Gratis',
]));
igual('Schesir: la caja de 12 es lo suelto (no hay lata suelta)', [1 => 1, 2 => 2, 3 => 4], niveles([
    1 => 'Pack 12 x Lata de 70 gr', 2 => 'Pack 24 x Lata de 70 gr', 3 => 'Pack 48 x Lata de 70 gr',
]));
igual('Farmina: la tabla encadena (48 = 2 del de 24; 24 = 4 del de 6)', [6 => 1, 12 => 2, 24 => 4, 48 => 8], niveles(
    [6 => 'Pack 6 x Lata de 70 gr', 12 => 'Pack 12 x Lata de 70 gr', 24 => 'Pack 24 x Lata de 70 gr', 48 => 'Pack 48 x Lata de 70 gr'],
    [24 => ['ipa' => 6, 'n' => 4], 48 => ['ipa' => 24, 'n' => 2]]
));
igual('la tabla manda sobre el nombre', [1 => 1, 2 => 3], niveles([1 => 'Saco de 3 Kg', 2 => 'Pack 2 x 3 Kg'], [2 => ['ipa' => 1, 'n' => 3]]));
igual('pack de sacos sin el saco suelto: sigue siendo pack', [5 => 2], niveles([5 => 'Pack 2 x Saco de 9 Kg']));
igual('caja de latas sin lata suelta: es lo suelto', [5 => 1], niveles([5 => 'Pack 12 x Lata de 400 gr']));
igual('el OUTLET de un pack es pack', [1 => 1, 2 => 2, 3 => 1, 4 => 2], niveles([
    1 => 'Saco de 12 Kg', 2 => 'Pack 2 x Saco de 12 Kg', 3 => 'Saco de 12 Kg - OUTLET', 4 => 'Pack 2 x Saco de 12 Kg - OUTLET',
]));
igual('latas y bolsas del mismo peso no se mezclan', [1 => 1, 2 => 1], niveles([1 => 'Bolsa de 70 gr', 2 => 'Pack 12 x Lata de 70 gr']));
$n = KpcFormatos::niveles([['ipa' => 1853, 'nombre' => 'Saco de 10 Kg'], ['ipa' => 1854, 'nombre' => 'Pack 2 x 10 Kg']]);
igual('el pack sabe cuál es su saco y su tamaño', ['nivel' => 2, 'base' => 1853, 'padre' => 1853, 'tam' => 'saco 10 kg'], $n[1854]);

echo "\nLa promoción: tamaños y niveles\n";
$producto = ['id_product' => 7214, 'id_manufacturer' => 238, 'categorias' => [6490]];
$farmina = [
    ['ipa' => 1, 'nombre' => 'Saco de 2,5 Kg', 'especial' => false],
    ['ipa' => 2, 'nombre' => 'Pack 2 x Saco de 2,5 Kg', 'especial' => false],
    ['ipa' => 3, 'nombre' => 'Saco de 12 Kg', 'especial' => false],
    ['ipa' => 4, 'nombre' => 'Pack 2 x Saco de 12 Kg', 'especial' => false],
    ['ipa' => 5, 'nombre' => 'Saco de 12 Kg - OUTLET', 'especial' => false],
];
$marca = [['tipo' => 'marca', 'id' => 238]];
igual('Farmina, sacos de 12 kg, 2ª unidad: el saco, sin pack ni OUTLET', [3],
    KpcElegibles::combinaciones(['tipo' => 'segunda_unidad', 'ambitos' => $marca, 'tamanos' => "saco 12 kg"], $producto, $farmina));
igual('Farmina, sacos de menos de 3 kg (el 2,5)', [1],
    KpcElegibles::combinaciones(['tipo' => 'segunda_unidad', 'ambitos' => $marca, 'tamanos' => ['saco 2,5 kg']], $producto, $farmina));
igual('Special Price, 12 kg: saco y pack', [3, 4],
    KpcElegibles::combinaciones(['tipo' => 'special_price', 'ambitos' => $marca, 'tamanos' => 'saco 12 kg'], $producto, $farmina));
igual('solo los packs de 2', [2, 4],
    KpcElegibles::combinaciones(['tipo' => 'special_price', 'ambitos' => $marca, 'packs' => '2'], $producto, $farmina));
igual('solo sueltos', [1, 3],
    KpcElegibles::combinaciones(['tipo' => 'cupon', 'ambitos' => $marca, 'packs' => '1'], $producto, $farmina));
igual('cualquier pack («2+»)', [2, 4],
    KpcElegibles::combinaciones(['tipo' => 'cupon', 'ambitos' => $marca, 'packs' => '2+'], $producto, $farmina));
igual('sueltos y packs de 2 = todo', [1, 2, 3, 4],
    KpcElegibles::combinaciones(['tipo' => 'cupon', 'ambitos' => $marca, 'packs' => '1,2'], $producto, $farmina));
igual('la lista con el saco trae su pack', [3, 4],
    KpcElegibles::combinaciones(['tipo' => 'cupon', 'ambitos' => [['tipo' => 'combinacion', 'id' => 3]]], $producto, $farmina));
igual('el tamaño no filtra la lista (la lista suma)', [3, 4],
    KpcElegibles::combinaciones(['tipo' => 'cupon', 'ambitos' => [['tipo' => 'combinacion', 'id' => 3]], 'tamanos' => 'saco 2,5 kg'], $producto, $farmina));

$schesir = [
    ['ipa' => 11, 'nombre' => 'Pack 12 x Lata de 70 gr', 'especial' => false],
    ['ipa' => 12, 'nombre' => 'Pack 24 x Lata de 70 gr', 'especial' => false],
    ['ipa' => 13, 'nombre' => 'Pack 48 x Lata de 70 gr', 'especial' => false],
];
$deSchesir = ['id_product' => 1, 'id_manufacturer' => 224, 'categorias' => []];
igual('Schesir, packs de 2: la caja de 24', [12],
    KpcElegibles::combinaciones(['tipo' => 'special_price', 'ambitos' => [['tipo' => 'marca', 'id' => 224]], 'packs' => '2'], $deSchesir, $schesir));
igual('Schesir, 2ª unidad: la caja de 12, que es lo suelto', [11],
    KpcElegibles::combinaciones(['tipo' => 'segunda_unidad', 'ambitos' => [['tipo' => 'marca', 'id' => 224]]], $deSchesir, $schesir));

echo "\nGamas: afinan su marca y solo la suya\n";
$gamas = [3 => [1679, 2270]];   // Royal Canin: razas pienso gato (y su subcategoría)
$ambos = [['tipo' => 'marca', 'id' => 3], ['tipo' => 'marca', 'id' => 238]];
$rcGato = ['id_product' => 784, 'id_manufacturer' => 3, 'categorias' => [1679]];
$rcPerro = ['id_product' => 800, 'id_manufacturer' => 3, 'categorias' => [1678]];
$unSaco = [['ipa' => 0, 'nombre' => '', 'especial' => false]];
igual('Royal Canin de la gama: entra', [0], KpcElegibles::combinaciones(['ambitos' => $ambos, 'gamas' => $gamas, 'generales' => []], $rcGato, $unSaco));
igual('Royal Canin de otra gama: no', [], KpcElegibles::combinaciones(['ambitos' => $ambos, 'gamas' => $gamas, 'generales' => []], $rcPerro, $unSaco));
igual('Farmina, sin gama elegida: entera', [0], KpcElegibles::combinaciones(['ambitos' => $ambos, 'gamas' => $gamas, 'generales' => []], $producto, $unSaco));

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
