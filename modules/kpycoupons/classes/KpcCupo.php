<?php
/**
 * El **cupo** de una promoción: cuántas unidades se pueden vender con ella (Rafa, 19/10/2026:
 * «bajaremos el precio de X productos… porque tengamos X regalos, y no podamos vender más de X
 * unidades»). Vale para los cuatro tipos, y cada uno cuenta lo suyo:
 *
 *   Special Price (y Liquidación, Outlet, Bajada de precio)  unidades vendidas al precio de la promoción
 *   Cupón con código                                          unidades a las que el cupón descuenta
 *   2ª unidad                                                 unidades que salen rebajadas (la 2ª de cada par)
 *   Regalo con la compra                                      regalos dados (el «tope» de antes de la 2.3.0)
 *
 * Dos maneras de ponerlo, que se pueden juntar:
 *
 *   - **un cupo para toda la promoción** (`kpy_coupon.cupo`; 0 = sin límite);
 *   - **unidades por artículo** (2.4.0, `kpy_coupon.cupo_lista`): la lista pegada trae, al lado de
 *     cada EAN, cuántas unidades se venden con la promoción (`kpy_coupon_scope.unidades`). Es lo
 *     de una liquidación: 12 sacos de este lote, 5 de aquel. Cada artículo se agota por su cuenta
 *     (`kpy_coupon_scope.agotado`) y se va de la tienda en el siguiente reparto; la promoción se da
 *     por agotada cuando no le queda ningún artículo.
 *
 * Tres números, siempre de la base de datos (nunca un contador suelto que pueda desviarse):
 *
 *   vendidas    lo que ya está en pedidos (`kpy_coupon_cupo_pedido`; en un regalo,
 *               `kpy_coupon_regalo_pedido`). Un pedido cancelado o con error de pago no cuenta.
 *   reservadas  lo que hay en otras cestas vivas (`kpy_coupon_cupo_cesta`, tocadas hace menos de
 *               `MINUTOS_RESERVA`): mientras un cliente la tiene en la cesta, esa unidad es suya.
 *               Si abandona la cesta, se libera sola; si vuelve, la cesta se repasa y vuelve a
 *               reservar lo que quede.
 *   quedan      cupo − vendidas − reservadas (de otras cestas): lo que puede llevarse esta cesta.
 *               Con unidades por artículo, lo mismo artículo a artículo, y manda el menor de los dos.
 *
 * Lo que no cabe en el cupo no se vende con la promoción: una línea que pide más de lo que queda
 * se lleva lo que queda y el resto va a precio habitual (lo reparte `KpcCupoCesta`); y mientras una
 * cesta se repasa, las demás esperan al cerrojo (`cerrojo()`): dos cestas a la vez no pueden
 * llevarse la misma unidad.
 *
 * Al validar un pedido (`alPedido`), lo reservado pasa a vendido y, si con él se llega al cupo, la
 * promoción queda **agotada** (`cupo_agotado`): deja de estar en vigor en todas partes (ficha,
 * listados, cesta, landing, Google) como si hubiera terminado, y el reparto se rehace en la
 * siguiente visita. Es definitivo: un pedido cancelado después no la vuelve a abrir sola (sería un
 * baile); se reabre desde la pantalla, o subiendo el cupo (o las unidades del artículo en la lista).
 *
 * La regla pura (`repartir`, `repartirConLimites`, `quedan`) no toca PrestaShop: se prueba sin
 * tienda (`tests/cupo.php`).
 */
class KpcCupo
{
    /** Minutos que una cesta sin tocar mantiene reservadas sus unidades. */
    public const MINUTOS_RESERVA = 60;

    /** Cómo se llama en la tienda un Special Price, según su `etiqueta`. */
    public const ETIQUETAS = ['' => 'Special Price', 'liquidacion' => 'Liquidación', 'outlet' => 'Outlet', 'bajada' => 'Bajada de precio'];

    /** La clase de cada etiqueta (color): en la ficha, la cesta y los listados. */
    public const CLASES = ['' => 'especial', 'liquidacion' => 'liquidacion', 'outlet' => 'outlet', 'bajada' => 'bajada'];

