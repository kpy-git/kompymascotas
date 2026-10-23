<?php
/**
 * Lo que se rehace en cada guardado de la cesta, siempre en el mismo orden:
 *
 *   1. **Regalos** (`KpcRegalo::aplicar`): se cuentan los disparadores que el cliente ha pedido
 *      con regalo (la tarjeta «12 kg + 3 kg gratis»), sin contar unidades regaladas; se dejan las
 *      líneas de regalo con la cantidad que toca (y reservadas en el cupo, si lo hay).
 *   2. **Special Price con cupo** (`KpcCupoCesta::especial`): qué unidades de cada línea caben en
 *      el cupo (reservadas) y cuáles van al precio habitual.
 *   3. **El precio habitual de la cesta** (`KpcPrecioCesta`): las líneas con unidades con regalo o
 *      que no caben en el cupo van enteras al precio de siempre (PrestaShop solo deja un precio por
 *      línea); las unidades que sí llevan el Special Price lo recuperan en el paso 6.
 *   4. **2ª unidad**: solo con las unidades que paga el cliente (las regaladas no cuentan), y con
 *      cupo, solo las parejas que caben.
 *   5. **Cupón**: se decide cuál va puesto (el elegido, el escrito o el que entra solo) y se pone o
 *      se quita. El descuento lo calcula PrestaShop con su regla de carrito; con cupo, un vale de la
 *      cesta por producto con las unidades que caben (`KpcCupoCesta::cupon`).
 *   6. **Los vales del regalo** (`KpcRegalo::regla`) y **del Special Price** de las unidades que
 *      lo conservan en una línea que va al precio habitual (`KpcRegalo::reglaEspecial`), los
 *      últimos: sobre lo que ya cuesta tras los pasos anteriores.
 *
 * Todo dentro del cerrojo del cupo (`KpcCupo::cerrojo`) cuando hay alguna promoción con cupo: dos
 * cestas a la vez no pueden llevarse la misma unidad.
 *
 * Regla de oro del cupón: si no descuenta nada en esta cesta (no hay ningún formato suyo dentro),
 * se quita. Un cupón puesto que no baja el precio es una queja segura.
 */
class KpcCarrito
{
    /** @var bool evita la pescadilla: meter la regla (o el regalo) vuelve a guardar la cesta */
    private static bool $dentro = false;

    /** ¿Se está repasando una cesta ahora mismo? (los guardados que provoca el repaso no son del cliente) */
    public static function ocupado(): bool
    {
        return self::$dentro;
    }

