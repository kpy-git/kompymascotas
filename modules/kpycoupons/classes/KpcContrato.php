<?php
/**
 * Lo que este módulo cuenta a los demás por sus hooks (3.0.0, 23/10/2026): **datos curados y
 * versionados**, no filas crudas de `kpy_coupon` ni objetos `KpcCupon`. Una columna nueva no cambia
 * el contrato; una clave nueva se añade sin quitar las que hay (`VERSION` sube si se quita alguna).
 *
 * Regla de Julián: ningún módulo llama a funciones de otro. Lo que otro módulo necesita saber de las
 * promociones le llega por aquí, en tres hooks:
 *
 *   actionKpyCouponsReparto      (aviso, lo dispara este módulo al final de cada reparto)
 *                                 version, id_shop, items, avisos, alcance, promociones, guardada
 *   actionKpyCouponsGuardar      (aviso, al guardar una promoción en la pantalla)
 *                                 version, id, valores, promocion
 *   actionKpyCouponsAlcance      (consulta, la dispara quien quiera: kpysupply)
 *                                 id, id_shop → entran, especial, promocion
 *   actionKpyCouponsPromociones  (consulta, la dispara quien quiera: kpypromolanding)
 *                                 id_shop → por id: la fila pública con estado, cajón, items, parejas…
 *
 * La fila pública (`promocion()`), clave a clave, está en el README («Contratos públicos»).
 */
class KpcContrato
{
    public const VERSION = 1;

    /**
     * La fila pública de una promoción.
     *
     * @param array $c la fila de `kpy_coupon` (con `ambitos` si ya se han leído)
     * @param ?array $ambitos sus ámbitos, si no vienen en la fila
     *
     * @return array{id_kpy_coupon: int, codigo: string, nombre: string, tipo: string, porcentaje: float,
     *               activo: bool, fecha_desde: ?string, fecha_hasta: ?string, etiqueta: string, estado: string,
     *               cajon: string, regalo_clase: string, auto_web: bool, en_ficha: bool, ver_canal: string, cupo: int, cupo_lista: bool,
     *               cupo_agotado: ?string, id_manufacturer: int, ambitos: array<int, array{tipo: string, id: int, valor: string, unidades: ?int, agotado: ?string}>}
     */
    public static function promocion(array $c, ?array $ambitos = null): array
    {
        $id = (int) ($c['id_kpy_coupon'] ?? 0);
        $tipo = (string) ($c['tipo'] ?? 'cupon');
        $ambitos = $ambitos ?? (is_array($c['ambitos'] ?? null) ? $c['ambitos'] : ($id > 0 ? (KpcCupon::ambitosDe([$id])[$id] ?? []) : []));
        $limpios = [];
        $marca = 0;
        foreach ($ambitos as $a) {
            $a = (array) $a;
            $limpios[] = [
                'tipo' => (string) ($a['tipo'] ?? ''),
                'id' => (int) ($a['id'] ?? $a['id_ref'] ?? 0),
                'valor' => (string) ($a['valor'] ?? ''),
                'unidades' => isset($a['unidades']) && $a['unidades'] !== null ? (int) $a['unidades'] : null,
                'agotado' => KpcCupon::fechaValida($a['agotado'] ?? null),
            ];
            if ($marca === 0 && (string) ($a['tipo'] ?? '') === 'marca') {
                $marca = (int) ($a['id'] ?? $a['id_ref'] ?? 0);
            }
        }

        return [
            'id_kpy_coupon' => $id,
            'codigo' => (string) ($c['codigo'] ?? ''),
            'nombre' => (string) ($c['nombre'] ?? ''),
            'tipo' => $tipo,
            'porcentaje' => (float) ($c['porcentaje'] ?? 0),
            'activo' => (int) ($c['activo'] ?? 0) === 1,
            'fecha_desde' => KpcCupon::fechaValida($c['fecha_desde'] ?? null),
            'fecha_hasta' => KpcCupon::fechaValida($c['fecha_hasta'] ?? null),
            'etiqueta' => (string) ($c['etiqueta'] ?? ''),
            'estado' => self::estado($c),
            'cajon' => KpcCupo::esCajon((string) ($c['etiqueta'] ?? '')) ? (string) $c['etiqueta'] : '',
            'regalo_clase' => $tipo === 'regalo' ? self::claseRegalo($id, (int) ($c['id_shop'] ?? 0)) : '',
            'auto_web' => !empty($c['auto_web']),
            'en_ficha' => (int) ($c['en_ficha'] ?? 1) === 1,
            // 3.1.0: desde qué canales se ve («shopping,email»); vacío = en toda la tienda, si en_ficha.
            'ver_canal' => (string) ($c['ver_canal'] ?? ''),
            'cupo' => (int) ($c['cupo'] ?? 0),
            'cupo_lista' => (int) ($c['cupo_lista'] ?? 0) === 1,
            'cupo_agotado' => KpcCupon::fechaValida($c['cupo_agotado'] ?? null),
            'id_manufacturer' => $marca,
            // Los tamaños y niveles de pack elegidos («saco 12 kg», «1,2»), tal como se escribieron: la landing
            // titula con ellos («−10 % en sacos de 8 a 15 kg»).
            'tamanos' => (string) ($c['tamanos'] ?? ''),
            'packs' => (string) ($c['packs'] ?? ''),
            'ambitos' => $limpios,
        ];
    }

