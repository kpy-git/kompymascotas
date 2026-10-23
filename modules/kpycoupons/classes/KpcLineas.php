<?php
/**
 * Las líneas de la cesta, cada promoción en la suya (Rafa, 16/10/2026: «no separa bien en el carro
 * las promos»).
 *
 * PrestaShop junta en una línea todas las unidades de un formato. Con el regalo con la compra, en
 * una misma línea pueden ir unidades de la tarjeta «12 kg + 3 kg gratis», unidades de la tarjeta
 * «12 kg» de siempre y unidades que son el regalo de otra línea. Aquí:
 *   - `trozos()` parte la línea presentada (hook `actionPresentCart`) en una fila por parte, cada una
 *     con su cantidad, su total y sus enlaces de «+», «−» y papelera, que cambian solo esa parte
 *     (llevan `kpy_regalo`, ver `KpcRegalo::antesDelCambio`). El regalo, una fila por saco que lo
 *     regala y sin nada que tocar: se quita con la papelera de su vale. Es lo que hace PrestaShop
 *     con sus propios regalos (`Cart::getProductsWithSeparatedGifts`). Los totales no cambian: solo
 *     cómo se pinta;
 *   - `ordenar()` pone cada regalo justo debajo de su saco (Rafa: «primero siempre el saco y debajo
 *     justo el regalo, sea lo que sea»);
 *   - `bloques()` dice debajo del nombre de cada fila su promoción (hook
 *     `displayCartExtraProductActions`): el regalo, la versión con regalo, el Special Price, la 2ª
 *     unidad o el cupón, con los colores de la ficha.
 */
class KpcLineas
{
    /** Las páginas que pintan las líneas una a una: la cesta y el pedido. En las demás, como están. */
    public const PAGINAS = ['cart', 'order'];

    /**
     * Las partes de una línea presentada, en este orden: las unidades con regalo, las de la tarjeta
     * de siempre (o, en la línea de un regalo, las que paga el cliente), las que se han quedado sin
     * promoción porque el cupo se acabó (a precio habitual) y las regaladas. Con una sola parte, la
     * misma línea, con su parte apuntada.
     *
     * @param object $linea la línea presentada (`CartProductLazyArray`)
     * @param array $d lo de esa línea según `KpcRegalo::partesDe()`
     *
     * @return object[]
     */
    public static function trozos($linea, array $d): array
    {
        $qty = (int) $linea['quantity'];
        $regaladas = min($qty, (int) $d['regaladas']);
        $pagadas = $qty - $regaladas;
        $con = min($pagadas, (int) $d['con']);
        $sinRegalo = $pagadas - $con;
        $partes = [];
        if ($con > 0) {
            $partes[] = ['con', $con];
        }
        if (!empty($d['habitual'])) {
            // La línea va al precio habitual: las que conservan el Special Price (por el vale) y, con
            // cupo, las que ya no caben en él.
            $conEspecial = ($d['sp'] ?? null) === null ? $sinRegalo : min($sinRegalo, max(0, (int) $d['sp']));
            if ($conEspecial > 0) {
                $partes[] = ['sin', $conEspecial];
            }
            if ($sinRegalo - $conEspecial > 0) {
                $partes[] = ['tope', $sinRegalo - $conEspecial];
            }
        } elseif ($sinRegalo > 0) {
            $partes[] = [$d['eleccion'] || $con > 0 ? 'sin' : 'pagado', $sinRegalo];
        }
        if ($regaladas > 0) {
            // Una fila por saco que lo regala (para ir debajo de cada uno); si no cuadra, una sola.
            $de = array_filter(array_map('intval', (array) ($d['de'] ?? [])));
            if ($de !== [] && array_sum($de) === $regaladas) {
                foreach ($de as $disparador => $uds) {
                    $partes[] = ['regalo', $uds, (int) $disparador];
                }
            } else {
                $partes[] = ['regalo', $regaladas, (int) (array_key_first($de) ?? 0)];
            }
        }
        if ($partes === []) {
            return [$linea];
        }
        // Partida, cada parte es una copia: la línea de PrestaShop se queda como está, que con ella
        // cuenta los artículos de la cesta («5 artículos»).
        $filas = count($partes) > 1 ? array_map(static fn () => clone $linea, $partes) : [$linea];
        foreach ($partes as $i => $p) {
            self::parte($filas[$i], $p[0], $p[1], $qty, $d);
            if ($p[0] === 'regalo') {
                self::poner($filas[$i], 'kpy_disparador', $p[2]);
            }
        }

        return $filas;
    }

