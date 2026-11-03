<?php
/**
 * Las listas que llegan de fuera (EAN, referencias, ids, enlaces, Excel). Sin tienda:
 *   php tests/lista.php
 */
require __DIR__ . '/../classes/KpcLista.php';

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

// Una tienda de mentira: Boske 7214 (15 kg y 3 kg), Eukanuba 2809, un accesorio sin formatos 3647.
$indice = [
    'ean' => [
        '8436000000015' => [[7214, 90684]],
        '8436000000039' => [[7214, 92895]],
        '52742805702' => [[438, 592]],             // UPC de 12 cifras, guardado sin el 0
    ],
    'eanP' => ['4011905322810' => [[3647, 0]]],
    'ref' => ['EU81378312' => [[2809, 3490]], '8057U' => [[438, 592]]],
    'refP' => ['TX32281' => [[3647, 0]]],
    'producto' => [7214 => ['activo' => true], 2809 => ['activo' => true], 3647 => ['activo' => true], 438 => ['activo' => false], 545 => ['activo' => true]],
    'combinacion' => [90684 => 7214, 92895 => 7214, 3490 => 2809, 592 => 438, 824 => 545],
];
$items = static fn (array $r): array => array_map(static fn ($i) => $i['id_product'] . '-' . $i['ipa'], $r['items']);

