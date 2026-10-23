<?php
/**
 * «Regalo con la compra»: en la ficha, al lado de «12 kg», sale una tarjeta más, «12 kg + 3 kg
 * gratis» (o «12 kg + Regalo», «+ Snacks», «+ Pack latas»…), como si fuera un saco Jirafa. Si el
 * cliente la elige, el saco de 12 kg entra **a su precio de siempre, sin el Special Price** (es lo
 * que pide la marca: regala el pequeño a cambio de no rebajar el grande) y el de 3 kg entra a 0 €.
 * La tarjeta «12 kg» de siempre sigue con su Special Price y sin regalo: el cliente elige.
 *
 * Cómo se registra, que es lo que importa: igual que el regalo de PrestaShop. El regalo es **una
 * línea normal** de la cesta y del pedido (con su referencia, su EAN, su precio y su stock), y una
 * **regla de carrito propia de esa cesta** (`KPYRG-…`) descuenta exactamente lo que vale. El pedido
 * suma 0 € por él, el almacén lo prepara como cualquier línea, la factura cuadra y el stock baja.
 * El precio de siempre del saco grande es un precio específico de esa cesta (`KpcPrecioCesta`), lo
 * mismo que hace PrestaShop al cambiar un precio desde el back office.
 *
 * Lo que PrestaShop no sabe hacer y aquí sí: la pareja («el 3 kg de la misma ficha que el 12 kg que
 * compras»), tantos regalos como sacos grandes, el regalo de reserva si el primero no tiene stock,
 * el cupo de la campaña (`KpcCupo`: regalos dados más los reservados en otras cestas), y que todo se
 * rehaga en cada cambio de la cesta sin estados colgados.
 *
 * Tres partes:
 *   - **parejas**: al guardar la promoción (y al rehacer el reparto) se decide qué regala cada
 *     artículo disparador y se deja escrito en `kpy_coupon_regalo`;
 *   - **la elección**: cuántas unidades de cada línea van con regalo (`kpy_coupon_regalo_eleccion`,
 *     por unidades desde la 2.2.0). Cada tarjeta añade lo suyo: desde la ficha, `kpy_regalo=1` suma
 *     unidades con regalo y `kpy_regalo=0` unidades sin él; en la cesta, cada parte de la línea
 *     lleva su «+», su «−» y su papelera, que cambian solo esa parte (`antesDelCambio` y
 *     `trasElCambio`). La papelera del vale «Regalo» las deja todas sin regalo;
 *   - **la cesta**: en cada guardado se cuentan las unidades con regalo (sin contar unidades
 *     regaladas), se deja la línea del regalo con la cantidad que toca (`kpy_coupon_regalo_cesta`
 *     dice qué parte de cada línea es regalo), el disparador a su precio de siempre, y la regla de
 *     la cesta descuenta el regalo. PrestaShop solo deja un precio por línea: si en la misma hay
 *     unidades de las dos tarjetas, las de la de siempre recuperan su Special Price con un vale de
 *     esa cesta (`KPYSP-…`, `reglaEspecial`). Al pedir, queda en `kpy_coupon_regalo_pedido` (el
 *     sell-out para la marca) y la línea lleva «(regalo)».
 *
 * La regla de negocio pura (`pequenoDe`, `parejas`, `plan`, `etiqueta`, `corto`) no toca PrestaShop:
 * se prueba sin tienda (`tests/regalo.php`).
 */
class KpcRegalo
{
    /** Prefijo del código de la regla de cada cesta: KPYRG-{cesta}-{producto regalo}-{firma}. */
    public const PREFIJO = 'KPYRG';

    /**
     * Prefijo del vale que devuelve el Special Price a las unidades de la tarjeta de siempre en una
     * línea que también lleva unidades con regalo: KPYSP-{cesta}-{producto}-{firma}.
     */
    public const PREFIJO_ESPECIAL = 'KPYSP';

    /** Cómo se elige el regalo: el formato pequeño de la misma ficha, o el mismo artículo para todos. */
    public const MODOS = ['pequeno', 'mismo'];

    /** Cuántos: uno por cada unidad comprada, o como mucho uno por pedido. */
    public const POR = ['unidad', 'pedido'];

    /** Las reglas de los cupones van antes (prioridad 1): el regalo se descuenta sobre lo que ya cuesta. */
    public const PRIORIDAD = 90;

    /** Lo que puede dejar fuera a un disparador o a un regalo, dicho para la pantalla y la cesta. */
    public const MOTIVOS = [
        'sin_pequeno' => 'sin un formato más pequeño en su ficha',
        'sin_regalo' => 'sin regalo elegido',
        'mismo_articulo' => 'el regalo es el mismo artículo',
        'sin_stock' => 'el regalo está agotado',
        'tope' => 'se ha alcanzado el cupo de regalos de la campaña',
        'no_anade' => 'la tienda no ha dejado añadir el regalo',
    ];

    /**
     * Qué es el regalo, para la ficha y la landing: `kilos` (más de lo mismo: el pequeño de su ficha,
     * «12 kg + 3 kg gratis») o `regalo` (otra cosa: un accesorio, un snack, unas latas).
     */
    public const CLASES = ['kilos', 'regalo'];

    // ------------------------------------------------------------------------------------------
    // Parejas: qué regala cada artículo
    // ------------------------------------------------------------------------------------------

    /**
     * El formato más pequeño de la misma ficha que el disparador: el que se regala en el modo
     * «pequeño». Misma clase de envase (un saco regala un saco, no una lata), suelto (no un pack),
     * sin OUTLET, sin «gratis» en el nombre (los sacos promocionales del fabricante) y más ligero.
     *
     * @param int $ipa el formato disparador (si es un pack, su suelto: el peso que manda es el del saco)
     * @param array<int, array{nombre: string, nivel?: int, apagado?: bool}> $formatos los de su producto
     */
    public static function pequenoDe(int $ipa, array $formatos): ?int
    {
        $mio = $formatos[$ipa] ?? null;
        if ($mio === null) {
            return null;
        }
        $peso = static fn (array $f): float => KpcFormatos::orden(KpcFormatos::tamano((string) ($f['nombre'] ?? '')))[1];
        $envase = static fn (array $f): string => KpcFormatos::grupo(KpcFormatos::tamano((string) ($f['nombre'] ?? '')))[0];
        $gramos = $peso($mio);
        if ($gramos >= PHP_FLOAT_MAX) {
            return null; // Sin peso en el nombre («Talla M») no hay «más pequeño».
        }
        $suEnvase = $envase($mio);
        $mejor = null;
        $mejorGramos = PHP_FLOAT_MAX;
        foreach ($formatos as $otro => $f) {
            $otro = (int) $otro;
            $nombre = (string) ($f['nombre'] ?? '');
            if ($otro === $ipa || !empty($f['apagado']) || (int) ($f['nivel'] ?? 1) > 1
                || KpcFormatos::esOutlet($nombre) || preg_match('/\b(gratis|regalo)\b/iu', $nombre)) {
                continue;
            }
            $g = $peso($f);
            if ($g >= $gramos || $g >= PHP_FLOAT_MAX || $envase($f) !== $suEnvase) {
                continue;
            }
            if ($mejor === null || $g < $mejorGramos) {
                $mejor = $otro;
                $mejorGramos = $g;
            }
        }

        return $mejor;
    }

    /** «8299-92468» → ['id' => 8299, 'ipa' => 92468]; «7123» o «7123-0» → ipa 0. Vacío o raro → null. */
    public static function leerArticulo($texto): ?array
    {
        if (is_array($texto)) {
            $id = (int) ($texto['id'] ?? $texto[0] ?? 0);
            $ipa = (int) ($texto['ipa'] ?? $texto[1] ?? 0);
        } elseif (preg_match('/^\s*(\d+)(?:-(\d+))?\s*$/', (string) $texto, $m)) {
            $id = (int) $m[1];
            $ipa = (int) ($m[2] ?? 0);
        } else {
            return null;
        }

        return $id > 0 ? ['id' => $id, 'ipa' => max(0, $ipa)] : null;
    }

    /** Lo contrario de `leerArticulo`: «8299-92468». */
    public static function escribirArticulo(?array $articulo): string
    {
        return $articulo === null ? '' : (int) $articulo['id'] . '-' . (int) $articulo['ipa'];
    }

    /**
     * Qué regala cada artículo disparador de una promoción.
     *
     * @param array $cupon la fila de `kpy_coupon` (o lo que llega del formulario): `regalo_modo`,
     *                     `regalo_mismo`, `regalo_reserva`, `regalo_parejas` (JSON clave → {r, s})
     * @param array<int, int> $items clave del disparador => id de su producto (el reparto)
     * @param array<int, int> $niveles clave => nivel de pack (1 suelto, 2 pack de dos…)
     * @param array<int, array<int, array>> $formatos por producto, sus formatos a la venta:
     *                     ipa => ['nombre', 'nivel', 'base', 'apagado']
     *
     * @return array<int, array{clave: int, id_product: int, n: int, r: ?array{id: int, ipa: int},
     *                          s: ?array{id: int, ipa: int}, origen: string, aviso: string}>
     *         por clave del disparador. `n`: regalos por unidad comprada (el nivel del pack).
     */
    public static function parejas(array $cupon, array $items, array $niveles = [], array $formatos = []): array
    {
        $modo = in_array($cupon['regalo_modo'] ?? '', self::MODOS, true) ? (string) $cupon['regalo_modo'] : 'pequeno';
        $mismo = self::leerArticulo($cupon['regalo_mismo'] ?? '');
        $reserva = self::leerArticulo($cupon['regalo_reserva'] ?? '');
        $manuales = self::leerParejas($cupon['regalo_parejas'] ?? '');

        $salida = [];
        foreach ($items as $clave => $idProducto) {
            $clave = (int) $clave;
            $idProducto = (int) $idProducto;
            $fila = ['clave' => $clave, 'id_product' => $idProducto, 'n' => max(1, (int) ($niveles[$clave] ?? 1)), 'r' => null, 's' => null, 'origen' => $modo, 'aviso' => ''];

            if (isset($manuales[$clave])) {
                $fila['r'] = $manuales[$clave]['r'] ?? null;
                $fila['s'] = $manuales[$clave]['s'] ?? $reserva;
                $fila['origen'] = 'mano';
            } elseif ($modo === 'mismo') {
                $fila['r'] = $mismo;
                $fila['s'] = $reserva;
            } else {
                $suyos = $formatos[$idProducto] ?? [];
                $ipa = $clave > 0 ? $clave : 0;
                // Un pack regala el pequeño de su saco: el peso que manda es el del suelto.
                $referencia = isset($suyos[$ipa]['base']) && $suyos[$ipa]['base'] !== null ? (int) $suyos[$ipa]['base'] : $ipa;
                $pequeno = $ipa > 0 ? self::pequenoDe($referencia, $suyos) : null;
                $fila['r'] = $pequeno !== null ? ['id' => $idProducto, 'ipa' => $pequeno] : null;
                $fila['s'] = $reserva;
                if ($fila['r'] === null && $fila['s'] !== null) {
                    // Sin pequeño, el de reserva pasa a ser el regalo.
                    $fila['r'] = $fila['s'];
                    $fila['s'] = null;
                    $fila['origen'] = 'reserva';
                }
                if ($fila['r'] === null) {
                    $fila['aviso'] = 'sin_pequeno';
                }
            }

            if ($fila['r'] === null && $fila['aviso'] === '') {
                $fila['aviso'] = 'sin_regalo';
            }
            if ($fila['r'] !== null && (int) $fila['r']['id'] === $idProducto && (int) $fila['r']['ipa'] === max(0, $clave)) {
                $fila['aviso'] = 'mismo_articulo';
            }
            if ($fila['s'] !== null && $fila['r'] !== null && $fila['s'] == $fila['r']) {
                $fila['s'] = null;
            }
            $salida[$clave] = $fila;
        }

        return $salida;
    }