    /**
     * Cada regalo, justo debajo del saco que lo regala (de su fila «con regalo»); los demás, en el
     * orden de la cesta. Un regalo cuyo saco no está a la vista va al final.
     *
     * @param object[] $filas
     *
     * @return object[]
     */
    public static function ordenar(array $filas): array
    {
        $regalos = [];
        $resto = [];
        foreach ($filas as $f) {
            if (($f['kpy_parte'] ?? '') === 'regalo' && !empty($f['kpy_disparador'])) {
                $regalos[(int) $f['kpy_disparador']][] = $f;
            } else {
                $resto[] = $f;
            }
        }
        if ($regalos === []) {
            return $filas;
        }
        $salida = [];
        foreach ($resto as $f) {
            $salida[] = $f;
            $clave = KpcAlcance::clave((int) $f['id_product'], (int) $f['id_product_attribute']);
            if (($f['kpy_parte'] ?? '') === 'con' && isset($regalos[$clave])) {
                array_push($salida, ...$regalos[$clave]);
                unset($regalos[$clave]);
            }
        }
        foreach ($regalos as $sueltos) {
            array_push($salida, ...$sueltos);
        }

        return $salida;
    }

    /** Deja una fila con su parte: cantidad, total, enlaces y, si va con regalo, el nombre de su tarjeta. */
    private static function parte($fila, string $parte, int $n, int $qty, array $d): void
    {
        self::poner($fila, 'kpy_parte', $parte);
        if ($n !== $qty) {
            $total = round((float) $fila['price_amount'] * $n, 2);
            self::poner($fila, 'quantity', $n);
            self::poner($fila, 'cart_quantity', $n);
            self::poner($fila, 'quantity_wanted', $n);
            self::poner($fila, 'total_wt', $total);
            self::poner($fila, 'total', self::euros($total));
        }
        if ($parte === 'regalo') {
            // Como los regalos de PrestaShop: sin cantidad ni papelera, y a 0 € con lo que vale tachado
            // (en el resumen del pago, «0,00 € 20,99 €»; el total cuadra con el vale «Regalo»).
            $vale = round((float) $fila['price_amount'] * $n, 2);
            self::poner($fila, 'kpy_is_gift', true);
            self::poner($fila, 'is_gift', true);
            self::poner($fila, 'regular_price', self::euros((float) $fila['price_amount']));
            self::poner($fila, 'has_discount', $vale > 0);
            self::poner($fila, 'price', self::euros(0.0));
            self::poner($fila, 'total', self::euros(0.0));

            return;
        }
        $marca = $parte === 'con' ? '&kpy_regalo=1' : (in_array($parte, ['sin', 'tope'], true) ? '&kpy_regalo=0' : '');
        $cambio = (string) $fila['update_quantity_url'];
        if ($marca !== '') {
            self::poner($fila, 'up_quantity_url', $fila['up_quantity_url'] . $marca);
            self::poner($fila, 'down_quantity_url', $fila['down_quantity_url'] . $marca);
            self::poner($fila, 'update_quantity_url', $cambio . $marca);
        }
        // La papelera de una parte quita solo sus unidades (la de PrestaShop borraría la línea entera).
        if ($n !== $qty) {
            self::poner($fila, 'remove_from_cart_url', $cambio . '&op=down&qty=' . $n . $marca);
        }
        if ($parte === 'con' && $d['corto'] !== '') {
            // Con el nombre de su tarjeta en la ficha: «Saco de 12 Kg + 3 kg gratis».
            $atributos = (array) $fila['attributes'];
            $ultimo = array_key_last($atributos);
            if ($ultimo !== null) {
                $atributos[$ultimo] = $atributos[$ultimo] . ' + ' . $d['corto'];
                self::poner($fila, 'attributes', $atributos);
            }
            $corto = trim((string) $fila['attributes_small']);
            self::poner($fila, 'attributes_small', ($corto !== '' ? $corto . ' + ' : '') . $d['corto']);
        }
    }

