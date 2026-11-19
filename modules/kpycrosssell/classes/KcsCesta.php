<?php
/**
 * La cesta de un solo almacén (1.7.0): qué se pinta en cada línea, en el pie y en la ventana «Añadido a
 * tu cesta», y el cambio de una línea por su alternativa.
 *
 * La regla es de Julián (`kpyorderdispatcher`), no nuestra: si su `hasMultiWarehouse()` dice que la cesta
 * no implica más de un almacén, aquí no se hace **nada**. Si lo implica, se elige el almacén de referencia
 * (el que cubre más euros), las líneas que no salen de él son las que **bloquean**, y a cada una se le
 * ofrecen alternativas de ese almacén (`KcsAlternativas`). Una línea que bloquea **sin alternativas no
 * lleva nada** (ni etiqueta ni aviso: `seEnsena`). Al cliente no se le dice nunca de qué almacén sale
 * nada, ni ninguna fecha: la fecha la pone el módulo de Julián.
 *
 * La decisión se calcula una vez por petición y la usan los cuatro hooks.
 */
class KcsCesta
{
    /** Los ajustes y sus valores de partida (Rafa, 16–18/11/2026). */
    public const AJUSTES = [
        'KCS_CESTA_ON' => 1,            // el bloque «Cámbialo por…» en la línea de la cesta
        'KCS_CESTA_VENTANA' => 1,       // y en la ventana «Añadido a tu cesta»
        'KCS_CESTA_RESUMEN' => 1,       // el aviso de la cesta, al pie de la lista
        'KCS_CESTA_MIN' => 70,          // precio parecido: desde el 70 %…
        'KCS_CESTA_MAX' => 130,         // …hasta el 130 % del de la línea
        'KCS_CESTA_LEJOS_MIN' => 25,    // si no hay parecidos, como mucho desde el 25 %…
        'KCS_CESTA_LEJOS_MAX' => 400,   // …hasta el 400 % (Rafa, 18/11: mejor una alternativa más cara que ninguna)…
        'KCS_CESTA_LEJOS_EUROS' => 10,  // …o hasta 10 € más, lo que sea mayor (a 1,55 € el 400 % son 6 €)
        'KCS_CESTA_TARJETAS' => 3,      // tarjetas a la vista
        'KCS_CESTA_TOPE' => 12,         // y hasta estas con «Ver más»
        'KCS_CESTA_MARCAS' => 10,       // chips de marcas
        'KCS_CESTA_DIETA_MARCAS' => 'Farmina', // marcas preferidas para las dietas, por orden, separadas por comas
        'KCS_CESTA_NUNCA' => 'EVOLUTION:5576', // lo que nunca sale de un almacén aunque el reparto de la cesta diga que sí: los snacks de Natural Greatness (su EvolutionOrderGuard)
    ];

    /** @var array<string, array> la decisión por cesta y firma de sus líneas */
    private static array $decisiones = [];

    /** @var array<string, ?array> los bloques ya pintados en esta petición, por decisión, línea y origen */
    private static array $bloques = [];

    /** ¿Está encendido y se puede preguntar al reparto? */
    public static function activo(): bool
    {
        return (bool) Configuration::get('KCS_CESTA_ON') && KcsAlmacenes::disponible();
    }

    /** Los ajustes, listos para el motor. */
    public static function ajustes(): array
    {
        $minimo = (int) Configuration::get('KCS_CESTA_MIN');
        $maximo = (int) Configuration::get('KCS_CESTA_MAX');
        $lejosMin = (int) Configuration::get('KCS_CESTA_LEJOS_MIN');
        $lejosMax = (int) Configuration::get('KCS_CESTA_LEJOS_MAX');
        $minimo = max(0.1, min(1.0, ($minimo > 0 ? $minimo : 70) / 100));
        $maximo = max(1.0, ($maximo > 0 ? $maximo : 130) / 100);

        return [
            'minimo' => $minimo,
            'maximo' => $maximo,
            // La banda «como mucho» envuelve siempre a la de precio parecido.
            'lejos_min' => min($minimo, max(0.01, ($lejosMin > 0 ? $lejosMin : 25) / 100)),
            'lejos_max' => max($maximo, ($lejosMax > 0 ? $lejosMax : 400) / 100),
            'lejos_euros' => max(0.0, (float) (Configuration::get('KCS_CESTA_LEJOS_EUROS') !== false ? Configuration::get('KCS_CESTA_LEJOS_EUROS') : 10)),
            'tarjetas' => max(1, (int) Configuration::get('KCS_CESTA_TARJETAS') ?: 3),
            'tope' => max(1, (int) Configuration::get('KCS_CESTA_TOPE') ?: 12),
            'marcas' => max(0, (int) Configuration::get('KCS_CESTA_MARCAS')),
            'marcas_dieta' => array_values(array_filter(array_map('trim', explode(',', (string) Configuration::get('KCS_CESTA_DIETA_MARCAS'))))),
            // Almacén => categorías que nunca salen de él (`KcsAlmacenes::leerVetos`).
            'nunca' => KcsAlmacenes::leerVetos((string) Configuration::get('KCS_CESTA_NUNCA')),
            'ventana' => (bool) Configuration::get('KCS_CESTA_VENTANA'),
            'resumen' => (bool) Configuration::get('KCS_CESTA_RESUMEN'),
        ];
    }

