<?php
/**
 * Añadir a la cesta lo elegido en el bloque «Caprichitos», sin recargar la página.
 *
 *   POST /module/kpycrosssell/anadir   articulos[]=1234-5678&articulos[]=…
 *
 * Solo acepta artículos que **de verdad** sean sugerencia de algún producto: así nadie puede usar
 * esta dirección para meter en la cesta lo que le apetezca a un precio que no toca (el precio lo
 * pone PrestaShop, pero conviene no abrir una puerta que no hace falta).
 */
class KpycrosssellAnadirModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function postProcess(): void
    {
        /** @var Kpycrosssell $modulo */
        $modulo = $this->module;
        $peticion = (array) Tools::getValue('articulos', []);
        if ($peticion === []) {
            $this->responder(['ok' => false, 'mensaje' => $modulo->t('No has elegido nada.')]);
        }

        $cart = $this->context->cart;
        if (!$cart || !$cart->id) {
            $cart = new Cart();
            $cart->id_currency = (int) $this->context->currency->id;
            $cart->id_lang = (int) $this->context->language->id;
            $cart->id_guest = (int) $this->context->cookie->id_guest;
            $cart->id_shop_group = (int) $this->context->shop->id_shop_group;
            $cart->id_shop = (int) $this->context->shop->id;
            $cart->id_customer = (int) $this->context->cookie->id_customer;
            $cart->add();
            $this->context->cookie->id_cart = (int) $cart->id;
            $this->context->cart = $cart;
        }

        $puestos = 0;

        // El producto que se está viendo entra también si todavía no estaba: sin los dos no hay
        // pareja, y el bloque acaba de prometer el descuento por llevarlos juntos.
        $principal = (string) Tools::getValue('principal', '');
        if ($principal !== '') {
            [$idPrincipal, $ipaPrincipal] = array_pad(explode('-', $principal, 2), 2, 0);
            $idPrincipal = (int) $idPrincipal;
            $ipaPrincipal = (int) $ipaPrincipal;
            $cuantas = max(1, (int) Tools::getValue('cantidad', 1));
            if ($idPrincipal > 0 && $ipaPrincipal > 0
                && $this->vale($idPrincipal, $ipaPrincipal)
                && (int) $cart->getProductQuantity($idPrincipal, $ipaPrincipal)['quantity'] === 0
                && $cart->updateQty($cuantas, $idPrincipal, $ipaPrincipal, false, 'up', 0, null, true)) {
                ++$puestos;
            }
        }

        foreach ($peticion as $articulo) {
            [$idProducto, $ipa] = array_pad(explode('-', (string) $articulo, 2), 2, 0);
            $idProducto = (int) $idProducto;
            $ipa = (int) $ipa;
            if ($idProducto <= 0 || $ipa <= 0 || !$this->vale($idProducto, $ipa)) {
                continue;
            }
            if ($cart->updateQty(1, $idProducto, $ipa, false, 'up', 0, null, true)) {
                ++$puestos;
                // Un clic: esta sugerencia ha funcionado en esta ficha.
                KcsClics::clic((int) Tools::getValue('ref'), $idProducto);
            }
        }

        if ($puestos === 0) {
            $this->responder(['ok' => false, 'mensaje' => $modulo->t('No se ha podido añadir. Inténtalo otra vez.')]);
        }

        // El descuento por llevarlos juntos se recalcula aquí mismo, para que la cesta ya llegue bien.
        KcsJuntos::sincronizar($cart, $modulo);

        $this->responder([
            'ok' => true,
            'puestos' => $puestos,
            'mensaje' => $puestos === 1
                ? $modulo->t('Añadido a la cesta')
                : $modulo->t('%n% productos añadidos a la cesta', ['%n%' => $puestos]),
            'cantidad' => (int) $cart->nbProducts(),
        ]);
    }

    /** Que ese formato exista, esté activo y tenga stock. */
    private function vale(int $idProducto, int $ipa): bool
    {
        $p = _DB_PREFIX_;

        return (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT 1
             FROM {$p}product_attribute pa
             INNER JOIN {$p}product pr ON pr.id_product = pa.id_product AND pr.active = 1
             INNER JOIN {$p}stock_available sa ON sa.id_product_attribute = pa.id_product_attribute
                   AND sa.id_shop = " . (int) $this->context->shop->id . " AND sa.quantity > 0
             WHERE pa.id_product = " . $idProducto . ' AND pa.id_product_attribute = ' . $ipa
        );
    }

    private function responder(array $datos): void
    {
        header('Content-Type: application/json');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