    /**
     * Lo que dice una fila de la cesta debajo de su nombre: un bloque por promoción, cada uno con su
     * chip y su color. Vacío si no lleva ninguna.
     *
     * @param object|array $linea la fila presentada (con `kpy_parte` si es una parte de su línea)
     *
     * @return array<int, array{tipo: string, icono: string, chip: string, fuerte: string, texto: string,
     *               precio: string, tachado: string, sub: string}>
     */
    public static function bloques($linea, Cart $cart, int $idTienda, Kpycoupons $modulo): array
    {
        $t = static fn (string $s, array $r = []): string => $modulo->t($s, $r);
        $bloque = static fn (string $tipo, string $chip, array $resto = []): array => $resto
            + ['tipo' => $tipo, 'icono' => '', 'chip' => $chip, 'fuerte' => '', 'texto' => '', 'precio' => '', 'tachado' => '', 'sub' => ''];
        $clave = KpcAlcance::clave((int) $linea['id_product'], (int) $linea['id_product_attribute']);
        $n = (int) $linea['quantity'];
        $parte = (string) ($linea['kpy_parte'] ?? '');
        $d = KpcRegalo::partesDe($cart)[$clave] ?? null;

        if ($d !== null && $parte === 'regalo') {
            $vale = round($d['precio'] * $n, 2);
            $disparador = (int) ($linea['kpy_disparador'] ?? 0);
            $saco = $d['sacos'][$disparador] ?? implode(' y ', $d['con_saco']);

            return [$bloque('regalo', $t('Regalo'), [
                'icono' => 'regalo',
                'fuerte' => ($n > 1 ? $n . ' × ' : '') . $d['formato'],
                'precio' => self::euros(0.0),
                'tachado' => $vale > 0 ? self::euros($vale) : '',
                'sub' => $t('Con tu %saco%', ['%saco%' => $saco]),
            ])];
        }
        if ($d !== null && $parte === 'con') {
            return [$bloque('regalo', $d['clase'] === 'kilos' ? $t('Kilos gratis') : $t('Con regalo'), [
                'icono' => 'regalo',
                'texto' => $t('Lleva de regalo %regalos%', ['%regalos%' => implode(' y ', $d['regalos'])]),
                'sub' => $d['habitual'] ? $t('A precio habitual por llevar el regalo') : '',
            ])];
        }
        if ($d !== null && $parte === 'sin' && $d['habitual'] && $d['precio'] > $d['hoy'] + 0.005) {
            // La tarjeta de siempre en una línea que va al precio habitual (por el regalo o por el cupo).
            $diferencia = round($d['precio'] - $d['hoy'], 2);
            $etiqueta = (string) ($d['etiqueta'] ?: 'Special Price');

            return [$bloque((string) ($d['etiqueta_clase'] ?: 'especial'), $etiqueta, [
                'icono' => 'especial',
                'texto' => $t('Con su %promo%, %precio% la ud.', ['%promo%' => $etiqueta, '%precio%' => self::euros($d['hoy'])]),
                'sub' => $n > 1
                    ? $t('La diferencia (%n% × %dif%) va en el vale «%promo%»', ['%n%' => $n, '%dif%' => self::euros($diferencia), '%promo%' => $etiqueta])
                    : $t('La diferencia (%dif%) va en el vale «%promo%»', ['%dif%' => self::euros($diferencia), '%promo%' => $etiqueta]),
            ])];
        }
        if ($d !== null && $parte === 'tope') {
            // Las que ya no caben en el cupo: a precio habitual, y se dice por qué.
            return [$bloque('tope', (string) ($d['etiqueta'] ?: 'Special Price'), [
                'icono' => 'tope',
                'texto' => $t('Se han agotado las unidades en promoción: estas van a precio habitual'),
                'sub' => $t('Precio habitual %precio% la ud.', ['%precio%' => self::euros($d['precio'])]),
            ])];
        }

        // Las demás: su Special Price, su 2ª unidad o su cupón, con las unidades que paga el cliente.
        $promos = self::promos($cart, $idTienda)[$clave] ?? [];
        $precio = (float) $linea['price_amount'];
        $bloques = [];
        if (isset($promos['habitual']) && $promos['habitual'] > $precio + 0.01) {
            $etiqueta = (string) ($promos['etiqueta'] ?? 'Special Price');
            $bloques[] = $bloque((string) ($promos['clase'] ?? 'especial'), $etiqueta, [
                'icono' => 'especial',
                'texto' => $t('Ahorras %ahorro% más', ['%ahorro%' => self::euros(round(($promos['habitual'] - $precio) * $n, 2))]),
                'sub' => $n > 1
                    ? $t('Precio habitual %precio% la ud.', ['%precio%' => self::euros($promos['habitual'])])
                    : $t('Precio habitual %precio%', ['%precio%' => self::euros($promos['habitual'])]),
            ]);
        }
        if (isset($promos['segunda'])) {
            $pct = KpcElegibles::pct($promos['segunda']['pct']);
            $pares = intdiv($n, 2);
            $tope = $promos['segunda']['n'] ?? null;
            if ($tope !== null && $tope < $pares) {
                // Con cupo: solo las parejas que han cabido.
                $bloques[] = $bloque('segunda', $t('2ª ud. −%pct% %', ['%pct%' => $pct]), $tope > 0 ? [
                    'texto' => $t('Te ahorras %ahorro% en %n% ud.', ['%ahorro%' => self::euros(round($tope * $precio * $promos['segunda']['pct'] / 100, 2)), '%n%' => $tope]),
                    'sub' => $t('Las demás van sin descuento: se ha agotado el cupo de la promoción'),
                ] : [
                    'texto' => $t('Se ha agotado el cupo de la promoción: estas unidades van sin descuento'),
                ]);
            } else {
                $bloques[] = $bloque('segunda', $t('2ª ud. −%pct% %', ['%pct%' => $pct]), $pares > 0 ? [
                    'texto' => $t('Te ahorras %ahorro%', ['%ahorro%' => self::euros(round($pares * $precio * $promos['segunda']['pct'] / 100, 2))]),
                    'sub' => $t('Va en el vale «2ª unidad al −%pct% %»', ['%pct%' => $pct]),
                ] : [
                    'texto' => $t('Con otra, la 2ª va al −%pct% %', ['%pct%' => $pct]),
                ]);
            }
        }
        if (isset($promos['cupon'])) {
            $conCupon = $promos['cupon']['n'] ?? null;
            $uds = $conCupon !== null ? min($n, $conCupon) : $n;
            $bloques[] = $bloque('cupon', $t('Cupón −%pct% %', ['%pct%' => KpcElegibles::pct($promos['cupon']['pct'])]), $uds < $n ? [
                'texto' => $uds > 0
                    ? $t('Te ahorras %ahorro% en %n% ud.', ['%ahorro%' => self::euros(round($precio * $uds * $promos['cupon']['pct'] / 100, 2)), '%n%' => $uds])
                    : $t('Se ha agotado el cupo del cupón: estas unidades van sin descuento'),
                'sub' => $uds > 0 ? $t('Con el cupón %codigo% · las demás, sin él: se ha agotado su cupo', ['%codigo%' => $promos['cupon']['codigo']]) : '',
            ] : [
                'texto' => $t('Te ahorras %ahorro%', ['%ahorro%' => self::euros(round($precio * $n * $promos['cupon']['pct'] / 100, 2))]),
                'sub' => $t('Con el cupón %codigo%', ['%codigo%' => $promos['cupon']['codigo']]),
            ]);
        }

        return $bloques;
    }