echo "Leer lo pegado\n";
igual('una columna de Excel', [['8436000000015'], ['8436000000039']], KpcLista::celdas("8436000000015\r\n8436000000039\r\n"));
igual('varios en una línea, con comas', [['7214'], ['2809']], KpcLista::celdas('7214, 2809'));
igual('lo que no lleva cifras se tira', [], KpcLista::celdas("Boske
Adult"));
igual('los espacios no separan: «TX 32281» es una referencia', [['TX 32281']], KpcLista::celdas('TX 32281'));
igual('varias columnas con tabuladores', [['8436000000015', 'Boske 15 kg', '49,99']], KpcLista::celdas("8436000000015\tBoske 15 kg\t49,99"));

echo "Qué parece cada cosa\n";
igual('EAN-13', 'ean', KpcLista::pinta('8436000000015'));
igual('producto-formato', 'combinacion', KpcLista::pinta('7214-90684'));
igual('id', 'numero', KpcLista::pinta('7214'));
igual('referencia', 'texto', KpcLista::pinta('EU81378312'));
igual('enlace', 'url', KpcLista::pinta('https://kompymascotas.com/pienso-perros-renal-/545-824-royal-canin-renal.html'));

echo "Buscar en la tienda\n";
$r = KpcLista::buscar(KpcLista::celdas("8436000000015\n0052742805702\n9999999999999"), 'auto', $indice);
igual('EAN, con ceros delante o sin ellos', ['7214-90684', '438-592'], $items($r));
igual('el que no está, se dice', ['9999999999999'], $r['no']);
igual('y el de un producto apagado se cuenta', 1, $r['apagados']);

$r = KpcLista::buscar(KpcLista::celdas("eu-81378312\n8057 u\nTX 32281"), 'auto', $indice);
igual('referencias sin mirar guiones, espacios ni mayúsculas', ['2809-3490', '438-592', '3647-0'], $items($r));

$r = KpcLista::buscar(KpcLista::celdas("7214\n2809"), 'auto', $indice);
igual('ids de producto: todo el producto', ['7214-0', '2809-0'], $items($r));
igual('leídos como ids', 'id', $r['tipo']);
$r = KpcLista::buscar(KpcLista::celdas("Boske 7214
7214 2809"), 'auto', $indice);
igual('con nombre delante, o varios en una línea: palabra a palabra', ['7214-0', '2809-0'], $items($r));

$r = KpcLista::buscar(KpcLista::celdas("7214-90684\n7214-3490"), 'auto', $indice);
igual('producto-formato: solo si el formato es de ese producto', ['7214-90684'], $items($r));

$r = KpcLista::buscar([['https://kompymascotas.com/x/545-824-royal-canin-renal.html'], ['https://kompymascotas.com/x/2809-eukanuba-adult.html']], 'auto', $indice);
igual('enlaces de la web', ['545-824', '2809-0'], $items($r));

echo "Un Excel de proveedor con varias columnas\n";
$excel = [
    ['Referencia', 'Descripción', 'EAN', 'PVP'],
    ['A1', 'Boske Adult 15 kg', '8436000000015', '49.99'],
    ['A2', 'Boske Adult 3 kg', '8436000000039', '12.5'],
    ['A3', 'Algo que no tenemos', '8436000000999', '7'],
];
$r = KpcLista::buscar($excel, 'auto', $indice);
igual('elige la columna del EAN', 3, $r['columna']);
igual('encuentra lo que hay', ['7214-90684', '7214-92895'], $items($r));
igual('la cabecera no cuenta como «no encontrado»', ['8436000000999'], $r['no']);
igual('y no confunde el precio con un id', 3, $r['filas']);

echo "Con su % o su precio (Special Price por artículo)\n";
$valores = static fn (array $r): array => array_map(static fn ($i) => $i['id_product'] . '-' . $i['ipa'] . '=' . json_encode($i['valor']), $r['items']);
$r = KpcLista::buscar(KpcLista::celdas("8436000000015;13\n8436000000039;12,5 %"), 'auto', $indice, 'pct');
igual('EAN;% (y «12,5 %»)', ['7214-90684=13', '7214-92895=12.5'], $valores($r));
$r = KpcLista::buscar(KpcLista::celdas("8436000000015 44,99 €\n8436000000039 11,5"), 'auto', $indice, 'precio');
igual('EAN y precio separados por un espacio', ['7214-90684=44.99', '7214-92895=11.5'], $valores($r));
igual('se guardan como «EAN;valor»', ['8436000000015;44,99 €', '8436000000039;11,5'], $r['valores']);
$proveedor = [
    ['EAN', 'Descripción', 'PVP', 'Dto.'],
    ['8436000000015', 'Boske 15 kg', '49.99', '0.13'],       // porcentaje de Excel
    ['8436000000039', 'Boske 3 kg', '12.5', '15'],
    ['8436000000999', 'No lo tenemos', '7', '10'],
];
$r = KpcLista::buscar($proveedor, 'auto', $indice, 'pct');
igual('elige la columna «Dto.» y no la del PVP', 4, $r['columna_valor']);
igual('0,13 de Excel es un 13 %', ['7214-90684=13', '7214-92895=15'], $valores($r));
$precios = [
    ['EAN', 'PVP', 'PVP promo'],
    ['8436000000015', '49,99', '42,50'],
    ['8436000000039', '12,50', 'consultar'],
];
$r = KpcLista::buscar($precios, 'auto', $indice, 'precio');
igual('elige «PVP promo» y no el PVP', 3, $r['columna_valor']);
igual('el que no trae un precio que se entienda, se dice', ['8436000000039 (consultar)'], $r['sin_valor']);
igual('leer valores', [13.0, 13.0, 1234.56, null, null], [
    KpcLista::leerValor('13%', 'pct'), KpcLista::leerValor('0,13', 'pct'), KpcLista::leerValor('1.234,56 €', 'precio'),
    KpcLista::leerValor('95', 'pct'), KpcLista::leerValor('gratis', 'precio'),
]);

echo "Forzar cómo leerla\n";
$r = KpcLista::buscar(KpcLista::celdas("7214\n2809"), 'codigo', $indice);
igual('como códigos, «7214» no es nada', [], $items($r));

echo "El EAN del saco lo lleva también su pack\n";
// Royal Canin 784: «Saco de 10 Kg» (1853) y «Pack 2 x 10 Kg» (1854) con el mismo EAN.
$conPack = $indice;
$conPack['ean']['3182550721233'] = [[784, 1853], [784, 1854]];
$conPack['producto'][784] = ['activo' => true];
$conPack['combinacion'] += [1853 => 784, 1854 => 784];
$conPack['padre'] = [1854 => 1853];
$r = KpcLista::buscar(KpcLista::celdas("3182550721233;45,00"), 'auto', $conPack, 'precio');
igual('se queda el saco, con su precio (el pack entra luego como pack)', ['784-1853'], $items($r));
igual('el precio es del saco', 45.0, $r['items'][0]['valor']);
$r = KpcLista::buscar(KpcLista::celdas("784-1854"), 'auto', $conPack);
igual('el pack pedido por su id sí entra', ['784-1854'], $items($r));

echo "Con las unidades de cada artículo (cupo por artículo)\n";
$unidades = static fn (array $r): array => array_map(static fn ($i) => $i['id_product'] . '-' . $i['ipa'] . '=' . json_encode($i['unidades']), $r['items']);
$r = KpcLista::buscar(KpcLista::celdas("8436000000015\t12\n8436000000039\t5"), 'auto', $indice, '', true);
igual('EAN y unidades, de Excel', ['7214-90684=12', '7214-92895=5'], $unidades($r));
igual('la columna de las unidades', 2, $r['columna_unidades']);
igual('se guardan como «EAN;unidades»', ['8436000000015;12', '8436000000039;5'], $r['valores']);
$r = KpcLista::buscar(KpcLista::celdas("8436000000015 12\n8436000000039 5 uds"), 'auto', $indice, '', true);
igual('EAN y unidades separados por un espacio (y «5 uds»)', ['7214-90684=12', '7214-92895=5'], $unidades($r));
$r = KpcLista::buscar(KpcLista::celdas("8436000000015;13;12\n8436000000039;12,5 %;5"), 'auto', $indice, 'pct', true);
igual('EAN, su % y sus unidades', ['7214-90684=12', '7214-92895=5'], $unidades($r));
igual('con el % en su sitio', ['7214-90684=13', '7214-92895=12.5'], $valores($r));
$r = KpcLista::buscar(KpcLista::celdas("8436000000015 13 % 12\n8436000000039 12,5 5"), 'auto', $indice, 'pct', true);
igual('en una sola columna: EAN, % y unidades', ['7214-90684=12', '7214-92895=5'], $unidades($r));
igual('y el % se entiende', ['7214-90684=13', '7214-92895=12.5'], $valores($r));
$r = KpcLista::buscar([['EAN', 'Unidades', 'Dto.'], ['8436000000015', '12', '13'], ['8436000000039', '5', '15']], 'auto', $indice, 'pct', true);
igual('con cabeceras manda la cabecera aunque las unidades vayan antes', ['7214-90684=12', '7214-92895=5'], $unidades($r));
igual('y el % también', ['7214-90684=13', '7214-92895=15'], $valores($r));
$r = KpcLista::buscar(KpcLista::celdas("8436000000015\t12\n8436000000039\tmuchas"), 'auto', $indice, '', true);
igual('sin unas unidades que se entiendan, se dice', ['8436000000039 (muchas)'], $r['sin_unidades']);
igual('y el artículo entra sin ellas', ['7214-90684=12', '7214-92895=null'], $unidades($r));
$r = KpcLista::buscar(KpcLista::celdas("8436000000015\t12\n8436000000015\t3"), 'auto', $indice, '', true);
igual('el mismo EAN dos veces (dos lotes): se suman', ['7214-90684=15'], $unidades($r));
$r = KpcLista::buscar(KpcLista::celdas("8436000000015\t12"), 'auto', $indice);
igual('sin pedirlas, no se leen', ['7214-90684=null'], $unidades($r));
igual('leerUnidades: «12», «12,0», «12 ud.», «1.200»; «12,5» y vacío, no', [12, 12, 12, 1200, null, null], [KpcLista::leerUnidades('12'), KpcLista::leerUnidades('12,0'), KpcLista::leerUnidades('12 ud.'), KpcLista::leerUnidades('1.200'), KpcLista::leerUnidades('12,5'), KpcLista::leerUnidades('')]);

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
