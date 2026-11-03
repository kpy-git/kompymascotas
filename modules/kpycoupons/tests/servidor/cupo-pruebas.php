<?php
/**
 * Pruebas de integración de las promociones **por cantidad** (el cupo), kpycoupons 2.3.0, en la
 * tienda de pruebas, por consola dentro del contenedor (desde /var/www/html/kompy):
 *   cat cupo-pruebas.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php -- [--pedido]
 *
 * Crea promociones de prueba (LIQ-TEST-BOSKE, CUPO-TEST-2UD, TESTCUPO10, REGALO-TEST-CUPO) y cestas
 * del cliente de desarrollo (id 4); con --pedido, un pedido de verdad (transferencia, sin email) que
 * agota un cupo. Mientras dura, apaga las demás promociones (y las vuelve a encender al final, pase
 * lo que pase). Al final borra las promociones y las cestas (el pedido se queda, se dice su id).
 */
$conPedido = in_array('--pedido', $argv ?? [], true);
require_once 'autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->usePutenv(false)->loadEnv(getcwd() . '/.env');
require 'config/config.inc.php';
$kernel = new FrontKernel('dev', false);
$kernel->boot();

$ctx = Context::getContext();
Shop::setContext(Shop::CONTEXT_SHOP, 1);
$ctx->shop = new Shop(1);
$ctx->language = new Language(1);
$ctx->currency = new Currency(1);
$ctx->country = new Country(6);
$ctx->customer = new Customer(4);
$ctx->link = new Link();
$ctx->cookie = new Cookie('kpycupo');
$ctx->currentLocale = $kernel->getContainer()->get('prestashop.core.localization.locale.repository')->getLocale($ctx->language->getLocale());
$modulo = Module::getInstanceByName('kpycoupons');
/** Lo que la ficha diría de un formato ahora mismo (3.0.0: `KpcFicha`, en vez del hook de kpyproductbuy). */
function fichaEstado(int $ipa): array
{
    global $modulo;
    KpcFicha::olvidar();

    return KpcFicha::de($modulo, S)->estado($ipa);
}

