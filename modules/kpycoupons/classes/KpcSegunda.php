<?php
/**
 * «2ª unidad al 30 %»: la promoción que más vende en pienso.
 *
 * PrestaShop no sabe hacer «la segunda unidad más barata» por sí solo (sus reglas descuentan todo
 * el producto o el más barato del carrito), así que la cuenta la hacemos aquí, **exacta**:
 *
 *     por cada par de unidades del mismo formato → media unidad al % del cupón
 *     (4 unidades = 2 pares = 2 unidades con descuento)
 *
 * y el importe se mete en una regla de carrito propia de ESA cesta, para que el descuento lo sume
 * PrestaShop en la cesta, en el pago y en el pedido, como cualquier otro vale.
 *
 * Con **cupo** (2.3.0), solo las parejas que caben: las unidades rebajadas (la 2ª de cada par) son
 * lo que cuenta el cupo, y se reparten en el orden de la cesta (`reparto()`, sin PrestaShop, para
 * probarlo sin tienda).
 *
 * Los packs no entran: el pack ya es el descuento por cantidad (lo filtra `KpcElegibles`).
 */
class KpcSegunda
{
    /** Prefijo del código de la regla de cada cesta. */
    public const PREFIJO = 'KPY2U';

    /**
     * La regla pura: cuántas unidades rebajadas hay en cada línea (una por par, y con cupo, hasta
     * donde llegue, en el orden de las líneas).
     *
     * @param array<int, int> $cantidades clave => unidades que paga el cliente, en el orden de la cesta
     * @param ?int $maximo las unidades rebajadas que caben en el cupo (null = sin límite)
     * @param array<int, ?int> $porClave con unidades por artículo, las que caben de cada uno
     *
     * @return array<int, int> clave => unidades rebajadas
     */
    public static function reparto(array $cantidades, ?int $maximo = null, array $porClave = []): array
    {
        $pares = [];
        foreach ($cantidades as $clave => $n) {
            $pares[$clave] = intdiv(max(0, (int) $n), 2);
        }

        return KpcCupo::repartirConLimites($pares, $maximo, $porClave);
    }

    /**
     * Lo que ahorra esta cesta con la promoción, y con cuántas unidades.
     *
     * @param array{id_kpy_coupon: int, porcentaje: float} $cupon
     * @param int[] $claves artículos a los que llega la promoción (`KpcAlcance::clave`)
     * @param array<int, int> $regaladas clave => unidades que son un regalo (no cuentan: solo se
     *                                   emparejan las que paga el cliente)
     * @param ?int $maximo con cupo, las unidades rebajadas que caben (null = sin límite)
     *
     * @return array{importe: float, unidades: int, pares: int, por_linea: array<int, int>}
     */
    public static function calcular(Cart $cart, array $cupon, array $claves, array $regaladas = [], ?int $maximo = null, array $porClave = []): array
    {
        $pct = (float) $cupon['porcentaje'];
        $cantidades = [];
        $precios = [];
        foreach ($cart->getProducts() as $linea) {
            $clave = KpcAlcance::clave((int) ($linea['id_product'] ?? 0), (int) ($linea['id_product_attribute'] ?? 0));
            if (!in_array($clave, $claves, true)) {
                continue;
            }
            $cantidades[$clave] = ($cantidades[$clave] ?? 0) + max(0, (int) ($linea['cart_quantity'] ?? 0) - (int) ($regaladas[$clave] ?? 0));
            $precios[$clave] = (float) ($linea['price_wt'] ?? $linea['price'] ?? 0);
        }
        $porLinea = self::reparto($cantidades, $maximo, $porClave);
        $importe = 0.0;
        foreach ($porLinea as $clave => $suyos) {
            $importe += $suyos * ($precios[$clave] ?? 0.0) * $pct / 100;
        }

        return ['importe' => round($importe, 2), 'unidades' => array_sum($cantidades), 'pares' => array_sum($porLinea), 'por_linea' => $porLinea];
    }

