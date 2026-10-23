<?php
/**
 * Qué artículo le toca a cada promoción, **calculado una vez y guardado** en `kpy_coupon_item`.
 *
 * Antes se calculaba en cada página (ficha, cesta, etiquetas), preguntando a la tienda por las
 * marcas y categorías de cada cupón. Ahora se calcula al guardar una promoción y se deja escrito:
 * la ficha y la cesta solo leen una tabla.
 *
 * Dos reglas:
 *
 *   1. **Un artículo solo puede estar en una promoción.** Si no, los descuentos se suman en la
 *      cesta (pasó con el Boske de 15 kg: BOSKE10 + 2ª unidad). Manda la que más descuenta de
 *      verdad (`KpcElegibles::manda`) y las demás lo sueltan.
 *   2. **Cada promoción se calcula para su primer día.** Una programada para el lunes no le quita
 *      nada hoy a la que está en marcha, y se calcula con los Special Price que habrá el lunes.
 *
 * Cuando empieza o acaba una promoción (o un Special Price), el reparto cambia. No hace falta
 * acordarse: se guarda cuándo es el próximo cambio (`KPC_PROXIMO`) y la primera visita de después
 * lo rehace, una sola vez y cuando ya se le ha contestado (el cliente no espera).
 */
class KpcReparto
{
    /** Cuándo toca rehacer (marca de tiempo). 0 o vacío = en la próxima visita. */
    public const PROXIMO = 'KPC_PROXIMO';

    /** @var array<int, array<int, array<int, int>>> caché de `vigentes()` por tienda */
    private static array $vigentes = [];

