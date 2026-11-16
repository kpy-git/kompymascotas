<?php
/**
 * Retoques al producto ya presentado (actionPresentProduct / actionPresentProductListing):
 *   - el EAN de la combinación cuando el producto no tiene EAN propio (así sale en la pestaña de
 *     detalles y en el JSON-LD que imprime el tema);
 *   - la leyenda (alt) de las imágenes que no la tienen: «Producto – Marca».
 *
 * El objeto que llega es un ProductLazyArray: los campos del producto («variables») se pueden
 * reescribir directamente; los que calcula un método (cover, images) solo con offsetSet(..., true).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoPresentacion
{
    /** @param mixed $producto */
    public static function completar($producto, bool $ficha): void
    {
        if (!$producto instanceof ArrayAccess) {
            return;
        }
        try {
            if ($ficha) {
                self::completarEan($producto);
            }
            if (KseoAjustes::activo('KPYSEO_ALT_AUTO')) {
                self::completarLeyendas($producto);
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpyseo presentación: ' . $e->getMessage(), 2);
        }
    }

    private static function completarEan(ArrayAccess $producto): void
    {
        $ean = (string) ($producto['ean13'] ?? '');
        if (KseoJsonLd::gtinValido($ean)) {
            return;
        }
        $nuevo = KseoJsonLd::eanCombinacion((int) ($producto['id_product'] ?? 0), (int) ($producto['id_product_attribute'] ?? 0));
        if ($nuevo !== '') {
            self::poner($producto, 'ean13', $nuevo);
        }
    }

    private static function completarLeyendas(ArrayAccess $producto): void
    {
        $nombre = trim(html_entity_decode((string) ($producto['name'] ?? ''), ENT_QUOTES, 'UTF-8'));
        if ($nombre === '') {
            return;
        }
        $marca = trim((string) ($producto['manufacturer_name'] ?? ''));
        $leyenda = ($marca !== '' && mb_stripos($nombre, $marca) === false) ? $nombre . ' – ' . $marca : $nombre;

        $portada = $producto['cover'] ?? null;
        if (is_array($portada) && trim((string) ($portada['legend'] ?? '')) === '') {
            $portada['legend'] = $leyenda;
            self::poner($producto, 'cover', $portada);
        }

        $imagenes = $producto['images'] ?? null;
        if (is_array($imagenes)) {
            $cambiado = false;
            $n = 0;
            foreach ($imagenes as $i => $imagen) {
                ++$n;
                if (is_array($imagen) && trim((string) ($imagen['legend'] ?? '')) === '') {
                    $imagenes[$i]['legend'] = $n > 1 ? $leyenda . ' (' . $n . ')' : $leyenda;
                    $cambiado = true;
                }
            }
            if ($cambiado) {
                self::poner($producto, 'images', $imagenes);
            }
        }
    }

    /** @param mixed $valor */
    private static function poner(ArrayAccess $producto, string $clave, $valor): void
    {
        if (method_exists($producto, 'offsetSet')) {
            // tercer parámetro: forzar aunque el índice lo defina un método del lazy array
            $producto->offsetSet($clave, $valor, true);
        }
    }
}
