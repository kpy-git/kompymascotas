<?php
/**
 * La ficha, sin tienda: qué bloque se pinta y qué precio se presenta para un formato según lo que
 * lleve (cupón puesto o sin poner, «para todos», robot; 2ª unidad con 1, 2 y 3 unidades; regalo
 * elegido o no, con kilos o con otra cosa; Liquidación con cupo, pocas unidades y agotado), los
 * textos («Quedan N», «Termina mañana»), los nombres cortos y la capa de campaña. Es la regla de
 * `KpcFicha::modelo()`, pura (`docs/plan-desacoplar-kpycoupons.md`, 3.2 y 3.4):
 *   php -n -d extension_dir=… -d extension=mbstring tests/ficha.php
 */
require __DIR__ . '/../classes/KpcFicha.php';
require __DIR__ . '/../classes/KpcEnvase.php';
require __DIR__ . '/../classes/KpcElegibles.php';   // el % escrito («10», «12,5»)
require __DIR__ . '/../classes/KpcFlags.php';       // los colores (la franja violeta de la tarjeta)

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
function contiene(string $que, string $trozo, string $texto): void
{
    global $fallos;
    if (mb_strpos($texto, $trozo) !== false) {
        echo "  ok  $que\n";

        return;
    }
    ++$fallos;
    echo "  MAL $que\n      falta:  " . $trozo . "\n      en:     " . $texto . "\n";
}
$euros = static fn (float $x): string => number_format($x, 2, ',', '.') . ' €';
$hoy = strtotime('2026-10-23 10:00:00');
$peticion = static fn (array $mas = []): array => $mas + ['kpy_regalo' => 0, 'cantidad' => 1, 'robot' => false, 'hoy' => $hoy];
$textos = [];

// Boske 12 kg: PVPR 69,99, habitual 63,99 (old_discount 0,0857), Special Price 57,59 hasta el 05/11.
$leido = KpcEnvase::leer('Saco de 12 Kg', 12.0);
$saco12 = [
    'ipa' => 92467, 'id_attribute' => 30, 'nombre' => 'Saco de 12 Kg', 'corto' => $leido['corto'], 'precio' => 57.59, 'pvpr' => 69.99, 'habitual' => 63.99,
    'especial' => ['old_discount' => 0.0857, 'expira' => '2026-11-05 23:59:59'],
    'gramos' => $leido['gramos'], 'unidades' => $leido['unidades'], 'envase' => $leido['envase'], 'litros' => $leido['litros'],
    'hermanos' => [92467 => '12 kg', 92468 => '3 kg', 92469 => '2 x 12 kg'],
];
// Eukanuba 15 kg sin Special Price: habitual = precio 63,99, PVPR 69,99.
$saco15 = ['ipa' => 3469, 'nombre' => 'Saco de 15 Kg', 'corto' => '15 kg', 'precio' => 63.99, 'pvpr' => 69.99, 'habitual' => null, 'especial' => null,
    'gramos' => 15000.0, 'unidades' => null, 'envase' => '', 'litros' => false, 'hermanos' => [3469 => '15 kg', 3468 => '3 kg']];
// Latas: pack 24 x 85 g a 21,60 € (0,90 €/lata).
$latas = ['ipa' => 500, 'nombre' => 'Pack 24 Latas 85 gr', 'corto' => '24 x 85 g', 'precio' => 21.60, 'pvpr' => 24.00, 'habitual' => null, 'especial' => null,
    'gramos' => 2040.0, 'unidades' => 24, 'envase' => 'lata', 'litros' => false, 'hermanos' => [500 => '24 x 85 g']];

echo "A. Sin promoción: nada que pintar\n";
$m = KpcFicha::modelo($saco15, null, null, null, $peticion(), $textos, $euros);
igual('sin promoción, el hook devuelve vacío (hay = false)', false, $m['hay']);
igual('y no se presenta ningún precio', null, $m['presentar']);
igual('el nombre corto del formato', '12 kg', $saco12['corto']);

