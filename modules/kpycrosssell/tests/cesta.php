<?php
/**
 * Las reglas de la cesta de un solo almacén que se pueden comprobar sin tienda:
 *   php tests/cesta.php
 */
require __DIR__ . '/../classes/KcsDistribuidor.php';
require __DIR__ . '/../classes/KcsSugerencias.php';
require __DIR__ . '/../classes/KcsPistas.php';
require __DIR__ . '/../classes/KcsAlmacenes.php';
require __DIR__ . '/../classes/KcsAlternativas.php';

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

echo "El almacén de referencia: el que cubre más euros\n";
igual('el pienso manda sobre el húmedo', 'DISTRIVET', KcsAlmacenes::referencia([
    ['almacen' => 'DISTRIVET', 'total' => 69.99], ['almacen' => 'NEFTYS', 'total' => 10.74], ['almacen' => 'DISTRIVET', 'total' => 34.50],
]));
igual('dos líneas pequeñas pueden más que una grande si suman más', 'NEFTYS', KcsAlmacenes::referencia([
    ['almacen' => 'DISTRIVET', 'total' => 30.00], ['almacen' => 'NEFTYS', 'total' => 20.00], ['almacen' => 'NEFTYS', 'total' => 15.00],
]));
igual('a igual importe, el de la línea más cara', 'TIENDA', KcsAlmacenes::referencia([
    ['almacen' => 'TIENDA', 'total' => 40.00], ['almacen' => 'NEFTYS', 'total' => 25.00], ['almacen' => 'NEFTYS', 'total' => 15.00],
]));
igual('si todo empata, el primero por nombre (siempre el mismo)', 'DISTRIVET', KcsAlmacenes::referencia([
    ['almacen' => 'NEFTYS', 'total' => 10.00], ['almacen' => 'DISTRIVET', 'total' => 10.00],
]));
igual('tres almacenes: manda el de más euros', 'EVOLUTION', KcsAlmacenes::referencia([
    ['almacen' => 'EVOLUTION', 'total' => 55.00], ['almacen' => 'NEFTYS', 'total' => 30.00], ['almacen' => 'TIENDA', 'total' => 20.00],
]));
igual('sin almacén no hay referencia', '', KcsAlmacenes::referencia([['almacen' => '', 'total' => 10.0]]));

echo "\nEl objeto mínimo que entiende el reparto de Julián\n";
$o = KcsAlmacenes::objeto(649, 90080, 15, 2);
igual('lleva los cuatro campos con ->, las unidades en quantity (Julián, 19/11)', [649, 90080, 15, 2], [$o->id_product, $o->id_product_attribute, $o->id_manufacturer, $o->quantity]);
igual('sin unidades, una', 1, KcsAlmacenes::objeto(649, 90080, 15)->quantity);
igual('y nunca menos de una', 1, KcsAlmacenes::objeto(649, 90080, 15, 0)->quantity);

echo "\nLo que nunca sale de un almacén (el ajuste KCS_CESTA_NUNCA; Rafa, 18/11: «esa categoría nunca va a Evolution Pet»)\n";
igual('el valor de partida: los snacks de Natural Greatness (5576) no salen de Evolution', ['EVOLUTION' => [5576]], KcsAlmacenes::leerVetos('EVOLUTION:5576'));
igual('varias categorías y varios almacenes, con espacios y minúsculas', ['EVOLUTION' => [5576, 5577], 'NEFTYS' => [12]], KcsAlmacenes::leerVetos(' evolution : 5576, 5577 ; neftys:12 '));
igual('también por líneas, y lo repetido una vez', ['EVOLUTION' => [5576, 5577]], KcsAlmacenes::leerVetos("EVOLUTION:5576\nEVOLUTION:5577,5576"));
igual('lo que no se entiende se ignora', [], KcsAlmacenes::leerVetos('5576; EVOLUTION; :12; NEFTYS:cero'));
igual('vacío, nada', [], KcsAlmacenes::leerVetos(''));
igual('y se vuelve a escribir limpio', 'EVOLUTION:5576,5577; NEFTYS:12', KcsAlmacenes::escribirVetos(KcsAlmacenes::leerVetos(' evolution : 5576, 5577 ; neftys:12 ')));

