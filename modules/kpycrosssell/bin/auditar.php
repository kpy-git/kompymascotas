<?php
/**
 * Kompy · Revisa TODAS las sugerencias guardadas buscando errores.
 *
 * Se ejecuta EN LA TIENDA, desde su raíz:
 *
 *   docker exec -i -w /var/www/html/kompy php85 php modules/kpycrosssell/bin/auditar.php
 *
 * No usa el motor que las calcula: mira las parejas ya guardadas en `accessory` con consultas
 * propias. Así, si el motor tuviera un fallo, esto lo vería. Solo lee.
 *
 * Lo que busca, de más grave a menos:
 *
 *   mascota       un producto de gato ofrecido con uno de perro, o al revés (por la ficha o por el
 *                 nombre: el catálogo marca muchos «Perro y Gato» que se llaman «… para perros»)
 *   patologia     una enfermedad que no es la del producto (diabetes junto a un pienso sano)
 *   comida_sana   comida normal junto a un producto para una enfermedad (una lata normal a un renal)
 *   edad          cachorro con adulto o senior, y al revés
 *   distribuidor  de otro distribuidor que el producto (salvo Boske, que es de la casa)
 *   no_se_vende   sin ninguna combinación con stock que no esté apagada
 *   suplemento    un suplemento que no comparte patología con el producto
 *   vetada        de una marca que no se ofrece como capricho (Trixie)
 *
 * Solo cuenta las parejas que ha puesto el cálculo; las puestas a mano en el back office se
 * enseñan aparte, porque ahí manda quien las puso.
 */
require dirname(__DIR__, 3) . '/config/config.inc.php';

$modulo = Module::getInstanceByName('kpycrosssell');
if (!$modulo) {
    exit("No está instalado kpycrosssell.\n");
}

$p = _DB_PREFIX_;
$db = Db::getInstance();
$ids = KcsSugerencias::ids();
$v = $ids['valores'];
$fMascota = (int) $ids['mascota'];
$fNecesidad = (int) $ids['necesidad'];
$fEdad = (int) $ids['edad'];
$fTipo = (int) $ids['tipo'];

$idPerro = (int) ($v['Perro'] ?? 0);
$idGato = (int) ($v['Gato'] ?? 0);
$patologias = KcsSugerencias::patologias();
$propia = (int) Configuration::get('KCS_MARCA_PROPIA');
$suplemento = (int) ($v['Complemento Nutricional'] ?? 0);

// Todas las parejas, con lo que hace falta de los dos lados.
$parejas = $db->executeS(
    "SELECT a.id_product_1 AS prod, a.id_product_2 AS sug, g.origen, g.posicion,
            p1.id_manufacturer AS marca1, p2.id_manufacturer AS marca2, p2.active AS activo2,
            pl1.name AS nombre1, pl2.name AS nombre2,
            cl1.name AS categoria1, cl2.name AS categoria2
     FROM {$p}accessory a
     INNER JOIN {$p}product p1 ON p1.id_product = a.id_product_1
     INNER JOIN {$p}product p2 ON p2.id_product = a.id_product_2
     INNER JOIN {$p}product_lang pl1 ON pl1.id_product = p1.id_product AND pl1.id_lang = 1 AND pl1.id_shop = 1
     INNER JOIN {$p}product_lang pl2 ON pl2.id_product = p2.id_product AND pl2.id_lang = 1 AND pl2.id_shop = 1
     LEFT JOIN {$p}category_lang cl1 ON cl1.id_category = p1.id_category_default AND cl1.id_lang = 1 AND cl1.id_shop = 1
     LEFT JOIN {$p}category_lang cl2 ON cl2.id_category = p2.id_category_default AND cl2.id_lang = 1 AND cl2.id_shop = 1
     LEFT JOIN {$p}kpy_cross_sell_auto g ON g.id_product = a.id_product_1 AND g.id_sugerido = a.id_product_2"
) ?: [];

/**
 * Lo que el nombre o la categoría dicen del animal, de la edad y de la enfermedad, aunque la ficha no
 * lo tenga marcado: es la red que coge lo que el catálogo no dice. El vocabulario es el de
 * `KcsPistas`, el mismo que usa el motor: con dos listas distintas, la auditoría daba por malas
 * parejas buenas (un «Gastrointestinal» con un «Gastro Intestinal») solo porque una palabra faltaba.
 */