    /**
     * Las líneas de la cesta, con lo que hace falta: ids, marca, cantidad, precios (con y sin impuestos),
     * peso del formato y nombre. Los regalos de las reglas de carrito no cuentan.
     *
     * @return array<string, array{clave: string, id_product: int, id_product_attribute: int, id_manufacturer: int, nombre: string,
     *                             cantidad: int, precio: float, precio_base: float, total: float, peso: float}>
     */
    public static function lineas(Cart $cart): array
    {
        $salida = [];
        foreach ($cart->getProducts() as $f) {
            if (!empty($f['is_gift'])) {
                continue;
            }
            $id = (int) $f['id_product'];
            $ipa = (int) $f['id_product_attribute'];
            $clave = $id . '-' . $ipa;
            $cantidad = (int) ($f['cart_quantity'] ?? $f['quantity'] ?? 0);
            $salida[$clave] = [
                'clave' => $clave,
                'id_product' => $id,
                'id_product_attribute' => $ipa,
                'id_manufacturer' => (int) ($f['id_manufacturer'] ?? 0),
                'nombre' => (string) ($f['name'] ?? ''),
                'cantidad' => $cantidad,
                // Lo que paga (con su promoción) y lo que vale (sin ella): la banda de precio parecido se mide
                // contra lo que vale, para que una oferta no deje la línea sin alternativas.
                'precio' => round((float) ($f['price_wt'] ?? 0), 2),
                'precio_lista' => round((float) ($f['price_without_reduction'] ?? 0), 2) ?: round((float) ($f['price_wt'] ?? 0), 2),
                'precio_base' => (float) ($f['price_without_reduction_without_tax'] ?? 0) ?: (float) ($f['price'] ?? 0),
                'total' => round((float) ($f['total_wt'] ?? 0), 2),
                // `weight` ya suma el del formato (comprobado en pruebas: 14 kg, no 28).
                'peso' => (float) ($f['weight'] ?? 0) ?: (float) ($f['weight_attribute'] ?? 0),
            ];
        }

        return $salida;
    }

    /**
     * La decisión de la cesta: si mezcla, el almacén de referencia y qué líneas bloquean.
     *
     * @return array{multi: bool, referencia: string, lineas: array<string, array>, bloqueantes: string[], firma: string}
     */
    public static function decision(Cart $cart): array
    {
        $lineas = self::lineas($cart);
        $firma = (int) $cart->id . ':' . md5(implode('|', array_map(static fn (array $l): string => $l['clave'] . 'x' . $l['cantidad'], $lineas)));
        if (isset(self::$decisiones[$firma])) {
            return self::$decisiones[$firma];
        }
        $vacia = ['multi' => false, 'referencia' => '', 'lineas' => $lineas, 'bloqueantes' => [], 'firma' => $firma];
        if (count($lineas) < 2 || !self::activo()) {
            return self::$decisiones[$firma] = $vacia;
        }

        // La puerta: si el reparto de Julián no ve más de un almacén (o cada almacén pasa del envío gratis y
        // se parte el pedido), nada. Con los productos de la cesta presentada, como en su módulo.
        $productos = KcsAlmacenes::productosDeCesta($cart);
        if ($productos === null || KcsAlmacenes::multi($productos) !== true) {
            return self::$decisiones[$firma] = $vacia;
        }
        foreach ($lineas as $clave => $l) {
            $lineas[$clave]['almacen'] = (string) (KcsAlmacenes::deProducto($l['id_product'], $l['id_product_attribute'], $l['id_manufacturer'], $l['cantidad']) ?? '');
        }
        if (in_array('', array_column($lineas, 'almacen'), true)) {
            // Alguna línea sin respuesta: no se inventa nada.
            return self::$decisiones[$firma] = $vacia;
        }
        $referencia = KcsAlmacenes::referencia($lineas);
        $bloqueantes = [];
        foreach ($lineas as $clave => $l) {
            $lineas[$clave]['bloquea'] = $l['almacen'] !== $referencia;
            if ($lineas[$clave]['bloquea']) {
                $bloqueantes[] = $clave;
            }
        }

        return self::$decisiones[$firma] = [
            'multi' => $bloqueantes !== [],
            'referencia' => $referencia,
            'lineas' => $lineas,
            'bloqueantes' => $bloqueantes,
            'firma' => $firma,
        ];
    }