const S = 8299; const S12 = 92467; const S3 = 92468; const SP2 = 92469;   // Boske salmón: 12 kg, 3 kg, pack 2 × 12
const CLIENTE = 4; const DIRECCION = 69508; const MARCA_BOSKE = 178;
const CODIGOS = ['LIQ-TEST-BOSKE', 'CUPO-TEST-2UD', 'TESTCUPO10', 'REGALO-TEST-CUPO', 'OUTLET-TEST-LISTA'];

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
function cerca(string $que, float $esperado, float $real, float $margen = 0.011): void
{
    global $fallos;
    if (abs($esperado - $real) <= $margen) {
        echo "  ok  $que (" . number_format($real, 2) . ")\n";
        return;
    }
    ++$fallos;
    echo "  MAL $que\n      esperado: $esperado\n      real:     $real\n";
}
function precio(int $id, int $ipa): float
{
    return Tools::ps_round((float) Product::getPriceStatic($id, true, $ipa, 6), 2);
}
function habitual(int $id, int $ipa): float
{
    $old = Db::getInstance()->getValue('SELECT old_discount FROM ' . _DB_PREFIX_ . 'kpy_special_price WHERE id_product = ' . $id . ' AND id_product_attribute = ' . $ipa . ' AND id_shop = 1 AND NOW() BETWEEN date_from AND expire ORDER BY date_from DESC');
    if ($old === false || $old === null) {
        return precio($id, $ipa);
    }
    $pvpr = (float) Product::getPriceStatic($id, true, $ipa, 6, null, false, false);

    return round($pvpr * (1 - (float) $old), 2);
}
function precioLinea(Cart $cart, int $id, int $ipa): float
{
    foreach ($cart->getProducts(true) as $l) {
        if ((int) $l['id_product'] === $id && (int) $l['id_product_attribute'] === $ipa) {
            return Tools::ps_round((float) $l['price_wt'], 2);
        }
    }

    return 0.0;
}
function preciosCesta(Cart $cart, int $id, int $ipa): int
{
    return (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price WHERE id_cart = ' . (int) $cart->id . ' AND id_product = ' . $id . ' AND id_product_attribute = ' . $ipa);
}
$cestas = [];
function cesta(): Cart
{
    global $ctx, $cestas;
    $cart = new Cart();
    $cart->id_customer = CLIENTE;
    $cart->id_address_delivery = DIRECCION;
    $cart->id_address_invoice = DIRECCION;
    $cart->id_lang = 1;
    $cart->id_currency = 1;
    $cart->id_shop = 1;
    $cart->id_shop_group = 1;
    $cart->id_carrier = 4;
    $cart->secure_key = $ctx->customer->secure_key;
    $cart->add();
    $ctx->cart = $cart;
    $cestas[] = (int) $cart->id;
    return $cart;
}
function usar(Cart $cart): Cart
{
    global $ctx;
    $ctx->cart = $cart;
    return $cart;
}
function foto(Cart $cart): array
{
    $lineas = [];
    foreach ($cart->getProducts(true) as $l) {
        $lineas[$l['id_product'] . '-' . $l['id_product_attribute']] = (int) $l['cart_quantity'];
    }
    ksort($lineas);
    $reglas = [];
    $nombres = [];
    foreach ($cart->getCartRules() as $cr) {
        $codigo = (string) $cr['code'];
        $clave = strpos($codigo, 'KPY') === 0 ? substr($codigo, 0, 5) : $codigo;
        $reglas[$clave] = round((float) $cr['value_real'], 2);
        $nombres[$clave] = (string) $cr['name'];
    }
    ksort($reglas);
    $reservas = [];
    foreach (KpcCupo::reservasDe((int) $cart->id) as $promo => $porClave) {
        $reservas[$promo] = $porClave;
    }
    return [
        'lineas' => $lineas,
        'reglas' => $reglas,
        'nombres' => $nombres,
        'reservas' => $reservas,
        'productos' => round((float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS), 2),
        'descuentos' => round((float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS), 2),
    ];
}
function sincronizar(Cart $cart): void
{
    global $modulo;
    usar($cart);
    $modulo->sincronizarCesta($cart);
}
function grupoDe(int $ipa): array
{
    $f = Db::getInstance()->getRow('SELECT a.id_attribute_group, pac.id_attribute FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
        INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute WHERE pac.id_product_attribute = ' . $ipa);

    return [(int) $f['id_attribute_group'] => (int) $f['id_attribute']];
}
/** Lo que hace la ficha al pulsar «Añadir a la cesta»: el formulario con `group[…]` y `kpy_regalo`. */
function desdeLaFicha(Cart $cart, int $id, int $ipa, int $uds, ?bool $conRegalo): bool
{
    usar($cart);
    $_POST = ['add' => '1', 'action' => 'update', 'id_product' => (string) $id, 'group' => grupoDe($ipa), 'qty' => (string) $uds];
    if ($conRegalo !== null) {
        $_POST['kpy_regalo'] = $conRegalo ? '1' : '0';
    }
    try {
        return $cart->updateQty($uds, $id, $ipa) === true;
    } finally {
        $_POST = [];
    }
}
function promo(array $campos, array $ambitos): KpcCupon
{
    $c = new KpcCupon();
    $base = [
        'porcentaje' => 0, 'activo' => 1, 'fecha_desde' => date('Y-m-d H:i:00', time() - 300), 'fecha_hasta' => date('Y-m-d 23:59:59', strtotime('+7 days')),
        'en_ficha' => 1, 'excluir_especial' => 0, 'incluir_packs' => 0, 'packs' => '1', 'sin_outlet' => 1, 'en_merchant' => 1, 'cupo_ficha' => 1,
    ];
    foreach ($campos + $base as $k => $v) {
        $c->{$k} = $v;
    }
    $c->id_shop = 1;
    if (!$c->add()) {
        throw new RuntimeException('no se ha podido crear la promoción ' . $campos['codigo']);
    }
    $c->guardarAmbitos($ambitos);
    rehacer();

    return new KpcCupon((int) $c->id);
}
function limpiarPromos(): void
{
    foreach (Db::getInstance()->executeS("SELECT id_kpy_coupon FROM " . _DB_PREFIX_ . "kpy_coupon WHERE codigo IN ('" . implode("','", CODIGOS) . "')") ?: [] as $f) {
        $c = new KpcCupon((int) $f['id_kpy_coupon']);
        if (Validate::isLoadedObject($c)) {
            $c->delete();
        }
    }
}
function rehacer(): void
{
    KpcCupon::olvidarCache();
    KpcReparto::rehacer(1);
    KpcCupon::olvidarCache();
}
function estado(int $id, int $sinCesta = 0): array
{
    KpcCupo::olvidarCache();
    $e = KpcCupo::estado([$id], $sinCesta)[$id];

    return [$e['vendidas'], $e['reservadas'], $e['quedan'], $e['agotado'] !== ''];
}
/** La reserva de una cesta, envejecida: como si llevara dos horas sin tocarse. */
function envejecer(Cart $cart): void
{
    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_coupon_cupo_cesta SET date_upd = DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE id_cart = ' . (int) $cart->id);
    KpcCupo::olvidarCache();
}
function avisos(Cart $cart): array
{
    global $modulo;
    usar($cart);
    $r = KpcResumen::deLaCesta($cart, 1, $modulo);

    return ['lineas' => array_column($r['lineas'], 'texto'), 'vales' => array_map(static fn ($v) => $v['tipo'] . ':' . $v['codigo'], $r['vales']), 'animos' => count($r['animos'])];
}
function partes(Cart $cart, int $clave): array
{
    usar($cart);
    $d = KpcRegalo::partesDe($cart)[$clave] ?? null;

    return $d === null ? [] : ['habitual' => $d['habitual'], 'sp' => $d['sp'], 'etiqueta' => $d['etiqueta'], 'clase' => $d['etiqueta_clase'], 'pagadas' => $d['pagadas']];
}

echo "Preparación\n";
usar(cesta());
limpiarPromos();
// Las demás promociones se apagan mientras dura la prueba (el 12 kg tiene que quedarse sin Special
// Price) y se vuelven a encender al final, pase lo que pase.
$apagadas = array_map('intval', array_column(Db::getInstance()->executeS("SELECT id_kpy_coupon FROM " . _DB_PREFIX_ . "kpy_coupon WHERE activo = 1") ?: [], 'id_kpy_coupon'));
if ($apagadas !== []) {
    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_coupon SET activo = 0 WHERE id_kpy_coupon IN (' . implode(',', $apagadas) . ')');
    echo '  apagadas mientras tanto: ' . implode(', ', $apagadas) . "\n";
}
// La campaña de la landing (Black Friday) renombra cupones y etiquetas: apagada mientras dura la prueba.
$campanas = array_map('intval', array_column(Db::getInstance()->executeS('SELECT id_kpy_promo_campana FROM ' . _DB_PREFIX_ . 'kpy_promo_campana WHERE activa = 1') ?: [], 'id_kpy_promo_campana'));
if ($campanas !== []) {
    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = 0 WHERE id_kpy_promo_campana IN (' . implode(',', $campanas) . ')');
    echo '  campañas apagadas mientras tanto: ' . implode(', ', $campanas) . "\n";
}
register_shutdown_function(static function () use ($apagadas, $campanas): void {
    if ($campanas !== []) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = 1 WHERE id_kpy_promo_campana IN (' . implode(',', $campanas) . ')');
        echo '  campañas encendidas otra vez: ' . implode(', ', $campanas) . "\n";
    }
    if ($apagadas !== []) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_coupon SET activo = 1 WHERE id_kpy_coupon IN (' . implode(',', $apagadas) . ')');
        KpcCupon::olvidarCache();
        KpcReparto::rehacer(1);
        echo '  encendidas otra vez: ' . implode(', ', $apagadas) . "\n";
    }
});
rehacer();
$h = habitual(S, S12);
igual('el 12 kg se ha quedado sin Special Price', true, abs($h - precio(S, S12)) < 0.011);
$p3 = precio(S, S3);