$animalDe = static fn (string $texto): string => KcsPistas::de($texto)['animal'];
$edadDe = static function (string $texto): string {
    $edad = KcsPistas::de($texto)['edad'];

    return $edad === 'adulto' ? '' : $edad;
};
$enfermedadDe = static fn (string $texto): array => KcsPistas::de($texto)['enfermedades'];
// Las patologías de la ficha, con los mismos nombres que las del nombre.
$nombreDeValor = array_flip($v);
$enfermedadesFicha = static fn (array $idsNecesidad): array => KcsPistas::desdeFicha(array_values(array_filter(array_map(
    static fn (int $id): ?string => $nombreDeValor[$id] ?? null,
    $idsNecesidad
))));

// Los rasgos de todos los productos implicados, de una vez.
$implicados = array_values(array_unique(array_merge(array_column($parejas, 'prod'), array_column($parejas, 'sug'))));
$rasgos = [];
foreach (array_chunk(array_map('intval', $implicados), 1000) as $trozo) {
    foreach ($db->executeS(
        "SELECT id_product, id_feature, id_feature_value FROM {$p}feature_product
         WHERE id_feature IN ({$fMascota}, {$fNecesidad}, {$fEdad}, {$fTipo}) AND id_product IN (" . implode(',', $trozo) . ')'
    ) ?: [] as $f) {
        $rasgos[(int) $f['id_product']][(int) $f['id_feature']][] = (int) $f['id_feature_value'];
    }
}
// Qué productos tienen algo que se venda de verdad.
$vendibles = [];
foreach (array_chunk(array_map('intval', $implicados), 1000) as $trozo) {
    foreach ($db->executeS(
        "SELECT DISTINCT pa.id_product FROM {$p}product_attribute pa
         INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute AND sa.id_shop = 1 AND sa.quantity > 0
         WHERE pa.id_product IN (" . implode(',', $trozo) . ")
           AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute k WHERE k.id_product_attribute = pa.id_product_attribute AND k.active = 0)"
    ) ?: [] as $f) {
        $vendibles[(int) $f['id_product']] = true;
    }
}

$etapa = static function (array $edades) use ($v): array {
    $salida = [];
    foreach (KcsSugerencias::ETAPAS as $nombre => $valores) {
        foreach ($valores as $valor) {
            if (in_array((int) ($v[$valor] ?? -1), $edades, true)) {
                $salida[] = $nombre;
            }
        }
    }

    return array_values(array_unique($salida));
};
$nombreDice = static function (string $nombre): string {
    $n = mb_strtolower($nombre);
    $perro = (bool) preg_match('/\bperr/u', $n);
    $gato = (bool) preg_match('/\bgat/u', $n);

    return $perro && !$gato ? 'Perro' : ($gato && !$perro ? 'Gato' : '');
};
$vetadas = KcsSugerencias::vetadas();
$comidas = array_values(array_filter(array_map(static fn (string $n): int => (int) ($v[$n] ?? 0), KcsSugerencias::COMIDA)));