    /** @return array<int, array{r: ?array, s: ?array}> las parejas puestas a mano, por clave */
    public static function leerParejas($json): array
    {
        $datos = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);
        $salida = [];
        foreach ((array) $datos as $clave => $p) {
            $clave = (int) $clave;
            if ($clave === 0 || !is_array($p)) {
                continue;
            }
            $r = self::leerArticulo($p['r'] ?? '');
            $s = self::leerArticulo($p['s'] ?? '');
            if ($r !== null || $s !== null) {
                $salida[$clave] = ['r' => $r, 's' => $s];
            }
        }

        return $salida;
    }

    /** Deja escritas las parejas de una promoción (borrando las de antes). */
    public static function guardar(int $idCupon, array $parejas): void
    {
        $db = Db::getInstance();
        // De una vez: una cesta que se repasa mientras tanto ve las de antes o las de ahora, nunca
        // ninguna (y soltaría la elección de regalo del cliente).
        $db->execute('START TRANSACTION');
        try {
            self::escribirParejas($db, $idCupon, $parejas);
            $db->execute('COMMIT');
        } catch (Throwable $e) {
            $db->execute('ROLLBACK');
            throw $e;
        }
    }

    private static function escribirParejas(Db $db, int $idCupon, array $parejas): void
    {
        $db->delete('kpy_coupon_regalo', 'id_kpy_coupon = ' . $idCupon);
        $valores = [];
        foreach ($parejas as $p) {
            if ($p['r'] === null) {
                continue; // Sin regalo no hay pareja: el disparador se queda sin promoción y se dice.
            }
            $valores[] = '(' . $idCupon . ', ' . (int) $p['clave'] . ', ' . (int) $p['id_product'] . ', ' . (int) $p['n'] . ', '
                . (int) $p['r']['id'] . ', ' . (int) $p['r']['ipa'] . ', '
                . ($p['s'] !== null ? (int) $p['s']['id'] . ', ' . (int) $p['s']['ipa'] : '0, 0') . ", '" . pSQL((string) $p['origen']) . "')";
        }
        foreach (array_chunk($valores, 500) as $trozo) {
            $db->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'kpy_coupon_regalo (id_kpy_coupon, clave, id_product, n, r_id_product, r_ipa, s_id_product, s_ipa, origen) VALUES '
                . implode(',', $trozo)
            );
        }
    }

    /** Las parejas guardadas de una promoción, por clave del disparador. */
    public static function guardadas(int $idCupon): array
    {
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo WHERE id_kpy_coupon = ' . $idCupon
        ) ?: [] as $f) {
            $salida[(int) $f['clave']] = self::fila($f);
        }

        return $salida;
    }

    /**
     * Las parejas **en vigor ahora** de unos disparadores (los artículos de una cesta), con lo que
     * la cesta necesita de su promoción. Una consulta.
     *
     * @param int[] $claves
     *
     * @return array<int, array<int, array>> clave del disparador => parejas (una por promoción), en
     *         orden de promoción (la más antigua primero)
     */
    public static function deClaves(int $idTienda, array $claves): array
    {
        $claves = array_values(array_unique(array_map('intval', $claves)));
        if ($claves === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT r.*, c.regalo_por, c.cupo, c.regalo_corto, c.fecha_hasta, c.nombre, c.codigo
             FROM {$p}kpy_coupon_regalo r
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = r.id_kpy_coupon
             WHERE c.activo = 1 AND c.tipo = 'regalo' AND c.id_shop = " . (int) $idTienda . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c') . '
               AND r.clave IN (' . implode(',', $claves) . ')
             ORDER BY c.id_kpy_coupon, r.clave'
        ) ?: [] as $f) {
            $salida[(int) $f['clave']][] = self::fila($f) + [
                'por' => in_array($f['regalo_por'] ?? '', self::POR, true) ? (string) $f['regalo_por'] : 'unidad',
                // El cupo de la campaña (hasta la 2.3.0, `regalo_tope`): regalos dados más reservados.
                'tope' => (int) ($f['cupo'] ?? 0),
                'hasta' => (string) ($f['fecha_hasta'] ?? ''),
                'nombre' => (string) ($f['nombre'] ?? ''),
                'codigo' => (string) ($f['codigo'] ?? ''),
                'corto' => (string) ($f['regalo_corto'] ?? ''),
            ];
        }

        return $salida;
    }

    /**
     * Los regalos de un producto (lo que pregunta la ficha): por formato disparador, su regalo.
     *
     * @return array<int, array> ipa del disparador (0 si el producto no tiene) => pareja
     */
    public static function deProducto(int $idTienda, int $idProducto): array
    {
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT r.*, c.nombre, c.codigo, c.fecha_hasta, c.regalo_por, c.regalo_corto, c.cupo, c.cupo_ficha FROM {$p}kpy_coupon_regalo r
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = r.id_kpy_coupon
             WHERE c.activo = 1 AND c.tipo = 'regalo' AND c.id_shop = " . (int) $idTienda . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c') . '
               AND r.id_product = ' . (int) $idProducto . '
             ORDER BY c.id_kpy_coupon'
        ) ?: [] as $f) {
            $ipa = max(0, (int) $f['clave']);
            if (!isset($salida[$ipa])) {
                $salida[$ipa] = self::fila($f) + [
                    'nombre' => (string) $f['nombre'],
                    'codigo' => (string) $f['codigo'],
                    'hasta' => (string) $f['fecha_hasta'],
                    'por' => in_array($f['regalo_por'] ?? '', self::POR, true) ? (string) $f['regalo_por'] : 'unidad',
                    'corto' => (string) ($f['regalo_corto'] ?? ''),
                    'cupo' => (int) ($f['cupo'] ?? 0),
                    'cupo_ficha' => (int) ($f['cupo_ficha'] ?? 1),
                ];
            }
        }

        return $salida;
    }

    /**
     * Lo que la ficha enseña de los regalos de un producto (`KpcFicha`, 3.0.0; hasta la 2.4 lo pedía
     * `kpyproductbuy` por un hook suyo): por formato disparador, su regalo con nombre, foto, precio, la
     * etiqueta de la franja («+ 3 kg gratis»), lo que va detrás del «+» en la tarjeta («3 kg
     * gratis», «Regalo», «Snacks»…), su clase (`kilos` o `regalo`) y su peso (para el €/kg de la
     * tarjeta). Si el regalo está agotado y hay reserva, se enseña la reserva, que es lo que
     * entraría en la cesta.
     *
     * @param int[] $ipasFicha los formatos que enseña la ficha (0 si el producto no tiene)
     *
     * @return array{ipas: int[], por: array<int, array>, banda: string, codigo: string, nombre: string, hasta: string}|null
     */
    public static function paraFicha(int $idTienda, int $idProducto, array $ipasFicha): ?array
    {
        $parejas = self::deProducto($idTienda, $idProducto);
        $ipasFicha = array_map('intval', $ipasFicha);
        $suyas = array_intersect_key($parejas, array_fill_keys($ipasFicha, true));
        if ($suyas === []) {
            return null;
        }
        $articulos = [];
        foreach ($suyas as $p) {
            $articulos[] = $p['r'];
            if ($p['s'] !== null) {
                $articulos[] = $p['s'];
            }
        }
        $datos = self::datos($articulos, $idTienda);
        $contexto = Context::getContext();
        $link = $contexto->link;
        $tipoImagen = ImageType::getFormattedName('small');
        // Con cupo, cuántos regalos quedan (sin contar las reservas de la cesta de la visita).
        $idCart = $contexto->cart instanceof Cart ? (int) $contexto->cart->id : 0;
        $salida = ['ipas' => [], 'por' => [], 'banda' => '', 'codigo' => '', 'nombre' => '', 'hasta' => '', 'quedan' => null];
        foreach ($suyas as $ipa => $p) {
            $regalo = $p['r'];
            $hay = self::disponible((int) $regalo['id'], (int) $regalo['ipa'], 1, $idTienda);
            if (!$hay && $p['s'] !== null && self::disponible((int) $p['s']['id'], (int) $p['s']['ipa'], 1, $idTienda)) {
                $regalo = $p['s'];
                $hay = true;
            }
            $d = $datos[(int) $regalo['id'] . '-' . (int) $regalo['ipa']] ?? null;
            if ($d === null) {
                continue;
            }
            $mismaFicha = (int) $regalo['id'] === $idProducto;
            $etiqueta = self::etiqueta($d['formato'], $mismaFicha);
            $salida['ipas'][] = (int) $ipa;
            $salida['por'][(int) $ipa] = [
                'id' => (int) $regalo['id'],
                'ipa' => (int) $regalo['ipa'],
                'nombre' => self::nombreCorto($d['nombre'], $d['formato']),
                'formato' => trim((string) preg_replace('/^[^:]+:\s*/u', '', $d['formato'])),
                'precio' => (float) $d['precio'],
                'pvpr' => (float) $d['pvpr'],
                'peso' => (float) $d['peso'],
                'imagen' => $d['imagen'] && $d['link_rewrite'] !== '' ? $link->getImageLink($d['link_rewrite'], (string) $d['imagen'], $tipoImagen) : '',
                'url' => $link->getProductLink((int) $regalo['id'], $d['link_rewrite'] !== '' ? $d['link_rewrite'] : null, null, null, null, $idTienda, (int) $regalo['ipa']),
                'etiqueta' => $etiqueta,
                'corto' => self::corto($d['formato'], $d['nombre'], $mismaFicha, (string) ($p['corto'] ?? '')),
                'clase' => self::clase($etiqueta, $mismaFicha),
                'n' => (int) $p['n'],
                'por' => (string) $p['por'],
                'agotado' => !$hay,
                'hasta' => (string) $p['hasta'],
                'quedan' => (int) ($p['cupo'] ?? 0) > 0 && !empty($p['cupo_ficha']) ? KpcCupo::quedanPara((int) $p['id_kpy_coupon'], $idCart) : null,
            ];
            if ($salida['banda'] === '') {
                $salida['banda'] = $etiqueta;
                $salida['codigo'] = (string) $p['codigo'];
                $salida['nombre'] = (string) $p['nombre'];
                $salida['hasta'] = (string) $p['hasta'];
                $salida['quedan'] = $salida['por'][(int) $ipa]['quedan'];
            }
        }

        return $salida['ipas'] === [] ? null : $salida;
    }

    /**
     * Lo regalado en una cesta, también después de pedirla (al pedir, la fila de la cesta pasa a la
     * del pedido): clave del artículo => unidades. Para decir «Regalo» en la línea de la cesta, de la
     * confirmación y del historial.
     */
    public static function regaladasDe(int $idCart): array
    {
        static $delPedido = [];
        if ($idCart <= 0) {
            return [];
        }
        $salida = self::previasDe($idCart);
        if ($salida !== []) {
            return $salida;
        }
        if (!isset($delPedido[$idCart])) {
            $delPedido[$idCart] = [];
            $idOrder = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT id_order FROM ' . _DB_PREFIX_ . 'orders WHERE id_cart = ' . $idCart . ' ORDER BY id_order DESC'
            );
            if ($idOrder > 0) {
                foreach (self::delPedido($idOrder) as $k => $n) {
                    [$id, $ipa] = array_map('intval', explode('-', $k));
                    $delPedido[$idCart][KpcAlcance::clave($id, $ipa)] = (int) $n;
                }
            }
        }

        return $delPedido[$idCart];
    }

    /**
     * Buscar artículos para la pantalla (el regalo «el mismo para todos», el de reserva y las parejas
     * a mano): por nombre, marca, EAN, referencia, id o «producto-formato». Solo lo que se vende, un
     * formato por fila, con precio y stock.
     *
     * @return array<int, array{valor: string, id: int, ipa: int, nombre: string, formato: string, marca: string, ean: string, precio: float, stock: int}>
     */
    public static function buscar(string $q, int $idTienda, int $idLang, int $max = 30): array
    {
        $q = trim(preg_replace('/\s+/u', ' ', $q) ?? '');
        if (mb_strlen($q) < 2) {
            return [];
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $vendible = "ps.id_shop = {$idTienda} AND ps.active = 1 AND ps.available_for_order = 1";
        $ids = [];
        $soloIpa = 0;
        if (preg_match('/^(\d+)(?:-(\d+))?$/', $q, $m)) {
            $ids[] = (int) $m[1];
            $soloIpa = (int) ($m[2] ?? 0);
            if ($soloIpa === 0 && mb_strlen($m[1]) >= 8) {
                // Un número largo es un EAN, no un id.
                $ids = [];
            }
        }
        if ($ids === [] && preg_match('/^\d{8,14}$/', $q)) {
            $ean = pSQL($q);
            foreach ($db->executeS(
                "SELECT DISTINCT p.id_product FROM {$p}product p
                 INNER JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND {$vendible}
                 LEFT JOIN {$p}product_attribute pa ON pa.id_product = p.id_product
                 WHERE p.ean13 = '{$ean}' OR pa.ean13 = '{$ean}' OR p.reference = '{$ean}' OR pa.reference = '{$ean}' LIMIT 12"
            ) ?: [] as $f) {
                $ids[] = (int) $f['id_product'];
            }
        }
        if ($ids === []) {
            $condiciones = [];
            foreach (explode(' ', $q) as $palabra) {
                $palabra = pSQL($palabra, true);
                $condiciones[] = "(pl.name LIKE '%{$palabra}%' OR IFNULL(m.name, '') LIKE '%{$palabra}%')";
            }
            $ref = pSQL($q, true);
            foreach ($db->executeS(
                "SELECT p.id_product FROM {$p}product p
                 INNER JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND {$vendible}
                 INNER JOIN {$p}product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = {$idLang} AND pl.id_shop = {$idTienda}
                 LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
                 WHERE (" . implode(' AND ', $condiciones) . ") OR p.reference LIKE '{$ref}%'
                 ORDER BY pl.name LIMIT 12"
            ) ?: [] as $f) {
                $ids[] = (int) $f['id_product'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        // Sus formatos a la venta (o el producto entero, si no tiene), en orden de tamaño.
        $articulos = [];
        $conFormatos = [];
        foreach ($db->executeS(
            "SELECT pa.id_product, pa.id_product_attribute AS ipa, " . KpcAlcance::sqlApagado() . " AS apagado,
                    GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre
             FROM {$p}product_attribute pa
             LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idLang}
             WHERE pa.id_product IN (" . implode(',', $ids) . ')
             GROUP BY pa.id_product_attribute'
        ) ?: [] as $f) {
            $conFormatos[(int) $f['id_product']] = true;
            if ((int) $f['apagado'] || ($soloIpa > 0 && (int) $f['ipa'] !== $soloIpa)) {
                continue;
            }
            $articulos[] = ['id' => (int) $f['id_product'], 'ipa' => (int) $f['ipa'], 'orden' => KpcFormatos::orden(KpcFormatos::tamano((string) $f['nombre']))];
        }
        foreach ($ids as $id) {
            if (!isset($conFormatos[$id])) {
                $articulos[] = ['id' => $id, 'ipa' => 0, 'orden' => [0, 0.0, '']];
            }
        }
        usort($articulos, static fn (array $a, array $b): int => [array_search($a['id'], $ids, true), $a['orden']] <=> [array_search($b['id'], $ids, true), $b['orden']]);
        $articulos = array_slice($articulos, 0, $max);

        $salida = [];
        foreach (self::datos($articulos, $idTienda, $idLang) as $k => $d) {
            if (!$d['vendible'] || $d['apagado']) {
                continue;
            }
            $salida[] = [
                'valor' => $k,
                'id' => $d['id'],
                'ipa' => $d['ipa'],
                'nombre' => $d['nombre'],
                'formato' => trim((string) preg_replace('/^[^:]+:\s*/u', '', $d['formato'])),
                'marca' => $d['marca'],
                'ean' => $d['ean'],
                'precio' => $d['precio'],
                'stock' => $d['stock'],
            ];
        }

        return $salida;
    }

    /**
     * Cuántos regalos de cada promoción están ya comprometidos, para el cupo: los dados en pedidos
     * más los reservados en otras cestas vivas (`KpcCupo`). Una promoción agotada sale con su cupo
     * entero (no da ni uno más).
     *
     * @param int $sinCesta la cesta que pregunta (sus reservas no cuentan en su contra)
     */
    public static function usados(array $idsCupon, int $sinCesta = 0): array
    {
        $salida = [];
        foreach (KpcCupo::estado($idsCupon, $sinCesta) as $id => $e) {
            $salida[$id] = $e['cupo'] > 0 && $e['agotado'] !== '' ? $e['cupo'] : $e['vendidas'] + $e['reservadas'];
        }

        return $salida;
    }

    private static function fila(array $f): array
    {
        return [
            'id_kpy_coupon' => (int) $f['id_kpy_coupon'],
            'clave' => (int) $f['clave'],
            'id_product' => (int) $f['id_product'],
            'n' => max(1, (int) $f['n']),
            'r' => ['id' => (int) $f['r_id_product'], 'ipa' => (int) $f['r_ipa']],
            's' => (int) $f['s_id_product'] > 0 ? ['id' => (int) $f['s_id_product'], 'ipa' => (int) $f['s_ipa']] : null,
            'origen' => (string) $f['origen'],
        ];
    }

    // ------------------------------------------------------------------------------------------
    // La cesta: el plan (puro) y aplicarlo (PrestaShop)
    // ------------------------------------------------------------------------------------------

    /**
     * Qué regalos tiene que haber en una cesta. Es la regla de negocio, sin PrestaShop, y se rehace
     * entera en cada guardado de la cesta: no hay estados que mantener, solo saber qué parte de cada
     * línea era regalo hasta ahora (`$previas`) para no contar esas unidades como compradas.
     *
     * @param array<int, array{clave: int, id_product: int, ipa: int, qty: int}> $lineas la cesta
     * @param array<int, int> $previas clave del artículo regalado => unidades regaladas hasta ahora
     * @param array<int, array<int, array>> $parejas lo de `deClaves()`: disparador => parejas
     * @param callable $disponible fn(int $idProducto, int $ipa, int $necesita): bool — ¿hay stock
     *                             (y se vende) para tener `necesita` unidades en la cesta?
     * @param array<int, int> $usados promoción => regalos ya dados en pedidos (para el tope)
     * @param array<int, mixed> $elegidas clave del disparador => su elección (`unidades`: cuántas
     *                                    van con regalo, la tarjeta «12 kg + 3 kg gratis») o `true`
     *                                    (todas). Las demás unidades no regalan nada, aunque tengan
     *                                    pareja: son de la tarjeta de siempre.
     *
     * @return array{regalos: array<string, array{promo: int, disparador: int, id_product: int, ipa: int,
     *               cantidad: int, unidades: int, por: string, hasta: string, nombre: string}>,
     *               totales: array<int, int>, pagadas: array<int, int>, avisos: array<string, array>,
     *               unidades: array<int, int>}
     *         regalos: por «promoción|disparador»; totales: clave del regalo => unidades regaladas;
     *         pagadas: clave => unidades que paga el cliente; avisos: por «promoción|disparador»;
     *         unidades: disparador => unidades que van de verdad con regalo
     */
    public static function plan(array $lineas, array $previas, array $parejas, callable $disponible, array $usados = [], array $elegidas = []): array
    {
        $cantidad = [];
        $producto = [];
        foreach ($lineas as $l) {
            $clave = (int) $l['clave'];
            $cantidad[$clave] = ($cantidad[$clave] ?? 0) + (int) $l['qty'];
            $producto[$clave] = ['id' => (int) $l['id_product'], 'ipa' => (int) $l['ipa']];
        }
        $pagadas = [];
        foreach ($cantidad as $clave => $q) {
            $pagadas[$clave] = max(0, $q - (int) ($previas[$clave] ?? 0));
        }

        $regalos = [];
        $totales = [];
        $avisos = [];
        $conRegalo = [];     // disparador => unidades que van de verdad con regalo
        $porPedido = [];     // promoción => ya ha regalado en esta cesta (modo «uno por pedido»)
        $dadosAqui = [];     // promoción => regalos planeados en esta cesta (para el tope)
        foreach ($lineas as $l) {
            $clave = (int) $l['clave'];
            // Las unidades que el cliente quiere con regalo; las demás de la línea son de la tarjeta
            // de siempre y no regalan nada.
            $quiere = self::unidadesDe($elegidas[$clave] ?? null, (int) ($pagadas[$clave] ?? 0));
            if ($quiere <= 0 || empty($parejas[$clave])) {
                continue;
            }
            foreach ($parejas[$clave] as $p) {
                $promo = (int) $p['id_kpy_coupon'];
                $sello = $promo . '|' . $clave;
                if (($p['por'] ?? 'unidad') === 'pedido') {
                    if (!empty($porPedido[$promo])) {
                        continue;
                    }
                    // Uno por pedido: una unidad va con regalo.
                    $uds = 1;
                    $cada = 1;
                } else {
                    $uds = $quiere;
                    $cada = max(1, (int) ($p['n'] ?? 1));
                }
                $tope = (int) ($p['tope'] ?? 0);
                if ($tope > 0) {
                    $quedan = $tope - (int) ($usados[$promo] ?? 0) - (int) ($dadosAqui[$promo] ?? 0);
                    // Hasta donde llegue el tope, por unidades enteras (cada una con todos sus regalos).
                    $uds = min($uds, intdiv(max(0, $quedan), $cada));
                    if ($uds <= 0) {
                        $avisos[$sello] = ['motivo' => 'tope', 'promo' => $promo, 'disparador' => $clave];
                        continue;
                    }
                }
                $n = $uds * $cada;

                $elegido = null;
                foreach ([$p['r'] ?? null, $p['s'] ?? null] as $candidato) {
                    if ($candidato === null) {
                        continue;
                    }
                    $claveRegalo = KpcAlcance::clave((int) $candidato['id'], (int) $candidato['ipa']);
                    $necesita = ($pagadas[$claveRegalo] ?? 0) + ($totales[$claveRegalo] ?? 0) + $n;
                    if ($disponible((int) $candidato['id'], (int) $candidato['ipa'], $necesita)) {
                        $elegido = $candidato + ['clave' => $claveRegalo];
                        break;
                    }
                }
                if ($elegido === null) {
                    $avisos[$sello] = ['motivo' => 'sin_stock', 'promo' => $promo, 'disparador' => $clave, 'regalo' => $p['r'] ?? null];
                    continue;
                }

                $regalos[$sello] = [
                    'promo' => $promo,
                    'disparador' => $clave,
                    'id_product' => (int) $elegido['id'],
                    'ipa' => (int) $elegido['ipa'],
                    'cantidad' => $n,
                    'unidades' => $uds,
                    'por' => (string) ($p['por'] ?? 'unidad'),
                    'hasta' => (string) ($p['hasta'] ?? ''),
                    'nombre' => (string) ($p['nombre'] ?? ''),
                ];
                $totales[$elegido['clave']] = ($totales[$elegido['clave']] ?? 0) + $n;
                $dadosAqui[$promo] = ($dadosAqui[$promo] ?? 0) + $n;
                $porPedido[$promo] = true;
                $conRegalo[$clave] = max($conRegalo[$clave] ?? 0, $uds);
            }
        }

        return ['regalos' => $regalos, 'totales' => $totales, 'pagadas' => $pagadas, 'avisos' => $avisos, 'unidades' => $conRegalo];
    }

    /**
     * Cuántas unidades de una línea quiere el cliente con regalo: las de su elección, como mucho
     * las que paga. `true` (y una elección de antes de la 2.2.0, sin unidades) son todas.
     *
     * @param mixed $eleccion lo de `elegidasDe()` para esa línea, `true` o nada
     */
    public static function unidadesDe($eleccion, int $pagadas): int
    {
        if ($pagadas <= 0 || empty($eleccion)) {
            return 0;
        }
        $unidades = is_array($eleccion) ? (int) ($eleccion['unidades'] ?? 0) : 0;

        return $unidades > 0 ? min($unidades, $pagadas) : $pagadas;
    }

    /** ¿Todas las parejas de un disparador regalan «uno por pedido»? (entonces va con regalo una unidad) */
    private static function soloPorPedido(array $parejas): bool
    {
        if ($parejas === []) {
            return false;
        }
        foreach ($parejas as $p) {
            if (($p['por'] ?? 'unidad') !== 'pedido') {
                return false;
            }
        }

        return true;
    }

    /**
     * Deja las líneas de regalo de la cesta como dice el plan (añade, sube, baja o quita), apunta en
     * `kpy_coupon_regalo_cesta` qué parte de cada línea es regalo y reserva los regalos en el cupo
     * de su promoción. Se llama en cada guardado de la cesta, dentro del candado de `KpcCarrito`
     * (los cambios que hace aquí vuelven a guardar la cesta y no deben volver a entrar). El precio
     * de siempre de los disparadores elegidos lo pone después `KpcCarrito` (`KpcPrecioCesta`) con
     * lo que devuelve esto (`elegidas` y `regalos`).
     *
     * @return array{regalos: array, totales: array<int, int>, pagadas: array<int, int>, avisos: array, unidades: array<int, int>, cambiado: bool, elegidas: int[]}
     */
    public static function aplicar(Cart $cart, int $idTienda): array
    {
        $vacio = ['regalos' => [], 'totales' => [], 'pagadas' => [], 'avisos' => [], 'unidades' => [], 'cambiado' => false, 'elegidas' => []];
        if (!$cart->id) {
            return $vacio;
        }
        $idCart = (int) $cart->id;
        $lineas = self::lineasDe($cart->getProducts(true));
        $previas = self::previasDe($idCart);
        $elegidas = self::elegidasDe($idCart);
        $enCesta = [];
        foreach ($lineas as $l) {
            $enCesta[(int) $l['clave']] = $l;
        }
        // La elección de una línea que ya no está en la cesta no vale de nada: fuera.
        foreach (array_keys($elegidas) as $clave) {
            if (!isset($enCesta[$clave])) {
                self::elegir($idCart, (int) $clave, false);
                unset($elegidas[$clave]);
            }
        }
        if ($lineas === [] && $previas === []) {
            return $vacio;
        }
        $parejas = $elegidas !== [] ? self::deClaves($idTienda, array_keys($elegidas)) : [];
        // Una elección sin promoción en marcha (terminada, apagada, agotada, o el artículo pasó a un
        // cupón) no vale de nada: fuera (su precio de cesta lo quita `KpcPrecioCesta`). Si la
        // promoción vuelve, el cliente vuelve a elegir.
        foreach ($elegidas as $clave => $eleccion) {
            if (empty($parejas[$clave])) {
                self::elegir($idCart, (int) $clave, false);
                unset($elegidas[$clave]);
            }
        }
        if ($parejas === [] && $previas === [] && $elegidas === []) {
            return $vacio;
        }

        $promos = [];
        foreach ($parejas as $suyas) {
            foreach ($suyas as $p) {
                $promos[(int) $p['id_kpy_coupon']] = true;
            }
        }
        $disponible = static fn (int $id, int $ipa, int $necesita): bool => self::disponible($id, $ipa, $necesita, $idTienda);
        $plan = self::plan($lineas, $previas, $parejas, $disponible, self::usados(array_keys($promos), $idCart), $elegidas);

        // Lo elegido nunca pasa de lo que paga el cliente, y con «uno por pedido» es una unidad: las
        // demás son de la tarjeta de siempre (y así salen en la cesta). Lo que no cabe por el stock o
        // el cupo se queda apuntado: si vuelve a haber, vuelve el regalo.
        foreach ($elegidas as $clave => $eleccion) {
            $cabe = self::unidadesDe($eleccion, (int) ($plan['pagadas'][$clave] ?? 0));
            if (self::soloPorPedido($parejas[$clave] ?? [])) {
                $cabe = min($cabe, 1);
            }
            if ($cabe === (int) $eleccion['unidades']) {
                continue;
            }
            self::fijarUnidades($idCart, (int) $clave, $cabe);
            if ($cabe <= 0) {
                unset($elegidas[$clave]);
            } else {
                $elegidas[$clave]['unidades'] = $cabe;
            }
        }

        // Los regalos de esta cesta, reservados en el cupo de su promoción (si lo tiene).
        $reservas = [];
        foreach ($plan['regalos'] as $r) {
            $reservas[(int) $r['promo']][(int) $r['disparador']] = ($reservas[(int) $r['promo']][(int) $r['disparador']] ?? 0) + (int) $r['cantidad'];
        }
        foreach ($parejas as $suyas) {
            foreach ($suyas as $p) {
                if ((int) ($p['tope'] ?? 0) > 0) {
                    KpcCupo::reservar($idCart, (int) $p['id_kpy_coupon'], $reservas[(int) $p['id_kpy_coupon']] ?? []);
                }
            }
        }

        // Cada artículo que era o va a ser regalo: su cantidad en la cesta tiene que ser lo que paga el
        // cliente más lo que se le regala.
        $cambiado = false;
        $claves = array_unique(array_merge(array_keys($previas), array_keys($plan['totales'])));
        foreach ($claves as $clave) {
            $clave = (int) $clave;
            $articulo = self::articuloDe($clave, $enCesta, $plan['regalos']);
            if ($articulo === null) {
                continue;
            }
            $objetivo = (int) ($plan['pagadas'][$clave] ?? 0) + (int) ($plan['totales'][$clave] ?? 0);
            $actual = (int) ($enCesta[$clave]['qty'] ?? 0);
            if ($objetivo === $actual) {
                continue;
            }
            $hecho = self::ajustar($cart, $articulo['id'], $articulo['ipa'], $actual, $objetivo);
            $cambiado = true;
            if (!$hecho) {
                // La tienda no ha dejado añadir el regalo (sin stock de repente, apagado…): fuera del
                // plan, se avisa, y la línea se deja con lo que paga el cliente.
                foreach ($plan['regalos'] as $sello => $r) {
                    if (KpcAlcance::clave((int) $r['id_product'], (int) $r['ipa']) === $clave) {
                        $plan['avisos'][$sello] = ['motivo' => 'no_anade', 'promo' => $r['promo'], 'disparador' => $r['disparador'], 'regalo' => ['id' => $r['id_product'], 'ipa' => $r['ipa']]];
                        unset($plan['regalos'][$sello]);
                    }
                }
                unset($plan['totales'][$clave]);
                $pagadas = (int) ($plan['pagadas'][$clave] ?? 0);
                if ($pagadas !== $actual) {
                    self::ajustar($cart, $articulo['id'], $articulo['ipa'], $actual, $pagadas);
                }
                PrestaShopLogger::addLog('kpycoupons: no se ha podido dejar el regalo ' . $articulo['id'] . '-' . $articulo['ipa'] . ' en ' . $objetivo . ' uds.', 2, null, 'Cart', (int) $cart->id, true);
            }
        }

        self::escribirCesta($idCart, $plan['regalos']);

        return $plan + ['cambiado' => $cambiado, 'elegidas' => array_keys($elegidas)];
    }

    /**
     * La regla de carrito que descuenta los regalos: una por producto regalado, con el importe justo
     * de las unidades regaladas **sobre lo que ya cuestan** en esta cesta (Special Price del regalo
     * incluido y, si un cupón de Kompy de % le llega al regalo, descontado ese %: PrestaShop aplica
     * el cupón antes, y así el neto queda en 0,00 y no en negativo).
     *
     * PrestaShop limita el descuento a lo que valen las líneas del producto (no puede salir
     * negativo), y como el regalo es una línea con su precio, el pedido y la factura llevan la
     * línea y el descuento. Las reglas de productos que ya no se regalan se quitan.
     *
     * @param array $plan lo que devuelve `aplicar()`
     * @param array|null $cuponPuesto la fila del cupón de Kompy puesto en la cesta, si hay
     *
     * @return float lo que descuentan los regalos en esta cesta
     */
    public static function regla(Cart $cart, int $idTienda, array $plan, ?array $cuponPuesto = null): float
    {
        $productos = $cart->getProducts(true);
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        $totales = (array) ($plan['totales'] ?? []);

        // Por producto regalado: unidades, precio hoy y nombre.
        $porProducto = [];
        $precios = [];
        foreach ($productos as $linea) {
            $clave = KpcAlcance::clave((int) $linea['id_product'], (int) ($linea['id_product_attribute'] ?? 0));
            $precios[$clave] = Tools::ps_round((float) ($linea['price_wt'] ?? $linea['price'] ?? 0), 2);
            if (empty($totales[$clave])) {
                continue;
            }
            $idProducto = (int) $linea['id_product'];
            $porProducto[$idProducto]['claves'][$clave] = (int) $totales[$clave];
            $porProducto[$idProducto]['nombres'][] = (int) $totales[$clave] . ' × ' . self::nombreCorto((string) ($linea['name'] ?? ''), (string) ($linea['attributes'] ?? ''));
        }

        // El % del cupón de Kompy puesto, si le llega a algún regalo.
        $cubiertas = [];
        if ($cuponPuesto !== null && (float) ($cuponPuesto['porcentaje'] ?? 0) > 0) {
            $reparto = KpcReparto::deClaves($idTienda, array_keys($totales));
            $cubiertas = array_fill_keys($reparto[(int) $cuponPuesto['id_kpy_coupon']] ?? [], (float) $cuponPuesto['porcentaje']);
        }

        // Hasta cuándo valen las reglas: hasta que acaba la promoción más larga de las que regalan.
        $hasta = '';
        foreach ((array) ($plan['regalos'] ?? []) as $r) {
            $fin = KpcCupon::fechaValida($r['hasta'] ?? '');
            if ($fin !== null && strtotime($fin) > time() && ($hasta === '' || $fin > $hasta)) {
                $hasta = $fin;
            }
        }
        $hasta = $hasta !== '' ? date('Y-m-d H:i:s', (int) strtotime($hasta)) : date('Y-m-d H:i:s', strtotime('+7 days'));

        $total = 0.0;
        $vivas = [];
        foreach ($porProducto as $idProducto => $d) {
            $importe = 0.0;
            foreach ($d['claves'] as $clave => $n) {
                $pct = $cubiertas[$clave] ?? 0.0;
                $importe += $n * ($precios[$clave] ?? 0.0) * (1 - $pct / 100);
            }
            $importe = round($importe, 6);
            if ($importe <= 0) {
                continue;
            }
            $idRegla = KpcVale::asegurar($cart, self::codigo($cart, $idProducto), $idProducto, $importe, 'Regalo: ' . implode(' + ', $d['nombres']), $hasta);
            if ($idRegla > 0) {
                $vivas[$idRegla] = true;
                if (!in_array($idRegla, $puestas, true)) {
                    $cart->addCartRule($idRegla);
                }
                $total += $importe;
            }
        }

        // Las de esta cesta que ya no toca (el producto ya no se regala): fuera.
        foreach ($puestas as $id) {
            if (isset($vivas[$id])) {
                continue;
            }
            $regla = new CartRule($id);
            if (Validate::isLoadedObject($regla) && strpos((string) $regla->code, self::PREFIJO . '-' . (int) $cart->id . '-') === 0) {
                $cart->removeCartRule($id);
                if (!$cart->orderExists()) {
                    $regla->delete();
                }
            }
        }

        return round($total, 2);
    }

    /**
     * El Special Price de las unidades que lo conservan en una línea que va al precio habitual de la
     * cesta (`KpcPrecioCesta`): las de la tarjeta de siempre junto a unidades con regalo, o las que
     * caben en el cupo de su promoción junto a las que ya no. PrestaShop solo deja un precio por
     * línea: esas unidades recuperan aquí su Special Price con un vale de esta cesta por producto
     * (`KPYSP-…`), por la diferencia exacta, con el nombre de su promoción («Special Price: 1 × …»,
     * «Liquidación: 2 × …»). Sin líneas así, ningún vale (y los que hubiera, fuera).
     *
     * @param array $plan lo que devuelve `aplicar()` (sus `unidades`: las que van con regalo)
     * @param array<int, array{n: int, etiqueta: string, hasta: string}> $conEspecial por línea, las
     *                                 unidades que caben en el cupo de su Special Price (`KpcCupoCesta`)
     *
     * @return float lo que devuelven esos vales
     */
    public static function reglaEspecial(Cart $cart, array $plan, array $conEspecial = []): float
    {
        $idCart = (int) $cart->id;
        if ($idCart <= 0) {
            return 0.0;
        }
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        $precios = KpcPrecioCesta::de($idCart);
        $conRegalo = (array) ($plan['unidades'] ?? []);
        $porProducto = [];
        $hastaCupo = '';
        if ($precios !== []) {
            $previas = self::previasDe($idCart);
            foreach ($cart->getProducts() as $l) {
                $clave = KpcAlcance::clave((int) $l['id_product'], (int) ($l['id_product_attribute'] ?? 0));
                if (!empty($l['id_customization']) || empty($precios[$clave])) {
                    continue; // Sin precio de esta cesta, la línea ya va a su precio de hoy.
                }
                $sin = (int) $l['cart_quantity'] - (int) ($previas[$clave] ?? 0) - (int) ($conRegalo[$clave] ?? 0);
                $etiqueta = 'Special Price';
                if (isset($conEspecial[$clave])) {
                    // Con cupo, solo las que caben; las demás se quedan al precio habitual.
                    $sin = min($sin, (int) $conEspecial[$clave]['n']);
                    $etiqueta = (string) ($conEspecial[$clave]['etiqueta'] ?: $etiqueta);
                    $fin = KpcCupon::fechaValida($conEspecial[$clave]['hasta'] ?? '');
                    if ($fin !== null && strtotime($fin) > time() && ($hastaCupo === '' || $fin > $hastaCupo)) {
                        $hastaCupo = $fin;
                    }
                }
                if ($sin <= 0) {
                    continue;
                }
                $ipa = (int) ($l['id_product_attribute'] ?? 0);
                // El de hoy: el del catálogo, con su Special Price (sin el precio de esta cesta).
                $hoy = Tools::ps_round((float) Product::getPriceStatic((int) $l['id_product'], true, $ipa ?: null, 6), 2);
                $linea = Tools::ps_round((float) ($l['price_wt'] ?? 0), 2);
                if ($linea <= $hoy + 0.005) {
                    continue;
                }
                $id = (int) $l['id_product'];
                $porProducto[$id]['importe'] = ($porProducto[$id]['importe'] ?? 0.0) + ($linea - $hoy) * $sin;
                $porProducto[$id]['nombres'][] = $sin . ' × ' . self::nombreCorto((string) ($l['name'] ?? ''), (string) ($l['attributes'] ?? ''));
                $porProducto[$id]['etiqueta'] = $etiqueta;
            }
        }

        // Hasta cuándo valen: como los vales del regalo (la promoción más larga de las que regalan), o
        // la del Special Price con cupo.
        $hasta = $hastaCupo;
        foreach ((array) ($plan['regalos'] ?? []) as $r) {
            $fin = KpcCupon::fechaValida($r['hasta'] ?? '');
            if ($fin !== null && strtotime($fin) > time() && ($hasta === '' || $fin > $hasta)) {
                $hasta = $fin;
            }
        }
        $hasta = $hasta !== '' ? date('Y-m-d H:i:s', (int) strtotime($hasta)) : date('Y-m-d H:i:s', strtotime('+7 days'));

        $total = 0.0;
        $vivas = [];
        foreach ($porProducto as $id => $d) {
            $importe = round($d['importe'], 6);
            $idRegla = KpcVale::asegurar($cart, self::codigoEspecial($cart, $id), $id, $importe, $d['etiqueta'] . ': ' . implode(' + ', $d['nombres']), $hasta);
            if ($idRegla > 0) {
                $vivas[$idRegla] = true;
                if (!in_array($idRegla, $puestas, true)) {
                    $cart->addCartRule($idRegla);
                }
                $total += $importe;
            }
        }
        foreach ($puestas as $id) {
            if (isset($vivas[$id])) {
                continue;
            }
            $regla = new CartRule($id);
            if (Validate::isLoadedObject($regla) && self::esSuReglaEspecial((string) $regla->code, $idCart)) {
                $cart->removeCartRule($id);
                if (!$cart->orderExists()) {
                    $regla->delete();
                }
            }
        }

        return round($total, 2);
    }

    /** Un código por cesta y producto, imposible de adivinar desde fuera (como el del regalo). */
    public static function codigoEspecial(Cart $cart, int $idProducto): string
    {
        $firma = substr(md5($cart->secure_key . '|especial|' . $idProducto . '|' . _COOKIE_KEY_), 0, 8);

        return self::PREFIJO_ESPECIAL . '-' . (int) $cart->id . '-' . $idProducto . '-' . mb_strtoupper($firma);
    }

    /** ¿Es un vale «Special Price» de las líneas mezcladas de esta cesta? */
    public static function esSuReglaEspecial(string $codigo, int $idCart): bool
    {
        return strpos($codigo, self::PREFIJO_ESPECIAL . '-' . $idCart . '-') === 0;
    }

    /** Un código por cesta y producto regalado, imposible de adivinar desde fuera. */
    public static function codigo(Cart $cart, int $idProducto): string
    {
        $firma = substr(md5($cart->secure_key . '|regalo|' . $idProducto . '|' . _COOKIE_KEY_), 0, 8);

        return self::PREFIJO . '-' . (int) $cart->id . '-' . $idProducto . '-' . mb_strtoupper($firma);
    }

    /** ¿Es una regla de regalo de esta cesta? */
    public static function esSuRegla(string $codigo, int $idCart): bool
    {
        return strpos($codigo, self::PREFIJO . '-' . $idCart . '-') === 0;
    }

    /** Sube o baja una línea hasta la cantidad objetivo. @return bool si la tienda lo ha dejado */
    private static function ajustar(Cart $cart, int $idProducto, int $ipa, int $actual, int $objetivo): bool
    {
        if ($objetivo <= 0) {
            return (bool) $cart->deleteProduct($idProducto, $ipa);
        }
        if ($objetivo > $actual) {
            // Sin la regla automática de PrestaShop (no mete vales sin código) y comprobando el stock.
            return $cart->updateQty($objetivo - $actual, $idProducto, $ipa, false, 'up', 0, null, false, false) === true;
        }

        return $cart->updateQty($actual - $objetivo, $idProducto, $ipa, false, 'down', 0, null, false, false) === true;
    }

    /**
     * ¿Se puede tener en la cesta `necesita` unidades de este artículo? A la venta (activo y se
     * puede pedir) y con stock, o con «pedir sin stock» permitido (como lo decide PrestaShop al
     * añadir a la cesta). Sin caché propia: se mira cada vez, que el stock cambia (PrestaShop ya
     * guarda lo suyo y lo vacía cuando toca).
     */
    public static function disponible(int $idProducto, int $ipa, int $necesita, int $idTienda): bool
    {
        $p = _DB_PREFIX_;
        $fila = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            "SELECT ps.active, ps.available_for_order, p.id_product FROM {$p}product p
             INNER JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = " . (int) $idTienda . '
             WHERE p.id_product = ' . $idProducto
            . ($ipa > 0 ? " AND EXISTS (SELECT 1 FROM {$p}product_attribute pa WHERE pa.id_product_attribute = " . $ipa . ' AND pa.id_product = p.id_product)' : '')
        );
        if (!$fila || (int) $fila['active'] !== 1 || (int) $fila['available_for_order'] !== 1) {
            return false;
        }
        // «Pedir sin stock»: lo que diga la fila de stock **del formato** (es lo que mira PrestaShop
        // al añadir a la cesta; un producto puede permitirlo y uno de sus formatos no), y si no la
        // tiene, lo del producto.
        $restriccion = StockAvailable::addSqlShopRestriction(null, $idTienda);
        $sinStock = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            "SELECT out_of_stock FROM {$p}stock_available WHERE id_product = " . $idProducto . ' AND id_product_attribute = ' . $ipa . (is_string($restriccion) ? $restriccion : '')
        );
        if ($sinStock === false || $sinStock === null) {
            $sinStock = StockAvailable::outOfStock($idProducto, $idTienda);
        }
        if (Product::isAvailableWhenOutOfStock((int) $sinStock)) {
            return true;
        }

        return (int) StockAvailable::getQuantityAvailableByProduct($idProducto, $ipa ?: null, $idTienda) >= $necesita;
    }

    /** @return array<int, array{clave: int, id_product: int, ipa: int, qty: int}> */
    public static function lineasDe(array $productos): array
    {
        $salida = [];
        foreach ($productos as $l) {
            $id = (int) ($l['id_product'] ?? 0);
            $ipa = (int) ($l['id_product_attribute'] ?? 0);
            if ($id <= 0 || !empty($l['id_customization'])) {
                continue;
            }
            $salida[] = ['clave' => KpcAlcance::clave($id, $ipa), 'id_product' => $id, 'ipa' => $ipa, 'qty' => (int) ($l['cart_quantity'] ?? 0)];
        }

        return $salida;
    }

    /** @var array<int, array<int, int>> caché de `previasDe()` por cesta (se olvida al escribir) */
    private static array $previas = [];

    /** Lo que ya era regalo en esta cesta: clave del artículo regalado => unidades. */
    public static function previasDe(int $idCart): array
    {
        if (!isset(self::$previas[$idCart])) {
            $salida = [];
            foreach (self::filasCesta($idCart) as $f) {
                $clave = KpcAlcance::clave((int) $f['r_id_product'], (int) $f['r_ipa']);
                $salida[$clave] = ($salida[$clave] ?? 0) + (int) $f['cantidad'];
            }
            self::$previas[$idCart] = $salida;
        }

        return self::$previas[$idCart];
    }

    /**
     * Los nombres de unos formatos («Saco de 3 Kg»), de una consulta.
     *
     * @param int[] $ipas
     *
     * @return array<int, string>
     */
    public static function nombresFormato(array $ipas): array
    {
        $ipas = array_values(array_filter(array_map('intval', $ipas)));
        if ($ipas === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pa.id_product_attribute AS ipa, GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre
             FROM {$p}product_attribute pa
             LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
             LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idioma}
             WHERE pa.id_product_attribute IN (" . implode(',', $ipas) . ')
             GROUP BY pa.id_product_attribute'
        ) ?: [] as $f) {
            $salida[(int) $f['ipa']] = (string) $f['nombre'];
        }

        return $salida;
    }

    /** Las filas de regalo de una cesta, tal cual. */
    public static function filasCesta(int $idCart): array
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_cesta WHERE id_cart = ' . $idCart
        ) ?: [];
    }

    private static function escribirCesta(int $idCart, array $regalos): void
    {
        unset(self::$previas[$idCart]);
        $db = Db::getInstance();
        $db->delete('kpy_coupon_regalo_cesta', 'id_cart = ' . $idCart);
        $valores = [];
        foreach ($regalos as $r) {
            if ((int) $r['cantidad'] <= 0) {
                continue;
            }
            $valores[] = '(' . $idCart . ', ' . (int) $r['promo'] . ', ' . (int) $r['disparador'] . ', ' . (int) $r['id_product'] . ', ' . (int) $r['ipa'] . ', ' . (int) $r['cantidad'] . ', ' . (int) ($r['unidades'] ?? 0) . ', NOW())';
        }
        if ($valores !== []) {
            $db->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'kpy_coupon_regalo_cesta (id_cart, id_kpy_coupon, disparador, r_id_product, r_ipa, cantidad, unidades, date_upd) VALUES '
                . implode(',', $valores)
            );
        }
    }

    /** El artículo (id, ipa) de una clave: de la cesta, o del plan si aún no está en ella. */
    private static function articuloDe(int $clave, array $enCesta, array $regalos): ?array
    {
        if (isset($enCesta[$clave])) {
            return ['id' => (int) $enCesta[$clave]['id_product'], 'ipa' => (int) $enCesta[$clave]['ipa']];
        }
        foreach ($regalos as $r) {
            if (KpcAlcance::clave((int) $r['id_product'], (int) $r['ipa']) === $clave) {
                return ['id' => (int) $r['id_product'], 'ipa' => (int) $r['ipa']];
            }
        }
        if ($clave < 0) {
            return ['id' => -$clave, 'ipa' => 0];
        }
        $idProducto = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT id_product FROM ' . _DB_PREFIX_ . 'product_attribute WHERE id_product_attribute = ' . $clave
        );

        return $idProducto > 0 ? ['id' => $idProducto, 'ipa' => $clave] : null;
    }

    // ------------------------------------------------------------------------------------------
    // La elección del cliente: qué líneas van con regalo, y su precio
    // ------------------------------------------------------------------------------------------

    /** Días que se guarda la elección de una cesta que nadie toca; luego el barrido la quita, con su precio. */
    public const DIAS_ELECCION = 30;

    /**
     * Las líneas de una cesta con unidades que el cliente ha pedido con regalo (la tarjeta «12 kg +
     * 3 kg gratis»): clave del disparador => cuántas (`unidades`), su precio de cesta (`precio`: el
     * `id_specific_price` que puso `KpcPrecioCesta`, 0 si no lleva) y desde cuándo. Es la **única**
     * memoria del regalo: todo lo demás se rehace en cada guardado de la cesta a partir de esto.
     *
     * @return array<int, array{precio: int, unidades: int, desde: string}>
     */
    public static function elegidasDe(int $idCart): array
    {
        if ($idCart <= 0) {
            return [];
        }
        $salida = [];
        $precios = KpcPrecioCesta::de($idCart);
        // De la base de datos principal: se lee justo después de escribirla, en la misma petición.
        foreach (Db::getInstance()->executeS(
            'SELECT clave, unidades, date_upd FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_eleccion WHERE id_cart = ' . $idCart
        ) ?: [] as $f) {
            $salida[(int) $f['clave']] = ['precio' => (int) ($precios[(int) $f['clave']] ?? 0), 'unidades' => (int) $f['unidades'], 'desde' => (string) $f['date_upd']];
        }

        return $salida;
    }

    /**
     * Apunta cuántas unidades de una línea van con regalo; con 0, ninguna: la elección se quita (su
     * precio de cesta lo quita `KpcPrecioCesta` en el repaso de la cesta, si ya no hace falta): el
     * artículo vuelve a su precio de hoy, con su Special Price si lo tiene.
     */
    public static function fijarUnidades(int $idCart, int $clave, int $unidades): void
    {
        if ($idCart <= 0 || $clave === 0) {
            return;
        }
        $db = Db::getInstance();
        if ($unidades > 0) {
            $db->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'kpy_coupon_regalo_eleccion (id_cart, clave, id_specific_price, unidades, date_upd)
                 VALUES (' . $idCart . ', ' . $clave . ', 0, ' . $unidades . ', NOW())
                 ON DUPLICATE KEY UPDATE unidades = VALUES(unidades), date_upd = NOW()'
            );

            return;
        }
        $db->delete('kpy_coupon_regalo_eleccion', 'id_cart = ' . $idCart . ' AND clave = ' . $clave);
    }

    /** Con regalo, todas las unidades que paga ahora la línea (al menos una); sin regalo, ninguna. */
    public static function elegir(int $idCart, int $clave, bool $conRegalo): void
    {
        if (!$conRegalo) {
            self::fijarUnidades($idCart, $clave, 0);

            return;
        }
        $articulo = self::articuloDe($clave, [], []);
        $pagadas = $articulo !== null ? self::pagadasEn($idCart, $articulo['id'], $articulo['ipa']) : 0;
        self::fijarUnidades($idCart, $clave, max(1, $pagadas));
    }

    /** @var array<int, array{firma: string, claves: array<int, array{con: bool, antes: int}>}> lo pedido en esta petición, por cesta, hasta que se guarde */
    private static array $pendientes = [];

    /**
     * Antes de que la cesta cambie la cantidad de una línea (hook `actionCartUpdateQuantityBefore`,
     * solo con los cambios del cliente): si la petición dice con qué tarjeta va (`kpy_regalo`: la
     * ficha al añadir, o el «+», el «−» y la papelera de cada parte de la línea en la cesta), se
     * apunta cuántas unidades pagaba la línea. Ya guardada la cesta, `trasElCambio` reparte lo que
     * ha cambiado de verdad (si no había stock para todo, lo que entró). Sin el parámetro («pedir de
     * nuevo», «llévalo junto», otro módulo…) la elección se queda como estaba. El formato, como lo
     * saca la cesta de PrestaShop (`CartController`), y solo en la cesta de la visita.
     */
    public static function antesDelCambio(Cart $cart, int $idProducto, int $ipa, int $idCustomization = 0): void
    {
        if (!$cart->id || $idCustomization > 0 || !Tools::getIsset('kpy_regalo')) {
            return;
        }
        $contexto = Context::getContext();
        if (!$contexto->cart instanceof Cart || (int) $contexto->cart->id !== (int) $cart->id) {
            return;
        }
        if ((int) Tools::getValue('id_product') !== $idProducto || self::ipaDeLaPeticion($idProducto) !== $ipa) {
            return;
        }
        $idCart = (int) $cart->id;
        $firma = self::firmaPeticion();
        if ((self::$pendientes[$idCart]['firma'] ?? '') !== $firma) {
            self::$pendientes[$idCart] = ['firma' => $firma, 'claves' => []];
        }
        self::$pendientes[$idCart]['claves'][KpcAlcance::clave($idProducto, $ipa)] = [
            'con' => (string) Tools::getValue('kpy_regalo') === '1',
            'antes' => self::pagadasEn($idCart, $idProducto, $ipa),
        ];
    }

    /**
     * Ya guardada la cesta (hook `actionCartSave`), lo apuntado en `antesDelCambio`: con
     * `kpy_regalo=1`, lo que ha subido o bajado la línea son unidades con regalo; con `kpy_regalo=0`,
     * de la tarjeta de siempre (las que van con regalo se quedan, si caben). Una vez por cambio:
     * PrestaShop guarda la cesta más de una vez en la misma petición.
     */
    public static function trasElCambio(Cart $cart): void
    {
        $idCart = (int) $cart->id;
        $pendiente = self::$pendientes[$idCart] ?? null;
        if ($pendiente === null) {
            return;
        }
        unset(self::$pendientes[$idCart]);
        if ($pendiente['firma'] !== self::firmaPeticion()) {
            return; // De otra petición (solo pasa en las pruebas por consola, que hacen varias seguidas).
        }
        $elegidas = self::elegidasDe($idCart);
        foreach ($pendiente['claves'] as $clave => $p) {
            $articulo = self::articuloDe((int) $clave, [], []);
            if ($articulo === null) {
                continue;
            }
            $despues = self::pagadasEn($idCart, $articulo['id'], $articulo['ipa']);
            $tenia = self::unidadesDe($elegidas[$clave] ?? null, (int) $p['antes']);
            $quiere = $p['con'] ? $tenia + $despues - (int) $p['antes'] : $tenia;
            self::fijarUnidades($idCart, (int) $clave, max(0, min($quiere, $despues)));
        }
    }

    /** Lo que identifica la petición de ahora (para no repartir el cambio de una en otra). */
    private static function firmaPeticion(): string
    {
        return md5(serialize([$_GET, $_POST, $_SERVER['REQUEST_TIME_FLOAT'] ?? 0]));
    }

    /** El formato de la petición: de los grupos de la ficha (`group[…]`) o del parámetro. */
    private static function ipaDeLaPeticion(int $idProducto): int
    {
        $grupos = Tools::getValue('group');
        if (Tools::getIsset('group') && is_array($grupos)) {
            try {
                return (int) Product::getIdProductAttributeByIdAttributes($idProducto, $grupos);
            } catch (Throwable $e) {
                return -1;
            }
        }

        return (int) Tools::getValue('id_product_attribute', Tools::getValue('ipa'));
    }

    /** Las unidades de una línea que paga el cliente (sin las regaladas), de la base de datos. */
    private static function pagadasEn(int $idCart, int $idProducto, int $ipa): int
    {
        $hay = (int) Db::getInstance()->getValue(
            'SELECT SUM(quantity) FROM ' . _DB_PREFIX_ . 'cart_product WHERE id_cart = ' . $idCart
            . ' AND id_product = ' . $idProducto . ' AND id_product_attribute = ' . $ipa . ' AND id_customization = 0'
        );

        return max(0, $hay - (int) (self::previasDe($idCart)[KpcAlcance::clave($idProducto, $ipa)] ?? 0));
    }
    /**
     * El cliente quita con la papelera el vale «Regalo: …» de la cesta: las líneas que regalaban ese
     * producto dejan de ir con regalo (vuelven a su precio de hoy y el regalo sale de la cesta).
     *
     * @return bool si era un vale de regalo de esta cesta
     */
    public static function quitarPorRegla(Cart $cart, int $idRegla): bool
    {
        if ($idRegla <= 0 || !$cart->id) {
            return false;
        }
        $regla = new CartRule($idRegla);
        if (!Validate::isLoadedObject($regla) || !self::esSuRegla((string) $regla->code, (int) $cart->id)) {
            return false;
        }
        $partes = explode('-', (string) $regla->code);
        $idProducto = (int) ($partes[2] ?? 0);
        foreach (self::filasCesta((int) $cart->id) as $f) {
            if ((int) $f['r_id_product'] === $idProducto) {
                self::elegir((int) $cart->id, (int) $f['disparador'], false);
            }
        }

        return true;
    }

    /**
     * «Pedir de nuevo» (la copia de una cesta ya pedida, `Cart::duplicate()`): PrestaShop copia todas
     * las líneas, también las unidades que fueron regalo, que en la cesta nueva se cobrarían. Aquí se
     * quitan esas unidades y se vuelve a elegir el regalo en sus disparadores (lo que eligió el cliente
     * la vez anterior). Al repasarse la cesta, el regalo vuelve si la promoción sigue (con el precio
     * de la versión con regalo) o se queda fuera si ya terminó.
     */
    public static function reordenar(int $idCartViejo, Cart $nueva): void
    {
        if ($idCartViejo <= 0 || !$nueva->id) {
            return;
        }
        $idOrder = (int) Order::getIdByCartId($idCartViejo);
        if ($idOrder <= 0) {
            return;
        }
        $filas = Db::getInstance()->executeS(
            'SELECT disparador, r_id_product, r_ipa, SUM(cantidad) AS n FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_pedido
             WHERE id_order = ' . $idOrder . ' GROUP BY disparador, r_id_product, r_ipa'
        ) ?: [];
        if ($filas === []) {
            return;
        }
        // Primero, fuera las unidades regaladas. Sin ninguna elección puesta todavía, los repasos de
        // la cesta que esto provoca no cambian nada.
        $regaladas = [];
        foreach ($filas as $f) {
            $k = (int) $f['r_id_product'] . '-' . (int) $f['r_ipa'];
            $regaladas[$k] = ($regaladas[$k] ?? 0) + (int) $f['n'];
        }
        foreach ($regaladas as $k => $n) {
            [$id, $ipa] = array_map('intval', explode('-', $k));
            $hay = (int) ($nueva->getProductQuantity($id, $ipa)['quantity'] ?? 0);
            if ($hay > 0) {
                self::ajustar($nueva, $id, $ipa, $hay, max(0, $hay - $n));
            }
        }
        // Luego, el regalo otra vez en las unidades que lo llevaron (las de los disparadores que siguen
        // en la cesta): tantas como regalos tuvieron, a razón de los de cada unidad.
        $porDisparador = [];
        foreach ($filas as $f) {
            $porDisparador[(int) $f['disparador']] = max($porDisparador[(int) $f['disparador']] ?? 0, (int) $f['n']);
        }
        $parejas = self::deClaves((int) $nueva->id_shop ?: (int) Configuration::get('PS_SHOP_DEFAULT'), array_keys($porDisparador));
        foreach ($porDisparador as $clave => $regalados) {
            $articulo = self::articuloDe($clave, [], []);
            $pagadas = $articulo !== null ? self::pagadasEn((int) $nueva->id, $articulo['id'], $articulo['ipa']) : 0;
            if ($pagadas <= 0) {
                continue;
            }
            $p = $parejas[$clave][0] ?? null;
            $cada = $p !== null && ($p['por'] ?? 'unidad') !== 'pedido' ? max(1, (int) ($p['n'] ?? 1)) : 0;
            $unidades = $cada > 0 ? (int) ceil($regalados / $cada) : 1;
            self::fijarUnidades((int) $nueva->id, $clave, max(1, min($pagadas, $unidades)));
        }
    }

    /**
     * Las partes de cada línea de la cesta con algo de regalo o de cupo, para pintarlas por separado
     * (`KpcLineas`): en una línea de PrestaShop pueden ir unidades con regalo (la tarjeta «12 kg +
     * 3 kg gratis»), unidades de la tarjeta de siempre (con su Special Price, o sin él porque el cupo
     * se acabó) y unidades que son el regalo de otra línea. Una vez por cesta y estado de la cesta,
     * en cada petición.
     *
     * @return array<int, array{regaladas: int, pagadas: int, con: int, eleccion: bool, clase: string,
     *               corto: string, regalos: string[], habitual: bool, hoy: float, precio: float,
     *               formato: string, con_saco: string[], sp: ?int, etiqueta: string, etiqueta_clase: string}>
     *         clave de la línea => lo suyo:
     *         regaladas, las unidades que son regalo; pagadas, las que paga el cliente; con, las que
     *         van con regalo (al precio de siempre); eleccion, si el cliente eligió regalo en ella;
     *         clase, kilos o regalo; corto, lo que dice la tarjeta («3 kg gratis»); regalos, lo que
     *         regalan («1 × Saco de 3 Kg»); habitual, si la línea va al precio de siempre (precio de
     *         esta cesta); hoy, el precio de hoy de la tarjeta de siempre; precio, el de la línea;
     *         formato, el suyo; con_saco, con qué va el regalo; de, en la línea de un regalo, cuántas
     *         unidades regala cada disparador (clave del disparador => unidades); sacos, el nombre de
     *         cada disparador («Saco de 12 Kg»); sp, con cupo, cuántas unidades de la tarjeta de
     *         siempre conservan el Special Price (null = todas); etiqueta, cómo se llama su Special
     *         Price («Liquidación»), y etiqueta_clase, su color
     */
    public static function partesDe(Cart $cart): array
    {
        static $cache = [];
        $idCart = (int) $cart->id;
        if ($idCart <= 0) {
            return [];
        }
        $filas = self::filasCesta($idCart);
        $elegidas = self::elegidasDe($idCart);
        $precios = KpcPrecioCesta::de($idCart);
        if ($filas === [] && $elegidas === [] && $precios === []) {
            return [];
        }
        $lineas = [];
        foreach ($cart->getProducts() as $l) {
            if (empty($l['id_customization'])) {
                $lineas[KpcAlcance::clave((int) $l['id_product'], (int) $l['id_product_attribute'])] = $l;
            }
        }
        $especiales = KpcCupoCesta::especialesDe($cart);
        $firma = md5(json_encode([$filas, $elegidas, $precios, $especiales, array_map(static fn ($l) => [(int) $l['cart_quantity'], (float) $l['price_wt']], $lineas)]));
        if (isset($cache[$idCart][$firma])) {
            return $cache[$idCart][$firma];
        }
        $previas = self::previasDe($idCart);
        $nueva = static function (array $l, int $clave) use ($previas, $especiales): array {
            $qty = (int) $l['cart_quantity'];
            $regaladas = min($qty, (int) ($previas[$clave] ?? 0));
            $formato = trim((string) ($l['attributes_small'] ?? ''));
            $e = $especiales[$clave] ?? null;

            return ['regaladas' => $regaladas, 'pagadas' => $qty - $regaladas, 'con' => 0, 'eleccion' => false, 'clase' => '',
                'corto' => '', 'regalos' => [], 'habitual' => false, 'hoy' => 0.0, 'precio' => round((float) $l['price_wt'], 2),
                'formato' => $formato !== '' ? $formato : (string) $l['name'], 'con_saco' => [], 'de' => [], 'sacos' => [],
                'sp' => $e !== null && $e['cupo'] ? (int) $e['n'] : null,
                'etiqueta' => (string) ($e['etiqueta'] ?? 'Special Price'), 'etiqueta_clase' => (string) ($e['clase'] ?? 'especial')];
        };
        // Lo que la promoción dice detrás del «+» en la tarjeta, si lo dice.
        $dichos = [];
        foreach (KpcCupon::enVigor((int) $cart->id_shop ?: (int) Configuration::get('PS_SHOP_DEFAULT')) as $c) {
            $dichos[(int) $c['id_kpy_coupon']] = (string) ($c['regalo_corto'] ?? '');
        }
        $salida = [];
        foreach ($filas as $f) {
            $r = KpcAlcance::clave((int) $f['r_id_product'], (int) $f['r_ipa']);
            $d = (int) $f['disparador'];
            $lr = $lineas[$r] ?? null;
            $ld = $lineas[$d] ?? null;
            $n = (int) $f['cantidad'];
            if ($lr === null || $ld === null || $n <= 0) {
                continue;
            }
            $salida[$r] ??= $nueva($lr, $r);
            $salida[$d] ??= $nueva($ld, $d);
            $misma = (int) $lr['id_product'] === (int) $ld['id_product'];
            $formatoR = trim((string) ($lr['attributes_small'] ?? ''));
            $formatoD = trim((string) ($ld['attributes_small'] ?? ''));
            $regalo = $misma && $formatoR !== '' ? $formatoR : self::nombreCorto((string) $lr['name'], $formatoR);
            $saco = $misma && $formatoD !== '' ? $formatoD : self::nombreCorto((string) $ld['name'], $formatoD);
            $salida[$r]['con_saco'][$saco] = $saco;
            $salida[$r]['de'][$d] = ($salida[$r]['de'][$d] ?? 0) + $n;
            $salida[$r]['sacos'][$d] = $saco;
            // Las unidades del disparador que llevan este regalo (las filas de antes de la 2.2.0 no lo
            // dicen: todas las que paga).
            $unidades = (int) ($f['unidades'] ?? 0);
            $salida[$d]['con'] = max($salida[$d]['con'], $unidades > 0 ? $unidades : $salida[$d]['pagadas']);
            $salida[$d]['regalos'][] = $n . ' × ' . $regalo;
            $salida[$d]['clase'] = self::clase(self::etiqueta($formatoR, $misma), $misma);
            $salida[$d]['corto'] = self::corto($formatoR, (string) $lr['name'], $misma, $dichos[(int) $f['id_kpy_coupon']] ?? '');
        }
        foreach ($elegidas as $clave => $e) {
            if (!isset($lineas[$clave])) {
                continue;
            }
            $salida[$clave] ??= $nueva($lineas[$clave], $clave);
            $salida[$clave]['eleccion'] = true;
        }
        // Las líneas al precio habitual de la cesta (por el regalo o por el cupo): el de hoy de la
        // tarjeta de siempre es el del catálogo, sin el precio de esta cesta.
        foreach (array_keys($precios) as $clave) {
            if (!isset($lineas[$clave])) {
                continue;
            }
            $salida[$clave] ??= $nueva($lineas[$clave], $clave);
            $salida[$clave]['habitual'] = true;
            $ipa = (int) $lineas[$clave]['id_product_attribute'];
            $salida[$clave]['hoy'] = round((float) Product::getPriceStatic((int) $lineas[$clave]['id_product'], true, $ipa ?: null, 6), 2);
        }

        return $cache[$idCart][$firma] = $salida;
    }
    /** Las elecciones de una cesta y sus precios de cesta, fuera (al pedir). */
    private static function olvidarElecciones(int $idCart): void
    {
        foreach (array_keys(self::elegidasDe($idCart)) as $clave) {
            self::elegir($idCart, (int) $clave, false);
        }
        KpcPrecioCesta::quitarTodos($idCart);
    }

    // ------------------------------------------------------------------------------------------
    // El pedido
    // ------------------------------------------------------------------------------------------

    /**
     * Al validar el pedido: lo regalado queda en `kpy_coupon_regalo_pedido` (el sell-out para la
     * marca y el tope de la campaña), y la línea del pedido dice «(regalo)» para quien lo prepare.
     * La regla de carrito ya la ha apuntado PrestaShop en `order_cart_rule` con su importe.
     */
    public static function alPedido(Order $order): void
    {
        $filas = self::filasCesta((int) $order->id_cart);
        if ($filas === []) {
            return;
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $lineas = [];
        foreach ($db->executeS(
            "SELECT id_order_detail, product_id, product_attribute_id, product_quantity, product_name, unit_price_tax_incl, original_wholesale_price
             FROM {$p}order_detail WHERE id_order = " . (int) $order->id
        ) ?: [] as $l) {
            $lineas[(int) $l['product_id'] . '-' . (int) $l['product_attribute_id']] = $l;
        }

        $valores = [];
        $regaladas = [];
        foreach ($filas as $f) {
            $k = (int) $f['r_id_product'] . '-' . (int) $f['r_ipa'];
            $linea = $lineas[$k] ?? null;
            if ($linea === null) {
                continue; // El regalo no llegó al pedido (se agotó al validar): no se apunta.
            }
            $regaladas[$k] = ($regaladas[$k] ?? 0) + (int) $f['cantidad'];
            $valores[] = '(' . (int) $order->id . ', ' . (int) $f['id_kpy_coupon'] . ', ' . (int) $f['disparador'] . ', '
                . (int) $f['r_id_product'] . ', ' . (int) $f['r_ipa'] . ', ' . (int) $f['cantidad'] . ', '
                . (float) $linea['unit_price_tax_incl'] . ', ' . (float) $linea['original_wholesale_price'] . ', NOW())';
        }
        foreach (array_chunk($valores, 200) as $trozo) {
            $db->execute(
                "INSERT INTO {$p}kpy_coupon_regalo_pedido (id_order, id_kpy_coupon, disparador, r_id_product, r_ipa, cantidad, precio_unidad, coste_unidad, date_add)
                 VALUES " . implode(',', $trozo) . ' ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)'
            );
        }
        // «(regalo)» en la línea: quien prepara el pedido lo ve sin mirar nada más.
        foreach ($regaladas as $k => $n) {
            $linea = $lineas[$k];
            $nombre = (string) $linea['product_name'];
            if (preg_match('/\((\d+ de )?regalo\)$/u', $nombre)) {
                continue;
            }
            $nombre .= (int) $linea['product_quantity'] <= $n ? ' (regalo)' : ' (' . $n . ' de regalo)';
            $db->update('order_detail', ['product_name' => pSQL($nombre)], 'id_order_detail = ' . (int) $linea['id_order_detail']);
        }
        $db->delete('kpy_coupon_regalo_cesta', 'id_cart = ' . (int) $order->id_cart);
        self::olvidarElecciones((int) $order->id_cart);
    }

    /** Lo regalado en un pedido: «producto-formato» => unidades. */
    public static function delPedido(int $idOrder): array
    {
        static $cache = [];
        if (!isset($cache[$idOrder])) {
            $cache[$idOrder] = [];
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT r_id_product, r_ipa, SUM(cantidad) AS n FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo_pedido
                 WHERE id_order = ' . $idOrder . ' GROUP BY r_id_product, r_ipa'
            ) ?: [] as $f) {
                $cache[$idOrder][(int) $f['r_id_product'] . '-' . (int) $f['r_ipa']] = (int) $f['n'];
            }
        }

        return $cache[$idOrder];
    }

    /**
     * Las filas de regalo de las cestas que ya no existen o ya son un pedido: fuera. Las de cestas
     * vivas se quedan (si el cliente vuelve, hay que saber qué parte de cada línea era regalo).
     *
     * @return int cuántas filas se han borrado
     */
    public static function limpiarCestas(): int
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $db->execute(
            "DELETE rc FROM {$p}kpy_coupon_regalo_cesta rc
             LEFT JOIN {$p}cart c ON c.id_cart = rc.id_cart
             WHERE c.id_cart IS NULL OR EXISTS (SELECT 1 FROM {$p}orders o WHERE o.id_cart = rc.id_cart)"
        );
        $borradas = (int) $db->Affected_Rows();
        // Y las elecciones de las cestas que ya no existen, ya son un pedido o nadie toca desde hace
        // un mes. Si el cliente vuelve a una de esas, su saco está a su precio de hoy y el regalo se
        // va: lo vuelve a elegir si quiere.
        $muerta = "(c.id_cart IS NULL OR c.date_upd < DATE_SUB(NOW(), INTERVAL " . (int) self::DIAS_ELECCION . " DAY)
                    OR EXISTS (SELECT 1 FROM {$p}orders o WHERE o.id_cart = e.id_cart))";
        $db->execute(
            "DELETE e FROM {$p}kpy_coupon_regalo_eleccion e
             LEFT JOIN {$p}cart c ON c.id_cart = e.id_cart
             WHERE {$muerta}"
        );
        // Con sus precios de cesta (el suyo, por id: nunca otro) y sus reservas del cupo.
        KpcPrecioCesta::limpiar(self::DIAS_ELECCION);
        KpcCupo::limpiar();

        return $borradas;
    }

    // ------------------------------------------------------------------------------------------
    // Nombres y textos
    // ------------------------------------------------------------------------------------------

    /**
     * El nombre de un artículo para la pantalla, la cesta y la ficha: «Boske Adult Grain Free Salmón
     * · Saco de 3 Kg», con marca y precio aparte.
     *
     * @param array<int, array{id: int, ipa: int}> $articulos
     *
     * @return array<string, array{nombre: string, formato: string, marca: string, ean: string, precio: float, stock: int, vendible: bool, apagado: bool, imagen: ?int}>
     *         por «id-ipa»
     */
    public static function datos(array $articulos, int $idTienda, ?int $idLang = null): array
    {
        $idLang = $idLang ?: (int) Context::getContext()->language->id;
        $productos = [];
        $ipas = [];
        foreach ($articulos as $a) {
            if ($a === null) {
                continue;
            }
            $productos[(int) $a['id']] = true;
            if ((int) $a['ipa'] > 0) {
                $ipas[(int) $a['ipa']] = true;
            }
        }
        if ($productos === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $nombres = [];
        foreach ($db->executeS(
            "SELECT p.id_product, pl.name, pl.link_rewrite, p.ean13, p.weight AS peso, IFNULL(m.name, '') AS marca, ps.active, ps.available_for_order,
                    (SELECT i.id_image FROM {$p}image i WHERE i.id_product = p.id_product ORDER BY i.cover DESC, i.position LIMIT 1) AS imagen
             FROM {$p}product p
             LEFT JOIN {$p}product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = {$idLang} AND pl.id_shop = " . (int) $idTienda . "
             LEFT JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = " . (int) $idTienda . "
             LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
             WHERE p.id_product IN (" . implode(',', array_keys($productos)) . ')'
        ) ?: [] as $f) {
            $nombres[(int) $f['id_product']] = $f;
        }
        $formatos = [];
        if ($ipas !== []) {
            foreach ($db->executeS(
                "SELECT pa.id_product_attribute AS ipa, pa.id_product, pa.ean13, pa.weight AS peso, " . KpcAlcance::sqlApagado() . " AS apagado,
                        GROUP_CONCAT(al.name ORDER BY al.name SEPARATOR ' ') AS nombre,
                        (SELECT pai.id_image FROM {$p}product_attribute_image pai WHERE pai.id_product_attribute = pa.id_product_attribute LIMIT 1) AS imagen
                 FROM {$p}product_attribute pa
                 LEFT JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                 LEFT JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = {$idLang}
                 WHERE pa.id_product_attribute IN (" . implode(',', array_keys($ipas)) . ')
                 GROUP BY pa.id_product_attribute'
            ) ?: [] as $f) {
                $formatos[(int) $f['ipa']] = $f;
            }
        }
        $salida = [];
        foreach ($articulos as $a) {
            if ($a === null) {
                continue;
            }
            $id = (int) $a['id'];
            $ipa = (int) $a['ipa'];
            $k = $id . '-' . $ipa;
            if (isset($salida[$k])) {
                continue;
            }
            $prod = $nombres[$id] ?? null;
            $form = $ipa > 0 ? ($formatos[$ipa] ?? null) : null;
            try {
                $precio = $prod ? (float) Product::getPriceStatic($id, true, $ipa ?: null, 2, null, false, true) : 0.0;
                $pvpr = $prod ? (float) Product::getPriceStatic($id, true, $ipa ?: null, 2, null, false, false) : 0.0;
            } catch (Throwable $e) {
                $precio = 0.0;
                $pvpr = 0.0;
            }
            $salida[$k] = [
                'id' => $id,
                'ipa' => $ipa,
                'nombre' => (string) ($prod['name'] ?? '#' . $id),
                'link_rewrite' => (string) ($prod['link_rewrite'] ?? ''),
                'formato' => (string) ($form['nombre'] ?? ''),
                'marca' => (string) ($prod['marca'] ?? ''),
                'ean' => (string) ($form['ean13'] ?? $prod['ean13'] ?? ''),
                'precio' => $precio,
                'pvpr' => max($pvpr, $precio),
                'stock' => $prod ? (int) StockAvailable::getQuantityAvailableByProduct($id, $ipa ?: null, $idTienda) : 0,
                'vendible' => $prod && (int) $prod['active'] === 1 && (int) $prod['available_for_order'] === 1 && ($ipa === 0 || $form !== null) && (int) ($form['id_product'] ?? $id) === $id,
                'apagado' => $form !== null && (int) $form['apagado'] === 1,
                'imagen' => (int) ($form['imagen'] ?? $prod['imagen'] ?? 0) ?: null,
                // El peso lo guarda PrestaShop en el formato (como lo lee la ficha); sin formato, el del producto.
                'peso' => (float) ($form !== null ? ($form['peso'] ?? 0) : ($prod['peso'] ?? 0)),
            ];
        }

        return $salida;
    }

    /** «Boske Adult Grain Free Salmón Pienso Natural para Perro» + «Saco de 3 Kg» → «Boske Adult Grain Free Salmón… · Saco de 3 Kg». */
    public static function nombreCorto(string $nombre, string $formato = ''): string
    {
        $nombre = trim($nombre);
        $formato = trim((string) preg_replace('/^[^:]+:\s*/u', '', $formato));
        if (mb_strlen($nombre) > 48) {
            $nombre = rtrim(mb_substr($nombre, 0, 46)) . '…';
        }

        return $formato !== '' ? $nombre . ' · ' . $formato : $nombre;
    }

    /**
     * Lo que dice la etiqueta sobre la foto (y la franja de la tarjeta): «+ 3 kg gratis» si el regalo
     * es más de lo mismo (un saco o una bolsa de su ficha, por su peso), «+ 1 lata gratis» o «+ 6
     * latas gratis» si son latas, sobres o tarrinas de su ficha (se cuentan), y «+ regalo» si es
     * otra cosa.
     */
    public static function etiqueta(string $nombreFormatoRegalo, bool $mismaFicha): string
    {
        if (!$mismaFicha) {
            return '+ regalo';
        }
        $tam = KpcFormatos::tamano($nombreFormatoRegalo);
        if (!preg_match('/(\d+(?:,\d+)?\s*(?:kg|g|l|ml))$/u', $tam, $m)) {
            return '+ regalo';
        }
        if (preg_match('/^(lata|tarrina|sobre|pouch|bandeja|bote|barrita)\b/u', $tam, $e)) {
            $n = KpcFormatos::unidades($nombreFormatoRegalo);

            return '+ ' . $n . ' ' . ($n === 1 ? $e[1] : self::plural($e[1])) . ' gratis';
        }

        return '+ ' . $m[1] . ' gratis';
    }

    /** `kilos` (más de lo mismo) o `regalo` (otra cosa), a partir de la etiqueta. */
    public static function clase(string $etiqueta, bool $mismaFicha): string
    {
        return $mismaFicha && $etiqueta !== '+ regalo' ? 'kilos' : 'regalo';
    }

    /**
     * Lo que va detrás del «+» en la tarjeta de la ficha («12 kg + ___»): «3 kg gratis» o «1 lata
     * gratis» si es más de lo mismo; si es otra cosa, lo que diga la promoción (`regalo_corto`) o,
     * si no dice nada, lo que sea: «Snacks» (un snack, un premio, una bolsita), «Latas» o «Pack latas»
     * (latas; igual con sobres, tarrinas, pouch), y «Regalo» para lo demás (una carda, un juguete).
     */
    public static function corto(string $formatoRegalo, string $nombreRegalo, bool $mismaFicha, string $dicho = ''): string
    {
        if ($mismaFicha) {
            $etiqueta = self::etiqueta($formatoRegalo, true);
            if ($etiqueta !== '+ regalo') {
                return mb_substr($etiqueta, 2);
            }
        }
        $dicho = trim($dicho);
        if ($dicho !== '') {
            return mb_substr($dicho, 0, 24);
        }
        $texto = $nombreRegalo . ' ' . $formatoRegalo;
        if (preg_match('/\b(snacks?|premios?|golosinas?|chuches?|treats?|galletas?|huesos?|sticks?|barritas?|dental)\b/iu', $texto)) {
            return 'Snacks';
        }
        $tam = KpcFormatos::tamano($formatoRegalo);
        if (preg_match('/^(lata|tarrina|sobre|pouch|bandeja|bote)\b/u', $tam, $e)) {
            $n = KpcFormatos::unidades($formatoRegalo);

            return $n > 1 ? 'Pack ' . self::plural($e[1]) : mb_convert_case($e[1], MB_CASE_TITLE, 'UTF-8');
        }
        if (preg_match('/^bolsa\b.*?(\d+(?:,\d+)?)\s*g$/u', $tam, $g) && (float) str_replace(',', '.', $g[1]) <= 250) {
            return 'Snacks';
        }

        return 'Regalo';
    }

    /** «lata» → «latas», «pouch» → «pouches». */
    private static function plural(string $envase): string
    {
        return $envase === 'pouch' ? 'pouches' : $envase . 's';
    }
}
