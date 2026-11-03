<?php
/**
 * Las etiquetas: el número que anuncian, qué sale en su ficha y en los listados, el chip de cada
 * formato y qué va con qué. Sin tienda (la regla es pura):
 *   php -n -d extension_dir=… -d extension=mbstring tests/flags.php
 */
require __DIR__ . '/../classes/KpcFlags.php';
require __DIR__ . '/../classes/KpcElegibles.php';   // el % escrito («10», «12,5»)
require __DIR__ . '/../classes/KpcCupo.php';        // los nombres del Special Price («Liquidación», «Outlet»)

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
/** clave => texto, para leer de un vistazo lo que saldría sobre la foto. */
function textos(array $flags): array
{
    $s = [];
    foreach ($flags as $k => $f) {
        $s[$k] = $f['label'];
    }

    return $s;
}

echo "El % de la etiqueta va del precio habitual al de hoy\n";
igual('de 80 € a 68 € es un 15 %', 15, KpcFlags::pct(80.0, 68.0));
igual('de 80 € a 60 € es un 25 %', 25, KpcFlags::pct(80.0, 60.0));
igual('de 107,98 € a 105,28 € es un 3 %', 3, KpcFlags::pct(107.98, 105.28));
// Si el PVPR son 100 € y el descuento de siempre el 20 %, el habitual son 80 €: el 15 % se anuncia
// contra esos 80 €, no contra el PVPR (contra el PVPR serían 32 %, y ese número no es anunciable).

echo "\nCuándo no hay etiqueta\n";
igual('la promo terminó: el precio volvió al habitual', 0, KpcFlags::pct(80.0, 80.0));
igual('el precio de hoy está por encima del habitual', 0, KpcFlags::pct(75.98, 105.67));
igual('un céntimo no es una promoción', 0, KpcFlags::pct(84.98, 84.91));
igual('sin precios no se inventa nada', 0, KpcFlags::pct(0.0, 0.0));

echo "\nColores, los mismos que la ficha (contrato con kpypromolanding)\n";
igual('Special Price en crema, como su etiqueta', '#FFEAB7', KpcFlags::COLORES['kpc-especial']);
igual('cupón en lila', '#7B5CB0', KpcFlags::COLORES['kpc-cupon']);
igual('2ª unidad en verde', '#2F6B34', KpcFlags::COLORES['kpc-segunda']);
igual('regalo en violeta', '#7F4AFF', KpcFlags::COLORES['kpc-regalo']);
igual('Liquidación, Outlet y Bajada de precio con los suyos', ['#B3261E', '#3A3A3A', '#C23A2C'], [KpcFlags::COLORES['kpc-liquidacion'], KpcFlags::COLORES['kpc-outlet'], KpcFlags::COLORES['kpc-bajada']]);

// Lo que `porFormato()` sabría de un Boske con tres formatos: el 12 kg (92467) con Special Price y
// regalo, el 3 kg (92468) con 2ª unidad, el pack (92469) con cupón. El Special Price no está aquí
// (lo lleva la tabla de precios); el regalo va en la clave «formato-regalo».
$cupon = ['tipo' => 'cupon', 'porcentaje' => 10.0, 'codigo' => 'BOSKE10', 'hasta' => '2026-10-31 23:59:59', 'id' => 1];
$segunda = ['tipo' => 'segunda_unidad', 'porcentaje' => 30.0, 'codigo' => 'BOSKE2UD', 'hasta' => null, 'id' => 3];
$regalo = ['tipo' => 'regalo', 'porcentaje' => 0.0, 'codigo' => 'REGALO-BOSKE', 'hasta' => null, 'id' => 40, 'etiqueta' => '+ 3 kg gratis', 'clave' => 92467];
$boske = [92468 => $segunda, '92467-regalo' => $regalo, 92469 => $cupon];

