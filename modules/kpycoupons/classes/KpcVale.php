<?php
/**
 * Los vales de una cesta: las reglas de carrito que este módulo crea **para una cesta concreta**
 * (la de la 2ª unidad, `KPY2U-…`; la del regalo, `KPYRG-…`; la del Special Price de una línea
 * mezclada, `KPYSP-…`; y, desde la 2.3.0, la del cupón con cupo, `KPYCP-…`). Todas iguales por
 * dentro: un importe fijo, con IVA, sobre un producto («este producto»: el importe se reparte entre
 * sus líneas y nunca pasa de lo que valen), con un código imposible de adivinar desde fuera y una
 * fecha de fin. Aquí está lo común: crearlas sin reescribirlas si no cambian, y quitarlas.
 */
class KpcVale
{
    /** Los prefijos de los códigos de todas las reglas por cesta de este módulo. */
    public const PREFIJOS = ['KPY2U', 'KPYRG', 'KPYSP', 'KPYCP'];

    /** Las reglas de los cupones van antes (prioridad 1): estas se descuentan sobre lo que ya cuesta. */
    public const PRIORIDAD = 90;

    /**
     * Crea o actualiza un vale de esta cesta para un producto. Si ya está como tiene que estar, no se
     * reescribe (la cesta se repasa a menudo).
     *
     * @return int su id (0 si falla)
     */
    public static function asegurar(Cart $cart, string $codigo, int $idProducto, float $importe, string $nombre, string $hasta): int
    {
        $idRegla = (int) CartRule::getIdByCode($codigo);
        $regla = $idRegla > 0 ? new CartRule($idRegla) : new CartRule();
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        // El nombre viaja a `cart_rule_lang`, que en esta tienda no aguanta el menos tipográfico.
        $nombre = mb_substr(str_replace(['−', '–', '—'], '-', $nombre), 0, 254);
        $igual = $regla->id
            && abs((float) $regla->reduction_amount - $importe) < 0.000005
            && (int) $regla->id_customer === (int) $cart->id_customer
            && (int) $regla->reduction_currency === (int) $cart->id_currency
            && (int) $regla->reduction_product === $idProducto
            && (string) (((array) $regla->name)[$idioma] ?? '') === $nombre
            && (string) $regla->date_to === $hasta;
        if ($igual) {
            return (int) $regla->id;
        }
        $regla->name = [$idioma => $nombre];
        $regla->code = $codigo;
        $regla->id_customer = (int) $cart->id_customer;
        if (!$regla->id) {
            $regla->date_from = date('Y-m-d H:i:s', strtotime('-1 hour'));
        }
        $regla->date_to = $hasta;
        $regla->quantity = 1;
        $regla->quantity_per_user = 1;
        $regla->priority = self::PRIORIDAD;
        $regla->reduction_amount = $importe;
        $regla->reduction_tax = 1;
        $regla->reduction_currency = (int) $cart->id_currency;
        $regla->reduction_percent = 0;
        // «Este producto»: el importe se reparte entre sus líneas y nunca pasa de lo que valen.
        $regla->reduction_product = $idProducto;
        $regla->product_restriction = 0;
        $regla->free_shipping = 0;
        $regla->partial_use = 0;
        $regla->highlight = 0;
        $regla->active = 1;
        try {
            if (!($regla->id ? $regla->update() : $regla->add())) {
                return 0;
            }
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, vale ' . $codigo . ': ' . $e->getMessage(), 3, null, 'Cart', (int) $cart->id, true);

            return 0;
        }

        return (int) $regla->id;
    }

    /** Quita un vale de la cesta y, si la cesta no es un pedido, lo borra. */
    public static function quitar(Cart $cart, int $idRegla): void
    {
        $regla = new CartRule($idRegla);
        if (!Validate::isLoadedObject($regla)) {
            return;
        }
        $cart->removeCartRule($idRegla);
        if (!$cart->orderExists()) {
            $regla->delete();
        }
    }

    /** La firma de un código de vale: distinta por cesta, imposible de adivinar desde fuera. */
    public static function firma(Cart $cart, string $sal): string
    {
        return mb_strtoupper(substr(md5($cart->secure_key . '|' . $sal . '|' . _COOKIE_KEY_), 0, 8));
    }

    /** ¿Es un vale por cesta de este módulo (cualquiera de los prefijos)? */
    public static function esNuestro(string $codigo): bool
    {
        $prefijo = (string) strtok($codigo, '-');

        return in_array($prefijo, self::PREFIJOS, true);
    }

    /**
     * Hasta cuándo vale un vale: hasta que acaba su promoción; sin fecha de fin, una semana que se
     * alarga sola cada vez que se toca la cesta.
     */
    public static function hasta(string $fechaHasta): string
    {
        $fin = KpcCupon::fechaValida($fechaHasta);
        if ($fin !== null && strtotime($fin) > time()) {
            return date('Y-m-d H:i:s', (int) strtotime($fin));
        }

        return date('Y-m-d H:i:s', strtotime('+7 days'));
    }
}
