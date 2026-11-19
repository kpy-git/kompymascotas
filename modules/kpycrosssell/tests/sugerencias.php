<?php
/**
 * Las reglas del motor que se pueden comprobar sin tienda:
 *   php tests/sugerencias.php
 */
require __DIR__ . '/../classes/KcsDistribuidor.php';
require __DIR__ . '/../classes/KcsSugerencias.php';
require __DIR__ . '/../classes/KcsPistas.php';
require __DIR__ . '/../classes/KcsAntiparasitarios.php';

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

echo "Hasta cuánto puede costar una sugerencia\n";
$ajustes = ['parte' => 0.6, 'tope' => 40.0];
igual('a un pienso de 50 € se le ofrece hasta 30 €', 30.0, KcsSugerencias::techo(50.0, $ajustes));
igual('a uno de 12,74 € hasta 7,64 €', 7.64, KcsSugerencias::techo(12.74, $ajustes));
igual('el tope manda en los caros: 100 € → 40 €', 40.0, KcsSugerencias::techo(100.0, $ajustes));
igual('sin precio, el tope', 40.0, KcsSugerencias::techo(0.0, $ajustes));
igual('con otros ajustes, el 30 % de 50 €', 15.0, KcsSugerencias::techo(50.0, ['parte' => 0.3, 'tope' => 40.0]));

echo "\nA un pienso no se le ofrece otro pienso\n";
foreach (['Comida Seca', 'Dieta Veterinaria Seca'] as $tipo) {
    $ofrece = KcsSugerencias::COMPLEMENTOS[$tipo];
    igual("«{$tipo}» no ofrece comida seca", false, in_array('Comida Seca', $ofrece, true));
    igual("«{$tipo}» ofrece algo", true, $ofrece !== []);
}
igual('al pienso se le ofrece húmedo lo primero', 'Comida Húmeda', KcsSugerencias::COMPLEMENTOS['Comida Seca'][0]);
igual('a la dieta veterinaria, su húmedo', 'Dieta Veterinaria Húmedo', KcsSugerencias::COMPLEMENTOS['Dieta Veterinaria Seca'][0]);

echo "\nTodo lo que se ofrece existe en el catálogo de Kompy\n";
$tiposDeKompy = [
    'Comida Seca', 'Comida Húmeda', 'Comida Semi-Húmeda', 'Snacks', 'Leche', 'Higiene', 'Accesorios',
    'Complemento Nutricional', 'Dieta Veterinaria Seca', 'Dieta Veterinaria Húmedo', 'Antiparasitario',
    'Educación y Adiestramiento', 'Arenas y Suministros para Gatos', 'Regalo',
];
$sueltos = [];
foreach (KcsSugerencias::COMPLEMENTOS as $tipo => $ofrece) {
    foreach (array_merge([$tipo], $ofrece) as $nombre) {
        if (!in_array($nombre, $tiposDeKompy, true)) {
            $sueltos[] = $nombre;
        }
    }
}
foreach (array_merge(KcsSugerencias::CUIDADOS, KcsSugerencias::REFUERZOS, KcsSugerencias::HUMEDOS) as $nombre) {
    if (!in_array($nombre, $tiposDeKompy, true)) {
        $sueltos[] = $nombre;
    }
}
igual('ningún tipo inventado', [], array_values(array_unique($sueltos)));

echo "\nA la comida nunca se le ofrece higiene\n";
foreach (KcsSugerencias::COMIDA as $tipo) {
    if (!isset(KcsSugerencias::COMPLEMENTOS[$tipo])) {
        continue;
    }
    igual("«{$tipo}» no ofrece higiene", false, in_array('Higiene', KcsSugerencias::COMPLEMENTOS[$tipo], true));
}

