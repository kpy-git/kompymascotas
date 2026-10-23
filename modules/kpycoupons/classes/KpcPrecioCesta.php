<?php
/**
 * El **precio habitual de una cesta** para una línea: un precio específico **de esa cesta**
 * (`specific_price.id_cart`, lo mismo que hace PrestaShop al cambiar un precio al crear un pedido
 * desde el back office) que deja un artículo con Special Price a su precio de siempre, sin la
 * rebaja. La ficha y los listados no lo ven (miran los precios sin cesta); la cesta y el pedido sí,
 * y gana al Special Price porque PrestaShop prefiere el precio de la cesta al del catálogo.
 *
 * Hace falta cuando no todas las unidades de una línea van al Special Price (PrestaShop solo deja
 * un precio por línea):
 *   - las unidades con regalo (la tarjeta «12 kg + 3 kg gratis») van al precio de siempre;
 *   - con un **cupo** (2.3.0), las unidades que ya no caben en él van al precio de siempre.
 * Las que sí llevan el Special Price lo recuperan con un vale de la cesta (`KpcRegalo::reglaEspecial`).
 *
 * Lleva la rebaja de siempre (`old_discount` de `kpy_special_price`), que es el «Precio habitual» de
 * la ficha, y con la misma condición que la ficha (que el Special Price rebaje de verdad): la
 * tarjeta y la línea de la cesta dicen el mismo número. Sin Special Price en el artículo no hace
 * falta nada: su precio ya es el de siempre.
 *
 * Qué precio ha puesto cada cesta queda en `kpy_coupon_precio_cesta` (hasta la 2.2.0 iba en la
 * elección del regalo): se quita en cuanto la línea ya no lo necesita, al pedir y al barrer cestas
 * muertas. Nunca se toca un precio del catálogo (sin cesta) ni uno de otra cesta.
 */
class KpcPrecioCesta
{
    /** @var array<int, array<int, int>> caché por cesta: clave => id del precio (se olvida al escribir) */
    private static array $cache = [];

    /** Los precios de cesta puestos en una cesta: clave de la línea => id del precio específico. */
    public static function de(int $idCart): array
    {
        if ($idCart <= 0) {
            return [];
        }
        if (!isset(self::$cache[$idCart])) {
            $salida = [];
            // De la base de datos principal: se lee justo después de escribirla, en la misma petición.
            foreach (Db::getInstance()->executeS(
                'SELECT clave, id_specific_price FROM ' . _DB_PREFIX_ . 'kpy_coupon_precio_cesta WHERE id_cart = ' . $idCart
            ) ?: [] as $f) {
                $salida[(int) $f['clave']] = (int) $f['id_specific_price'];
            }
            self::$cache[$idCart] = $salida;
        }

        return self::$cache[$idCart];
    }