echo "\nB. El cupón\n";
$cupon = ['tipo' => 'cupon', 'codigo' => 'EUKA10', 'pct' => 10.0, 'ipas' => [3469, 3468], 'aplicado' => false, 'auto_web' => false, 'quedan' => null, 'quedan_por' => [], 'url' => '/module/kpycoupons/aplicar'];
$m = KpcFicha::modelo($saco15, $cupon, null, null, $peticion(), $textos, $euros);
igual('sin poner: estado cupon-sin, bloque cupon', ['cupon-sin', 'cupon'], [$m['estado'], $m['tipo']]);
igual('el título y el código', ['Aplica tu cupón −10 %', 'EUKA10'], [$m['cupon']['titulo'], $m['cupon']['codigo']]);
igual('«Aplícalo y págalo a 57,59 €»', 'Aplícalo y págalo a 57,59 €', $m['cupon']['sub']);
igual('el precio grande no cambia sin poner', null, $m['presentar']);
$m = KpcFicha::modelo($saco15, ['aplicado' => true] + $cupon, null, null, $peticion(), $textos, $euros);
igual('puesto: estado cupon, el precio grande pasa a 57,59 con el PVPR tachado', ['cupon', 57.59, 69.99, 'cupon'], [$m['estado'], $m['presentar']['precio'], $m['presentar']['pvpr'], $m['presentar']['motivo']]);
igual('«Cupón −10 % aplicado» · «Ahorras 6,40 € · se mantiene en la cesta»', ['Cupón −10 % aplicado', 'Ahorras 6,40 € · se mantiene en la cesta'], [$m['cupon']['titulo'], $m['cupon']['sub']]);
igual('y nuestro €/kg con el cupón', '3,84 €/kg con el cupón', $m['kg']);
igual('la Special Price no se toca (no la había)', false, $m['especial_fuera']);
$m = KpcFicha::modelo($saco15, ['aplicado' => true, 'auto_web' => true] + $cupon, null, null, $peticion(), $textos, $euros);
igual('«para todos» (auto_web): aplicado y el precio grande baja igual que con el de canal (3.2.0)', ['cupon', 57.59, true], [$m['estado'], $m['presentar']['precio'] ?? null, $m['cupon']['auto']]);
igual('y lo dice como siempre: «Ahorras 6,40 € · se mantiene en la cesta»', 'Ahorras 6,40 € · se mantiene en la cesta', $m['cupon']['sub']);
igual('sin Special Price en el formato, el modelo lo dice (especial = false)', false, $m['especial']);
$m = KpcFicha::modelo($saco15, ['aplicado' => true] + $cupon, null, null, $peticion(['robot' => true]), $textos, $euros);
igual('un robot nunca ve el cupón aplicado ni el precio con cupón', [false, null, 'cupon-sin'], [$m['cupon']['aplicado'], $m['presentar'], $m['estado']]);
$m = KpcFicha::modelo($saco15, ['quedan' => 12] + $cupon, null, null, $peticion(), $textos, $euros);
contiene('con cupo: « · quedan 12 ud. con cupón»', ' · quedan 12 ud. con cupón', $m['cupon']['sub']);
$otro = ['ipa' => 3470, 'corto' => '2 x 15 kg'] + $saco15;
$m = KpcFicha::modelo($otro, $cupon, null, null, $peticion(), $textos, $euros);
igual('el formato elegido no lo lleva pero otros sí: «El cupón −10 % es para: 15 kg, 3 kg»', [false, 'El cupón −10 % es para: 15 kg, 3 kg'], [$m['cupon']['entra'], $m['cupon']['para']]);
igual('y no hay botón ni precio', null, $m['presentar']);
$m = KpcFicha::modelo($saco12, ['ipas' => [92467], 'aplicado' => true] + $cupon, null, null, $peticion(), $textos, $euros);
igual('cupón en un formato con Special Price (excluir_especial = 0): no se pisa el precio grande, el cupón va en la cesta', [null, 'cupon-cesta', true], [$m['presentar'], $m['estado'], $m['especial']]);
igual('y la caja lo dice: «En la cesta lo pagas a 51,83 €»', 'En la cesta lo pagas a 51,83 €', $m['cupon']['sub']);

