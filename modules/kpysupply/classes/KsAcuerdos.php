<?php
/**
 * Los acuerdos: las filas de `kpy_supplier_promo_products` (una por artículo, con su tipo, su
 * valor y sus fechas), agrupadas en **lotes** para la pantalla (tipo + fechas + promoción +
 * marca), y todo lo que se escribe: guardar una lista comprobada (con sus solapes), cambiar las
 * fechas de un lote, borrar, y el acuerdo de una promoción de kpycoupons.
 *
 * Tres reglas:
 *   - «Hasta nueva orden» se guarda como `SIN_FIN` (2099-12-31 23:59:59, como las promociones sin
 *     fin de kpycoupons) y no como NULL: MySQL deja repetir filas con NULL en una clave única, y la
 *     de Julián (artículo + fechas) tiene que valer siempre. Hacia fuera de esta clase, `null`.
 *   - Cada fila dice de dónde viene (`origen`): `promo` = la puso el paso «Acuerdo con el
 *     proveedor» de una promoción (el mismo % para todos sus artículos); `lista` = se subió con una
 *     lista (cada artículo con su valor). **La lista manda**: el paso no pisa ni duplica un artículo
 *     que ya tiene fila de lista.
 *   - Lo marcado en el paso de cada promoción se guarda aparte (`kpy_supplier_promo_coupon`): así
 *     la promoción puede dárselo a sus artículos nuevos y el paso lo enseña aunque aún no tenga
 *     ninguno.
 */
class KsAcuerdos
{
    public const TABLA = 'kpy_supplier_promo_products';
    public const TABLA_PROMO = 'kpy_supplier_promo_coupon';

    /** «Hasta nueva orden». Cualquier fecha desde el 2099 se lee como sin fin. */
    public const SIN_FIN = '2099-12-31 23:59:59';

    public const ORIGEN_PROMO = 'promo';
    public const ORIGEN_LISTA = 'lista';

    // ---------- fechas ----------

    /** Lo que se escribe en `date_to`: la fecha, o `SIN_FIN`. */
    public static function hastaDb(?string $hasta): string
    {
        return $hasta === null || $hasta === '' ? self::SIN_FIN : $hasta;
    }

    /** Lo que se lee de `date_to`: la fecha, o null si es sin fin. */
    public static function hastaDe($valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' || $valor >= '2099-01-01' ? null : $valor;
    }

    /** «del 01/11/2026 al 30/11/2026» o «desde el 01/10/2026, hasta nueva orden». */
    public static function fechasTexto(string $desde, ?string $hasta): string
    {
        $d = date('d/m/Y', strtotime($desde));

        return $hasta === null ? 'desde el ' . $d . ', hasta nueva orden' : 'del ' . $d . ' al ' . date('d/m/Y', strtotime($hasta));
    }

    /**
     * Una fecha de verdad, o null si está vacía o es la «0000-00-00» de la base de datos. (Copia de
     * `KpcCupon::fechaValida()` de kpycoupons: tres líneas que no justifican llamar a otro módulo.)
     */
    public static function fechaValida($fecha): ?string
    {
        $fecha = trim((string) $fecha);

        return $fecha === '' || strpos($fecha, '0000-00-00') === 0 || strtotime($fecha) === false ? null : $fecha;
    }

    // ---------- lotes ----------

    /**
     * La clave de un lote en las direcciones: tipo, fechas (en cifras, `AAAAMMDDHHMMSS`; 0 = sin
     * fin), promoción y marca. Sin pasar por segundos: así no depende de la zona horaria del servidor.
     */
    public static function claveLote(int $idTipo, string $desde, ?string $hasta, int $idPromo, int $idMarca): string
    {
        return implode('_', [$idTipo, self::compacta($desde), $hasta !== null ? self::compacta($hasta) : '0', $idPromo, $idMarca]);
    }

    /** «2026-11-01 00:00:00» → «20261101000000». */
    private static function compacta(string $fecha): string
    {
        return substr(preg_replace('/\D/', '', $fecha) ?? '', 0, 14);
    }

    /** «20261101000000» → «2026-11-01 00:00:00», o null si no es una fecha. */
    private static function expande(string $cifras): ?string
    {
        if (!preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $cifras, $m)) {
            return null;
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[6] > 59) {
            return null;
        }