echo "\nLas patologías no se cruzan\n";
igual('la diabetes es patología', true, in_array('Diabetes', KcsSugerencias::PATOLOGIAS, true));
igual('los problemas renales son patología', true, in_array('Problemas renales', KcsSugerencias::PATOLOGIAS, true));
igual('estar esterilizado no es una enfermedad', false, in_array('Esterilizados/Castrados', KcsSugerencias::PATOLOGIAS, true));
igual('el control del peso tampoco', false, in_array('Control del peso', KcsSugerencias::PATOLOGIAS, true));
igual('lo dental es prevención: un snack dental vale para cualquiera', false, in_array('Problemas orales y dentales', KcsSugerencias::PATOLOGIAS, true));

echo "\nLo que dicen el nombre y la categoría\n";
igual('«Royal Canin» no es un perro', 'Gato', KcsPistas::de('Royal Canin Kitten comida húmeda para gatos')['animal']);
igual('«para perros y gatos» es de los dos', 'ambos', KcsPistas::de("Hill's a/d alimento húmedo para Perros y Gatos")['animal']);
igual('el nombre manda sobre la categoría', 'Gato', KcsPistas::de('Schesir Stix Snack para Gatos', 'Golosinas adiestrar perros')['animal']);
igual('sin nada en el nombre, la categoría', 'Perro', KcsPistas::de('Capa Begard', 'Ropa para perros')['animal']);
igual('u/d es urinario', ['urinario'], KcsPistas::de("Hill's Prescription Diet u/d lata")['enfermedades']);
igual('m/d es diabetes y peso', ['diabetes', 'peso'], KcsPistas::de("Hill's Prescription Diet m/d")['enfermedades']);
igual('Glycobalance es diabetes', ['diabetes'], KcsPistas::de('Royal Canin Glycobalance')['enfermedades']);
igual('un m/d encaja con un r/d (los dos llevan peso)', true, KcsPistas::encajan(KcsPistas::de('m/d'), KcsPistas::de('r/d')));
igual('un pienso sano no encaja con un renal', false, KcsPistas::encajan(KcsPistas::de('Adult Pollo'), KcsPistas::de('Renal lata')));
igual('un cachorro no encaja con un senior', false, KcsPistas::encajan(KcsPistas::de('Puppy Pollo'), KcsPistas::de('Senior 7+')));
igual('el tipo sale del nombre: un snack', 'Snacks', KcsPistas::tipo('Schesir Stix Pollo en Crema Snack para Gatos'));
igual('o de la categoría: un comedero', 'Accesorios', KcsPistas::tipo('Comedero Boss para Perro', 'Comederos de plastico para perros'));
igual('un «snack para gatos» es snack, no comida', 'Snacks', KcsPistas::tipo('Snack húmedo para gatos'));
igual('sin pistas, nada', '', KcsPistas::tipo('Hepatosil Plus Gel', 'Perros'));

echo "\nLas edades no se cruzan\n";
igual('el cachorro es de las edades jóvenes', true, in_array('Cachorro', KcsSugerencias::ETAPAS['joven'], true));
igual('el gatito también', true, in_array('Gatito', KcsSugerencias::ETAPAS['joven'], true));
igual('senior y mature son los mayores', ['Senior', 'Mature'], KcsSugerencias::ETAPAS['mayor']);
$edades = array_merge(KcsSugerencias::ETAPAS['joven'], KcsSugerencias::ETAPAS['adulto'], KcsSugerencias::ETAPAS['mayor']);
igual('ninguna edad en dos etapas a la vez', count($edades), count(array_unique($edades)));

echo "\nCada marca, en un solo distribuidor\n";
$vistas = [];
$repes = [];
foreach (KcsDistribuidor::DE_PARTIDA as $quien => $marcas) {
    foreach ($marcas as $marca) {
        if (isset($vistas[$marca])) {
            $repes[] = $marca;
        }
        $vistas[$marca] = $quien;
    }
}
igual('ninguna marca en dos distribuidores', [], $repes);
igual('Schesir es de Neftys', 'neftys', $vistas['Schesir'] ?? '');
igual('Farmina es de Distrivet', 'distrivet', $vistas['Farmina'] ?? '');
igual('Natural Greatness es de Evolution', 'evolution', $vistas['Natural Greatness'] ?? '');
igual('Royal Canin es del almacén', 'propio', $vistas['Royal Canin'] ?? '');
igual('Boske sale de Neftys, que es su almacén principal', 'neftys', $vistas['Boske'] ?? '');
igual('Pedigree es de Distrivet', 'distrivet', $vistas['Pedigree'] ?? '');