echo "\nC. La 2ª unidad\n";
$segunda = ['tipo' => 'segunda_unidad', 'codigo' => 'EUKA2UD', 'pct' => 30.0, 'ipas' => [3469], 'aplicado' => true, 'auto_web' => false, 'quedan' => null, 'quedan_por' => [], 'url' => ''];
$m = KpcFicha::modelo($saco15, $segunda, null, null, $peticion(['cantidad' => 1]), $textos, $euros);
igual('con 1 unidad: anima y es pulsable', ['2ª unidad al −30 %', true, false], [$m['segunda']['titulo'], $m['segunda']['pulsable'], $m['segunda']['puesta']]);
igual('«Añade 1 ud. más: te ahorras 19,20 € y el kg te sale a 3,63 €»', 'Añade 1 ud. más: te ahorras 19,20 € y el kg te sale a 3,63 €', $m['segunda']['texto']);
$m = KpcFicha::modelo($saco15, $segunda, null, null, $peticion(['cantidad' => 2]), $textos, $euros);
igual('con 2: puesta, no pulsable', [true, false], [$m['segunda']['puesta'], $m['segunda']['pulsable']]);
igual('«Llevas 2 ud.: te ahorras 19,20 € y el kg te sale a 3,63 €»', 'Llevas 2 ud.: te ahorras 19,20 € y el kg te sale a 3,63 €', $m['segunda']['texto']);
$m = KpcFicha::modelo($saco15, $segunda, null, null, $peticion(['cantidad' => 3]), $textos, $euros);
igual('con 3: un par, y pulsable otra vez', ['Llevas 3 ud.: te ahorras 19,20 € y el kg te sale a 3,84 €', true], [$m['segunda']['texto'], $m['segunda']['pulsable']]);
igual('la 2ª unidad no presenta otro precio grande (la hace la cesta)', [null, 'segunda'], [$m['presentar'], $m['estado']]);
$m = KpcFicha::modelo($latas, ['ipas' => [500]] + $segunda, null, null, $peticion(['cantidad' => 1]), $textos, $euros);
igual('con latas: «y cada lata te sale a 0,77 €»', 'Añade 1 ud. más: te ahorras 6,48 € y cada lata te sale a 0,77 €', $m['segunda']['texto']);
$m = KpcFicha::modelo($saco15, ['quedan' => 0] + $segunda, null, null, $peticion(['cantidad' => 1]), $textos, $euros);
igual('con el cupo a 0, ya no es pulsable', false, $m['segunda']['pulsable']);
$m = KpcFicha::modelo($otro, $segunda, null, null, $peticion(), $textos, $euros);
igual('otro formato: «La 2ª unidad al −30 % es para: 15 kg»', 'La 2ª unidad al −30 % es para: 15 kg', $m['segunda']['para']);

echo "\nD. El regalo con la compra: la tarjeta «12 kg + 3 kg gratis» y lo que dice el bloque al elegirla\n";
$regalo = ['ipas' => [92467], 'banda' => '+ 3 kg gratis', 'codigo' => 'REGALO-BOSKE', 'nombre' => 'Boske', 'hasta' => '2026-10-31 23:59:59', 'quedan' => null, 'por' => [
    92467 => ['id' => 8299, 'ipa' => 92468, 'nombre' => 'Boske Adult Salmón', 'formato' => 'Saco de 3 Kg', 'precio' => 17.99, 'pvpr' => 19.99, 'peso' => 3.0, 'imagen' => '', 'url' => '',
        'etiqueta' => '+ 3 kg gratis', 'corto' => '3 kg gratis', 'clase' => 'kilos', 'n' => 1, 'por' => 'unidad', 'agotado' => false, 'hasta' => '2026-10-31 23:59:59', 'quedan' => null],
]];
$saco12 += ['grupo' => 4];
$m = KpcFicha::modelo($saco12, null, $regalo, null, $peticion(), $textos, $euros);
igual('sin elegir: bloque regalo, nada que presentar, sin «Incluye…»', ['regalo', null, 0, null], [$m['tipo'], $m['presentar'], $m['regalo_elegido'], $m['regalo']]);
$tj = $m['tarjeta'];
igual('la tarjeta: «12 kg + 3 kg gratis», banda «Kilos gratis» en violeta, al habitual 63,99 € con 89,98 tachado y 4,27 €/kg, en el grupo 4 del atributo 30', ['12 kg + 3 kg gratis', 'Saco de 12 Kg + 3 kg gratis', 'Kilos gratis', 'background-color:#7F4AFF;color:#ffffff', 'kilos', '63,99 €', '89,98 €', '(4,27 €/kg)', 92467, 30, 4],
    [$tj['nombre'], $tj['titulo'], $tj['banda'], $tj['estilo'], $tj['clase'], $tj['precio'], $tj['pvpr'], $tj['kg'], $tj['ipa'], $tj['attr'], $tj['grupo']]);