    /**
     * Deja los precios de cesta justo en las líneas que los necesitan: los pone donde faltan (si el
     * artículo tiene un Special Price que rebaja de verdad), los quita donde sobran. Se llama una vez
     * por repaso de la cesta, dentro del candado de `KpcCarrito`.
     *
     * @param array<int, true> $necesitan claves de las líneas que van al precio habitual
     * @param array<int, array{id_product: int, ipa: int}> $enCesta clave => artículo de cada línea
     *
     * @return bool si ha cambiado algún precio (quien siga tiene que releer las líneas)
     */
    public static function ajustar(Cart $cart, int $idTienda, array $necesitan, array $enCesta): bool
    {
        $idCart = (int) $cart->id;
        if ($idCart <= 0) {
            return false;
        }
        $puestos = self::de($idCart);
        $cambiado = false;

        // Los que sobran: líneas que ya no lo necesitan (o ya no están).
        foreach ($puestos as $clave => $idPrecio) {
            if (!isset($necesitan[$clave]) || !isset($enCesta[$clave])) {
                self::quitar($idCart, (int) $clave);
                $cambiado = true;
            }
        }
        if ($necesitan === []) {
            return $cambiado;
        }

        // La rebaja de siempre de los artículos que lo necesitan y hoy tienen Special Price.
        $pares = [];
        foreach (array_keys($necesitan) as $clave) {
            if (isset($enCesta[$clave])) {
                $pares[] = '(' . (int) $enCesta[$clave]['id_product'] . ', ' . (int) $enCesta[$clave]['ipa'] . ')';
            }
        }
        $old = [];
        if ($pares !== [] && KpcCupon::hayTablaEspeciales()) {
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_product, id_product_attribute AS ipa, old_discount FROM ' . _DB_PREFIX_ . 'kpy_special_price
                 WHERE id_shop = ' . $idTienda . ' AND NOW() BETWEEN date_from AND expire
                   AND (id_product, id_product_attribute) IN (' . implode(',', $pares) . ')
                 ORDER BY date_from'
            ) ?: [] as $f) {
                $old[KpcAlcance::clave((int) $f['id_product'], (int) $f['ipa'])] = (float) $f['old_discount'];
            }
        }

        foreach (array_keys($necesitan) as $clave) {
            $linea = $enCesta[$clave] ?? null;
            if ($linea === null) {
                continue;
            }
            $idProducto = (int) $linea['id_product'];
            $ipa = (int) $linea['ipa'];
            $rebaja = $old[$clave] ?? null;
            if ($rebaja !== null && !self::rebajaDeVerdad($idProducto, $ipa, $rebaja)) {
                $rebaja = null;
            }
            $idPuesto = (int) ($puestos[$clave] ?? 0);
            if ($rebaja === null) {
                // Sin Special Price de verdad, la línea ya va a su precio de siempre: nada que poner.
                if ($idPuesto > 0) {
                    self::quitar($idCart, (int) $clave);
                    $cambiado = true;
                }
                continue;
            }
            $puesto = $idPuesto > 0 ? new SpecificPrice($idPuesto) : null;
            if ($puesto !== null && Validate::isLoadedObject($puesto) && (int) $puesto->id_cart === $idCart
                && (int) $puesto->id_product === $idProducto && (int) $puesto->id_product_attribute === $ipa
                && abs((float) $puesto->reduction - $rebaja) < 0.0000005) {
                continue; // Ya está como tiene que estar.
            }
            if ($idPuesto > 0) {
                self::quitar($idCart, (int) $clave);
            }
            $precio = new SpecificPrice();
            $precio->id_specific_price_rule = 0;
            $precio->id_cart = $idCart;
            $precio->id_product = $idProducto;
            $precio->id_product_attribute = $ipa;
            $precio->id_shop = $idTienda;
            $precio->id_shop_group = 0;
            $precio->id_currency = 0;
            $precio->id_country = 0;
            $precio->id_group = 0;
            // Sin cliente: la cesta es de quien sea (también de un invitado que luego entra en su cuenta).
            $precio->id_customer = 0;
            $precio->price = -1;
            $precio->from_quantity = 1;
            $precio->reduction = $rebaja;
            $precio->reduction_tax = 1;
            $precio->reduction_type = 'percentage';
            $precio->from = '0000-00-00 00:00:00';
            $precio->to = '0000-00-00 00:00:00';
            try {
                if (!$precio->add()) {
                    throw new RuntimeException('no se ha podido guardar');
                }
                Db::getInstance()->execute(
                    'INSERT INTO ' . _DB_PREFIX_ . 'kpy_coupon_precio_cesta (id_cart, clave, id_specific_price, date_upd)
                     VALUES (' . $idCart . ', ' . (int) $clave . ', ' . (int) $precio->id . ', NOW())
                     ON DUPLICATE KEY UPDATE id_specific_price = VALUES(id_specific_price), date_upd = NOW()'
                );
            } catch (Throwable $e) {
                // Uno que ya hubiera para esta cesta y este artículo (puesto a mano en el back office)
                // se respeta: la línea se queda con ese precio.
                PrestaShopLogger::addLog('kpycoupons, precio habitual de la cesta para ' . $idProducto . '-' . $ipa . ': ' . $e->getMessage(), 3, null, 'Cart', $idCart, true);
            }
            unset(self::$cache[$idCart]);
            $cambiado = true;
        }
        if ($cambiado) {
            self::olvidarPrecios();
        }

        return $cambiado;
    }

    /** Quita el precio de cesta de una línea (el suyo, por id: nunca otro). */
    public static function quitar(int $idCart, int $clave): void
    {
        $db = Db::getInstance();
        $idPrecio = (int) $db->getValue('SELECT id_specific_price FROM ' . _DB_PREFIX_ . 'kpy_coupon_precio_cesta WHERE id_cart = ' . $idCart . ' AND clave = ' . $clave);
        $db->delete('kpy_coupon_precio_cesta', 'id_cart = ' . $idCart . ' AND clave = ' . $clave);
        if ($idPrecio > 0) {
            $precio = new SpecificPrice($idPrecio);
            if (Validate::isLoadedObject($precio) && (int) $precio->id_cart > 0) {
                $precio->delete();
            }
        }
        unset(self::$cache[$idCart]);
        self::olvidarPrecios();
    }

    /** Todos los de una cesta, fuera (al pedir). */
    public static function quitarTodos(int $idCart): void
    {
        foreach (array_keys(self::de($idCart)) as $clave) {
            self::quitar($idCart, (int) $clave);
        }
    }

    /**
     * Los precios de cesta de las cestas que ya no existen, ya son un pedido o nadie toca desde hace
     * un mes: fuera (el precio y la fila). Si el cliente vuelve a una de esas, su línea va a su precio
     * de hoy y el repaso de la cesta vuelve a poner lo que toque.
     */
    public static function limpiar(int $dias = 30): void
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $muerta = "(c.id_cart IS NULL OR c.date_upd < DATE_SUB(NOW(), INTERVAL " . (int) $dias . " DAY)
                    OR EXISTS (SELECT 1 FROM {$p}orders o WHERE o.id_cart = e.id_cart))";
        // Los precios específicos, por la API del core (`SpecificPrice->delete()`), no con un DELETE
        // crudo (3.0.0): solo los que este módulo apuntó y que son de esa cesta.
        foreach ($db->executeS(
            "SELECT e.id_specific_price, e.id_cart FROM {$p}kpy_coupon_precio_cesta e
             LEFT JOIN {$p}cart c ON c.id_cart = e.id_cart
             WHERE {$muerta}"
        ) ?: [] as $f) {
            $sp = new SpecificPrice((int) $f['id_specific_price']);
            if (Validate::isLoadedObject($sp) && (int) $sp->id_cart === (int) $f['id_cart'] && (int) $sp->id_cart > 0) {
                $sp->delete();
            }
        }
        $db->execute(
            "DELETE e FROM {$p}kpy_coupon_precio_cesta e
             LEFT JOIN {$p}cart c ON c.id_cart = e.id_cart
             WHERE {$muerta}"
        );
        self::$cache = [];
    }

    /**
     * ¿El Special Price de hoy rebaja de verdad el precio de siempre? La misma condición que pone la
     * ficha para enseñar «Precio habitual» (`KpbCompra::montar`): si no, la línea va al precio de hoy.
     */
    public static function rebajaDeVerdad(int $idProducto, int $ipa, float $old): bool
    {
        if ($old < 0 || $old >= 1) {
            return false;
        }
        // Sin cesta: el precio del catálogo, el que ve la ficha (no el de esta cesta).
        $hoy = round((float) Product::getPriceStatic($idProducto, true, $ipa ?: null, 6), 2);
        $pvpr = round((float) Product::getPriceStatic($idProducto, true, $ipa ?: null, 6, null, false, false), 2);
        $habitual = round($pvpr * (1 - $old), 2);

        return $habitual > $hoy + 0.01 && $habitual <= $pvpr + 0.01;
    }

    /** Que lo que siga en esta petición lea los precios nuevos (PrestaShop guarda los suyos en memoria). */
    public static function olvidarPrecios(): void
    {
        if (method_exists('SpecificPrice', 'flushCache')) {
            SpecificPrice::flushCache();
        }
        Product::flushPriceCache();
        Cache::clean('SpecificPrice::*');
        Cache::clean('Product::getPriceStatic_*');
    }
}
