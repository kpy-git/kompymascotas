<?php
/**
 * Pruebas de integración de la **ficha** (kpycoupons 3.0.0: la ficha en los huecos del tema de
 * Julián) en la tienda de pruebas, como la pide el navegador: páginas de verdad por HTTP (curl a la
 * propia tienda, con cookies por visitante) y lo que PrestaShop devuelve en el refresco, con
 * promociones de prueba creadas aquí. Desde /var/www/html/kompy, dentro del contenedor:
 *   cat ficha-pruebas.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php
 *
 * Qué se comprueba (docs/plan-desacoplar-kpycoupons.md, apartados 3 y 8): el bloque bajo el precio y
 * el chip de la tarjeta; el cupón sin poner, puesto (el precio grande baja, el JSON-LD y og:price no),
 * «para todos» (desde la 3.2.0 baja igual), con un robot (nada) y por canal; la 2ª unidad con 1 y 2 unidades; la
 * elección «12 kg» / «12 kg + 3 kg gratis» (el precio habitual, sin amarillo, con nuestro €/kg, la
 * etiqueta de la foto), también **con el saco ya en la cesta con su vale** (la caché estática de
 * `Product::getProductProperties` no se mete en medio) y la ventana «Añadido»; el cupo agotado para
 * esta cesta (precio habitual); los hooks de consulta; el orden de los hooks. Mientras dura apaga las
 * demás promociones (y las vuelve a encender al final, pase lo que pase).
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
$ctx->cookie = new Cookie('kpyficha');
$ctx->currentLocale = $kernel->getContainer()->get('prestashop.core.localization.locale.repository')->getLocale($ctx->language->getLocale());
$modulo = Module::getInstanceByName('kpycoupons');

const S = 8299; const S12 = 92467; const S3 = 92468; const SP2 = 92469;   // Boske salmón: 12 kg, 3 kg, pack 2 × 12
const OTRO = 9026;   // Boske Clinical Diet Hypoallergenic: un producto que no entra en las promociones de prueba
const CLIENTE = 4; const DIRECCION = 69508;
const CODIGOS = ['TEST-FICHA-SP', 'TEST-FICHA-REGALO', 'TEST-FICHA-CUPON', 'TEST-FICHA-2UD'];
const BASE = 'http://host.docker.internal';   // la tienda, desde dentro del contenedor
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
function pvpr(int $id, int $ipa): float
{
    return Tools::ps_round((float) Product::getPriceStatic($id, true, $ipa, 6, null, false, false), 2);
}
function euros(float $x): string
{
    global $ctx;
    return $ctx->currentLocale->formatPrice($x, 'EUR');
}
function numero(float $x): string
{
    return str_replace('.', ',', (string) $x);
}

// ------------------------------------------------------------------ HTTP, como el navegador
function visitante(string $nombre, string $ua = UA_CHROME): array
{
    $jar = sys_get_temp_dir() . '/kpc-ficha-' . $nombre . '-' . getmypid() . '.txt';
    @unlink($jar);
    return ['jar' => $jar, 'ua' => $ua];
}
$tiempos = [];
function http(array $v, string $ruta, ?array $post = null): array
{
    global $tiempos;
    $ch = curl_init(BASE . $ruta);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => ['Host: ' . HOST, 'Accept: text/html,*/*', 'Accept-Language: es-ES,es'],
        CURLOPT_USERAGENT => $v['ua'], CURLOPT_COOKIEJAR => $v['jar'], CURLOPT_COOKIEFILE => $v['jar'],
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $t0 = microtime(true);
    $r = (string) curl_exec($ch);
    $t = microtime(true) - $t0;
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tam = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $tiempos[] = [$post === null ? 'GET' : 'POST', $ruta, round($t, 2)];
    return ['codigo' => $codigo, 'cabeceras' => substr($r, 0, $tam), 'cuerpo' => substr($r, $tam), 't' => $t];
}
function ruta(string $url): string
{
    $p = parse_url($url);
    return ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
}
$fichaRuta = ruta($ctx->link->getProductLink(S));
$cestaRuta = ruta($ctx->link->getPageLink('cart'));
$aplicarRuta = ruta($ctx->link->getModuleLink('kpycoupons', 'aplicar'));
$modalRuta = ruta($ctx->link->getModuleLink('ps_shoppingcart', 'ajax'));
echo "  ficha: $fichaRuta\n  cesta: $cestaRuta\n  aplicar: $aplicarRuta\n";