igual('y hay algo que pintar (el campo oculto y los datos de la tarjeta)', true, $m['hay']);
igual('la Special Price se queda (la tarjeta de siempre)', false, $m['especial_fuera']);
$m = KpcFicha::modelo($saco12, null, $regalo, null, $peticion(['kpy_regalo' => 1]), $textos, $euros);
igual('elegida: el precio grande pasa al habitual 63,99 con 89,98 tachado, motivo regalo', ['regalo', 63.99, 89.98, 'regalo', 1], [$m['estado'], $m['presentar']['precio'], $m['presentar']['pvpr'], $m['presentar']['motivo'], $m['regalo_elegido']]);
igual('la Special Price, fuera (precio y etiqueta dicen lo mismo)', true, $m['especial_fuera']);
igual('nuestro €/kg con los 15 kg', '4,27 €/kg con los 15 kg', $m['kg']);
igual('la pastilla «Kilos gratis» y «Incluye 3 kg gratis (17,99 €), a 0 € en la cesta · Promoción hasta el 31 de octubre»', ['Kilos gratis', 'kilos', 'Incluye 3 kg gratis (17,99 €), a 0 € en la cesta', 'Promoción hasta el 31 de octubre', ''], [$m['regalo']['pill'], $m['regalo']['clase'], $m['regalo']['incluye'], $m['regalo']['fin'], $m['regalo']['quedan']]);
igual('sin «Quedan N» (no hay Special Price en la tarjeta con regalo)', '', $m['quedan']['texto']);
$conCupon = ['tipo' => 'cupon', 'codigo' => 'BOSKE10', 'pct' => 10.0, 'ipas' => [92468], 'aplicado' => true, 'auto_web' => false, 'quedan' => null, 'quedan_por' => [], 'url' => ''];
$m = KpcFicha::modelo($saco12, $conCupon, $regalo, null, $peticion(['kpy_regalo' => 1]), $textos, $euros);
igual('con el regalo elegido, el cupón de otros formatos no se enseña (un formato, una promoción)', null, $m['cupon']);
$agotadoRegalo = $regalo;
$agotadoRegalo['por'][92467]['agotado'] = true;
$m = KpcFicha::modelo($saco12, null, $agotadoRegalo, null, $peticion(['kpy_regalo' => 1]), $textos, $euros);
igual('regalo agotado y sin reserva: no hay tarjeta, y kpy_regalo=1 no cambia el precio', [null, null, 0], [$m['tarjeta'], $m['presentar'], $m['regalo_elegido']]);
$sinCupo = $regalo;
$sinCupo['por'][92467]['quedan'] = 0;
$m = KpcFicha::modelo($saco12, null, $sinCupo, null, $peticion(), $textos, $euros);
igual('regalos agotados por cupo: sin tarjeta y con el aviso', [null, 'Se han agotado los regalos de esta promoción'], [$m['tarjeta'], $m['aviso']]);
$otraCosa = $regalo;
$otraCosa['por'][92467] = ['id' => 7000, 'ipa' => 70001, 'nombre' => 'Snacks de pollo', 'formato' => 'Bolsa 100 g', 'precio' => 4.50, 'pvpr' => 4.99, 'peso' => 0.1, 'imagen' => 'https://x/foto.jpg', 'url' => '',
    'etiqueta' => '+ regalo', 'corto' => 'Snacks', 'clase' => 'regalo', 'n' => 2, 'por' => 'pedido', 'agotado' => false, 'hasta' => '', 'quedan' => 3];
