<?php
/**
 * La capa de campaña (Black Friday, Cyber Monday, Reyes…) sobre lo que pinta este módulo: la ficha
 * (`KpcFicha`), las filas de la cesta y la ventana «Añadido» (`KpcLineas`).
 *
 * Este módulo no sabe de campañas: las pregunta por su hook de consulta `actionKpyCouponsCampana`
 * (lo contesta `kpypromolanding` si hay una campaña viva que incluya el producto) y se queda con la
 * primera respuesta que traiga `tipos`. Una vez por producto, formato y tipos en la petición (la
 * cesta pinta cada línea dos veces y la ficha pregunta desde varios hooks).
 *
 * Contrato (README, «Hooks propios»): params `id_product`, `id_product_attribute`, `tipos`
 * (string[]: cupon, segunda_unidad, special_price) y, desde la 3.2.0, `pct` (por tipo, el % que
 * rebaja de verdad: para un Special Price cargado por SQL, que la landing no conoce). Respuesta: null
 * o {nombre, clave, piel, tipos ⇒ {label, background, color}}.
 */
class KpcCampana
{
    /** @var array<string, ?array> por producto, formato, tipos y porcentajes */
    private static array $cache = [];

    /**
     * @param string[] $tipos los tipos de promoción que hay en pantalla (vacío = nada que preguntar)
     * @param array<string, float> $pct por tipo, el % que rebaja de verdad (opcional)
     *
     * @return ?array{nombre: string, clave: string, piel: string, tipos: array<string, array{label: string, background: string, color: string}>}
     */
    public static function de(int $idProducto, int $ipa, array $tipos, array $pct = []): ?array
    {
        $tipos = array_values(array_unique(array_filter(array_map('strval', $tipos))));
        if ($tipos === [] || $idProducto <= 0) {
            return null;
        }
        sort($tipos);
        ksort($pct);
        $firma = $idProducto . '|' . $ipa . '|' . implode(',', $tipos) . '|' . json_encode($pct);
        if (array_key_exists($firma, self::$cache)) {
            return self::$cache[$firma];
        }
        $salida = null;
        try {
            $respuestas = Hook::exec('actionKpyCouponsCampana', [
                'id_product' => $idProducto,
                'id_product_attribute' => $ipa,
                'tipos' => $tipos,
                'pct' => $pct,
            ], null, true);
            foreach ((array) ($respuestas ?: []) as $r) {
                if (is_array($r) && !empty($r['tipos']) && is_array($r['tipos'])) {
                    $salida = $r;
                    break;
                }
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al preguntar por la campaña (actionKpyCouponsCampana): ' . $e->getMessage(), 2, null, 'Product', $idProducto, true);
        }

        return self::$cache[$firma] = $salida;
    }

    /**
     * La etiqueta de campaña de un tipo, si la respuesta la trae con nombre y colores válidos
     * (`#rrggbb`): lo que otro módulo contesta se comprueba antes de imprimirlo en un `style=`.
     *
     * @return ?array{label: string, background: string, color: string}
     */
    public static function etiqueta(?array $campana, string $tipo): ?array
    {
        $e = $campana['tipos'][$tipo] ?? null;
        if (!is_array($e) || trim((string) ($e['label'] ?? '')) === '') {
            return null;
        }
        $bg = KpcFicha::color($e['background'] ?? '', '');
        $fg = KpcFicha::color($e['color'] ?? '', '');
        if ($bg === '' || $fg === '') {
            return null;
        }

        return ['label' => trim((string) $e['label']), 'background' => $bg, 'color' => $fg];
    }

    /** El tipo de promoción (el del hook) de un tipo de bloque o chip nuestro; vacío si no lleva capa. */
    public static function tipoDe(string $tipo): string
    {
        return [
            'cupon' => 'cupon', 'kpc-cupon' => 'cupon',
            'segunda' => 'segunda_unidad', 'kpc-segunda' => 'segunda_unidad',
            'especial' => 'special_price', 'liquidacion' => 'special_price', 'outlet' => 'special_price', 'bajada' => 'special_price',
            'kpc-especial' => 'special_price', 'kpc-liquidacion' => 'special_price', 'kpc-outlet' => 'special_price', 'kpc-bajada' => 'special_price',
            'kpy-special-price' => 'special_price',
        ][$tipo] ?? '';
    }

    /** Olvida lo preguntado en esta petición (pruebas, repartos). */
    public static function olvidar(): void
    {
        self::$cache = [];
    }
}