    /**
     * Las etiquetas que son un **cajón** y no una promoción con banner: en la landing van todas
     * juntas a «Outlet y liquidación» (todas las marcas), no a una categoría por marca.
     */
    public const CAJON = ['liquidacion', 'outlet'];

    /** Qué cuenta el cupo en cada tipo, dicho para la pantalla. */
    public const UNIDAD = [
        'special_price' => 'unidades vendidas a este precio',
        'cupon' => 'unidades con el cupón descontado',
        'segunda_unidad' => 'unidades rebajadas (la 2ª de cada par)',
        'regalo' => 'regalos dados',
    ];

    /** @var array<string, array> caché de `estado()` en esta petición (se olvida al reservar) */
    private static array $estados = [];

    /** @var array<int, array<int, array{unidades: int, agotado: string}>> caché de `limites()` por promoción */
    private static array $limites = [];

    /** @var array<string, array> caché de `estadoArticulos()` por promoción y cesta */
    private static array $articulos = [];

    /** @var array<int, bool> caché de `hayCupos()` por tienda */
    private static array $hay = [];

    // ------------------------------------------------------------------------------------------
    // La regla pura
    // ------------------------------------------------------------------------------------------

    /**
     * Lo que puede llevarse una cesta: el cupo menos lo vendido y lo reservado en otras cestas.
     * Sin cupo (0), sin límite.
     */
    public static function quedan(int $cupo, int $vendidas, int $reservadas): ?int
    {
        return $cupo > 0 ? max(0, $cupo - $vendidas - $reservadas) : null;
    }

    /**
     * Reparte lo que queda entre lo que piden las líneas de una cesta, en su orden (la primera que
     * entró, primero): cada una se lleva lo que pide hasta agotar. Sin límite (`null`), todas lo suyo.
     *
     * @param array<int, int> $quiere clave de la línea => unidades que querrían la promoción
     *
     * @return array<int, int> clave => unidades que la llevan (0 si no le toca nada)
     */
    public static function repartir(array $quiere, ?int $quedan): array
    {
        return self::repartirConLimites($quiere, $quedan, []);
    }

    /**
     * Lo mismo, con un límite más por artículo (las unidades de la lista): cada línea se lleva lo
     * que pide hasta donde llegue el cupo de la promoción **y** el de su artículo. Un artículo que
     * no está en `$porClave` solo tiene el de la promoción; `null` en cualquiera de los dos es sin
     * límite.
     *
     * @param array<int, int> $quiere clave => unidades que querrían la promoción, en el orden de la cesta
     * @param ?int $quedan lo que queda del cupo de la promoción (null = sin límite)
     * @param array<int, ?int> $porClave clave => lo que queda de ese artículo (null = sin límite)
     *
     * @return array<int, int> clave => unidades que la llevan
     */
    public static function repartirConLimites(array $quiere, ?int $quedan, array $porClave): array
    {
        $salida = [];
        foreach ($quiere as $clave => $n) {
            $cabe = max(0, (int) $n);
            if ($quedan !== null) {
                $cabe = min($cabe, max(0, $quedan));
            }
            if (array_key_exists($clave, $porClave) && $porClave[$clave] !== null) {
                $cabe = min($cabe, max(0, (int) $porClave[$clave]));
                $porClave[$clave] = (int) $porClave[$clave] - $cabe;
            }
            $salida[$clave] = $cabe;
            if ($quedan !== null) {
                $quedan -= $cabe;
            }
        }

        return $salida;
    }

    /** «Special Price», «Liquidación», «Outlet» o «Bajada de precio». */
    public static function nombreEtiqueta(?string $etiqueta): string
    {
        return self::ETIQUETAS[(string) $etiqueta] ?? self::ETIQUETAS[''];
    }

    /** `especial`, `liquidacion`, `outlet` o `bajada`. */
    public static function claseEtiqueta(?string $etiqueta): string
    {
        return self::CLASES[(string) $etiqueta] ?? 'especial';
    }

    /** ¿Es de las que van al cajón «Outlet y liquidación» (liquidación, outlet)? */
    public static function esCajon(?string $etiqueta): bool
    {
        return in_array((string) $etiqueta, self::CAJON, true);
    }