echo "\nLa misma patología, exacta\n";
igual('en comida normal solo cuentan las enfermedades', ['Problemas renales'], KcsAlternativas::patologiasDe(['Esterilizados/Castrados', 'Problemas renales', 'Vitaminas'], false));
igual('sin enfermedad, lista vacía', [], KcsAlternativas::patologiasDe(['Esterilizados/Castrados', 'Control del peso'], false));
igual('en una dieta cuentan todas las necesidades salvo las no clínicas', ['Esterilizados/Castrados', 'Problemas urinarios'], KcsAlternativas::patologiasDe(['Problemas urinarios', 'Vitaminas', 'Esterilizados/Castrados', 'Sabor'], true));
igual('«Control del peso» y «Sobrepeso» son lo mismo', ['Sobrepeso'], KcsAlternativas::patologiasDe(['Control del peso'], true));
igual('ordenadas, para comparar listas', ['Diabetes', 'Sobrepeso'], KcsAlternativas::patologiasDe(['Sobrepeso', 'Diabetes'], true));
igual('renal + cardiaco no es renal', false, KcsAlternativas::patologiasDe(['Problemas renales', 'Problemas cardiacos y corazón'], false) === KcsAlternativas::patologiasDe(['Problemas renales'], false));

echo "\nEl perfil de la línea\n";
$rasgos = ['tipo' => 'Dieta Veterinaria Seca', 'mascota' => 'Perro', 'mascotas' => ['Perro'], 'edad' => 'Adulto', 'tamano' => 'Medium',
    'necesidad' => 'Problemas renales', 'necesidades' => ['Problemas renales'], 'opcion' => '', 'sabor' => '', 'id_product' => 1];
$perfil = KcsAlternativas::perfil($rasgos, "Hill's Prescription Diet k/d Renal para perros");
igual('una dieta renal de perro adulto medium', ['mascotas' => ['Perro'], 'patologias' => ['Problemas renales'], 'todas' => ['Problemas renales'], 'temas' => [], 'dieta' => true, 'edad' => 'Adulto', 'tamano' => 'Medium', 'talla' => 'mediano'], $perfil);
igual('en un snack el tamaño no filtra, pero la talla sí ordena («perros grandes»)', ['', 'grande'], array_values(array_intersect_key(KcsAlternativas::perfil(['tipo' => 'Snacks', 'mascotas' => ['Perro'], 'necesidades' => [], 'edad' => '', 'tamano' => 'Grande'], 'Purina Dentalife perros grandes'), ['tamano' => 1, 'talla' => 1])));
igual('una dieta sin patología no tiene alternativas', null, KcsAlternativas::perfil(['tipo' => 'Dieta Veterinaria Húmedo', 'mascotas' => ['Gato'], 'necesidades' => [], 'edad' => '', 'tamano' => ''], 'Lata'));
igual('de perro y gato, el nombre elige', ['Gato'], KcsAlternativas::perfil(['tipo' => 'Snacks', 'mascotas' => ['Perro', 'Gato'], 'necesidades' => [], 'edad' => '', 'tamano' => ''], 'Schesir Stix snack para gatos')['mascotas']);
igual('de perro y gato sin pista, para los dos', ['Perro', 'Gato'], KcsAlternativas::perfil(['tipo' => 'Snacks', 'mascotas' => ['Perro', 'Gato'], 'necesidades' => [], 'edad' => '', 'tamano' => ''], 'Snack natural de pollo')['mascotas']);
igual('el tamaño solo cuenta en pienso', '', KcsAlternativas::perfil(['tipo' => 'Comida Húmeda', 'mascotas' => ['Perro'], 'necesidades' => [], 'edad' => '', 'tamano' => 'Mini'], 'Lata')['tamano']);
igual('sin mascota no hay perfil', null, KcsAlternativas::perfil(['tipo' => 'Accesorios', 'mascotas' => [], 'necesidades' => [], 'edad' => '', 'tamano' => ''], 'Comedero de acero'));