    /** Que la siguiente llamada vuelva a mirar la cesta (después de cambiar una línea). */
    public static function olvidar(): void
    {
        self::$decisiones = [];
        self::$bloques = [];
    }

    /**
     * ¿Se le enseña algo a esta línea que bloquea? Solo si tiene alternativas, o si el cliente ya las vio y
     * pulsó «Dejarlo como está» (se queda la etiqueta con la (i)). Sin alternativas, **nada**: ni etiqueta, ni
     * bloque, ni aviso al pie, ni en la ventana; la cesta se queda como si todo saliera del mismo almacén
     * (Rafa, 18/11 a mediodía: «si no encuentras ningún producto no me pongas ningún mensaje, lo dejas tal
     * y como estaba… para no duplicar mensajes»: el de la fecha ya lo pone el módulo de Julián).
     *
     * @param array $linea una línea de la decisión, con `referencia` (`bloqueante()`)
     */
    public static function seEnsena(Cart $cart, array $linea): bool
    {
        if (KcsCestaLog::dejada((int) $cart->id, (int) $linea['id_product'], (int) $linea['id_product_attribute'])) {
            return true;
        }

        return KcsAlternativas::para($linea, (string) $linea['referencia'], (int) $cart->id, self::ajustes(), array_keys(self::decision($cart)['lineas'])) !== [];
    }

    /**
     * Las líneas que bloquean **y se enseñan** (con alternativas, o dejadas), por clave: son las que cuenta el
     * aviso del pie. Si no hay ninguna, el aviso tampoco sale.
     *
     * @return string[]
     */
    public static function ensenadas(Cart $cart): array
    {
        $d = self::decision($cart);
        $salida = [];
        foreach ($d['bloqueantes'] as $clave) {
            if (self::seEnsena($cart, $d['lineas'][$clave] + ['referencia' => $d['referencia']])) {
                $salida[] = $clave;
            }
        }

        return $salida;
    }

    /** Una línea de la decisión, por producto y formato; null si no está o no bloquea. */
    public static function bloqueante(Cart $cart, int $idProducto, int $ipa): ?array
    {
        $d = self::decision($cart);
        $l = $d['lineas'][$idProducto . '-' . $ipa] ?? null;

        return $l !== null && !empty($l['bloquea']) ? $l + ['referencia' => $d['referencia']] : null;
    }

    /**
     * Lo que pinta el bloque «Cámbialo por…» de una línea: las alternativas (a la vista y las de «ver
     * más»), las marcas, los datos para el formulario de cambio. `null` si no hay alternativas.
     *
     * @return array{linea: array, tarjetas: array, extras: array, marcas: array, url: string, token: string, referencia: string}|null
     */
    public static function bloque(Cart $cart, array $linea, Kpycrosssell $modulo, string $origen = 'linea'): ?array
    {
        // Una vez por petición, línea y origen: se pregunta desde la etiqueta y desde el bloque, y la vista se
        // cuenta una sola vez.
        $firma = self::decision($cart)['firma'] . '|' . $linea['clave'] . '|' . $origen;
        if (array_key_exists($firma, self::$bloques)) {
            return self::$bloques[$firma];
        }
        $ajustes = self::ajustes();
        $alternativas = KcsAlternativas::para($linea, (string) $linea['referencia'], (int) $cart->id, $ajustes, array_keys(self::decision($cart)['lineas']));
        if ($alternativas === []) {
            return self::$bloques[$firma] = null;
        }
        $rasgos = KcsSugerencias::rasgos((int) $linea['id_product']);
        $perfil = KcsAlternativas::perfil($rasgos, (string) $linea['nombre']);
        $comida = in_array((string) $rasgos['tipo'], array_merge(KcsSugerencias::COMIDA, ['Snacks']), true);
        $marcas = $ajustes['marcas'] > 0
            ? KcsAlternativas::marcas((string) $linea['referencia'], (int) $cart->id, $alternativas, $ajustes['marcas'], $perfil['mascotas'] ?? [], $comida)
            : [];
        KcsCestaLog::apuntar((int) $cart->id, $origen === 'ventana' ? 'ventana' : 'vista', $linea['id_product'], $linea['id_product_attribute'], (string) $linea['referencia']);
        $tarjetas = array_slice($alternativas, 0, $ajustes['tarjetas']);

        return self::$bloques[$firma] = [
            'linea' => $linea,
            'tarjetas' => $tarjetas,
            'extras' => array_slice($alternativas, $ajustes['tarjetas']),
            // Si alguna de las que se ven no es de precio parecido, el texto no promete «precio parecido».
            'parecidos' => !in_array(false, array_map(static fn (array $t): bool => (bool) ($t['parecido'] ?? false), $tarjetas), true),
            'marcas' => $marcas,
            'url' => Context::getContext()->link->getModuleLink($modulo->name, 'cambiar', [], true),
            'token' => Tools::getToken(false),
            'referencia' => (string) $linea['referencia'],
            'origen' => $origen,
        ];
    }