    /** ¿Tiene cupo esta promoción (fila de `kpy_coupon`)? Por promoción o por artículo. */
    public static function tieneCupo(array $fila): bool
    {
        return (int) ($fila['cupo'] ?? 0) > 0 || (int) ($fila['cupo_lista'] ?? 0) === 1;
    }

    /** En SQL, las promociones que no están agotadas (sin cupo, o con cupo y sin agotar). */
    public static function sqlVivo(string $alias = 'c'): string
    {
        return '((' . $alias . '.cupo = 0 AND ' . $alias . '.cupo_lista = 0) OR ' . $alias . '.cupo_agotado IS NULL)';
    }

    /**
     * La clave de un ámbito de la lista: la combinación, o el producto en negativo (un producto sin
     * formatos). Un producto con formatos puesto como producto no lleva unidades propias (hay que
     * pegar el EAN de cada formato); devuelve 0 para lo que no es de la lista.
     */
    public static function claveDeAmbito(string $tipo, int $id): int
    {
        if ($tipo === 'combinacion') {
            return $id;
        }

        return $tipo === 'producto' ? -$id : 0;
    }

    // ------------------------------------------------------------------------------------------
    // Los números, de la base de datos
    // ------------------------------------------------------------------------------------------

    /**
     * El estado del cupo de unas promociones: su cupo, lo vendido, lo reservado en **otras** cestas
     * y lo que queda para esta. Una promoción sin cupo sale con `quedan` null (sin límite); una con
     * unidades por artículo y sin cupo total, también (lo suyo va artículo a artículo,
     * `estadoArticulos`), salvo agotada (0).
     *
     * @param int[] $ids
     * @param int $sinCesta la cesta que pregunta: sus propias reservas no cuentan en su contra
     *
     * @return array<int, array{cupo: int, vendidas: int, reservadas: int, quedan: ?int, agotado: string, tipo: string, lista: bool}>
     */
    public static function estado(array $ids, int $sinCesta = 0): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $firma = implode(',', $ids) . '|' . $sinCesta;
        if (isset(self::$estados[$firma])) {
            return self::$estados[$firma];
        }
        $promos = [];
        foreach (Db::getInstance()->executeS(
            'SELECT id_kpy_coupon, tipo, cupo, cupo_lista, cupo_agotado FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE id_kpy_coupon IN (' . implode(',', $ids) . ')'
        ) ?: [] as $f) {
            $promos[(int) $f['id_kpy_coupon']] = $f;
        }
        $conNumeros = array_keys(array_filter($promos, static fn (array $f): bool => self::tieneCupo($f)));
        $vendidas = self::vendidas($conNumeros);
        $reservadas = self::reservadas($conNumeros, $sinCesta);
        $salida = [];
        foreach ($promos as $id => $f) {
            $cupo = (int) $f['cupo'];
            $lista = (int) ($f['cupo_lista'] ?? 0) === 1;
            $agotado = KpcCupon::fechaValida($f['cupo_agotado'] ?? '') ?? '';
            $salida[$id] = [
                'cupo' => $cupo,
                'vendidas' => (int) ($vendidas[$id] ?? 0),
                'reservadas' => (int) ($reservadas[$id] ?? 0),
                // Agotada, nada: aunque un pedido cancelado haya dejado hueco, no se reabre sola.
                'quedan' => ($cupo > 0 || $lista) && $agotado !== '' ? 0 : self::quedan($cupo, (int) ($vendidas[$id] ?? 0), (int) ($reservadas[$id] ?? 0)),
                'agotado' => $agotado,
                'tipo' => (string) $f['tipo'],
                'lista' => $lista,
            ];
        }