$m = KpcFicha::modelo($saco12, null, $otraCosa, null, $peticion(['kpy_regalo' => 1]), $textos, $euros);
$tj = $m['tarjeta'];
igual('otra cosa de regalo: tarjeta «12 kg + 2 x Snacks», banda «Con regalo», sin kilos de más (5,33 €/kg a 63,99)', ['12 kg + 2 x Snacks', 'Con regalo', 'regalo', '(5,33 €/kg)'], [$tj['nombre'], $tj['banda'], $tj['clase'], $tj['kg']]);
igual('elegida: «Incluye 2 × Snacks de pollo · Bolsa 100 g (9,00 €), a 0 € en la cesta · uno por pedido», con foto y «quedan 3 regalos»', ['Incluye 2 × Snacks de pollo · Bolsa 100 g (9,00 €), a 0 € en la cesta · uno por pedido', 'https://x/foto.jpg', 'quedan 3 regalos', 'Con regalo'], [$m['regalo']['incluye'], $m['regalo']['imagen'], $m['regalo']['quedan'], $m['regalo']['pill']]);
$m = KpcFicha::modelo($saco15, null, ['ipas' => [92467], 'por' => [92467 => $regalo['por'][92467]]], null, $peticion(['kpy_regalo' => 1]), $textos, $euros);
igual('el regalo es de otro formato: en este no hay tarjeta', null, $m['tarjeta']);

echo "\nE. Liquidación con cupo: «Quedan N», pocas, agotado\n";
$liq = ['etiqueta' => 'Liquidación', 'clase' => 'liquidacion', 'quedan' => 12, 'id_kpy_coupon' => 91];
$m = KpcFicha::modelo($saco12, null, null, $liq, $peticion(), $textos, $euros);
igual('«Quedan 12 unidades en Liquidación», estado precio, sin pisar el precio', ['Quedan 12 unidades en Liquidación', false, false, 'precio', null], [$m['quedan']['texto'], $m['quedan']['pocas'], $m['quedan']['agotado'], $m['estado'], $m['presentar']]);
$m = KpcFicha::modelo($saco12, null, null, ['quedan' => 3] + $liq, $peticion(), $textos, $euros);
igual('con 3: «¡Últimas 3 unidades en Liquidación!», pocas', ['¡Últimas 3 unidades en Liquidación!', true], [$m['quedan']['texto'], $m['quedan']['pocas']]);
$m = KpcFicha::modelo($saco12, null, null, ['quedan' => 1] + $liq, $peticion(), $textos, $euros);
igual('con 1: «¡Última unidad en Liquidación!»', '¡Última unidad en Liquidación!', $m['quedan']['texto']);
$m = KpcFicha::modelo($saco12, null, null, ['quedan' => 0] + $liq, $peticion(), $textos, $euros);
igual('agotado para esta cesta: lo dice, el precio grande pasa al habitual 63,99 y la Special Price se va', ['Se han agotado las unidades en Liquidación: a precio habitual', true, 'agotado', 63.99, 69.99, true], [$m['quedan']['texto'], $m['quedan']['agotado'], $m['estado'], $m['presentar']['precio'], $m['presentar']['pvpr'], $m['especial_fuera']]);
igual('y nuestro €/kg a precio habitual', '5,33 €/kg a precio habitual', $m['kg']);
$m = KpcFicha::modelo($saco12, null, null, ['quedan' => null] + $liq, $peticion(), $textos, $euros);
igual('sin cupo (null): no hay línea de quedan', ['', false], [$m['quedan']['texto'], $m['hay']]);
$m = KpcFicha::modelo($saco15, null, null, ['quedan' => 5] + $liq, $peticion(), $textos, $euros);
igual('un formato sin Special Price de verdad no dice «quedan»', '', $m['quedan']['texto']);
$m = KpcFicha::modelo($saco12, null, $regalo, ['quedan' => 4] + $liq, $peticion(), $textos, $euros);
igual('Liquidación + regalo: «¡Últimas 4…!» y la tarjeta de regalo sigue ahí', ['¡Últimas 4 unidades en Liquidación!', '12 kg + 3 kg gratis'], [$m['quedan']['texto'], $m['tarjeta']['nombre'] ?? null]);

