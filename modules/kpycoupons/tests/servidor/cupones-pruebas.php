<?php
/**
 * Pruebas de integración de **varios cupones a la vez** (kpycoupons 3.2.0, 1/11/2026) y de la capa de
 * campaña en la ficha y en la cesta, en la tienda de pruebas: páginas de verdad por HTTP (curl a la
 * propia tienda, con cookies por visitante, como el navegador) y cestas de la API. Desde
 * /var/www/html/kompy, dentro del contenedor:
 *   cat cupones-pruebas.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php -- [--pedido]
 *
 * Lo que pasa en una campaña con un cupón por marca (lo que vio Rafa en producción el 1/11): cuatro
 * cupones de prueba en cuatro marcas (Boske y Specific «ya puestos para todos», Advance «lo pone el
 * cliente», Frontline con cupo), y un Special Price de prueba. Se comprueba que:
 *   - en la ficha, un cupón «para todos» sale puesto y baja el precio grande (3.2.0), y ver la ficha de
 *     otra marca no desmarca el de la primera;
 *   - en la cesta van los dos (o tres) cupones a la vez, cada uno descontando solo lo suyo; «Quitar» o la
 *     papelera quitan solo ese y no vuelve solo (sí tras una visita nueva desde Google); escribir el
 *     código lo vuelve a poner; sin cookie (la pasarela), puesto es puesto; un robot no lleva ninguno;
 *   - un cupón con cupo (vales `KPYCP`) convive con los demás; el pedido (con --pedido) lleva los dos vales;
 *   - con la campaña encendida: nombres y colores de campaña en el bloque, los chips enteros («Cupón
 *     Black», no «Cupón Black −1»), la ficha con solo Special Price lleva la piel, y las filas de la
 *     cesta y la ventana «Añadido» van con los colores de la campaña.
 * Mientras dura apaga las demás promociones y la campaña (y las vuelve a dejar como estaban, pase lo que pase).
 */
require_once 'autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->usePutenv(false)->loadEnv(getcwd() . '/.env');
require 'config/config.inc.php';
$kernel = new FrontKernel('dev', false);
$kernel->boot();
$conPedido = in_array('--pedido', $argv ?? [], true);

$ctx = Context::getContext();
Shop::setContext(Shop::CONTEXT_SHOP, 1);
$ctx->shop = new Shop(1);
$ctx->language = new Language(1);
$ctx->currency = new Currency(1);
$ctx->country = new Country(6);
$ctx->customer = new Customer(4);
$ctx->link = new Link();
$ctx->cookie = new Cookie('kpycupones');
$ctx->currentLocale = $kernel->getContainer()->get('prestashop.core.localization.locale.repository')->getLocale($ctx->language->getLocale());
$modulo = Module::getInstanceByName('kpycoupons');

// A: Boske salmón (12 kg, 3 kg); B: Specific CXD-M (12 kg, 4 kg); C: Advance Sensitive (12 kg); D: Frontline Tri-Act (10-20 kg); E: Boske Hypoallergenic (12 kg, con Special Price de prueba).
const A = 8299; const A12 = 92467; const A3 = 92468;
const B = 649; const B12 = 90080; const B4 = 90078;
const C = 176; const C12 = 230;
const D = 3312; const D1 = 5178;
const E = 9026; const E12 = 93679;
const CLIENTE = 4; const DIRECCION = 69508;
const CODIGOS = ['TEST-MC-A', 'TEST-MC-B', 'TEST-MC-C', 'TEST-MC-D', 'TEST-MC-SP'];
const BASE = 'http://host.docker.internal';
const HOST = 'kompy.local';
const UA_CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
const UA_BOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

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
function euros(float $x): string
{
    global $ctx;
    return $ctx->currentLocale->formatPrice($x, 'EUR');
}

// ------------------------------------------------------------------ HTTP, como el navegador
function visitante(string $nombre, string $ua = UA_CHROME): array
{
    $jar = sys_get_temp_dir() . '/kpc-cupones-' . $nombre . '-' . getmypid() . '.txt';
    @unlink($jar);
    return ['jar' => $jar, 'ua' => $ua, 'nombre' => $nombre];
}
function http(array $v, string $ruta, ?array $post = null): array
{
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => ['Host: ' . HOST, 'Accept: text/html,*/*', 'Accept-Language: es-ES,es', 'X-Requested-With: XMLHttpRequest'],
        CURLOPT_USERAGENT => $v['ua'], CURLOPT_COOKIEJAR => $v['jar'], CURLOPT_COOKIEFILE => $v['jar'],
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $r = (string) curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tam = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['codigo' => $codigo, 'cabeceras' => substr($r, 0, $tam), 'cuerpo' => substr($r, $tam)];
}
function ruta(string $url): string
{
    $p = parse_url($url);
    return ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
}
function ficha(int $id): string
{
    global $ctx;
    return ruta($ctx->link->getProductLink($id));
}
$cestaRuta = ruta($ctx->link->getPageLink('cart'));
$aplicarRuta = ruta($ctx->link->getModuleLink('kpycoupons', 'aplicar'));
$modalRuta = ruta($ctx->link->getModuleLink('ps_shoppingcart', 'ajax'));

