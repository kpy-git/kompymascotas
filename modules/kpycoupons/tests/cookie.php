<?php
/**
 * La cookie de los cupones (`KpcCookie`), sin tienda: varios cupones puestos y quitados a la vez,
 * lo que se lee de la cookie de antes (un solo código), el tope y lo que no es un código.
 *   php -n tests/cookie.php
 */
require __DIR__ . '/../classes/KpcCookie.php';

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

echo "A. Lo de antes se lee igual\n";
igual('vacía: nada', ['puestos' => [], 'quitados' => []], KpcCookie::leer(''));
igual('«EUKA10» (puesto, como hasta la 3.1)', ['puestos' => ['EUKA10'], 'quitados' => []], KpcCookie::leer('EUKA10'));
igual('«!EUKA10» (quitado a mano)', ['puestos' => [], 'quitados' => ['EUKA10']], KpcCookie::leer('!EUKA10'));
igual('en minúsculas y con espacios, se normaliza', ['puestos' => ['EUKA10'], 'quitados' => []], KpcCookie::leer(' euka10 '));

echo "\nB. Varios a la vez\n";
$c = KpcCookie::poner('', 'SPECIFIC10');
$c = KpcCookie::poner($c, 'advance15');
igual('dos puestos, en orden', 'SPECIFIC10,ADVANCE15', $c);
igual('poner el que ya está no lo repite ni lo mueve de sitio… lo pone el último', 'ADVANCE15,SPECIFIC10', KpcCookie::poner($c, 'SPECIFIC10'));
$c = KpcCookie::quitar($c, 'SPECIFIC10');
igual('quitar uno: pasa a los quitados, el otro sigue', 'ADVANCE15,!SPECIFIC10', $c);
igual('estaPuesto / estaQuitado', [true, false, false, true], [KpcCookie::estaPuesto($c, 'advance15'), KpcCookie::estaPuesto($c, 'SPECIFIC10'), KpcCookie::estaQuitado($c, 'ADVANCE15'), KpcCookie::estaQuitado($c, 'specific10')]);
$c = KpcCookie::poner($c, 'BFBOEH10');
igual('un tercero puesto, detrás de los puestos y antes de los quitados', 'ADVANCE15,BFBOEH10,!SPECIFIC10', $c);
igual('volver a poner el quitado lo perdona', 'ADVANCE15,BFBOEH10,SPECIFIC10', KpcCookie::poner($c, 'SPECIFIC10'));
igual('una visita nueva desde un canal perdona a todos los quitados', 'ADVANCE15,BFBOEH10', KpcCookie::sinQuitados($c));
igual('quitar uno que no está puesto también se apunta (no volverá a entrar solo)', 'ADVANCE15,!SPECIFIC10,!EUKA10', KpcCookie::quitar('ADVANCE15,!SPECIFIC10', 'EUKA10'));

echo "\nC. Lo raro\n";
igual('un código que no vale no entra (espacios dentro, demasiado largo, vacío)', ['puestos' => ['OK_1-2'], 'quitados' => []], KpcCookie::leer('OK_1-2,MAL CODIGO,' . str_repeat('A', 33) . ',,!'));
igual('poner un código que no vale no cambia nada', 'ADVANCE15', KpcCookie::poner('ADVANCE15', 'no vale'));
igual('repetido en la cookie: una vez', ['puestos' => ['A1'], 'quitados' => []], KpcCookie::leer('A1,a1,A1'));
igual('puesto y quitado a la vez: manda el último apuntado', [['puestos' => [], 'quitados' => ['A1']], ['puestos' => ['A1'], 'quitados' => []]], [KpcCookie::leer('A1,!A1'), KpcCookie::leer('!A1,A1')]);
$muchos = '';
for ($i = 1; $i <= 14; ++$i) {
    $muchos = KpcCookie::poner($muchos, 'C' . $i);
}
igual('con más de MAX puestos se caen los más antiguos', ['C5', 'C14', KpcCookie::MAX], [KpcCookie::leer($muchos)['puestos'][0], KpcCookie::leer($muchos)['puestos'][KpcCookie::MAX - 1], count(KpcCookie::leer($muchos)['puestos'])]);
for ($i = 1; $i <= 14; ++$i) {
    $muchos = KpcCookie::quitar($muchos, 'Q' . $i);
}
igual('y lo mismo con los quitados; la cookie se queda corta (' . strlen($muchos) . ' bytes)', [KpcCookie::MAX, KpcCookie::MAX, true], [count(KpcCookie::leer($muchos)['puestos']), count(KpcCookie::leer($muchos)['quitados']), strlen($muchos) < 200]);
igual('escribir() limpia (mayúsculas, repetidos, lo que no es código, un quitado que también está puesto)', 'AA,BB,CC,!DD', KpcCookie::escribir(['aa', 'bb', 'cc', 'bb', 'x y', 'a'], ['dd', 'cc']));

echo $fallos ? "\n$fallos fallos\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
