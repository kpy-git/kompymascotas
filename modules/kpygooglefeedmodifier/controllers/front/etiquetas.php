<?php
declare(strict_types=1);

use PrestaShop\Module\KpyGoogleFeedModifier\Etiquetas\EtiquetasException;
use PrestaShop\Module\KpyGoogleFeedModifier\Etiquetas\Importador;

/**
 * POST /module/kpygooglefeedmodifier/etiquetas
 *
 * Recibe del Etiquetador de Google Ads la lista entera «id-combinacion → etiqueta» y sustituye la tabla de la que
 * lee el feed. Clave en la cabecera X-Kompy-Clave. Cuerpo JSON:
 *   {"origen": "M7 1.3.0 · M7-20261110-0500", "probar": false, "etiquetas": [["236-328", "excluir"], …]}
 * Respuestas: 200 {"ok":true,"probado":false,"filas":8134,"antes":5366,"por_etiqueta":{…}};
 * 403 clave; 400 JSON; 422 datos (id, etiqueta, repetido, menos de 5.000 filas); 405 si no es POST; 500 al guardar.
 * En cualquier error la tabla queda como estaba. La clave no se escribe en ningún registro.
 */
class KpyGoogleFeedModifierEtiquetasModuleFrontController extends ModuleFrontController
{
    /** No hace falta nada del front (ni cesta, ni cookies, ni plantillas): se contesta y se termina. */
    public function init(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            $this->responder(405, ['ok' => false, 'error' => 'Solo POST.']);
        }

        $clave = (string) ($_SERVER['HTTP_X_KOMPY_CLAVE'] ?? '');
        if (!Importador::claveValida($clave)) {
            $this->responder(403, ['ok' => false, 'error' => 'Clave no válida.']);
        }

        $crudo = (string) file_get_contents('php://input');
        $cuerpo = json_decode($crudo, true);
        if (!is_array($cuerpo)) {
            $this->responder(400, ['ok' => false, 'error' => 'JSON no válido: ' . json_last_error_msg()]);
        }

        try {
            $datos = Importador::validar($cuerpo);
            $antes = Importador::contar();
            $porEtiqueta = Importador::porEtiqueta($datos['filas']);
            if (!$datos['probar']) {
                Importador::sustituir($datos['filas']);
                Importador::anotarActualizacion($datos['origen'], count($datos['filas']));
            }
            $this->responder(200, [
                'ok' => true,
                'probado' => $datos['probar'],
                'filas' => count($datos['filas']),
                'antes' => $antes,
                'por_etiqueta' => $porEtiqueta,
            ]);
        } catch (EtiquetasException $e) {
            $this->responder($e->getCodigoHttp(), ['ok' => false, 'error' => $e->getMessage()]);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpygooglefeedmodifier etiquetas: ' . $e->getMessage(), 3);
            $this->responder(500, ['ok' => false, 'error' => 'Error inesperado al guardar; la tabla no ha cambiado.']);
        }
    }

    /** @param array<string, mixed> $datos */
    private function responder(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
