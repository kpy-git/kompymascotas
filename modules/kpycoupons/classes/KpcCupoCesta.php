<?php
/**
 * El cupo (`KpcCupo`) dentro de una cesta, para las promociones que no son un regalo (el regalo lo
 * reparte `KpcRegalo::plan` con los mismos números):
 *
 *   - **Special Price con cupo** (`especial()`): de cada línea con un Special Price de una promoción
 *     con cupo, las unidades que caben se quedan con el precio y se reservan; las que no caben van
 *     al precio habitual. Como PrestaShop solo deja un precio por línea, una línea partida va entera
 *     al precio habitual (`KpcPrecioCesta`) y las unidades que sí llevan el Special Price lo
 *     recuperan con un vale de la cesta (`KpcRegalo::reglaEspecial`, «Liquidación: 2 × …»).
 *   - **Cupón con cupo** (`cupon()`): la regla de carrito del cupón descuenta el % a todas las
 *     unidades de sus artículos, así que con cupo no se mete esa regla en la cesta: se mete un vale
 *     de la cesta por producto (`KPYCP-…`) con el importe justo de las unidades que caben, con el
 *     código del cupón como nombre. En la cesta se ve igual que el cupón de siempre (su color, su
 *     papelera).
 *   - **2ª unidad con cupo**: lo recorta `KpcSegunda::calcular` (las parejas que caben).
 *
 * Todo se rehace en cada repaso de la cesta, dentro del cerrojo de `KpcCupo`: no hay estados que
 * mantener, solo las reservas (`kpy_coupon_cupo_cesta`), que se reescriben.
 */
class KpcCupoCesta
{
    /** Prefijo del vale por cesta de un cupón con cupo: KPYCP-{promoción}-{cesta}-{producto}-{firma}. */
    public const PREFIJO_CUPON = 'KPYCP';

    /**
     * Las líneas de la cesta con un Special Price de una promoción con cupo: cuántas unidades de
     * cada una se quedan con el precio (reservadas) y cuáles tienen que ir al precio habitual.
     *
     * @param array $plan lo que devuelve `KpcRegalo::aplicar()` (`unidades`: las que van con regalo
     *                    en cada línea; esas ya van al precio habitual y no gastan cupo)
     *
     * @return array{habitual: array<int, true>, con: array<int, array{n: int, etiqueta: string, hasta: string, promo: int, pide: int}>}
     *         habitual: las líneas que van al precio habitual por el cupo; con: por línea, las
     *         unidades que se quedan con el Special Price y cómo se llama («Liquidación»)
     */
    public static function especial(Cart $cart, int $idTienda, array $plan): array
    {
        $vacio = ['habitual' => [], 'con' => []];
        $idCart = (int) $cart->id;
        if ($idCart <= 0 || !KpcCupo::hayCupos($idTienda)) {
            return $vacio;
        }
        $lineas = KpcRegalo::lineasDe($cart->getProducts());
        $promos = array_filter(self::promosEspecial($lineas, $idTienda), static fn (array $f): bool => KpcCupo::tieneCupo($f));
        if ($promos === []) {
            return $vacio;
        }

        $previas = KpcRegalo::previasDe($idCart);
        $conRegalo = (array) ($plan['unidades'] ?? []);
        // Qué pide cada línea, por promoción y en el orden de la cesta.
        $quiere = [];
        foreach ($lineas as $l) {
            $clave = (int) $l['clave'];
            if (!isset($promos[$clave])) {
                continue;
            }
            $pide = max(0, (int) $l['qty'] - (int) ($previas[$clave] ?? 0) - (int) ($conRegalo[$clave] ?? 0));
            $quiere[(int) $promos[$clave]['id_kpy_coupon']][$clave] = $pide;
        }

        $salida = $vacio;
        foreach ($quiere as $idPromo => $porClave) {
            $reparto = KpcCupo::repartirPara($idPromo, $idCart, $porClave);
            KpcCupo::reservar($idCart, $idPromo, $reparto);
            foreach ($porClave as $clave => $pide) {
                $n = (int) ($reparto[$clave] ?? 0);
                $f = $promos[$clave];
                if ($n < $pide) {
                    $salida['habitual'][$clave] = true;
                }
                $salida['con'][$clave] = [
                    'n' => $n,
                    'pide' => $pide,
                    'etiqueta' => KpcCupo::nombreEtiqueta((string) ($f['etiqueta'] ?? '')),
                    'clase' => KpcCupo::claseEtiqueta((string) ($f['etiqueta'] ?? '')),
                    'hasta' => (string) ($f['fecha_hasta'] ?? ''),
                    'promo' => $idPromo,
                ];
            }
        }

        return $salida;
    }