echo "\nA. Liquidación −20 % con cupo de 5 en el 12 kg: lo que cabe va al precio, lo demás al habitual\n";
$liq = promo(['codigo' => 'LIQ-TEST-BOSKE', 'nombre' => 'Prueba liquidación', 'tipo' => 'special_price', 'modo_precio' => 'pct', 'porcentaje' => 20, 'etiqueta' => 'liquidacion', 'cupo' => 5, 'excluir_especial' => 1],
    [['tipo' => 'combinacion', 'id' => S12]]);
$idL = (int) $liq->id;
igual('llega a 1 artículo', 1, (int) $liq->formatos);
$p = precio(S, S12);
echo "  12 kg: habitual {$h} €, en liquidación {$p} €\n";
igual('el precio baja', true, $h > $p + 0.5);
igual('estado del cupo: 0 vendidas, 0 en cestas, quedan 5', [0, 0, 5, false], estado($idL));
igual('hay cupos', true, KpcCupo::hayCupos(1));
$c1 = cesta();
igual('cesta 1: 3 uds desde la ficha', true, desdeLaFicha($c1, S, S12, 3, false));
$f = foto($c1);
cerca('las 3 al precio de liquidación (línea)', $p, precioLinea($c1, S, S12));
igual('reservadas las 3 en el cupo', [$idL => [S12 => 3]], $f['reservas']);
igual('sin vales ni precio de cesta', [[], 0], [$f['reglas'], preciosCesta($c1, S, S12)]);
igual('para otra cesta quedan 2', 2, KpcCupo::quedanPara($idL, 0));
igual('para la cesta 1 (sin contar lo suyo) quedan 5', 5, KpcCupo::quedanPara($idL, (int) $c1->id));
$c2 = cesta();
igual('cesta 2: 3 uds desde la ficha', true, desdeLaFicha($c2, S, S12, 3, false));
$f = foto($c2);
cerca('la línea va al precio habitual (PrestaShop solo deja un precio por línea)', $h, precioLinea($c2, S, S12));
igual('con un precio de cesta', 1, preciosCesta($c2, S, S12));
cerca('y el vale «Liquidación» devuelve la rebaja a las 2 que caben', round(2 * ($h - $p), 2), $f['reglas']['KPYSP'] ?? 0.0);
igual('el vale se llama por la promoción', true, strpos((string) ($f['nombres']['KPYSP'] ?? ''), 'Liquidación: 2 ×') === 0);
echo '  vale: ' . ($f['nombres']['KPYSP'] ?? '') . "\n";
cerca('productos − descuentos = 2 en liquidación + 1 al habitual', round(2 * $p + $h, 2), $f['productos'] - $f['descuentos']);
igual('reservadas 2 en el cupo', [$idL => [S12 => 2]], $f['reservas']);
igual('estado: 0 vendidas, 5 en cestas, quedan 0', [0, 5, 0, false], estado($idL));
igual('las partes de la línea: al habitual, 2 con el precio, «Liquidación»', ['habitual' => true, 'sp' => 2, 'etiqueta' => 'Liquidación', 'clase' => 'liquidacion', 'pagadas' => 3], partes($c2, S12));
$av = avisos($c2);
igual('la cesta lo dice', true, count(array_filter($av['lineas'], static fn ($t) => strpos($t, 'Liquidación: solo quedaban 2') === 0)) === 1);
echo '  aviso: ' . implode(' | ', $av['lineas']) . "\n";
igual('y el vale del Special Price se dibuja como tal', ['especial:'], array_values(array_unique(array_values($av['vales']))));

echo "\nB. La cesta 1 suelta sus unidades: la cesta 2 se las queda al repasarse\n";
usar($c1);
$c1->deleteProduct(S, S12);
igual('cesta 1 sin reservas', [], foto($c1)['reservas']);
sincronizar($c2);
$f = foto($c2);
cerca('la cesta 2 vuelve al precio de liquidación', $p, precioLinea($c2, S, S12));
igual('sin vale ni precio de cesta', [[], 0], [$f['reglas'], preciosCesta($c2, S, S12)]);
igual('con las 3 reservadas', [$idL => [S12 => 3]], $f['reservas']);
igual('cesta 1: 5 uds: solo caben 2', true, desdeLaFicha($c1, S, S12, 5, false));
$f = foto($c1);
cerca('al habitual, con el vale de las 2', round(2 * ($h - $p), 2), $f['reglas']['KPYSP'] ?? 0.0);
igual('reservadas 2', [$idL => [S12 => 2]], $f['reservas']);

