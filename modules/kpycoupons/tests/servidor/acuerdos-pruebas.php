<?php
/**
 * Prueba de integración de **kpysupply** con **kpycoupons** en la tienda de pruebas. Vive aquí, en
 * `kpycoupons/tests/servidor/`, porque crea promociones de prueba con las clases de kpycoupons
 * (`KpcCupon`, `KpcAlcance`, `KpcContrato`, `KpcReparto`): en la carpeta de kpysupply no hay ni una
 * llamada a ellas, tampoco en sus pruebas. Solo corre en pruebas. Por consola dentro del contenedor
 * (desde /var/www/html/kompy):
 *   cat acuerdos-pruebas.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php --
 *
 * Escribe acuerdos de prueba sobre Boske (producto 8299) en fechas de octubre de 2026 y una
 * promoción de prueba (ACUERDO-TEST, cupón −5 % en Boske, primero apagada); al final lo borra todo,
 * pase lo que pase, y deja los ajustes como estaban. No crea pedidos: las ventas se comprueban sobre
 * los pedidos de prueba que ya hay (#866298, con regalo, del 16/10; #866301, 5 sacos, del 19/10).
 *
 * Los tres caminos por los que kpysupply cuadra el acuerdo de una promoción, con el contrato 3.0.0:
 *   - al guardar la promoción (`actionKpyCouponsGuardar`), también apagada: pide el alcance a
 *     kpycoupons por el hook de consulta `actionKpyCouponsAlcance` y espeja en el momento;
 *   - el botón «Poner al día con la promoción» de su pantalla (`kpsSincronizarPromo`): el mismo hook;
 *   - el reparto (`actionKpyCouponsReparto`): con lo que trae (`alcance`, `promociones`, `guardada`).
 * Y la respuesta nula del hook de consulta (una promoción que no existe): no se toca nada.
 */
require_once 'autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->usePutenv(false)->loadEnv(getcwd() . '/.env');
require 'config/config.inc.php';
$kernel = new FrontKernel('dev', false);
$kernel->boot();
require_once 'modules/kpysupply/controllers/admin/AdminKpySupplyController.php';

$ctx = Context::getContext();
Shop::setContext(Shop::CONTEXT_SHOP, 1);
$ctx->shop = new Shop(1);
$ctx->language = new Language(1);
$ctx->currency = new Currency(1);
$ctx->employee = new Employee(1);
$ctx->link = new Link();
$modulo = Module::getInstanceByName('kpysupply');
if (!$modulo instanceof Kpysupply) {
    fwrite(STDERR, "kpysupply no está instalado\n");
    exit(2);
}
if (!Module::getInstanceByName('kpycoupons') instanceof Kpycoupons) {
    fwrite(STDERR, "kpycoupons no está instalado\n");
    exit(2);
}

const S = 8299; const S12 = 92467; const S3 = 92468; const SP2 = 92469; const MARCA_BOSKE = 178;
const EAN12 = '8437019628055'; const EAN3 = '8437019628062'; const EAN_RC = '3182550846042';
const CODIGO_PROMO = 'ACUERDO-TEST';
const DESDE = '2026-10-15 00:00:00'; const HASTA = '2026-10-19 23:59:59'; // antes de hoy: la promoción de prueba empieza hoy
const URL_PANTALLA = 'index.php?controller=AdminKpySupply';

$fallos = 0;
$n = 0;
$repartoReal = false;
function igual(string $que, $esperado, $real): void
{
    global $fallos, $n;
    ++$n;
    if ($esperado === $real) {
        echo "  ok  $que\n";

        return;
    }
    ++$fallos;
    echo "  MAL $que\n      esperado: " . json_encode($esperado, JSON_UNESCAPED_UNICODE) . "\n      real:     " . json_encode($real, JSON_UNESCAPED_UNICODE) . "\n";
}
function cerca(string $que, float $esperado, float $real, float $margen = 0.011): void
{
    global $fallos, $n;
    ++$n;
    if (abs($esperado - $real) <= $margen) {
        echo "  ok  $que ($real)\n";

        return;
    }
    ++$fallos;
    echo "  MAL $que\n      esperado: $esperado\n      real:     $real\n";
}
function filas(string $donde = '', string $orden = 'id'): array
{
    return Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . KsAcuerdos::TABLA . ($donde !== '' ? ' WHERE ' . $donde : '') . ' ORDER BY ' . $orden) ?: [];
}
/** Para comparar dos arrays sin que importe el orden de las claves. */
function canon($x)
{
    if (!is_array($x)) {
        return $x;
    }
    ksort($x);
    foreach ($x as $k => $v) {
        $x[$k] = canon($v);
    }

    return $x;
}
/** La fila cruda de la promoción con sus ámbitos. */
function filaPromo(int $id): array
{
    $f = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $id) ?: [];
    $f['ambitos'] = KpcCupon::ambitosDe([$id])[$id] ?? [];

    return $f;
}
/** Su alcance (`entran` y `especial`), calculado aquí con KpcAlcance como lo calcula kpycoupons. */
function alcanceDe(int $id): array
{
    $a = KpcAlcance::articulos(filaPromo($id), 1);

    return ['entran' => (array) $a['entran'], 'especial' => (array) ($a['especial'] ?? [])];
}
/**
 * El hook del reparto, llamado como lo llama kpycoupons pero montado aquí: rápido y sin tocar la
 * tienda. `$ids`: las promociones en marcha; `$guardada`: la recién guardada; `$sinAlcance`: como
 * si kpycoupons no hubiera podido calcular los artículos de ninguna.
 */
