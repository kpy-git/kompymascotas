<?php
/**
 * Los ficheros que Google Merchant necesita, sacados de las promociones que ya están en la tienda.
 *
 * La idea: la promoción se crea **una vez** (en la pantalla del módulo o con el fichero del
 * registro) y de ahí salen solos los tres ficheros de Merchant. No hay que volver a escribir nada
 * ni copiar precios a mano, y la tienda y Google no se pueden contradecir porque leen lo mismo.
 *
 *   promociones.txt     las promos de carrito (cupón y 2ª unidad). Marketing → Promociones.
 *   precio-oferta.txt   el Special Price: `price` y `sale_price` con sus fechas. Fuente
 *                       complementaria de productos.
 *   productos-promo.txt qué promoción de carrito lleva cada artículo (`promotion_id`).
 *
 * Los tres se pueden descargar desde la pantalla del módulo o, mejor, dejar que Merchant los
 * recoja solo de su dirección (ver `KpcMerchant::direcciones()`): así no hay que subir nada a mano.
 *
 * La regla de Google que manda: el Special Price va como `sale_price` (el precio de la ficha ya
 * está rebajado) y NUNCA como promoción; el cupón y la 2ª unidad van como promoción, porque su
 * descuento se aplica en el carrito. Mezclarlo es motivo de rechazo.
 */
class KpcMerchant
{
    public const FICHEROS = ['promociones', 'precios', 'etiquetas'];

    /** Nombre con el que se descarga cada uno, igual que los de `catalog-ops`. */
    public const NOMBRES = [
        'promociones' => 'promociones.txt',
        'precios' => 'precio-oferta.txt',
        'etiquetas' => 'productos-promo.txt',
    ];

    private const COLUMNAS_PROMO = [
        'promotion_id', 'product_applicability', 'offer_type', 'long_title', 'promotion_effective_dates',
        'redemption_channel', 'promotion_destination', 'generic_redemption_code', 'coupon_value_type',
        'percent_off', 'money_off_amount', 'minimum_purchase_amount', 'redemption_restriction',
        'free_gift_description', 'brand', 'fine_print', 'buy_this_quantity', 'get_this_quantity_discounted',
    ];

    /** El contenido de un fichero, ya en texto separado por tabuladores. */
    public static function fichero(string $cual, int $idTienda): string
    {
        switch ($cual) {
            case 'promociones':
                return self::promociones($idTienda);
            case 'precios':
                return self::precios($idTienda);
            case 'etiquetas':
                return self::etiquetas($idTienda);
            default:
                return '';
        }
    }

    /**
     * Las promos de carrito: cupón («código de descuento») y 2ª unidad («compra 1, llévate otro
     * al X %»). El Special Price no sale aquí a propósito.
     *
     * Van las que están en marcha **y las programadas**, con sus fechas: Google revisa cada
     * promoción antes de enseñarla, así que conviene que la tenga unos días antes.
     */
    private static function promociones(int $idTienda): string
    {
        $lineas = [implode("\t", self::COLUMNAS_PROMO)];
        $reparto = KpcReparto::guardados($idTienda);
        foreach (KpcReparto::cupones($idTienda) as $c) {
            $id = (int) $c['id_kpy_coupon'];
            // Sin artículos no hay nada que anunciar; hay cupones que no van a Google; y un Special
            // Price nunca va como promoción (va en `precio-oferta.txt`: Google lo rechazaría).
            if (($reparto[$id] ?? []) === [] || !(int) ($c['en_merchant'] ?? 1) || KpcElegibles::esPrecio($c)) {
                continue;
            }
            // Un regalo, solo si le queda algún artículo sin Special Price (ver `anunciable()`).
            if (KpcElegibles::esRegalo($c) && array_filter($reparto[$id], static fn ($idProducto, $articulo): bool => self::anunciable($c, (int) $idProducto, (int) $articulo, $idTienda), ARRAY_FILTER_USE_BOTH) === []) {
                continue;
            }
            $esSegunda = ($c['tipo'] ?? 'cupon') === 'segunda_unidad';
            $esRegalo = ($c['tipo'] ?? 'cupon') === 'regalo';
            $codigo = $esSegunda || $esRegalo ? '' : (string) $c['codigo'];
            $fila = [
                'promotion_id' => self::idPromo($c),
                'product_applicability' => 'specific_products',
                'offer_type' => $codigo !== '' ? 'generic_code' : 'no_code',
                'long_title' => self::titulo($c),
                'promotion_effective_dates' => self::fechas($c),
                'redemption_channel' => 'online',
                // Solo Shopping: «Free_listings» es solo para Estados Unidos (y varios destinos irían en
                // columnas separadas, no en una celda con comas).
                'promotion_destination' => 'Shopping_ads',
                'generic_redemption_code' => $codigo,
                // El regalo es «free_gift» para Google, con su descripción (obligatoria).
                'coupon_value_type' => $esRegalo ? 'free_gift' : ($esSegunda ? 'buy_m_get_n_percent_off' : 'percent_off'),
                'percent_off' => $esRegalo ? '' : (string) (int) round((float) $c['porcentaje']),
                'money_off_amount' => '',
                'minimum_purchase_amount' => '',
                'redemption_restriction' => '',
                'free_gift_description' => $esRegalo ? self::descripcionRegalo($c, $idTienda) : '',
                'brand' => '',
                'fine_print' => '',
                'buy_this_quantity' => $esSegunda ? '1' : '',
                // Google la llama así; con otro nombre rechaza la promoción («falta un atributo»).
                'get_this_quantity_discounted' => $esSegunda ? '1' : '',
            ];
            $lineas[] = implode("\t", array_map(
                static fn (string $col): string => self::limpio($fila[$col]),
                self::COLUMNAS_PROMO
            ));
        }

        return implode("\n", $lineas) . "\n";
    }