function precioGrande(string $html): string
{
    return preg_match('/product__current-price[^>]*>\s*([^<]+)/u', $html, $m) ? trim($m[1]) : '';
}
function bloque(string $html): array
{
    if (!preg_match('/<div class="kpc-ficha([^"]*)" data-kpc-ficha data-kpc-estado="([^"]*)" data-kpc-regalo="(\d)"([^>]*)>/', $html, $m)) {
        return ['estado' => null, 'clases' => '', 'campana' => '', 'estilo' => ''];
    }
    $b = ['estado' => $m[2], 'clases' => trim($m[1]), 'campana' => '', 'estilo' => ''];
    if (preg_match('/data-kpc-campana="([^"]*)"/', $m[4], $c)) {
        $b['campana'] = $c[1];
    }
    if (preg_match('/style="([^"]*)"/', $m[4], $c)) {
        $b['estilo'] = $c[1];
    }
    $b['cupon_titulo'] = preg_match('/kpc-cupon__titulo">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['cupon_sub'] = preg_match('/kpc-cupon__sub">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['cupon_accion'] = preg_match('/data-kpc-accion="([^"]+)"/', $html, $m) ? $m[1] : '';
    $b['cupon_clases'] = preg_match('/class="kpc-cupon([^"]*)"/', $html, $m) ? trim($m[1]) : '';
    return $b;
}
function jsonLd(string $html): ?float
{
    if (!preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $mm)) {
        return null;
    }
    foreach ($mm[1] as $json) {
        $d = json_decode($json, true);
        $ofertas = $d['offers'] ?? null;
        if ($ofertas === null) {
            continue;
        }
        if (isset($ofertas['price'])) {
            return (float) $ofertas['price'];
        }
        foreach ((array) $ofertas as $o) {
            if (isset($o['price'])) {
                return (float) $o['price'];
            }
        }
    }
    return null;
}
function flags(string $html): array
{
    if (!preg_match('/<ul class="product-flags js-product-flags">(.*?)<\/ul>/s', $html, $m)) {
        return [];
    }
    preg_match_all('/<li class="badge ([^"]*)"[^>]*>(.*?)<\/li>/s', $m[1], $mm, PREG_SET_ORDER);
    return array_map(static fn ($f) => trim(strip_tags($f[2])), $mm);
}
function chips(string $html): array
{
    preg_match_all('/<div class="kpc-attr[^>]*>([^<]*)/', $html, $mm);
    return array_map('trim', $mm[1]);
}
function token(string $html): string
{
    return preg_match('/name="token" value="([a-f0-9]+)"/', $html, $m) ? $m[1] : '';
}
function grupo(string $html, string $nombre): array
{
    if (!preg_match('/name="group\[(\d+)\]" value="(\d+)" title="' . preg_quote($nombre, '/') . '"/', $html, $m)) {
        return [];
    }
    return [(int) $m[1] => (int) $m[2]];
}
/** Añadir desde la ficha, como el formulario del tema (ajax): devuelve los vales de la cesta presentada y el descuento. */
function anadir(array $v, string $tok, int $id, int $ipa, array $grupo, int $qty = 1): array
{
    global $cestaRuta;
    $r = http($v, $cestaRuta, ['ajax' => 1, 'add' => 1, 'action' => 'update', 'id_product' => $id, 'id_product_attribute' => $ipa, 'qty' => $qty, 'token' => $tok, 'group' => $grupo]);
    return cestaDe($r['cuerpo']);
}
/** «+» en una línea de la cesta (op=up), como el tema. */
function masUno(array $v, string $tok, int $id, int $ipa): array
{
    global $cestaRuta;
    $r = http($v, $cestaRuta, ['ajax' => 1, 'action' => 'update', 'id_product' => $id, 'id_product_attribute' => $ipa, 'qty' => 1, 'op' => 'up', 'token' => $tok]);
    return cestaDe($r['cuerpo']);
}
/** Lo que interesa de la cesta presentada que devuelve PrestaShop: vales (código => importe) y descuento total. */
function cestaDe(string $json): array
{
    $j = json_decode($json, true);
    if (!is_array($j)) {
        return ['ok' => false, 'vales' => [], 'descuento' => 0.0, 'error' => substr($json, 0, 200)];
    }
    $vales = [];
    foreach ((array) ($j['cart']['vouchers']['added'] ?? []) as $vale) {
        $codigo = (string) ($vale['code'] ?? '');
        $clave = strpos($codigo, 'KPY') === 0 ? substr($codigo, 0, 5) : $codigo;
        $vales[$clave] = round((float) ($vale['reduction'] ?? 0), 2);
    }
    ksort($vales);
    $descuento = (float) ($j['cart']['subtotals']['discounts']['amount'] ?? 0);
    return ['ok' => empty($j['errors']) || $j['errors'] === [], 'vales' => $vales, 'descuento' => round($descuento, 2), 'n' => (int) ($j['cart']['products_count'] ?? 0), 'errores' => $j['errors'] ?? []];
}
/** La cesta de la página de la cesta: los vales que lista PrestaShop (por su nombre) y las píldoras de las filas. */
function paginaCesta(array $v): array
{
    global $cestaRuta;
    $html = http($v, $cestaRuta)['cuerpo'];
    preg_match_all('/cart-voucher__name[^>]*>([^<]+)/', $html, $mm);
    preg_match_all('/<div class="kpc-lr kpc-lr--([a-z]+)([^"]*)"([^>]*)>\s*<span class="kpc-lr__chip"([^>]*)>(.*?)<\/span>/s', $html, $pp, PREG_SET_ORDER);
    $pildoras = [];
    foreach ($pp as $p) {
        $pildoras[] = ['tipo' => $p[1], 'campana' => strpos($p[2], 'kpc-lr--campana') !== false, 'estilo' => preg_match('/style="([^"]*)"/', $p[3], $s) ? $s[1] : '', 'chip' => trim(strip_tags($p[5]))];
    }
    $datos = preg_match('/<script type="application\/json" data-kpc-datos>(.*?)<\/script>/s', $html, $d) ? json_decode(html_entity_decode($d[1]), true) : null;
    return ['vales' => array_map('trim', $mm[1]), 'pildoras' => $pildoras, 'datos' => $datos, 'html' => $html];
}
/** La última cesta de visitante (sin cliente) tocada: la del visitante que acaba de añadir. */
function idCesta(int $desde): int
{
    return (int) Db::getInstance()->getValue('SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart > ' . $desde . ' AND id_customer = 0 ORDER BY date_upd DESC, id_cart DESC');
}
function reglasEnBd(int $idCart): array
{
    $salida = [];
    foreach (Db::getInstance()->executeS('SELECT cr.code FROM ' . _DB_PREFIX_ . 'cart_cart_rule ccr INNER JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON cr.id_cart_rule = ccr.id_cart_rule WHERE ccr.id_cart = ' . $idCart) ?: [] as $f) {
        $codigo = (string) $f['code'];
        $salida[] = strpos($codigo, 'KPY') === 0 ? substr($codigo, 0, 5) : $codigo;
    }
    sort($salida);
    return $salida;
}

// ------------------------------------------------------------------ fixtures (como ficha-pruebas)
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
function grupoDe(int $ipa): array
{
    $f = Db::getInstance()->getRow('SELECT a.id_attribute_group, pac.id_attribute FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
        INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute WHERE pac.id_product_attribute = ' . $ipa);
    return [(int) $f['id_attribute_group'] => (int) $f['id_attribute']];
}
function desdeLaFicha(Cart $cart, int $id, int $ipa, int $uds): bool
{
    global $ctx;
    $ctx->cart = $cart;
    $_POST = ['add' => '1', 'action' => 'update', 'id_product' => (string) $id, 'group' => grupoDe($ipa), 'qty' => (string) $uds];
    try {
        return $cart->updateQty($uds, $id, $ipa) === true;
    } finally {
        $_POST = [];
    }
}
function foto(Cart $cart): array
{
    $reglas = [];
    foreach ($cart->getCartRules() as $cr) {
        $codigo = (string) $cr['code'];
        $clave = strpos($codigo, 'KPY') === 0 ? substr($codigo, 0, 5) : $codigo;
        $reglas[$clave] = round((float) $cr['value_real'], 2);
    }
    ksort($reglas);
    return ['reglas' => $reglas, 'descuentos' => round((float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS), 2)];
}
function sincronizar(Cart $cart): void
{
    global $modulo, $ctx;
    $ctx->cart = $cart;
    $modulo->sincronizarCesta($cart);
}
function promo(array $campos, array $ambitos): KpcCupon
{
    $c = new KpcCupon();
    $base = [
        'porcentaje' => 0, 'activo' => 1, 'fecha_desde' => date('Y-m-d H:i:00', time() - 300), 'fecha_hasta' => date('Y-m-d 23:59:59', strtotime('+7 days')),
        'en_ficha' => 1, 'excluir_especial' => 1, 'incluir_packs' => 0, 'packs' => '1', 'sin_outlet' => 1, 'en_merchant' => 1, 'cupo_ficha' => 1,
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
    KpcFicha::olvidar();
    KpcFlags::olvidarCache();
    KpcCupo::olvidarCache();
    KpcCampana::olvidar();
}
function campana(bool $encendida): void
{
    global $campanaBf;
    if ($campanaBf > 0) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = ' . ($encendida ? 1 : 0) . ' WHERE id_kpy_promo_campana = ' . $campanaBf);
    }
    if (class_exists('KplCampanas')) {
        KplCampanas::olvidar();
    }
    KpcCampana::olvidar();
}

echo "Preparación\n";
$ctx->cart = cesta();
$maxCart = (int) Db::getInstance()->getValue('SELECT MAX(id_cart) FROM ' . _DB_PREFIX_ . 'cart');
limpiarPromos();
$apagadas = array_map('intval', array_column(Db::getInstance()->executeS("SELECT id_kpy_coupon FROM " . _DB_PREFIX_ . "kpy_coupon WHERE activo = 1") ?: [], 'id_kpy_coupon'));
if ($apagadas !== []) {
    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_coupon SET activo = 0 WHERE id_kpy_coupon IN (' . implode(',', $apagadas) . ')');
    echo '  apagadas mientras tanto: ' . implode(', ', $apagadas) . "\n";
}
// La campaña Black Friday de la landing: apagada en la primera parte, encendida en la última; al final, como estaba.
$campanaBf = (int) Db::getInstance()->getValue("SELECT id_kpy_promo_campana FROM " . _DB_PREFIX_ . "kpy_promo_campana WHERE clave = 'blackfriday'");
$campanaEstaba = (int) Db::getInstance()->getValue('SELECT activa FROM ' . _DB_PREFIX_ . 'kpy_promo_campana WHERE id_kpy_promo_campana = ' . $campanaBf);
$otrasCampanas = array_map('intval', array_column(Db::getInstance()->executeS('SELECT id_kpy_promo_campana FROM ' . _DB_PREFIX_ . 'kpy_promo_campana WHERE activa = 1 AND id_kpy_promo_campana <> ' . $campanaBf) ?: [], 'id_kpy_promo_campana'));
if ($otrasCampanas !== []) {
    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = 0 WHERE id_kpy_promo_campana IN (' . implode(',', $otrasCampanas) . ')');
}
campana(false);
echo "  campaña Black Friday #{$campanaBf} (estaba " . ($campanaEstaba ? 'encendida' : 'apagada') . "): apagada para empezar\n";
register_shutdown_function(static function () use ($apagadas, $maxCart, $campanaBf, $campanaEstaba, $otrasCampanas): void {
    global $cestas;
    limpiarPromos();
    if ($campanaBf > 0) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = ' . $campanaEstaba . ' WHERE id_kpy_promo_campana = ' . $campanaBf);
    }
    if ($otrasCampanas !== []) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = 1 WHERE id_kpy_promo_campana IN (' . implode(',', $otrasCampanas) . ')');
    }
    echo '  campaña Black Friday como estaba (' . ($campanaEstaba ? 'encendida' : 'apagada') . ")\n";
    if ($apagadas !== []) {
        Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_coupon SET activo = 1 WHERE id_kpy_coupon IN (' . implode(',', $apagadas) . ')');
        KpcCupon::olvidarCache();
        KpcReparto::rehacer(1);
        echo '  encendidas otra vez: ' . implode(', ', $apagadas) . "\n";
    }
    $ids = array_merge($cestas, array_map('intval', array_column(Db::getInstance()->executeS('SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart > ' . $maxCart . ' AND id_customer = 0') ?: [], 'id_cart')));
    foreach (array_unique($ids) as $idCart) {
        $c = new Cart($idCart);
        if (Validate::isLoadedObject($c) && !$c->orderExists()) {
            $c->delete();
        }
    }
    KpcLimpieza::barrer();
    echo '  cestas borradas: ' . count(array_unique($ids)) . "\n";
});
rehacer();

$cA = promo(['codigo' => 'TEST-MC-A', 'nombre' => 'Prueba cupón Boske', 'tipo' => 'cupon', 'porcentaje' => 10, 'auto_web' => 1], [['tipo' => 'combinacion', 'id' => A12], ['tipo' => 'combinacion', 'id' => A3]]);
$cB = promo(['codigo' => 'TEST-MC-B', 'nombre' => 'Prueba cupón Specific', 'tipo' => 'cupon', 'porcentaje' => 15, 'auto_web' => 1], [['tipo' => 'combinacion', 'id' => B12], ['tipo' => 'combinacion', 'id' => B4]]);
$cC = promo(['codigo' => 'TEST-MC-C', 'nombre' => 'Prueba cupón Advance', 'tipo' => 'cupon', 'porcentaje' => 12, 'auto_web' => 0], [['tipo' => 'combinacion', 'id' => C12]]);
$cD = promo(['codigo' => 'TEST-MC-D', 'nombre' => 'Prueba cupón Frontline con cupo', 'tipo' => 'cupon', 'porcentaje' => 10, 'auto_web' => 1, 'cupo' => 2], [['tipo' => 'combinacion', 'id' => D1]]);
igual('cuatro cupones de prueba, cada uno con su regla de carrito', [2, 2, 1, 1, true], [(int) $cA->formatos, (int) $cB->formatos, (int) $cC->formatos, (int) $cD->formatos, (int) $cA->id_cart_rule > 0 && (int) $cB->id_cart_rule > 0 && (int) $cC->id_cart_rule > 0 && (int) $cD->id_cart_rule > 0]);
$pA = precio(A, A12);
$pB = precio(B, B12);
$pC = precio(C, C12);
$pD = precio(D, D1);
echo "  precios: A 12 kg {$pA} · B 12 kg {$pB} · C 12 kg {$pC} · D {$pD}\n";

echo "\nA. La ficha: «para todos» baja el precio, y la ficha de otra marca no desmarca el primero\n";
$v = visitante('v1');
$html = http($v, ficha(A))['cuerpo'];
$b = bloque($html);
igual('ficha A: cupón puesto («para todos»), estado cupon, el precio grande con el 10 %, el JSON-LD sin él', ['cupon', 'Cupón −10 % aplicado', euros(round($pA * 0.9, 2)), $pA, true], [$b['estado'], $b['cupon_titulo'], precioGrande($html), jsonLd($html), strpos($b['cupon_clases'], 'is-aplicado') !== false]);
igual('y lo dice como el de canal: «Ahorras … · se mantiene en la cesta»', 'Ahorras ' . euros(round($pA - round($pA * 0.9, 2), 2)) . ' · se mantiene en la cesta', $b['cupon_sub']);
$tokA = token($html);
$gA12 = grupo($html, 'Saco de 12 Kg');
$htmlB = http($v, ficha(B))['cuerpo'];
$b = bloque($htmlB);
igual('ficha B después: también puesto (hasta la 3.1 salía sin poner: la cookie solo tenía A)', ['cupon', 'Cupón −15 % aplicado', euros(round($pB * 0.85, 2))], [$b['estado'], $b['cupon_titulo'], precioGrande($htmlB)]);
$gB12 = grupo($htmlB, 'Saco de 12 Kg');
$html = http($v, ficha(A))['cuerpo'];
igual('y de vuelta en A, sigue puesto', 'cupon', bloque($html)['estado']);

echo "\nB. La cesta lleva los dos cupones a la vez, cada uno con lo suyo\n";
$r = anadir($v, $tokA, A, A12, $gA12);
igual('añadir A: su regla en la cesta con el 10 % del 12 kg', ['TEST-MC-A' => round($pA * 0.1, 2)], $r['vales']);
$idCesta1 = idCesta($maxCart);
$r = anadir($v, $tokA, B, B12, $gB12);
igual('añadir B: las dos reglas, A con su 10 % y B con su 15 %', ['TEST-MC-A' => round($pA * 0.1, 2), 'TEST-MC-B' => round($pB * 0.15, 2)], $r['vales']);
cerca('el descuento total es la suma', round($pA * 0.1, 2) + round($pB * 0.15, 2), $r['descuento']);
igual('(en la base de datos, las mismas dos)', ['TEST-MC-A', 'TEST-MC-B'], reglasEnBd($idCesta1));
$r = masUno($v, $tokA, A, A12);
igual('con 2 × A el cupón A descuenta el doble y B sigue igual', ['TEST-MC-A' => round($pA * 2 * 0.1, 2), 'TEST-MC-B' => round($pB * 0.15, 2)], $r['vales']);
$pc = paginaCesta($v);
igual('la página de la cesta lista los dos vales y dibuja cada fila con su píldora de cupón', [2, ['cupon', 'cupon'], ['Cupón −10 %', 'Cupón −15 %']], [count($pc['vales']), array_column($pc['pildoras'], 'tipo'), array_column($pc['pildoras'], 'chip')]);
igual('y los dos van como cupones en los datos para cesta.js', ['cupon', 'cupon'], array_values(array_map(static fn ($x) => $x['tipo'], (array) ($pc['datos']['vales'] ?? []))));
foreach (['A' => A, 'B' => B] as $nombre => $id) {
    $html = http($v, ficha($id))['cuerpo'];
    igual("la ficha $nombre, con la cesta llena, sigue diciendo puesto", 'cupon', bloque($html)['estado']);
}

echo "\nC. Un tercero que pone el cliente: entra sin tocar los otros dos\n";
$htmlC = http($v, ficha(C))['cuerpo'];
$b = bloque($htmlC);
igual('ficha C («lo pone el cliente»): sin poner', ['cupon-sin', 'poner', euros($pC)], [$b['estado'], $b['cupon_accion'], precioGrande($htmlC)]);
$gC12 = grupo($htmlC, 'Saco de 12 Kg');
$j = json_decode(http($v, $aplicarRuta, ['ajax' => 1, 'codigo' => 'TEST-MC-C', 'accion' => 'poner'])['cuerpo'], true);
igual('aplicar C → ok (aún sin descuento: C no está en la cesta)', [true, true, 0.0], [$j['ok'] ?? null, $j['aplicado'] ?? null, (float) ($j['descuento'] ?? -1)]);
$html = http($v, ficha(C))['cuerpo'];
igual('ficha C: puesto, con el precio con cupón', ['cupon', euros(round($pC * 0.88, 2))], [bloque($html)['estado'], precioGrande($html)]);
$r = anadir($v, $tokA, C, C12, $gC12);
igual('añadir C: tres reglas, cada una con lo suyo', ['TEST-MC-A' => round($pA * 2 * 0.1, 2), 'TEST-MC-B' => round($pB * 0.15, 2), 'TEST-MC-C' => round($pC * 0.12, 2)], $r['vales']);

echo "\nD. «Quitar» quita solo ese; no vuelve solo; una visita desde Google lo perdona\n";
$j = json_decode(http($v, $aplicarRuta, ['ajax' => 1, 'codigo' => 'TEST-MC-B', 'accion' => 'quitar'])['cuerpo'], true);
igual('quitar B → ok', [true, false], [$j['ok'] ?? null, $j['aplicado'] ?? null]);
igual('en la cesta quedan A y C', ['TEST-MC-A', 'TEST-MC-C'], reglasEnBd($idCesta1));
$html = http($v, ficha(B))['cuerpo'];
igual('ficha B: sin poner (quitado), al precio de siempre', ['cupon-sin', euros($pB)], [bloque($html)['estado'], precioGrande($html)]);
$r = masUno($v, $tokA, B, B12);
igual('cambiar la cantidad de B no lo vuelve a meter (el cliente lo quitó)', ['TEST-MC-A', 'TEST-MC-C'], array_keys($r['vales']));
$html = http($v, ficha(B) . '?gclid=prueba')['cuerpo'];
igual('una visita nueva desde Google: B vuelve a estar puesto', 'cupon', bloque($html)['estado']);
$pc = paginaCesta($v);
igual('y al ver la cesta, su regla vuelve, con las otras dos', ['TEST-MC-A', 'TEST-MC-B', 'TEST-MC-C'], reglasEnBd($idCesta1));

echo "\nE. La papelera de un vale y escribir el código en la cesta\n";
$idReglaA = (int) $cA->id_cart_rule;
http($v, $cestaRuta . (strpos($cestaRuta, '?') === false ? '?' : '&') . 'deleteDiscount=' . $idReglaA . '&token=' . $tokA);
igual('la papelera de A lo quita y deja B y C', ['TEST-MC-B', 'TEST-MC-C'], reglasEnBd($idCesta1));
$r = masUno($v, $tokA, A, A12);
igual('y tocar la cantidad de A no lo devuelve (la papelera lo quitó, en el servidor, sin JavaScript)', ['TEST-MC-B', 'TEST-MC-C'], array_keys($r['vales']));
$html = http($v, ficha(A))['cuerpo'];
igual('la ficha A lo sabe: sin poner', 'cupon-sin', bloque($html)['estado']);
$r = cestaDe(http($v, $cestaRuta, ['ajax' => 1, 'addDiscount' => 1, 'discount_name' => 'test-mc-a', 'token' => $tokA])['cuerpo']);
igual('escribir el código A en «¿Tienes un cupón?» lo vuelve a poner (con los otros dos)', ['TEST-MC-A', 'TEST-MC-B', 'TEST-MC-C'], array_keys($r['vales']));
$html = http($v, ficha(A))['cuerpo'];
igual('y la ficha A vuelve a decir puesto', 'cupon', bloque($html)['estado']);

echo "\nF. Un robot no lleva ninguno; sin cookie (la pasarela), puesto es puesto\n";
$bot = visitante('bot', UA_BOT);
$html = http($bot, ficha(A) . '?gclid=prueba')['cuerpo'];
igual('Googlebot: la ficha A sin poner y al precio del feed', ['cupon-sin', euros($pA)], [bloque($html)['estado'], precioGrande($html)]);
$html = http($bot, ficha(B))['cuerpo'];
igual('y la B, igual (no se le escribió nada)', 'cupon-sin', bloque($html)['estado']);
$x = cesta();
$ctx->cookie->kpy_cupon = '';
desdeLaFicha($x, A, A12, 1);
desdeLaFicha($x, B, B12, 1);
sincronizar($x);
igual('cesta de la API sin cookie: los dos «para todos» entran solos', ['TEST-MC-A' => round($pA * 0.1, 2), 'TEST-MC-B' => round($pB * 0.15, 2)], foto($x)['reglas']);
igual('la cookie se queda con los dos puestos', ['TEST-MC-A', 'TEST-MC-B'], $modulo->puestos());
$ctx->cookie->kpy_cupon = '!TEST-MC-A';
sincronizar($x);
igual('con A quitado en la cookie, se va A y se queda B', ['TEST-MC-B'], array_keys(foto($x)['reglas']));
$ctx->cookie->kpy_cupon = 'TEST-MC-C,!TEST-MC-A';
sincronizar($x);
igual('C puesto pero sin nada suyo en la cesta: no entra; A sigue fuera; B sigue', ['TEST-MC-B'], array_keys(foto($x)['reglas']));
// La notificación de la pasarela llega sin cookie: el repaso de antes del pedido no mete ningún cupón
// nuevo (A, que el cliente quitó, no vuelve a bajar el total por debajo de lo pagado); B se queda.
$ctx->cookie->kpy_cupon = '';
$modulo->hookActionValidateOrderBefore(['cart' => $x]);
igual('antes del pedido, sin cookie: puesto es puesto (B) y nada nuevo entra (A no vuelve)', ['TEST-MC-B'], array_keys(foto($x)['reglas']));
sincronizar($x);
igual('en un repaso normal sin cookie (un visitante nuevo), lo «para todos» entra solo: A vuelve', ['TEST-MC-A', 'TEST-MC-B'], array_keys(foto($x)['reglas']));
igual('productosConPromo (para «llévalo junto»): los dos productos', [A, B], KpcCarrito::productosConPromo($x, 1));
$ctx->cookie->kpy_cupon = '';

echo "\nG. Un cupón con cupo convive con los demás\n";
$y = cesta();
$ctx->cookie->kpy_cupon = '';
desdeLaFicha($y, A, A12, 1);
desdeLaFicha($y, D, D1, 3);
sincronizar($y);
$f = foto($y);
igual('A con su regla y D con su vale KPYCP (2 de las 3 unidades: su cupo); la regla base de D, fuera', ['KPYCP', 'TEST-MC-A'], array_keys($f['reglas']));
cerca('el vale de D descuenta el 10 % de 2 unidades', round(2 * $pD * 0.1, 2), $f['reglas']['KPYCP'] ?? 0.0);
cerca('y A el 10 % de su saco', round($pA * 0.1, 2), $f['reglas']['TEST-MC-A'] ?? 0.0);
igual('reservadas 2 de D', [(int) $cD->id => [D1 => 2]], KpcCupo::reservasDe((int) $y->id));
$ctx->cookie->kpy_cupon = '!TEST-MC-D';
sincronizar($y);
igual('D quitado: se va su vale y A sigue', ['TEST-MC-A'], array_keys(foto($y)['reglas']));
$ctx->cookie->kpy_cupon = '';

if ($conPedido) {
    echo "\nH. Un pedido de verdad con dos cupones (transferencia, sin email)\n";
    $z = cesta();
    $ctx->cookie->kpy_cupon = '';
    desdeLaFicha($z, A, A12, 1);
    desdeLaFicha($z, B, B12, 1);
    sincronizar($z);
    $total = (float) $z->getOrderTotal(true, Cart::BOTH);
    $pago = Module::getInstanceByName('ps_wirepayment');
    $pago->validateOrder((int) $z->id, (int) Configuration::get('PS_OS_BANKWIRE'), $total, $pago->displayName, null, [], 1, false, $ctx->customer->secure_key);
    $idOrder = (int) Order::getIdByCartId((int) $z->id);
    igual('pedido creado', true, $idOrder > 0);
    echo "  pedido #{$idOrder}\n";
    $vales = [];
    foreach (Db::getInstance()->executeS('SELECT name, value FROM ' . _DB_PREFIX_ . 'order_cart_rule WHERE id_order = ' . $idOrder . ' ORDER BY name') ?: [] as $f) {
        $vales[(string) $f['name']] = round((float) $f['value'], 2);
    }
    igual('el pedido lleva los dos vales, cada uno con lo suyo', ['Prueba cupón Boske' => round($pA * 0.1, 2), 'Prueba cupón Specific' => round($pB * 0.15, 2)], $vales);
    $o = new Order($idOrder);
    cerca('y el total de descuentos del pedido es la suma', round($pA * 0.1, 2) + round($pB * 0.15, 2), (float) $o->total_discounts_tax_incl);
} else {
    echo "\nH. (sin --pedido: no se crea el pedido con dos cupones)\n";
}

echo "\nI. Con la campaña Black Friday encendida: la ficha, los chips, el Special Price solo y la cesta\n";
$sp = promo(['codigo' => 'TEST-MC-SP', 'nombre' => 'Prueba Special Price campaña', 'tipo' => 'special_price', 'modo_precio' => 'pct', 'porcentaje' => 10], [['tipo' => 'combinacion', 'id' => E12]]);
$pE = precio(E, E12);
campana(true);
$w = visitante('w');
$html = http($w, ficha(A))['cuerpo'];
$b = bloque($html);
igual('ficha A en campaña: el bloque lleva la campaña, el título de campaña, el precio con cupón', ['blackfriday', 'Cupón Black −10 % · aplicado', 'cupon', euros(round($pA * 0.9, 2))], [$b['campana'], $b['cupon_titulo'], $b['estado'], precioGrande($html)]);
igual('con las variables de color del cupón en el estilo', true, strpos($b['estilo'], '--kpc-cup-bg:#111111') !== false);
igual('los chips de las tarjetas enteros: «Cupón Black» en el 12 kg y el 3 kg (no «Cupón Black −1»)', ['Cupón Black', 'Cupón Black'], chips($html));
igual('la etiqueta de la foto, la de la campaña', ['Cupón Black −10 %'], flags($html));
$htmlE = http($w, ficha(E))['cuerpo'];
$b = bloque($htmlE);
igual('ficha E (solo Special Price): el bloque sale vacío y escondido, con la campaña y los colores del Special Price', [true, 'blackfriday', true], [strpos($b['clases'], 'is-solo-campana') !== false, $b['campana'], strpos($b['estilo'], '--kpc-esp-bg:#111111;--kpc-esp-fg:#F2C965') !== false]);
igual('el chip «Black Friday» solo en la tarjeta del 12 kg', ['Black Friday'], chips($htmlE));
igual('y sobre la foto, «Black Friday −10 %»', ['Black Friday −10 %'], flags($htmlE));
igual('el precio grande sigue siendo el Special Price (el JSON-LD también)', [euros($pE), $pE], [precioGrande($htmlE), jsonLd($htmlE)]);
$tokW = token($html);
anadir($w, $tokW, A, A12, grupo($html, 'Saco de 12 Kg'));
anadir($w, $tokW, E, E12, grupo($htmlE, 'Saco de 12 Kg'));
$pc = paginaCesta($w);
$cupon = array_values(array_filter($pc['pildoras'], static fn ($p) => $p['tipo'] === 'cupon'))[0] ?? null;
$especial = array_values(array_filter($pc['pildoras'], static fn ($p) => $p['tipo'] === 'especial'))[0] ?? null;
igual('la fila de A en la cesta: píldora de campaña, chip «Cupón Black −10 %», fondo negro y texto coral', [true, 'Cupón Black −10 %', 'background-color:#111111;color:#FF6A5C'], [$cupon['campana'] ?? null, $cupon['chip'] ?? null, $cupon['estilo'] ?? null]);
igual('la fila de E: píldora de campaña «Black Friday −10 %», fondo negro y texto dorado', [true, 'Black Friday −10 %', 'background-color:#111111;color:#F2C965'], [$especial['campana'] ?? null, $especial['chip'] ?? null, $especial['estilo'] ?? null]);
igual('el vale del resumen se llama como la campaña', true, count(array_filter($pc['vales'], static fn ($n) => strpos($n, 'Cupón Black −10 % · TEST-MC-A') === 0)) === 1);
$r = http($w, $modalRuta . (strpos($modalRuta, '?') === false ? '?' : '&') . 'action=add-to-cart&id_product=' . A . '&id_product_attribute=' . A12 . '&id_customization=0');
igual('la ventana «Añadido» lleva la píldora de campaña', [200, true], [$r['codigo'], strpos($r['cuerpo'], 'kpc-lr--campana') !== false && strpos($r['cuerpo'], 'Cupón Black −10 %') !== false]);
// El hook de consulta con `pct`: un Special Price que no es promoción de la landing (cargado por SQL) se nombra igual.
$r = Hook::exec('actionKpyCouponsCampana', ['id_product' => C, 'id_product_attribute' => 0, 'tipos' => ['special_price'], 'pct' => ['special_price' => 12.0]], null, true)['kpypromolanding'] ?? null;
igual('actionKpyCouponsCampana con pct, en un producto sin promoción de precio de la landing: «Black Friday −12 %»', 'Black Friday −12 %', $r['tipos']['special_price']['label'] ?? null);
$r = Hook::exec('actionKpyCouponsCampana', ['id_product' => C, 'id_product_attribute' => 0, 'tipos' => ['special_price'], 'pct' => ['special_price' => 0]], null, true)['kpypromolanding'] ?? null;
igual('y sin %, «Black Friday» a secas', 'Black Friday', $r['tipos']['special_price']['label'] ?? null);
$r = Hook::exec('actionKpyCouponsCampana', ['id_product' => C, 'id_product_attribute' => 0, 'tipos' => ['special_price']], null, true)['kpypromolanding'] ?? null;
igual('sin pct (kpycoupons 3.1), como antes: nada', null, $r);
campana(false);
$html = http($w, ficha(E))['cuerpo'];
igual('campaña apagada: la ficha E vuelve a no pintar nada (ni bloque ni chip)', [null, []], [bloque($html)['estado'], chips($html)]);
igual('sin excepciones del módulo en el registro de la tienda', 0, (int) Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "log WHERE message LIKE 'kpycoupons%' AND date_add > DATE_SUB(NOW(), INTERVAL 10 MINUTE)"));

echo "\nLimpieza\n";
echo $fallos ? "\n$fallos FALLOS\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
