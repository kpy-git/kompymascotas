<?php
/**
 * Pruebas de integración del «regalo con la compra», kpycoupons 2.1.0 (la versión con regalo es una
 * tarjeta más de la ficha que elige el cliente, y el saco grande va a su precio de siempre), en la
 * tienda de pruebas, por consola dentro del contenedor (desde /var/www/html/kompy):
 *   cat regalo-pruebas-21.php | docker exec -i -u www-data -w /var/www/html/kompy php85 php -- [--pedido]
 *
 * Crea promociones de prueba (REGALO-TEST-BOSKE, TESTREG10, 2UD-TEST-3KG), cestas del cliente de
 * desarrollo (id 4) y, con --pedido, un pedido de verdad (transferencia, sin email) y su «pedir de
 * nuevo». Mientras dura, apaga las demás promociones de regalo (y las vuelve a encender al final,
 * pase lo que pase). Al final borra las promociones y las cestas (el pedido se queda, se dice su id).
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
$ctx->cookie = new Cookie('kpyregalo');
$modulo = Module::getInstanceByName('kpycoupons');
/** Lo que la ficha diría de un formato ahora mismo (3.0.0: `KpcFicha`, en vez del hook de kpyproductbuy). */
function fichaEstado(int $ipa): array
{
    global $modulo;
    KpcFicha::olvidar();

    return KpcFicha::de($modulo, S)->estado($ipa);
}