// ------------------------------------------------------------------ leer la página como el cliente
function precioGrande(string $html): string
{
    return preg_match('/product__current-price[^>]*>\s*([^<]+)/u', $html, $m) ? trim($m[1]) : '';
}
function amarillo(string $html): bool
{
    return (bool) preg_match('/product__current-price kpy-special-price"/', $html);
}
function tachado(string $html): string
{
    return preg_match('/product__price-regular">([^<]+)/', $html, $m) ? trim($m[1]) : '';
}
function bloque(string $html): array
{
    if (!preg_match('/<div class="kpc-ficha[^"]*" data-kpc-ficha data-kpc-estado="([^"]*)" data-kpc-regalo="(\d)"/', $html, $m)) {
        return ['estado' => null, 'regalo' => null];
    }
    $b = ['estado' => $m[1], 'regalo' => (int) $m[2]];
    $b['kg'] = preg_match('/kpc-kg">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['quedan'] = preg_match('/kpc-quedan[^>]*>([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['cupon_titulo'] = preg_match('/kpc-cupon__titulo">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['cupon_sub'] = preg_match('/kpc-cupon__sub">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['cupon_accion'] = preg_match('/data-kpc-accion="([^"]+)"/', $html, $m) ? $m[1] : '';
    $b['segunda_titulo'] = preg_match('/kpc-segunda__titulo">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['segunda_sub'] = preg_match('/kpc-segunda__sub">([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['segunda_pulsable'] = (bool) preg_match('/kpc-segunda is-pulsable/', $html);
    // Las tarjetas de regalo (las monta ficha.js con estos datos) y el campo oculto kpy_regalo.
    $b['tarjetas'] = [];
    if (preg_match('/<script type="application\/json" data-kpc-datos>(.*?)<\/script>/s', $html, $m)) {
        $d = json_decode(html_entity_decode($m[1]), true);
        foreach ((array) ($d['tarjetas'] ?? []) as $tj) {
            $b['tarjetas'][] = $tj['nombre'] . ' · ' . $tj['precio'] . ' · ' . $tj['banda'];
        }
    }
    $b['radio'] = preg_match('/name="kpy_regalo" value="(\d)" data-kpc-regalo-campo/', $html, $m) ? (int) $m[1] : null;
    $b['pill'] = preg_match('/data-kpc-pill>([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['incluye'] = preg_match('/kpc-incluye__texto">\s*<strong>([^<]+)/', $html, $m) ? trim($m[1]) : '';
    $b['nota'] = preg_match('/kpc-nota[^>]*>([^<]+)/', $html, $m) ? trim($m[1]) : '';
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
function ogPrecio(string $html): ?float
{
    return preg_match('/property="product:price:amount" content="([\d.]+)"/', $html, $m) ? (float) $m[1] : null;
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
/** `group[idGrupo] = id_attribute` del formato con ese nombre, como lo manda el formulario del tema. */
function grupo(string $html, string $nombre): array
{
    if (!preg_match('/name="group\[(\d+)\]" value="(\d+)" title="' . preg_quote($nombre, '/') . '"/', $html, $m)) {
        return [];
    }
    return [(int) $m[1] => (int) $m[2]];
}
function refresco(array $v, string $ruta, array $grupo, int $cantidad = 1, ?int $regalo = null): array
{
    $post = ['ajax' => 1, 'action' => 'refresh', 'id_product' => S, 'quantity_wanted' => $cantidad, 'group' => $grupo];
    if ($regalo !== null) {
        $post['kpy_regalo'] = $regalo;
    }
    $r = http($v, $ruta, $post);
    $j = json_decode($r['cuerpo'], true);
    return is_array($j) ? $j : ['error' => substr($r['cuerpo'], 0, 300)];
}

// ------------------------------------------------------------------ fixtures (como cupo-pruebas)
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
function desdeLaFicha(Cart $cart, int $id, int $ipa, int $uds, ?bool $conRegalo): bool
{
    global $ctx;
    $ctx->cart = $cart;
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
}
function cambiar(int $id, array $campos): void
{
    Db::getInstance()->update('kpy_coupon', $campos, 'id_kpy_coupon = ' . $id);
    rehacer();
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
// La campaña de la landing (Black Friday) renombra cupones y etiquetas: apagada mientras dura la prueba.
$campanas = array_map('intval', array_column(Db::getInstance()->executeS('SELECT id_kpy_promo_campana FROM ' . _DB_PREFIX_ . 'kpy_promo_campana WHERE activa = 1') ?: [], 'id_kpy_promo_campana'));
if ($campanas !== []) {
    Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'kpy_promo_campana SET activa = 0 WHERE id_kpy_promo_campana IN (' . implode(',', $campanas) . ')');
    echo '  campañas apagadas mientras tanto: ' . implode(', ', $campanas) . "\n";
}
register_shutdown_function(static function () use ($apagadas, $maxCart, $campanas): void {
    global $cestas;
    limpiarPromos();
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
    // Las cestas de la prueba (las de la API y las de los visitantes de curl, sin cliente).
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
$habitual12 = precio(S, S12);
$pvpr12 = pvpr(S, S12);
$precio3 = precio(S, S3);
echo "  12 kg sin Special Price: {$habitual12} € (PVPR {$pvpr12} €) · 3 kg: {$precio3} €\n";

echo "\nA. Sin promoción: la ficha es la de Julián tal cual\n";
$v = visitante('a');
$r = http($v, $fichaRuta);
igual('la ficha responde 200', 200, $r['codigo']);
$html = $r['cuerpo'];
igual('sin bloque, sin chips', [null, []], [bloque($html)['estado'], chips($html)]);
igual('la hoja y el script de la ficha van en la página (por actionFrontControllerSetMedia)', [true, true], [strpos($html, 'kpycoupons/views/css/ficha.css') !== false, strpos($html, 'kpycoupons/views/js/ficha.js') !== false]);
igual('el precio grande es el de siempre', euros($habitual12), precioGrande($html));
echo '  (el amarillo del tema con el precio de siempre: ' . (amarillo($html) ? 'sí, kpyspecialprices decide por su tabla, no por el precio (9.1 del plan)' : 'no') . ")
";
igual('el JSON-LD y og:price dicen ese precio', [$habitual12, $habitual12], [jsonLd($html), ogPrecio($html)]);
$g12 = grupo($html, 'Saco de 12 Kg');
$g3 = grupo($html, 'Saco de 3 Kg');
$gP = grupo($html, 'Pack 2 x Saco de 12 Kg');
igual('los tres formatos están en el formulario', [1, 1, 1], [count($g12), count($g3), count($gP)]);
$tok = token($html);
igual('con su token', true, strlen($tok) === 32);

echo "\nB. El cupón: sin poner, puesto, para todos, un robot, por canal\n";
$cupon = promo(['codigo' => 'TEST-FICHA-CUPON', 'nombre' => 'Prueba cupón ficha', 'tipo' => 'cupon', 'porcentaje' => 10, 'auto_canal' => 'shopping'],
    [['tipo' => 'combinacion', 'id' => S12], ['tipo' => 'combinacion', 'id' => S3]]);
igual('llega a 2 artículos', 2, (int) $cupon->formatos);
$conCupon12 = round($habitual12 * 0.9, 2);
$v = visitante('b');
$html = http($v, $fichaRuta)['cuerpo'];
$b = bloque($html);
igual('sin poner: la caja lila con «Aplica tu cupón −10 %» y «Aplícalo y págalo a …»', ['cupon-sin', 'Aplica tu cupón −10 %', 'Aplícalo y págalo a ' . euros($conCupon12), 'poner'], [$b['estado'], $b['cupon_titulo'], $b['cupon_sub'], $b['cupon_accion']]);
igual('el precio grande y el JSON-LD, sin cupón', [euros($habitual12), $habitual12], [precioGrande($html), jsonLd($html)]);
igual('el chip «Cupón −10 %» en las tarjetas del 12 kg y del 3 kg (no en el pack)', ['Cupón −10 %', 'Cupón −10 %'], chips($html));
igual('la etiqueta de la foto', ['Código −10 %'], flags($html));
$r = http($v, $aplicarRuta, ['ajax' => 1, 'codigo' => 'TEST-FICHA-CUPON', 'accion' => 'poner']);
$j = json_decode($r['cuerpo'], true);
igual('aplicar → ok, aplicado, 10 %', [true, true, 10.0], [$j['ok'] ?? null, $j['aplicado'] ?? null, (float) ($j['pct'] ?? 0)]);
$html = http($v, $fichaRuta)['cuerpo'];
$b = bloque($html);
igual('puesto: «Cupón −10 % aplicado» · «Ahorras … · se mantiene en la cesta» · Quitar', ['cupon', 'Cupón −10 % aplicado', 'Ahorras ' . euros(round($habitual12 - $conCupon12, 2)) . ' · se mantiene en la cesta', 'quitar'], [$b['estado'], $b['cupon_titulo'], $b['cupon_sub'], $b['cupon_accion']]);
igual('el precio grande baja al precio con cupón, con el PVPR tachado', [euros($conCupon12), euros($pvpr12)], [precioGrande($html), tachado($html)]);
igual('pero el JSON-LD y og:price siguen diciendo el precio del feed (norma de Merchant)', [$habitual12, $habitual12], [jsonLd($html), ogPrecio($html)]);
igual('y nuestro €/kg con el cupón', true, strpos($b['kg'], 'con el cupón') !== false);
$j = refresco($v, $fichaRuta, $g12, 2);
igual('el refresco con 2 unidades mantiene el cupón puesto y el precio', [true, euros($conCupon12)], [strpos($j['product_prices'] ?? '', 'data-kpc-estado="cupon"') !== false, precioGrande($j['product_prices'] ?? '')]);
$j = refresco($v, $fichaRuta, $gP, 1);
igual('en el pack (sin cupón) la nota dice para qué formatos es', true, strpos(bloque($j['product_prices'] ?? '')['nota'], 'El cupón −10 % es para:') === 0);
$r = http($v, $aplicarRuta, ['ajax' => 1, 'codigo' => 'TEST-FICHA-CUPON', 'accion' => 'quitar']);
$html = http($v, $fichaRuta)['cuerpo'];
igual('quitado: vuelve el precio de siempre y la caja a «poner»', [euros($habitual12), 'poner'], [precioGrande($html), bloque($html)['cupon_accion']]);
// Un robot con el enlace de Google Ads: nunca ve el cupón aplicado.
$bot = visitante('bot', UA_BOT);
$html = http($bot, $fichaRuta . '?gclid=prueba')['cuerpo'];
igual('Googlebot con ?gclid: el cupón sin poner y el precio del feed', ['cupon-sin', euros($habitual12), $habitual12], [bloque($html)['estado'], precioGrande($html), jsonLd($html)]);
$html = http($bot, $fichaRuta)['cuerpo'];
igual('y en la siguiente petición, igual (no se le escribió la cookie)', 'cupon-sin', bloque($html)['estado']);
// Un cliente desde Google Ads: el cupón entra solo por el canal.
$ads = visitante('ads');
$html = http($ads, $fichaRuta . '?gclid=prueba')['cuerpo'];
igual('un cliente con ?gclid: el cupón entra solo y el precio grande ya lo lleva', ['cupon', euros($conCupon12), $habitual12], [bloque($html)['estado'], precioGrande($html), jsonLd($html)]);
$html = http($ads, $fichaRuta)['cuerpo'];
igual('y se queda puesto en la siguiente página (cookie)', 'cupon', bloque($html)['estado']);
// «Para todos» (auto_web): aplicado, y desde la 3.2.0 el precio grande baja como con el de canal
// (el JSON-LD y og:price siguen con el precio del feed; el robot no lo ve).
cambiar((int) $cupon->id, ['auto_web' => 1, 'auto_canal' => '']);
$todos = visitante('todos');
$html = http($todos, $fichaRuta)['cuerpo'];
$b = bloque($html);
igual('cupón «para todos»: aplicado, el precio grande baja y el JSON-LD no', ['cupon', euros($conCupon12), 'Ahorras ' . euros(round($habitual12 - $conCupon12, 2)) . ' · se mantiene en la cesta', $habitual12], [$b['estado'], precioGrande($html), $b['cupon_sub'], jsonLd($html)]);
$html = http($bot, $fichaRuta)['cuerpo'];
igual('y para el robot, ni eso', ['cupon-sin', euros($habitual12)], [bloque($html)['estado'], precioGrande($html)]);
// «Para todos» como lo define Julián (23/10): lo ve cualquiera en la ficha de sus productos aunque
// «se enseña» esté desmarcado, venga de donde venga; y nunca en un producto que no entra.
cambiar((int) $cupon->id, ['en_ficha' => 0]);
$html = http(visitante('todos2'), $fichaRuta)['cuerpo'];
igual('«para todos» con «se enseña» desmarcado: la caja del cupón sigue en la ficha', 'cupon', bloque($html)['estado']);
$html = http($todos, ruta($ctx->link->getProductLink(OTRO)))['cuerpo'];
igual('y en un producto que no entra (Boske Hypoallergenic), ni caja ni nota', [null, false], [bloque($html)['estado'], strpos($html, 'kpc-cupon') !== false]);
$cupon->delete();
rehacer();

echo "\nC. La 2ª unidad en el 3 kg: con 1 anima (y se pulsa), con 2 lo dice\n";
// Diagnóstico aparte: una 2ª unidad con el pack 2 × 12 kg como ámbito no le llega en pruebas; se imprime lo que contesta el alcance.
$diag = promo(['codigo' => 'TEST-FICHA-2UD', 'nombre' => 'Diagnóstico pack', 'tipo' => 'segunda_unidad', 'porcentaje' => 30, 'packs' => '', 'excluir_especial' => 0], [['tipo' => 'combinacion', 'id' => SP2]]);
$al = Hook::exec('actionKpyCouponsAlcance', ['id' => (int) $diag->id, 'id_shop' => 1], null, true)['kpycoupons'] ?? null;
echo '  (pack 2 × 12 kg como ámbito de una 2ª unidad: formatos=' . (int) $diag->formatos . ' entran=' . json_encode($al['entran'] ?? null) . ' especial=' . json_encode($al['especial'] ?? null) . ' aviso=«' . $diag->aviso . "»)\n";
$diag->delete();
rehacer();
$seg = promo(['codigo' => 'TEST-FICHA-2UD', 'nombre' => 'Prueba 2ª unidad ficha', 'tipo' => 'segunda_unidad', 'porcentaje' => 30], [['tipo' => 'combinacion', 'id' => S3]]);
igual('la 2ª unidad llega al 3 kg', 1, (int) $seg->formatos);
$v = visitante('c');
$html = http($v, $fichaRuta)['cuerpo'];
$j = refresco($v, $fichaRuta, $g3, 1);
$b = bloque($j['product_prices'] ?? '');
$pP = precio(S, S3);
igual('con 1 unidad: «2ª unidad al −30 %», «Añade 1 ud. más: te ahorras …», pulsable', ['segunda', '2ª unidad al −30 %', true, true], [$b['estado'], $b['segunda_titulo'], strpos($b['segunda_sub'], 'Añade 1 ud. más: te ahorras ' . euros(round($pP * 0.3, 2))) === 0, $b['segunda_pulsable']]);
$j = refresco($v, $fichaRuta, $g3, 2);
$b = bloque($j['product_prices'] ?? '');
igual('con 2: «Llevas 2 ud.: te ahorras …», ya no se pulsa', [true, false], [strpos($b['segunda_sub'], 'Llevas 2 ud.: te ahorras ' . euros(round($pP * 0.3, 2))) === 0, $b['segunda_pulsable']]);
igual('el precio grande no cambia (la 2ª unidad la hace la cesta)', euros($pP), precioGrande($j['product_prices'] ?? ''));
igual('el chip «2ª ud. −30 %» solo en la tarjeta del 3 kg', ['2ª ud. −30 %'], chips($html));
$seg->delete();
rehacer();

echo "\nD. Special Price + regalo: «12 kg» / «12 kg + 3 kg gratis»\n";
$sp = promo(['codigo' => 'TEST-FICHA-SP', 'nombre' => 'Prueba Special Price ficha', 'tipo' => 'special_price', 'modo_precio' => 'pct', 'porcentaje' => 10],
    [['tipo' => 'combinacion', 'id' => S12]]);
$p12 = precio(S, S12);
cerca('el 12 kg baja un 10 %', round($habitual12 * 0.9, 2), $p12);
$reg = promo(['codigo' => 'TEST-FICHA-REGALO', 'nombre' => 'Prueba regalo ficha', 'tipo' => 'regalo', 'regalo_modo' => 'pequeno', 'regalo_por' => 'unidad', 'regalo_tope' => 0, 'regalo_packs' => 0],
    [['tipo' => 'producto', 'id' => S]]);
igual('la pareja 12 kg → 3 kg', ['id' => S, 'ipa' => S3], KpcRegalo::guardadas((int) $reg->id)[S12]['r'] ?? null);
$pvprCon = round(max($pvpr12, $habitual12) + pvpr(S, S3), 2);
$v = visitante('d');
$html = http($v, $fichaRuta)['cuerpo'];
$b = bloque($html);
igual('al cargar: la tarjeta de regalo en los datos para ficha.js, el campo oculto a 0 y nada de «Incluye…»', ['regalo-sin', 0, 0, ['12 kg + 3 kg gratis · ' . euros($habitual12) . ' · Kilos gratis'], '', ''], [$b['estado'], $b['regalo'], $b['radio'], $b['tarjetas'], $b['pill'], $b['incluye']]);
igual('el formato de siempre no lleva el chip «+ 3 kg gratis» (lo lleva su tarjeta aparte): queda el tag del tema', [], chips($html));
igual('el precio grande es el Special Price, en amarillo, con el bloque «Precio habitual» del tema', [euros($p12), true, true], [precioGrande($html), amarillo($html), strpos($html, 'kpy-special-price-block') !== false]);
igual('el JSON-LD dice el Special Price (el del feed)', $p12, jsonLd($html));
igual('sobre la foto, «Special Price» y «+ 3 kg gratis»', ['Special Price', '+ 3 kg gratis'], flags($html));
$j = refresco($v, $fichaRuta, $g12, 2, 1);
$p = $j['product_prices'] ?? '';
$b = bloque($p);
igual('elegida la opción con regalo (refresco): el precio habitual, el PVPR de los dos tachado, sin amarillo', [euros($habitual12), euros($pvprCon), false], [precioGrande($p), tachado($p), amarillo($p)]);
igual('el bloque lo dice: campo oculto a 1, pastilla «Kilos gratis», «Incluye 3 kg gratis (…), a 0 € en la cesta», nuestro €/kg con los 15 kg', ['regalo', 1, 1, 'Kilos gratis', 'Incluye 3 kg gratis (' . euros(precio(S, S3)) . '), a 0 € en la cesta', true], [$b['estado'], $b['regalo'], $b['radio'], $b['pill'], $b['incluye'], strpos($b['kg'], 'con los 15 kg') !== false]);
igual('el bloque «Precio habitual» de kpyspecialprices se va', false, strpos($p, 'kpy-special-price-block') !== false);
igual('sobre la foto solo «+ 3 kg gratis»', ['+ 3 kg gratis'], flags($j['product_flags'] ?? ''));
$j = refresco($v, $fichaRuta, $g12, 2, 0);
igual('y con la de siempre otra vez, el Special Price vuelve', [euros($p12), true], [precioGrande($j['product_prices'] ?? ''), amarillo($j['product_prices'] ?? '')]);

echo "\nE. Con el saco ya en la cesta (con su vale de regalo), la ficha sigue diciendo la verdad\n";
$r = http($v, $cestaRuta, ['ajax' => 1, 'add' => 1, 'action' => 'update', 'id_product' => S, 'id_product_attribute' => S12, 'qty' => 1, 'token' => $tok, 'group' => $g12, 'kpy_regalo' => 1]);
$j = json_decode($r['cuerpo'], true);
igual('añadir desde la ficha con regalo responde bien', true, !empty($j['success']) || (isset($j['cart']) && empty($j['errors'])));
$lineas = [];
foreach ((array) ($j['cart']['products'] ?? []) as $l) {
    $lineas[(int) $l['id_product_attribute']] = ['n' => (int) $l['quantity'], 'precio' => (string) $l['price'], 'regalo' => !empty($l['kpy_is_gift'])];
}
igual('la cesta del visitante lleva el 12 kg al precio habitual y el 3 kg a 0 € (el regalo)', [['n' => 1, 'precio' => euros($habitual12)], ['n' => 1, 'precio' => euros(0.0)]], [isset($lineas[S12]) ? ['n' => $lineas[S12]['n'], 'precio' => $lineas[S12]['precio']] : null, isset($lineas[S3]) ? ['n' => $lineas[S3]['n'], 'precio' => $lineas[S3]['precio']] : null]);
$html = http($v, $fichaRuta)['cuerpo'];
igual('la ficha, al volver: el Special Price de siempre (opción 0), amarillo, JSON-LD con el Special Price', [euros($p12), true, $p12], [precioGrande($html), amarillo($html), jsonLd($html)]);
$j = refresco($v, $fichaRuta, $g12, 1, 1);
$p = $j['product_prices'] ?? '';
igual('y al elegir el regalo con el saco ya en la cesta, el precio habitual sale igual (la caché estática no se mete en medio)', [euros($habitual12), false, 'regalo'], [precioGrande($p), amarillo($p), bloque($p)['estado']]);
$r = http($v, $modalRuta . (strpos($modalRuta, '?') === false ? '?' : '&') . 'action=add-to-cart&id_product=' . S . '&id_product_attribute=' . S12 . '&id_customization=0');
igual('la ventana «Añadido» lleva la promoción de la línea («Kilos gratis»)', [200, true, true], [$r['codigo'], strpos($r['cuerpo'], 'kpc-modal') !== false, strpos($r['cuerpo'], 'Kilos gratis') !== false]);
if (strpos($r['cuerpo'], 'kpc-modal') === false) {
    $pos = strpos($r['cuerpo'], 'modal-footer');
    echo '  ventana (' . strlen($r['cuerpo']) . " bytes), desde modal-footer: " . preg_replace('/\s+/', ' ', substr($r['cuerpo'], $pos === false ? 0 : $pos, 600)) . "
";
    foreach (Db::getInstance()->executeS("SELECT LEFT(message, 300) m FROM " . _DB_PREFIX_ . "log WHERE message LIKE 'kpycoupons, la ventana%' ORDER BY id_log DESC LIMIT 2") ?: [] as $l) { echo '  log: ' . $l['m'] . "
"; }
}

echo "\nF. El cupo agotado para esta cesta: precio habitual y sin amarillo\n";
cambiar((int) $sp->id, ['cupo' => 2, 'cupo_ficha' => 1]);
$w = visitante('f');
$html = http($w, $fichaRuta)['cuerpo'];
$b = bloque($html);
igual('con cupo: «¡Últimas 2 unidades en Special Price!» y el Special Price', ['¡Últimas 2 unidades en Special Price!', euros($p12), true], [$b['quedan'], precioGrande($html), amarillo($html)]);
$x = cesta();
igual('otra cesta (de la API) se lleva las 2', true, desdeLaFicha($x, S, S12, 2, false));
KpcCupo::olvidarCache();
igual('para los demás quedan 0', 0, KpcCupo::quedanPara((int) $sp->id, 0));
$html = http($w, $fichaRuta)['cuerpo'];
$b = bloque($html);
igual('la ficha del visitante: agotado, el precio grande al habitual, sin amarillo, sin bloque «Precio habitual», con nuestro €/kg', ['Se han agotado las unidades en Special Price: a precio habitual', 'agotado', euros($habitual12), false, false, true],
    [$b['quedan'], $b['estado'], precioGrande($html), amarillo($html), strpos($html, 'kpy-special-price-block') !== false, strpos($b['kg'], 'a precio habitual') !== false]);
igual('sobre la foto, sin «Special Price» (queda el regalo)', ['+ 3 kg gratis'], flags($html));
$ctx->cart = $x;
$x->deleteProduct(S, S12);
KpcCupo::olvidarCache();
$html = http($w, $fichaRuta)['cuerpo'];
igual('al soltarlas la otra cesta, vuelven las 2', '¡Últimas 2 unidades en Special Price!', bloque($html)['quedan']);

echo "\nG. Los hooks de consulta, el orden de los hooks y lo que se publica para el tema\n";
$r = Hook::exec('actionKpyCouponsAlcance', ['id' => (int) $sp->id, 'id_shop' => 1], null, true);
$a = $r['kpycoupons'] ?? null;
igual('actionKpyCouponsAlcance contesta el alcance de la promoción y su fila pública', [1, [S12 => S], 'special_price', 'viva', 'TEST-FICHA-SP'], [$a['version'] ?? null, array_map('intval', (array) ($a['entran'] ?? [])), $a['promocion']['tipo'] ?? null, $a['promocion']['estado'] ?? null, $a['promocion']['codigo'] ?? null]);
igual('con un id que no existe, nada', null, Hook::exec('actionKpyCouponsAlcance', ['id' => 999999, 'id_shop' => 1], null, true)['kpycoupons'] ?? null);
$r = Hook::exec('actionKpyCouponsPromociones', ['id_shop' => 1], null, true);
$todas = $r['kpycoupons'] ?? [];
$mia = $todas[(int) $reg->id] ?? null;
igual('actionKpyCouponsPromociones trae el regalo con su estado, clase, items y parejas', ['regalo', 'viva', 'kilos', [S12 => S], [['clave' => S12, 'id_product' => S, 'r_id_product' => S, 'r_ipa' => S3, 'n' => 1]]], [$mia['tipo'] ?? null, $mia['estado'] ?? null, $mia['regalo_clase'] ?? null, $mia['items'] ?? null, $mia['parejas'] ?? null]);
igual('y las apagadas, como apagadas', true, count(array_filter($todas, static fn ($p) => $p['estado'] === 'apagada')) >= count($apagadas));
igual('el orden de los hooks está como debe', [], $modulo->posicionesMal());
$attr12 = array_values($g12)[0];
KpcFicha::olvidar();
$_GET['id_product'] = (string) S;
$tema = KpcFicha::de($modulo, S)->paraTema();
$tarjetas = KpcFicha::de($modulo, S)->tarjetas();
igual('tarjetas(): la del 12 kg con su grupo y atributo', [1, S12, $attr12, array_key_first($g12)], [count($tarjetas), $tarjetas[0]['ipa'] ?? null, $tarjetas[0]['attr'] ?? null, $tarjetas[0]['grupo'] ?? null]);
echo '  deFormato(12 kg): ' . json_encode(KpcFlags::deFormato(S, S12, $modulo), JSON_UNESCAPED_UNICODE) . ' · chip: ' . json_encode(KpcFicha::de($modulo, S)->chip($attr12 ?? 0), JSON_UNESCAPED_UNICODE) . "
";
unset($_GET['id_product']);
igual('$modules.kpycoupons: el formato elegido; el 12 kg sin chip (su regalo va en tarjeta aparte)', [S12, 'regalo-sin', ''], [$tema['id_product_attribute'], $tema['estado'], $tema['formatos'][$attr12]['tipo'] ?? null]);
igual('las cuatro páginas de la ficha sin excepciones en el registro de la tienda', 0, (int) Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "log WHERE message LIKE 'kpycoupons, %ficha%' AND date_add > DATE_SUB(NOW(), INTERVAL 10 MINUTE)"));

echo "\nTiempos (s): ";
$get = array_filter($tiempos, static fn ($t) => $t[0] === 'GET' && strpos($t[1], 'ajax') === false && strpos($t[1], 'aplicar') === false);
$post = array_filter($tiempos, static fn ($t) => $t[0] === 'POST' && $t[1] === $GLOBALS['fichaRuta']);
echo 'ficha GET media ' . round(array_sum(array_column($get, 2)) / max(1, count($get)), 2) . ' (' . count($get) . ') · refresco POST media ' . round(array_sum(array_column($post, 2)) / max(1, count($post)), 2) . ' (' . count($post) . ")\n";

echo "\nLimpieza\n";
echo $fallos ? "\n$fallos FALLOS\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