    /**
     * El Special Price: `price` es el **precio habitual** (el mínimo de los últimos 30 días, que es
     * lo que pide la normativa europea) y `sale_price` el que se paga hoy. Los dos salen de la
     * tienda al generar el fichero, así que no hay que copiarlos de ninguna parte.
     */
    private static function precios(int $idTienda): string
    {
        $lineas = ["id\tprice\tsale_price\tsale_price_effective_date"];
        foreach (self::especialesVivas($idTienda) as $f) {
            $lineas[] = implode("\t", [
                $f['clave'],
                number_format($f['habitual'], 2, '.', '') . ' EUR',
                number_format($f['hoy'], 2, '.', '') . ' EUR',
                self::iso((string) $f['date_from']) . '/' . self::iso((string) $f['expire']),
            ]);
        }

        return implode("\n", $lineas) . "\n";
    }

    /**
     * Qué promoción de carrito lleva cada artículo (`promotion_id`), que es lo que une el artículo
     * con su promoción en Merchant.
     *
     * Sin `custom_label_4`: en el feed de Julián las cinco etiquetas personalizadas ya están usadas
     * (la 4 es el margen: MARGEN_NORMAL, MARGEN_ALTO…) y este fichero la habría pisado. El Special
     * Price no lleva `promotion_id` (no es una promoción de Merchant), así que aquí no sale.
     */
    private static function etiquetas(int $idTienda): string
    {
        $lineas = ["id\tpromotion_id"];
        $porArticulo = [];
        $reparto = KpcReparto::vigentes($idTienda);
        foreach (KpcCupon::enVigor($idTienda) as $c) {
            if (!(int) ($c['en_merchant'] ?? 1) || KpcElegibles::esPrecio($c)) {
                continue;
            }
            $idPromo = self::idPromo($c);
            foreach ($reparto[(int) $c['id_kpy_coupon']] ?? [] as $articulo => $idProducto) {
                if (!self::anunciable($c, (int) $idProducto, (int) $articulo, $idTienda)) {
                    continue;
                }
                $porArticulo[self::idFeed((int) $idProducto, max(0, (int) $articulo))][] = $idPromo;
            }
        }

        ksort($porArticulo);
        foreach ($porArticulo as $clave => $promos) {
            $lineas[] = $clave . "\t" . implode(',', array_unique($promos));
        }

        return implode("\n", $lineas) . "\n";
    }