echo "\nLa etiqueta de cada promoción, con su kpc\n";
$e = KpcFlags::deLaPromo($cupon);
igual('cupón: «Código −10 %», lila, texto blanco', ['kpc-cupon', 'Código −10 %', '#7B5CB0', '#ffffff'], [$e['type'], $e['label'], $e['background'], $e['color']]);
igual('con el campo kpc: tipo, promoción y %', ['tipo' => 'cupon', 'id_kpy_coupon' => 1, 'pct' => 10.0], $e['kpc']);
$e = KpcFlags::deLaPromo($segunda);
igual('2ª unidad: «2ª ud. −30 %», verde', ['kpc-segunda', '2ª ud. −30 %', '#2F6B34', ['tipo' => 'segunda_unidad', 'id_kpy_coupon' => 3, 'pct' => 30.0]], [$e['type'], $e['label'], $e['background'], $e['kpc']]);
$e = KpcFlags::deLaPromo($regalo);
igual('regalo: lo que diga su etiqueta, violeta, pct 0', ['kpc-regalo', '+ 3 kg gratis', '#7F4AFF', ['tipo' => 'regalo', 'id_kpy_coupon' => 40, 'pct' => 0.0]], [$e['type'], $e['label'], $e['background'], $e['kpc']]);
igual('un regalo sin etiqueta dice «+ regalo»', '+ regalo', KpcFlags::deLaPromo(['tipo' => 'regalo', 'id' => 41])['label']);
igual('el % se escribe sin decimales de más: 12,5', 'Código −12,5 %', KpcFlags::deLaPromo(['tipo' => 'cupon', 'porcentaje' => 12.5, 'id' => 2])['label']);

echo "\nQué se anuncia sobre la foto: en su ficha, la del formato elegido; fuera, la que manda\n";
igual('en su ficha, el 12 kg: solo su regalo (la 2ª unidad es del 3 kg, el cupón del pack)', [$regalo], KpcFlags::elegir($boske, 92467, true));
igual('en su ficha, el 3 kg: su 2ª unidad', [$segunda], KpcFlags::elegir($boske, 92468, true));
igual('en su ficha, el pack: su cupón', [$cupon], KpcFlags::elegir($boske, 92469, true));
igual('en su ficha, un formato sin nada: nada (ni la de otro formato)', [], KpcFlags::elegir($boske, 92466, true));
igual('en su ficha, un formato con cupón y regalo: los dos, el cupón primero', [$cupon, $regalo], KpcFlags::elegir([92467 => $cupon, '92467-regalo' => $regalo], 92467, true));
igual('un producto sin combinaciones va por su clave negativa', [$cupon, $regalo], KpcFlags::elegir([-8299 => $cupon, '-8299-regalo' => $regalo], -8299, true));
igual('en el listado: una, la que manda (la primera en orden de mando)', [$segunda], KpcFlags::elegir($boske, 92467, false));
igual('en el listado, un cupón o una 2ª unidad antes que un regalo aunque el regalo vaya primero', [$cupon], KpcFlags::elegir(['92467-regalo' => $regalo, 92469 => $cupon], 0, false));
igual('en el listado, el regalo solo si es lo único', [$regalo], KpcFlags::elegir(['92467-regalo' => $regalo], 0, false));
igual('sin promociones, nada', [], KpcFlags::elegir([], 0, false));
// Revisión del 26/10/2026: un cupón por canal (`solo_ficha`) no se anuncia fuera de su ficha.
$porCanal = $cupon + ['solo_ficha' => true];
igual('un cupón por canal, en su ficha: sale', [$porCanal], KpcFlags::elegir([92469 => $porCanal], 92469, true));
igual('un cupón por canal, en un listado (la portada, Doofinder): nunca', [], KpcFlags::elegir([92469 => $porCanal], 0, false));
igual('en el listado, con un cupón por canal y una 2ª unidad: manda la 2ª unidad', [$segunda], KpcFlags::elegir([92469 => $porCanal, 92468 => $segunda], 0, false));