echo "\nF. Los textos sueltos\n";
igual('fin: hoy / mañana / en 3 días / fecha / pasado / vacío', ['Termina hoy', 'Termina mañana', 'Termina en 3 días', 'Promoción hasta el 30 de noviembre', '', ''], [
    KpcFicha::fin('2026-10-23 23:59:59', $hoy, []), KpcFicha::fin('2026-10-24 23:59:59', $hoy, []), KpcFicha::fin('2026-10-26 23:59:59', $hoy, []),
    KpcFicha::fin('2026-11-30 23:59:59', $hoy, []), KpcFicha::fin('2026-10-01 00:00:00', $hoy, []), KpcFicha::fin('0000-00-00 00:00:00', $hoy, []),
]);
igual('quedanTexto', ['Quedan 7 unidades en Outlet', '', 'Se han agotado las unidades en Outlet: a precio habitual'], [KpcFicha::quedanTexto(7, false, 'Outlet', []), KpcFicha::quedanTexto(0, false, 'Outlet', []), KpcFicha::quedanTexto(0, true, 'Outlet', [])]);
igual('textos traducibles: una clave desconocida sale tal cual y una traducida manda', ['Quedan 7 unidades en Outlet', 'Faltan 7 de Outlet'], [KpcFicha::quedanTexto(7, false, 'Outlet', []), KpcFicha::quedanTexto(7, false, 'Outlet', ['quedan' => 'Faltan %n% de %promo%'])]);
igual('robots: Googlebot, Storebot, AdsBot, bingbot sí; Chrome y Safari no', [true, true, true, true, false, false], [
    KpcFicha::esRobot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'),
    KpcFicha::esRobot('Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/W.X.Y.Z Mobile Safari/537.36 (compatible; Storebot-Google/1.0)'),
    KpcFicha::esRobot('AdsBot-Google (+http://www.google.com/adsbot.html)'),
    KpcFicha::esRobot('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'),
    KpcFicha::esRobot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36'),
    KpcFicha::esRobot('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'),
]);
igual('paraTexto: nombres cortos, sin repetir', '15 kg, 3 kg', KpcFicha::paraTexto([3469, 3468, 3469], [3469 => '15 kg', 3468 => '3 kg']));
igual('numero', ['15', '2,5', '0,1'], [KpcFicha::numero(15.0), KpcFicha::numero(2.5), KpcFicha::numero(0.1)]);
igual('tipoDeChip', ['cupon', 'segunda_unidad', 'special_price', 'special_price', ''], [KpcFicha::tipoDeChip('kpc-cupon'), KpcFicha::tipoDeChip('kpc-segunda'), KpcFicha::tipoDeChip('kpc-liquidacion'), KpcFicha::tipoDeChip('kpy-special-price'), KpcFicha::tipoDeChip('kpc-regalo')]);