        return "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}";
    }

    /** El lote de una clave (null si la clave no es de un lote). */
    public static function lote(string $clave): ?array
    {
        if (!preg_match('/^(\d+)_(\d{14})_(\d{14}|0)_(\d+)_(\d+)$/', $clave, $m) || KsTipos::clave((int) $m[1]) === '') {
            return null;
        }
        $desde = self::expande($m[2]);
        $hasta = $m[3] === '0' ? null : self::expande($m[3]);
        if ($desde === null || ($m[3] !== '0' && $hasta === null)) {
            return null;
        }

        return self::describir([
            'id_supplier_promo_type' => (int) $m[1],
            'date_from' => $desde,
            'date_to' => self::hastaDb($hasta),
            'id_kpy_coupon' => (int) $m[4],
            'id_manufacturer' => (int) $m[5],
        ]);
    }

    // ---------- escribir sin dejar nada a medias ----------

    /** @var bool hay una transacción abierta por esta clase (MySQL no las anida) */
    private static bool $enTransaccion = false;

    /**
     * Ejecuta `$f` dentro de una transacción: o se escribe todo o nada (una lista a medias sería
     * peor que ninguna). Si esta clase ya tiene una abierta, se sigue en la misma.
     *
     * @return mixed lo que devuelva `$f`
     */
    public static function transaccion(callable $f)
    {
        if (self::$enTransaccion) {
            return $f();
        }
        $db = Db::getInstance();
        self::$enTransaccion = true;
        $db->execute('START TRANSACTION');
        try {
            $salida = $f();
            $db->execute('COMMIT');
        } catch (Throwable $e) {
            $db->execute('ROLLBACK');
            throw $e;
        } finally {
            self::$enTransaccion = false;
        }

        return $salida;
    }

    /**
     * Un `insert`, `update` o `delete` que devuelve false (fuera del modo desarrollo PrestaShop no
     * lanza nada: solo devuelve false): se para aquí, y la transacción deshace lo anterior.
     *
     * @param mixed $resultado
     */
    private static function ok($resultado, string $que): void
    {
        if ($resultado === false) {
            throw new RuntimeException('No se ha podido ' . $que . ': ' . (Db::getInstance()->getMsgError() ?: 'la base de datos no lo ha aceptado'));
        }
    }

    /** Un lote con lo que las pantallas necesitan saber de él. */
    private static function describir(array $l, ?string $ahora = null): array
    {
        $ahora = $ahora ?: date('Y-m-d H:i:s');
        $l['tipo'] = KsTipos::clave((int) $l['id_supplier_promo_type']);
        $l['tipo_titulo'] = KsTipos::titulo($l['tipo']);
        $l['desde'] = (string) $l['date_from'];
        $l['hasta'] = self::hastaDe($l['date_to'] ?? null);
        $l['id_kpy_coupon'] = (int) ($l['id_kpy_coupon'] ?? 0);
        $l['id_manufacturer'] = (int) ($l['id_manufacturer'] ?? 0);
        $l['clave'] = self::claveLote((int) $l['id_supplier_promo_type'], $l['desde'], $l['hasta'], $l['id_kpy_coupon'], $l['id_manufacturer']);
        $l['estado'] = $l['desde'] > $ahora ? 'programado' : ($l['hasta'] !== null && $l['hasta'] < $ahora ? 'terminado' : 'vivo');
        $l['sin_fin'] = $l['hasta'] === null;
        $l['fechas'] = self::fechasTexto($l['desde'], $l['hasta']);
        // Para la tabla: en una línea.
        $l['fechas_corto'] = $l['hasta'] === null ? 'desde ' . date('d/m/Y', strtotime($l['desde'])) : date('d/m/Y', strtotime($l['desde'])) . ' – ' . date('d/m/Y', strtotime($l['hasta']));

        return $l;
    }

    /** SQL: la condición de un lote (alias `s` para la tabla y `p` para el producto). */
    public static function condicion(array $lote): string
    {
        return 's.id_supplier_promo_type = ' . (int) $lote['id_supplier_promo_type']
            . " AND s.date_from = '" . pSQL((string) $lote['desde']) . "'"
            . " AND s.date_to = '" . pSQL(self::hastaDb($lote['hasta'])) . "'"
            . ' AND IFNULL(s.id_kpy_coupon, 0) = ' . (int) $lote['id_kpy_coupon']
            . ' AND p.id_manufacturer = ' . (int) $lote['id_manufacturer'];
    }

    /** Todos los lotes con sus cuentas, los más recientes primero. */
    public static function lotes(): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $filas = $db->executeS(
            "SELECT s.id_supplier_promo_type, s.date_from, s.date_to, IFNULL(s.id_kpy_coupon, 0) AS id_kpy_coupon,
                    p.id_manufacturer, MIN(IFNULL(m.name, '')) AS marca, COUNT(*) AS n, SUM(s.origen = 'lista') AS n_lista,
                    MIN(s.value) AS v_min, MAX(s.value) AS v_max, MIN(s.date_add) AS date_add, MAX(s.date_upd) AS date_upd
             FROM {$p}" . self::TABLA . " s
             INNER JOIN {$p}product p ON p.id_product = s.id_product
             LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
             GROUP BY s.id_supplier_promo_type, s.date_from, s.date_to, IFNULL(s.id_kpy_coupon, 0), p.id_manufacturer
             ORDER BY s.date_from DESC, marca ASC"
        ) ?: [];
        $idsPromo = array_values(array_unique(array_filter(array_map('intval', array_column($filas, 'id_kpy_coupon')))));
        $promos = [];
        if ($idsPromo !== []) {
            foreach ($db->executeS('SELECT id_kpy_coupon, codigo, nombre, tipo, activo FROM ' . $p . 'kpy_coupon WHERE id_kpy_coupon IN (' . implode(',', $idsPromo) . ')') ?: [] as $f) {
                $promos[(int) $f['id_kpy_coupon']] = $f + ['borrada' => false];
            }
        }
        $solapes = self::solapesPorLote();
        $ahora = date('Y-m-d H:i:s');
        $salida = [];
        foreach ($filas as $f) {
            $l = self::describir($f, $ahora);
            $l['n'] = (int) $f['n'];
            $l['n_lista'] = (int) $f['n_lista'];
            $l['marca'] = (string) $f['marca'];
            $vMin = (float) $f['v_min'];
            $vMax = (float) $f['v_max'];
            if (abs($vMin - $vMax) < 0.000001) {
                $l['valor_texto'] = KsTipos::valorTexto($l['tipo'], $vMin);
            } elseif ($l['tipo'] === KsTipos::COSTE) {
                $l['valor_texto'] = 'precio propio por artículo';
            } else {
                $l['valor_texto'] = 'de ' . KsTipos::valorTexto($l['tipo'], $vMin) . ' a ' . KsTipos::valorTexto($l['tipo'], $vMax);
            }
            $l['promo'] = null;
            if ($l['id_kpy_coupon'] > 0) {
                $l['promo'] = $promos[$l['id_kpy_coupon']] ?? ['id_kpy_coupon' => $l['id_kpy_coupon'], 'codigo' => '#' . $l['id_kpy_coupon'], 'nombre' => 'promoción borrada', 'tipo' => '', 'activo' => 0, 'borrada' => true];
            }
            $l['solapes'] = (int) ($solapes[$l['clave']] ?? 0);
            $l['date_add'] = (string) $f['date_add'];
            $l['date_upd'] = (string) $f['date_upd'];
            $salida[] = $l;
        }

        return $salida;
    }

    /**
     * Cuántos artículos de cada lote tienen otra fila que se cruza en fechas (del tipo que sea).
     *
     * @return array<string, int> clave del lote => artículos con solape
     */
    private static function solapesPorLote(): array
    {
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance()->executeS(
            "SELECT a.id_supplier_promo_type, a.date_from, a.date_to, IFNULL(a.id_kpy_coupon, 0) AS id_kpy_coupon, p.id_manufacturer, COUNT(DISTINCT a.id) AS n
             FROM {$p}" . self::TABLA . " a
             INNER JOIN {$p}" . self::TABLA . " b ON b.id <> a.id AND b.id_product = a.id_product
                AND (a.id_product_attribute = 0 OR b.id_product_attribute = 0 OR a.id_product_attribute = b.id_product_attribute)
                AND a.date_to >= b.date_from AND b.date_to >= a.date_from
             INNER JOIN {$p}product p ON p.id_product = a.id_product
             GROUP BY a.id_supplier_promo_type, a.date_from, a.date_to, IFNULL(a.id_kpy_coupon, 0), p.id_manufacturer"
        ) ?: [] as $f) {
            $salida[self::claveLote((int) $f['id_supplier_promo_type'], (string) $f['date_from'], self::hastaDe($f['date_to']), (int) $f['id_kpy_coupon'], (int) $f['id_manufacturer'])] = (int) $f['n'];
        }

        return $salida;
    }

    /** Las filas de un lote, con el nombre, el formato, los códigos y el coste de cada artículo. */
    public static function filasDeLote(array $lote): array
    {
        $p = _DB_PREFIX_;
        $filas = Db::getInstance()->executeS(
            "SELECT s.* FROM {$p}" . self::TABLA . " s INNER JOIN {$p}product p ON p.id_product = s.id_product WHERE " . self::condicion($lote)
        ) ?: [];
        if ($filas === []) {
            return [];
        }
        $datos = self::datosArticulos(array_map(static fn (array $f): array => [(int) $f['id_product'], (int) $f['id_product_attribute']], $filas));
        foreach ($filas as $i => $f) {
            $filas[$i] = $f + ($datos[(int) $f['id_product'] . '-' . (int) $f['id_product_attribute']] ?? []);
            $filas[$i]['valor_texto'] = KsTipos::valorTexto((string) $lote['tipo'], (float) $f['value']);
            $filas[$i]['date_to'] = self::hastaDe($f['date_to']);
        }
        usort($filas, static fn (array $a, array $b): int => strcasecmp((string) ($a['producto'] ?? ''), (string) ($b['producto'] ?? '')) ?: strnatcasecmp((string) ($a['formato'] ?? ''), (string) ($b['formato'] ?? '')));

        return $filas;
    }

    /**
     * Nombre, formato, EAN, referencia, coste y marca de unos artículos.
     *
     * @param array<int, array{0: int, 1: int}> $pares [id de producto, id de combinación (0 = el producto)]
     *
     * @return array<string, array> clave «producto-ipa» => datos
     */
    public static function datosArticulos(array $pares): array
    {
        $productos = [];
        $ipas = [];
        foreach ($pares as [$id, $ipa]) {
            $productos[(int) $id] = true;
            if ((int) $ipa > 0) {
                $ipas[(int) $ipa] = true;
            }
        }
        if ($productos === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $tienda = (int) (Context::getContext()->shop->id ?? 0) ?: (int) Configuration::get('PS_SHOP_DEFAULT');
        $prod = [];
        foreach (array_chunk(array_keys($productos), 1000) as $trozo) {
            foreach ($db->executeS(
                "SELECT p.id_product, p.ean13, p.reference, p.wholesale_price, p.id_manufacturer, IFNULL(m.name, '') AS marca, IFNULL(pl.name, '') AS nombre
                 FROM {$p}product p
                 LEFT JOIN {$p}product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = {$idioma} AND pl.id_shop = {$tienda}
                 LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
                 WHERE p.id_product IN (" . implode(',', $trozo) . ')'
            ) ?: [] as $f) {
                $prod[(int) $f['id_product']] = $f;
            }
        }
        $comb = [];
        foreach (array_chunk(array_keys($ipas), 1000) as $trozo) {
            foreach ($db->executeS(
                "SELECT pa.id_product_attribute AS ipa, pa.id_product, pa.ean13, pa.reference, pa.wholesale_price,
                        GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS formato
                 FROM {$p}product_attribute pa
                 LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                 LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idioma}
                 WHERE pa.id_product_attribute IN (" . implode(',', $trozo) . ')
                 GROUP BY pa.id_product_attribute'
            ) ?: [] as $f) {
                $comb[(int) $f['ipa']] = $f;
            }
        }
        $salida = [];
        foreach ($pares as [$id, $ipa]) {
            $id = (int) $id;
            $ipa = (int) $ipa;
            $pr = $prod[$id] ?? null;
            $c = $ipa > 0 ? ($comb[$ipa] ?? null) : null;
            $salida[$id . '-' . $ipa] = [
                'id_product' => $id,
                'ipa' => $ipa,
                'existe' => $pr !== null,
                'producto' => $pr !== null && (string) $pr['nombre'] !== '' ? (string) $pr['nombre'] : 'producto #' . $id,
                'formato' => (string) ($c['formato'] ?? ''),
                'ean' => (string) ($c !== null ? $c['ean13'] : ($pr['ean13'] ?? '')),
                'ref' => (string) ($c !== null ? $c['reference'] : ($pr['reference'] ?? '')),
                'coste' => (float) ($c !== null ? $c['wholesale_price'] : ($pr['wholesale_price'] ?? 0)),
                'marca' => (string) ($pr['marca'] ?? ''),
                'id_manufacturer' => (int) ($pr['id_manufacturer'] ?? 0),
            ];
        }

        return $salida;
    }

    // ---------- guardar una lista ----------

    /**
     * Las filas que ya hay de unos artículos y se cruzan con un periodo (para los solapes), con
     * `date_to` ya leído (null = sin fin).
     */
    public static function existentes(array $items, string $desde, ?string $hasta): array
    {
        $productos = array_values(array_unique(array_map(static fn ($i): int => (int) $i['id_product'], $items)));
        if ($productos === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (array_chunk($productos, 1000) as $trozo) {
            foreach (Db::getInstance()->executeS(
                "SELECT s.* FROM {$p}" . self::TABLA . ' s WHERE s.id_product IN (' . implode(',', $trozo) . ")
                 AND s.date_to >= '" . pSQL($desde) . "' AND s.date_from <= '" . pSQL(self::hastaDb($hasta)) . "'"
            ) ?: [] as $f) {
                $f['date_to'] = self::hastaDe($f['date_to']);
                $salida[] = $f;
            }
        }

        return $salida;
    }

    /**
     * Guarda una lista comprobada: una fila por artículo, de origen `lista`. Para cada artículo, lo
     * que ya hay y se cruza con el periodo:
     *   - nada: fila nueva;
     *   - solo la misma fila (mismas fechas) con el mismo tipo y valor: no se toca;
     *   - otra cosa: con `saltar` (o `parar`), el artículo no se guarda; con `sustituir`, la misma
     *     fila se actualiza y las demás se recortan (o se quitan si quedan dentro) para que no se
     *     pisen.
     * Con promoción, la fila se enlaza a ella (sigue sus fechas). Sin promoción, una fila que ya
     * era de una promoción sigue enlazada a esa promoción.
     *
     * @param array<int, array{id_product: int, ipa: int, valor: float}> $items
     *
     * @return array{guardados: int, actualizados: int, iguales: int, saltados: int, recortados: int, borrados: int}
     */
    public static function guardar(array $items, int $idTipo, string $desde, ?string $hasta, ?int $idPromo, string $politica): array
    {
        $r = ['guardados' => 0, 'actualizados' => 0, 'iguales' => 0, 'saltados' => 0, 'recortados' => 0, 'borrados' => 0];
        if ($items === [] || $idTipo <= 0) {
            return $r;
        }

        return self::transaccion(static fn (): array => self::guardarDentro($items, $idTipo, $desde, $hasta, $idPromo, $politica, $r));
    }

    private static function guardarDentro(array $items, int $idTipo, string $desde, ?string $hasta, ?int $idPromo, string $politica, array $r): array
    {
        $db = Db::getInstance();
        $ahora = date('Y-m-d H:i:s');
        $solapes = KsLista::solapes($items, $desde, $hasta, self::existentes($items, $desde, $hasta));
        foreach ($items as $it) {
            $idProducto = (int) $it['id_product'];
            $ipa = (int) $it['ipa'];
            $valor = (float) $it['valor'];
            $misma = null;
            $otras = [];
            foreach ($solapes[$idProducto . '-' . $ipa] ?? [] as $f) {
                if ((int) $f['id_product_attribute'] === $ipa && (string) $f['date_from'] === $desde && $f['date_to'] === $hasta) {
                    $misma = $f;
                } else {
                    $otras[] = $f;
                }
            }
            $promoFila = $idPromo !== null && $idPromo > 0 ? $idPromo : ($misma !== null && (int) $misma['id_kpy_coupon'] > 0 ? (int) $misma['id_kpy_coupon'] : 0);
            if ($otras === [] && $misma !== null && KsLista::igual($misma, $idTipo, $valor, $desde, $hasta)
                && (int) $misma['id_kpy_coupon'] === $promoFila && (string) $misma['origen'] === self::ORIGEN_LISTA) {
                ++$r['iguales'];
                continue;
            }
            $conflicto = $otras !== [] || ($misma !== null && !KsLista::igual($misma, $idTipo, $valor, $desde, $hasta));
            if ($conflicto && $politica !== 'sustituir') {
                ++$r['saltados'];
                continue;
            }
            foreach ($otras as $f) {
                $rec = KsLista::recorte($f, $desde, $hasta);
                if ($rec['accion'] === 'borrar') {
                    self::ok($db->delete(self::TABLA, 'id = ' . (int) $f['id']), 'quitar un acuerdo anterior');
                    ++$r['borrados'];
                    continue;
                }
                $campos = ['date_upd' => $ahora];
                if (array_key_exists('date_to', $rec)) {
                    $campos['date_to'] = pSQL(self::hastaDb($rec['date_to']));
                }
                if (isset($rec['date_from'])) {
                    $campos['date_from'] = pSQL($rec['date_from']);
                }
                self::ok($db->update(self::TABLA, $campos, 'id = ' . (int) $f['id']), 'recortar un acuerdo anterior');
                ++$r['recortados'];
                if (isset($rec['nueva'])) {
                    // El trozo de detrás: la misma fila, con las fechas de después del nuevo acuerdo
                    // (si ya hubiera una con esas fechas exactas, se queda la que había).
                    self::ok($db->insert(self::TABLA, [
                        'id_product' => (int) $f['id_product'],
                        'id_product_attribute' => (int) $f['id_product_attribute'],
                        'id_supplier_promo_type' => (int) $f['id_supplier_promo_type'],
                        'value' => (float) $f['value'],
                        'date_from' => pSQL($rec['nueva']['date_from']),
                        'date_to' => pSQL(self::hastaDb($rec['nueva']['date_to'])),
                        'id_kpy_coupon' => (int) $f['id_kpy_coupon'] > 0 ? (int) $f['id_kpy_coupon'] : ['type' => 'sql', 'value' => 'NULL'],
                        'origen' => pSQL((string) $f['origen']),
                        'date_add' => $ahora,
                        'date_upd' => $ahora,
                    ], false, true, Db::INSERT_IGNORE), 'partir un acuerdo anterior');
                }
            }
            $campos = [
                'id_supplier_promo_type' => $idTipo,
                'value' => $valor,
                'id_kpy_coupon' => $promoFila > 0 ? $promoFila : ['type' => 'sql', 'value' => 'NULL'],
                'origen' => self::ORIGEN_LISTA,
                'date_upd' => $ahora,
            ];
            if ($misma !== null) {
                self::ok($db->update(self::TABLA, $campos, 'id = ' . (int) $misma['id']), 'actualizar un artículo');
                ++$r['actualizados'];
                continue;
            }
            self::ok($db->insert(self::TABLA, $campos + [
                'id_product' => $idProducto,
                'id_product_attribute' => $ipa,
                'date_from' => pSQL($desde),
                'date_to' => pSQL(self::hastaDb($hasta)),
                'date_add' => $ahora,
            ]), 'guardar un artículo');
            ++$r['guardados'];
        }

        return $r;
    }

    /**
     * Cambia el final de un lote suelto (prolongar, cerrar hoy, o dejarlo hasta nueva orden).
     * Antes mira que ningún artículo tenga ya otra fila con esas fechas (la clave única lo
     * impediría a medias).
     *
     * @return int|string las filas cambiadas, o por qué no se ha podido
     */
    public static function cambiarFinal(array $lote, ?string $hasta)
    {
        $p = _DB_PREFIX_;
        $hastaDb = self::hastaDb($hasta);
        $choques = (int) Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM {$p}" . self::TABLA . " s
             INNER JOIN {$p}product p ON p.id_product = s.id_product
             INNER JOIN {$p}" . self::TABLA . " o ON o.id <> s.id AND o.id_product = s.id_product
                AND o.id_product_attribute = s.id_product_attribute AND o.date_from = s.date_from AND o.date_to = '" . pSQL($hastaDb) . "'
             WHERE " . self::condicion($lote)
        );
        if ($choques > 0) {
            return sprintf('No se han cambiado las fechas: %d artículos ya tienen otro acuerdo que empieza el mismo día y acaba en esa fecha. Bórralo o elige otra fecha.', $choques);
        }
        self::ok(Db::getInstance()->execute(
            "UPDATE {$p}" . self::TABLA . " s INNER JOIN {$p}product p ON p.id_product = s.id_product
             SET s.date_to = '" . pSQL($hastaDb) . "', s.date_upd = '" . pSQL(date('Y-m-d H:i:s')) . "'
             WHERE " . self::condicion($lote)
        ), 'cambiar las fechas');

        return (int) Db::getInstance()->Affected_Rows();
    }

    public static function borrarLote(array $lote): int
    {
        $p = _DB_PREFIX_;
        self::ok(Db::getInstance()->execute("DELETE s FROM {$p}" . self::TABLA . " s INNER JOIN {$p}product p ON p.id_product = s.id_product WHERE " . self::condicion($lote)), 'borrar el acuerdo');

        return (int) Db::getInstance()->Affected_Rows();
    }

    public static function borrarFila(int $id): bool
    {
        return $id > 0 && (bool) Db::getInstance()->delete(self::TABLA, 'id = ' . $id);
    }

    // ---------- las promociones de kpycoupons ----------

    /** Las promociones que se pueden enlazar al subir una lista: las vivas, las programadas y las terminadas hace menos de dos meses. */
    public static function promosParaElegir(): array
    {
        $salida = [];
        foreach (Db::getInstance()->executeS(
            'SELECT id_kpy_coupon, codigo, nombre, tipo, fecha_desde, fecha_hasta, activo FROM ' . _DB_PREFIX_ . "kpy_coupon
             WHERE fecha_hasta IS NULL OR fecha_hasta = '0000-00-00 00:00:00' OR fecha_hasta >= DATE_SUB(NOW(), INTERVAL 60 DAY)
             ORDER BY activo DESC, fecha_desde DESC, id_kpy_coupon DESC LIMIT 150"
        ) ?: [] as $f) {
            $desde = self::fechaValida($f['fecha_desde'] ?? '');
            $hasta = self::fechaValida($f['fecha_hasta'] ?? '');
            $salida[] = [
                'id' => (int) $f['id_kpy_coupon'],
                'codigo' => (string) $f['codigo'],
                'nombre' => (string) $f['nombre'],
                'tipo' => (string) $f['tipo'],
                'activo' => (int) $f['activo'] === 1,
                'desde' => $desde !== null ? substr($desde, 0, 10) : '',
                'hasta' => $hasta !== null ? substr($hasta, 0, 10) : '',
                'texto' => trim((string) $f['codigo'] . ' · ' . (string) $f['nombre']) . ($desde !== null ? ' (' . self::fechasTexto($desde, $hasta) . ')' : '') . ((int) $f['activo'] === 1 ? '' : ' · apagada'),
            ];
        }

        return $salida;
    }

    /** Lo marcado en el paso de una promoción: tipo (clave) y valor; null si «sin acuerdo». */
    public static function configDe(int $idPromo): ?array
    {
        if ($idPromo <= 0) {
            return null;
        }
        $f = Db::getInstance()->getRow('SELECT id_supplier_promo_type, value FROM ' . _DB_PREFIX_ . self::TABLA_PROMO . ' WHERE id_kpy_coupon = ' . $idPromo);
        $tipo = $f ? KsTipos::clave((int) $f['id_supplier_promo_type']) : '';

        return $tipo !== '' ? ['tipo' => $tipo, 'valor' => (float) $f['value']] : null;
    }

    /** Guarda lo marcado en el paso (tipo '' = sin acuerdo: se quita). */
    public static function guardarConfig(int $idPromo, string $tipo, ?float $valor): void
    {
        $db = Db::getInstance();
        if ($tipo === '' || $valor === null) {
            self::ok($db->delete(self::TABLA_PROMO, 'id_kpy_coupon = ' . $idPromo), 'quitar el acuerdo de la promoción');

            return;
        }
        $ahora = date('Y-m-d H:i:s');
        $idTipo = KsTipos::id($tipo);
        if ($idTipo <= 0) {
            KsTipos::crear();
            $idTipo = KsTipos::id($tipo);
        }
        if ($idTipo <= 0) {
            throw new RuntimeException('no existe el tipo de acuerdo ' . $tipo);
        }
        if ($db->getValue('SELECT id_kpy_coupon FROM ' . _DB_PREFIX_ . self::TABLA_PROMO . ' WHERE id_kpy_coupon = ' . $idPromo)) {
            self::ok($db->update(self::TABLA_PROMO, ['id_supplier_promo_type' => $idTipo, 'value' => $valor, 'date_upd' => $ahora], 'id_kpy_coupon = ' . $idPromo), 'guardar el acuerdo de la promoción');
        } else {
            self::ok($db->insert(self::TABLA_PROMO, ['id_kpy_coupon' => $idPromo, 'id_supplier_promo_type' => $idTipo, 'value' => $valor, 'date_add' => $ahora, 'date_upd' => $ahora]), 'guardar el acuerdo de la promoción');
        }
    }

    /**
     * Lo que tiene una promoción: lo marcado en su paso y cuántas filas hay de cada origen.
     *
     * @return array{config: ?array, n_promo: int, n_lista: int, tipos_lista: string[]}
     */
    public static function dePromo(int $idPromo): array
    {
        $salida = ['config' => self::configDe($idPromo), 'n_promo' => 0, 'n_lista' => 0, 'tipos_lista' => []];
        if ($idPromo <= 0) {
            return $salida;
        }
        foreach (Db::getInstance()->executeS(
            'SELECT origen, id_supplier_promo_type, COUNT(*) AS n FROM ' . _DB_PREFIX_ . self::TABLA . '
             WHERE id_kpy_coupon = ' . $idPromo . ' GROUP BY origen, id_supplier_promo_type'
        ) ?: [] as $f) {
            if ($f['origen'] === self::ORIGEN_PROMO) {
                $salida['n_promo'] += (int) $f['n'];
            } else {
                $salida['n_lista'] += (int) $f['n'];
                $salida['tipos_lista'][] = KsTipos::titulo(KsTipos::clave((int) $f['id_supplier_promo_type']));
            }
        }
        $salida['tipos_lista'] = array_values(array_unique($salida['tipos_lista']));

        return $salida;
    }

    /** «Redención 10 % en 42 artículos» (y lo de las listas). Vacío si la promoción no tiene nada. */
    public static function textoDePromo(array $d): string
    {
        $partes = [];
        if ($d['config'] !== null) {
            $n = (int) $d['n_promo'];
            $partes[] = KsTipos::titulo($d['config']['tipo']) . ' ' . KsTipos::valorTexto($d['config']['tipo'], (float) $d['config']['valor'])
                . ' en ' . $n . ($n === 1 ? ' artículo' : ' artículos');
        }
        if ((int) $d['n_lista'] > 0) {
            $n = (int) $d['n_lista'];
            $partes[] = $n . ($n === 1 ? ' artículo' : ' artículos') . ' con su propio valor por lista (' . implode(', ', $d['tipos_lista']) . ')';
        }

        return implode(' y ', $partes);
    }

    /** Las promociones con algo de esto: con el paso marcado o con filas. */
    public static function promosConAcuerdo(): array
    {
        $p = _DB_PREFIX_;
        $ids = array_map('intval', array_column(Db::getInstance()->executeS(
            "SELECT id_kpy_coupon FROM {$p}" . self::TABLA_PROMO . "
             UNION SELECT DISTINCT id_kpy_coupon FROM {$p}" . self::TABLA . ' WHERE id_kpy_coupon IS NOT NULL AND id_kpy_coupon > 0'
        ) ?: [], 'id_kpy_coupon'));

        return array_values(array_unique(array_filter($ids)));
    }

    // ---------- lo que se le pregunta a kpycoupons (su hook de consulta) ----------

    /**
     * Los artículos de una promoción y su fila pública, preguntándoselo a kpycoupons por su hook de
     * consulta `actionKpyCouponsAlcance` (contrato 3.0.0, `KpcContrato`): `entran` y `especial`
     * (clave => id_product; la clave es el id de la combinación, o −id_product sin combinaciones) y
     * `promocion` (claves fijas: id_kpy_coupon, codigo, nombre, tipo, activo, fecha_desde,
     * fecha_hasta, estado…). Null si kpycoupons no contesta: no está, la promoción no existe, o la
     * respuesta no trae `entran`. Es la única manera de saber los artículos de una promoción fuera
     * del reparto (al guardarla y con el botón «Poner al día») sin llamar a ninguna clase ajena.
     *
     * @return array{version: int, entran: array<int|string, int>, especial: array<int|string, int>, promocion: array}|null
     */
    public static function alcanceDeKpycoupons(int $idPromo, int $idTienda): ?array
    {
        if ($idPromo <= 0) {
            return null;
        }
        try {
            $respuestas = Hook::exec('actionKpyCouponsAlcance', ['id' => $idPromo, 'id_shop' => $idTienda], null, true);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpysupply, al pedir el alcance de la promoción ' . $idPromo . ' (actionKpyCouponsAlcance): ' . $e->getMessage(), 3, null, 'Kpysupply', $idPromo, true);

            return null;
        }
        $a = is_array($respuestas) ? ($respuestas['kpycoupons'] ?? null) : null;
        if (!is_array($a) || !isset($a['entran']) || !is_array($a['entran'])) {
            return null;
        }

        return [
            'version' => (int) ($a['version'] ?? 0),
            'entran' => $a['entran'],
            'especial' => is_array($a['especial'] ?? null) ? $a['especial'] : [],
            'promocion' => is_array($a['promocion'] ?? null) ? $a['promocion'] : [],
        ];
    }

    /**
     * Pone el acuerdo de una promoción al día con ella **ahora** (al guardar la promoción y con el
     * botón «Poner al día con la promoción» de la pantalla): pide a kpycoupons sus artículos y sus
     * fechas y hace el espejo (`sincronizarPromo` con espejo). Si kpycoupons no contesta, no se toca
     * nada y se dice.
     *
     * @return array{ok: bool, texto: string, cuentas: ?array}
     */
    public static function ponerAlDia(int $idPromo, int $idTienda): array
    {
        $a = self::alcanceDeKpycoupons($idPromo, $idTienda);
        if ($a === null) {
            return ['ok' => false, 'texto' => 'kpycoupons no contesta: no se ha tocado el acuerdo de la promoción #' . $idPromo . '.', 'cuentas' => null];
        }
        $r = self::sincronizarPromo($idPromo, true, $a, $a['promocion']);
        if ($r === null) {
            return ['ok' => false, 'texto' => 'Esa promoción no existe.', 'cuentas' => null];
        }

        return [
            'ok' => true,
            'texto' => sprintf('Acuerdo de la promoción al día: %d artículos en la promoción, %d filas nuevas, %d quitadas%s.', $r['articulos'], $r['creados'], $r['borrados'], $r['con_ventas'] ? sprintf(' (%d se quedan aunque ya no están en la promoción: vendieron con el acuerdo)', $r['con_ventas']) : ''),
            'cuentas' => $r,
        ];
    }

    /**
     * Pone el acuerdo de una promoción al día con ella. Lo llaman el hook del reparto de kpycoupons
     * (`actionKpyCouponsReparto`, con lo que trae) y `ponerAlDia()` (con lo que contesta el hook de
     * consulta): `$alcance` son sus artículos (`entran` y `especial`, clave => id_product, tal como
     * los calcula kpycoupons) y `$promocion` su fila pública (de la que solo se usan `fecha_desde` y
     * `fecha_hasta`). Qué hace:
     *   1. todas sus filas (las del paso y las de lista) siguen **sus fechas**; una del paso que
     *      chocaría con otra fila del mismo artículo y esas mismas fechas se quita (manda la otra);
     *      una de lista se deja como estaba y se avisa;
     *   2. sin nada marcado en el paso: con `$espejo`, se quitan las filas del paso (las de lista
     *      se quedan: se subieron a propósito);
     *   3. las del paso, con el tipo y el valor del paso;
     *   4. con `$espejo` (al guardar la promoción): las del paso de artículos que ya no están en la
     *      promoción se quitan, **salvo las que ya han vendido** en el periodo (esas ventas fueron
     *      con el acuerdo; un formato apagado por falta de stock no puede borrar sus ventas);
     *   5. los artículos de la promoción sin fila, una del paso; salvo los que ya tienen otra fila
     *      con estas mismas fechas (una lista suelta, otra promoción): manda la que había.
     * En los repartos por fecha (sin espejo) solo se hacen 1, 3 y 5: nunca se quita nada. El espejo
     * se hace al guardar la promoción, con el botón «Poner al día» y en el reparto que sigue al
     * guardar (`guardada`).
     *
     * @param array{entran?: array<int|string, int>, especial?: array<int|string, int>} $alcance la
     *        clave es el id de la combinación, o −id_product si el producto no tiene combinaciones
     * @param array $promocion la fila pública de la promoción (`fecha_desde`, `fecha_hasta`; null = sin fecha)
     *
     * @return array|null las cuentas; null sin promoción
     */
    public static function sincronizarPromo(int $idPromo, bool $espejo, array $alcance, array $promocion): ?array
    {
        if ($idPromo <= 0) {
            return null;
        }

        return self::transaccion(static fn (): array => self::sincronizarPromoDentro($idPromo, $espejo, $alcance, $promocion));
    }

    private static function sincronizarPromoDentro(int $idPromo, bool $espejo, array $alcance, array $promocion): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $ahora = date('Y-m-d H:i:s');
        $r = ['articulos' => 0, 'creados' => 0, 'actualizados' => 0, 'movidas' => 0, 'borrados' => 0, 'con_ventas' => 0, 'ya_tenian' => 0, 'sin_mover' => 0];
        $desde = self::fechaValida($promocion['fecha_desde'] ?? '') ?? $ahora;
        $hasta = self::fechaValida($promocion['fecha_hasta'] ?? '');
        $hastaDb = self::hastaDb($hasta);
        $config = self::configDe($idPromo);

        $propias = [];
        foreach ($db->executeS('SELECT * FROM ' . $p . self::TABLA . ' WHERE id_kpy_coupon = ' . $idPromo . ' ORDER BY id') ?: [] as $f) {
            $propias[(int) $f['id']] = $f;
        }

        // 1. Las fechas de la promoción.
        $mover = array_filter($propias, static fn (array $f): bool => (string) $f['date_from'] !== $desde || (string) $f['date_to'] !== $hastaDb);
        if ($mover !== []) {
            $ocupadas = self::ocupadas(array_map(static fn (array $f): int => (int) $f['id_product'], $mover), $desde, $hastaDb, array_keys($mover));
            foreach ($mover as $id => $f) {
                $clave = (int) $f['id_product'] . '-' . (int) $f['id_product_attribute'];
                if (isset($ocupadas[$clave])) {
                    if ($f['origen'] === self::ORIGEN_PROMO) {
                        self::ok($db->delete(self::TABLA, 'id = ' . (int) $id), 'quitar una fila que choca');
                        ++$r['ya_tenian'];
                    } else {
                        ++$r['sin_mover'];
                        PrestaShopLogger::addLog('kpysupply, promoción ' . $idPromo . ': la fila de lista #' . $id . ' no se ha movido a las fechas nuevas de la promoción porque ese artículo ya tiene otro acuerdo con esas mismas fechas', 2, null, 'Kpysupply', $idPromo, true);
                    }
                    unset($propias[$id]);
                    continue;
                }
                self::ok($db->update(self::TABLA, ['date_from' => pSQL($desde), 'date_to' => pSQL($hastaDb), 'date_upd' => $ahora], 'id = ' . (int) $id), 'mover una fila a las fechas de la promoción');
                $ocupadas[$clave] = true;
                $propias[$id]['date_from'] = $desde;
                $propias[$id]['date_to'] = $hastaDb;
                ++$r['movidas'];
            }
        }

        $delPaso = array_filter($propias, static fn (array $f): bool => $f['origen'] === self::ORIGEN_PROMO);

        // 2. Sin nada marcado en el paso.
        if ($config === null) {
            if ($espejo && $delPaso !== []) {
                self::ok($db->delete(self::TABLA, 'id IN (' . implode(',', array_keys($delPaso)) . ')'), 'quitar las filas del paso');
                $r['borrados'] = count($delPaso);
            }

            return $r;
        }
        $idTipo = KsTipos::id($config['tipo']);
        $valor = (float) $config['valor'];

        // 3. Las del paso, con su tipo y su valor.
        $cambiar = array_keys(array_filter($delPaso, static fn (array $f): bool => (int) $f['id_supplier_promo_type'] !== $idTipo || abs((float) $f['value'] - $valor) > 0.000001));
        if ($cambiar !== []) {
            self::ok($db->update(self::TABLA, ['id_supplier_promo_type' => $idTipo, 'value' => $valor, 'date_upd' => $ahora], 'id IN (' . implode(',', $cambiar) . ')'), 'poner el tipo y el valor del paso');
            $r['actualizados'] = count($cambiar);
        }

        // Los artículos de la promoción (paso 2), como los calcula kpycoupons y los trae el hook del
        // reparto: los que entran y también los que aparta por tener otro Special Price (`especial`):
        // el acuerdo con el proveedor es sobre la gama, no sobre la mecánica del cupón. Los quitados
        // a mano, no.
        $quiere = [];
        foreach ((array) ($alcance['entran'] ?? []) + (array) ($alcance['especial'] ?? []) as $clave => $idProducto) {
            $ipa = (int) $clave > 0 ? (int) $clave : 0;
            $quiere[(int) $idProducto . '-' . $ipa] = ['id_product' => (int) $idProducto, 'ipa' => $ipa];
        }
        $r['articulos'] = count($quiere);
        $tienen = [];
        foreach ($propias as $f) {
            $tienen[(int) $f['id_product'] . '-' . (int) $f['id_product_attribute']] = true;
        }

        // 4. Espejo: las del paso que ya no están en la promoción, fuera (salvo si ya han vendido).
        if ($espejo) {
            $sobran = [];
            foreach ($delPaso as $id => $f) {
                $clave = (int) $f['id_product'] . '-' . (int) $f['id_product_attribute'];
                if (!isset($quiere[$clave])) {
                    $sobran[$clave] = $id;
                }
            }
            if ($sobran !== []) {
                $pares = array_map(static fn (string $c): array => array_map('intval', explode('-', $c)), array_keys($sobran));
                $finVentas = $hasta !== null && $hasta < $ahora ? $hasta : $ahora;
                $vendidas = $desde <= $finVentas ? KsVentas::conVentas($pares, $desde, $finVentas) : [];
                $quitar = [];
                foreach ($sobran as $clave => $id) {
                    if (isset($vendidas[$clave])) {
                        ++$r['con_ventas'];
                    } else {
                        $quitar[] = $id;
                        unset($tienen[$clave]);
                    }
                }
                if ($quitar !== []) {
                    self::ok($db->delete(self::TABLA, 'id IN (' . implode(',', $quitar) . ')'), 'quitar los artículos que ya no están en la promoción');
                    $r['borrados'] = count($quitar);
                }
            }
        }

        // 5. Los que faltan.
        $faltan = array_diff_key($quiere, $tienen);
        if ($faltan !== []) {
            $ocupadas = self::ocupadas(array_map(static fn (array $a): int => $a['id_product'], $faltan), $desde, $hastaDb, []);
            foreach ($faltan as $clave => $a) {
                if (isset($ocupadas[$clave])) {
                    ++$r['ya_tenian'];
                    continue;
                }
                // INSERT IGNORE: si otro proceso (un reparto a la vez) acaba de crearla, se queda la suya.
                self::ok($db->insert(self::TABLA, [
                    'id_product' => $a['id_product'],
                    'id_product_attribute' => $a['ipa'],
                    'id_supplier_promo_type' => $idTipo,
                    'value' => $valor,
                    'date_from' => pSQL($desde),
                    'date_to' => pSQL($hastaDb),
                    'id_kpy_coupon' => $idPromo,
                    'origen' => self::ORIGEN_PROMO,
                    'date_add' => $ahora,
                    'date_upd' => $ahora,
                ], false, true, Db::INSERT_IGNORE), 'dar el acuerdo a un artículo');
                if ((int) $db->Affected_Rows() > 0) {
                    ++$r['creados'];
                } else {
                    ++$r['ya_tenian'];
                }
            }
        }

        return $r;
    }

    /**
     * Los artículos de unos productos que ya tienen una fila con esas fechas exactas (sin contar
     * las filas `$sinIds`).
     *
     * @param int[] $productos
     * @param int[] $sinIds
     *
     * @return array<string, true> clave «producto-ipa»
     */
    private static function ocupadas(array $productos, string $desde, string $hastaDb, array $sinIds): array
    {
        $productos = array_values(array_unique(array_filter(array_map('intval', $productos))));
        if ($productos === []) {
            return [];
        }
        $sinIds = array_values(array_filter(array_map('intval', $sinIds)));
        $salida = [];
        foreach (array_chunk($productos, 1000) as $trozo) {
            foreach (Db::getInstance()->executeS(
                'SELECT id_product, id_product_attribute FROM ' . _DB_PREFIX_ . self::TABLA . '
                 WHERE id_product IN (' . implode(',', $trozo) . ") AND date_from = '" . pSQL($desde) . "' AND date_to = '" . pSQL($hastaDb) . "'"
                . ($sinIds !== [] ? ' AND id NOT IN (' . implode(',', $sinIds) . ')' : '')
            ) ?: [] as $f) {
                $salida[(int) $f['id_product'] . '-' . (int) $f['id_product_attribute']] = true;
            }
        }

        return $salida;
    }
}