    /**
     * Las líneas de una cesta cuyo Special Price es de una promoción con cupo o con nombre propio
     * («Liquidación», «Bajada de precio»), para pintarlas (`KpcRegalo::partesDe`): cuántas unidades
     * tienen reservadas en el cupo (`n`; sin cupo, todas), cómo se llama y su color. Solo lee.
     *
     * @return array<int, array{n: int, cupo: bool, etiqueta: string, clase: string, promo: int}>
     */
    public static function especialesDe(Cart $cart): array
    {
        $idCart = (int) $cart->id;
        if ($idCart <= 0) {
            return [];
        }
        $lineas = KpcRegalo::lineasDe($cart->getProducts());
        $promos = self::promosEspecial($lineas, (int) $cart->id_shop ?: (int) Configuration::get('PS_SHOP_DEFAULT'));
        if ($promos === []) {
            return [];
        }
        $reservas = KpcCupo::reservasDe($idCart);
        $salida = [];
        foreach ($promos as $clave => $f) {
            $idPromo = (int) $f['id_kpy_coupon'];
            $salida[$clave] = [
                'n' => (int) ($reservas[$idPromo][$clave] ?? 0),
                'cupo' => KpcCupo::tieneCupo($f),
                'etiqueta' => KpcCupo::nombreEtiqueta((string) ($f['etiqueta'] ?? '')),
                'clase' => KpcCupo::claseEtiqueta((string) ($f['etiqueta'] ?? '')),
                'promo' => $idPromo,
            ];
        }

        return $salida;
    }

    /**
     * De unas líneas, las que tienen un Special Price puesto por una promoción de esta pantalla que
     * tiene cupo o nombre propio, con esa promoción. Las agotadas también entran (hasta que el reparto
     * quite sus precios, en la siguiente visita, el catálogo aún los tiene): con 0 que queden, todo al
     * precio habitual. Entre dos, la más antigua (un Special Price no pisa otro).
     *
     * @param array<int, array{clave: int, id_product: int, ipa: int}> $lineas
     *
     * @return array<int, array> clave => fila con id_kpy_coupon, cupo, etiqueta, fecha_hasta
     */
    private static function promosEspecial(array $lineas, int $idTienda): array
    {
        if ($lineas === []) {
            return [];
        }
        $pares = [];
        foreach ($lineas as $l) {
            $pares[] = '(' . (int) $l['id_product'] . ', ' . (int) $l['ipa'] . ')';
        }
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance()->executeS(
            "SELECT cp.id_product, cp.id_product_attribute AS ipa, c.id_kpy_coupon, c.cupo, c.cupo_lista, c.etiqueta, c.fecha_hasta
             FROM {$p}kpy_coupon_precio cp
             INNER JOIN {$p}kpy_coupon c ON c.id_kpy_coupon = cp.id_kpy_coupon
             WHERE (c.cupo > 0 OR c.cupo_lista = 1 OR c.etiqueta <> '') AND c.activo = 1 AND c.tipo = 'special_price' AND c.id_shop = " . $idTienda . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND (cp.id_product, cp.id_product_attribute) IN (' . implode(',', $pares) . ')
             ORDER BY c.id_kpy_coupon'
        ) ?: [] as $f) {
            $salida[KpcAlcance::clave((int) $f['id_product'], (int) $f['ipa'])] ??= $f;
        }