echo "\nEl chip de la tarjeta de cada formato (deFormato)\n";
$c = KpcFlags::chip($boske, 92469);
igual('formato con cupón: «Cupón −10 %» lila, con su kpc', ['kpc-cupon', 'Cupón −10 %', '#7B5CB0', '#ffffff', ['tipo' => 'cupon', 'id_kpy_coupon' => 1, 'pct' => 10.0]], [$c['type'], $c['label'], $c['background'], $c['color'], $c['kpc']]);
igual('formato con 2ª unidad: «2ª ud. −30 %» verde', ['kpc-segunda', '2ª ud. −30 %'], [KpcFlags::chip($boske, 92468)['type'], KpcFlags::chip($boske, 92468)['label']]);
igual('formato con solo regalo (y Special Price): el regalo', ['kpc-regalo', '+ 3 kg gratis'], [KpcFlags::chip($boske, 92467)['type'], KpcFlags::chip($boske, 92467)['label']]);
igual('formato con cupón y regalo: el cupón (un formato lleva una sola promoción de cesta)', 'Cupón −10 %', KpcFlags::chip([92467 => $cupon, '92467-regalo' => $regalo], 92467)['label']);
igual('formato con solo Special Price (no está en el reparto de cesta): nada, el tema ya pinta «Precio especial»', null, KpcFlags::chip($boske, 92466));
igual('producto sin promociones: nada', null, KpcFlags::chip([], 92467));
foreach ([92467, 92468, 92469] as $clave) {
    igual("el chip del $clave cabe en la tarjeta (14 caracteres como mucho)", true, mb_strlen(KpcFlags::chip($boske, $clave)['label']) <= KpcFlags::CHIP_MAX);
}
igual('un regalo largo se acorta: «+ 6 latas gratis» → «+ 6 latas»', '+ 6 latas', KpcFlags::chip(['1-regalo' => ['tipo' => 'regalo', 'id' => 42, 'etiqueta' => '+ 6 latas gratis']], 1)['label']);
igual('y si ni así cabe, «+ regalo»', '+ regalo', KpcFlags::corto('+ 12 bandejas grandes gratis'));
igual('«+ 400 g gratis» cabe tal cual', '+ 400 g gratis', KpcFlags::corto('+ 400 g gratis'));
igual('«2ª ud. −12,5 %» cabe justo', '2ª ud. −12,5 %', KpcFlags::corto('2ª ud. −12,5 %'));
// El chip con el nombre de campaña (3.2.0): entero si cabe; si no, sin el % (antes se cortaba: «Cupón Black −1»).
igual('«Cupón Black −10 %» no cabe: «Cupón Black»', 'Cupón Black', KpcFlags::cortoCampana('Cupón Black −10 %'));
igual('«Black Friday −25 %» → «Black Friday»; «2ª ud. Black −40 %» → «2ª ud. Black»', ['Black Friday', '2ª ud. Black'], [KpcFlags::cortoCampana('Black Friday −25 %'), KpcFlags::cortoCampana('2ª ud. Black −40 %')]);
igual('«Reyes −10 %» cabe entero; con guion normal y decimales también se entiende', ['Reyes −10 %', 'Cupón Cyber'], [KpcFlags::cortoCampana('Reyes −10 %'), KpcFlags::cortoCampana('Cupón Cyber -12,5 %')]);
igual('un nombre larguísimo sin % se corta como siempre (cabe en el chip)', true, mb_strlen(KpcFlags::cortoCampana('Campaña de la casa larguísima')) <= KpcFlags::CHIP_MAX);

echo "\nLa Special Price que manda kpyspecialprices, vestida con la nuestra\n";
$deJulian = ['type' => 'kpy-special-price', 'label' => 'Special price', 'icon' => '/modules/kpyspecialprices/views/img/special-price.svg'];
$e = KpcFlags::etiquetaDeEspecial('', 25, 10.0);
igual('sin nombre propio: «Special Price» en crema con texto rojo, kpc de su promoción', ['kpc-especial', 'Special Price', '#FFEAB7', '#B8322A', ['tipo' => 'special_price', 'id_kpy_coupon' => 25, 'pct' => 10.0]], [$e['type'], $e['label'], $e['background'], $e['color'], $e['kpc']]);
$v = KpcFlags::vestirEspecial($deJulian, $e);
igual('vestida: conserva el type y el icono de kpyspecialprices (su hoja pinta la crema), con nuestro nombre y kpc', ['kpy-special-price', 'Special Price', true, 25], [$v['type'], $v['label'], isset($v['icon']), $v['kpc']['id_kpy_coupon']]);
igual('y sin fondo en línea (no se pisa su hoja)', false, isset($v['background']));
$e = KpcFlags::etiquetaDeEspecial('liquidacion', 91, 25.0);
igual('«Liquidación»: rojo oscuro, texto blanco', ['kpc-liquidacion', 'Liquidación', '#B3261E', '#ffffff'], [$e['type'], $e['label'], $e['background'], $e['color']]);
$v = KpcFlags::vestirEspecial($deJulian, $e);
igual('vestida con nombre propio: pasa a nuestro type y color, y se queda el icono', ['kpc-liquidacion', 'Liquidación', '#B3261E', '#ffffff', true, ['tipo' => 'special_price', 'id_kpy_coupon' => 91, 'pct' => 25.0]], [$v['type'], $v['label'], $v['background'], $v['color'], isset($v['icon']), $v['kpc']]);
$e = KpcFlags::etiquetaDeEspecial('outlet', 105, 20.0);
igual('«Outlet»: gris oscuro', ['kpc-outlet', 'Outlet', '#3A3A3A'], [$e['type'], $e['label'], $e['background']]);
igual('«Bajada de precio»: el rojo de la ficha', ['kpc-bajada', 'Bajada de precio', '#C23A2C'], [($e = KpcFlags::etiquetaDeEspecial('bajada', 7, 15.0))['type'], $e['label'], $e['background']]);
igual('un Special Price cargado por SQL, sin promoción: kpc con id 0', 0, KpcFlags::etiquetaDeEspecial('', 0, 8.0)['kpc']['id_kpy_coupon']);

