<?php
/**
 * Nombres, claves y direcciones de la landing de promociones.
 *
 * La **clave** de una promoción es `{marca}-{tipo}[-{mascota}]`: `royal-canin-special-price-perro`,
 * `boske-special-price`, `advance-cupon`, `farmina-2a-unidad-perro`. Es la dirección
 * (`/promociones/{clave}`), el `link_rewrite` de su categoría y lo que se reutiliza mes a mes
 * aunque cambie la gama. La mascota solo va si toda la promoción es de una. Si la promoción es de
 * varias marcas, `promo-{codigo}`.
 */
class KplClaves
{
    /** Tipo de promoción (el `tipo` de la fila pública de kpycoupons) → trozo de la clave. */
    public const TIPO_CLAVE = [
        'special_price' => 'special-price', 'cupon' => 'cupon', 'segunda_unidad' => '2a-unidad',
        // El regalo con la compra de kpycoupons: «boske-kilos-gratis» o «boske-regalos».
        'kilos_gratis' => 'kilos-gratis', 'regalos' => 'regalos',
    ];

    /** Tipo → trozo de dirección de la página «por tipo» (`/promociones/special-price`). */
    public const TIPO_URL = [
        'special_price' => 'special-price', 'cupon' => 'cupones', 'segunda_unidad' => 'segunda-unidad',
        'kilos_gratis' => 'kilos-gratis', 'regalos' => 'regalos', 'outlet' => 'outlet',
    ];

    /** Las categorías permanentes (no dependen de ninguna promoción): tipo → clave. */
    public const PERMANENTES = ['kilos_gratis' => 'kilos-gratis', 'regalos' => 'regalos', 'outlet' => 'outlet'];

    /** Cómo se llama cada tipo para el cliente. */
    public const NOMBRE_TIPO = [
        'special_price' => 'Special Price', 'cupon' => 'Cupón', 'segunda_unidad' => '2ª unidad',
        'kilos_gratis' => 'Kilos gratis', 'regalos' => 'Regalos', 'outlet' => 'Outlet y liquidación',
    ];

    /** Orden en que salen los tipos en la landing. */
    public const ORDEN_TIPOS = ['special_price', 'cupon', 'segunda_unidad', 'kilos_gratis', 'regalos', 'outlet'];

    /** Mascotas que se distinguen: trozo de dirección → trozo de clave. */
    public const MASCOTAS = ['perros' => 'perro', 'gatos' => 'gato'];

    /** Palabras que no puede ser una clave de promoción (son direcciones de la landing). */
    public const RESERVADAS = ['todas', 'special-price', 'cupones', 'segunda-unidad', 'kilos-gratis', 'regalos', 'outlet', 'perros', 'gatos'];

    /** El tipo de promoción que corresponde a un trozo de dirección, o null. */
    public static function tipoDeUrl(string $trozo): ?string
    {
        $tipo = array_search($trozo, self::TIPO_URL, true);

        return $tipo === false ? null : (string) $tipo;
    }

    /** «Hill's Science Plan» → `hills-science-plan`. Sin apóstrofos ni tildes, como una dirección. */
    public static function slug(string $texto): string
    {
        $texto = str_replace(["'", '’', '`', '´'], '', $texto);
        $slug = Tools::str2url($texto);

        return trim((string) preg_replace('/-+/', '-', $slug), '-');
    }

    /**
     * La clave de una promoción.
     *
     * @param ?string $marca el slug de la marca, o null si es de varias (entonces va el código)
     */
    public static function clave(?string $marca, string $tipo, string $mascota, string $codigo): string
    {
        $tipoClave = self::TIPO_CLAVE[$tipo] ?? self::slug($tipo);
        if ($marca === null || $marca === '') {
            $clave = 'promo-' . self::slug($codigo);
        } else {
            $clave = $marca . '-' . $tipoClave . ($mascota !== '' ? '-' . $mascota : '');
        }
        if (in_array($clave, self::RESERVADAS, true)) {
            $clave .= '-promo';
        }

        return mb_substr($clave, 0, 120);
    }

    /** ¿Es una clave válida para una dirección? */
    public static function claveValida(string $clave): bool
    {
        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $clave) && mb_strlen($clave) <= 120 && !in_array($clave, self::RESERVADAS, true);
    }

    /** «Royal Canin · Special Price · Perros»: el nombre de la categoría en el back office. */
    public static function nombreCategoria(string $marca, string $tipo, string $mascota): string
    {
        $partes = $marca !== '' ? [$marca, self::NOMBRE_TIPO[$tipo] ?? $tipo] : [self::NOMBRE_TIPO[$tipo] ?? $tipo];
        if ($mascota !== '') {
            $partes[] = $mascota === 'perro' ? 'Perros' : 'Gatos';
        }

        return mb_substr(implode(' · ', $partes), 0, 128);
    }

    /** «perros» / «gatos» / «perros y gatos». */
    public static function mascotaTexto(string $mascota, string $sinMascota = 'perros y gatos'): string
    {
        return ['perro' => 'perros', 'gato' => 'gatos'][$mascota] ?? $sinMascota;
    }
}