echo "\nEl tema: para qué es, por el nombre y por la ficha\n";
igual('«Dentalife» es dental aunque la ficha no lo diga', ['dental'], KcsPistas::temas('Purina Dentalife perros grandes'));
igual('«VeggieDent Fresh Snack dental» es dental', ['dental'], KcsPistas::temas('VeggieDent Fresh Snack dental para perros'));
igual('«Premios Bajos en Grasa» es de peso', ['peso'], KcsPistas::temas('Specific CT-H Healthy Treats Premios Bajos en Grasa para Perros'));
igual('la ficha también cuenta', ['calma', 'dental'], KcsPistas::temas('Snack para perros', ['Problemas orales y dentales', 'Estrés y ansiedad']));
igual('«Sterilised» es de esterilizados', ['esterilizado'], KcsPistas::temas('Royal Canin Sterilised 37 para gatos'));
igual('la malta es para las bolas de pelo, y un maltés no', [['bolas'], []], [KcsPistas::temas('Gimcat Malt-Soft pasta de malta'), KcsPistas::temas('Pienso para bichón maltés adulto')]);
igual('un snack de pollo no es de nada en concreto', [], KcsPistas::temas('Snack natural de pollo para perros'));
igual('a un dental, otro dental primero', 0, KcsPistas::ordenPorTema(['dental'], ['dental']));
igual('a un dental, uno genérico después', 1, KcsPistas::ordenPorTema(['dental'], []));
igual('a un dental, uno para adelgazar el último (solo si no hay otra cosa)', 2, KcsPistas::ordenPorTema(['dental'], ['peso']));
igual('a uno genérico, los genéricos primero y los especializados después', [0, 1], [KcsPistas::ordenPorTema([], []), KcsPistas::ordenPorTema([], ['dental'])]);
igual('un snack monoproteico «para alérgicos» es tema alergia, no patología', [['alergia'], []], [KcsPistas::temas('Moments Ibérico Snacks Naturales para Perros', ['Problemas alérgicos e intolerancias']), KcsAlternativas::patologiasDe(['Problemas alérgicos e intolerancias'], false)]);
igual('en un snack renal, la patología sí es exacta', ['Problemas renales'], KcsAlternativas::patologiasDe(['Problemas renales', 'Problemas de pelo y piel'], false));

echo "\nLas dietas: la etiqueta de síntoma no iguala, pero la que coincide del todo va primero\n";
$zd = ['Problemas alérgicos e intolerancias', 'Problemas de pelo y piel'];
igual('z/d «alérgicos + piel» y Piper Allergy «alérgicos» son la misma patología', KcsAlternativas::patologiasDe($zd, true), KcsAlternativas::patologiasDe(['Problemas alérgicos e intolerancias'], true));
igual('pero Skin Support «piel» no es z/d', false, KcsAlternativas::patologiasDe(['Problemas de pelo y piel'], true) === KcsAlternativas::patologiasDe($zd, true));
igual('una dieta solo de piel sí tiene patología (piel)', ['Problemas de pelo y piel'], KcsAlternativas::patologiasDe(['Problemas de pelo y piel'], true));
igual('lo clínico completo distingue a Boske (alérgicos + piel) de Piper (alérgicos)', [true, false], [KcsAlternativas::clinicas($zd, true) === KcsAlternativas::clinicas($zd, true), KcsAlternativas::clinicas(['Problemas alérgicos e intolerancias'], true) === KcsAlternativas::clinicas($zd, true)]);
igual('renal no es renal + cardiaco, tampoco en dietas', false, KcsAlternativas::patologiasDe(['Problemas renales'], true) === KcsAlternativas::patologiasDe(['Problemas renales', 'Problemas cardiacos y corazón'], true));