    /**
     * En qué está una promoción: `apagada` (sin encender), `programada` (empieza más tarde), `viva`,
     * `agotada` (vendió su cupo) o `terminada` (pasó su fecha). Una sola definición para todos los
     * módulos (hasta la 2.4 la landing la copiaba en SQL en tres sitios).
     */
    public static function estado(array $c, ?int $ahora = null): string
    {
        $ahora = $ahora ?? time();
        if ((int) ($c['activo'] ?? 0) !== 1) {
            return 'apagada';
        }
        $hasta = KpcCupon::fechaValida($c['fecha_hasta'] ?? null);
        if ($hasta !== null && strtotime($hasta) < $ahora) {
            return 'terminada';
        }
        if (KpcCupon::fechaValida($c['cupo_agotado'] ?? null) !== null && ((int) ($c['cupo'] ?? 0) > 0 || (int) ($c['cupo_lista'] ?? 0) === 1)) {
            return 'agotada';
        }
        $desde = KpcCupon::fechaValida($c['fecha_desde'] ?? null);
        if ($desde !== null && strtotime($desde) > $ahora) {
            return 'programada';
        }

        return 'viva';
    }

    /**
     * Qué regala una promoción de regalo: `kilos` (el formato pequeño de la misma ficha: «12 kg + 3 kg
     * gratis») o `regalo` (otra cosa: latas, un snack, una carda). Por sus parejas.
     */
    public static function claseRegalo(int $id, int $idTienda = 0): string
    {
        if ($id <= 0) {
            return '';
        }
        $mismo = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo WHERE id_kpy_coupon = ' . $id . ' AND r_id_product = id_product'
        );
        $total = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo WHERE id_kpy_coupon = ' . $id
        );
        if ($total === 0) {
            return 'kilos';
        }

        return $mismo * 2 >= $total ? 'kilos' : 'regalo';
    }

    /**
     * Las parejas de una promoción de regalo: qué formato disparador regala qué.
     *
     * @return array<int, array{clave: int, id_product: int, r_id_product: int, r_ipa: int, n: int}>
     */
    public static function parejas(int $id): array
    {
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT clave, id_product, r_id_product, r_ipa, n FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo WHERE id_kpy_coupon = ' . $id . ' ORDER BY clave'
        ) ?: [] as $f) {
            $salida[] = ['clave' => (int) $f['clave'], 'id_product' => (int) $f['id_product'], 'r_id_product' => (int) $f['r_id_product'], 'r_ipa' => (int) $f['r_ipa'], 'n' => max(1, (int) $f['n'])];
        }

        return $salida;
    }

    /**
     * Lo que contesta `actionKpyCouponsPromociones`: todas las promociones de la tienda (encendidas o
     * no), por id, con su fila pública, sus artículos del reparto (`items`: clave ⇒ id_product; la
     * clave es `id_product_attribute`, o −id_product sin combinaciones) y, en un regalo, sus parejas.
     *
     * @return array<int, array>
     */
    public static function promociones(int $idTienda): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $filas = $db->executeS('SELECT * FROM ' . $p . 'kpy_coupon WHERE id_shop = ' . $idTienda . ' ORDER BY id_kpy_coupon') ?: [];
        if ($filas === []) {
            return [];
        }
        $ids = array_map(static fn (array $f): int => (int) $f['id_kpy_coupon'], $filas);
        $ambitos = KpcCupon::ambitosDe($ids);
        $items = [];
        foreach ($db->executeS('SELECT id_kpy_coupon, clave, id_product FROM ' . $p . 'kpy_coupon_item WHERE id_kpy_coupon IN (' . implode(',', $ids) . ')') ?: [] as $f) {
            $items[(int) $f['id_kpy_coupon']][(int) $f['clave']] = (int) $f['id_product'];
        }
        $salida = [];
        foreach ($filas as $f) {
            $id = (int) $f['id_kpy_coupon'];
            $fila = self::promocion($f, $ambitos[$id] ?? []);
            $fila['items'] = $items[$id] ?? [];
            $fila['parejas'] = $fila['tipo'] === 'regalo' ? self::parejas($id) : [];
            $salida[$id] = $fila;
        }

        return $salida;
    }

    /**
     * Lo que contesta `actionKpyCouponsAlcance`: a qué artículos llega una promoción, tal como lo
     * calcula `KpcAlcance` (`entran`) y los que se quedan apartados por tener otro Special Price
     * (`especial`), con la clave del artículo ⇒ id_product; y su fila pública. Null si no existe.
     *
     * @return ?array{version: int, entran: array<int, int>, especial: array<int, int>, promocion: array}
     */
    public static function alcance(int $id, int $idTienda): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $fila = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon = ' . $id . ($idTienda > 0 ? ' AND id_shop = ' . $idTienda : ''));
        if (!is_array($fila) || $fila === []) {
            return null;
        }
        $fila['ambitos'] = KpcCupon::ambitosDe([$id])[$id] ?? [];
        $articulos = KpcAlcance::articulos($fila, (int) $fila['id_shop']);

        return [
            'version' => self::VERSION,
            'entran' => array_map('intval', (array) ($articulos['entran'] ?? [])),
            'especial' => array_map('intval', (array) ($articulos['especial'] ?? [])),
            'promocion' => self::promocion($fila),
        ];
    }
}
