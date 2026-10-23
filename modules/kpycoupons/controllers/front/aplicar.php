<?php
/**
 * Poner o quitar el cupón desde la ficha, sin recargar la página.
 *
 *   POST /module/kpycoupons/aplicar   codigo=BOSKE10&accion=poner|quitar
 *
 * Responde en JSON con si ha quedado puesto y cuánto descuenta en la cesta de ahora (0 si la cesta
 * está vacía: el descuento aparecerá al añadir el producto).
 */
class KpycouponsAplicarModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function postProcess(): void
    {
        $codigo = trim((string) Tools::getValue('codigo'));
        $accion = (string) Tools::getValue('accion');
        $idTienda = (int) $this->context->shop->id;
        /** @var Kpycoupons $modulo */
        $modulo = $this->module;

        $cupon = $codigo !== '' ? KpcCupon::porCodigo($codigo, $idTienda) : null;
        if ($cupon === null) {
            $this->responder(['ok' => false, 'motivo' => 'cupon_no_valido']);
        }

        if ($accion === 'quitar') {
            // Se apunta cuál: así no vuelve a entrar solo (por canal o por la web) mientras navega.
            $modulo->olvidar((string) $cupon['codigo']);
        } else {
            $modulo->guardar((string) $cupon['codigo']);
        }

        $descuento = 0.0;
        if ($this->context->cart && $this->context->cart->id) {
            $modulo->sincronizarCesta($this->context->cart);
            $regla = new CartRule((int) $cupon['id_cart_rule']);
            if (Validate::isLoadedObject($regla) && $accion !== 'quitar') {
                // Con cupo, el cupón son sus vales por producto (lo que de verdad descuenta).
                $descuento = KpcCupo::tieneCupo($cupon)
                    ? KpcCupoCesta::descuentoCupon($this->context->cart, (int) $cupon['id_kpy_coupon'])
                    : round(KpcCarrito::descuento($regla, $this->context->cart), 2);
            }
        }

        $this->responder([
            'ok' => true,
            'aplicado' => $accion !== 'quitar',
            'codigo' => (string) $cupon['codigo'],
            'pct' => (float) $cupon['porcentaje'],
            'descuento' => $descuento,
        ]);
    }

    private function responder(array $datos): void
    {
        header('Content-Type: application/json');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
