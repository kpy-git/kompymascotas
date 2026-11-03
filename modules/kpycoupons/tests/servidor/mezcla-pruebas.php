<?php
/**
 * Boske Clinical Diet Hypoallergenic (9026) con las tres promociones de verdad de pruebas: Special
 * Price en el 12 kg (SP-BOSKE-10), «12 kg + 3 kg gratis» (REGALO-BOSKE-DEMO) y 2ª ud. −40 % en el
 * 3 kg (2UD-40-261001). El recorrido de Rafa del 16/10 por la noche, con las peticiones como las
 * manda la tienda (la ficha con `group[…]` y `kpy_regalo`; el «+», el «−» y la papelera de cada
 * parte de la línea con sus enlaces). Cestas del cliente de desarrollo; se borran al final.
 *   cat hypo-mezcla.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php
 */
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
$ctx->cookie = new Cookie('kpyhypo');
$ctx->currentLocale = $kernel->getContainer()->get('prestashop.core.localization.locale.repository')->getLocale($ctx->language->getLocale());
$ctx->controller = new stdClass();
$ctx->controller->php_self = 'cart';
$modulo = Module::getInstanceByName('kpycoupons');
const H = 9026; const H12 = 93679; const H3 = 93680;

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
function cerca(string $que, float $esperado, float $real): void
{
    global $fallos;
    if (abs($esperado - $real) <= 0.011) {
        echo "  ok  $que (" . number_format($real, 2, ',', '.') . ")\n";
        return;
    }
    ++$fallos;
    echo "  MAL $que\n      esperado: $esperado\n      real:     $real\n";
}
$cestas = [];
function cesta(): Cart
{
    global $ctx, $cestas;
    $c = new Cart();
    $c->id_customer = 4; $c->id_address_delivery = 69508; $c->id_address_invoice = 69508;
    $c->id_lang = 1; $c->id_currency = 1; $c->id_shop = 1; $c->id_shop_group = 1; $c->id_carrier = 4;
    $c->secure_key = $ctx->customer->secure_key;
    $c->add();
    $ctx->cart = $c;
    $cestas[] = (int) $c->id;
    return $c;
}
function grupoDe(int $ipa): array
{
    $f = Db::getInstance()->getRow('SELECT a.id_attribute_group, pac.id_attribute FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute WHERE pac.id_product_attribute = ' . $ipa);
    return [(int) $f['id_attribute_group'] => (int) $f['id_attribute']];
}
/** «Añadir a la cesta» en la ficha: el formulario con `group[…]` y, si es la tarjeta de un formato con regalo, `kpy_regalo`. */
function ficha(Cart $cart, int $ipa, int $uds, ?bool $regalo): void
{
    $_GET = [];
    $_POST = ['add' => '1', 'action' => 'update', 'id_product' => (string) H, 'group' => grupoDe($ipa), 'qty' => (string) $uds];
    if ($regalo !== null) {
        $_POST['kpy_regalo'] = $regalo ? '1' : '0';
    }
    try {
        $cart->updateQty($uds, H, $ipa);
    } finally {
        $_POST = [];
    }
    otraPeticion($cart);
}
/** Un enlace de la cesta (el «+», el «−» o la papelera de una fila), como lo manda el tema. */
function enlace(Cart $cart, string $url, int $qty, string $op): void
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $get);
    $_GET = $get;
    $_POST = ['ajax' => '1', 'action' => 'update', 'qty' => (string) $qty, 'op' => $op];
    $q = (int) ($_GET['qty'] ?? $_POST['qty']);
    $o = (string) ($_GET['op'] ?? $_POST['op']);
    try {
        $cart->updateQty($q, (int) $get['id_product'], (int) $get['id_product_attribute'], 0, $o);
    } finally {
        $_GET = [];
        $_POST = [];
    }
    otraPeticion($cart);
}
/** Lo que guarda PrestaShop en memoria entre una petición y otra, fuera (en la tienda, cada petición empieza de cero). */
function otraPeticion(Cart $cart): void
{
    Cache::clean('*');
    SpecificPrice::flushCache();
    Product::flushPriceCache();
    $cart->getProducts(true);
}
function foto(Cart $cart): array
{
    $cart = new Cart((int) $cart->id);
    $lineas = [];
    foreach ($cart->getProducts(true) as $l) {
        $lineas[$l['attributes_small']] = [(int) $l['cart_quantity'], round((float) $l['price_wt'], 2)];
    }
    ksort($lineas);
    $reglas = [];
    foreach ($cart->getCartRules() as $r) {
        $reglas[substr((string) $r['code'], 0, 5)] = round((float) $r['value_real'], 2);
    }
    ksort($reglas);
    $e = KpcRegalo::elegidasDe((int) $cart->id);
    return [
        'lineas' => $lineas,
        'reglas' => $reglas,
        'con_regalo' => (int) ($e[H12]['unidades'] ?? 0),
        'total' => round((float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING), 2),
    ];
}
/** Las filas de la cesta como las pinta la tienda: [parte, formato, uds, total, lo que dice debajo]. */
function filas(Cart $cart): array
{
    global $modulo;
    $cart = new Cart((int) $cart->id);
    Cache::clean('presentedCart_*');
    $presentada = (new PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter())->present($cart, true);
    $salida = [];
    foreach ($presentada['products'] as $p) {
        $bloques = KpcLineas::bloques($p, $cart, 1, $modulo);
        $dice = implode(' | ', array_map(static fn ($b) => trim($b['chip'] . ': ' . trim($b['fuerte'] . ' ' . $b['texto'] . ' ' . $b['precio'] . ($b['tachado'] ? ' ~' . $b['tachado'] . '~' : '')) . ($b['sub'] ? ' / ' . $b['sub'] : '')), $bloques));
        $salida[] = [(string) ($p['kpy_parte'] ?? ''), (string) $p['attributes_small'], (int) $p['quantity'], (string) $p['total'], $dice, $p];
    }
    return $salida;
}
function verFilas(array $filas): void
{
    foreach ($filas as [$parte, $formato, $uds, $total, $dice]) {
        printf("      %-7s %-28s x%d %10s  %s\n", $parte ?: '-', $formato, $uds, $total, $dice);
    }
}
function filaDe(array $filas, string $parte, string $formato): ?object
{
    foreach ($filas as $f) {
        if ($f[0] === $parte && strpos($f[1], $formato) === 0) {
            return $f[5];
        }
    }
    return null;
}