echo "\nG. La capa de campaña (Black Friday)\n";
$campana = ['nombre' => 'Black Friday', 'clave' => 'black-friday', 'piel' => 'negra', 'tipos' => [
    'cupon' => ['label' => 'Cupón Black −10 %', 'background' => '#111111', 'color' => '#F2C965'],
    'special_price' => ['label' => 'Black Friday', 'background' => '#111111', 'color' => '#F2C965'],
]];
$m = KpcFicha::conCampana(KpcFicha::modelo($saco15, ['aplicado' => true] + $cupon, null, null, $peticion(), $textos, $euros), $campana);
igual('el cupón se llama como la campaña', 'Cupón Black −10 % · aplicado', $m['cupon']['titulo']);
contiene('y las variables de color van en el estilo del bloque', '--kpc-cup-bg:#111111;--kpc-cup-borde:#F2C965;--kpc-cup-fg:#F2C965;--kpc-cup-sub:#D5DBDE', $m['campana']['estilo']);
contiene('también las del Special Price', '--kpc-esp-bg:#111111;--kpc-esp-fg:#F2C965', $m['campana']['estilo']);
$m = KpcFicha::conCampana(KpcFicha::modelo($saco12, null, null, ['quedan' => 2] + $liq, $peticion(), $textos, $euros), $campana);
igual('«Liquidación» con nombre propio no se renombra por la campaña', '¡Últimas 2 unidades en Liquidación!', $m['quedan']['texto']);
$m = KpcFicha::conCampana(KpcFicha::modelo($saco12, null, null, ['etiqueta' => 'Special Price', 'clase' => 'especial', 'quedan' => 2, 'id_kpy_coupon' => 25], $peticion(), $textos, $euros), $campana);
igual('un Special Price sin nombre propio sí: «¡Últimas 2 unidades en Black Friday!»', '¡Últimas 2 unidades en Black Friday!', $m['quedan']['texto']);
igual('un color que no es #rrggbb cae al de siempre', '#7B5CB0', KpcFicha::color('red', '#7B5CB0'));
igual('esOscuro', [true, false], [KpcFicha::esOscuro('#111111'), KpcFicha::esOscuro('#FFEAB7')]);
// Una ficha con solo Special Price (sin cupo, sin cupón): sin campaña no hay nada que pintar; con
// campaña, el bloque sale vacío y escondido para llevar los colores a la piel del precio del tema.
$m = KpcFicha::modelo($saco12, null, null, null, $peticion(), $textos, $euros);
igual('solo Special Price, sin campaña: nada que pintar, pero el modelo sabe que lo hay', [false, true, false], [$m['hay'], $m['especial'], $m['solo_campana']]);
$m = KpcFicha::conCampana($m, $campana);
igual('con campaña: hay bloque, vacío (solo_campana), con los colores del Special Price delante', [true, true, true], [$m['hay'], $m['solo_campana'], strpos($m['campana']['estilo'], '--kpc-esp-bg:#111111;--kpc-esp-fg:#F2C965') === 0]);
$m = KpcFicha::conCampana(KpcFicha::modelo($saco12, null, null, null, $peticion(), $textos, $euros), ['nombre' => 'X', 'clave' => 'x', 'piel' => 'negra', 'tipos' => ['cupon' => $campana['tipos']['cupon']]]);
igual('si la campaña no toca el Special Price, sigue sin haber nada que pintar', [false, false], [$m['hay'], $m['solo_campana']]);
$m = KpcFicha::conCampana(KpcFicha::modelo($saco15, null, null, null, $peticion(), $textos, $euros), $campana);
igual('y un formato sin Special Price, tampoco (aunque la campaña lo tenga)', [false, false], [$m['hay'], $m['solo_campana']]);
$m = KpcFicha::conCampana(KpcFicha::modelo($saco15, ['aplicado' => true, 'auto_web' => true] + $cupon, null, null, $peticion(), $textos, $euros), $campana);
igual('el cupón «para todos» con campaña: título de campaña, precio con cupón, no es solo_campana', ['Cupón Black −10 % · aplicado', 57.59, false], [$m['cupon']['titulo'], $m['presentar']['precio'], $m['solo_campana']]);

echo "\nH. El lector de nombres de formato (KpcEnvase)\n";
igual('«Saco de 15 Kg» → 15 kg, 15000 g', ['15 kg', 15000.0], [KpcEnvase::leer('Saco de 15 Kg')['corto'], KpcEnvase::leer('Saco de 15 Kg')['gramos']]);
igual('«Pack 24 Latas 85 gr» → 24 x 85 g, 24 latas', ['24 x 85 g', 24, 'lata'], [KpcEnvase::leer('Pack 24 Latas 85 gr')['corto'], KpcEnvase::leer('Pack 24 Latas 85 gr')['unidades'], KpcEnvase::leer('Pack 24 Latas 85 gr')['envase']]);
igual('el peso del catálogo manda sobre el texto', 12000.0, KpcEnvase::leer('Saco de 12 Kg', 12.0)['gramos']);

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