    /**
     * Rehace todo: el reparto, las reglas de carrito de los cupones y las etiquetas.
     *
     * @return array{items: array<int, array<int, int>>, choques: array<int, array<string, int>>, avisos: array<int, string[]>}
     *         items: id de promoción => [clave => id de producto]; choques: id de cupón => [código que
     *         manda => artículos que suelta]; avisos: id de promoción => lo que se queda fuera y por qué
     */
    public static function rehacer(int $idTienda, int $guardada = 0): array
    {
        $ahora = date('Y-m-d H:i:s');
        $todas = self::cupones($idTienda);
        // Las de cesta (cupón, 2ª unidad y regalo), en orden de mando: un regalo no descuenta en el
        // precio, así que manda menos que cualquier cupón o 2ª unidad, y entre regalos el más antiguo.
        $cupones = array_values(array_filter($todas, static fn (array $c): bool => !KpcElegibles::esPrecio($c)));
        $avisos = [];
        $items = [];
        $especial = [];
        // Lo que se cuenta a los demás módulos por el hook (2.5.0): el alcance de cada promoción tal
        // como lo calcula `KpcAlcance` (los que entran y los apartados por tener otro Special Price),
        // para que nadie tenga que llamar a `KpcAlcance` desde fuera (`kpysupply` da su acuerdo a
        // «entran + especial»).
        $alcanceHook = [];

        // 0. Los Special Price, primero: sus precios deciden a qué llegan luego los cupones (un
        //    formato con Special Price no lleva cupón). Entre dos Special Price, manda el que ya estaba.
        foreach ($todas as $c) {
            if (!KpcElegibles::esPrecio($c)) {
                continue;
            }
            $id = (int) $c['id_kpy_coupon'];
            if (self::fecha($c['fecha_desde']) === null || self::fecha($c['fecha_hasta']) === null) {
                $items[$id] = [];
                $avisos[$id][] = 'le faltan las fechas';
                continue;
            }
            // Un fallo en una no para las demás: se apunta en su aviso y en el registro de la tienda.
            try {
                $articulos = KpcAlcance::articulos($c, $idTienda);
                $alcanceHook[$id] = ['entran' => (array) $articulos['entran'], 'especial' => (array) ($articulos['especial'] ?? [])];
                // Los artículos que ya vendieron sus unidades (cupo por artículo), fuera: su precio vuelve.
                $agotados = KpcCupo::agotadasDe($id);
                if ($agotados !== []) {
                    $articulos['entran'] = array_diff_key($articulos['entran'], array_fill_keys($agotados, true));
                    $avisos[$id][] = sprintf('%d con sus unidades ya vendidas', count($agotados));
                }
                $puesto = KpcEspecial::sincronizar($c, $articulos['entran'], $articulos['valores'] ?? [], $idTienda);
            } catch (Throwable $e) {
                $items[$id] = [];
                $avisos[$id][] = 'ERROR: ' . $e->getMessage();
                PrestaShopLogger::addLog('kpycoupons, Special Price ' . $c['codigo'] . ': ' . $e->getMessage(), 3, null, 'KpcReparto', $id, true);
                continue;
            }
            $fuera = [];
            foreach ($puesto['fuera'] as $motivo => $claves) {
                $fuera += array_fill_keys($claves, true);
                $avisos[$id][] = sprintf('%d %s', count($claves), KpcEspecial::MOTIVOS[$motivo] ?? $motivo);
            }
            $items[$id] = array_diff_key($articulos['entran'], $fuera);
            if ($articulos['especial']) {
                $avisos[$id][] = sprintf('%d ya con otro Special Price en esas fechas', count($articulos['especial']));
            }
        }
        // Los Special Price que ya no tocan, fuera (el precio vuelve al de siempre): los de una
        // promoción apagada, los de una que ha dejado de ser Special Price, los de una borrada por
        // otro camino, **los de una que ha terminado** y los de una con el cupo agotado. Caducados ya
        // no rebajan nada, pero `kpyspecialprices` sin su parche seguiría enseñando en la ficha
        // «Precio habitual» y «Precio especial» con el precio de siempre (pasa en producción con la
        // carga de agosto).
        $p = _DB_PREFIX_;
        foreach (Db::getInstance()->executeS(
            "SELECT DISTINCT cp.id_kpy_coupon FROM {$p}kpy_coupon_precio cp
             LEFT JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = cp.id_kpy_coupon
             WHERE c.id_kpy_coupon IS NULL
                OR (c.id_shop = " . (int) $idTienda . " AND (c.activo = 0 OR c.tipo <> 'special_price' OR c.fecha_hasta < NOW()
                    OR NOT " . KpcCupo::sqlVivo('c') . '))'
        ) ?: [] as $f) {
            KpcEspecial::quitarTodo((int) $f['id_kpy_coupon'], $idTienda);
        }

        // 1. A qué llega cada cupón, cada 2ª unidad y cada regalo, en su primer día. Un regalo no
        //    respeta el Special Price: el artículo rebajado es justo el disparador (la tarjeta «12 kg
        //    + 3 kg gratis» va al precio de siempre; la de «12 kg», con su rebaja).
        $alcance = [];
        $inicio = [];
        $datos = [];
        foreach ($cupones as $c) {
            $id = (int) $c['id_kpy_coupon'];
            $esRegalo = KpcElegibles::esRegalo($c);
            $inicio[$id] = self::fecha($c['fecha_desde']) !== null && $c['fecha_desde'] > $ahora ? (string) $c['fecha_desde'] : $ahora;
            try {
                $articulos = KpcAlcance::articulos($esRegalo ? ['excluir_especial' => 0] + $c : $c, $idTienda, $inicio[$id]);
            } catch (Throwable $e) {
                // Un fallo en una no para las demás: se apunta en su aviso y en el registro de la tienda.
                $alcance[$id] = [];
                $avisos[$id][] = 'ERROR: ' . $e->getMessage();
                PrestaShopLogger::addLog('kpycoupons, alcance de ' . $c['codigo'] . ': ' . $e->getMessage(), 3, null, 'KpcReparto', $id, true);
                continue;
            }
            $alcanceHook[$id] = ['entran' => (array) $articulos['entran'], 'especial' => (array) ($articulos['especial'] ?? [])];
            $alcance[$id] = $articulos['entran'];
            // Los artículos que ya vendieron sus unidades (cupo por artículo), fuera.
            $agotados = KpcCupo::agotadasDe($id);
            if ($agotados !== []) {
                $alcance[$id] = array_diff_key($alcance[$id], array_fill_keys($agotados, true));
                $avisos[$id][] = sprintf('%d con sus unidades ya vendidas', count($agotados));
            }
            $especial[$id] = $esRegalo ? 0 : count($articulos['especial']);
            $datos[$id] = ['niveles' => $articulos['niveles'] ?? [], 'formatos' => $articulos['formatos'] ?? []];
        }

        // 2. Cada una suelta lo que ya tiene otra que manda más y que estará en marcha ese día. Un
        //    artículo lleva una sola promoción: o el cupón, o la 2ª unidad, o el regalo (manda la que
        //    más descuenta; el regalo, al no descuentar en el precio, cede ante las otras dos y entre
        //    regalos manda el más antiguo). El Special Price no entra aquí: es del catálogo y en un
        //    regalo convive con él (son las dos tarjetas de la ficha).
        $choques = [];
        $parejas = [];
        foreach ($cupones as $i => $c) {
            $id = (int) $c['id_kpy_coupon'];
            $suyos = $alcance[$id] ?? [];
            for ($j = 0; $j < $i && $suyos !== []; ++$j) {
                $otra = $cupones[$j];
                if (!self::enMarcha($otra, $inicio[$id])) {
                    continue;
                }
                $comunes = array_intersect_key($suyos, $alcance[(int) $otra['id_kpy_coupon']] ?? []);
                if ($comunes !== []) {
                    $suyos = array_diff_key($suyos, $comunes);
                    $choques[$id][(string) $otra['codigo']] = count($comunes);
                    $avisos[$id][] = sprintf('%d en %s', count($comunes), (string) $otra['codigo']);
                }
            }
            if (!KpcElegibles::esRegalo($c)) {
                $items[$id] = $suyos;
                if (!empty($especial[$id])) {
                    $avisos[$id][] = sprintf('%d con Special Price', $especial[$id]);
                }
                continue;
            }
            // Un regalo: qué regala cada artículo (sus parejas), que se deja escrito aparte. Un
            // disparador sin regalo no entra: no hay promoción que darle.
            $parejas[$id] = KpcRegalo::parejas($c, $suyos, $datos[$id]['niveles'] ?? [], $datos[$id]['formatos'] ?? []);
            $sinRegalo = array_filter($parejas[$id], static fn (array $p): bool => $p['r'] === null);
            $items[$id] = array_diff_key($suyos, $sinRegalo);
            if ($sinRegalo !== []) {
                $avisos[$id][] = sprintf('%d sin regalo (sin formato más pequeño en su ficha)', count($sinRegalo));
            }
            $mismos = array_filter($parejas[$id], static fn (array $p): bool => $p['aviso'] === 'mismo_articulo');
            if ($mismos !== []) {
                $avisos[$id][] = sprintf('%d regalan el mismo artículo que se compra', count($mismos));
            }
        }

        self::guardar($idTienda, $todas, $items, $avisos);
        foreach ($parejas as $id => $suyas) {
            KpcRegalo::guardar($id, $suyas);
        }
        Db::getInstance()->execute(
            'DELETE r FROM ' . _DB_PREFIX_ . 'kpy_coupon_regalo r LEFT JOIN ' . _DB_PREFIX_ . 'kpy_coupon c ON c.id_kpy_coupon = r.id_kpy_coupon WHERE c.id_kpy_coupon IS NULL'
        );

        // 3. Las reglas de carrito: la de cada cupón, justo a sus artículos; las demás, apagadas.
        $vivos = [];
        foreach ($cupones as $c) {
            $id = (int) $c['id_kpy_coupon'];
            $vivos[] = $id;
            if (($c['tipo'] ?? 'cupon') === 'cupon') {
                $cupon = new KpcCupon($id);
                if (!Validate::isLoadedObject($cupon)) {
                    continue;
                }
                try {
                    $cupon->sincronizarRegla(array_keys($items[$id]), $items[$id]);
                } catch (Throwable $e) {
                    // Que se vea en la lista de la pantalla, y que las demás sigan.
                    $avisos[$id][] = 'ERROR en la regla de carrito: ' . $e->getMessage();
                    Db::getInstance()->update('kpy_coupon', ['aviso' => pSQL(mb_substr(implode(', ', $avisos[$id]), 0, 255))], 'id_kpy_coupon = ' . $id);
                    PrestaShopLogger::addLog('kpycoupons, regla de ' . $c['codigo'] . ': ' . $e->getMessage(), 3, null, 'KpcReparto', $id, true);
                }
            }
        }
        foreach (Db::getInstance()->executeS(
            'SELECT id_kpy_coupon FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_cart_rule > 0 AND id_shop = ' . (int) $idTienda
            . ($vivos ? ' AND id_kpy_coupon NOT IN (' . implode(',', $vivos) . ')' : '')
        ) ?: [] as $f) {
            (new KpcCupon((int) $f['id_kpy_coupon']))->apagarRegla();
        }

        self::$vigentes = [];
        KpcCupon::olvidarCache();
        // El id de Google de cada promoción de carrito, escrito en su fila (lo lee el feed de Julián).
        KpcMerchant::fijarIds($idTienda);

        // 4. Cuándo toca la próxima vez. Las etiquetas de los listados ya no se escriben en ninguna
        //    tabla (2.5.0): `KpcFlags` las calcula en cada petición de lo que se acaba de guardar; solo
        //    tiene que olvidar lo que hubiera leído en esta.
        KpcFlags::olvidarCache();
        Configuration::updateGlobalValue(self::PROXIMO, self::proximo($idTienda));

        // 5. Quien quiera saberlo (la landing de promociones, los acuerdos con proveedores): el
        //    reparto ha cambiado. Un fallo suyo no es un fallo de las promociones: se apunta y se
        //    sigue. Contrato del hook (README, «Hooks propios»):
        //      id_shop      la tienda;
        //      items        por promoción, clave del artículo => id_product (lo que le toca de verdad);
        //      avisos       por promoción, lo que se quedó fuera y por qué;
        //      alcance      por promoción, `entran` y `especial` (clave => id_product) tal como los
        //                   calcula KpcAlcance, antes de quitar agotados y choques (2.5.0);
        //      promociones  por id, la fila pública de la promoción (`KpcContrato::promocion`, claves
        //                   fijas; no la fila cruda de la tabla) (3.0.0);
        //      version      la del contrato (`KpcContrato::VERSION`);
        //      guardada     el id de la promoción que se acaba de guardar en la pantalla, 0 en los
        //                   repartos por fecha o por consola (2.5.0).
        $promociones = [];
        foreach ($todas as $c) {
            $promociones[(int) $c['id_kpy_coupon']] = KpcContrato::promocion($c);
        }
        try {
            Hook::exec('actionKpyCouponsReparto', [
                'version' => KpcContrato::VERSION,
                'id_shop' => $idTienda,
                'items' => $items,
                'avisos' => $avisos,
                'alcance' => $alcanceHook,
                'promociones' => $promociones,
                'guardada' => $guardada,
            ]);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al avisar del reparto (actionKpyCouponsReparto): ' . $e->getMessage(), 3, null, 'KpcReparto', 0, true);
        }

        return ['items' => $items, 'choques' => $choques, 'avisos' => $avisos];
    }

    /**
     * Si ya ha pasado el próximo cambio, lo rehace **después de contestar** a la visita, y solo una
     * a la vez (si otra visita ya está en ello, esta no hace nada). Es una comprobación en memoria:
     * no cuesta nada en las visitas normales.
     */
    public static function siToca(int $idTienda): void
    {
        $cuando = (int) Configuration::getGlobalValue(self::PROXIMO);
        if ($cuando > time()) {
            return;
        }
        static $apuntado = false;
        if ($apuntado) {
            return;
        }
        $apuntado = true;
        register_shutdown_function(static function () use ($idTienda): void {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            self::conCerrojo($idTienda);
        });
    }

    /** Rehace con cerrojo en la base de datos, para que dos visitas a la vez no lo hagan dos veces. */
    public static function conCerrojo(int $idTienda): void
    {
        $db = Db::getInstance();
        $nombre = pSQL(_DB_NAME_ . '_kpycoupons');
        if (!(int) $db->getValue("SELECT GET_LOCK('{$nombre}', 0)")) {
            return;
        }
        try {
            // Otra visita puede haberlo hecho mientras: se mira en la tabla, no en la caché.
            $cuando = (int) $db->getValue(
                'SELECT value FROM ' . _DB_PREFIX_ . "configuration WHERE name = '" . pSQL(self::PROXIMO) . "' AND id_shop IS NULL AND id_shop_group IS NULL"
            );
            if ($cuando <= time()) {
                self::rehacer($idTienda);
            }
        } catch (Throwable $e) {
            // Que un fallo no se repita en cada visita: se reintenta dentro de una hora.
            Configuration::updateGlobalValue(self::PROXIMO, time() + 3600);
            PrestaShopLogger::addLog('kpycoupons, rehacer el reparto: ' . $e->getMessage(), 3, null, 'KpcReparto', 0, true);
        } finally {
            $db->getValue("SELECT RELEASE_LOCK('{$nombre}')");
        }
    }

    /**
     * Los artículos de las promociones **de cesta** (cupón y 2ª unidad) que están en marcha ahora,
     * por promoción. Los Special Price no salen aquí: están en el precio, y la tienda los lee de
     * `kpy_special_price` (ficha, etiquetas, Google). Lo mismo en `deClaves()` y `deProducto()`.
     *
     * @return array<int, array<int, int>> id de cupón => [clave => id de producto]
     */
    public static function vigentes(int $idTienda): array
    {
        if (!isset(self::$vigentes[$idTienda])) {
            self::$vigentes[$idTienda] = self::leer($idTienda, '');
        }

        return self::$vigentes[$idTienda];
    }

    /**
     * Los de todas las promociones guardadas, también las programadas (para Google, que las quiere
     * con antelación para revisarlas) y los Special Price.
     *
     * @return array<int, array<int, int>> id de cupón => [clave => id de producto]
     */
    public static function guardados(int $idTienda): array
    {
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT i.id_kpy_coupon, i.clave, i.id_product FROM {$p}kpy_coupon_item i
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = i.id_kpy_coupon AND c.id_shop = " . (int) $idTienda
        ) ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']][(int) $f['clave']] = (int) $f['id_product'];
        }

        return $salida;
    }