echo "\nLo que llega de los demás módulos, puesto en orden (componer)\n";
$manuales = [
    'bajadadeprecio' => ['type' => 'bajadadeprecio', 'label' => 'Bajada de Precio'],
    'topventas' => ['type' => 'topventas', 'label' => 'Top Ventas'],
    'ahorroxl' => ['type' => 'ahorroxl', 'label' => 'Ahorro XL'],
    'new' => ['type' => 'new', 'label' => 'Nuevo'],
    'out_of_stock' => ['type' => 'out_of_stock', 'label' => 'Agotado'],
];
$especial = KpcFlags::vestirEspecial($deJulian, KpcFlags::etiquetaDeEspecial('', 25, 10.0));
$f = KpcFlags::componer(['kpy_special_price' => $deJulian] + $manuales, $especial, [KpcFlags::deLaPromo($regalo)], true);
igual('en su ficha con Special Price y regalo: Special Price, el regalo, Top Ventas, Nuevo y agotado al final; fuera lo que habla de precio',
    ['kpy_special_price' => 'Special Price', 'kpc_0' => '+ 3 kg gratis', 'topventas' => 'Top Ventas', 'new' => 'Nuevo', 'out_of_stock' => 'Agotado'], textos($f));
$f = KpcFlags::componer($manuales, null, [KpcFlags::deLaPromo($segunda)], true);
igual('en su ficha sin Special Price: la 2ª unidad primero, «Ahorro XL» y «Bajada de Precio» se quedan, agotado al final',
    ['kpc_0' => '2ª ud. −30 %', 'bajadadeprecio' => 'Bajada de Precio', 'topventas' => 'Top Ventas', 'ahorroxl' => 'Ahorro XL', 'new' => 'Nuevo', 'out_of_stock' => 'Agotado'], textos($f));
$f = KpcFlags::componer($manuales, null, [KpcFlags::deLaPromo($segunda)], false);
igual('en el listado, «Ahorro XL» fuera', false, isset($f['ahorroxl']));
igual('en el listado, lo demás igual', ['kpc_0', 'bajadadeprecio', 'topventas', 'new', 'out_of_stock'], array_keys($f));
$f = KpcFlags::componer([], null, [KpcFlags::deLaPromo($cupon), KpcFlags::deLaPromo($regalo)], true);
igual('dos nuestras van como kpc_0 y kpc_1', ['kpc_0' => 'Código −10 %', 'kpc_1' => '+ 3 kg gratis'], textos($f));
igual('con Special Price que no rebaja de verdad (null) no sale ni ella ni se quita nada', ['topventas', 'new'], array_keys(KpcFlags::componer(['kpy_special_price' => $deJulian, 'topventas' => $manuales['topventas'], 'new' => $manuales['new']], null, [], false)));