echo "\nC. Una reserva de hace dos horas ya no cuenta\n";
envejecer($c2);
igual('para la cesta 1 quedan 5', 5, KpcCupo::quedanPara($idL, (int) $c1->id));
sincronizar($c1);
$f = foto($c1);
cerca('las 5 al precio de liquidación', $p, precioLinea($c1, S, S12));
igual('sin vale, reservadas 5', [[], [$idL => [S12 => 5]]], [$f['reglas'], $f['reservas']]);
sincronizar($c2);
$f = foto($c2);
cerca('la cesta 2, al volver, se queda sin ninguna: todo al habitual', $h, precioLinea($c2, S, S12));
igual('sin vale (0 unidades con el precio) y sin reservas', [[], []], [$f['reglas'], $f['reservas']]);
$av = avisos($c2);
igual('y se lo dice', true, count(array_filter($av['lineas'], static fn ($t) => strpos($t, 'Liquidación: solo quedaban 0') === 0)) === 1);

echo "\nD. Lo que ve el cliente: la ficha, las etiquetas y la pantalla\n";
usar($c2);
$e = fichaEstado(S12);
igual('la ficha dice que el 12 kg está agotado para esta cesta (la 1 tiene las 5) y presenta el precio habitual', ['Se han agotado las unidades en Liquidación: a precio habitual', 'agotado', $h, true], [$e['quedan']['texto'], $e['estado'], $e['presentar']['precio'] ?? null, $e['especial_fuera']]);
igual('y nada del 3 kg', '', fichaEstado(S3)['quedan']['texto']);
usar($c1);
igual('desde la cesta 1, quedan 5 (las suyas no cuentan en su contra)', '¡Últimas 5 unidades en Liquidación!', fichaEstado(S12)['quedan']['texto']);
$limpia = cesta();
$flags = ['kpy_special_price' => ['type' => 'kpy_special_price', 'label' => 'Precio especial', 'color' => '#B8322A', 'background' => '#FFEAB7'], 'topventas' => ['type' => 'topventas', 'label' => 'Top ventas']];
KpcFlags::ajustar($flags, ['id_product' => S, 'id_product_attribute' => S12], $modulo);
igual('la etiqueta del listado dice «Liquidación» en rojo', ['Liquidación', '#B3261E', '#ffffff'], [$flags['kpy_special_price']['label'] ?? '', $flags['kpy_special_price']['background'] ?? '', $flags['kpy_special_price']['color'] ?? '']);
igual('y «Top ventas» sigue a su lado', true, isset($flags['topventas']));
$doof = KpcFlags::paraDoofinder($modulo)[S] ?? [];
igual('Doofinder: Special Price y «Liquidación»', ['1', 'Liquidación', 'liquidacion'], [$doof['kpy_especial'] ?? '', $doof['kpy_promo'] ?? '', $doof['kpy_promo_tipo'] ?? '']);