    /**
     * ¿Va esta promoción en Google con este artículo? Todas, salvo un regalo en un artículo con
     * Special Price: en Google el artículo sale a su precio rebajado, y la versión con regalo (la
     * tarjeta «12 kg + 3 kg gratis») va a su precio de siempre. Google pide que la promoción se
     * pueda canjear al precio anunciado, así que ahí no se anuncia: lo anuncia la propia tienda.
     */
    private static function anunciable(array $cupon, int $idProducto, int $articulo, int $idTienda): bool
    {
        if (!KpcElegibles::esRegalo($cupon)) {
            return true;
        }
        static $conEspecial = null;
        if ($conEspecial === null) {
            $conEspecial = array_fill_keys(array_column(self::especialesVivas($idTienda), 'clave'), true);
        }

        return !isset($conEspecial[self::idFeed($idProducto, max(0, $articulo))]);
    }

    /**
     * El id del artículo en el feed: «producto-combinación» (`7214-90684`); un producto sin
     * combinaciones, solo el producto (a confirmar con Julián cómo los manda el feed principal).
     */
    private static function idFeed(int $idProducto, int $ipa): string
    {
        return $ipa > 0 ? $idProducto . '-' . $ipa : (string) $idProducto;
    }

    /** Las direcciones que se le dan a Merchant para que recoja los ficheros él solo. */
    public static function direcciones(): array
    {
        $enlace = Context::getContext()->link;
        $salida = [];
        foreach (self::FICHEROS as $cual) {
            $salida[$cual] = $enlace->getModuleLink('kpycoupons', 'merchant', [
                'fichero' => $cual,
                'clave' => self::clave(),
            ], true);
        }

        return $salida;
    }

    /** La clave que protege esas direcciones. Se crea sola la primera vez. */
    public static function clave(): string
    {
        $clave = (string) Configuration::get('KPC_CLAVE_MERCHANT');
        if ($clave === '') {
            $clave = substr(md5(uniqid('kpc', true) . _COOKIE_KEY_), 0, 24);
            Configuration::updateValue('KPC_CLAVE_MERCHANT', $clave);
        }

        return $clave;
    }

    /**
     * Los Special Price que **de verdad** están rebajando, con sus dos precios.
     *
     * El listón es el mismo que el de la etiqueta de la tienda: por debajo del 1 % no es una oferta.
     * Mandar a Google una que no lo es es anunciar un descuento que el cliente no ve en la ficha, y
     * eso lo rechaza.
     *
     * @return array<int, array{clave: string, habitual: float, hoy: float, date_from: string, expire: string}>
     */
    private static function especialesVivas(int $idTienda): array
    {
        static $vivas = null;
        if ($vivas !== null) {
            return $vivas;
        }
        $vivas = [];
        foreach (self::especiales($idTienda) as $f) {
            $id = (int) $f['id_product'];
            $ipa = (int) $f['ipa'];
            $hoy = (float) Product::getPriceStatic($id, true, $ipa);
            $pvpr = (float) Product::getPriceStatic($id, true, $ipa, 6, null, false, false);
            $habitual = round($pvpr * (1 - (float) $f['old_discount']), 2);
            // El mismo listón que la etiqueta de la tienda, y con los mismos precios: así no puede
            // salir en Google una oferta que en la ficha no se ve, ni al revés.
            if (KpcFlags::pct($habitual, $hoy) < KpcFlags::MINIMO) {
                continue;
            }
            $vivas[] = [
                'clave' => self::idFeed($id, $ipa),
                'habitual' => $habitual,
                'hoy' => $hoy,
                'date_from' => (string) $f['date_from'],
                'expire' => (string) $f['expire'],
            ];
        }

        return $vivas;
    }