function reparto(array $ids, int $guardada = 0, bool $sinAlcance = false): void
{
    global $modulo;
    $params = ['version' => KpcContrato::VERSION, 'id_shop' => 1, 'items' => [], 'avisos' => [], 'alcance' => [], 'promociones' => [], 'guardada' => $guardada];
    foreach ($ids as $id) {
        $params['items'][$id] = [];
        $params['promociones'][$id] = KpcContrato::promocion(filaPromo($id));
        if (!$sinAlcance) {
            $params['alcance'][$id] = alcanceDe($id);
        }
    }
    $modulo->hookActionKpyCouponsReparto($params);
}
/** El reparto de verdad de kpycoupons: el que lanza su pantalla tras guardar (con `guardada`) o el de cada cambio de fecha. Devuelve los segundos. */
function repartoReal(int $guardada = 0): float
{
    global $repartoReal;
    $repartoReal = true;
    $t = microtime(true);
    KpcReparto::rehacer(1, $guardada);

    return round(microtime(true) - $t, 1);
}
/** El hook del guardar, como lo lanza la pantalla de kpycoupons (fila pública, sin el objeto). */
function hookGuardar(int $id, array $post): void
{
    global $modulo;
    $_POST = $post;
    $modulo->hookActionKpyCouponsGuardar(['version' => KpcContrato::VERSION, 'id' => $id, 'valores' => [], 'promocion' => KpcContrato::promocion(filaPromo($id))]);
    $_POST = [];
}
/** Guardar la promoción como lo hace la pantalla de kpycoupons: el hook del guardar y, detrás, el reparto con `guardada`. */
function guardarPromo(int $id, array $post, bool $real = false): void
{
    hookGuardar($id, $post);
    if ($real) {
        repartoReal($id);
    } else {
        reparto([$id], $id);
    }
}
/**
 * El botón «Poner al día con la promoción» de la pantalla de kpysupply, por su camino real (el
 * controlador, `kpsSincronizarPromo`). La redirección final no funciona por consola (no hay
 * `_PS_ADMIN_DIR_`): se recoge ese error y se mira lo que ha hecho antes.
 */
function botonPonerAlDia(int $id): string
{
    global $ctx, $modulo;
    $ctrl = (new ReflectionClass('AdminKpySupplyController'))->newInstanceWithoutConstructor();
    $ctrl->module = $modulo;
    (function () use ($ctx) { $this->context = $ctx; })->call($ctrl);
    $_POST = $_REQUEST = ['kpsSincronizarPromo' => '1', 'id' => $id];
    $error = '';
    try {
        $m = new ReflectionMethod($ctrl, 'procesar');
        $m->setAccessible(true);
        $m->invoke($ctrl, URL_PANTALLA);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    } finally {
        $_POST = $_REQUEST = [];
    }

    return $error;
}
function limpiar(): void
{
    global $repartoReal;
    $db = Db::getInstance();
    $p = _DB_PREFIX_;
    // Todo lo de Boske escrito por estas pruebas (fechas de octubre a diciembre de 2026) y la promoción de prueba.
    $db->execute("DELETE s FROM {$p}" . KsAcuerdos::TABLA . " s INNER JOIN {$p}product pr ON pr.id_product = s.id_product WHERE pr.id_manufacturer = " . MARCA_BOSKE . " AND s.date_from >= '2026-10-01' AND s.date_from < '2027-01-01'");
    $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA . " WHERE id_product IN (294, 298) AND date_from >= '2026-10-01' AND date_from < '2027-01-01'");
    foreach ($db->executeS("SELECT id_kpy_coupon FROM {$p}kpy_coupon WHERE codigo = '" . CODIGO_PROMO . "'") ?: [] as $f) {
        $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA . ' WHERE id_kpy_coupon = ' . (int) $f['id_kpy_coupon']);
        $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA_PROMO . ' WHERE id_kpy_coupon = ' . (int) $f['id_kpy_coupon']);
        $c = new KpcCupon((int) $f['id_kpy_coupon']);
        if (Validate::isLoadedObject($c)) {
            $c->delete();
        }
    }
    KpcCupon::olvidarCache();
    // Si ha corrido el reparto de verdad con la promoción de prueba viva, otro sin ella deja la tienda como estaba.
    if ($repartoReal) {
        KpcReparto::rehacer(1);
        $repartoReal = false;
    }
}
$antes = count(filas());
$estadosAntes = Configuration::getGlobalValue(KsVentas::CONF_ESTADOS);
$avisosAntes = Configuration::getGlobalValue(Kpysupply::CONF_AVISOS);
limpiar();