    /**
     * Las promociones de cada línea de la cesta: el precio habitual si tiene Special Price (como
     * `KpcResumen::ahorroEspecial`) y cómo se llama («Liquidación»), la 2ª unidad que le llega y el
     * cupón puesto que le llega (por el reparto, como los aplica la cesta); con cupo, las unidades
     * de la línea que han cabido (`n`). Una vez por estado de la cesta.
     *
     * @return array<int, array{habitual?: float, etiqueta?: string, clase?: string, segunda?: array{pct: float, n: ?int}, cupon?: array{pct: float, codigo: string, n: ?int}}>
     */
    private static function promos(Cart $cart, int $idTienda): array
    {
        static $cache = [];
        $productos = $cart->getProducts();
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        $firma = (int) $cart->id . '|' . md5(json_encode([
            array_map(static fn ($l) => [(int) $l['id_product_attribute'], (int) $l['cart_quantity'], (float) $l['price_wt']], $productos),
            $puestas,
        ]));
        if (isset($cache[$firma])) {
            return $cache[$firma];
        }
        $salida = [];
        $ipas = array_values(array_filter(array_map(static fn ($l) => (int) ($l['id_product_attribute'] ?? 0), $productos)));
        if ($ipas !== [] && KpcCupon::hayTablaEspeciales()) {
            $viejo = [];
            foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_product_attribute AS ipa, old_discount FROM ' . _DB_PREFIX_ . 'kpy_special_price
                 WHERE id_product_attribute IN (' . implode(',', $ipas) . ') AND NOW() BETWEEN date_from AND expire'
            ) ?: [] as $f) {
                $viejo[(int) $f['ipa']] = (float) $f['old_discount'];
            }
            $especiales = KpcCupoCesta::especialesDe($cart);
            foreach ($productos as $l) {
                $ipa = (int) ($l['id_product_attribute'] ?? 0);
                if (isset($viejo[$ipa])) {
                    $pvpr = (float) Product::getPriceStatic((int) $l['id_product'], true, $ipa, 6, null, false, false);
                    $clave = KpcAlcance::clave((int) $l['id_product'], $ipa);
                    $salida[$clave]['habitual'] = round($pvpr * (1 - $viejo[$ipa]), 2);
                    $salida[$clave]['etiqueta'] = (string) ($especiales[$clave]['etiqueta'] ?? 'Special Price');
                    $salida[$clave]['clase'] = (string) ($especiales[$clave]['clase'] ?? 'especial');
                }
            }
        }
        $reservas = KpcCupo::reservasDe((int) $cart->id);
        $reparto = KpcReparto::deClaves($idTienda, KpcReparto::clavesDe($productos));
        foreach (KpcCupon::enVigor($idTienda) as $c) {
            $id = (int) $c['id_kpy_coupon'];
            $suyos = $reparto[$id] ?? [];
            $tipo = (string) ($c['tipo'] ?? 'cupon');
            if ($suyos === []) {
                continue;
            }
            $conCupo = (int) ($c['cupo'] ?? 0) > 0;
            if ($tipo === 'segunda_unidad') {
                foreach ($suyos as $k) {
                    $salida[(int) $k]['segunda'] = ['pct' => (float) $c['porcentaje'], 'n' => $conCupo ? (int) ($reservas[$id][(int) $k] ?? 0) : null];
                }
            } elseif ($tipo === 'cupon' && KpcCarrito::cuponPuesto($cart, $c, $puestas)) {
                foreach ($suyos as $k) {
                    $salida[(int) $k]['cupon'] = ['pct' => (float) $c['porcentaje'], 'codigo' => (string) $c['codigo'], 'n' => $conCupo ? (int) ($reservas[$id][(int) $k] ?? 0) : null];
                }
            }
        }

        return $cache[$firma] = $salida;
    }

    /** Pone una clave de la fila presentada, también si PrestaShop la calcula al pedirla. */
    private static function poner($fila, string $clave, $valor): void
    {
        $fila->offsetSet($clave, $valor, true);
    }

    private static function euros(float $v): string
    {
        $contexto = Context::getContext();
        $locale = $contexto->getCurrentLocale();

        return $locale ? $locale->formatPrice($v, $contexto->currency->iso_code) : number_format($v, 2, ',', '.') . ' €';
    }
}