    /**
     * @param string $codigo el cupón que el cliente lleva elegido (vacío si ninguno)
     * @param callable|null $entraSolo fn(array $cupon): bool — si no hay elegido, ¿este entra solo
     *                                 (web o canal de la visita)?
     * @param callable|null $adoptar fn(string $codigo): bool — el cliente ha escrito otro código en
     *                               la cesta: pasa a ser el elegido (false = no, que lo acaba de quitar)
     */
    public static function sincronizar(Cart $cart, string $codigo, int $idTienda, ?callable $entraSolo = null, ?callable $adoptar = null): void
    {
        if (self::$dentro || !$cart->id) {
            return;
        }
        self::$dentro = true;
        $conCupo = KpcCupo::hayCupos($idTienda);
        $cerrojo = $conCupo && KpcCupo::cerrojo();

        try {
            if ($conCupo) {
                // Las reservas de antes, fuera: se vuelven a poner las que toquen en cada paso.
                KpcCupo::vaciar((int) $cart->id);
            }
            $regalos = KpcRegalo::aplicar($cart, $idTienda);
            if (!empty($regalos['cambiado'])) {
                // Líneas nuevas o cantidades nuevas: lo que sigue las lee ya.
                $cart->getProducts(true);
            }
            $regaladas = (array) ($regalos['totales'] ?? []);
            $cupoEspecial = KpcCupoCesta::especial($cart, $idTienda, $regalos);
            if (self::preciosHabituales($cart, $idTienda, $regalos, $cupoEspecial)) {
                $cart->getProducts(true);
            }
            self::segundasUnidades($cart, $idTienda, $regaladas);
            $cuponPuesto = self::cupon($cart, $codigo, $idTienda, $regaladas, $entraSolo, $adoptar);
            self::valesMuertos($cart, $idTienda);
            KpcRegalo::regla($cart, $idTienda, $regalos, $cuponPuesto);
            KpcRegalo::reglaEspecial($cart, $regalos, $cupoEspecial['con'] ?? []);
            if (!empty($regalos['cambiado'])) {
                // Las líneas han cambiado dentro del guardado: que quien siga con esta cesta en la
                // misma petición (la respuesta de la cesta por ajax) las lea nuevas.
                $cart->getProducts(true);
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons: ' . $e->getMessage(), 2, null, 'Cart', (int) $cart->id, true);
        } finally {
            if ($cerrojo) {
                KpcCupo::soltar();
            }
            self::$dentro = false;
        }
    }

    /**
     * Las líneas que van enteras al precio habitual: las que llevan unidades con regalo (su
     * elección) y las que no caben enteras en el cupo. Devuelve si ha cambiado algún precio.
     */
    private static function preciosHabituales(Cart $cart, int $idTienda, array $regalos, array $cupoEspecial): bool
    {
        $enCesta = [];
        foreach (KpcRegalo::lineasDe($cart->getProducts()) as $l) {
            $enCesta[(int) $l['clave']] = ['id_product' => (int) $l['id_product'], 'ipa' => (int) $l['ipa']];
        }
        $necesitan = (array) ($cupoEspecial['habitual'] ?? []);
        // Las elegidas con regalo que de verdad lo llevan (sin regalo, agotado o tope, nadie paga de más).
        $conRegalo = [];
        foreach ((array) ($regalos['regalos'] ?? []) as $r) {
            if ((int) ($r['cantidad'] ?? 0) > 0) {
                $conRegalo[(int) $r['disparador']] = true;
            }
        }
        foreach ((array) ($regalos['elegidas'] ?? []) as $clave) {
            if (isset($conRegalo[(int) $clave])) {
                $necesitan[(int) $clave] = true;
            }
        }

        return KpcPrecioCesta::ajustar($cart, $idTienda, $necesitan, $enCesta);
    }

    /**
     * El cupón de Kompy que va puesto en esta cesta (solo puede haber uno). Devuelve su fila si al
     * final queda puesto, o null.
     *
     * @param array<int, int> $regaladas clave => unidades regaladas (no cuentan para que un cupón entre solo)
     */
    private static function cupon(Cart $cart, string $codigo, int $idTienda, array $regaladas, ?callable $entraSolo, ?callable $adoptar): ?array
    {
        $nuestras = self::reglasDeKompy($idTienda);
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        $conCupo = KpcCupoCesta::reglasCupon($cart);

        $deseada = 0;
        if ($codigo !== '') {
            $cupon = KpcCupon::porCodigo($codigo, $idTienda);
            $deseada = $cupon !== null ? (int) $cupon['id_cart_rule'] : 0;
        }

        // Aquí nunca se deja más de un cupón de Kompy; si hay otro puesto, lo ha escrito el
        // cliente en «¿Tienes un cupón?» (PrestaShop lo mete sin avisar). Manda ese: es lo
        // último que ha hecho. Antes se quitaba en cuanto cambiaba una cantidad.
        foreach ($puestas as $id) {
            if (!isset($nuestras[$id]) || $id === $deseada) {
                continue;
            }
            // Solo si sigue en marcha: uno apagado o terminado se quita abajo.
            $escrito = KpcCupon::porCodigo($nuestras[$id], $idTienda);
            // `$adoptar` dice que no si es el que el cliente acaba de quitar con la papelera.
            if ($escrito !== null && (int) $escrito['id_cart_rule'] === $id
                && ($adoptar === null || $adoptar($nuestras[$id]))) {
                $deseada = $id;
                break;
            }
        }

        // Sin elegido: el que entra solo (web o canal), el que más descuenta de los que tienen
        // algo en esta cesta (algo que el cliente paga: un regalo no hace entrar un cupón).
        if ($deseada <= 0 && $entraSolo !== null) {
            $enCesta = KpcReparto::deClaves($idTienda, self::clavesPagadas($cart, $regaladas));
            foreach (KpcCupon::enVigor($idTienda) as $cupon) {
                if (($cupon['tipo'] ?? 'cupon') === 'cupon' && (int) $cupon['id_cart_rule'] > 0
                    && !empty($enCesta[(int) $cupon['id_kpy_coupon']]) && $entraSolo($cupon)) {
                    $deseada = (int) $cupon['id_cart_rule'];
                    break;
                }
            }
        }

        // Solo puede haber un cupón de Kompy a la vez (también entre los vales de cupón con cupo).
        foreach ($puestas as $id) {
            if (isset($nuestras[$id]) && $id !== $deseada) {
                $cart->removeCartRule($id);
            }
        }
        $promoDeseada = 0;
        $cuponDeseado = null;
        if ($deseada > 0) {
            foreach (KpcCupon::enVigor($idTienda) as $cupon) {
                if ((int) $cupon['id_cart_rule'] === $deseada) {
                    $cuponDeseado = $cupon;
                    $promoDeseada = (int) $cupon['id_kpy_coupon'];
                    break;
                }
            }
        }
        foreach ($conCupo as $idRegla => $idPromo) {
            if ($idPromo !== $promoDeseada) {
                KpcVale::quitar($cart, $idRegla);
            }
        }

        if ($cuponDeseado === null) {
            return null;
        }
        $regla = new CartRule($deseada);
        if (!Validate::isLoadedObject($regla)) {
            return null;
        }

        // Con cupo, nada de la regla del cupón: un vale por producto con las unidades que caben.
        if (KpcCupo::tieneCupo($cuponDeseado)) {
            $suyos = KpcReparto::deClaves($idTienda, KpcReparto::clavesDe($cart->getProducts()))[$promoDeseada] ?? [];
            $valor = KpcCupoCesta::cupon($cart, $cuponDeseado, $idTienda, $suyos, $regaladas);

            return $valor > 0 ? $cuponDeseado : null;
        }

        $valor = self::descuento($regla, $cart);
        if ($valor > 0 && !in_array($deseada, $puestas, true)) {
            $cart->addCartRule($deseada);
        } elseif ($valor <= 0 && in_array($deseada, $puestas, true)) {
            $cart->removeCartRule($deseada);
        }

        return $valor > 0 ? $cuponDeseado : null;
    }

    /**
     * Las promociones de «2ª unidad» son automáticas: no hace falta que el cliente las pida, así que
     * se revisan en cada cambio de la cesta. Solo se mira el cupón cuyos formatos están en la cesta.
     * Con cupo, solo las parejas que caben, y se reservan.
     *
     * @param array<int, int> $regaladas clave => unidades regaladas, que no cuentan como pares
     */
    private static function segundasUnidades(Cart $cart, int $idTienda, array $regaladas = []): void
    {
        // Con el reparto, un formato que ya se lleva un cupón mejor no entra además en la 2ª unidad.
        $suyos = KpcReparto::deClaves($idTienda, KpcReparto::clavesDe($cart->getProducts()));
        foreach (KpcCupon::enVigor($idTienda) as $datos) {
            if (($datos['tipo'] ?? 'cupon') !== 'segunda_unidad') {
                continue;
            }
            $id = (int) $datos['id_kpy_coupon'];
            $conCupo = KpcCupo::tieneCupo($datos);
            // Sin artículos suyos en la cesta, se quita la regla que pudiera quedar. Con cupo, lo que
            // queda de la promoción y, con unidades por artículo, lo de cada uno.
            $cuenta = KpcSegunda::sincronizar(
                $cart, $datos, $suyos[$id] ?? [], $regaladas,
                $conCupo ? KpcCupo::quedanPara($id, (int) $cart->id) : null,
                $conCupo ? KpcCupo::quedanPorClave($id, (int) $cart->id, $suyos[$id] ?? []) : []
            );
            if ($conCupo) {
                KpcCupo::reservar((int) $cart->id, $id, $cuenta['por_linea'] ?? []);
            }
        }
    }

    /**
     * Los vales por cesta de promociones que ya no están en vigor (terminada, apagada o agotada): fuera
     * ahora, sin esperar al barrido. Los del regalo y del Special Price de una línea mezclada los
     * repasan sus propios pasos.
     */
    private static function valesMuertos(Cart $cart, int $idTienda): void
    {
        $vivas = [];
        foreach (KpcCupon::enVigor($idTienda) as $c) {
            $vivas[(int) $c['id_kpy_coupon']] = true;
        }
        foreach ($cart->getCartRules() as $cr) {
            $codigo = (string) ($cr['code'] ?? '');
            $partes = explode('-', $codigo);
            if (!in_array($partes[0] ?? '', [KpcSegunda::PREFIJO, KpcCupoCesta::PREFIJO_CUPON], true) || (int) ($partes[2] ?? 0) !== (int) $cart->id) {
                continue;
            }
            if (!isset($vivas[(int) ($partes[1] ?? 0)])) {
                KpcVale::quitar($cart, (int) $cr['id_cart_rule']);
            }
        }
    }

    /** Las claves de la cesta con alguna unidad que el cliente paga (no solo regaladas). @return int[] */
    private static function clavesPagadas(Cart $cart, array $regaladas): array
    {
        $salida = [];
        foreach ($cart->getProducts() as $linea) {
            $clave = KpcAlcance::clave((int) ($linea['id_product'] ?? 0), (int) ($linea['id_product_attribute'] ?? 0));
            if ((int) ($linea['cart_quantity'] ?? 0) - (int) ($regaladas[$clave] ?? 0) > 0) {
                $salida[] = $clave;
            }
        }

        return $salida;
    }

    /** Cuánto descuenta esta regla en esta cesta, con impuestos. */
    public static function descuento(CartRule $regla, Cart $cart): float
    {
        $contexto = Context::getContext();
        $antes = $contexto->cart;
        $contexto->cart = $cart;
        try {
            return (float) $regla->getContextualValue(true, $contexto, CartRule::FILTER_ACTION_REDUCTION);
        } finally {
            $contexto->cart = $antes;
        }
    }

    /** ¿Está puesto en la cesta este cupón de Kompy? (su regla, o sus vales si tiene cupo) */
    public static function cuponPuesto(Cart $cart, array $cupon, array $puestas): bool
    {
        if (in_array((int) ($cupon['id_cart_rule'] ?? 0), $puestas, true)) {
            return true;
        }

        return KpcCupo::tieneCupo($cupon) && in_array((int) $cupon['id_kpy_coupon'], KpcCupoCesta::reglasCupon($cart), true);
    }

    /**
     * Los productos de una cesta que ya llevan una promoción de este módulo (cupón, 2ª unidad o
     * regalo).
     *
     * Lo usa `kpycrosssell` para no poner encima su descuento por llevarlos juntos: dos promociones
     * sobre lo mismo es el fallo que ya se corrigió aquí.
     *
     * @return int[] id_product
     */
    public static function productosConPromo(Cart $cart, int $idTienda): array
    {
        if (!$cart || !$cart->id) {
            return [];
        }
        $enCesta = [];
        foreach ($cart->getProducts() as $linea) {
            $enCesta[KpcAlcance::clave((int) ($linea['id_product'] ?? 0), (int) ($linea['id_product_attribute'] ?? 0))] = (int) ($linea['id_product'] ?? 0);
        }
        if ($enCesta === []) {
            return [];
        }

        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        $reparto = KpcReparto::deClaves($idTienda, array_keys($enCesta));

        $salida = [];
        foreach (KpcCupon::enVigor($idTienda) as $datos) {
            $id = (int) $datos['id_kpy_coupon'];
            $tipo = (string) ($datos['tipo'] ?? 'cupon');
            // El regalo solo cuenta en las líneas que el cliente ha pedido con regalo (abajo).
            if ($tipo === 'special_price' || $tipo === 'regalo') {
                continue;
            }
            // Un cupón solo cuenta si está de verdad puesto; la 2ª unidad y el regalo entran solos.
            if ($tipo === 'cupon' && !self::cuponPuesto($cart, $datos, $puestas)) {
                continue;
            }
            foreach ($reparto[$id] ?? [] as $clave) {
                if (isset($enCesta[$clave])) {
                    $salida[] = $enCesta[$clave];
                }
            }
        }
        // Y las líneas con regalo y lo regalado en esta cesta.
        foreach (array_keys(KpcRegalo::elegidasDe((int) $cart->id)) as $clave) {
            if (isset($enCesta[$clave])) {
                $salida[] = $enCesta[$clave];
            }
        }
        foreach (KpcRegalo::filasCesta((int) $cart->id) as $f) {
            $salida[] = (int) $f['r_id_product'];
        }

        return array_values(array_unique($salida));
    }

    /** @return array<int, string> id de regla de carrito => código del cupón */
    public static function reglasDeKompy(int $idTienda): array
    {
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_cart_rule, codigo FROM ' . _DB_PREFIX_ . 'kpy_coupon
             WHERE id_cart_rule > 0 AND id_shop = ' . (int) $idTienda
        ) ?: [];
        $salida = [];
        foreach ($filas as $f) {
            $salida[(int) $f['id_cart_rule']] = (string) $f['codigo'];
        }

        return $salida;
    }
}