    /**
     * Cambiar una línea por una alternativa: entra la nueva con la misma cantidad y, solo si ha entrado,
     * sale la vieja. El candidato tiene que ser de verdad una alternativa de esa línea **ahora** (se
     * recalcula): así nadie usa esta puerta para meter lo que quiera, y si el stock se fue en ese segundo,
     * no se toca nada.
     *
     * @return array{ok: bool, mensaje: string, nombre?: string, formato?: string, precio_txt?: string, multi?: bool}
     */
    public static function cambiar(Cart $cart, string $de, string $a, Kpycrosssell $modulo, string $origen = 'linea'): array
    {
        [$idDe, $ipaDe] = array_map('intval', array_pad(explode('-', $de, 2), 2, 0));
        [$idA, $ipaA] = array_map('intval', array_pad(explode('-', $a, 2), 2, 0));
        if ($idDe <= 0 || $idA <= 0 || $ipaA <= 0) {
            return ['ok' => false, 'mensaje' => $modulo->t('No se ha podido cambiar. Revisa la cesta.')];
        }
        self::olvidar();
        $linea = self::bloqueante($cart, $idDe, $ipaDe);
        if ($linea === null) {
            return ['ok' => false, 'mensaje' => $modulo->t('La cesta ha cambiado. Revísala antes de seguir.')];
        }
        $alternativas = KcsAlternativas::para($linea, (string) $linea['referencia'], (int) $cart->id, self::ajustes(), array_keys(self::decision($cart)['lineas']));
        $elegida = null;
        foreach ($alternativas as $alt) {
            if ((int) $alt['id_product'] === $idA && (int) $alt['id_product_attribute'] === $ipaA) {
                $elegida = $alt;
                break;
            }
        }
        if ($elegida === null) {
            return ['ok' => false, 'mensaje' => $modulo->t('Ese producto ya no está disponible. Elige otro.')];
        }

        $cantidad = max(1, (int) $linea['cantidad']);
        $entra = $cart->updateQty($cantidad, $idA, $ipaA, false, 'up');
        if ($entra !== true) {
            KcsCestaLog::apuntar((int) $cart->id, 'error', $idDe, $ipaDe, (string) $linea['referencia'], $idA);

            return ['ok' => false, 'mensaje' => $entra === -1
                ? $modulo->t('No hay unidades suficientes de ese producto. Elige otro.')
                : $modulo->t('No se ha podido añadir. Inténtalo otra vez.')];
        }
        if (!$cart->deleteProduct($idDe, $ipaDe)) {
            PrestaShopLogger::addLog('kpycrosssell, cambiar: entró ' . $a . ' pero no salió ' . $de, 2, null, 'Cart', (int) $cart->id, true);
        }
        Cart::resetStaticCache();
        self::olvidar();
        // El descuento por llevarlos juntos, al día, como en «Caprichitos».
        try {
            KcsJuntos::sincronizar($cart, $modulo);
        } catch (Throwable $e) {
            // La cesta ya está bien; el descuento se rehace al pintarla.
        }
        KcsCestaLog::apuntar((int) $cart->id, 'cambio', $idDe, $ipaDe, (string) $linea['referencia'], $idA);

        $despues = self::decision($cart);

        return [
            'ok' => true,
            'mensaje' => $modulo->t('Cambiado: %nuevo% en lugar de %viejo%', ['%nuevo%' => $elegida['nombre'], '%viejo%' => $linea['nombre']]),
            'nombre' => $elegida['nombre'],
            'formato' => $elegida['formato'],
            'precio_txt' => $elegida['precio_txt'],
            'multi' => $despues['multi'],
        ];
    }
}
