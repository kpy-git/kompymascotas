<?php
/**
 * Las promociones de Kompy · Promociones (`kpycoupons`), tal como las cuenta su **hook de consulta**
 * `actionKpyCouponsPromociones` (kpycoupons 3.0.0, 23/10/2026). Este módulo lo dispara y kpycoupons
 * lo contesta: es la única fuente de las promociones en la landing. Desde aquí no se lee ninguna
 * tabla de kpycoupons ni se llama a ninguna de sus funciones (regla de Julián: ningún módulo llama
 * a funciones de otro; lo que hay que saber de otro módulo llega por hooks).
 *
 *   $r = Hook::exec('actionKpyCouponsPromociones', ['id_shop' => $idShop], null, true);
 *   $promos = $r['kpycoupons'] ?? [];
 *
 * La respuesta, por `id_kpy_coupon`, es la **fila pública** de cada promoción (todas las de la
 * tienda, encendidas o no), con claves fijas:
 *   id_kpy_coupon, codigo, nombre, tipo (cupon|segunda_unidad|special_price|regalo), porcentaje,
 *   activo (bool), fecha_desde y fecha_hasta ('Y-m-d H:i:s' o null), etiqueta ('' | liquidacion |
 *   outlet | bajada), estado (viva|programada|terminada|agotada|apagada: lo calcula kpycoupons, una
 *   sola definición para todos), cajon ('' | liquidacion | outlet), regalo_clase (kilos|regalo, solo
 *   en tipo regalo), auto_web, en_ficha, cupo, cupo_lista, cupo_agotado, id_manufacturer (el de su
 *   ámbito de marca, 0 si no hay), ambitos [{tipo, id, valor, unidades, agotado}], items
 *   {clave => id_product} (el reparto: la clave es id_product_attribute, o −id_product sin
 *   combinaciones) y parejas [{clave, id_product, r_id_product, r_ipa, n}] (solo en regalo).
 *
 * Se pregunta una vez por sincronización (y una por petición en la pantalla) y se guarda en
 * estático. Si **nadie contesta** (kpycoupons apagado, o anterior a la 3.0.0) devuelve null y quien
 * sincroniza no toca nada; una lista vacía de verdad (`[]`) sí significa «no hay promociones».
 */
class KplPromociones
{
    public const HOOK = 'actionKpyCouponsPromociones';

    /** Quien contesta: la clave del resultado de `Hook::exec(…, array_return)` es el nombre del módulo. */
    public const MODULO = 'kpycoupons';

    public const ESTADOS = ['viva', 'programada', 'terminada', 'agotada', 'apagada'];

    /** Los canales de `ver_canal` (kpycoupons 3.1.0) con su nombre para la pantalla. */
    public const CANALES = ['shopping' => 'Google', 'email' => 'email', 'social' => 'redes'];

    /**
     * ¿Esta promoción va a la landing? Lo que no se enseña a todo el mundo, no (kpycoupons 3.1.0): un cupón
     * oculto (solo por enlace, `en_ficha` = 0) o solo para quien llega de un canal (`ver_canal`). El «para
     * todos» (`auto_web`) sí: lo ve cualquiera en la ficha de sus productos. Una sola regla para sincronizar
     * y para la pantalla.
     */
    public static function enLaLanding(array $f): bool
    {
        return !empty($f['auto_web']) || (!empty($f['en_ficha']) && (string) ($f['ver_canal'] ?? '') === '');
    }

    /** Por qué no va a la landing, para decirlo en la pantalla ('' si va). */
    public static function porQueFuera(array $f): string
    {
        if (self::enLaLanding($f)) {
            return '';
        }
        if ((string) ($f['ver_canal'] ?? '') !== '') {
            $nombres = array_map(static fn (string $c): string => self::CANALES[$c] ?? $c, array_filter(array_map('trim', explode(',', (string) $f['ver_canal']))));

            return 'solo la ve quien llega de ' . implode(', ', $nombres);
        }

        return 'no se enseña (solo por enlace o código)';
    }

    /** @var array<int, ?array<int, array>> por tienda, lo contestado en esta petición (null = nadie contestó) */
    private static array $cache = [];

    /**
     * Todas las promociones de la tienda, por id, con la fila pública ya normalizada (tipos seguros).
     *
     * @param bool $frescas true = vuelve a preguntar aunque ya se haya preguntado en esta petición
     *                      (al sincronizar: lo que acaba de repartir kpycoupons es lo que tiene que salir)
     *
     * @return ?array<int, array> null si ningún módulo contesta
     */
    public static function todas(int $idTienda, bool $frescas = false): ?array
    {
        if ($frescas || !array_key_exists($idTienda, self::$cache)) {
            self::$cache[$idTienda] = self::preguntar($idTienda);
        }

        return self::$cache[$idTienda];
    }

    public static function olvidar(): void
    {
        self::$cache = [];
    }

