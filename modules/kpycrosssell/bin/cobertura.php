<?php
/**
 * Kompy · A cuántos productos les sale algo que ofrecer, y de dónde sale.
 *
 * Se ejecuta EN LA TIENDA, desde su raíz:
 *
 *   docker exec -i -w /var/www/html/kompy php85 php modules/kpycrosssell/bin/cobertura.php
 *   … modules/kpycrosssell/bin/cobertura.php --tipo="Comida Seca"   (solo un tipo de producto)
 *
 * Sirve para dos cosas: comprobar que el motor no revienta en ningún producto del catálogo, y saber
 * antes de encender el bloque a cuánta ficha llega de verdad.
 *
 * Solo lee.
 */
require dirname(__DIR__, 3) . '/config/config.inc.php';
// El módulo, para que existan sus clases y sus ajustes.
$modulo = Module::getInstanceByName('kpycrosssell');
if (!$modulo) {
    exit("No está instalado kpycrosssell.\n");
}

// El precio de cada producto lo calcula PrestaShop, y en consola necesita a alguien detrás.
Context::getContext()->employee = new Employee((int) Db::getInstance()->getValue('SELECT MIN(id_employee) FROM ' . _DB_PREFIX_ . 'employee'));

$soloTipo = '';
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--tipo=') === 0) {
        $soloTipo = trim(substr($arg, 7), '"\'');
    }
}

$p = _DB_PREFIX_;
$productos = Db::getInstance()->executeS(
    "SELECT pr.id_product, pr.id_manufacturer FROM {$p}product pr WHERE pr.active = 1 ORDER BY pr.id_product"
) ?: [];

$ajustes = Kpycrosssell::comoBuscar();
$cuenta = [];
$sin = 0;
$mirados = 0;
$errores = [];
$sinNada = [];

foreach ($productos as $fila) {
    $id = (int) $fila['id_product'];
    $rasgos = KcsSugerencias::rasgos($id);
    if ($soloTipo !== '' && $rasgos['tipo'] !== $soloTipo) {
        continue;
    }
    ++$mirados;
    try {
        $precio = (float) Product::getPriceStatic($id, true, null);
        $suyas = KcsSugerencias::para([
            'id_product' => $id,
            'id_manufacturer' => (int) $fila['id_manufacturer'],
            'precio' => $precio,
        ], $ajustes);
    } catch (Throwable $e) {
        $errores[] = $id . ': ' . $e->getMessage();
        continue;
    }
    if ($suyas === []) {
        ++$sin;
        if (count($sinNada) < 15) {
            $sinNada[] = '#' . $id . ' ' . ($rasgos['tipo'] ?: 'sin tipo') . ' / ' . ($rasgos['mascota'] ?: 'sin mascota');
        }
        continue;
    }
    // Cuenta el origen de la primera, que es la que más se ve.
    $cuenta[$suyas[0]['origen']] = ($cuenta[$suyas[0]['origen']] ?? 0) + 1;
}

$pct = static fn (int $n): string => $mirados > 0 ? number_format($n * 100 / $mirados, 1, ',', '.') . ' %' : '—';

echo 'Productos mirados: ', $mirados, $soloTipo !== '' ? " (solo «{$soloTipo}»)" : '', "\n";
echo str_repeat('-', 58), "\n";
// De dónde sale la primera sugerencia, que es la que más se ve.
$comoSeLlama = [
    'mano' => 'puesta a mano',
    'propia' => 'húmedo clínico de Boske',
    'marca' => 'algo de su propia marca',
    'reserva' => 'de las marcas de reserva',
    'refuerzo' => 'complemento de su patología',
    'cuidado' => 'snack o higiene',
    'capricho' => 'capricho',
];
arsort($cuenta);
foreach ($cuenta as $origen => $cuantos) {
    printf("%-30s %7d  %8s\n", $comoSeLlama[$origen] ?? $origen, $cuantos, $pct((int) $cuantos));
}
printf("%-30s %7d  %8s\n", 'sin nada que ofrecer', $sin, $pct($sin));

if ($sinNada !== []) {
    echo "\nEjemplos de los que se quedan sin nada:\n";
    foreach ($sinNada as $linea) {
        echo '  ', $linea, "\n";
    }
}

echo "\n", $errores === [] ? "Sin errores.\n" : count($errores) . " errores:\n  " . implode("\n  ", array_slice($errores, 0, 20)) . "\n";
exit($errores === [] ? 0 : 1);