        return $salida;
    }

    /**
     * Un cupón con cupo en esta cesta: por producto, un vale con el importe justo de las unidades
     * que caben (y se reservan). La regla de carrito del cupón, fuera de la cesta (descontaría
     * todas). Devuelve lo que descuentan los vales.
     *
     * @param array $cupon la fila de la promoción (`KpcCupon::enVigor`)
     * @param int[] $claves los artículos de esta cesta a los que llega el cupón (del reparto)
     * @param array<int, int> $regaladas clave => unidades regaladas (no se descuentan)
     */
    public static function cupon(Cart $cart, array $cupon, int $idTienda, array $claves, array $regaladas): float
    {
        $idCart = (int) $cart->id;
        $idPromo = (int) $cupon['id_kpy_coupon'];
        $pct = (float) ($cupon['porcentaje'] ?? 0);
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        if (in_array((int) ($cupon['id_cart_rule'] ?? 0), $puestas, true)) {
            $cart->removeCartRule((int) $cupon['id_cart_rule']);
        }

        // Qué pide cada línea suya, en el orden de la cesta.
        $quiere = [];
        $porClave = [];
        foreach ($cart->getProducts() as $l) {
            $clave = KpcAlcance::clave((int) $l['id_product'], (int) ($l['id_product_attribute'] ?? 0));
            if (!in_array($clave, $claves, true) || !empty($l['id_customization'])) {
                continue;
            }
            $quiere[$clave] = max(0, (int) ($l['cart_quantity'] ?? 0) - (int) ($regaladas[$clave] ?? 0));
            $porClave[$clave] = $l;
        }
        $reparto = KpcCupo::repartirPara($idPromo, $idCart, $quiere);
        KpcCupo::reservar($idCart, $idPromo, $reparto);

        // Un vale por producto, con el importe de sus unidades que caben.
        $porProducto = [];
        foreach ($reparto as $clave => $n) {
            if ($n <= 0) {
                continue;
            }
            $l = $porClave[$clave];
            $id = (int) $l['id_product'];
            $precio = Tools::ps_round((float) ($l['price_wt'] ?? $l['price'] ?? 0), 2);
            $porProducto[$id]['importe'] = ($porProducto[$id]['importe'] ?? 0.0) + $n * $precio * $pct / 100;
            $porProducto[$id]['nombres'][] = $n . ' × ' . KpcRegalo::nombreCorto((string) ($l['name'] ?? ''), (string) ($l['attributes'] ?? ''));
        }
        $hasta = KpcVale::hasta((string) ($cupon['fecha_hasta'] ?? ''));
        $titulo = (string) ($cupon['codigo'] ?? '') . ' (-' . KpcElegibles::pct($pct) . ' %)';
        $total = 0.0;
        $vivas = [];
        foreach ($porProducto as $id => $d) {
            $importe = round($d['importe'], 6);
            if ($importe <= 0) {
                continue;
            }
            $idRegla = KpcVale::asegurar($cart, self::codigoCupon($cart, $idPromo, $id), $id, $importe, $titulo . ': ' . implode(' + ', $d['nombres']), $hasta);
            if ($idRegla > 0) {
                $vivas[$idRegla] = true;
                if (!in_array($idRegla, $puestas, true)) {
                    $cart->addCartRule($idRegla);
                }
                $total += $importe;
            }
        }
        self::quitarCupon($cart, $idPromo, $vivas);

        return round($total, 2);
    }

    /**
     * Fuera los vales de cupón con cupo de esta cesta (los de una promoción, o todos), salvo los que
     * se digan.
     *
     * @param array<int, true> $salvo ids de regla que se quedan
     */
    public static function quitarCupon(Cart $cart, int $idPromo = 0, array $salvo = []): void
    {
        foreach ($cart->getCartRules() as $cr) {
            $idRegla = (int) ($cr['id_cart_rule'] ?? 0);
            $codigo = (string) ($cr['code'] ?? '');
            if (isset($salvo[$idRegla]) || !self::esReglaCupon($codigo, (int) $cart->id)) {
                continue;
            }
            if ($idPromo > 0 && self::promoDeRegla($codigo) !== $idPromo) {
                continue;
            }
            KpcVale::quitar($cart, $idRegla);
        }
    }

    /** Los vales de cupón con cupo puestos en una cesta: id de regla => id de promoción. */
    public static function reglasCupon(Cart $cart): array
    {
        $salida = [];
        foreach ($cart->getCartRules() as $cr) {
            $codigo = (string) ($cr['code'] ?? '');
            if (self::esReglaCupon($codigo, (int) $cart->id)) {
                $salida[(int) $cr['id_cart_rule']] = self::promoDeRegla($codigo);
            }
        }

        return $salida;
    }

    /** Lo que descuentan en una cesta los vales de un cupón con cupo. */
    public static function descuentoCupon(Cart $cart, int $idPromo): float
    {
        $total = 0.0;
        foreach ($cart->getCartRules() as $cr) {
            if ((int) self::promoDeRegla((string) ($cr['code'] ?? '')) === $idPromo && self::esReglaCupon((string) $cr['code'], (int) $cart->id)) {
                $total += (float) ($cr['value_real'] ?? $cr['reduction_amount'] ?? 0);
            }
        }

        return round($total, 2);
    }

    public static function codigoCupon(Cart $cart, int $idPromo, int $idProducto): string
    {
        return self::PREFIJO_CUPON . '-' . $idPromo . '-' . (int) $cart->id . '-' . $idProducto . '-' . KpcVale::firma($cart, 'cupon|' . $idPromo . '|' . $idProducto);
    }

    /** ¿Es un vale de cupón con cupo de esta cesta? */
    public static function esReglaCupon(string $codigo, int $idCart): bool
    {
        $partes = explode('-', $codigo);

        return ($partes[0] ?? '') === self::PREFIJO_CUPON && (int) ($partes[2] ?? 0) === $idCart;
    }

    /** La promoción de un vale de cupón con cupo (0 si no lo es). */
    public static function promoDeRegla(string $codigo): int
    {
        $partes = explode('-', $codigo);

        return ($partes[0] ?? '') === self::PREFIJO_CUPON ? (int) ($partes[1] ?? 0) : 0;
    }
}