echo "\nUna fila vieja de la tabla de kpyproductflags con nuestro texto se tira (no sale dos veces)\n";
$viejas = [
    'cdigo10%' => ['type' => 'cdigo10%', 'label' => 'Código −10 %', 'color' => '#ffffff', 'background' => '#7B5CB0'],
    '2ud.30%' => ['type' => '2ud.30%', 'label' => '2ª ud. −30 %', 'color' => '#ffffff', 'background' => '#2F6B34'],
    '+3kggratis' => ['type' => '+3kggratis', 'label' => '+ 3 kg gratis', 'color' => '#ffffff', 'background' => '#7F4AFF'],
    '+regalo' => ['type' => '+regalo', 'label' => '+ regalo', 'color' => '#ffffff', 'background' => '#7F4AFF'],
    'topventas' => $manuales['topventas'],
];
$f = KpcFlags::componer($viejas, null, [KpcFlags::deLaPromo($regalo)], true);
igual('solo queda la calculada y Top Ventas', ['kpc_0' => '+ 3 kg gratis', 'topventas' => 'Top Ventas'], textos($f));
igual('sin promoción calculada, las viejas tampoco salen', ['topventas' => 'Top Ventas'], textos(KpcFlags::componer($viejas, null, [], false)));

echo "\nLas nuestras, por su texto (la red de seguridad)\n";
igual('«2ª ud. −30 %» es la 2ª unidad', 'kpc-segunda', KpcFlags::tipoPromo('2ª ud. −30 %'));
igual('«Código −10 %» es el cupón', 'kpc-cupon', KpcFlags::tipoPromo('Código −10 %'));
igual('«+ 3 kg gratis» y «+ regalo» son el regalo', ['kpc-regalo', 'kpc-regalo'], [KpcFlags::tipoPromo('+ 3 kg gratis'), KpcFlags::tipoPromo('+ regalo')]);
igual('«Top Ventas» no es nuestra', null, KpcFlags::tipoPromo('Top Ventas'));
igual('«Oferta −15 %» (la de antes de la 1.6.1) no es de cupón', null, KpcFlags::tipoPromo('Oferta −15 %'));
igual('«kg Gratis» (la manual de Julián) no es nuestra', null, KpcFlags::tipoPromo('kg Gratis'));

echo "\nQué puede ir junto a «Special Price» (Rafa, 30/09/2026)\n";
igual('Top Ventas sí (kpyproductflags la manda como «topventas»)', true, KpcFlags::vaConElEspecial('topventas', ['type' => 'topventas', 'label' => 'Top Ventas']));
igual('Nuevo sí (la del core)', true, KpcFlags::vaConElEspecial('new', ['type' => 'new', 'label' => 'Nuevo']));
igual('agotado sí', true, KpcFlags::vaConElEspecial('out_of_stock', ['type' => 'out_of_stock', 'label' => 'Agotado']));
igual('Innovación sí, aunque llegue sin tilde', true, KpcFlags::vaConElEspecial('innovacin', ['type' => 'innovacin', 'label' => 'Innovación']));
igual('Bajada de Precio no', false, KpcFlags::vaConElEspecial('bajadadeprecio', ['type' => 'bajadadeprecio', 'label' => 'Bajada de Precio']));
igual('Ahorro XL no', false, KpcFlags::vaConElEspecial('ahorroxl', ['type' => 'ahorroxl', 'label' => 'Ahorro XL']));
igual('la rebaja de PrestaShop no', false, KpcFlags::vaConElEspecial('on-sale', ['type' => 'on-sale', 'label' => '¡En oferta!']));

echo "\nLo que solo sale en la ficha de su producto (Rafa, 07/10/2026)\n";
igual('Ahorro XL, solo en su ficha (kpyproductflags la manda como «ahorroxl»)', true, KpcFlags::soloEnSuFicha('ahorroxl', ['type' => 'ahorroxl', 'label' => 'Ahorro XL']));
igual('Top Ventas sale también en los listados', false, KpcFlags::soloEnSuFicha('topventas', ['type' => 'topventas', 'label' => 'Top Ventas']));
igual('la de cupón sale también en los listados', false, KpcFlags::soloEnSuFicha('kpc_0', ['type' => 'kpc-cupon', 'label' => 'Código −10 %']));

// Lo que pregunta a la tienda (`ajustar`, `deFormato`, `pctEspecialDe`, `limpiarTablasAjenas`) se
// comprueba en pruebas, con el guion de servidor.

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
