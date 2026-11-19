<?php
/**
 * La cesta de un solo almacén: cambiar una línea por su alternativa, «Dejarlo como está», contar lo que
 * se usa y decir el estado del aviso del pie.
 *
 *   POST /module/kpycrosssell/cambiar   token, accion=cambiar, de=1234-5678, a=2345-6789, origen=linea|ventana
 *   POST …                              token, accion=dejar, de=1234-5678
 *   POST …                              token, accion=contar, evento=vermas|info, de=1234-5678
 *   GET  …?accion=estado&ajax=1         el aviso del pie, ya pintado (sin token: solo lee)
 *
 * Con `ajax=1` contesta JSON; sin él (sin JavaScript) vuelve a la cesta con `kcs=cambiado|error`, que el
 * aviso del pie convierte en un mensaje. Solo acepta como alternativa lo que **ahora mismo** sea una
 * alternativa de esa línea (`KcsCesta::cambiar` lo recalcula): nadie mete por aquí lo que quiera.
 */
class KpycrosssellCambiarModuleFrontController extends ModuleFrontController
{
    public function postProcess(): void
    {
        /** @var Kpycrosssell $modulo */
        $modulo = $this->module;
        $ajax = (bool) Tools::getValue('ajax') || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
        $accion = (string) Tools::getValue('accion', 'cambiar');
        $cart = $this->context->cart;

        if (!$cart instanceof Cart || !$cart->id) {
            $this->responder(['ok' => false, 'mensaje' => $modulo->t('No hay nada en tu cesta.')], $ajax, 'error');
        }

        if ($accion === 'estado') {
            $this->responder(['ok' => true] + $modulo->estadoCesta($cart), true, '');
        }

        if ((string) Tools::getValue('token') !== Tools::getToken(false)) {
            $this->responder(['ok' => false, 'mensaje' => $modulo->t('La página ha caducado. Recárgala e inténtalo otra vez.')], $ajax, 'error');
        }

        [$idDe, $ipaDe] = array_map('intval', array_pad(explode('-', (string) Tools::getValue('de', ''), 2), 2, 0));

        switch ($accion) {
            case 'cambiar':
                $r = KcsCesta::cambiar($cart, (string) Tools::getValue('de', ''), (string) Tools::getValue('a', ''), $modulo, Tools::getValue('origen') === 'ventana' ? 'ventana' : 'linea');
                $this->responder($r, $ajax, $r['ok'] ? 'cambiado' : 'error');
                break;

            case 'dejar':
                $linea = KcsCesta::bloqueante($cart, $idDe, $ipaDe);
                KcsCestaLog::apuntar((int) $cart->id, 'dejar', $idDe, $ipaDe, (string) ($linea['referencia'] ?? ''));
                $this->responder(['ok' => true], $ajax, '');
                break;

            case 'contar':
                $evento = (string) Tools::getValue('evento');
                if (in_array($evento, ['vermas', 'info'], true)) {
                    KcsCestaLog::apuntar((int) $cart->id, $evento, $idDe, $ipaDe, (string) KcsCesta::decision($cart)['referencia']);
                }
                $this->responder(['ok' => true], $ajax, '');
                break;

            default:
                $this->responder(['ok' => false, 'mensaje' => $modulo->t('No se ha podido cambiar. Revisa la cesta.')], $ajax, 'error');
        }
    }

    /** JSON si es ajax; si no, de vuelta a la cesta con el resultado en la dirección. */
    private function responder(array $datos, bool $ajax, string $resultado): void
    {
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode($datos, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $parametros = ['action' => 'show'];
        if ($resultado !== '') {
            $parametros['kcs'] = $resultado;
        }
        Tools::redirect($this->context->link->getPageLink('cart', true, null, $parametros));
    }
}