echo "\nEl formato más parecido: primero los de precio parecido, después los de la banda «como mucho»\n";
$formatos = [
    ['ipa' => 1, 'precio' => 16.23, 'peso' => 2.5],
    ['ipa' => 2, 'precio' => 52.02, 'peso' => 12.27],
    ['ipa' => 3, 'precio' => 50.39, 'peso' => 14.0],
];
igual('a un saco de 66 € (12 kg), el formato de precio más parecido: 52 € y 12,27 kg', [2, true], array_values(array_intersect_key(KcsAlternativas::formatoParecido($formatos, 66.0, 12.0, 0.7, 1.3), ['ipa' => 1, 'parecido' => 1])));
igual('a un pack de latas de 25 € (1,5 kg), un pack de 30 € antes que una lata de 3 €', 12, KcsAlternativas::formatoParecido([
    ['ipa' => 11, 'precio' => 3.0, 'peso' => 0.37], ['ipa' => 12, 'precio' => 30.0, 'peso' => 2.2],
], 25.0, 1.5, 0.7, 1.3)['ipa']);
igual('más barato por kilo vale (es mejor compra)', 12, KcsAlternativas::formatoParecido([
    ['ipa' => 12, 'precio' => 20.0, 'peso' => 4.0],
], 25.0, 1.5, 0.7, 1.3)['ipa']);
igual('más caro por kilo que el máximo, no: 25 € por 0,5 kg frente a 25 € por 1,5 kg', null, KcsAlternativas::formatoParecido([
    ['ipa' => 13, 'precio' => 25.0, 'peso' => 0.5],
], 25.0, 1.5, 0.7, 1.3));
igual('si el más parecido en precio se pasa por kilo, el siguiente que cumpla', 15, KcsAlternativas::formatoParecido([
    ['ipa' => 14, 'precio' => 26.0, 'peso' => 0.5], ['ipa' => 15, 'precio' => 20.0, 'peso' => 1.2],
], 25.0, 1.5, 0.7, 1.3)['ipa']);
igual('fuera de la banda de precio parecido y sin banda «como mucho», nada', null, KcsAlternativas::formatoParecido($formatos, 20.0, 12.0, 0.7, 1.3));
igual('sin peso, el precio más parecido', 7, KcsAlternativas::formatoParecido([
    ['ipa' => 6, 'precio' => 4.0, 'peso' => 0.0], ['ipa' => 7, 'precio' => 9.5, 'peso' => 0.0],
], 10.0, 0.0, 0.7, 1.3)['ipa']);
igual('sin peso y fuera de la banda, nada', null, KcsAlternativas::formatoParecido([['ipa' => 6, 'precio' => 4.0, 'peso' => 0.0]], 10.0, 0.0, 0.7, 1.3));
igual('la distancia es relativa al precio de la línea', 0.1, KcsAlternativas::formatoParecido([['ipa' => 8, 'precio' => 11.0, 'peso' => 0.0]], 10.0, 0.0, 0.7, 1.3)['distancia']);
igual('sin precio de línea, nada', null, KcsAlternativas::formatoParecido($formatos, 0.0, 12.0, 0.7, 1.3));
igual('sin precio del formato (no se sabe), no cuenta', null, KcsAlternativas::formatoParecido([['ipa' => 9, 'precio' => 0.0, 'peso' => 1.0]], 10.0, 1.0, 0.7, 1.3, 0.25, 4.0));
// La banda «como mucho» (Rafa, 18/11): a un snack dental de 3,29 €, el VeggieDent de 9,89 € antes que nada.
igual('a un snack de 3,29 €, uno de 9,89 € entra por la banda «como mucho», marcado como no parecido', [21, false], array_values(array_intersect_key(KcsAlternativas::formatoParecido([
    ['ipa' => 21, 'precio' => 9.89, 'peso' => 0.12],
], 3.29, 0.142, 0.7, 1.3, 0.25, 4.0), ['ipa' => 1, 'parecido' => 1])));
igual('pero no uno de 18 € (más del 400 %)', null, KcsAlternativas::formatoParecido([['ipa' => 22, 'precio' => 18.0, 'peso' => 0.1]], 3.29, 0.142, 0.7, 1.3, 0.25, 4.0));
igual('si hay uno parecido y otro más cercano pero lejano por kilo, gana el parecido', [31, true], array_values(array_intersect_key(KcsAlternativas::formatoParecido([
    ['ipa' => 30, 'precio' => 24.0, 'peso' => 0.3], ['ipa' => 31, 'precio' => 30.0, 'peso' => 2.0],
], 25.0, 1.5, 0.7, 1.3, 0.25, 4.0), ['ipa' => 1, 'parecido' => 1])));
igual('en la banda «como mucho» la red por kilo es a esa escala: 24 € por 0,4 kg (60 €/kg) entra como no parecido', [30, false], array_values(array_intersect_key(KcsAlternativas::formatoParecido([
    ['ipa' => 30, 'precio' => 24.0, 'peso' => 0.4],
], 25.0, 1.5, 0.7, 1.3, 0.25, 4.0), ['ipa' => 1, 'parecido' => 1])));
igual('y 24 € por 0,3 kg (80 €/kg, más del 400 % por kilo), ni así', null, KcsAlternativas::formatoParecido([
    ['ipa' => 30, 'precio' => 24.0, 'peso' => 0.3],
], 25.0, 1.5, 0.7, 1.3, 0.25, 4.0));
igual('la banda «como mucho» nunca estrecha la de parecido', 41, KcsAlternativas::formatoParecido([['ipa' => 41, 'precio' => 8.0, 'peso' => 0.0]], 10.0, 0.0, 0.7, 1.3, 0.9, 1.1)['ipa']);
// Hasta 10 € más, lo que sea mayor (Rafa, 18/11, Moments a 1,55 €): el VeggieDent de 8,99 € entra; uno de 12 € no.
igual('a un snack de 1,55 €, el 400 % son 6 €, pero con 10 € más entra uno de 8,99 €', [61, false], array_values(array_intersect_key(KcsAlternativas::formatoParecido([
    ['ipa' => 61, 'precio' => 8.99, 'peso' => 0.0],
], 1.55, 0.0, 0.7, 1.3, 0.25, 4.0, '', 10.0), ['ipa' => 1, 'parecido' => 1])));
igual('y uno de 12 € ya no', null, KcsAlternativas::formatoParecido([['ipa' => 62, 'precio' => 12.0, 'peso' => 0.0]], 1.55, 0.0, 0.7, 1.3, 0.25, 4.0, '', 10.0));
igual('en un saco de 50 € mandan los porcentajes (200 €), no los 10 €', 63, KcsAlternativas::formatoParecido([['ipa' => 63, 'precio' => 150.0, 'peso' => 0.0]], 50.0, 0.0, 0.7, 1.3, 0.25, 4.0, '', 10.0)['ipa']);
// El mismo precio por kilo en otro tamaño (Rafa, 18/11, Hill's z/d lata de 370 g frente al pack de tarrinas de Specific).
igual('a una lata de 4,70 € (370 g), un pack de 26,63 € (1,8 kg) al mismo precio por kilo entra como lejano', [71, false], array_values(array_intersect_key(KcsAlternativas::formatoParecido([
    ['ipa' => 71, 'precio' => 26.63, 'peso' => 1.8],
], 4.70, 0.37, 0.7, 1.3, 0.25, 4.0, '', 10.0), ['ipa' => 1, 'parecido' => 1])));
igual('pero no si por kilo es mucho más caro', null, KcsAlternativas::formatoParecido([['ipa' => 72, 'precio' => 26.63, 'peso' => 0.9]], 4.70, 0.37, 0.7, 1.3, 0.25, 4.0, '', 10.0));
igual('sin peso en la línea (un snack), no hay camino por kilo', null, KcsAlternativas::formatoParecido([['ipa' => 73, 'precio' => 26.63, 'peso' => 1.8]], 4.70, 0.0, 0.7, 1.3, 0.25, 4.0, '', 10.0));