echo "\nLas marcas de reserva son del mismo distribuidor\n";
foreach (KcsDistribuidor::RESERVA as $quien => $porMascota) {
    foreach ($porMascota as $mascota => $marcas) {
        foreach ($marcas as $marca) {
            igual("«{$marca}» ({$mascota}) es de {$quien}", $quien, $vistas[$marca] ?? '');
        }
    }
}

echo "\nPack de seis y cruces cerrados\n";
igual('Boske va en pack de seis', true, in_array('Boske', KcsDistribuidor::EN_PACK_DE_SEIS, true));
igual('Natural Greatness también', true, in_array('Natural Greatness', KcsDistribuidor::EN_PACK_DE_SEIS, true));
igual('Evolution solo se cruza consigo mismo', true, in_array('evolution', KcsDistribuidor::SOLO_LO_SUYO, true));
foreach (KcsDistribuidor::SOLO_LO_SUYO as $quien) {
    igual("«{$quien}» existe como distribuidor", true, isset(KcsDistribuidor::NOMBRES[$quien]));
}

echo "\nAntiparasitarios: forma y peso\n";
igual('el Seresto es collar', 'collar', KcsAntiparasitarios::forma('Collar antiparasitario Seresto'));
igual('AdTab es comprimido', 'comprimido', KcsAntiparasitarios::forma('AdTab Comprimido masticable antiparasitario para perros'));
igual('Advantix es pipeta', 'pipeta', KcsAntiparasitarios::forma('Advantix Monodosis Pipeta Antiparasitaria'));
igual('Frontline Combo es pipeta', 'pipeta', KcsAntiparasitarios::forma('Frontline Combo Spot On Gatos'));
igual('«de 5,5 a 11 Kg»', [5.5, 11.0], KcsAntiparasitarios::rango('de 5,5 a 11 Kg'));
igual('«Pipeta de 1,5 a 4 kg, 3 unidades»', [1.5, 4.0], KcsAntiparasitarios::rango('Pipeta de 1,5 a 4 kg, 3 unidades'));
igual('«Pipeta hasta 4 kg»', [0.0, 4.0], KcsAntiparasitarios::rango('Pipeta hasta 4 kg'));
igual('«Seresto menos de 8 kg»', [0.0, 8.0], KcsAntiparasitarios::rango('Seresto menos de 8 kg'));
igual('«Pack 2 x Seresto más de 8 kg»', [8.0, 200.0], KcsAntiparasitarios::rango('Pack 2 x Seresto más de 8 kg'));
igual('el peso en el nombre: «Perros 10-20 Kg»', [10.0, 20.0], KcsAntiparasitarios::rango('Frontline Combo Spot On Perros 10-20 Kg'));
igual('«+40 Kg»', [40.0, 200.0], KcsAntiparasitarios::rango('Frontline Combo Spot On Perros +40 Kg'));
igual('una talla en centímetros no es un peso', null, KcsAntiparasitarios::rango('S/M (48 cm)'));
igual('«de 5,5 a 11» y «más de 8» se pisan', true, KcsAntiparasitarios::solapan([5.5, 11.0], [8.0, 200.0]));
igual('«hasta 4» y «de 4 a 10» no', false, KcsAntiparasitarios::solapan([0.0, 4.0], [4.0, 10.0]));
igual('sin peso, vale para cualquiera', true, KcsAntiparasitarios::solapan(null, [22.0, 45.0]));

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