    /**
     * Pone, actualiza o quita la regla de esta cesta. Devuelve las cuentas (`calcular`), con lo que
     * se descuenta ahora en `importe`.
     */
    public static function sincronizar(Cart $cart, array $cupon, array $claves, array $regaladas = [], ?int $maximo = null, array $porClave = []): array
    {
        $cuenta = self::calcular($cart, $cupon, $claves, $regaladas, $maximo, $porClave);
        $codigo = self::codigo($cart, (int) $cupon['id_kpy_coupon']);
        $idRegla = (int) CartRule::getIdByCode($codigo);
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));

        if ($cuenta['importe'] <= 0) {
            if ($idRegla > 0) {
                if (in_array($idRegla, $puestas, true)) {
                    $cart->removeCartRule($idRegla);
                }
                self::borrarSiSobra($idRegla, $cart);
            }

            return ['importe' => 0.0] + $cuenta;
        }

        $regla = $idRegla > 0 ? new CartRule($idRegla) : new CartRule();
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $hasta = self::hasta($cupon);
        // La cesta se repasa a menudo (cada cambio y al verla): si la regla ya está como tiene que
        // estar, no se vuelve a escribir.
        $igual = $regla->id
            && abs((float) $regla->reduction_amount - $cuenta['importe']) < 0.005
            && (int) $regla->id_customer === (int) $cart->id_customer
            && (int) $regla->reduction_currency === (int) $cart->id_currency
            && (string) (((array) $regla->name)[$idioma] ?? '') === self::nombre($cupon)
            && ((string) $regla->date_to === $hasta || (!self::tieneFin($cupon) && strtotime((string) $regla->date_to) > strtotime('+6 days')));
        if (!$igual) {
            $regla->name = [$idioma => self::nombre($cupon)];
            $regla->code = $codigo;
            $regla->id_customer = (int) $cart->id_customer;
            if (!$regla->id) {
                $regla->date_from = date('Y-m-d H:i:s', strtotime('-1 hour'));
            }
            $regla->date_to = $hasta;
            $regla->quantity = 1;
            $regla->quantity_per_user = 1;
            $regla->reduction_amount = $cuenta['importe'];
            $regla->reduction_tax = 1;
            $regla->reduction_currency = (int) $cart->id_currency;
            $regla->reduction_percent = 0;
            $regla->reduction_product = 0;
            $regla->free_shipping = 0;
            $regla->partial_use = 0;
            $regla->highlight = 0;
            $regla->active = 1;
            $regla->id ? $regla->update() : $regla->add();
        }

        if (!in_array((int) $regla->id, $puestas, true)) {
            $cart->addCartRule((int) $regla->id);
        }

        return $cuenta;
    }

    /**
     * Hasta cuándo vale la regla de una cesta: **hasta que acaba la promoción** (Rafa, 30/09/2026:
     * antes se ponía un mes y la regla seguía viva después de la promo). Una promoción sin fecha de
     * fin deja la regla una semana, que se alarga sola cada vez que se toca la cesta.
     */
    public static function hasta(array $cupon): string
    {
        if (self::tieneFin($cupon)) {
            return date('Y-m-d H:i:s', (int) strtotime((string) $cupon['fecha_hasta']));
        }

        return date('Y-m-d H:i:s', strtotime('+7 days'));
    }

    /** ¿La promoción tiene fecha de fin (y es de hoy en adelante)? */
    private static function tieneFin(array $cupon): bool
    {
        $fin = (string) ($cupon['fecha_hasta'] ?? '');

        return $fin !== '' && strpos($fin, '0000') !== 0 && (int) strtotime($fin) > time();
    }

    /** «2ª unidad al 30 %» (el nombre que ve el cliente en la cesta y en el pedido). */
    public static function nombre(array $cupon): string
    {
        $pct = rtrim(rtrim(number_format((float) $cupon['porcentaje'], 2, ',', ''), '0'), ',');

        return '2ª unidad al ' . $pct . ' %';
    }

    /** Un código por cesta y cupón, imposible de adivinar desde fuera. */
    public static function codigo(Cart $cart, int $idCupon): string
    {
        $firma = substr(md5($cart->secure_key . '|' . $idCupon . '|' . _COOKIE_KEY_), 0, 8);

        return self::PREFIJO . '-' . $idCupon . '-' . (int) $cart->id . '-' . mb_strtoupper($firma);
    }

    /**
     * Barrido de reglas que sobran (cestas abandonadas, caducadas, promo terminada). Lo hace solo
     * `KpcLimpieza` cada 6 horas; esto queda para quien lo llamaba antes.
     *
     * @return int cuántas se han borrado
     */
    public static function limpiarViejas(): int
    {
        return array_sum(KpcLimpieza::barrer());
    }

    /** Las reglas de una cesta sin pedido no hacen falta para nada: se borran. */
    private static function borrarSiSobra(int $idRegla, Cart $cart): void
    {
        if ($cart->orderExists()) {
            return;
        }
        $regla = new CartRule($idRegla);
        if (Validate::isLoadedObject($regla) && strpos((string) $regla->code, self::PREFIJO . '-') === 0) {
            $regla->delete();
        }
    }
}