$ctx->cart = cesta();
$p12 = round((float) Product::getPriceStatic(H, true, H12, 6), 2);
$p3 = round((float) Product::getPriceStatic(H, true, H3, 6), 2);
$old = (float) Db::getInstance()->getValue('SELECT old_discount FROM ' . _DB_PREFIX_ . 'kpy_special_price WHERE id_product = ' . H . ' AND id_product_attribute = ' . H12 . ' AND NOW() BETWEEN date_from AND expire ORDER BY date_from DESC');
$h12 = round((float) Product::getPriceStatic(H, true, H12, 6, null, false, false) * (1 - $old), 2);
$segunda = round($p3 * 0.40, 2);
echo "Precios: 12 kg {$p12} € (habitual {$h12} €) · 3 kg {$p3} € · 2ª ud. del 3 kg −{$segunda} €\n";

try {
    echo "\nA. Solo 2 × 3 kg: la 2ª al −40 %\n";
    $c = cesta();
    ficha($c, H3, 2, null);
    $f = foto($c);
    igual('una regla, la de la 2ª unidad', ['KPY2U'], array_keys($f['reglas']));
    cerca('descuenta el 40 % de un 3 kg', $segunda, $f['reglas']['KPY2U'] ?? 0.0);

    echo "\nB. «12 kg + 3 kg gratis» + 1 × 3 kg: el regalado no hace par\n";
    $c = cesta();
    ficha($c, H12, 1, true);
    ficha($c, H3, 1, null);
    $f = foto($c);
    igual('el 3 kg: uno regalado y uno pagado', 2, $f['lineas']['Saco de 3 Kg'][0] ?? 0);
    igual('solo la regla del regalo (sin 2ª unidad)', ['KPYRG'], array_keys($f['reglas']));
    $r = KpcResumen::deLaCesta(new Cart((int) $c->id), 1, $modulo);
    igual('y el empujón: con 1 pagado, «añade 1 ud.» sí', 1, count($r['animos']));

    echo "\nC. La cesta de la captura: «12 kg + 3 kg gratis» + 2 × 3 kg\n";
    $c = cesta();
    ficha($c, H12, 1, true);
    ficha($c, H3, 2, null);
    $f = foto($c);
    igual('12 kg a su precio habitual; 3 kg × 3 (uno regalado)', ['Saco de 12 Kg' => [1, $h12], 'Saco de 3 Kg' => [3, $p3]], $f['lineas']);
    cerca('regalo', $p3, $f['reglas']['KPYRG'] ?? 0.0);
    cerca('2ª unidad, con los dos pagados', $segunda, $f['reglas']['KPY2U'] ?? 0.0);
    cerca('total', $h12 + 2 * $p3 - $segunda, $f['total']);
    $r = KpcResumen::deLaCesta(new Cart((int) $c->id), 1, $modulo);
    igual('sin el «Añade 1 ud. más» de antes (los dos pagados ya hacen par)', 0, count($r['animos']));
    $filas = filas($c);
    verFilas($filas);
    igual('tres filas: 12 kg con regalo, su regalo debajo, y los 3 kg pagados', [['con', 1], ['regalo', 1], ['pagado', 2]], array_map(static fn ($x) => [$x[0], $x[2]], $filas));
    igual('el 12 kg se llama como su tarjeta', 'Saco de 12 Kg + 3 kg gratis', $filas[0][1]);
    $pagado = filaDe($filas, 'pagado', 'Saco de 3 Kg');
    igual('la papelera de los 3 kg pagados quita solo esos dos', true, $pagado !== null && strpos((string) $pagado['remove_from_cart_url'], 'op=down&qty=2') !== false);

    echo "\nD. Y ahora la tarjeta «12 kg» con Special Price (lo que hizo Rafa): se suma aparte, el regalo se queda\n";
    ficha($c, H12, 1, false);
    $f = foto($c);
    igual('dos 12 kg: uno con regalo y otro sin', [2, 1], [$f['lineas']['Saco de 12 Kg'][0] ?? 0, $f['con_regalo']]);
    cerca('el regalo sigue', $p3, $f['reglas']['KPYRG'] ?? 0.0);
    cerca('el vale «Special Price» devuelve la diferencia del de la tarjeta de siempre', $h12 - $p12, $f['reglas']['KPYSP'] ?? 0.0);
    cerca('total: 12 kg con regalo + 12 kg Special Price + 3 kg con su 2ª unidad', $h12 + $p12 + 2 * $p3 - $segunda, $f['total']);
    $filas = filas($c);
    verFilas($filas);
    igual('cuatro filas: el saco con regalo, su regalo, el de siempre y los 3 kg pagados', [['con', 1], ['regalo', 1], ['sin', 1], ['pagado', 2]], array_map(static fn ($x) => [$x[0], $x[2]], $filas));
    $sin = filaDe($filas, 'sin', 'Saco de 12 Kg');
    $con = filaDe($filas, 'con', 'Saco de 12 Kg');

    echo "\nE. «+» en la fila «12 kg» (Special Price)\n";
    enlace($c, (string) $sin['up_quantity_url'], 1, 'up');
    $f = foto($c);
    igual('tres 12 kg, uno con regalo', [3, 1], [$f['lineas']['Saco de 12 Kg'][0] ?? 0, $f['con_regalo']]);
    cerca('el vale «Special Price», de los dos', 2 * ($h12 - $p12), $f['reglas']['KPYSP'] ?? 0.0);

    echo "\nF. «+» en la fila «12 kg + 3 kg gratis»\n";
    $filas = filas($c);
    enlace($c, (string) filaDe($filas, 'con', 'Saco de 12 Kg')['up_quantity_url'], 1, 'up');
    $f = foto($c);
    igual('cuatro 12 kg, dos con regalo; dos 3 kg regalados + dos pagados', [4, 2, 4], [$f['lineas']['Saco de 12 Kg'][0] ?? 0, $f['con_regalo'], $f['lineas']['Saco de 3 Kg'][0] ?? 0]);
    cerca('regalo de dos', 2 * $p3, $f['reglas']['KPYRG'] ?? 0.0);

    echo "\nG. Papelera de la fila «12 kg» (Special Price): se van los dos de la tarjeta de siempre\n";
    $filas = filas($c);
    enlace($c, (string) filaDe($filas, 'sin', 'Saco de 12 Kg')['remove_from_cart_url'], 1, 'up');
    $f = foto($c);
    igual('quedan dos 12 kg, los dos con regalo, y sin vale «Special Price»', [2, 2, false], [$f['lineas']['Saco de 12 Kg'][0] ?? 0, $f['con_regalo'], isset($f['reglas']['KPYSP'])]);

    echo "\nH. «−» en la fila «12 kg + 3 kg gratis»\n";
    $filas = filas($c);
    enlace($c, (string) filaDe($filas, 'con', 'Saco de 12 Kg')['down_quantity_url'], 1, 'down');
    $f = foto($c);
    igual('un 12 kg con regalo; 3 kg: uno regalado + dos pagados', [1, 1, 3], [$f['lineas']['Saco de 12 Kg'][0] ?? 0, $f['con_regalo'], $f['lineas']['Saco de 3 Kg'][0] ?? 0]);

    echo "\nI. Papelera de la fila de los 3 kg pagados: el regalo se queda\n";
    $filas = filas($c);
    enlace($c, (string) filaDe($filas, 'pagado', 'Saco de 3 Kg')['remove_from_cart_url'], 1, 'up');
    $f = foto($c);
    igual('el 3 kg: solo el regalado; sin 2ª unidad', [1, ['KPYRG']], [$f['lineas']['Saco de 3 Kg'][0] ?? 0, array_keys($f['reglas'])]);

    echo "\nJ. La tarjeta «12 kg + 3 kg gratis» con 2 unidades, sobre un 12 kg de la tarjeta de siempre\n";
    $c = cesta();
    ficha($c, H12, 1, false);
    ficha($c, H12, 2, true);
    $f = foto($c);
    igual('tres 12 kg, dos con regalo, dos 3 kg regalados', [3, 2, 2], [$f['lineas']['Saco de 12 Kg'][0] ?? 0, $f['con_regalo'], $f['lineas']['Saco de 3 Kg'][0] ?? 0]);
    cerca('y el «Special Price» del de siempre', $h12 - $p12, $f['reglas']['KPYSP'] ?? 0.0);
    cerca('total', 2 * $h12 + $p12, $f['total']);

    echo "\nK. Papelera del vale «Regalo»: todo a la tarjeta de siempre\n";
    $idRegla = (int) CartRule::getIdByCode(KpcRegalo::codigo($c, H));
    KpcRegalo::quitarPorRegla($c, $idRegla);
    $modulo->sincronizarCesta($c);
    otraPeticion($c);
    $f = foto($c);
    igual('tres 12 kg a su Special Price, sin regalo ni vales', [['Saco de 12 Kg' => [3, $p12]], []], [$f['lineas'], $f['reglas']]);
} finally {
    foreach ($cestas as $id) {
        $c = new Cart($id);
        if (Validate::isLoadedObject($c) && !$c->orderExists()) {
            $c->delete();
        }
    }
    $barrido = KpcLimpieza::barrer();
    echo "\nCestas borradas: " . implode(', ', $cestas) . ' · barrido: ' . json_encode($barrido) . "\n";
    igual('sin precios de cesta de estas cestas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price WHERE id_cart IN (' . implode(',', $cestas) . ')'));
    igual('sin vales KPYSP colgados', 0, (int) Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "cart_rule cr WHERE cr.code LIKE 'KPYSP-%' AND NOT EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "order_cart_rule o WHERE o.id_cart_rule = cr.id_cart_rule) AND NOT EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "cart_cart_rule cc WHERE cc.id_cart_rule = cr.id_cart_rule)"));
    echo $fallos ? "\n$fallos FALLOS\n" : "\nTodo bien\n";
}