    /** Dispara el hook y se queda con la respuesta de kpycoupons (o la primera que sea una lista). */
    private static function preguntar(int $idTienda): ?array
    {
        try {
            $respuestas = Hook::exec(self::HOOK, ['id_shop' => $idTienda], null, true);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpypromolanding, al preguntar las promociones (' . self::HOOK . '): ' . $e->getMessage(), 3, null, 'KplPromociones', 0, true);

            return null;
        }
        if (!is_array($respuestas)) {
            return null;
        }
        $filas = null;
        if (isset($respuestas[self::MODULO]) && is_array($respuestas[self::MODULO])) {
            $filas = $respuestas[self::MODULO];
        } else {
            foreach ($respuestas as $r) {
                if (is_array($r)) {
                    $filas = $r;
                    break;
                }
            }
        }
        if ($filas === null) {
            return null;
        }
        $salida = [];
        foreach ($filas as $f) {
            $p = is_array($f) ? self::normalizar($f) : null;
            if ($p !== null) {
                $salida[$p['id_kpy_coupon']] = $p;
            }
        }
        ksort($salida);

        return $salida;
    }

    /**
     * La fila pública con tipos seguros: es un dato de otro módulo, se comprueba, no se confía. Null
     * si no trae id. Una clave que falte toma su valor neutro (sin estado conocido, «terminada»).
     *
     * @return ?array{id_kpy_coupon: int, codigo: string, nombre: string, tipo: string, porcentaje: float, activo: bool,
     *                fecha_desde: ?string, fecha_hasta: ?string, etiqueta: string, estado: string, cajon: string,
     *                regalo_clase: string, auto_web: bool, en_ficha: bool, cupo: int, cupo_lista: bool, cupo_agotado: ?string,
     *                id_manufacturer: int, tamanos: string, ambitos: array<int, array>, items: array<int, int>, parejas: array<int, array>}
     */
    public static function normalizar(array $f): ?array
    {
        $id = (int) ($f['id_kpy_coupon'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $estado = (string) ($f['estado'] ?? '');
        $ambitos = [];
        foreach ((array) ($f['ambitos'] ?? []) as $a) {
            if (!is_array($a)) {
                continue;
            }
            $ambitos[] = [
                'tipo' => (string) ($a['tipo'] ?? ''),
                'id' => (int) ($a['id'] ?? 0),
                'valor' => (string) ($a['valor'] ?? ''),
                'unidades' => isset($a['unidades']) ? (int) $a['unidades'] : null,
                'agotado' => self::fecha($a['agotado'] ?? null),
            ];
        }
        $items = [];
        foreach ((array) ($f['items'] ?? []) as $clave => $idProducto) {
            if ((int) $idProducto > 0) {
                $items[(int) $clave] = (int) $idProducto;
            }
        }
        $parejas = [];
        foreach ((array) ($f['parejas'] ?? []) as $pr) {
            if (!is_array($pr)) {
                continue;
            }
            $parejas[] = [
                'clave' => (int) ($pr['clave'] ?? 0),
                'id_product' => (int) ($pr['id_product'] ?? 0),
                'r_id_product' => (int) ($pr['r_id_product'] ?? 0),
                'r_ipa' => (int) ($pr['r_ipa'] ?? 0),
                'n' => max(1, (int) ($pr['n'] ?? 1)),
            ];
        }

        return [
            'id_kpy_coupon' => $id,
            'codigo' => (string) ($f['codigo'] ?? ''),
            'nombre' => (string) ($f['nombre'] ?? ''),
            'tipo' => (string) ($f['tipo'] ?? 'cupon'),
            'porcentaje' => (float) ($f['porcentaje'] ?? 0),
            'activo' => !empty($f['activo']),
            'fecha_desde' => self::fecha($f['fecha_desde'] ?? null),
            'fecha_hasta' => self::fecha($f['fecha_hasta'] ?? null),
            'etiqueta' => (string) ($f['etiqueta'] ?? ''),
            'estado' => in_array($estado, self::ESTADOS, true) ? $estado : 'terminada',
            'cajon' => (string) ($f['cajon'] ?? ''),
            'regalo_clase' => (string) ($f['regalo_clase'] ?? ''),
            'auto_web' => !empty($f['auto_web']),
            'en_ficha' => !isset($f['en_ficha']) || !empty($f['en_ficha']),
            // kpycoupons 3.1.0: desde qué canales se ve; vacío = en toda la tienda (o la fila es anterior).
            'ver_canal' => (string) ($f['ver_canal'] ?? ''),
            'cupo' => (int) ($f['cupo'] ?? 0),
            'cupo_lista' => !empty($f['cupo_lista']),
            'cupo_agotado' => self::fecha($f['cupo_agotado'] ?? null),
            'id_manufacturer' => (int) ($f['id_manufacturer'] ?? 0),
            // Los tamaños elegidos («saco 12 kg», uno por línea) no van en la fila pública de la
            // 3.0.0: si algún día van, el título del banner («−10 % en sacos de 8 a 15 kg») los usa.
            'tamanos' => (string) ($f['tamanos'] ?? ''),
            'ambitos' => $ambitos,
            'items' => $items,
            'parejas' => $parejas,
        ];
    }

    private static function fecha($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' || strpos($valor, '0000-00-00') === 0 ? null : $valor;
    }
}