$fallos = [];
$aMano = 0;
foreach ($parejas as $par) {
    if ($par['origen'] === null) {
        ++$aMano;
        continue;
    }
    $a = (int) $par['prod'];
    $b = (int) $par['sug'];
    $ra = $rasgos[$a] ?? [];
    $rb = $rasgos[$b] ?? [];
    $problemas = [];

    // Mascota: por la ficha y por el nombre.
    $ma = $ra[$fMascota] ?? [];
    $mb = $rb[$fMascota] ?? [];
    $soloPerroA = in_array($idPerro, $ma, true) && !in_array($idGato, $ma, true);
    $soloGatoA = in_array($idGato, $ma, true) && !in_array($idPerro, $ma, true);
    $soloPerroB = in_array($idPerro, $mb, true) && !in_array($idGato, $mb, true);
    $soloGatoB = in_array($idGato, $mb, true) && !in_array($idPerro, $mb, true);
    $dice = $nombreDice((string) $par['nombre2']);
    if (($soloPerroA && ($soloGatoB || $dice === 'Gato')) || ($soloGatoA && ($soloPerroB || $dice === 'Perro'))) {
        $problemas[] = 'mascota';
    }
    // Un producto de los dos animales cuyo nombre no dice cuál: lo sugerido tiene que valer para los dos.
    $deLosDosA = in_array($idPerro, $ma, true) && in_array($idGato, $ma, true);
    if ($deLosDosA && !in_array('mascota', $problemas, true)
        && !in_array($animalDe((string) $par['nombre1']), ['Perro', 'Gato'], true)
        && ($soloPerroB || $soloGatoB) && $animalDe((string) $par['nombre2']) !== 'ambos') {
        $problemas[] = 'mascota';
    }
    // Y por el nombre y la categoría de los dos lados, aunque la ficha diga «Perro y Gato».
    $animalA = $animalDe((string) $par['nombre1']) ?: $animalDe((string) $par['categoria1']);
    $animalB = $animalDe((string) $par['nombre2']) ?: $animalDe((string) $par['categoria2']);
    if ($animalA !== '' && $animalB !== '' && $animalA !== 'ambos' && $animalB !== 'ambos'
        && $animalA !== $animalB && !in_array('mascota', $problemas, true)) {
        $problemas[] = 'mascota_nombre';
    }

    // La edad que dice el nombre (cachorro, kitten, senior…).
    $edadA = $edadDe((string) $par['nombre1']);
    $edadB = $edadDe((string) $par['nombre2']);
    if ($edadB !== '' && $edadB !== $edadA && ($edadB === 'joven' || $edadA === 'joven')) {
        $problemas[] = 'edad_nombre';
    }

    // La enfermedad que dice el nombre del sugerido tiene que estar también en el producto: en su
    // nombre, en su categoría o en su ficha.
    $enfA = array_merge(
        $enfermedadDe((string) $par['nombre1']),
        $enfermedadDe((string) $par['categoria1']),
        $enfermedadesFicha($ra[$fNecesidad] ?? [])
    );
    $enfB = $enfermedadDe((string) $par['nombre2']);
    if ($enfB !== [] && array_intersect($enfB, $enfA) === []) {
        $problemas[] = 'enfermedad_nombre';
    }

    // Patología: el sugerido no puede tener una que no sea la del producto. Lo buscado **por** su
    // patología (suplemento, snack funcional, húmedo clínico de Boske) basta con que la comparta.
    $pa = array_values(array_intersect($ra[$fNecesidad] ?? [], $patologias));
    $pb = array_values(array_intersect($rb[$fNecesidad] ?? [], $patologias));
    $porPatologia = in_array($par['origen'], ['refuerzo', 'funcional', 'propia'], true);
    if ($porPatologia ? ($pb !== [] && array_intersect($pa, $pb) === []) : array_diff($pb, $pa) !== []) {
        $problemas[] = 'patologia';
    }

    // Edad: etapas que no se tocan.
    $ea = $etapa($ra[$fEdad] ?? []);
    $eb = $etapa($rb[$fEdad] ?? []);
    // «Todas las edades» vale para cualquiera.
    $todas = (int) ($v['Todas las edades'] ?? -1);
    if ($ea !== [] && $eb !== [] && array_intersect($ea, $eb) === [] && !in_array($todas, $rb[$fEdad] ?? [], true)) {
        $joven = in_array('joven', $ea, true) || in_array('joven', $eb, true);
        if ($joven) {
            $problemas[] = 'edad';
        }
    }

    // Distribuidor: el mismo, salvo Boske (que tampoco va con Evolution).
    $da = KcsDistribuidor::deLaMarca((int) $par['marca1']);
    $dbb = KcsDistribuidor::deLaMarca((int) $par['marca2']);
    $esPropia = (int) $par['marca2'] === $propia;
    // La tienda es el último recurso a propósito, para no dejar huecos: no cuenta como cruce.
    $deTienda = $par['origen'] === 'tienda' && ($dbb === KcsDistribuidor::PROPIO || $esPropia);
    // El suplemento de su patología puede venir de otro distribuidor (pedido de Rafa, 29/09), nunca
    // con Evolution.
    // Y los antiparasitarios se combinan entre ellos sean de quien sean (collar + pastilla + pipeta).
    $refuerzoDeFuera = in_array($par['origen'], ['refuerzo', 'antiparasitario'], true) && $da !== 'evolution';
    if ($da !== $dbb && !$deTienda && !$refuerzoDeFuera && (!$esPropia || $da === 'evolution')) {
        $problemas[] = 'distribuidor';
    }

    if (empty($vendibles[$b])) {
        $problemas[] = 'no_se_vende';
    }
    // Un snack que el catálogo también marca como complemento no es un suplemento.
    $snack = (int) ($v['Snacks'] ?? -1);
    if (in_array($suplemento, $rb[$fTipo] ?? [], true) && !in_array($snack, $rb[$fTipo] ?? [], true)
        && ($pb === [] || array_intersect($pa, $pb) === [])) {
        $problemas[] = 'suplemento';
    }
    if (in_array((int) $par['marca2'], $vetadas, true)) {
        $problemas[] = 'vetada';
    }
    // Comida normal junto a un producto para una enfermedad: a un renal, solo comida renal. Un snack
    // que el catálogo marca también como comida (el queso en spray de Kong) sigue siendo un snack.
    $enfermoA = array_values(array_unique(array_merge(
        $enfermedadDe((string) $par['nombre1']),
        $enfermedadesFicha($pa)
    )));
    if ($enfermoA !== [] && array_intersect($comidas, $rb[$fTipo] ?? []) !== [] && !in_array($snack, $rb[$fTipo] ?? [], true)
        && array_intersect($enfermoA, array_merge(
            $enfB,
            $enfermedadDe((string) $par['categoria2']),
            $enfermedadesFicha($rb[$fNecesidad] ?? [])
        )) === []) {
        $problemas[] = 'comida_sana';
    }

    foreach ($problemas as $tipo) {
        $fallos[$tipo][] = sprintf('#%d %s  →  #%d %s', $a, mb_substr((string) $par['nombre1'], 0, 42), $b, mb_substr((string) $par['nombre2'], 0, 42));
    }
}

