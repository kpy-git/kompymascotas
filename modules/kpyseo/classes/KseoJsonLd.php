<?php
/**
 * Datos estructurados: completa el JSON-LD de producto que imprime el tema (GTIN de la combinación,
 * envío, devoluciones, marca, descripción, fin real de la rebaja, sin mpn inventado) y pinta la ficha de Organización en la portada.
 *
 * El JSON-LD del producto se retoca sobre el HTML final (actionOutputHTMLBefore): se busca el bloque
 * de tipo Product, se decodifica, se completa y se vuelve a escribir. Si por lo que sea no es un JSON
 * válido, se deja tal cual.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoJsonLd
{
    public static function enriquecerProducto(string $html, Context $ctx): string
    {
        if (!KseoAjustes::activo('KPYSEO_JSONLD')) {
            return $html;
        }
        $producto = $ctx->smarty->getTemplateVars('product');
        if (!$producto instanceof ArrayAccess && !is_array($producto)) {
            return $html;
        }

        $resultado = preg_replace_callback(
            '~<script type="application/ld\+json">(.*?)</script>~s',
            static function (array $m) use ($producto, $ctx): string {
                $datos = json_decode(trim($m[1]), true);
                if (!is_array($datos) || ($datos['@type'] ?? '') !== 'Product') {
                    return $m[0];
                }
                $datos = self::completarProducto($datos, $producto, $ctx);

                return '<script type="application/ld+json">' . json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
            },
            $html
        );

        return is_string($resultado) ? $resultado : $html;
    }

    /** @param ArrayAccess|array $producto */
    private static function completarProducto(array $datos, $producto, Context $ctx): array
    {
        $idProducto = (int) ($producto['id_product'] ?? $producto['id'] ?? 0);
        $ipa = (int) ($producto['id_product_attribute'] ?? 0);

        // GTIN: el que trae el presentador, si no el de la combinación, si no el de la combinación por defecto
        $gtin = (string) ($producto['ean13'] ?? '');
        if (!self::gtinValido($gtin)) {
            $gtin = self::eanCombinacion($idProducto, $ipa);
        }
        foreach (['gtin', 'gtin8', 'gtin12', 'gtin13', 'gtin14'] as $clave) {
            unset($datos[$clave]);
        }
        if (self::gtinValido($gtin)) {
            $datos['gtin' . strlen($gtin)] = $gtin;
        }

        if (empty($datos['brand']) || !is_array($datos['brand'])) {
            $marca = trim((string) ($producto['manufacturer_name'] ?? ''));
            if ($marca !== '') {
                $datos['brand'] = ['@type' => 'Brand', 'name' => $marca];
            }
        }

        $descripcion = KseoMarcas::textoPlano((string) ($producto['description_short'] ?? ''));
        if (mb_strlen($descripcion) > mb_strlen((string) ($datos['description'] ?? ''))) {
            $datos['description'] = mb_substr($descripcion, 0, 5000);
        }

        if (!empty($producto['url']) && empty($datos['url'])) {
            $datos['url'] = (string) $producto['url'];
        }

        // el tema pone como mpn la referencia o el id del producto cuando no hay MPN: eso no es un MPN
        $mpn = trim((string) ($producto['mpn'] ?? ''));
        if ($mpn === '') {
            unset($datos['mpn']);
        } else {
            $datos['mpn'] = $mpn;
        }

        if (isset($datos['offers']) && is_array($datos['offers']) && ($datos['offers']['@type'] ?? '') === 'Offer') {
            $datos['offers'] = self::completarOferta($datos['offers'], (float) ($producto['price_amount'] ?? 0), self::finDelPrecio($producto), $ctx);
        }

        return $datos;
    }

    private static function completarOferta(array $oferta, float $precio, string $validez, Context $ctx): array
    {
        // el tema pone siempre «hoy + 15 días»; solo vale la fecha real de fin de la rebaja, si la hay
        if ($validez !== '') {
            $oferta['priceValidUntil'] = $validez;
        } else {
            unset($oferta['priceValidUntil']);
        }
        if (empty($oferta['priceCurrency'])) {
            $oferta['priceCurrency'] = (string) ($ctx->currency->iso_code ?? 'EUR');
        }
        if (empty($oferta['itemCondition'])) {
            $oferta['itemCondition'] = 'https://schema.org/NewCondition';
        }
        $moneda = (string) $oferta['priceCurrency'];

        $umbral = (float) Configuration::get('PS_SHIPPING_FREE_PRICE');
        $portes = KseoAjustes::numero('KPYSEO_PORTES');
        $coste = ($umbral > 0 && $precio >= $umbral) ? 0.0 : $portes;

        $oferta['shippingDetails'] = [
            '@type' => 'OfferShippingDetails',
            'shippingRate' => ['@type' => 'MonetaryAmount', 'value' => number_format($coste, 2, '.', ''), 'currency' => $moneda],
            'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'ES'],
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => self::dias('KPYSEO_ENVIO_PREP_MIN', 'KPYSEO_ENVIO_PREP_MAX'),
                'transitTime' => self::dias('KPYSEO_ENVIO_TRANSITO_MIN', 'KPYSEO_ENVIO_TRANSITO_MAX'),
            ],
        ];

        $diasDevolucion = (int) KseoAjustes::numero('KPYSEO_DEVOLUCION_DIAS');
        $costeDevolucion = KseoAjustes::numero('KPYSEO_DEVOLUCION_COSTE');
        $politica = [
            '@type' => 'MerchantReturnPolicy',
            'applicableCountry' => 'ES',
            'returnPolicyCategory' => $diasDevolucion > 0
                ? 'https://schema.org/MerchantReturnFiniteReturnWindow'
                : 'https://schema.org/MerchantReturnNotPermitted',
        ];
        if ($diasDevolucion > 0) {
            $politica['merchantReturnDays'] = $diasDevolucion;
            $politica['returnMethod'] = 'https://schema.org/ReturnByMail';
            if ($costeDevolucion > 0) {
                $politica['returnFees'] = 'https://schema.org/ReturnShippingFees';
                $politica['returnShippingFeesAmount'] = ['@type' => 'MonetaryAmount', 'value' => number_format($costeDevolucion, 2, '.', ''), 'currency' => $moneda];
            } else {
                $politica['returnFees'] = 'https://schema.org/FreeReturn';
            }
        }
        $oferta['hasMerchantReturnPolicy'] = $politica;

        return $oferta;
    }

    /**
     * La fecha de fin (Y-m-d) de la rebaja vigente del producto o de la combinación, o '' si el precio
     * no caduca. El presentador trae en `specific_prices` la rebaja que se está aplicando.
     *
     * @param ArrayAccess|array $producto
     */
    private static function finDelPrecio($producto): string
    {
        $rebaja = $producto['specific_prices'] ?? null;
        if (!is_array($rebaja) || empty($rebaja['to'])) {
            return '';
        }
        $hasta = (string) $rebaja['to'];
        if (str_starts_with($hasta, '0000-00-00')) {
            return '';
        }
        $momento = strtotime($hasta);
        if ($momento === false || $momento < time()) {
            return '';
        }

        return date('Y-m-d', $momento);
    }

    private static function dias(string $claveMin, string $claveMax): array
    {
        $min = max(0, (int) KseoAjustes::numero($claveMin));
        $max = max($min, (int) KseoAjustes::numero($claveMax));

        return ['@type' => 'QuantitativeValue', 'minValue' => $min, 'maxValue' => $max, 'unitCode' => 'DAY'];
    }

    public static function gtinValido(string $gtin): bool
    {
        return preg_match('~^(\d{8}|\d{12,14})$~', $gtin) === 1 && ltrim($gtin, '0') !== '';
    }

    /** El EAN de la combinación pedida o, si no vale, el de la combinación por defecto. */
    public static function eanCombinacion(int $idProducto, int $ipa): string
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        if ($ipa > 0) {
            $ean = (string) $db->getValue('SELECT ean13 FROM `' . _DB_PREFIX_ . 'product_attribute` WHERE id_product_attribute = ' . $ipa);
            if (self::gtinValido($ean)) {
                return $ean;
            }
        }
        if ($idProducto > 0) {
            $ean = (string) $db->getValue('SELECT pa.ean13 FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON pas.id_product_attribute = pa.id_product_attribute
                WHERE pa.id_product = ' . $idProducto . ' AND pas.default_on = 1 ORDER BY pas.id_shop LIMIT 1');
            if (self::gtinValido($ean)) {
                return $ean;
            }
            $ean = (string) $db->getValue('SELECT ean13 FROM `' . _DB_PREFIX_ . 'product` WHERE id_product = ' . $idProducto);
            if (self::gtinValido($ean)) {
                return $ean;
            }
        }

        return '';
    }

    /** La ficha de la empresa, para la portada. */
    public static function organizacion(Context $ctx): string
    {
        $nombre = (string) Configuration::get('PS_SHOP_NAME');
        $url = $ctx->link->getPageLink('index', true);
        $logo = KseoAjustes::texto('KPYSEO_LOGO');
        if ($logo === '') {
            $logo = rtrim($url, '/') . _PS_IMG_ . Configuration::get('PS_LOGO');
        }
        $telefono = trim((string) Configuration::get('PS_SHOP_PHONE'));
        if ($telefono !== '' && $telefono[0] !== '+') {
            $telefono = '+34 ' . $telefono;
        }

        $datos = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $url . '#organizacion',
            'name' => $nombre,
            'url' => $url,
            'logo' => $logo,
        ];
        $razon = KseoAjustes::texto('KPYSEO_RAZON_SOCIAL');
        if ($razon !== '') {
            $datos['legalName'] = $razon;
        }
        $nif = strtoupper(KseoAjustes::texto('KPYSEO_NIF'));
        if ($nif !== '') {
            $datos['vatID'] = str_starts_with($nif, 'ES') ? $nif : 'ES' . $nif;
        }
        $email = trim((string) Configuration::get('PS_SHOP_EMAIL'));
        if ($email !== '') {
            $datos['email'] = $email;
        }
        if ($telefono !== '') {
            $datos['telephone'] = $telefono;
            $datos['contactPoint'] = [[
                '@type' => 'ContactPoint',
                'telephone' => $telefono,
                'contactType' => 'customer service',
                'areaServed' => 'ES',
                'availableLanguage' => 'es',
            ]];
        }
        $calle = trim(implode(', ', array_filter([
            trim((string) Configuration::get('PS_SHOP_ADDR1')),
            trim((string) Configuration::get('PS_SHOP_ADDR2')),
        ])));
        if ($calle !== '') {
            $datos['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $calle,
                'postalCode' => trim((string) Configuration::get('PS_SHOP_CODE')),
                'addressLocality' => trim((string) Configuration::get('PS_SHOP_CITY')),
                'addressCountry' => 'ES',
            ];
        }
        $sameAs = KseoAjustes::sameAs();
        if ($sameAs) {
            $datos['sameAs'] = $sameAs;
        }

        return '<script type="application/ld+json">' . json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