const S = 8299; const S12 = 92467; const S3 = 92468; const SP2 = 92469;   // Boske salmón: 12 kg, 3 kg, pack 2 × 12
const G = 9208; const G10 = 94016; const G25 = 94015;                     // Boske gato: 10 kg, 2,5 kg
const LATAS = 8473; const LATAS6 = 92791;                                 // Boske Clinical GI: pack 6 latas
const CLIENTE = 4; const DIRECCION = 69508; const MARCA_BOSKE = 178;

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
/** El precio del catálogo (con su Special Price si lo tiene): lo que ve la ficha, sin cesta. */
function precio(int $id, int $ipa): float
{
    return Tools::ps_round((float) Product::getPriceStatic($id, true, $ipa, 6), 2);
}
/** El «Precio habitual» de la ficha: PVPR × (1 − old_discount) si hay Special Price en vigor; si no, el precio. */
function habitual(int $id, int $ipa): float
{
    $old = Db::getInstance()->getValue('SELECT old_discount FROM ' . _DB_PREFIX_ . 'kpy_special_price WHERE id_product = ' . $id . ' AND id_product_attribute = ' . $ipa . ' AND id_shop = 1 AND NOW() BETWEEN date_from AND expire ORDER BY date_from DESC');
    if ($old === false || $old === null) {
        return precio($id, $ipa);
    }
    $pvpr = (float) Product::getPriceStatic($id, true, $ipa, 6, null, false, false);

    return round($pvpr * (1 - (float) $old), 2);
}
/** El precio de la línea en la cesta (con el precio de la versión con regalo, si lo lleva). */
function precioLinea(Cart $cart, int $id, int $ipa): float
{
    foreach ($cart->getProducts(true) as $l) {
        if ((int) $l['id_product'] === $id && (int) $l['id_product_attribute'] === $ipa) {
            return Tools::ps_round((float) $l['price_wt'], 2);
        }
    }

    return 0.0;
}
/** Los precios de cesta (specific_price.id_cart) de un artículo en una cesta. */
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
function foto(Cart $cart): array
{
    $lineas = [];
    foreach ($cart->getProducts(true) as $l) {
        $lineas[$l['id_product'] . '-' . $l['id_product_attribute']] = (int) $l['cart_quantity'];
    }
    ksort($lineas);
    $reglas = [];
    $otras = [];
    foreach ($cart->getCartRules() as $cr) {
        $codigo = (string) $cr['code'];
        if (strpos($codigo, 'KPY') === 0 || strpos($codigo, 'TESTREG') === 0) {
            $reglas[$codigo] = round((float) $cr['value_real'], 2);
        } else {
            $otras[$codigo] = round((float) $cr['value_real'], 2);
        }
    }
    ksort($reglas);
    $regalos = [];
    foreach (KpcRegalo::filasCesta((int) $cart->id) as $f) {
        $k = $f['r_id_product'] . '-' . $f['r_ipa'];
        $regalos[$k] = ($regalos[$k] ?? 0) + (int) $f['cantidad'];
    }
    ksort($regalos);
    return [
        'lineas' => $lineas,
        'regalos' => $regalos,
        'reglas' => $reglas,
        'otras' => $otras,
        'elegidas' => array_keys(KpcRegalo::elegidasDe((int) $cart->id)),
        'productos' => round((float) $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS), 2),
        'descuentos' => round((float) $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS), 2),
    ];
}
function reglaRegalo(Cart $cart, int $idProducto): string
{
    return KpcRegalo::codigo($cart, $idProducto);
}
function sincronizar(Cart $cart): void
{
    global $modulo;
    $modulo->sincronizarCesta($cart);
}
/** El grupo y el atributo de un formato, como los manda el formulario de la ficha (`group[…]`). */
function grupoDe(int $ipa): array
{
    $f = Db::getInstance()->getRow('SELECT a.id_attribute_group, pac.id_attribute FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
        INNER JOIN ' . _DB_PREFIX_ . 'attribute a ON a.id_attribute = pac.id_attribute WHERE pac.id_product_attribute = ' . $ipa);

    return [(int) $f['id_attribute_group'] => (int) $f['id_attribute']];
}
/** Lo que hace la ficha al pulsar «Añadir a la cesta»: el formulario con `group[…]` y `kpy_regalo`. */
function desdeLaFicha(Cart $cart, int $id, int $ipa, int $uds, ?bool $conRegalo): bool
{
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
/** La tarjeta con regalo, ya en la cesta (lo mismo que deja la ficha). */
function conRegalo(Cart $cart, int $id, int $ipa): void
{
    KpcRegalo::elegir((int) $cart->id, KpcAlcance::clave($id, $ipa), true);
    sincronizar($cart);
}
function promo(array $campos): KpcCupon
{
    $c = new KpcCupon();
    foreach ($campos as $k => $v) {
        $c->{$k} = $v;
    }
    $c->id_shop = 1;
    if (!$c->add()) {
        throw new RuntimeException('no se ha podido crear la promoción ' . $campos['codigo']);
    }
    return $c;
}
function limpiarPromos(): void
{
    foreach (Db::getInstance()->executeS("SELECT id_kpy_coupon FROM " . _DB_PREFIX_ . "kpy_coupon WHERE codigo IN ('REGALO-TEST-BOSKE', 'TESTREG10', '2UD-TEST-3KG')") ?: [] as $f) {
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

echo "Preparación\n";
$ctx->cart = cesta();
limpiarPromos();
// Las demás promociones de regalo (la de muestra de Rafa) se apagan mientras dura la prueba y se
// vuelven a encender al final, pase lo que pase.
$apagadas = array_map('intval', array_column(Db::getInstance()->executeS("SELECT id_kpy_coupon FROM " . _DB_PREFIX_ . "kpy_coupon WHERE tipo = 'regalo' AND activo = 1") ?: [], 'id_kpy_coupon'));
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
$r1 = promo([
    'codigo' => 'REGALO-TEST-BOSKE', 'nombre' => 'Prueba regalo Boske', 'tipo' => 'regalo', 'porcentaje' => 0, 'activo' => 1,
    'fecha_desde' => date('Y-m-d H:i:00', time() - 300), 'fecha_hasta' => date('Y-m-d 23:59:59', strtotime('+7 days')),
    'en_ficha' => 1, 'excluir_especial' => 0, 'incluir_packs' => 0, 'packs' => '1', 'sin_outlet' => 1, 'en_merchant' => 1,
    'regalo_modo' => 'pequeno', 'regalo_por' => 'unidad', 'regalo_tope' => 0, 'regalo_packs' => 0, 'regalo_mismo' => '', 'regalo_reserva' => '', 'regalo_parejas' => '', 'regalo_corto' => '',
]);
$r1->guardarAmbitos([['tipo' => 'marca', 'id' => MARCA_BOSKE]]);
rehacer();
$r1 = new KpcCupon((int) $r1->id);
$parejas = KpcRegalo::guardadas((int) $r1->id);
echo "  la promoción llega a {$r1->formatos} artículos; aviso: «{$r1->aviso}»\n";
igual('12 kg salmón → 3 kg salmón', ['id' => S, 'ipa' => S3], $parejas[S12]['r'] ?? null);
igual('10 kg gato → 2,5 kg gato', ['id' => G, 'ipa' => G25], $parejas[G10]['r'] ?? null);
igual('el 3 kg no dispara nada (sin pequeño, sin reserva)', false, isset($parejas[S3]));
igual('el pack 2 × 12 no dispara (los packs no regalan)', false, isset($parejas[SP2]));
$p12 = precio(S, S12);
$h12 = habitual(S, S12);
$p3 = precio(S, S3);
$pG10 = precio(G, G10);
$hG10 = habitual(G, G10);
$pG25 = precio(G, G25);
echo "  precios: 12 kg {$p12} € (habitual {$h12} €) · 3 kg {$p3} € · gato 10 kg {$pG10} € (habitual {$hG10} €)\n";
igual('el 12 kg tiene Special Price (hace falta para probar el precio habitual)', true, $h12 > $p12 + 0.5);

echo "\nA. La tarjeta de siempre no regala; la tarjeta con regalo sí, al precio habitual\n";
$cart = cesta();
igual('añadir desde la ficha la tarjeta «12 kg» (kpy_regalo=0)', true, desdeLaFicha($cart, S, S12, 1, false));
$f = foto($cart);
igual('solo el 12 kg, sin regalo ni elección', [[S . '-' . S12 => 1], [], []], [$f['lineas'], $f['reglas'], $f['elegidas']]);
cerca('al precio con Special Price', $p12, precioLinea($cart, S, S12));
igual('añadir desde la ficha la tarjeta «12 kg + 3 kg gratis» (group[…] y kpy_regalo=1)', true, desdeLaFicha($cart, S, S12, 1, true));
$f = foto($cart);
igual('la línea queda elegida con regalo, una unidad', [[S12], 1], [$f['elegidas'], (int) (KpcRegalo::elegidasDe((int) $cart->id)[S12]['unidades'] ?? 0)]);
igual('líneas: dos 12 kg (uno con regalo, otro de la tarjeta de siempre) y un 3 kg', [S . '-' . S12 => 2, S . '-' . S3 => 1], $f['lineas']);
igual('el 3 kg está apuntado como regalo', [S . '-' . S3 => 1], $f['regalos']);
cerca('la regla del regalo descuenta un 3 kg', $p3, $f['reglas'][reglaRegalo($cart, S)] ?? 0.0);
cerca('la línea del 12 kg va a su precio habitual (el de la versión con regalo)', $h12, precioLinea($cart, S, S12));
igual('con un precio de cesta, uno', 1, preciosCesta($cart, S, S12));
cerca('y el vale «Special Price» devuelve al de la tarjeta de siempre su rebaja', round($h12 - $p12, 2), $f['reglas'][KpcRegalo::codigoEspecial($cart, S)] ?? 0.0);
cerca('productos − descuentos = un 12 kg al habitual + uno a su Special Price', round($h12 + $p12, 2), $f['productos'] - $f['descuentos']);
igual('sin otros vales en medio', [], $f['otras']);
cerca('la ficha sigue viendo el Special Price', $p12, precio(S, S12));
igual('una petición sin kpy_regalo («pedir de nuevo», otro módulo) no cambia la elección', true, desdeLaFicha($cart, S, S12, 1, null));
$f = foto($cart);
igual('tres 12 kg, uno con regalo: un 3 kg', [[S . '-' . S12 => 3, S . '-' . S3 => 1], [S12]], [$f['lineas'], $f['elegidas']]);
cerca('y el vale «Special Price» de los dos de la tarjeta de siempre', round(2 * ($h12 - $p12), 2), $f['reglas'][KpcRegalo::codigoEspecial($cart, S)] ?? 0.0);
igual('bajar a uno con el «−» de la cesta', true, $cart->updateQty(2, S, S12, false, 'down'));
$f = foto($cart);
igual('líneas: un 12 kg y un 3 kg', [S . '-' . S12 => 1, S . '-' . S3 => 1], $f['lineas']);
cerca('la regla, de un 3 kg', $p3, $f['reglas'][reglaRegalo($cart, S)] ?? 0.0);

echo "\nB. Añadir la tarjeta de siempre a una línea con regalo: se suma aparte, el regalo se queda (2.2.0)\n";
igual('añadir desde la ficha la tarjeta «12 kg» (kpy_regalo=0)', true, desdeLaFicha($cart, S, S12, 1, false));
$f = foto($cart);
igual('dos 12 kg (uno con regalo) y su 3 kg regalado', [[S . '-' . S12 => 2], [S . '-' . S3 => 1], [S12]], [array_intersect_key($f['lineas'], [S . '-' . S12 => 1]), $f['regalos'], $f['elegidas']]);
cerca('el vale «Special Price» del de la tarjeta de siempre', round($h12 - $p12, 2), $f['reglas'][KpcRegalo::codigoEspecial($cart, S)] ?? 0.0);
cerca('productos − descuentos = un 12 kg al habitual + uno a su Special Price', round($h12 + $p12, 2), $f['productos'] - $f['descuentos']);
igual('y otra vez con regalo', true, desdeLaFicha($cart, S, S12, 1, true));
$f = foto($cart);
igual('tres 12 kg, dos con regalo: dos 3 kg', [S . '-' . S12 => 3, S . '-' . S3 => 2], $f['lineas']);
$cart->updateQty(2, S, S12, false, 'down');

echo "\nC. El cliente borra la línea del regalo: vuelve (el saco sigue con regalo)\n";
$cart->deleteProduct(S, S3);
$f = foto($cart);
igual('líneas', [S . '-' . S12 => 1, S . '-' . S3 => 1], $f['lineas']);
igual('regalos', [S . '-' . S3 => 1], $f['regalos']);

echo "\nD. Compra además un 3 kg suelto desde su ficha: paga uno, le regalan otro\n";
igual('añadir un 3 kg (kpy_regalo=0: su tarjeta de siempre)', true, desdeLaFicha($cart, S, S3, 1, false));
$f = foto($cart);
igual('líneas', [S . '-' . S12 => 1, S . '-' . S3 => 2], $f['lineas']);
igual('un regalo y la elección del 12 kg intacta', [[S . '-' . S3 => 1], [S12]], [$f['regalos'], $f['elegidas']]);
cerca('la regla descuenta solo uno', $p3, $f['reglas'][reglaRegalo($cart, S)] ?? 0.0);
cerca('productos − descuentos = 12 kg habitual + un 3 kg', round($h12 + $p3, 2), $f['productos'] - $f['descuentos']);

echo "\nE. La papelera del vale «Regalo» (por el hook de la tienda, como en la cesta): el 12 kg vuelve al Special Price\n";
$idRegla = (int) CartRule::getIdByCode(reglaRegalo($cart, S));
$ctx->controller = new class() {
    public $php_self = 'cart';
};
$_GET = ['deleteDiscount' => (string) $idRegla, 'token' => Tools::getToken(false)];
Hook::exec('actionFrontControllerInitAfter');
$_GET = [];
$ctx->controller = null;
$f = foto($cart);
igual('ya no está elegida', [], $f['elegidas']);
igual('líneas: el 12 kg y el 3 kg comprado', [S . '-' . S12 => 1, S . '-' . S3 => 1], $f['lineas']);
igual('sin regalos ni reglas', [[], []], [$f['regalos'], $f['reglas']]);
igual('la regla se ha borrado', 0, (int) CartRule::getIdByCode(reglaRegalo($cart, S)));
cerca('el 12 kg vuelve a su Special Price', $p12, precioLinea($cart, S, S12));
igual('y su precio de cesta se ha ido', 0, preciosCesta($cart, S, S12));
$ctx->controller = new class() {
    public $php_self = 'cart';
};
conRegalo($cart, S, S12);
$idRegla = (int) CartRule::getIdByCode(reglaRegalo($cart, S));
$_GET = ['deleteDiscount' => (string) $idRegla, 'token' => 'otro'];
Hook::exec('actionFrontControllerInitAfter');
$_GET = [];
$ctx->controller = null;
igual('con un token que no es el suyo, nada', [S12], foto($cart)['elegidas']);
igual('un vale que no es de regalo no hace nada', false, KpcRegalo::quitarPorRegla($cart, 999999999));

echo "\nF. Quita el saco: el regalo se va, su 3 kg comprado se queda, la elección y el precio de cesta también se van\n";
$codigoRegla = reglaRegalo($cart, S);
$cart->deleteProduct(S, S12);
$f = foto($cart);
igual('líneas', [S . '-' . S3 => 1], $f['lineas']);
igual('sin regalos, reglas ni elección', [[], [], []], [$f['regalos'], $f['reglas'], $f['elegidas']]);
igual('la regla se ha borrado', 0, (int) CartRule::getIdByCode($codigoRegla));
igual('sin precio de cesta colgado', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price WHERE id_cart = ' . (int) $cart->id));
cerca('paga el 3 kg entero', $p3, $f['productos'] - $f['descuentos']);

echo "\nG. Perro y gato con regalo: dos regalos, una regla por producto regalado, los dos al habitual\n";
$cart = cesta();
desdeLaFicha($cart, S, S12, 1, true);
desdeLaFicha($cart, G, G10, 1, true);
$f = foto($cart);
$esperadas = [S . '-' . S12 => 1, S . '-' . S3 => 1, G . '-' . G10 => 1, G . '-' . G25 => 1];
ksort($esperadas);
igual('líneas', $esperadas, $f['lineas']);
igual('regalos', [S . '-' . S3 => 1, G . '-' . G25 => 1], $f['regalos']);
igual('dos reglas', [reglaRegalo($cart, S) => $p3, reglaRegalo($cart, G) => $pG25], $f['reglas']);
cerca('productos − descuentos = los dos sacos grandes al habitual', round($h12 + $hG10, 2), $f['productos'] - $f['descuentos']);
KpcRegalo::elegir((int) $cart->id, G10, false);
sincronizar($cart);
$f = foto($cart);
igual('quita el regalo del gato: el perro sigue con el suyo', [S . '-' . S3 => 1], $f['regalos']);
cerca('el gato vuelve a su Special Price', $pG10, precioLinea($cart, G, G10));
cerca('el perro sigue al habitual', $h12, precioLinea($cart, S, S12));

echo "\nH. Un cupón sobre el mismo artículo se lo queda (un artículo, una promoción)\n";
$cupon = promo([
    'codigo' => 'TESTREG10', 'nombre' => 'Prueba cupón 10', 'tipo' => 'cupon', 'porcentaje' => 10, 'activo' => 1,
    'fecha_desde' => date('Y-m-d H:i:00', time() - 300), 'fecha_hasta' => date('Y-m-d 23:59:59', strtotime('+7 days')),
    'en_ficha' => 1, 'excluir_especial' => 0, 'incluir_packs' => 1, 'packs' => '', 'sin_outlet' => 1, 'en_merchant' => 0, 'lista' => S . '-' . S12, 'lista_tipo' => 'combinacion',
]);
$cupon->guardarAmbitos([['tipo' => 'combinacion', 'id' => S12]]);
$cart = cesta();
desdeLaFicha($cart, S, S12, 1, true);
igual('antes del cupón: con regalo', [S . '-' . S12 => 1, S . '-' . S3 => 1], foto($cart)['lineas']);
rehacer();
$cupon = new KpcCupon((int) $cupon->id);
$r1 = new KpcCupon((int) $r1->id);
echo "  el cupón llega a {$cupon->formatos} artículos; el regalo ahora dice: «{$r1->aviso}»\n";
igual('el regalo ya no tiene el 12 kg (lo tiene el cupón)', false, isset(KpcRegalo::guardadas((int) $r1->id)[S12]));
// El aviso, de la base de datos: PrestaShop guarda en memoria el objeto ya cargado.
igual('y lo dice en su aviso', true, strpos((string) Db::getInstance()->getValue('SELECT aviso FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . (int) $r1->id), '1 en TESTREG10') !== false);
$ctx->cookie->kpy_cupon = 'TESTREG10';
sincronizar($cart);
$f = foto($cart);
igual('en la cesta, el regalo se va y la elección también (su promoción ya no lo tiene)', [[S . '-' . S12 => 1], []], [$f['lineas'], $f['elegidas']]);
igual('el cupón está puesto', true, isset($f['reglas']['TESTREG10']));
cerca('y descuenta su 10 % sobre el Special Price', round($p12 * 0.1, 2), $f['reglas']['TESTREG10'] ?? 0.0, 0.02);
cerca('el 12 kg se queda con su Special Price', $p12, precioLinea($cart, S, S12));
igual('sin precio de cesta', 0, preciosCesta($cart, S, S12));
$ctx->cookie->kpy_cupon = '';
$cupon->delete();
rehacer();

echo "\nI. 2ª unidad en el 3 kg: las unidades regaladas no cuentan como par\n";
$segunda = promo([
    'codigo' => '2UD-TEST-3KG', 'nombre' => 'Prueba 2ª unidad 3 kg', 'tipo' => 'segunda_unidad', 'porcentaje' => 30, 'activo' => 1,
    'fecha_desde' => date('Y-m-d H:i:00', time() - 300), 'fecha_hasta' => date('Y-m-d 23:59:59', strtotime('+7 days')),
    'en_ficha' => 1, 'excluir_especial' => 0, 'incluir_packs' => 1, 'packs' => '', 'sin_outlet' => 1, 'en_merchant' => 0, 'lista' => S . '-' . S3, 'lista_tipo' => 'combinacion',
]);
$segunda->guardarAmbitos([['tipo' => 'combinacion', 'id' => S3]]);
rehacer();
$segunda = new KpcCupon((int) $segunda->id);
echo "  la 2ª unidad llega a {$segunda->formatos} artículos; aviso: «{$segunda->aviso}»\n";
$cart = cesta();
desdeLaFicha($cart, S, S12, 1, true);
desdeLaFicha($cart, S, S3, 1, false);
$f = foto($cart);
igual('líneas: 12 kg y dos 3 kg (uno pagado, uno regalo)', [S . '-' . S12 => 1, S . '-' . S3 => 2], $f['lineas']);
igual('sin regla de 2ª unidad: solo hay un 3 kg pagado', false, isset($f['reglas'][KpcSegunda::codigo($cart, (int) $segunda->id)]));
desdeLaFicha($cart, S, S3, 1, false);
$f = foto($cart);
igual('líneas: tres 3 kg (dos pagados, uno regalo)', [S . '-' . S12 => 1, S . '-' . S3 => 3], $f['lineas']);
cerca('la 2ª unidad descuenta un par al 30 %', round($p3 * 0.3, 2), $f['reglas'][KpcSegunda::codigo($cart, (int) $segunda->id)] ?? 0.0, 0.02);
cerca('el regalo descuenta un 3 kg', $p3, $f['reglas'][reglaRegalo($cart, S)] ?? 0.0, 0.02);
cerca('productos − descuentos = 12 kg habitual + 2 × 3 kg − 30 % de uno', round($h12 + 2 * $p3 - $p3 * 0.3, 2), $f['productos'] - $f['descuentos'], 0.03);
$segunda->delete();
rehacer();

echo "\nJ. Los packs también regalan: el pack 2 × 12 con regalo regala dos, al precio habitual del pack\n";
$r1 = new KpcCupon((int) $r1->id);
$r1->regalo_packs = 1;
$r1->incluir_packs = 1;
$r1->packs = '';
$r1->update();
rehacer();
$parejas = KpcRegalo::guardadas((int) $r1->id);
igual('el pack entra con n = 2', 2, $parejas[SP2]['n'] ?? 0);
$cart = cesta();
desdeLaFicha($cart, S, SP2, 1, true);
$f = foto($cart);
igual('líneas: el pack y dos 3 kg', [S . '-' . S3 => 2, S . '-' . SP2 => 1], $f['lineas']);
cerca('la regla descuenta dos 3 kg', round(2 * $p3, 2), $f['reglas'][reglaRegalo($cart, S)] ?? 0.0, 0.02);
cerca('el pack al precio habitual', habitual(S, SP2), precioLinea($cart, S, SP2));
$r1->regalo_packs = 0;
$r1->incluir_packs = 0;
$r1->packs = '1';
$r1->update();
rehacer();

echo "\nK. Uno por pedido: dos sacos con regalo, un regalo\n";
$r1->regalo_por = 'pedido';
$r1->update();
rehacer();
$cart = cesta();
desdeLaFicha($cart, S, S12, 2, true);
$f = foto($cart);
igual('líneas', [S . '-' . S12 => 2, S . '-' . S3 => 1], $f['lineas']);
igual('una unidad con regalo (uno por pedido)', 1, (int) (KpcRegalo::elegidasDe((int) $cart->id)[S12]['unidades'] ?? 0));
cerca('productos − descuentos = el del regalo al habitual y el otro a su Special Price', round($h12 + $p12, 2), $f['productos'] - $f['descuentos']);
$r1->regalo_por = 'unidad';
$r1->update();
rehacer();

echo "\nL. El 3 kg agotado: sin regalo (el saco a su Special Price) y la cesta lo dice; con reserva, entra la reserva\n";
$stock3 = (int) StockAvailable::getQuantityAvailableByProduct(S, S3, 1);
$oos = (int) StockAvailable::outOfStock(S, 1);
StockAvailable::setProductOutOfStock(S, 0, 1);
StockAvailable::setQuantity(S, S3, 0, 1);
$cart = cesta();
desdeLaFicha($cart, S, S12, 1, true);
$f = foto($cart);
igual('solo el 12 kg', [S . '-' . S12 => 1], $f['lineas']);
igual('sin regalos ni reglas; la elección se queda, por si vuelve el stock', [[], [], [S12]], [$f['regalos'], $f['reglas'], $f['elegidas']]);
cerca('sin regalo nadie paga de más: el 12 kg a su Special Price', $p12, precioLinea($cart, S, S12));
$resumen = KpcResumen::deLaCesta($cart, 1, $modulo);
$avisos = array_column($resumen['lineas'], 'texto');
igual('la cesta avisa de que el regalo está agotado', true, count(array_filter($avisos, static fn ($t) => stripos($t, 'agotado') !== false)) === 1);
echo '  aviso: ' . implode(' | ', $avisos) . "\n";
igual('y la ficha no ofrece la opción con regalo (el regalo, agotado y sin reserva)', null, fichaEstado(S12)['regalo']);
$r1->regalo_reserva = LATAS . '-' . LATAS6;
$r1->update();
rehacer();
sincronizar($cart);
$f = foto($cart);
$pLatas = precio(LATAS, LATAS6);
igual('con reserva: entra el pack de latas', [S . '-' . S12 => 1, LATAS . '-' . LATAS6 => 1], $f['lineas']);
cerca('la regla descuenta las latas', $pLatas, $f['reglas'][reglaRegalo($cart, LATAS)] ?? 0.0, 0.02);
cerca('y el 12 kg, con regalo, al habitual', $h12, precioLinea($cart, S, S12));
StockAvailable::setQuantity(S, S3, $stock3, 1);
sincronizar($cart);
$f = foto($cart);
igual('vuelve el stock: vuelve el 3 kg y se van las latas', [S . '-' . S12 => 1, S . '-' . S3 => 1], $f['lineas']);
igual('y queda solo la regla del 3 kg', [reglaRegalo($cart, S) => $p3], $f['reglas']);
StockAvailable::setProductOutOfStock(S, $oos, 1);
$r1->regalo_reserva = '';
$r1->update();
rehacer();

echo "\nM. Se acaba la promoción: el regalo y la elección se van, el saco vuelve a su precio\n";
$cart = cesta();
desdeLaFicha($cart, S, S12, 1, true);
$codigoRegla = reglaRegalo($cart, S);
$hasta = $r1->fecha_hasta;
$r1->fecha_hasta = date('Y-m-d H:i:s', time() - 60);
$r1->update();
rehacer();
sincronizar($cart);
$f = foto($cart);
igual('solo el 12 kg, sin elección', [[S . '-' . S12 => 1], []], [$f['lineas'], $f['elegidas']]);
igual('sin reglas', [], $f['reglas']);
igual('la regla se ha borrado', 0, (int) CartRule::getIdByCode($codigoRegla));
cerca('el 12 kg a su Special Price', $p12, precioLinea($cart, S, S12));
igual('sin precio de cesta', 0, preciosCesta($cart, S, S12));
$r1->fecha_hasta = $hasta;
$r1->update();
rehacer();
sincronizar($cart);
igual('vuelve la promoción: sin regalo hasta que el cliente lo vuelva a elegir', [S . '-' . S12 => 1], foto($cart)['lineas']);
desdeLaFicha($cart, S, S12, 1, true);
igual('lo elige otra vez: dos 12 kg (uno con regalo) y un 3 kg', [S . '-' . S12 => 2, S . '-' . S3 => 1], foto($cart)['lineas']);
cerca('la línea, al habitual (y el de siempre, con su vale «Special Price»)', $h12, precioLinea($cart, S, S12));
$cart->updateQty(1, S, S12, false, 'down');

echo "\nN. Lo que ve el cliente: la línea de regalo, el vale, la ficha, las etiquetas, Google y Doofinder\n";
try {
    $ctx->currentLocale = $kernel->getContainer()->get('prestashop.core.localization.locale.repository')->getLocale($ctx->language->getLocale());
    $presentada = (new PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter())->present($cart);
    $marcas = [];
    foreach ($presentada['products'] as $pr) {
        $marcas[$pr['id_product'] . '-' . $pr['id_product_attribute']] = !empty($pr['kpy_is_gift']) ? 1 : 0;
    }
    ksort($marcas);
    igual('la línea del 3 kg es regalo (kpy_is_gift); la del 12 kg no', [S . '-' . S12 => 0, S . '-' . S3 => 1], $marcas);
} catch (Throwable $e) {
    igual('presentar la cesta', 'sin error', get_class($e) . ': ' . $e->getMessage());
}
$resumen = KpcResumen::deLaCesta($cart, 1, $modulo);
igual('el vale del regalo en el resumen de la cesta', ['regalo'], array_values(array_unique(array_column($resumen['vales'], 'tipo'))));
echo '  texto del vale: ' . implode(' | ', array_column($resumen['vales'], 'texto')) . "\n";
igual('sin «Special Price en N productos» (el 12 kg va al habitual y el 3 kg es regalo)', [], array_values(array_filter(array_column($resumen['lineas'], 'clase'), static fn ($c) => $c === 'especial')));
$tpl = ['name' => 'Boske 3 kg'];
$fila = ['id_product' => S, 'id_product_attribute' => S3, 'cart_quantity' => 1];
Hook::exec('actionPaymentModuleProductVarTplAfter', ['product_var_tpl' => &$tpl, 'product' => $fila, 'order' => null, 'context' => $ctx]);
igual('el email dirá «(regalo)»', 'Boske 3 kg (regalo)', $tpl['name']);
$e = fichaEstado(S12);
igual('la ficha ofrece la opción con regalo en el 12 kg', 'regalo', $e['tipo']);
$opcion = $e['regalo']['opciones'][1] ?? [];
igual('«12 kg + 3 kg gratis», chip «Kilos gratis», €/kg con los 15 kg', ['12 kg + 3 kg gratis', 'Kilos gratis', true], [$opcion['nombre'] ?? null, $opcion['chip'] ?? null, strpos((string) ($opcion['kg_txt'] ?? ''), '€/kg') !== false]);
igual('y el 3 kg y el pack no la ofrecen', [null, null], [fichaEstado(S3)['regalo'], fichaEstado(SP2)['regalo']]);
$enFicha = KpcRegalo::paraFicha(1, S, [S12, S3, SP2])['por'][S12] ?? [];
igual('etiqueta «+ 3 kg gratis», tarjeta «3 kg gratis», clase kilos, formato y peso del regalo', ['+ 3 kg gratis', '3 kg gratis', 'kilos', 'Saco de 3 Kg', 3.0], [$enFicha['etiqueta'] ?? null, $enFicha['corto'] ?? null, $enFicha['clase'] ?? null, $enFicha['formato'] ?? null, (float) ($enFicha['peso'] ?? 0)]);
igual('y su PVPR (para el tachado de la tarjeta)', true, (float) ($enFicha['pvpr'] ?? 0) >= (float) ($enFicha['precio'] ?? 1));

echo '  regalo en la ficha: ' . json_encode($enFicha, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
// 3.0.0: las etiquetas se calculan por hook; en las tablas de kpyproductflags no hay ninguna fila nuestra.
igual('ninguna fila nuestra en las tablas de kpyproductflags', 0, (int) Db::getInstance()->getValue(
    'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_product_flag pf INNER JOIN ' . _DB_PREFIX_ . 'kpy_flag f ON f.id_flag = pf.id_flag
     WHERE pf.id_product = ' . S . " AND f.type LIKE '+ %'"
));
$flags = [];
$_GET['id_product'] = (string) S;
KpcFlags::ajustar($flags, ['id_product' => S, 'id_product_attribute' => S12], $modulo);
unset($_GET['id_product']);
igual('y la etiqueta «+ 3 kg gratis» sale calculada', '+ 3 kg gratis', $flags['kpc_0']['label'] ?? '');
$r1 = new KpcCupon((int) $r1->id);
$porArticulo = [];
foreach (explode("\n", KpcMerchant::fichero('etiquetas', 1)) as $l) {
    $partes = explode("\t", $l);
    if (count($partes) === 2) {
        $porArticulo[$partes[0]] = $partes[1];
    }
}
igual('Google: el 12 kg (con Special Price) no lleva la promoción de regalo', false, strpos((string) ($porArticulo[S . '-' . S12] ?? ''), (string) $r1->merchant_id) !== false);
$sinSp = (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_item i WHERE i.id_kpy_coupon = ' . (int) $r1->id . ' AND NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'kpy_special_price sp WHERE sp.id_product_attribute = i.clave AND NOW() BETWEEN sp.date_from AND sp.expire)');
$enPromos = strpos(KpcMerchant::fichero('promociones', 1), (string) $r1->merchant_id) !== false;
echo "  artículos del regalo sin Special Price: {$sinSp}; la promoción va en el fichero de Google: " . ($enPromos ? 'sí' : 'no') . "\n";
igual('Google: la promoción va solo si le queda algún artículo sin Special Price', $sinSp > 0, $enPromos);
$doofinder = KpcFlags::paraDoofinder($modulo);
igual('Doofinder: el producto lleva «+ 3 kg gratis»', '+ 3 kg gratis', $doofinder[S]['kpy_promo'] ?? null);

if ($conPedido) {
    echo "\nO. El pedido: el 12 kg al precio habitual, la línea del regalo con su EAN y «(regalo)», el vale y el sell-out\n";
    $mail = Configuration::get('PS_MAIL_METHOD');
    Configuration::updateValue('PS_MAIL_METHOD', 3);
    try {
        $cart = cesta();
        desdeLaFicha($cart, S, S12, 1, true);
        desdeLaFicha($cart, S, S12, 1, false);
        $f = foto($cart);
        igual('cesta lista: «12 kg + 3 kg gratis» y «12 kg» con su Special Price', [S . '-' . S12 => 2, S . '-' . S3 => 1], $f['lineas']);
        $pago = Module::getInstanceByName('ps_wirepayment');
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $envio = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $pago->validateOrder((int) $cart->id, (int) Configuration::get('PS_OS_BANKWIRE'), $total, $pago->displayName, null, [], 1, false, $ctx->customer->secure_key);
        $idOrder = (int) $pago->currentOrder;
        igual('pedido creado', true, $idOrder > 0);
        echo "  pedido #{$idOrder}\n";
        $order = new Order($idOrder);
        $lineas = [];
        foreach (Db::getInstance()->executeS('SELECT product_id, product_attribute_id, product_quantity, product_name, product_ean13, unit_price_tax_incl, reduction_percent FROM ' . _DB_PREFIX_ . 'order_detail WHERE id_order = ' . $idOrder) as $l) {
            $lineas[$l['product_id'] . '-' . $l['product_attribute_id']] = $l;
        }
        ksort($lineas);
        igual('dos líneas en el pedido', [S . '-' . S12, S . '-' . S3], array_keys($lineas));
        cerca('la línea del 12 kg lleva el precio habitual (las dos unidades)', $h12, (float) ($lineas[S . '-' . S12]['unit_price_tax_incl'] ?? 0));
        echo '  12 kg: ' . ($lineas[S . '-' . S12]['unit_price_tax_incl'] ?? '') . ' € (rebaja apuntada ' . ($lineas[S . '-' . S12]['reduction_percent'] ?? '') . " %)\n";
        igual('la línea del 3 kg dice «(regalo)»', true, (bool) preg_match('/\(regalo\)$/u', (string) ($lineas[S . '-' . S3]['product_name'] ?? '')));
        igual('la del 12 kg no', false, (bool) preg_match('/regalo/u', (string) ($lineas[S . '-' . S12]['product_name'] ?? '')));
        echo '  línea: ' . ($lineas[S . '-' . S3]['product_name'] ?? '') . ' · EAN ' . ($lineas[S . '-' . S3]['product_ean13'] ?? '') . ' · ' . ($lineas[S . '-' . S3]['unit_price_tax_incl'] ?? '') . " €\n";
        cerca('la línea del regalo lleva su precio', $p3, (float) ($lineas[S . '-' . S3]['unit_price_tax_incl'] ?? 0));
        $vales = [];
        foreach (Db::getInstance()->executeS('SELECT ocr.name, ocr.value, cr.code FROM ' . _DB_PREFIX_ . 'order_cart_rule ocr INNER JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON cr.id_cart_rule = ocr.id_cart_rule WHERE ocr.id_order = ' . $idOrder) ?: [] as $v) {
            $vales[substr((string) $v['code'], 0, 5)] = $v;
        }
        ksort($vales);
        igual('dos vales en el pedido: el del regalo y el Special Price del de siempre', ['KPYRG', 'KPYSP'], array_keys($vales));
        cerca('el del regalo descuenta el 3 kg', $p3, (float) ($vales['KPYRG']['value'] ?? 0));
        cerca('el Special Price, la diferencia del 12 kg de la tarjeta de siempre', round($h12 - $p12, 2), (float) ($vales['KPYSP']['value'] ?? 0));
        echo '  vales: ' . implode(' | ', array_map(static fn ($v) => $v['name'] . ' ' . $v['value'], $vales)) . "\n";
        cerca('total de productos = dos 12 kg al habitual + 3 kg', round(2 * $h12 + $p3, 2), (float) $order->total_products_wt);
        cerca('descuentos del pedido = 3 kg + la diferencia', round($p3 + $h12 - $p12, 2), (float) $order->total_discounts_tax_incl);
        cerca('total pagado = 12 kg habitual + 12 kg Special Price + envío', round($h12 + $p12 + $envio, 2), (float) $order->total_paid_tax_incl);
        $sellout = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_pedido WHERE id_order = ' . $idOrder) ?: [];
        igual('el sell-out apunta el regalo', 1, count($sellout));
        igual('con su promoción, disparador y unidades', [(int) $r1->id, S12, S, S3, 1], [(int) ($sellout[0]['id_kpy_coupon'] ?? 0), (int) ($sellout[0]['disparador'] ?? 0), (int) ($sellout[0]['r_id_product'] ?? 0), (int) ($sellout[0]['r_ipa'] ?? 0), (int) ($sellout[0]['cantidad'] ?? 0)]);
        igual('la fila de la cesta se ha ido', [], KpcRegalo::filasCesta((int) $cart->id));
        igual('la elección y el precio de cesta también (el pedido ya lleva sus precios)', [[], 0], [array_keys(KpcRegalo::elegidasDe((int) $cart->id)), (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price WHERE id_cart = ' . (int) $cart->id)]);
        igual('pero la cesta pedida sigue sabiendo qué era regalo (confirmación e historial)', [S3 => 1], KpcRegalo::regaladasDe((int) $cart->id));
        igual('usados de la promoción', [(int) $r1->id => 1], KpcRegalo::usados([(int) $r1->id]));

        echo "\nP. «Pedir de nuevo»: el 3 kg regalado no se cobra, vuelve como regalo\n";
        $dup = (new Cart((int) $cart->id))->duplicate();
        $nueva = new Cart((int) ($dup['cart']->id ?? 0));
        $cestas[] = (int) $nueva->id;
        igual('cesta copiada', true, Validate::isLoadedObject($nueva) && (int) $nueva->id !== (int) $cart->id);
        $ctx->cart = $nueva;
        sincronizar($nueva);
        $f = foto($nueva);
        igual('los dos 12 kg y un 3 kg (el regalo, no uno pagado de más)', [S . '-' . S12 => 2, S . '-' . S3 => 1], $f['lineas']);
        igual('con el regalo elegido otra vez, en una unidad', [[S12], [S . '-' . S3 => 1], 1], [$f['elegidas'], $f['regalos'], (int) (KpcRegalo::elegidasDe((int) $nueva->id)[S12]['unidades'] ?? 0)]);
        cerca('y la línea del 12 kg al habitual', $h12, precioLinea($nueva, S, S12));
        cerca('productos − descuentos = uno al habitual y otro a su Special Price', round($h12 + $p12, 2), $f['productos'] - $f['descuentos']);

        echo "\nQ. El cupo de la campaña: con uno dado y cupo 1, el siguiente no lo lleva (y no paga de más)\n";
        $r1->cupo = 1;
        $r1->update();
        rehacer();
        $cart = cesta();
        desdeLaFicha($cart, S, S12, 1, true);
        $f = foto($cart);
        igual('solo el 12 kg', [S . '-' . S12 => 1], $f['lineas']);
        cerca('a su Special Price', $p12, precioLinea($cart, S, S12));
        $resumen = KpcResumen::deLaCesta($cart, 1, $modulo);
        $avisos = array_column($resumen['lineas'], 'texto');
        igual('la cesta dice que se han acabado los regalos', true, count(array_filter($avisos, static fn ($t) => stripos($t, 'acabado') !== false)) === 1);
        echo '  aviso: ' . implode(' | ', $avisos) . "\n";
        $r1->cupo = 0;
        $r1->update();
        rehacer();
    } finally {
        Configuration::updateValue('PS_MAIL_METHOD', $mail);
    }
} else {
    echo "\n(sin --pedido: no se valida ningún pedido)\n";
}

echo "\nLimpieza\n";
limpiarPromos();
rehacer();
foreach ($cestas as $idCart) {
    $c = new Cart($idCart);
    if (Validate::isLoadedObject($c) && !$c->orderExists()) {
        $c->delete();
    }
}
// Las cestas borradas dejan sus reglas KPYRG, sus elecciones y sus precios de cesta: el barrido de
// cada 6 horas los quita (aquí, ya).
$barrido = KpcLimpieza::barrer();
echo '  barrido: ' . json_encode($barrido) . "\n";
igual('sin filas de regalo colgadas en cestas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_cesta rc LEFT JOIN ' . _DB_PREFIX_ . 'cart c ON c.id_cart = rc.id_cart WHERE c.id_cart IS NULL'));
igual('sin elecciones colgadas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_eleccion e LEFT JOIN ' . _DB_PREFIX_ . 'cart c ON c.id_cart = e.id_cart WHERE c.id_cart IS NULL'));
igual('sin precios de cesta de estas cestas', 0, (int) Db::getInstance()->getValue('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'specific_price WHERE id_cart IN (' . implode(',', $cestas) . ')'));
$deEstas = implode(' OR ', array_map(static fn (int $id): string => "cr.code LIKE 'KPYRG-{$id}-%' OR cr.code LIKE 'KPYSP-{$id}-%'", $cestas));
igual('sin reglas KPYRG ni KPYSP huérfanas de estas cestas', 0, (int) Db::getInstance()->getValue("SELECT COUNT(*) FROM " . _DB_PREFIX_ . "cart_rule cr WHERE ({$deEstas}) AND NOT EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "order_cart_rule ocr WHERE ocr.id_cart_rule = cr.id_cart_rule) AND NOT EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "cart_cart_rule ccr WHERE ccr.id_cart_rule = cr.id_cart_rule)"));
echo $fallos ? "\n$fallos FALLOS\n" : "\nTodo bien\n";
exit($fallos ? 1 : 0);
