<?php
/**
 * Retoques de HTML para la ficha. No depende de PrestaShop.
 */
class KppHtml
{
    /**
     * La descripción viene del back office (a veces pegada de la web de la marca, con imágenes
     * enormes). Sin cambiar su contenido:
     *  - toda imagen carga en diferido (`loading="lazy"`, `decoding="async"`): la descripción
     *    está siempre por debajo de la primera pantalla, así que no retrasa nada de lo que se ve;
     *  - la imagen sin `alt` recibe el nombre del producto (Google y los lectores de pantalla lo
     *    necesitan);
     *  - fuera los atributos del editor (`data-mce-*`).
     */
    public static function descripcion(string $html, string $nombreProducto): string
    {
        if (stripos($html, '<img') === false) {
            return $html;
        }
        $alt = htmlspecialchars($nombreProducto, ENT_QUOTES, 'UTF-8');

        return preg_replace_callback('/<img\b[^>]*>/i', function (array $m) use ($alt) {
            $tag = preg_replace('/\sdata-mce-[a-z-]+="[^"]*"/i', '', $m[0]);
            if (!preg_match('/\sloading=/i', $tag)) {
                $tag = preg_replace('/^<img\b/i', '<img loading="lazy"', $tag);
            }
            if (!preg_match('/\sdecoding=/i', $tag)) {
                $tag = preg_replace('/^<img\b/i', '<img decoding="async"', $tag);
            }
            if (!preg_match('/\salt=/i', $tag)) {
                $tag = preg_replace('/^<img\b/i', '<img alt="' . $alt . '"', $tag);
            }

            return $tag;
        }, $html) ?? $html;
    }

    /** Nombre corto de las cuatro cifras principales de la composición. */
    public static function nombreCorto(string $clave): string
    {
        return ['proteina' => 'Proteína', 'grasa' => 'Grasa', 'fibra' => 'Fibra', 'ceniza' => 'Ceniza'][$clave] ?? $clave;
    }
}