echo "\nLa talla del perro, por la ficha, el nombre o el formato\n";
igual('la ficha manda: «Grande»', 'grande', KcsPistas::talla('Snack', 'Grande'));
igual('«Mini» es pequeño; «Todos los tamaños» no dice nada', ['pequeño', ''], [KcsPistas::talla('', 'Mini'), KcsPistas::talla('Snack', 'Todos los tamaños')]);
igual('«perros grandes» en el nombre', 'grande', KcsPistas::talla('Purina Dentalife perros grandes'));
igual('los formatos de VeggieDent: XS, S, M, L', ['pequeño', 'pequeño', 'mediano', 'grande'], [KcsPistas::talla('XS - Menos de 5 kg'), KcsPistas::talla('S - 5 a 10 kg'), KcsPistas::talla('M - 10 a 30 kg'), KcsPistas::talla('L - Más de 30 kg')]);
igual('«Botella de 2 l» no es una talla', '', KcsPistas::talla('Botella de 2 l'));
igual('«Mature Adult Large Breed» es grande', 'grande', KcsPistas::talla("Hill's Science Plan Mature Adult Large Breed con pollo"));
igual('«Saco de 15 Kg» no dice talla', '', KcsPistas::talla('Saco de 15 Kg'));
igual('si dice dos tallas, ninguna', '', KcsPistas::talla('Mini & Medium'));
igual('entre formatos, primero el de su talla aunque el otro esté más cerca en precio', 52, KcsAlternativas::formatoParecido([
    ['ipa' => 51, 'precio' => 9.0, 'peso' => 0.0, 'talla' => 'pequeño'], ['ipa' => 52, 'precio' => 11.5, 'peso' => 0.0, 'talla' => 'grande'],
], 10.0, 0.0, 0.7, 1.3, 0.25, 4.0, 'grande')['ipa']);
igual('si solo hay otra talla, esa (mejor que nada)', 51, KcsAlternativas::formatoParecido([
    ['ipa' => 51, 'precio' => 9.0, 'peso' => 0.0, 'talla' => 'pequeño'],
], 10.0, 0.0, 0.7, 1.3, 0.25, 4.0, 'grande')['ipa']);
igual('sin talla en la línea, el precio manda', 51, KcsAlternativas::formatoParecido([
    ['ipa' => 51, 'precio' => 9.0, 'peso' => 0.0, 'talla' => 'pequeño'], ['ipa' => 52, 'precio' => 11.5, 'peso' => 0.0, 'talla' => 'grande'],
], 10.0, 0.0, 0.7, 1.3)['ipa']);

