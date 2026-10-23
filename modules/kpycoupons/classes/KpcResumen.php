<?php
/**
 * Lo que la cesta tiene que contar al cliente sobre sus promociones: qué le han aplicado, cuánto
 * se está ahorrando y qué le falta para ahorrar un poco más.
 *
 * Es el otro lado de la ficha: si en la ficha se le ha prometido «2ª unidad al 30 %», en la cesta
 * tiene que verlo aplicado con el mismo nombre y el mismo importe, o deja de fiarse.
 */
class KpcResumen
{
    /**
     * @return array{vales: array<int, array{tipo: string, texto: string, codigo: string}>,
     *               lineas: array<int, array{clase: string, texto: string, importe: string}>,
     *               animos: array<int, array{texto: string, sube: string}>}
     *         vales: los vales de PrestaShop que son nuestros (por id de regla), para dibujarlos;
     *         lineas: lo que no es un vale pero se ahorra (Special Price);
     *         animos: lo que falta para ahorrar más, con la línea a la que sumar una unidad
     */
    public static function deLaCesta(Cart $cart, int $idTienda, Kpycoupons $modulo): array
    {
        $vales = [];
        $lineas = [];
        $animos = [];
        if (!$cart || !$cart->id) {
            return ['vales' => $vales, 'lineas' => $lineas, 'animos' => $animos];
        }

        $locale = Context::getContext()->getCurrentLocale();
        $moneda = Context::getContext()->currency->iso_code;
        $euros = static fn (float $v): string => $locale
            ? $locale->formatPrice($v, $moneda)
            : number_format($v, 2, ',', '.') . ' €';
        $t = static fn (string $texto, array $reemplazos = []) => $modulo->t($texto, $reemplazos);

        $productos = $cart->getProducts();
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));

        // 1. Special Price: ya viene en el precio de cada línea, pero conviene decirlo.
        $ahorroEspecial = self::ahorroEspecial($productos, KpcRegalo::previasDe((int) $cart->id));
        if ($ahorroEspecial['importe'] > 0.009) {
            $lineas[] = [
                'clase' => 'especial',
                'tipo' => 'special_price',
                'n' => $ahorroEspecial['productos'],
                'texto' => $ahorroEspecial['productos'] === 1
                    ? $t('Special Price en 1 producto')
                    : $t('Special Price en %n% productos', ['%n%' => $ahorroEspecial['productos']]),
                'importe' => '−' . $euros($ahorroEspecial['importe']),
            ];
        }

        // 2. Cupones y «2ª unidad» puestos de verdad en esta cesta. El importe lo pinta PrestaShop en
        //    su lista de vales; aquí solo se dice qué es cada uno, para dibujarlo con su color. Los
        //    formatos, los del reparto: los mismos que usa la cesta para aplicarlos.
        $reparto = KpcReparto::deClaves($idTienda, KpcReparto::clavesDe($productos));
        $reglasCupo = KpcCupoCesta::reglasCupon($cart);
        foreach (KpcCupon::enVigor($idTienda) as $datos) {
            if (KpcElegibles::esPrecio($datos) || KpcElegibles::esRegalo($datos)) {
                continue; // El Special Price ya va en el precio de la línea (arriba); el regalo, abajo.
            }
            $pct = KpcElegibles::pct((float) $datos['porcentaje']);
            $id = (int) $datos['id_kpy_coupon'];
            $conCupo = KpcCupo::tieneCupo($datos);

            if (($datos['tipo'] ?? 'cupon') === 'segunda_unidad') {
                $claves = $reparto[$id] ?? [];
                if ($claves === []) {
                    continue;
                }
                $idRegla = (int) CartRule::getIdByCode(KpcSegunda::codigo($cart, $id));
                if ($idRegla > 0 && in_array($idRegla, $puestas, true)) {
                    $vales[$idRegla] = [
                        'tipo' => 'segunda',
                        'texto' => $t('2ª unidad al −%pct% %', ['%pct%' => $pct]),
                        'codigo' => '',
                        'id_kpy_coupon' => $id,
                        'pct' => (float) $datos['porcentaje'],
                    ];
                }
                // El empujón, solo si queda cupo para una pareja más.
                if ($conCupo && KpcCupo::quedanPara($id, (int) $cart->id) === 0) {
                    continue;
                }
                foreach (self::faltaUna($productos, $claves, (float) $datos['porcentaje'], KpcRegalo::previasDe((int) $cart->id)) as $falta) {
                    $animos[] = [
                        'texto' => $falta['cantidad'] > 1
                            ? $t('Añade 1 ud. más de %producto% y te ahorras %ahorro%', ['%producto%' => $falta['nombre'], '%ahorro%' => $euros($falta['ahorro'])])
                            : $t('Añade 1 ud. de %producto% y te ahorras %ahorro%', ['%producto%' => $falta['nombre'], '%ahorro%' => $euros($falta['ahorro'])]),
                        'sube' => $falta['sube'],
                    ];
                }
                continue;
            }

            $vale = [
                'tipo' => 'cupon',
                'texto' => $t('Cupón %codigo% (−%pct% %)', ['%codigo%' => (string) $datos['codigo'], '%pct%' => $pct]),
                'codigo' => (string) $datos['codigo'],
                'id_kpy_coupon' => $id,
                'pct' => (float) $datos['porcentaje'],
            ];
            if (in_array((int) $datos['id_cart_rule'], $puestas, true)) {
                $vales[(int) $datos['id_cart_rule']] = $vale;
            }
            // Con cupo, el cupón son sus vales por producto: cada uno con su nombre (lo que descuenta).
            foreach ($reglasCupo as $idRegla => $idPromo) {
                if ($idPromo === $id) {
                    $vales[$idRegla] = ['texto' => ''] + $vale;
                }
            }
        }

        // 2 bis. Lo que no ha cabido en el cupo de una promoción, dicho: el cliente tiene que
        //        entender por qué parte de su línea va a precio habitual o sin descuento.
        foreach (self::avisosDeCupo($cart, $idTienda, $t) as $texto) {
            $lineas[] = ['clase' => 'tope', 'tipo' => 'tope', 'n' => 0, 'texto' => $texto, 'importe' => ''];
        }

        // 3. Regalos: la regla de cada producto regalado (con su nombre: «Regalo: 1 × Boske… · Saco de
        //    3 Kg»), y si algún regalo no ha podido entrar, por qué (agotado, tope de la campaña).
        //    Y el Special Price que devuelve a las unidades de la tarjeta de siempre de una línea con
        //    regalo («Special Price: 1 × … · Saco de 12 Kg»), en crema.
        foreach ($cart->getCartRules() as $cr) {
            $idRegla = (int) ($cr['id_cart_rule'] ?? 0);
            if (!in_array($idRegla, $puestas, true)) {
                continue;
            }
            if (KpcRegalo::esSuRegla((string) ($cr['code'] ?? ''), (int) $cart->id)) {
                $vales[$idRegla] = ['tipo' => 'regalo', 'texto' => (string) ($cr['name'] ?? $t('Regalo')), 'codigo' => '', 'id_kpy_coupon' => 0, 'pct' => 0.0];
            } elseif (KpcRegalo::esSuReglaEspecial((string) ($cr['code'] ?? ''), (int) $cart->id)) {
                $vales[$idRegla] = ['tipo' => 'especial', 'texto' => (string) ($cr['name'] ?? $t('Special Price')), 'codigo' => '', 'id_kpy_coupon' => 0, 'pct' => 0.0];
            }
        }
        foreach (self::avisosDeRegalo($cart, $idTienda, $t) as $texto) {
            $lineas[] = ['clase' => 'regalo', 'tipo' => 'regalo', 'n' => 0, 'texto' => $texto, 'importe' => ''];
        }

        $salida = ['vales' => $vales, 'lineas' => $lineas, 'animos' => $animos];
        // Otro módulo puede repasar los textos (la landing de promociones, durante una campaña:
        // «Cupón Black −10 % · EUKA10»). Si falla, la cesta sigue con los de siempre.
        try {
            Hook::exec('actionKpyCouponsResumen', ['cart' => $cart, 'resumen' => &$salida]);
        } catch (Throwable $e) {
            PrestaShopLogger::addLog('kpycoupons, al avisar del resumen de la cesta (actionKpyCouponsResumen): ' . $e->getMessage(), 2, null, 'KpcResumen', 0, true);
        }

        return $salida;
    }

    /**
     * Los regalos que no han podido entrar en esta cesta, dichos para el cliente: el mismo plan que
     * decide los regalos (`KpcRegalo::plan`), sin tocar nada.
     *
     * @return string[]
     */
    private static function avisosDeRegalo(Cart $cart, int $idTienda, callable $t): array
    {
        $lineas = KpcRegalo::lineasDe($cart->getProducts());
        if ($lineas === []) {
            return [];
        }
        $elegidas = KpcRegalo::elegidasDe((int) $cart->id);
        if ($elegidas === []) {
            return [];
        }
        $parejas = KpcRegalo::deClaves($idTienda, array_keys($elegidas));
        if ($parejas === []) {
            return [];
        }
        $promos = [];
        foreach ($parejas as $suyas) {
            foreach ($suyas as $p) {
                $promos[(int) $p['id_kpy_coupon']] = true;
            }
        }
        $plan = KpcRegalo::plan(
            $lineas,
            KpcRegalo::previasDe((int) $cart->id),
            $parejas,
            static fn (int $id, int $ipa, int $necesita): bool => KpcRegalo::disponible($id, $ipa, $necesita, $idTienda),
            KpcRegalo::usados(array_keys($promos), (int) $cart->id),
            $elegidas
        );
        if ($plan['avisos'] === []) {
            return [];
        }
        $articulos = [];
        foreach ($plan['avisos'] as $a) {
            if (!empty($a['regalo'])) {
                $articulos[] = $a['regalo'];
            }
        }
        $datos = KpcRegalo::datos($articulos, $idTienda);
        $salida = [];
        foreach ($plan['avisos'] as $a) {
            $regalo = !empty($a['regalo']) ? ($datos[(int) $a['regalo']['id'] . '-' . (int) $a['regalo']['ipa']] ?? null) : null;
            $nombre = $regalo ? KpcRegalo::nombreCorto($regalo['nombre'], $regalo['formato']) : $t('el regalo');
            $texto = ($a['motivo'] ?? '') === 'tope'
                ? $t('Se han acabado los regalos de esta promoción (o están en otras cestas): tu compra sigue igual.')
                : $t('El regalo (%regalo%) está agotado: tu compra sigue igual.', ['%regalo%' => $nombre]);
            $salida[$texto] = $texto;
        }

        return array_values($salida);
    }

    /**
     * Lo que no ha cabido en el cupo de una promoción en esta cesta, dicho para el cliente: las
     * unidades de un Special Price (o Liquidación) que van a precio habitual, las de un cupón o de
     * una 2ª unidad que van sin descuento. Solo lee lo que dejó el repaso de la cesta (las reservas).
     *
     * @return string[]
     */
    private static function avisosDeCupo(Cart $cart, int $idTienda, callable $t): array
    {
        if (!KpcCupo::hayCupos($idTienda)) {
            return [];
        }
        $salida = [];
        $previas = KpcRegalo::previasDe((int) $cart->id);
        $partes = KpcRegalo::partesDe($cart);
        foreach ($partes as $d) {
            if (empty($d['habitual']) || ($d['sp'] ?? null) === null) {
                continue;
            }
            $fuera = (int) $d['pagadas'] - (int) $d['con'] - (int) $d['sp'];
            if ($fuera > 0) {
                $texto = $t('%promo%: solo quedaban %n% ud. en promoción; las otras %m% van a precio habitual.', ['%promo%' => $d['etiqueta'], '%n%' => (int) $d['sp'], '%m%' => $fuera]);
                $salida[$texto] = $texto;
            }
        }
        $reservas = KpcCupo::reservasDe((int) $cart->id);
        $reparto = KpcReparto::deClaves($idTienda, KpcReparto::clavesDe($cart->getProducts()));
        $puestas = array_map('intval', array_column($cart->getCartRules(), 'id_cart_rule'));
        foreach (KpcCupon::enVigor($idTienda) as $c) {
            $id = (int) $c['id_kpy_coupon'];
            $tipo = (string) ($c['tipo'] ?? 'cupon');
            if (!KpcCupo::tieneCupo($c) || !in_array($tipo, ['cupon', 'segunda_unidad'], true) || empty($reparto[$id])) {
                continue;
            }
            if ($tipo === 'cupon' && !KpcCarrito::cuponPuesto($cart, $c, $puestas)) {
                continue;
            }
            $pide = 0;
            foreach ($cart->getProducts() as $l) {
                $clave = KpcAlcance::clave((int) $l['id_product'], (int) ($l['id_product_attribute'] ?? 0));
                if (in_array($clave, $reparto[$id], true)) {
                    $pagadas = max(0, (int) ($l['cart_quantity'] ?? 0) - (int) ($previas[$clave] ?? 0));
                    $pide += $tipo === 'segunda_unidad' ? intdiv($pagadas, 2) : $pagadas;
                }
            }
            $caben = array_sum($reservas[$id] ?? []);
            if ($pide > $caben) {
                $texto = $tipo === 'cupon'
                    ? $t('Cupón %codigo%: solo quedaban %n% ud. con descuento; las otras %m% van sin él.', ['%codigo%' => (string) $c['codigo'], '%n%' => $caben, '%m%' => $pide - $caben])
                    : $t('2ª unidad: solo quedaban %n% ud. rebajadas; las otras %m% van sin descuento.', ['%n%' => $caben, '%m%' => $pide - $caben]);
                $salida[$texto] = $texto;
            }
        }

        return array_values($salida);
    }

    /**
     * Lo que se ahorra por los Special Price de la cesta, contra el precio habitual de cada formato.
     *
     * @return array{importe: float, productos: int}
     */
    private static function ahorroEspecial(array $productos, array $regaladas = []): array
    {
        if (!KpcCupon::hayTablaEspeciales() || $productos === []) {
            return ['importe' => 0.0, 'productos' => 0];
        }
        $ipas = array_values(array_filter(array_map(static fn ($p) => (int) ($p['id_product_attribute'] ?? 0), $productos)));
        if ($ipas === []) {
            return ['importe' => 0.0, 'productos' => 0];
        }
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_product_attribute AS ipa, old_discount FROM ' . _DB_PREFIX_ . 'kpy_special_price
             WHERE id_product_attribute IN (' . implode(',', $ipas) . ') AND NOW() BETWEEN date_from AND expire'
        ) ?: [];
        if ($filas === []) {
            return ['importe' => 0.0, 'productos' => 0];
        }
        $viejo = [];
        foreach ($filas as $f) {
            $viejo[(int) $f['ipa']] = (float) $f['old_discount'];
        }

        $importe = 0.0;
        $cuantos = 0;
        foreach ($productos as $linea) {
            $ipa = (int) ($linea['id_product_attribute'] ?? 0);
            if (!isset($viejo[$ipa])) {
                continue;
            }
            $precio = (float) ($linea['price_wt'] ?? 0);
            $pvpr = (float) Product::getPriceStatic((int) $linea['id_product'], true, $ipa, 6, null, false, false);
            $habitual = round($pvpr * (1 - $viejo[$ipa]), 2);
            if ($habitual > $precio + 0.01) {
                // Las unidades regaladas no cuentan: no las paga, no se ahorra nada en ellas.
                $uds = (int) ($linea['cart_quantity'] ?? 1) - (int) ($regaladas[KpcAlcance::clave((int) $linea['id_product'], $ipa)] ?? 0);
                if ($uds <= 0) {
                    continue;
                }
                $importe += ($habitual - $precio) * $uds;
                ++$cuantos;
            }
        }

        return ['importe' => round($importe, 2), 'productos' => $cuantos];
    }

    /**
     * Líneas con un número impar de unidades que paga el cliente: con una más entra la «2ª unidad».
     * Es el empujón que más vende, y hay que decirlo con el nombre del producto y el dinero exacto.
     * Las unidades regaladas no cuentan (un 3 kg de regalo más dos pagados ya hacen su par).
     *
     * @param array<int, int> $regaladas clave => unidades de la línea que son regalo
     *
     * @return array<int, array{nombre: string, ahorro: float, cantidad: int, sube: string}>
     *         sube: «producto-formato» de la línea, para sumarle la unidad de un toque
     */
    private static function faltaUna(array $productos, array $claves, float $pct, array $regaladas = []): array
    {
        $salida = [];
        foreach ($productos as $linea) {
            $ipa = (int) ($linea['id_product_attribute'] ?? 0);
            $clave = KpcAlcance::clave((int) ($linea['id_product'] ?? 0), $ipa);
            $cantidad = (int) ($linea['cart_quantity'] ?? 0) - (int) ($regaladas[$clave] ?? 0);
            if (!in_array($clave, $claves, true) || $cantidad <= 0 || $cantidad % 2 === 0) {
                continue;
            }
            // El nombre, corto: en la cesta el aviso tiene que leerse de un vistazo.
            $nombre = trim((string) ($linea['name'] ?? ''));
            if (mb_strlen($nombre) > 42) {
                $nombre = mb_substr($nombre, 0, 40) . '…';
            }
            $formato = trim(preg_replace('/^[^:]+:s*/u', '', (string) ($linea['attributes'] ?? '')) ?? '');
            $salida[] = [
                'nombre' => $formato !== '' ? $nombre . ' · ' . $formato : $nombre,
                'ahorro' => round((float) ($linea['price_wt'] ?? 0) * $pct / 100, 2),
                'cantidad' => $cantidad,
                'sube' => (int) ($linea['id_product'] ?? 0) . '-' . $ipa,
            ];
        }

        return $salida;
    }
}
