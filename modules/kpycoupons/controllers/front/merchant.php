<?php
/**
 * Los ficheros de Google Merchant, servidos por su dirección para que los recoja Merchant solo.
 *
 *   /module/kpycoupons/merchant?fichero=promociones&clave=…
 *   /module/kpycoupons/merchant?fichero=precios&clave=…
 *   /module/kpycoupons/merchant?fichero=etiquetas&clave=…
 *
 * En Merchant Center se pega la dirección una vez («Añadir promociones desde un archivo» →
 * programar la obtención, y lo mismo en las dos fuentes complementarias de productos) y ya no hay
 * que volver a subir nada a mano: cada vez que cambie una promoción en la tienda, Google lee lo
 * nuevo en su siguiente pasada.
 *
 * La clave es la de la pantalla del módulo. Sin ella no se sirve nada: estos ficheros dicen qué
 * promociones hay antes de que salgan.
 */
class KpycouponsMerchantModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function postProcess(): void
    {
        $cual = (string) Tools::getValue('fichero');
        $clave = (string) Tools::getValue('clave');

        if (!hash_equals(KpcMerchant::clave(), $clave) || !in_array($cual, KpcMerchant::FICHEROS, true)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $contenido = KpcMerchant::fichero($cual, (int) $this->context->shop->id);
        $nombre = KpcMerchant::NOMBRES[$cual];

        header('Content-Type: text/tab-separated-values; charset=utf-8');
        header('Content-Disposition: ' . (Tools::getValue('descargar') ? 'attachment' : 'inline') . '; filename="' . $nombre . '"');
        header('Cache-Control: no-store');
        echo $contenido;
        exit;
    }
}