        return self::$estados[$firma] = $salida;
    }

    /**
     * Lo que queda para una cesta en una promoción (null = sin límite). Con una clave (un artículo)
     * y unidades por artículo, lo que queda de ese artículo, si es menos.
     */
    public static function quedanPara(int $idPromo, int $sinCesta = 0, ?int $clave = null): ?int
    {
        $e = self::estado([$idPromo], $sinCesta)[$idPromo] ?? null;
        if ($e === null) {
            return null;
        }
        $global = $e['quedan'];
        if ($clave === null || empty($e['lista'])) {
            return $global;
        }
        $suyo = self::estadoArticulos($idPromo, $sinCesta)[$clave]['quedan'] ?? null;
        if ($suyo === null) {
            return $global;
        }

        return $global === null ? $suyo : min($global, $suyo);
    }

    /**
     * Lo que queda de cada artículo con unidades propias, de entre unas claves (los demás, sin
     * límite propio: no salen). Para `KpcSegunda`.
     *
     * @param int[] $claves
     *
     * @return array<int, ?int>
     */
    public static function quedanPorClave(int $idPromo, int $sinCesta, array $claves): array
    {
        $e = self::estado([$idPromo], $sinCesta)[$idPromo] ?? null;
        if ($e === null || empty($e['lista'])) {
            return [];
        }
        $articulos = self::estadoArticulos($idPromo, $sinCesta);
        $salida = [];
        foreach ($claves as $clave) {
            if (isset($articulos[(int) $clave])) {
                $salida[(int) $clave] = (int) $articulos[(int) $clave]['quedan'];
            }
        }

        return $salida;
    }

    /**
     * Reparte entre las líneas de una cesta lo que queda de una promoción (su cupo y, si lo hay, el
     * de cada artículo), en el orden de la cesta.
     *
     * @param array<int, int> $quiere clave => unidades que querrían la promoción
     *
     * @return array<int, int> clave => unidades que la llevan
     */
    public static function repartirPara(int $idPromo, int $sinCesta, array $quiere): array
    {
        $e = self::estado([$idPromo], $sinCesta)[$idPromo] ?? null;
        $global = $e['quedan'] ?? null;
        $porClave = $e !== null && !empty($e['lista']) ? self::quedanPorClave($idPromo, $sinCesta, array_keys($quiere)) : [];

        return self::repartirConLimites($quiere, $global, $porClave);
    }

    /**
     * Lo vendido con cada promoción: las unidades apuntadas en los pedidos (los cancelados o con
     * error de pago, no), y en un regalo, los regalos dados.
     *
     * @param int[] $ids
     *
     * @return array<int, int>
     */
    public static function vendidas(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $en = implode(',', $ids);
        $sinEstados = self::sinEstados();
        $salida = [];
        foreach (Db::getInstance()->executeS(
            "SELECT x.id_kpy_coupon, SUM(x.n) AS n FROM (
                SELECT cp.id_kpy_coupon, cp.unidades AS n FROM {$p}kpy_coupon_cupo_pedido cp
                INNER JOIN {$p}orders o ON o.id_order = cp.id_order AND o.current_state NOT IN ({$sinEstados})
                WHERE cp.id_kpy_coupon IN ({$en})
                UNION ALL
                SELECT rp.id_kpy_coupon, rp.cantidad AS n FROM {$p}kpy_coupon_regalo_pedido rp
                INNER JOIN {$p}orders o ON o.id_order = rp.id_order AND o.current_state NOT IN ({$sinEstados})
                WHERE rp.id_kpy_coupon IN ({$en})
             ) x GROUP BY x.id_kpy_coupon"
        ) ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']] = (int) $f['n'];
        }

        return $salida;
    }

    /**
     * Lo reservado en las cestas vivas (tocadas hace menos de `MINUTOS_RESERVA` y que no son ya un
     * pedido), sin contar la que pregunta.
     *
     * @param int[] $ids
     *
     * @return array<int, int>
     */
    public static function reservadas(array $ids, int $sinCesta = 0): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $salida = [];
        foreach (Db::getInstance()->executeS(
            'SELECT r.id_kpy_coupon, SUM(r.unidades) AS n FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_cesta r
             WHERE r.id_kpy_coupon IN (' . implode(',', $ids) . ') AND ' . self::sqlReservaViva('r', $sinCesta) . '
             GROUP BY r.id_kpy_coupon'
        ) ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']] = (int) $f['n'];
        }

        return $salida;
    }

    /** Los estados de pedido que no cuentan (cancelado, error de pago), para un `NOT IN`. */
    private static function sinEstados(): string
    {
        return implode(',', array_filter([(int) Configuration::get('PS_OS_CANCELED'), (int) Configuration::get('PS_OS_ERROR')])) ?: '0';
    }

    /** En SQL, una reserva que cuenta: de otra cesta, reciente y que no es ya un pedido. */
    private static function sqlReservaViva(string $alias, int $sinCesta): string
    {
        return $alias . '.id_cart <> ' . (int) $sinCesta
            . ' AND ' . $alias . '.date_upd >= DATE_SUB(NOW(), INTERVAL ' . (int) self::MINUTOS_RESERVA . ' MINUTE)'
            . ' AND NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'orders o WHERE o.id_cart = ' . $alias . '.id_cart)';
    }

    // ------------------------------------------------------------------------------------------
    // Las unidades por artículo (la lista)
    // ------------------------------------------------------------------------------------------

    /**
     * Los artículos de una promoción con unidades propias (de la lista): clave => unidades y cuándo
     * se agotó (vacío si no).
     *
     * @return array<int, array{unidades: int, agotado: string}>
     */
    public static function limites(int $idPromo): array
    {
        if ($idPromo <= 0) {
            return [];
        }
        if (isset(self::$limites[$idPromo])) {
            return self::$limites[$idPromo];
        }
        $salida = [];
        foreach (Db::getInstance()->executeS(
            'SELECT tipo, id_ref, unidades, agotado FROM ' . _DB_PREFIX_ . 'kpy_coupon_scope
             WHERE id_kpy_coupon = ' . $idPromo . " AND unidades IS NOT NULL AND tipo IN ('producto', 'combinacion')"
        ) ?: [] as $f) {
            $clave = self::claveDeAmbito((string) $f['tipo'], (int) $f['id_ref']);
            if ($clave !== 0) {
                $salida[$clave] = ['unidades' => (int) $f['unidades'], 'agotado' => KpcCupon::fechaValida($f['agotado'] ?? '') ?? ''];
            }
        }

        return self::$limites[$idPromo] = $salida;
    }

    /**
     * El estado de cada artículo con unidades propias: sus unidades, lo vendido, lo reservado en
     * otras cestas y lo que queda para esta (0 si el artículo está agotado).
     *
     * @return array<int, array{unidades: int, vendidas: int, reservadas: int, quedan: int, agotado: string}>
     */
    public static function estadoArticulos(int $idPromo, int $sinCesta = 0): array
    {
        $limites = self::limites($idPromo);
        if ($limites === []) {
            return [];
        }
        $firma = $idPromo . '|' . $sinCesta;
        if (isset(self::$articulos[$firma])) {
            return self::$articulos[$firma];
        }
        $vendidas = self::vendidasPorClave($idPromo);
        $reservadas = self::reservadasPorClave($idPromo, $sinCesta);
        $salida = [];
        foreach ($limites as $clave => $l) {
            $v = (int) ($vendidas[$clave] ?? 0);
            $r = (int) ($reservadas[$clave] ?? 0);
            $salida[$clave] = [
                'unidades' => $l['unidades'],
                'vendidas' => $v,
                'reservadas' => $r,
                'quedan' => $l['agotado'] !== '' ? 0 : max(0, $l['unidades'] - $v - $r),
                'agotado' => $l['agotado'],
            ];
        }

        return self::$articulos[$firma] = $salida;
    }

    /** Lo vendido con una promoción, artículo a artículo (clave => unidades), sin cancelados ni errores. */
    public static function vendidasPorClave(int $idPromo): array
    {
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance()->executeS(
            "SELECT cp.clave, SUM(cp.unidades) AS n FROM {$p}kpy_coupon_cupo_pedido cp
             INNER JOIN {$p}orders o ON o.id_order = cp.id_order AND o.current_state NOT IN (" . self::sinEstados() . ')
             WHERE cp.id_kpy_coupon = ' . (int) $idPromo . '
             GROUP BY cp.clave'
        ) ?: [] as $f) {
            $salida[(int) $f['clave']] = (int) $f['n'];
        }

        return $salida;
    }

    /** Lo reservado en otras cestas vivas con una promoción, artículo a artículo. */
    public static function reservadasPorClave(int $idPromo, int $sinCesta = 0): array
    {
        $salida = [];
        foreach (Db::getInstance()->executeS(
            'SELECT r.clave, SUM(r.unidades) AS n FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_cesta r
             WHERE r.id_kpy_coupon = ' . (int) $idPromo . ' AND ' . self::sqlReservaViva('r', $sinCesta) . '
             GROUP BY r.clave'
        ) ?: [] as $f) {
            $salida[(int) $f['clave']] = (int) $f['n'];
        }

        return $salida;
    }

    /**
     * Los artículos de una promoción que ya han vendido sus unidades: el reparto los deja fuera (su
     * precio vuelve, su etiqueta se va) aunque la promoción siga con los demás.
     *
     * @return int[] claves
     */
    public static function agotadasDe(int $idPromo): array
    {
        $salida = [];
        foreach (self::limites($idPromo) as $clave => $l) {
            if ($l['agotado'] !== '') {
                $salida[] = $clave;
            }
        }

        return $salida;
    }

    /** ¿Hay alguna promoción con cupo encendida en la tienda? (si no, nada de esto cuesta nada) */
    public static function hayCupos(int $idTienda): bool
    {
        if (!isset(self::$hay[$idTienda])) {
            self::$hay[$idTienda] = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                'SELECT 1 FROM ' . _DB_PREFIX_ . 'kpy_coupon WHERE (cupo > 0 OR cupo_lista = 1) AND activo = 1 AND id_shop = ' . (int) $idTienda
            );
        }

        return self::$hay[$idTienda];
    }

    /** Tras guardar o rehacer: que lo siguiente de esta misma petición lea lo nuevo. */
    public static function olvidarCache(): void
    {
        self::$hay = [];
        self::olvidarNumeros();
        self::$limites = [];
    }

    /** Los números (no los límites, que solo cambian al guardar o reabrir). */
    private static function olvidarNumeros(): void
    {
        self::$estados = [];
        self::$articulos = [];
    }

    // ------------------------------------------------------------------------------------------
    // Las reservas de una cesta
    // ------------------------------------------------------------------------------------------

    /** Al empezar a repasar una cesta: sus reservas de antes, fuera (se vuelven a poner las que toquen). */
    public static function vaciar(int $idCart): void
    {
        if ($idCart > 0) {
            Db::getInstance()->delete('kpy_coupon_cupo_cesta', 'id_cart = ' . $idCart);
        }
        self::olvidarNumeros();
    }

    /**
     * Deja reservadas en una cesta las unidades de una promoción, por línea (clave => unidades).
     * Las de 0 no se apuntan.
     *
     * @param array<int, int> $porClave
     */
    public static function reservar(int $idCart, int $idPromo, array $porClave): void
    {
        if ($idCart <= 0 || $idPromo <= 0) {
            return;
        }
        $db = Db::getInstance();
        $db->delete('kpy_coupon_cupo_cesta', 'id_cart = ' . $idCart . ' AND id_kpy_coupon = ' . $idPromo);
        $valores = [];
        foreach ($porClave as $clave => $n) {
            if ((int) $n > 0 && (int) $clave !== 0) {
                $valores[] = '(' . $idCart . ', ' . $idPromo . ', ' . (int) $clave . ', ' . (int) $n . ', NOW())';
            }
        }
        if ($valores !== []) {
            $db->execute(
                'INSERT INTO ' . _DB_PREFIX_ . 'kpy_coupon_cupo_cesta (id_cart, id_kpy_coupon, clave, unidades, date_upd) VALUES ' . implode(',', $valores)
            );
        }
        self::olvidarNumeros();
    }

    /**
     * Lo reservado en una cesta: por promoción, clave => unidades. Es lo que dice en la cesta qué
     * parte de cada línea lleva la promoción.
     *
     * @return array<int, array<int, int>>
     */
    public static function reservasDe(int $idCart): array
    {
        if ($idCart <= 0) {
            return [];
        }
        $salida = [];
        foreach (Db::getInstance()->executeS(
            'SELECT id_kpy_coupon, clave, unidades FROM ' . _DB_PREFIX_ . 'kpy_coupon_cupo_cesta WHERE id_cart = ' . $idCart
        ) ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']][(int) $f['clave']] = (int) $f['unidades'];
        }

        return $salida;
    }

    /**
     * El cerrojo mientras se repasa una cesta: dos cestas a la vez no pueden llevarse la misma
     * unidad. Es un cerrojo de la base de datos (como el del reparto). Si en 3 segundos no se
     * consigue, se sigue sin él: mejor una unidad de más en un caso rarísimo que una cesta colgada.
     */
    public static function cerrojo(): bool
    {
        return (bool) Db::getInstance()->getValue("SELECT GET_LOCK('" . pSQL(_DB_NAME_ . '_kpycoupons_cupo') . "', 3)");
    }

    public static function soltar(): void
    {
        Db::getInstance()->getValue("SELECT RELEASE_LOCK('" . pSQL(_DB_NAME_ . '_kpycoupons_cupo') . "')");
    }

    // ------------------------------------------------------------------------------------------
    // El pedido
    // ------------------------------------------------------------------------------------------

    /**
     * Al validar el pedido: lo reservado en su cesta pasa a vendido (`kpy_coupon_cupo_pedido`; los
     * regalos ya los apunta `KpcRegalo::alPedido` en su tabla), y cada promoción que con este pedido
     * llega a su cupo queda agotada: deja de estar en vigor y el reparto se rehace en la siguiente
     * visita (precios fuera, etiquetas, landing, Google). Con unidades por artículo, cada artículo
     * que vende las suyas queda agotado (y fuera en el siguiente reparto), y la promoción, cuando
     * no le queda ninguno. Devuelve las promociones que se han agotado.
     *
     * @return int[] ids de las promociones agotadas con este pedido
     */
    public static function alPedido(Order $order): array
    {
        $idCart = (int) $order->id_cart;
        $reservas = self::reservasDe($idCart);
        if ($reservas === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $estado = self::estado(array_keys($reservas), $idCart);
        $valores = [];
        foreach ($reservas as $idPromo => $porClave) {
            if (($estado[$idPromo]['tipo'] ?? '') === 'regalo') {
                continue; // Lo regalado lo cuenta `kpy_coupon_regalo_pedido`, con su precio y su coste.
            }
            foreach ($porClave as $clave => $n) {
                $valores[] = '(' . (int) $order->id . ', ' . (int) $idPromo . ', ' . (int) $clave . ', ' . (int) $n . ', NOW())';
            }
        }
        foreach (array_chunk($valores, 200) as $trozo) {
            $db->execute(
                "INSERT INTO {$p}kpy_coupon_cupo_pedido (id_order, id_kpy_coupon, clave, unidades, date_add) VALUES " . implode(',', $trozo)
                . ' ON DUPLICATE KEY UPDATE unidades = unidades + VALUES(unidades)'
            );
        }
        $db->delete('kpy_coupon_cupo_cesta', 'id_cart = ' . $idCart);
        self::olvidarNumeros();

        // ¿Algo se ha agotado con este pedido? Con los números ya en la base de datos.
        $agotadas = [];
        $articulos = 0;
        foreach (self::estado(array_keys($reservas), 0) as $idPromo => $e) {
            if ($e['lista']) {
                $articulos += self::agotarArticulos($idPromo);
                if (self::comprobarAgotada($idPromo)) {
                    $agotadas[] = (int) $idPromo;
                    continue;
                }
            }
            if ($e['cupo'] > 0 && $e['agotado'] === '' && $e['vendidas'] >= $e['cupo']) {
                $db->update('kpy_coupon', ['cupo_agotado' => date('Y-m-d H:i:s')], 'id_kpy_coupon = ' . (int) $idPromo . ' AND cupo_agotado IS NULL');
                $agotadas[] = (int) $idPromo;
            }
        }
        if ($agotadas !== [] || $articulos > 0) {
            self::olvidarCache();
            KpcCupon::olvidarCache();
            // El reparto, en la siguiente visita y después de contestar: los precios de lo agotado
            // fuera, las etiquetas al día, la landing y Google enterados.
            Configuration::updateGlobalValue(KpcReparto::PROXIMO, 0);
            KpcReparto::siToca((int) $order->id_shop ?: (int) Configuration::get('PS_SHOP_DEFAULT'));
            PrestaShopLogger::addLog(
                'kpycoupons: con el pedido ' . (int) $order->id . ' se agotan ' . ($agotadas !== [] ? 'las promociones ' . implode(', ', $agotadas) : 'promociones: ninguna')
                . ($articulos > 0 ? ' y ' . $articulos . ' artículo(s) con unidades propias' : ''),
                1, null, 'Order', (int) $order->id, true
            );
        }

        return $agotadas;
    }

    /**
     * Los artículos con unidades propias que ya han vendido las suyas quedan agotados (fecha en su
     * fila de la lista). Devuelve cuántos se han agotado ahora.
     */
    private static function agotarArticulos(int $idPromo): int
    {
        $n = 0;
        $db = Db::getInstance();
        foreach (self::estadoArticulos($idPromo, 0) as $clave => $a) {
            if ($a['agotado'] !== '' || $a['vendidas'] < $a['unidades']) {
                continue;
            }
            $db->execute(
                'UPDATE ' . _DB_PREFIX_ . 'kpy_coupon_scope SET agotado = NOW() WHERE id_kpy_coupon = ' . $idPromo . ' AND agotado IS NULL AND '
                . ($clave > 0 ? "tipo = 'combinacion' AND id_ref = " . (int) $clave : "tipo = 'producto' AND id_ref = " . (int) -$clave)
            );
            $n += (int) $db->Affected_Rows();
        }
        if ($n > 0) {
            self::$limites = [];
            self::olvidarNumeros();
        }

        return $n;
    }

    /**
     * Con unidades por artículo: si no queda ningún artículo sin agotar, la promoción entera queda
     * agotada (si no lo estaba ya). Devuelve si se ha agotado **ahora**. Lo llama el pedido y la
     * pantalla al guardar (por si la lista cambia).
     */
    public static function comprobarAgotada(int $idPromo): bool
    {
        $limites = self::limites($idPromo);
        if ($limites === []) {
            return false;
        }
        foreach ($limites as $l) {
            if ($l['agotado'] === '') {
                return false;
            }
        }
        Db::getInstance()->update('kpy_coupon', ['cupo_agotado' => date('Y-m-d H:i:s')], 'id_kpy_coupon = ' . $idPromo . ' AND cupo_agotado IS NULL');
        $ahora = (int) Db::getInstance()->Affected_Rows() > 0;
        self::olvidarNumeros();
        KpcCupon::olvidarCache();

        return $ahora;
    }

    /** Vuelve a abrir una promoción agotada, y sus artículos (desde la pantalla, o al subir su cupo). */
    public static function reabrir(int $idPromo): void
    {
        Db::getInstance()->update('kpy_coupon', ['cupo_agotado' => null], 'id_kpy_coupon = ' . (int) $idPromo, 0, true);
        Db::getInstance()->update('kpy_coupon_scope', ['agotado' => null], 'id_kpy_coupon = ' . (int) $idPromo, 0, true);
        self::olvidarCache();
        KpcCupon::olvidarCache();
    }

    /**
     * Las reservas de cestas que ya no existen, ya son un pedido o llevan más de dos días sin
     * tocarse (las caducadas no cuentan de todos modos): fuera. Lo llama el barrido de `KpcLimpieza`.
     *
     * @return int filas borradas
     */
    public static function limpiar(): int
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $db->execute(
            "DELETE r FROM {$p}kpy_coupon_cupo_cesta r
             LEFT JOIN {$p}cart c ON c.id_cart = r.id_cart
             WHERE c.id_cart IS NULL OR EXISTS (SELECT 1 FROM {$p}orders o WHERE o.id_cart = r.id_cart)
                OR r.date_upd < DATE_SUB(NOW(), INTERVAL 2 DAY)"
        );

        return (int) $db->Affected_Rows();
    }

    /** Lo que cuenta el cupo de una promoción, dicho para la pantalla («regalos dados»). */
    public static function unidadDe(string $tipo): string
    {
        return self::UNIDAD[$tipo] ?? self::UNIDAD['special_price'];
    }
}