try {
    $p = _DB_PREFIX_;
    $db = Db::getInstance();

    echo "A. Tablas, tipos, hooks y pestaña\n";
    igual('las tres tablas existen', [true, true, true], [KsVentas::hayTabla('kpy_supplier_promo_type'), KsVentas::hayTabla('kpy_supplier_promo_products'), KsVentas::hayTabla('kpy_supplier_promo_coupon')]);
    $tipos = $db->executeS("SELECT nombre FROM {$p}kpy_supplier_promo_type ORDER BY id") ?: [];
    igual('con los tres tipos, letra por letra', ['redención', 'Dto en Factura', 'Precio de coste actualizado'], array_column($tipos, 'nombre'));
    $ids = KsTipos::ids();
    igual('cada clave con su id', true, $ids['redencion'] > 0 && $ids['factura'] > 0 && $ids['coste'] > 0 && count(array_unique($ids)) === 3);
    $col = ($db->executeS("SHOW COLUMNS FROM {$p}kpy_supplier_promo_products LIKE 'date_to'") ?: [[]])[0];
    igual('date_to no admite NULL (la clave única vale también para «hasta nueva orden»)', 'NO', (string) ($col['Null'] ?? ''));
    igual('las columnas id_kpy_coupon y origen', [true, true], [(bool) $db->executeS("SHOW COLUMNS FROM {$p}kpy_supplier_promo_products LIKE 'id_kpy_coupon'"), (bool) $db->executeS("SHOW COLUMNS FROM {$p}kpy_supplier_promo_products LIKE 'origen'")]);
    $hooks = array_column($db->executeS("SELECT h.name FROM {$p}hook h INNER JOIN {$p}hook_module hm ON hm.id_hook = h.id_hook INNER JOIN {$p}module m ON m.id_module = hm.id_module WHERE m.name = 'kpysupply' ORDER BY h.name") ?: [], 'name');
    igual('los tres hooks de kpycoupons enganchados (el de consulta lo dispara, no lo escucha)', ['actionKpyCouponsGuardar', 'actionKpyCouponsReparto', 'displayKpyCouponsFormulario'], $hooks);
    igual('kpycoupons contesta el hook de consulta actionKpyCouponsAlcance', ['kpycoupons'], array_column($db->executeS("SELECT m.name FROM {$p}hook h INNER JOIN {$p}hook_module hm ON hm.id_hook = h.id_hook INNER JOIN {$p}module m ON m.id_module = hm.id_module WHERE h.name = 'actionKpyCouponsAlcance'") ?: [], 'name'));
    igual('la pestaña en Catálogo', (int) Tab::getIdFromClassName('AdminCatalog'), (int) $db->getValue("SELECT id_parent FROM {$p}tab WHERE class_name = 'AdminKpySupply'"));
    $versionKpc = (string) $db->getValue("SELECT version FROM {$p}module WHERE name = 'kpycoupons'");
    $versionFicheros = Kpysupply::versionEnFicheros('kpycoupons');
    igual('la versión de kpycoupons en ficheros se lee sin cargar el módulo', 1, preg_match('/^\d+\.\d+\.\d+$/', $versionFicheros));
    echo "  (kpycoupons: ficheros {$versionFicheros}, base de datos {$versionKpc}; kpysupply pide la " . Kpysupply::KPYCOUPONS_MINIMA . ")\n";
    igual('kpysupply pide la 3.0.0 de kpycoupons (el contrato)', '3.0.0', Kpysupply::KPYCOUPONS_MINIMA);
    igual('kpycoupons está al día para kpysupply', '', Kpysupply::faltaKpycoupons());
    try {
        $db->update('module', ['version' => '2.4.1'], "name = 'kpycoupons'");
        $falta = Kpysupply::faltaKpycoupons();
    } finally {
        $db->update('module', ['version' => pSQL($versionKpc)], "name = 'kpycoupons'");
    }
    igual('con kpycoupons 2.4.1 en la base de datos (el upgrade sin hacer), la instalación se negaría y lo diría', true, strpos($falta, '2.4.1 (base de datos)') !== false && strpos($falta, '3.0.0') !== false);
    igual('y la versión de kpycoupons vuelve a estar como estaba', $versionKpc, (string) $db->getValue("SELECT version FROM {$p}module WHERE name = 'kpycoupons'"));
    $clave = KsAcuerdos::claveLote(KsTipos::id('redencion'), '2026-11-01 00:00:00', null, 0, MARCA_BOSKE);
    igual('la clave de un lote es tipo, fechas en cifras, promoción y marca', KsTipos::id('redencion') . '_20261101000000_0_0_' . MARCA_BOSKE, $clave);
    igual('y vuelve al mismo lote', ['2026-11-01 00:00:00', null, true], [KsAcuerdos::lote($clave)['desde'], KsAcuerdos::lote($clave)['hasta'], KsAcuerdos::lote($clave)['sin_fin']]);
    igual('una clave con una fecha imposible no es un lote', null, KsAcuerdos::lote(KsTipos::id('redencion') . '_20261301000000_0_0_' . MARCA_BOSKE));

    echo "\nB. Una lista pegada, comprobada contra la tienda (con el lector propio)\n";
    $indice = KsLectura::indice(1);
    $indiceKpc = KpcLista::indice(1);
    igual('la copia propia del lector (KsLectura) saca el mismo índice del catálogo que KpcLista', true, canon($indice) === canon($indiceKpc));
    echo '  (índice: ' . count($indice['producto']) . ' productos, ' . count($indice['combinacion']) . ' formatos, ' . count($indice['apagada']) . ' apagados, ' . count($indice['padre']) . " packs)\n";
    $lista = "EAN\tDto.\n" . EAN12 . "\t15 %\n" . EAN3 . "\t\n" . EAN_RC . "\t20 %\n9999999999999\t10 %";
    igual('y lee la misma lista igual que KpcLista', KpcLista::buscar(KpcLista::celdas($lista), 'codigo', $indiceKpc, 'pct'), KsLectura::buscar(KsLectura::celdas($lista), 'codigo', $indice, 'pct'));
    $r = KsLista::comprobar(KsLectura::celdas($lista), 'pct', 10.0, $indice);
    igual('Boske 12 kg al 15, Boske 3 kg al general, Royal Canin al 20; el falso no', [S . ':15', S . ':10', '298:20'], array_map(static fn ($i) => $i['id_product'] . ':' . $i['valor'], $r['items']));
    $ipaRC = (int) $r['items'][2]['ipa'];
    igual('el falso, dicho', ['9999999999999'], $r['no']);
    $datos = KsAcuerdos::datosArticulos([[S, S12], [S, S3], [298, $ipaRC]]);
    igual('los nombres salen', true, strpos((string) $datos[S . '-' . S12]['producto'], 'Boske') !== false && $datos[S . '-' . S12]['formato'] !== '' && $datos['298-' . $ipaRC]['marca'] !== '');
    igual('y el coste de PrestaShop y el EAN de la combinación', [true, EAN12], [$datos[S . '-' . S12]['coste'] > 0, $datos[S . '-' . S12]['ean']]);

    echo "\nC. Guardar un acuerdo suelto (redención 10 %, del 15 al 19 de octubre)\n";
    $items = [['id_product' => S, 'ipa' => S12, 'valor' => 10.0], ['id_product' => S, 'ipa' => S3, 'valor' => 10.0]];
    $g = KsAcuerdos::guardar($items, KsTipos::id('redencion'), DESDE, HASTA, null, 'parar');
    igual('dos filas nuevas', ['guardados' => 2, 'actualizados' => 0, 'iguales' => 0, 'saltados' => 0, 'recortados' => 0, 'borrados' => 0], $g);
    $f = filas('id_product = ' . S . " AND date_from = '" . DESDE . "'");
    igual('con su tipo, valor, fechas y origen', [[S12, KsTipos::id('redencion'), '10.000000', DESDE, HASTA, null, 'lista'], [S3, KsTipos::id('redencion'), '10.000000', DESDE, HASTA, null, 'lista']],
        array_map(static fn ($x) => [(int) $x['id_product_attribute'], (int) $x['id_supplier_promo_type'], $x['value'], $x['date_from'], $x['date_to'], $x['id_kpy_coupon'], $x['origen']], $f));
    $g = KsAcuerdos::guardar($items, KsTipos::id('redencion'), DESDE, HASTA, null, 'parar');
    igual('la misma lista otra vez: nada que hacer', ['guardados' => 0, 'actualizados' => 0, 'iguales' => 2, 'saltados' => 0], array_intersect_key($g, ['guardados' => 1, 'actualizados' => 1, 'iguales' => 1, 'saltados' => 1]));
    $g = KsAcuerdos::guardar([['id_product' => S, 'ipa' => S12, 'valor' => 12.0]], KsTipos::id('redencion'), DESDE, HASTA, null, 'saltar');
    igual('otro valor con «saltar»: no se toca', [1, '10.000000'], [$g['saltados'], filas('id_product_attribute = ' . S12 . " AND date_from = '" . DESDE . "'")[0]['value']]);
    $g = KsAcuerdos::guardar([['id_product' => S, 'ipa' => S12, 'valor' => 12.0]], KsTipos::id('redencion'), DESDE, HASTA, null, 'sustituir');
    igual('con «sustituir»: se actualiza, no se duplica', [1, 1, '12.000000'], [$g['actualizados'], count(filas('id_product_attribute = ' . S12 . " AND date_from = '" . DESDE . "'")), filas('id_product_attribute = ' . S12 . " AND date_from = '" . DESDE . "'")[0]['value']]);
    // Todo o nada: una lista en la que la segunda fila no cabe en la base de datos (aquí, el mismo
    // artículo dos veces: la clave única lo rechaza) no deja ni la primera. (Un número fuera de rango
    // no vale para probarlo: PrestaShop trabaja con sql_mode vacío y MySQL lo recorta sin error.)
    $antesDeFallar = count(filas('id_product = ' . S));
    $fallo = '';
    try {
        KsAcuerdos::guardar([['id_product' => S, 'ipa' => SP2, 'valor' => 5.0], ['id_product' => S, 'ipa' => SP2, 'valor' => 6.0]], KsTipos::id('coste'), '2026-12-10 00:00:00', '2026-12-12 23:59:59', null, 'parar');
    } catch (Throwable $e) {
        $fallo = $e->getMessage();
    }
    igual('guardar es todo o nada: la lista que falla a medias no deja nada', [true, $antesDeFallar], [$fallo !== '', count(filas('id_product = ' . S))]);

    echo "\nD. Solapes, recortes y «hasta nueva orden»\n";
    $nuevo = [['id_product' => S, 'ipa' => S12, 'valor' => 15.0], ['id_product' => S, 'ipa' => SP2, 'valor' => 15.0]];
    $desde2 = '2026-10-18 00:00:00';
    $hasta2 = '2026-10-25 23:59:59';
    $sol = KsLista::solapes($nuevo, $desde2, $hasta2, KsAcuerdos::existentes($nuevo, $desde2, $hasta2));
    igual('el 12 kg se cruza con el acuerdo del 15 al 19; el pack no', [S . '-' . S12], array_keys($sol));
    $g = KsAcuerdos::guardar($nuevo, KsTipos::id('factura'), $desde2, $hasta2, null, 'saltar');
    igual('saltar: el 12 kg no se guarda, el pack sí', ['guardados' => 1, 'saltados' => 1], array_intersect_key($g, ['guardados' => 1, 'saltados' => 1]));
    $g = KsAcuerdos::guardar([['id_product' => S, 'ipa' => S12, 'valor' => 15.0]], KsTipos::id('factura'), $desde2, $hasta2, null, 'sustituir');
    igual('sustituir: el 12 kg se guarda y el anterior se recorta', ['guardados' => 1, 'recortados' => 1, 'borrados' => 0], array_intersect_key($g, ['guardados' => 1, 'recortados' => 1, 'borrados' => 1]));
    $f = filas('id_product = ' . S . ' AND id_product_attribute = ' . S12, 'date_from');
    igual('el anterior acaba el 17 a las 23:59:59 y el nuevo empieza el 18', [[DESDE, '2026-10-17 23:59:59'], [$desde2, $hasta2]], array_map(static fn ($x) => [$x['date_from'], $x['date_to']], $f));
    KsAcuerdos::guardar([['id_product' => S, 'ipa' => S12, 'valor' => 9.0]], KsTipos::id('coste'), '2026-10-16 00:00:00', '2026-10-16 23:59:59', null, 'sustituir');
    $f = filas('id_product = ' . S . ' AND id_product_attribute = ' . S12, 'date_from');
    igual('un día en medio parte el anterior en dos trozos', [[DESDE, '2026-10-15 23:59:59'], ['2026-10-16 00:00:00', '2026-10-16 23:59:59'], ['2026-10-17 00:00:00', '2026-10-17 23:59:59'], [$desde2, $hasta2]], array_map(static fn ($x) => [$x['date_from'], $x['date_to']], $f));
    igual('y el trozo de detrás conserva tipo, valor y origen', [KsTipos::id('redencion'), '12.000000', 'lista'], [(int) $f[2]['id_supplier_promo_type'], $f[2]['value'], $f[2]['origen']]);
    // «Hasta nueva orden»: dos veces la misma lista no duplica, y la base de datos tampoco lo deja.
    $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA . ' WHERE id_product = ' . S . " AND date_from >= '2026-10-15' AND date_from < '2026-11-01'");
    KsAcuerdos::guardar([['id_product' => S, 'ipa' => S3, 'valor' => 7.5]], KsTipos::id('coste'), '2026-11-01 00:00:00', null, null, 'parar');
    $g = KsAcuerdos::guardar([['id_product' => S, 'ipa' => S3, 'valor' => 7.5]], KsTipos::id('coste'), '2026-11-01 00:00:00', null, null, 'parar');
    $f = filas('id_product_attribute = ' . S3 . " AND date_from = '2026-11-01 00:00:00'");
    igual('hasta nueva orden: una sola fila, guardada como 2099-12-31 23:59:59', [1, KsAcuerdos::SIN_FIN, 1], [count($f), $f[0]['date_to'] ?? '', $g['iguales']]);
    $duplicada = true;
    try {
        $duplicada = (bool) $db->insert(KsAcuerdos::TABLA, ['id_product' => S, 'id_product_attribute' => S3, 'id_supplier_promo_type' => KsTipos::id('coste'), 'value' => 7.0,
            'date_from' => '2026-11-01 00:00:00', 'date_to' => KsAcuerdos::SIN_FIN, 'origen' => 'lista', 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);
    } catch (Throwable $e) {
        $duplicada = false;
    }
    igual('la clave única no deja meter otra igual', [false, 1], [$duplicada, count(filas('id_product_attribute = ' . S3 . " AND date_from = '2026-11-01 00:00:00'"))]);
    $lSinFin = array_values(array_filter(KsAcuerdos::lotes(), static fn ($l) => (int) $l['id_manufacturer'] === MARCA_BOSKE && $l['desde'] === '2026-11-01 00:00:00'));
    igual('su lote dice «hasta nueva orden»', [1, true, 'desde el 01/11/2026, hasta nueva orden'], [count($lSinFin), $lSinFin[0]['sin_fin'] ?? null, $lSinFin[0]['fechas'] ?? '']);
    $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA . ' WHERE id_product = ' . S . " AND date_from = '2026-11-01 00:00:00'");
    KsAcuerdos::guardar($items, KsTipos::id('redencion'), DESDE, HASTA, null, 'parar');

    echo "\nE. Los lotes\n";
    $lotes = array_values(array_filter(KsAcuerdos::lotes(), static fn ($l) => (int) $l['id_manufacturer'] === MARCA_BOSKE && $l['desde'] === DESDE));
    igual('un lote de Boske, redención 10 %, dos artículos', 1, count($lotes));
    $lote = $lotes[0];
    igual('con su texto', ['Redención', '10 %', 2, 'del 15/10/2026 al 19/10/2026', 0], [$lote['tipo_titulo'], $lote['valor_texto'], $lote['n'], $lote['fechas'], $lote['solapes']]);
    igual('la clave vuelve al mismo lote', $lote['clave'], KsAcuerdos::lote($lote['clave'])['clave']);
    $eans = array_values(array_map(static fn ($x) => $x['ean'], KsAcuerdos::filasDeLote($lote)));
    sort($eans);
    igual('sus filas, con nombre y EAN', [EAN12, EAN3], $eans);

    echo "\nF. Las ventas del periodo (sobre los pedidos de prueba que ya hay)\n";
    $v = KsVentas::deLote($lote, false);
    echo "  (pedidos pagados: {$v['n_pedidos']}; pendientes de pago fuera: {$v['pendientes_fuera']})\n";
    $v = KsVentas::deLote($lote, true);
    igual('con los pendientes entran los pedidos de prueba', true, $v['n_pedidos'] > 0);
    $l866298 = array_column(array_values(array_filter($v['detalle'], static fn ($d) => (int) $d['id_order'] === 866298)), null, 'clave');
    cerca('#866298: el 12 kg, a lo cobrado (116,35 menos el vale del Special Price)', 110.53, (float) ($l866298[S . '-' . S12]['neto'] ?? 0));
    cerca('#866298: el 3 kg de regalo, a 0', 0.0, (float) ($l866298[S . '-' . S3]['neto'] ?? 99));
    igual('#866298: 2 + 1 unidades', [2, 1], [(int) ($l866298[S . '-' . S12]['uds'] ?? 0), (int) ($l866298[S . '-' . S3]['uds'] ?? 0)]);
    igual('#866298: la del 3 kg, de regalo; las del 12 kg, no', [0, 1], [(int) ($l866298[S . '-' . S12]['regalo'] ?? -1), (int) ($l866298[S . '-' . S3]['regalo'] ?? -1)]);
    igual('los regalos del total son los del detalle', array_sum(array_column($v['detalle'], 'regalo')), (int) $v['totales']['regalo']);
    cerca('#866298: la redención del 12 kg es el 10 %', 11.05, (float) ($l866298[S . '-' . S12]['importe'] ?? 0));
    cerca('el total es la suma del detalle', round(array_sum(array_column($v['detalle'], 'neto')), 2), (float) $v['totales']['neto']);
    cerca('y el importe total, el 10 % de las ventas', round((float) $v['totales']['neto'] * 0.10, 2), (float) $v['totales']['importe'], 0.02);
    igual('solo artículos del acuerdo', [], array_values(array_diff(array_unique(array_column($v['detalle'], 'clave')), [S . '-' . S12, S . '-' . S3])));
    Configuration::updateGlobalValue(KsVentas::CONF_ESTADOS, implode(',', array_merge(KsVentas::estadosFueraPorDefecto(), [(int) Configuration::get('PS_OS_BANKWIRE')])));
    igual('con «espera de transferencia» marcado como fuera, esos pedidos ya no cuentan', 0, KsVentas::deLote($lote, true)['n_pedidos']);
    Configuration::updateGlobalValue(KsVentas::CONF_ESTADOS, (string) $estadosAntes);
    igual('el Excel de ventas se genera', 'PK', substr((string) KsExcel::libro([['nombre' => 'Por artículo', 'cabecera' => ['EAN', 'Uds', 'Ventas'], 'filas' => array_map(static fn ($a) => [$a['ean'], (int) $a['uds'], (float) $a['neto']], array_values($v['articulos']))]]), 0, 2));
    $loteCoste = KsAcuerdos::lote(KsAcuerdos::claveLote(KsTipos::id('coste'), DESDE, HASTA, 0, MARCA_BOSKE));
    KsAcuerdos::guardar([['id_product' => S, 'ipa' => S12, 'valor' => 30.0]], KsTipos::id('coste'), DESDE, HASTA, null, 'sustituir');
    $vc = KsVentas::deLote($loteCoste, true);
    $a12 = $vc['articulos'][S . '-' . S12] ?? null;
    igual('precio de coste: coste de lo vendido = unidades × precio, y margen = ventas − coste', true, $a12 !== null && abs($a12['coste'] - $a12['uds'] * 30.0) < 0.01 && abs($a12['margen'] - ($a12['neto'] - $a12['coste'])) < 0.01);
    $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA . ' WHERE id_product = ' . S . " AND date_from >= '2026-10-15' AND date_from < '2026-11-01'");
    KsAcuerdos::guardar($items, KsTipos::id('redencion'), DESDE, HASTA, null, 'parar');

    echo "\nG. El acuerdo de una promoción (paso del formulario): al guardar, en el momento, por el hook de consulta\n";
    // La promoción de prueba nace APAGADA: no entra en ningún reparto, y el acuerdo tiene que cuadrarse igual al guardarla.
    $promo = new KpcCupon();
    foreach (['codigo' => CODIGO_PROMO, 'nombre' => 'Prueba de acuerdo', 'tipo' => 'cupon', 'porcentaje' => 5, 'activo' => 0,
        'fecha_desde' => date('Y-m-d H:i:00', time() - 300), 'fecha_hasta' => date('Y-m-d 23:59:59', strtotime('+7 days')),
        'en_ficha' => 1, 'excluir_especial' => 0, 'incluir_packs' => 0, 'packs' => '1', 'sin_outlet' => 1, 'en_merchant' => 0, 'cupo_ficha' => 1, 'id_shop' => 1] as $k => $val) {
        $promo->{$k} = $val;
    }
    if (!$promo->add()) {
        throw new RuntimeException('no se ha podido crear la promoción de prueba');
    }
    $promo->guardarAmbitos([['tipo' => 'marca', 'id' => MARCA_BOSKE]]);
    $idPromo = (int) $promo->id;
    $alcance = alcanceDe($idPromo);
    // Los del paso 2: los que entran y los apartados por otro Special Price (el acuerdo es sobre la gama).
    $entranClaves = $alcance['entran'] + $alcance['especial'];
    $entran = count($entranClaves);
    echo "  (en la promoción entran {$entran} artículos de Boske; " . count($alcance['especial']) . " apartados por Special Price)\n";
    $a = KsAcuerdos::alcanceDeKpycoupons($idPromo, 1);
    igual('kpycoupons contesta el alcance de la promoción (apagada) por su hook de consulta: versión 1, los mismos artículos que KpcAlcance y la fila pública', [1, true, 'apagada', (string) $promo->fecha_hasta, CODIGO_PROMO],
        [$a['version'] ?? 0, canon($a['entran'] ?? []) === canon($alcance['entran']) && canon($a['especial'] ?? []) === canon($alcance['especial']), $a['promocion']['estado'] ?? '', $a['promocion']['fecha_hasta'] ?? '', $a['promocion']['codigo'] ?? '']);
    igual('para una promoción que no existe, no contesta (null)', null, KsAcuerdos::alcanceDeKpycoupons(999999, 1));
    igual('en los repartos la promoción apagada no está', false, in_array($idPromo, array_map('intval', array_column(KpcReparto::cupones(1), 'id_kpy_coupon')), true));
    hookGuardar($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'redencion', 'kps_valor_redencion' => '12,5']);
    $f = filas('id_kpy_coupon = ' . $idPromo);
    igual('al guardar la promoción (apagada), el paso apunta lo marcado y cuadra el acuerdo en el momento: una fila del paso por artículo', [['tipo' => 'redencion', 'valor' => 12.5], $entran, ['promo']], [KsAcuerdos::configDe($idPromo), count($f), array_values(array_unique(array_column($f, 'origen')))]);
    igual('con el 12,5 %, el tipo redención y las fechas de la promoción', [KsTipos::id('redencion'), '12.500000', (string) $promo->fecha_desde, (string) $promo->fecha_hasta],
        [(int) $f[0]['id_supplier_promo_type'], $f[0]['value'], $f[0]['date_from'], $f[0]['date_to']]);
    $d = KsAcuerdos::dePromo($idPromo);
    igual('el texto del paso', 'Redención 12,5 % en ' . $entran . ' artículos', KsAcuerdos::textoDePromo($d));
    $parametrosPaso = ['version' => KpcContrato::VERSION, 'id' => $idPromo, 'valores' => [], 'duplicar' => 0, 'url_editar' => 'index.php?controller=AdminKpyPromociones&editar=' . $idPromo, 'url_ver' => ''];
    $html = $modulo->hookDisplayKpyCouponsFormulario($parametrosPaso + ['paso' => 7]);
    igual('el paso se pinta con el acuerdo marcado', true, strpos($html, 'name="kps_tipo" value="redencion" checked') !== false && strpos($html, 'value="12,5"') !== false);
    igual('y con el número que le dice kpycoupons (paso 7)', true, strpos($html, '<span class="kpc-num">7</span>') !== false);
    igual('sin número de kpycoupons, el 6', true, strpos($modulo->hookDisplayKpyCouponsFormulario($parametrosPaso), '<span class="kpc-num">6</span>') !== false);
    $html = $modulo->hookDisplayKpyCouponsFormulario(['version' => KpcContrato::VERSION, 'id' => 0, 'valores' => [], 'duplicar' => $idPromo, 'paso' => 6]);
    igual('al duplicar la promoción, el paso sale como el de la original', true, strpos($html, 'name="kps_tipo" value="redencion" checked') !== false);
    hookGuardar($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'redencion', 'kps_valor_redencion' => 'muchos']);
    igual('un % que no se entiende no cambia nada', ['redencion', 12.5, $entran], [KsAcuerdos::configDe($idPromo)['tipo'], KsAcuerdos::configDe($idPromo)['valor'], count(filas('id_kpy_coupon = ' . $idPromo))]);
    $html = $modulo->hookDisplayKpyCouponsFormulario($parametrosPaso + ['paso' => 6]);
    igual('y el paso lo dice la próxima vez, una sola vez', [true, false], [strpos($html, '«muchos»') !== false, strpos($modulo->hookDisplayKpyCouponsFormulario($parametrosPaso + ['paso' => 6]), '«muchos»') !== false]);
    hookGuardar($idPromo, ['kps_tipo' => 'factura', 'kps_valor_factura' => '10']);
    igual('sin el campo del paso en el formulario, no se toca nada', 'redencion', KsAcuerdos::configDe($idPromo)['tipo']);
    // Encendida: a partir de aquí entra en los repartos.
    $db->update('kpy_coupon', ['activo' => 1], 'id_kpy_coupon = ' . $idPromo);
    KpcCupon::olvidarCache();
    $s = repartoReal($idPromo);
    echo "  (encendida; el reparto de verdad de kpycoupons con guardada = {$idPromo}: {$s} s)\n";
    igual('el reparto que sigue al guardar vuelve a espejar: no cambia nada (idempotente)', [$entran, ['promo'], ['12.500000']], [count(filas('id_kpy_coupon = ' . $idPromo)), array_values(array_unique(array_column(filas('id_kpy_coupon = ' . $idPromo), 'origen'))), array_values(array_unique(array_column(filas('id_kpy_coupon = ' . $idPromo), 'value')))]);

    echo "\nH. La promoción cambia: el acuerdo la sigue (el reparto, y lo que el reparto no trae no se toca)\n";
    $nuevaHasta = date('Y-m-d 23:59:59', strtotime('+21 days'));
    $db->update('kpy_coupon', ['fecha_hasta' => $nuevaHasta], 'id_kpy_coupon = ' . $idPromo);
    guardarPromo($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'factura', 'kps_valor_factura' => '15']);
    $f = filas('id_kpy_coupon = ' . $idPromo);
    igual('mismas filas, ahora descuento en factura 15 % hasta la fecha nueva', [$entran, [KsTipos::id('factura')], ['15.000000'], [$nuevaHasta]],
        [count($f), array_values(array_unique(array_map('intval', array_column($f, 'id_supplier_promo_type')))), array_values(array_unique(array_column($f, 'value'))), array_values(array_unique(array_column($f, 'date_to')))]);
    $db->delete(KsAcuerdos::TABLA, 'id = ' . (int) $f[0]['id']);
    $s = repartoReal();
    echo "  (un reparto de verdad por fecha, sin guardada: {$s} s)\n";
    igual('tras el reparto vuelve a estar la que faltaba', $entran, count(filas('id_kpy_coupon = ' . $idPromo)));
    $db->delete(KsAcuerdos::TABLA, 'id = ' . (int) filas('id_kpy_coupon = ' . $idPromo)[0]['id']);
    reparto([]);
    igual('un reparto en el que la promoción no está en marcha no toca nada', $entran - 1, count(filas('id_kpy_coupon = ' . $idPromo)));
    $mensaje = 'kpysupply, promoción ' . $idPromo . ': el reparto no trae su alcance';
    $logsAntes = (int) $db->getValue("SELECT COUNT(*) FROM {$p}log WHERE message LIKE '" . pSQL($mensaje) . "%'");
    reparto([$idPromo], $idPromo, true);
    igual('una con acuerdo que viene en el reparto sin su alcance (kpycoupons no pudo calcularlo) no se toca, ni siendo la guardada', $entran - 1, count(filas('id_kpy_coupon = ' . $idPromo)));
    igual('y se apunta en el registro', $logsAntes + 1, (int) $db->getValue("SELECT COUNT(*) FROM {$p}log WHERE message LIKE '" . pSQL($mensaje) . "%'"));
    reparto([$idPromo]);
    igual('en el siguiente reparto con su alcance, vuelve', $entran, count(filas('id_kpy_coupon = ' . $idPromo)));
    $lotesPromo = array_values(array_filter(KsAcuerdos::lotes(), static fn ($l) => (int) $l['id_kpy_coupon'] === $idPromo));
    igual('en la pantalla, un lote con su promoción', [1, CODIGO_PROMO, 'Descuento en factura', '15 %'], [count($lotesPromo), $lotesPromo[0]['promo']['codigo'] ?? '', $lotesPromo[0]['tipo_titulo'] ?? '', $lotesPromo[0]['valor_texto'] ?? '']);

    echo "\nI. El botón «Poner al día con la promoción» (kpsSincronizarPromo), por el hook de consulta\n";
    $db->delete(KsAcuerdos::TABLA, 'id = ' . (int) filas('id_kpy_coupon = ' . $idPromo)[0]['id']);
    $r = KsAcuerdos::ponerAlDia($idPromo, 1);
    igual('ponerAlDia(): pide el alcance a kpycoupons y espeja', [true, $entran, 1, 0], [$r['ok'], $r['cuentas']['articulos'] ?? -1, $r['cuentas']['creados'] ?? -1, $r['cuentas']['borrados'] ?? -1]);
    igual('y lo cuenta', true, strpos($r['texto'], 'Acuerdo de la promoción al día: ' . $entran . ' artículos en la promoción, 1 filas nuevas, 0 quitadas') === 0);
    $r = KsAcuerdos::ponerAlDia(999999, 1);
    igual('una promoción que no existe: kpycoupons no contesta, no se toca nada y se dice', [false, null, 'kpycoupons no contesta: no se ha tocado el acuerdo de la promoción #999999.'], [$r['ok'], $r['cuentas'], $r['texto']]);
    $db->delete(KsAcuerdos::TABLA, 'id = ' . (int) filas('id_kpy_coupon = ' . $idPromo)[0]['id']);
    $error = botonPonerAlDia($idPromo);
    igual('el botón, por el controlador: repone la fila que faltaba (y acaba en la redirección, que por consola no vale)', [$entran, true], [count(filas('id_kpy_coupon = ' . $idPromo)), strpos($error, '_PS_ADMIN_DIR_') !== false]);
    $antesBoton = count(filas());
    botonPonerAlDia(999999);
    igual('el botón con una promoción que no existe no toca nada', $antesBoton, count(filas()));

    echo "\nJ. Una lista enlazada a la promoción manda sobre el % del paso\n";
    $g = KsAcuerdos::guardar([['id_product' => S, 'ipa' => S12, 'valor' => 20.0]], KsTipos::id('factura'), (string) $promo->fecha_desde, $nuevaHasta, $idPromo, 'sustituir');
    $f12 = filas('id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . S12);
    igual('el 12 kg pasa a la lista con su 20 %, sin duplicarse', [1, 1, '20.000000', 'lista'], [$g['actualizados'], count($f12), $f12[0]['value'] ?? '', $f12[0]['origen'] ?? '']);
    guardarPromo($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'factura', 'kps_valor_factura' => '16']);
    $f12 = filas('id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . S12);
    igual('al guardar la promoción con el 16 %, el 12 kg sigue al 20 % y los demás al 16 %', [1, '20.000000', ['16.000000']], [count($f12), $f12[0]['value'] ?? '', array_values(array_unique(array_column(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'promo'"), 'value')))]);
    igual('el lote dice «de 16 % a 20 %» y uno por lista', ['de 16 % a 20 %', 1], (static function () use ($idPromo) {
        $l = array_values(array_filter(KsAcuerdos::lotes(), static fn ($x) => (int) $x['id_kpy_coupon'] === $idPromo))[0] ?? [];

        return [$l['valor_texto'] ?? '', $l['n_lista'] ?? -1];
    })());
    igual('el paso lo cuenta', 'Descuento en factura 16 % en ' . ($entran - 1) . ' artículos y 1 artículo con su propio valor por lista (Descuento en factura)', KsAcuerdos::textoDePromo(KsAcuerdos::dePromo($idPromo)));
    // «Sin acuerdo» al guardar: lo marcado se quita y las filas del paso se van en el momento (hook de consulta); la de lista se queda.
    hookGuardar($idPromo, ['kps_paso' => '1', 'kps_tipo' => '']);
    igual('«Sin acuerdo» al guardar: lo marcado se quita, se van las del paso en el momento y se queda la de lista', [null, 0, 1], [KsAcuerdos::configDe($idPromo), count(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'promo'")), count(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'lista'"))]);
    // El reparto solo quita en la guardada: con lo marcado quitado a secas (como si kpycoupons no hubiera contestado al guardar) y filas del paso aún puestas…
    guardarPromo($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'factura', 'kps_valor_factura' => '16']);
    KsAcuerdos::guardarConfig($idPromo, '', null);
    reparto([$idPromo]);
    igual('…un reparto por fecha deja las filas del paso', $entran - 1, count(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'promo'")));
    reparto([$idPromo], $idPromo);
    igual('y el reparto de la guardada las quita', [0, 1], [count(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'promo'")), count(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'lista'"))]);
    $otraHasta = date('Y-m-d 23:59:59', strtotime('+28 days'));
    $db->update('kpy_coupon', ['fecha_hasta' => $otraHasta], 'id_kpy_coupon = ' . $idPromo);
    guardarPromo($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'factura', 'kps_valor_factura' => '15']);
    igual('la lista sigue las fechas de la promoción', $otraHasta, filas('id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . S12)[0]['date_to'] ?? '');
    igual('y el paso vuelve a dar su % a los demás', [$entran - 1, $entran], [count(filas('id_kpy_coupon = ' . $idPromo . " AND origen = 'promo'")), count(filas('id_kpy_coupon = ' . $idPromo))]);

    echo "\nK. Una lista suelta con las mismas fechas que la promoción: manda la lista\n";
    $otro = 0;
    $otroProducto = 0;
    foreach ($entranClaves as $clave => $idProducto) {
        if ((int) $clave > 0 && !in_array((int) $clave, [S12, S3], true)) {
            $otro = (int) $clave;
            $otroProducto = (int) $idProducto;
            break;
        }
    }
    igual('hay otro artículo en la promoción', true, $otro > 0);
    $db->delete(KsAcuerdos::TABLA, 'id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . $otro);
    KsAcuerdos::guardar([['id_product' => $otroProducto, 'ipa' => $otro, 'valor' => 3.0]], KsTipos::id('redencion'), (string) $promo->fecha_desde, $otraHasta, null, 'parar');
    $r = KsAcuerdos::ponerAlDia($idPromo, 1);
    $fo = filas('id_product_attribute = ' . $otro . " AND date_from = '" . $promo->fecha_desde . "'");
    igual('la promoción no la pisa ni la duplica (y lo cuenta)', [1, null, '3.000000', 1], [count($fo), array_key_exists('id_kpy_coupon', $fo[0] ?? []) ? $fo[0]['id_kpy_coupon'] : 'x', $fo[0]['value'] ?? '', $r['cuentas']['ya_tenian'] ?? -1]);
    $db->delete(KsAcuerdos::TABLA, 'id = ' . (int) $fo[0]['id']);

    echo "\nL. Al quitar artículos de la promoción, lo que ya vendió con el acuerdo se queda\n";
    $desdeL = '2026-10-18 00:00:00';
    $db->update('kpy_coupon', ['fecha_desde' => $desdeL], 'id_kpy_coupon = ' . $idPromo);
    guardarPromo($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'redencion', 'kps_valor_redencion' => '10']);
    $vendio = KsVentas::conVentas([[S, S12]], $desdeL, date('Y-m-d H:i:s'));
    igual('el 12 kg vendió en el periodo (pedidos de prueba del 19/10)', true, isset($vendio[S . '-' . S12]));
    $sinVentas = 0;
    foreach ($entranClaves as $clave => $idProducto) {
        if ((int) $clave > 0 && (int) $clave !== S12 && KsVentas::conVentas([[(int) $idProducto, (int) $clave]], $desdeL, date('Y-m-d H:i:s')) === []) {
            $sinVentas = (int) $clave;
            break;
        }
    }
    igual('y hay otro sin ventas', true, $sinVentas > 0);
    // El 12 kg estaba en la lista (manda la lista): se devuelve al paso para probar esto.
    $db->update(KsAcuerdos::TABLA, ['origen' => 'promo'], 'id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . S12);
    $promoL = new KpcCupon($idPromo);
    $promoL->guardarAmbitos([['tipo' => 'marca', 'id' => MARCA_BOSKE], ['tipo' => 'excluir_combinacion', 'id' => S12], ['tipo' => 'excluir_combinacion', 'id' => $sinVentas]]);
    KpcCupon::olvidarCache();
    guardarPromo($idPromo, ['kps_paso' => '1', 'kps_tipo' => 'redencion', 'kps_valor_redencion' => '10']);
    igual('fuera de la promoción: el que vendió se queda; el que no, se va', [1, 0], [count(filas('id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . S12)), count(filas('id_kpy_coupon = ' . $idPromo . ' AND id_product_attribute = ' . $sinVentas))]);

    echo "\nM. Prolongar, cerrar y borrar un lote suelto\n";
    $r = KsAcuerdos::cambiarFinal($lote, '2026-10-22 23:59:59');
    igual('prolongar hasta el 22: dos filas', 2, $r);
    $lote2 = KsAcuerdos::lote(KsAcuerdos::claveLote((int) $lote['id_supplier_promo_type'], DESDE, '2026-10-22 23:59:59', 0, MARCA_BOSKE));
    igual('el lote nuevo tiene las dos', 2, count(KsAcuerdos::filasDeLote($lote2)));
    // Otra fila del 3 kg con el mismo inicio y sin fin, metida a mano (guardar() no la dejaría: se cruza).
    $db->insert(KsAcuerdos::TABLA, ['id_product' => S, 'id_product_attribute' => S3, 'id_supplier_promo_type' => KsTipos::id('factura'), 'value' => 5.0,
        'date_from' => DESDE, 'date_to' => KsAcuerdos::SIN_FIN, 'origen' => 'lista', 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);
    $choque = KsAcuerdos::cambiarFinal($lote2, null);
    igual('dejarlo hasta nueva orden choca con otra fila con esas fechas: no se hace nada', [true, 2], [is_string($choque), count(KsAcuerdos::filasDeLote($lote2))]);
    $db->execute("DELETE FROM {$p}" . KsAcuerdos::TABLA . ' WHERE id_product_attribute = ' . S3 . " AND date_from = '" . DESDE . "' AND date_to = '" . KsAcuerdos::SIN_FIN . "'");
    KsAcuerdos::cambiarFinal($lote2, null);
    $lote3 = KsAcuerdos::lote(KsAcuerdos::claveLote((int) $lote['id_supplier_promo_type'], DESDE, null, 0, MARCA_BOSKE));
    igual('hasta nueva orden: sin fin y en marcha', ['desde el 15/10/2026, hasta nueva orden', 'vivo', true, 2], [$lote3['fechas'], $lote3['estado'], $lote3['sin_fin'], count(KsAcuerdos::filasDeLote($lote3))]);
    $v = KsVentas::deLote($lote3, true);
    igual('las ventas de un lote sin fin llegan hasta hoy', substr(date('Y-m-d H:i:s'), 0, 13), substr($v['periodo']['hasta'], 0, 13));
    igual('quitar una fila', true, KsAcuerdos::borrarFila((int) KsAcuerdos::filasDeLote($lote3)[0]['id']));
    igual('borrar el lote: la que queda', 1, KsAcuerdos::borrarLote($lote3));
    igual('y ya no hay filas sueltas de Boske en esas fechas', 0, count(filas('id_product = ' . S . " AND date_from = '" . DESDE . "'")));
} catch (Throwable $e) {
    ++$fallos;
    echo "\nERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
} finally {
    limpiar();
    if ($estadosAntes === false || $estadosAntes === null || $estadosAntes === '') {
        Configuration::deleteByName(KsVentas::CONF_ESTADOS);
    } else {
        Configuration::updateGlobalValue(KsVentas::CONF_ESTADOS, $estadosAntes);
    }
    if ($avisosAntes === false || $avisosAntes === null || $avisosAntes === '') {
        Configuration::deleteByName(Kpysupply::CONF_AVISOS);
    } else {
        Configuration::updateGlobalValue(Kpysupply::CONF_AVISOS, $avisosAntes);
    }
    $despues = count(filas());
    echo "\nLimpieza: la tabla vuelve a tener {$despues} filas (tenía {$antes}).\n";
}

echo $fallos === 0 ? "\nTodo bien ({$n} comprobaciones).\n" : "\n{$fallos} fallos de {$n}.\n";
exit($fallos === 0 ? 0 : 1);