    /** Las filas de Special Price apuntadas y en fecha. */
    private static function especiales(int $idTienda): array
    {
        if (!KpcCupon::hayTablaEspeciales()) {
            return [];
        }

        $p = _DB_PREFIX_;
        $tienda = (int) $idTienda;

        // Sin los formatos apagados (`kpy_product_attribute.active = 0`, los que la ficha no enseña): no se
        // venden, y el feed de Julián tampoco los lleva (Merchant los daba por «la oferta no existe», 26/10/2026).
        $apagado = KpcAlcance::hayFormatosApagados()
            ? " AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute k WHERE k.id_product_attribute = sp.id_product_attribute AND k.active = 0)"
            : '';

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT sp.id_product, sp.id_product_attribute AS ipa, sp.old_discount, sp.date_from, sp.expire
             FROM {$p}kpy_special_price sp
             INNER JOIN {$p}product p ON p.id_product = sp.id_product AND p.active = 1
             WHERE sp.id_shop = {$tienda} AND NOW() BETWEEN sp.date_from AND sp.expire{$apagado}
             ORDER BY sp.id_product, sp.id_product_attribute"
        ) ?: [];
    }

    /**
     * Deja escrito en `kpy_coupon.merchant_id` el `promotion_id` de cada promoción de carrito que aún
     * no lo tiene. Así el feed de productos de Julián lo lee de una columna, sin repetir la regla, y
     * no cambia aunque luego se muevan las fechas. Se llama al rehacer el reparto.
     */
    public static function fijarIds(int $idTienda): void
    {
        $db = Db::getInstance();
        foreach ($db->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . "kpy_coupon
             WHERE merchant_id = '' AND tipo IN ('cupon', 'segunda_unidad', 'regalo') AND id_shop = " . (int) $idTienda
        ) ?: [] as $c) {
            $db->update('kpy_coupon', ['merchant_id' => pSQL(self::idPromo($c))], 'id_kpy_coupon = ' . (int) $c['id_kpy_coupon']);
        }
    }

    /** Google no deja repetir un `promotion_id` aprobado: por eso lleva la fecha de inicio. */
    private static function idPromo(array $cupon): string
    {
        $suyo = trim((string) ($cupon['merchant_id'] ?? ''));
        if ($suyo !== '') {
            return preg_replace('/[^A-Za-z0-9_]/', '_', $suyo) ?? $suyo;
        }
        $codigo = preg_replace('/[^A-Za-z0-9]/', '', (string) $cupon['codigo']) ?? '';

        return mb_strtolower($codigo) . '_' . date('Ymd', strtotime((string) ($cupon['fecha_desde'] ?: 'now')));
    }

    /** Lo que lee el cliente en Google: 60 caracteres, sin mayúsculas gritadas ni exclamaciones. */
    private static function titulo(array $cupon): string
    {
        $titulo = trim((string) ($cupon['merchant_titulo'] ?? '')) ?: trim((string) $cupon['nombre']);
        if ($titulo === '') {
            $pct = KpcElegibles::pct((float) $cupon['porcentaje']);
            $titulo = ($cupon['tipo'] ?? 'cupon') === 'segunda_unidad'
                ? '2a unidad al ' . $pct . ' %'
                : (($cupon['tipo'] ?? 'cupon') === 'regalo' ? 'Regalo con la compra' : $pct . ' % de descuento con el codigo ' . $cupon['codigo']);
        }

        return mb_substr(str_replace('!', '', $titulo), 0, 60);
    }

    /**
     * Lo que se regala, para Google (`free_gift_description`, 100 caracteres): los regalos distintos
     * de la promoción («Saco de 3 Kg», «Caja 6 latas de Boske…»), o «Un regalo con tu compra».
     */
    private static function descripcionRegalo(array $cupon, int $idTienda): string
    {
        $articulos = [];
        foreach (KpcRegalo::guardadas((int) $cupon['id_kpy_coupon']) as $p) {
            $articulos[(int) $p['r']['id'] . '-' . (int) $p['r']['ipa']] = $p['r'];
        }
        $nombres = [];
        foreach (KpcRegalo::datos(array_values($articulos), $idTienda) as $d) {
            $nombres[] = KpcRegalo::nombreCorto($d['nombre'], $d['formato']);
            if (count($nombres) >= 3) {
                break;
            }
        }
        $texto = $nombres !== [] ? 'Gratis: ' . implode(' / ', $nombres) : 'Un regalo con tu compra';

        return mb_substr(str_replace('!', '', $texto), 0, 100);
    }

    private static function fechas(array $cupon): string
    {
        $desde = (string) ($cupon['fecha_desde'] ?: date('Y-m-d 00:00:00'));
        $hasta = (string) ($cupon['fecha_hasta'] ?: date('Y-m-d 23:59:59', strtotime('+180 days')));

        return self::iso($desde) . '/' . self::iso($hasta);
    }

    private static function iso(string $fecha): string
    {
        $t = strtotime($fecha) ?: time();

        return date('c', $t);
    }

    private static function limpio(string $valor): string
    {
        return str_replace(["\t", "\r", "\n"], ' ', $valor);
    }
}