// Lo que ve el cliente: cuántas enseña cada ficha **ahora**, con el stock de este momento. Es la
// medida de «siempre tres». Se cuenta aparte lo que se puede comprar (con stock): una ficha agotada
// no vende nada, y ahí es donde tiene que haber tres sí o sí. Los regalos de promoción no cuentan.
$activos = $db->executeS(
    "SELECT pr.id_product,
            EXISTS (SELECT 1 FROM {$p}product_attribute pa
                    INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute AND sa.id_shop = 1 AND sa.quantity > 0
                    WHERE pa.id_product = pr.id_product) AS con_stock
     FROM {$p}product pr
     INNER JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_lang = 1 AND pl.id_shop = 1
     WHERE pr.active = 1 AND pl.name NOT LIKE 'Regalo%'
       AND NOT EXISTS (SELECT 1 FROM {$p}feature_product fr WHERE fr.id_product = pr.id_product
                       AND fr.id_feature = {$fTipo} AND fr.id_feature_value = " . (int) ($v['Regalo'] ?? 0) . ')'
) ?: [];
$grupos = ['con stock' => [0 => 0, 1 => 0, 2 => 0, 3 => 0], 'agotados' => [0 => 0, 1 => 0, 2 => 0, 3 => 0]];
$pocas = [];
foreach ($activos as $fila) {
    $id = (int) $fila['id_product'];
    $grupo = (int) $fila['con_stock'] === 1 ? 'con stock' : 'agotados';
    $n = min(3, count(KcsGuardadas::leer($id, 3)));
    ++$grupos[$grupo][$n];
    if ($n < 3 && $grupo === 'con stock' && count($pocas) < 20) {
        $pocas[] = $id;
    }
}
printf("Lo que enseña cada ficha ahora (%d productos activos, sin regalos):\n", count($activos));
foreach ($grupos as $grupo => $ve) {
    $total = array_sum($ve);
    printf("  %s (%d):\n", $grupo, $total);
    foreach ([3, 2, 1, 0] as $n) {
        printf("    %d sugerencias  %5d  %5.1f %%\n", $n, $ve[$n], $total ? $ve[$n] * 100 / $total : 0);
    }
}
if ($pocas !== []) {
    echo '  (con stock y menos de 3: #', implode(', #', $pocas), ")\n";
}
echo "\n";

$productos = count(array_unique(array_column($parejas, 'prod')));
printf("Parejas guardadas: %d, de %d productos (%d puestas a mano, que no se revisan)\n\n", count($parejas), $productos, $aMano);
$orden = ['mascota', 'mascota_nombre', 'patologia', 'enfermedad_nombre', 'comida_sana', 'edad', 'edad_nombre', 'distribuidor', 'no_se_vende', 'suplemento', 'vetada'];
$total = 0;
foreach ($orden as $tipo) {
    $n = count($fallos[$tipo] ?? []);
    $total += $n;
    printf("  %-14s %5d\n", $tipo, $n);
}
foreach ($orden as $tipo) {
    if (empty($fallos[$tipo])) {
        continue;
    }
    echo "\n{$tipo} (hasta 8):\n  ", implode("\n  ", array_slice($fallos[$tipo], 0, 8)), "\n";
}
echo $total === 0 ? "\nNinguna pareja con problemas.\n" : "\n{$total} parejas con algún problema.\n";
exit($total === 0 ? 0 : 1);