    /**
     * Solo los de unos artículos (los de una cesta): así no se lee la promoción entera de una marca
     * para saber si una línea está dentro.
     *
     * @param int[] $claves
     *
     * @return array<int, int[]> id de cupón => claves suyas de las pedidas
     */
    public static function deClaves(int $idTienda, array $claves): array
    {
        $claves = array_values(array_unique(array_map('intval', $claves)));
        if ($claves === []) {
            return [];
        }
        $salida = [];
        foreach (self::leer($idTienda, 'i.clave IN (' . implode(',', $claves) . ')') as $id => $suyos) {
            $salida[$id] = array_keys($suyos);
        }

        return $salida;
    }

    /**
     * Las de un producto: lo que pregunta la ficha.
     *
     * @return array<int, int[]> id de cupón => ids de combinación suyos (0 si el producto no tiene)
     */
    public static function deProducto(int $idTienda, int $idProducto): array
    {
        $salida = [];
        foreach (self::leer($idTienda, 'i.id_product = ' . (int) $idProducto) as $id => $suyos) {
            $salida[$id] = array_map(static fn (int $clave): int => max(0, $clave), array_keys($suyos));
        }

        return $salida;
    }

    /** Las claves de las líneas de una cesta. @return int[] */
    public static function clavesDe(array $productos): array
    {
        return array_map(
            static fn ($linea) => KpcAlcance::clave((int) ($linea['id_product'] ?? 0), (int) ($linea['id_product_attribute'] ?? 0)),
            $productos
        );
    }