echo "\nVariedad en las tarjetas a la vista\n";
$f = static fn (int $id, int $marca, int $tema = 0, bool $parecido = true): array => ['id_product' => $id, 'id_manufacturer' => $marca, 'tema' => $tema, 'parecido' => $parecido];
$ids = static fn (array $l): array => array_column($l, 'id_product');
igual('siete Farminas y una Lenda: la Lenda entra en la tercera tarjeta y el resto sigue igual', [1, 2, 8, 3, 4, 5, 6, 7], $ids(KcsAlternativas::variar([
    $f(1, 238), $f(2, 238), $f(3, 238), $f(4, 238), $f(5, 238), $f(6, 238), $f(7, 238), $f(8, 92),
], 3)));
igual('si la otra marca es de precio lejano, no adelanta a una parecida', [1, 2, 3, 8], $ids(KcsAlternativas::variar([
    $f(1, 238), $f(2, 238), $f(3, 238), $f(8, 92, 0, false),
], 3)));
igual('si la otra marca es de otro tema (genérica), tampoco', [1, 2, 3, 8], $ids(KcsAlternativas::variar([
    $f(1, 238), $f(2, 238), $f(3, 238), $f(8, 92, 1),
], 3)));
igual('con marcas variadas no cambia nada', [1, 2, 3, 4], $ids(KcsAlternativas::variar([$f(1, 1), $f(2, 2), $f(3, 3), $f(4, 1)], 3)));
igual('con menos que tarjetas, igual', [1, 2], $ids(KcsAlternativas::variar([$f(1, 1), $f(2, 1)], 3)));
igual('sin nada, nada', [], KcsAlternativas::variar([], 3));

echo "\nLos tipos a los que se ofrece cambio\n";
igual('antiparasitarios, nunca', false, in_array('Antiparasitario', KcsAlternativas::TIPOS, true));
igual('las dietas, sí (Rafa, 16/11)', true, in_array('Dieta Veterinaria Seca', KcsAlternativas::TIPOS, true) && in_array('Dieta Veterinaria Húmedo', KcsAlternativas::TIPOS, true));
igual('el tamaño del perro cuenta en pienso y en dieta seca', ['Comida Seca', 'Dieta Veterinaria Seca'], KcsAlternativas::PIENSOS);

echo $fallos === 0 ? "\nTodo bien.\n" : "\n{$fallos} fallos.\n";
exit($fallos === 0 ? 0 : 1);