echo "\nE. Se agota: la promoción desaparece de todas partes y la cesta vuelve al precio de siempre\n";
if ($conPedido) {
    $mail = Configuration::get('PS_MAIL_METHOD');
    Configuration::updateValue('PS_MAIL_METHOD', 3);
    try {
        usar($c1);
        $pago = Module::getInstanceByName('ps_wirepayment');
        $total = (float) $cart = (float) $c1->getOrderTotal(true, Cart::BOTH);
        $pago->validateOrder((int) $c1->id, (int) Configuration::get('PS_OS_BANKWIRE'), $total, $pago->displayName, null, [], 1, false, $ctx->customer->secure_key);
        $idOrder = (int) $pago->currentOrder;
        igual('pedido creado con las 5 uds en liquidación', true, $idOrder > 0);
        echo "  pedido #{$idOrder}\n";
        $linea = Db::getInstance()->getRow('SELECT product_quantity, unit_price_tax_incl FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . $idOrder . ' AND product_attribute_id = ' . S12);
        igual('5 unidades en el pedido', 5, (int) ($linea['product_quantity'] ?? 0));
        cerca('al precio de liquidación', $p, (float) ($linea['unit_price_tax_incl'] ?? 0));
        igual('lo vendido queda apuntado', [[$idOrder, $idL, S12, 5]], array_map(static fn ($f) => [(int) $f['id_order'], (int) $f['id_kpy_coupon'], (int) $f['clave'], (int) $f['unidades']],
            Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_pedido WHERE id_order = ' . $idOrder) ?: []));
        igual('la cesta pedida ya no reserva nada', [], KpcCupo::reservasDe((int) $c1->id));
    } finally {
        Configuration::updateValue('PS_MAIL_METHOD', $mail);
    }
} else {
    echo "  (sin --pedido: se simula el pedido con lo reservado por la cesta 1, apuntado bajo el último pedido real; se borra en la limpieza)\n";
    $idOrder = (int) Db::getInstance()->getValue('SELECT MAX(id_order) FROM ' . _DB_PREFIX_ . 'orders o WHERE o.current_state NOT IN (' . (int) Configuration::get('PS_OS_CANCELED') . ', ' . (int) Configuration::get('PS_OS_ERROR') . ')');
    $falsa = new Order($idOrder);
    $falsa->id_cart = (int) $c1->id;
    $falsa->id_shop = 1;
    igual('al apuntar el pedido, la promoción se da por agotada', [$idL], KpcCupo::alPedido($falsa));
    igual('lo vendido queda apuntado', [[$idL, S12, 5]], array_map(static fn ($f) => [(int) $f['id_kpy_coupon'], (int) $f['clave'], (int) $f['unidades']],
        Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_pedido WHERE id_order = ' . $idOrder . ' AND id_kpy_coupon = ' . $idL) ?: []));
    igual('la cesta pedida ya no reserva nada', [], KpcCupo::reservasDe((int) $c1->id));
}
igual('estado: 5 vendidas, agotada', [5, 0, 0, true], estado($idL));
$liq = new KpcCupon($idL);
igual('con la fecha de agotado', true, KpcCupon::fechaValida(Db::getInstance()->getValue('SELECT cupo_agotado FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $idL)) !== null);
igual('ya no está en vigor', false, in_array($idL, array_map('intval', array_column(KpcCupon::enVigor(1), 'id_kpy_coupon')), true));
igual('toca rehacer el reparto en la siguiente visita', true, (int) Configuration::getGlobalValue(KpcReparto::PROXIMO) <= time());
rehacer();
cerca('el precio del catálogo vuelve al de siempre', $h, precio(S, S12));
igual('sus precios se han quitado', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_precio WHERE id_kpy_coupon = ' . $idL));
sincronizar($c2);
$f = foto($c2);
cerca('la cesta 2, al repasarse, va al precio de siempre', $h, precioLinea($c2, S, S12));
igual('sin precio de cesta, sin vales, sin reservas', [0, [], []], [preciosCesta($c2, S, S12), $f['reglas'], $f['reservas']]);
igual('la cesta ya no avisa de nada', [], avisos($c2)['lineas']);
usar($limpia);
igual('la ficha ya no sabe nada de la promoción', false, fichaEstado(S12)['hay']);
$flags = ['kpy_special_price' => ['type' => 'kpy_special_price', 'label' => 'Precio especial']];
KpcFlags::ajustar($flags, ['id_product' => S, 'id_product_attribute' => S12], $modulo);
igual('ni la etiqueta', false, isset($flags['kpy_special_price']));
igual('la landing la da por terminada', true, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon c WHERE c.id_kpy_coupon = ' . $idL . ' AND NOT ' . KpcCupo::sqlVivo('c')) === 1);

echo "\nF. Reabrir y ampliar el cupo\n";
KpcCupo::reabrir($idL);
rehacer();
igual('reabierta, pero con el cupo cubierto: 0 que queden', [5, 0, 0, false], estado($idL));
cerca('el precio vuelve al catálogo', $p, precio(S, S12));
$c3 = cesta();
desdeLaFicha($c3, S, S12, 1, false);
cerca('una cesta nueva lo paga al habitual (no queda cupo)', $h, precioLinea($c3, S, S12));
$liq = new KpcCupon($idL);
$liq->cupo = 7;
$liq->update();
KpcCupon::olvidarCache();
igual('con cupo 7: quedan 2', [5, 0, 2, false], estado($idL));
sincronizar($c3);
cerca('y la cesta 3 recupera el precio', $p, precioLinea($c3, S, S12));
igual('reservada 1', [$idL => [S12 => 1]], foto($c3)['reservas']);
$liq->delete();
rehacer();
foreach ([$c1, $c2, $c3] as $c) {
    if (!$c->orderExists()) {
        usar($c);
        $c->deleteProduct(S, S12);
    }
}

echo "\nG. 2ª unidad al −40 % con cupo de 1 unidad rebajada en el 3 kg\n";
$seg = promo(['codigo' => 'CUPO-TEST-2UD', 'nombre' => 'Prueba 2ª unidad con cupo', 'tipo' => 'segunda_unidad', 'porcentaje' => 40, 'cupo' => 1],
    [['tipo' => 'combinacion', 'id' => S3]]);
$idS = (int) $seg->id;
igual('llega al 3 kg', 1, (int) $seg->formatos);
$c4 = cesta();
desdeLaFicha($c4, S, S3, 4, false);
$f = foto($c4);
cerca('4 uds: solo una rebajada (no dos)', round($p3 * 0.4, 2), $f['reglas']['KPY2U'] ?? 0.0);
igual('reservada 1', [$idS => [S3 => 1]], $f['reservas']);
$av = avisos($c4);
igual('la cesta lo dice', true, count(array_filter($av['lineas'], static fn ($t) => strpos($t, '2ª unidad: solo quedaban 1') === 0)) === 1);
echo '  aviso: ' . implode(' | ', $av['lineas']) . "\n";
$c5 = cesta();
desdeLaFicha($c5, S, S3, 3, false);
$f = foto($c5);
igual('otra cesta con 3: nada que rebajar (sin vale) y sin empujón a la 4ª', [[], 0], [$f['reglas'], avisos($c5)['animos']]);
igual('la ficha dice que quedan 0 (y la caja de la 2ª unidad ya no se pulsa)', [true, false], [strpos((string) (fichaEstado(S3)['segunda']['texto'] ?? ''), 'quedan 0 ud.') !== false, fichaEstado(S3)['segunda']['pulsable'] ?? null]);
$seg->delete();
rehacer();
usar($c4);
$c4->deleteProduct(S, S3);
usar($c5);
$c5->deleteProduct(S, S3);

echo "\nH. Cupón −10 % con cupo de 2 unidades en el 3 kg: un vale por producto con lo que cabe, la regla del cupón fuera\n";
$cup = promo(['codigo' => 'TESTCUPO10', 'nombre' => 'Prueba cupón con cupo', 'tipo' => 'cupon', 'porcentaje' => 10, 'cupo' => 2, 'auto_web' => 1],
    [['tipo' => 'combinacion', 'id' => S3]]);
$idC = (int) $cup->id;
igual('con su regla de carrito', true, (int) Db::getInstance()->getValue('SELECT id_cart_rule FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $idC) > 0);
$c6 = cesta();
$modulo->guardar('TESTCUPO10');
desdeLaFicha($c6, S, S3, 3, false);
$f = foto($c6);
cerca('el vale KPYCP descuenta el 10 % de 2 unidades', round(2 * $p3 * 0.1, 2), $f['reglas']['KPYCP'] ?? 0.0);
igual('la regla del cupón no está en la cesta', false, isset($f['reglas']['TESTCUPO10']));
igual('reservadas 2', [$idC => [S3 => 2]], $f['reservas']);
igual('el vale lleva el código del cupón', true, strpos((string) ($f['nombres']['KPYCP'] ?? ''), 'TESTCUPO10 (-10 %): 2 ×') === 0);
echo '  vale: ' . ($f['nombres']['KPYCP'] ?? '') . "\n";
$av = avisos($c6);
igual('la cesta dibuja el vale como un cupón y avisa', ['cupon:TESTCUPO10', true], [array_values($av['vales'])[0] ?? '', count(array_filter($av['lineas'], static fn ($t) => strpos($t, 'Cupón TESTCUPO10: solo quedaban 2') === 0)) === 1]);
igual('el cupón cuenta como puesto (para «llévalo junto»)', [S], KpcCarrito::productosConPromo($c6, 1));
cerca('lo que descuenta, para la ficha', round(2 * $p3 * 0.1, 2), KpcCupoCesta::descuentoCupon($c6, $idC));
usar($c6);
$idRegla = (int) array_key_first(KpcCupoCesta::reglasCupon($c6));
$ctx->controller = new class() {
    public $php_self = 'cart';
};
$_GET = ['deleteDiscount' => (string) $idRegla, 'token' => Tools::getToken(false)];
Hook::exec('actionFrontControllerInitAfter');
$_GET = [];
$ctx->controller = null;
igual('la papelera del vale olvida el cupón', ['TESTCUPO10'], $modulo->quitados());
$c6->removeCartRule($idRegla);
sincronizar($c6);
$f = foto($c6);
igual('y se va del todo: sin vales ni reservas', [[], []], [$f['reglas'], $f['reservas']]);
$modulo->guardar('TESTCUPO10');
sincronizar($c6);
igual('elegido otra vez, vuelve', true, ($f = foto($c6))['reglas']['KPYCP'] > 0);
// Revisión del 26/10: sin la cookie del cliente (la notificación servidor a servidor de la pasarela, o un canal
// que ya no se recuerda), el cupón con cupo ya puesto se queda: puesto es puesto hasta la papelera.
$ctx->cookie->kpy_cupon = '';
sincronizar($c6);
igual('sin cookie (validación servidor a servidor): los vales del cupo se quedan', true, ($f = foto($c6))['reglas']['KPYCP'] > 0);
igual('y el cupón vuelve a estar entre los puestos', true, in_array('TESTCUPO10', $modulo->puestos(), true));
$c7 = cesta();
$modulo->guardar('TESTCUPO10');
desdeLaFicha($c7, S, S3, 1, false);
$f = foto($c7);
igual('otra cesta: sin cupo que quede, sin vale', [], $f['reglas']);
$cup->delete();
rehacer();
$modulo->olvidar();
usar($c6);
$c6->deleteProduct(S, S3);
usar($c7);
$c7->deleteProduct(S, S3);

echo "\nI. Regalo con cupo de 1: dos cestas, un regalo; la reserva vieja se libera\n";
$reg = promo(['codigo' => 'REGALO-TEST-CUPO', 'nombre' => 'Prueba regalo con cupo', 'tipo' => 'regalo', 'cupo' => 1, 'regalo_modo' => 'pequeno', 'regalo_por' => 'unidad', 'regalo_packs' => 0, 'excluir_especial' => 0],
    [['tipo' => 'marca', 'id' => MARCA_BOSKE]]);
$idR = (int) $reg->id;
igual('llega a varios sacos', true, (int) $reg->formatos > 1);
$a = cesta();
desdeLaFicha($a, S, S12, 1, true);
$f = foto($a);
igual('cesta A: el 12 kg y su 3 kg de regalo', [S . '-' . S12 => 1, S . '-' . S3 => 1], $f['lineas']);
igual('reservado 1 regalo', [$idR => [S12 => 1]], $f['reservas']);
$b = cesta();
desdeLaFicha($b, S, S12, 1, true);
$f = foto($b);
igual('cesta B: solo el 12 kg (el regalo está en la cesta A)', [S . '-' . S12 => 1], $f['lineas']);
cerca('y lo paga al precio de hoy, no de más', precio(S, S12), precioLinea($b, S, S12));
$av = avisos($b);
igual('la cesta lo dice', true, count(array_filter($av['lineas'], static fn ($t) => stripos($t, 'acabado') !== false)) === 1);
$e = fichaEstado(S12);
igual('para la ficha, los regalos se han agotado: sin opción con regalo y con el aviso', [null, 'Se han agotado los regalos de esta promoción'], [$e['regalo'], $e['aviso']]);
envejecer($a);
sincronizar($b);
$f = foto($b);
igual('al pasar la hora, la cesta B se lo lleva', [S . '-' . S12 => 1, S . '-' . S3 => 1], $f['lineas']);
sincronizar($a);
$f = foto($a);
igual('y la cesta A lo pierde al volver (sin pagar de más)', [[S . '-' . S12 => 1], []], [$f['lineas'], $f['reservas']]);
cerca('a su precio de hoy', precio(S, S12), precioLinea($a, S, S12));
$reg->delete();
rehacer();

echo "\nJ. Outlet con unidades por artículo (la lista): 2 del 12 kg y 3 del 3 kg, sin tope total\n";
$out = promo(['codigo' => 'OUTLET-TEST-LISTA', 'nombre' => 'Prueba outlet por artículo', 'tipo' => 'special_price', 'modo_precio' => 'pct', 'porcentaje' => 30, 'etiqueta' => 'outlet', 'cupo' => 0, 'cupo_lista' => 1, 'excluir_especial' => 1],
    [['tipo' => 'combinacion', 'id' => S12, 'unidades' => 2], ['tipo' => 'combinacion', 'id' => S3, 'unidades' => 3]]);
$idO = (int) $out->id;
igual('llega a 2 artículos', 2, (int) $out->formatos);
igual('los límites por artículo, de la lista', [S12 => ['unidades' => 2, 'agotado' => ''], S3 => ['unidades' => 3, 'agotado' => '']], KpcCupo::limites($idO));
igual('hay cupos aunque el total sea 0', true, KpcCupo::hayCupos(1));
$p12 = precio(S, S12);
$p3o = precio(S, S3);
igual('los dos bajan un 30 %', [round($h * 0.7, 2), round($p3 * 0.7, 2)], [$p12, $p3o]);
igual('sin tope total: la promoción no limita; por artículo, 2 y 1 … 3', [null, 2, 3], [KpcCupo::quedanPara($idO, 0), KpcCupo::quedanPara($idO, 0, S12), KpcCupo::quedanPara($idO, 0, S3)]);
igual('la landing la manda al cajón «outlet», no a una categoría de marca', 'outlet', (string) Db::getInstance()->getValue('SELECT clave FROM ' . _DB_PREFIX_ . 'kpy_promo_promo WHERE id_kpy_coupon = ' . $idO));
igual('y el cajón tiene el producto', true, (bool) Db::getInstance()->getValue('SELECT 1 FROM ' . _DB_PREFIX_ . 'category_product cp INNER JOIN ' . _DB_PREFIX_ . "kpy_promo_categoria pc ON pc.id_category = cp.id_category WHERE pc.clave = 'outlet' AND cp.id_product = " . S));
$j1 = cesta();
desdeLaFicha($j1, S, S12, 3, false);
desdeLaFicha($j1, S, S3, 2, false);
$f = foto($j1);
igual('reservadas 2 del 12 kg y 2 del 3 kg', [$idO => [S12 => 2, S3 => 2]], $f['reservas']);
cerca('el 12 kg parte: la línea al habitual', $h, precioLinea($j1, S, S12));
cerca('el 3 kg cabe entero: al precio outlet, sin partir', $p3o, precioLinea($j1, S, S3));
igual('un precio de cesta (el del 12 kg)', [1, 0], [preciosCesta($j1, S, S12), preciosCesta($j1, S, S3)]);
cerca('el vale «Outlet» devuelve la rebaja de las 2 del 12 kg', round(2 * ($h - $p12), 2), $f['reglas']['KPYSP'] ?? 0.0);
igual('y se llama Outlet', true, strpos((string) ($f['nombres']['KPYSP'] ?? ''), 'Outlet: 2 ×') === 0);
echo '  vale: ' . ($f['nombres']['KPYSP'] ?? '') . "\n";
$av = avisos($j1);
igual('la cesta avisa solo del 12 kg', 1, count(array_filter($av['lineas'], static fn ($t) => strpos($t, 'Outlet: solo quedaban 2') === 0)));
usar($limpia);
igual('desde otra cesta la ficha dice «Outlet» y lo que queda de cada formato: agotado y 1', ['Se han agotado las unidades en Outlet: a precio habitual', 'outlet', '¡Última unidad en Outlet!', 'outlet'],
    [fichaEstado(S12)['quedan']['texto'], fichaEstado(S12)['quedan']['clase'], fichaEstado(S3)['quedan']['texto'], fichaEstado(S3)['quedan']['clase']]);
$flags = ['kpy_special_price' => ['type' => 'kpy_special_price', 'label' => 'Precio especial']];
KpcFlags::ajustar($flags, ['id_product' => S, 'id_product_attribute' => S12], $modulo);
igual('la etiqueta del listado dice «Outlet» en gris oscuro', ['Outlet', '#3A3A3A'], [$flags['kpy_special_price']['label'] ?? '', $flags['kpy_special_price']['background'] ?? '']);
echo "  (el pedido de la cesta J1 se simula, como en E)\n";
$falsa = new Order($idOrder);
$falsa->id_cart = (int) $j1->id;
$falsa->id_shop = 1;
igual('el pedido se lleva las 2 del 12 kg: ese artículo se agota, el 3 kg no, la promoción sigue', [], KpcCupo::alPedido($falsa));
igual('el 12 kg agotado; el 3 kg con 1 que queda', [true, false, 1], [KpcCupo::limites($idO)[S12]['agotado'] !== '', KpcCupo::limites($idO)[S3]['agotado'] !== '', KpcCupo::quedanPara($idO, 0, S3)]);
igual('la promoción sigue en vigor', true, in_array($idO, array_map('intval', array_column(KpcCupon::enVigor(1), 'id_kpy_coupon')), true));
rehacer();
cerca('tras el reparto, el 12 kg vuelve al precio de siempre', $h, precio(S, S12));
cerca('y el 3 kg sigue en outlet', $p3o, precio(S, S3));
igual('la promoción llega ahora a 1 artículo', 1, (int) Db::getInstance()->getValue('SELECT formatos FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $idO));
$flags = ['kpy_special_price' => ['type' => 'kpy_special_price', 'label' => 'Precio especial']];
KpcFlags::ajustar($flags, ['id_product' => S, 'id_product_attribute' => S12], $modulo);
// En el listado el producto entero sigue con etiqueta de precio: el 3 kg sigue en outlet (3.0.0:
// `KpcFlags` relee la tabla en cada petición; antes una caché vieja hacía que aquí no saliera).
igual('en el listado el producto sigue con etiqueta de precio (el 3 kg sigue en outlet)', true, isset($flags['kpy_special_price']));
usar($limpia);
igual('la ficha ya solo nombra el 3 kg', ['', '¡Última unidad en Outlet!'], [fichaEstado(S12)['quedan']['texto'], fichaEstado(S3)['quedan']['texto']]);
$out->guardarAmbitos([['tipo' => 'combinacion', 'id' => S12, 'unidades' => 2], ['tipo' => 'combinacion', 'id' => S3, 'unidades' => 3]]);
igual('guardar la misma lista no reabre el 12 kg', true, KpcCupo::limites($idO)[S12]['agotado'] !== '');
$out->guardarAmbitos([['tipo' => 'combinacion', 'id' => S12, 'unidades' => 5], ['tipo' => 'combinacion', 'id' => S3, 'unidades' => 3]]);
igual('con 5 unidades en la lista, el 12 kg vuelve: quedan 3', ['', 3], [KpcCupo::limites($idO)[S12]['agotado'], KpcCupo::quedanPara($idO, 0, S12)]);
rehacer();
cerca('y recupera el precio outlet', $p12, precio(S, S12));
$j2 = cesta();
desdeLaFicha($j2, S, S3, 2, false);
$f = foto($j2);
igual('otra cesta con 2 × 3 kg: solo 1 cabe (quedaba 1)', [$idO => [S3 => 1]], $f['reservas']);
cerca('la línea al habitual con el vale de 1', round($p3 - $p3o, 2), $f['reglas']['KPYSP'] ?? 0.0);
$falsa->id_cart = (int) $j2->id;
igual('ese pedido agota el 3 kg y, con el 12 kg ya reabierto, la promoción sigue', [], KpcCupo::alPedido($falsa));
$out->guardarAmbitos([['tipo' => 'combinacion', 'id' => S12, 'unidades' => 2], ['tipo' => 'combinacion', 'id' => S3, 'unidades' => 3]]);
igual('bajando el 12 kg a 2 en la lista: con 2 vendidas, sin unidades que queden (y no agotado hasta un pedido)', [0, ''], [KpcCupo::quedanPara($idO, 0, S12), KpcCupo::limites($idO)[S12]['agotado']]);
KpcCupo::reabrir($idO);
igual('reabrir limpia los artículos agotados', ['', ''], [KpcCupo::limites($idO)[S12]['agotado'], KpcCupo::limites($idO)[S3]['agotado']]);
$out->delete();
rehacer();
foreach ([$j1, $j2] as $c) {
    usar($c);
    $c->deleteProduct(S, S12);
    $c->deleteProduct(S, S3);
}

echo "\nLimpieza\n";
limpiarPromos();
Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_pedido WHERE id_kpy_coupon IN (' . $idL . ', ' . $idO . ')' . ($conPedido ? ' AND id_kpy_coupon <> ' . $idL : ''));
rehacer();
foreach ($cestas as $idCart) {
    $c = new Cart($idCart);
    if (Validate::isLoadedObject($c) && !$c->orderExists()) {
        $c->delete();
    }
}
$barrido = KpcLimpieza::barrer();
echo '  barrido: ' . json_encode($barrido) . "\n";
igual('sin reservas de estas cestas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_cesta WHERE id_cart IN (' . implode(',', $cestas) . ')'));
igual('sin precios de cesta de estas cestas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price WHERE id_cart IN (' . implode(',', $cestas) . ')'));
igual('sin filas de precios de cesta colgadas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_precio_cesta e LEFT JOIN ' . _DB_PREFIX_ . 'cart c ON c.id_cart = e.id_cart WHERE c.id_cart IS NULL'));
$deEstas = implode(' OR ', array_map(static fn (int $id): string => "cr.code LIKE 'KPYRG-{$id}-%' OR cr.code LIKE 'KPYSP-{$id}-%' OR cr.code LIKE 'KPY2U-%-{$id}-%' OR cr.code LIKE 'KPYCP-%-{$id}-%'", $cestas));
igual('sin vales huérfanos de estas cestas', 0, (int) Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "cart_rule cr WHERE ({$deEstas}) AND NOT EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "order_cart_rule ocr WHERE ocr.id_cart_rule = cr.id_cart_rule) AND NOT EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "cart_cart_rule ccr WHERE ccr.id_cart_rule = cr.id_cart_rule)"));
echo $fallos ? "\n$fallos FALLOS\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