    /**
     * Las promociones que cuentan para el reparto: encendidas, sin terminar (las de ahora y las
     * programadas) y sin el cupo agotado, con sus ámbitos, **en orden de mando**.
     */
    public static function cupones(int $idTienda): array
    {
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon c
             WHERE c.activo = 1 AND c.id_shop = ' . (int) $idTienda . '
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c')
        ) ?: [];
        $ambitos = KpcCupon::ambitosDe(array_map('intval', array_column($filas, 'id_kpy_coupon')));
        foreach ($filas as $i => $f) {
            $filas[$i]['ambitos'] = $ambitos[(int) $f['id_kpy_coupon']] ?? [];
        }
        usort($filas, [KpcElegibles::class, 'manda']);

        return $filas;
    }

    /** ¿Está en marcha esa promoción en ese momento? */
    public static function enMarcha(array $cupon, string $momento): bool
    {
        $desde = self::fecha($cupon['fecha_desde'] ?? null);
        $hasta = self::fecha($cupon['fecha_hasta'] ?? null);

        return ($desde === null || $desde <= $momento) && ($hasta === null || $hasta >= $momento);
    }

    /**
     * El próximo momento en que cambia algo: empieza o acaba una promoción o un Special Price.
     * Si no hay ninguno a la vista, dentro de un día (por si alguien toca algo sin avisar).
     */
    public static function proximo(int $idTienda): int
    {
        $p = _DB_PREFIX_;
        $partes = [
            "SELECT fecha_desde AS t FROM {$p}kpy_coupon WHERE activo = 1 AND id_shop = " . (int) $idTienda . ' AND fecha_desde > NOW()',
            "SELECT DATE_ADD(fecha_hasta, INTERVAL 1 SECOND) FROM {$p}kpy_coupon WHERE activo = 1 AND id_shop = " . (int) $idTienda . ' AND fecha_hasta > NOW()',
        ];
        if (KpcCupon::hayTablaEspeciales()) {
            $partes[] = "SELECT date_from FROM {$p}kpy_special_price WHERE date_from > NOW()";
            $partes[] = "SELECT DATE_ADD(expire, INTERVAL 1 SECOND) FROM {$p}kpy_special_price WHERE expire > NOW()";
        }
        $siguiente = (string) Db::getInstance()->getValue('SELECT MIN(t) FROM (' . implode(' UNION ALL ', $partes) . ') x');
        $t = $siguiente !== '' ? strtotime($siguiente) : false;

        return min($t ?: PHP_INT_MAX, time() + 86400);
    }

    /** @return array<int, array<int, int>> id de cupón => [clave => id de producto] */
    private static function leer(int $idTienda, string $donde): array
    {
        $p = _DB_PREFIX_;
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT i.id_kpy_coupon, i.clave, i.id_product
             FROM {$p}kpy_coupon_item i
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = i.id_kpy_coupon
             WHERE c.activo = 1 AND c.tipo <> 'special_price' AND c.id_shop = " . (int) $idTienda . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c')
            . ($donde !== '' ? ' AND ' . $donde : '')
        ) ?: [];
        $salida = [];
        foreach ($filas as $f) {
            $salida[(int) $f['id_kpy_coupon']][(int) $f['clave']] = (int) $f['id_product'];
        }

        return $salida;
    }

    /** Deja escrito el reparto y, en cada promoción, a cuántos artículos llega y qué ha soltado. */
    /**
     * @param array<int, string[]> $avisos id de cupón => lo que se ha quedado fuera y por qué
     */
    private static function guardar(int $idTienda, array $cupones, array $items, array $avisos): void
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $db->execute('START TRANSACTION');
        try {
            $db->execute(
                "DELETE i FROM {$p}kpy_coupon_item i
                 LEFT JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = i.id_kpy_coupon
                 WHERE c.id_kpy_coupon IS NULL OR c.id_shop = " . (int) $idTienda
            );
            $db->execute("UPDATE {$p}kpy_coupon SET formatos = 0, aviso = '' WHERE id_shop = " . (int) $idTienda);
            foreach ($cupones as $c) {
                $id = (int) $c['id_kpy_coupon'];
                foreach (array_chunk($items[$id] ?? [], 500, true) as $trozo) {
                    $valores = [];
                    foreach ($trozo as $clave => $idProducto) {
                        $valores[] = '(' . $id . ', ' . (int) $clave . ', ' . (int) $idProducto . ')';
                    }
                    $db->execute("INSERT INTO {$p}kpy_coupon_item (id_kpy_coupon, clave, id_product) VALUES " . implode(',', $valores));
                }
                $suyos = $avisos[$id] ?? [];
                $db->update('kpy_coupon', [
                    'formatos' => count($items[$id] ?? []),
                    'aviso' => pSQL(mb_substr($suyos ? 'Fuera: ' . implode(', ', $suyos) : '', 0, 255)),
                ], 'id_kpy_coupon = ' . $id);
            }
            $db->execute('COMMIT');
        } catch (Throwable $e) {
            $db->execute('ROLLBACK');
            throw $e;
        }
    }

    private static function fecha($valor): ?string
    {
        $valor = (string) $valor;

        return $valor === '' || strpos($valor, '0000-00-00') === 0 ? null : $valor;
    }
}
