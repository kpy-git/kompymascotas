<?php
/**
 * Un cupón de Kompy: el dato, sus ámbitos (marcas, categorías, características, lista y
 * excluidos) y la regla de carrito de PrestaShop que hace el descuento de verdad.
 *
 * Por qué una regla de carrito y no un cálculo propio: así el descuento lo hace PrestaShop en la
 * cesta, en el pedido, en la factura y en los importes que ve el cliente, sin repetir su matemática.
 *
 * A qué artículos llega no se calcula aquí: lo calcula `KpcReparto` al guardar y lo deja escrito.
 */
class KpcCupon extends ObjectModel
{
    public $id_kpy_coupon;
    public $codigo;
    public $nombre;
    /**
     * `cupon` (código que se aplica), `segunda_unidad` (la 2ª unidad, más barata) o
     * `special_price` (el precio rebajado en la ficha, ver `KpcEspecial`).
     */
    public $tipo = 'cupon';
    /**
     * Solo en Special Price, cómo se rebaja: `pct` (el mismo % para todos), `pct_articulo` (cada
     * artículo de la lista con su %) o `precio_articulo` (cada uno con su precio final con IVA). Los
     * que no traen el suyo llevan el % general, si lo hay.
     */
    public $modo_precio = 'pct';
    /** Los formatos OUTLET se quedan fuera (ya van rebajados). */
    public $sin_outlet = 1;
    public $porcentaje;
    public $activo = 1;
    public $fecha_desde;
    public $fecha_hasta;
    /** Aplicado de entrada a todo el mundo en la web. */
    public $auto_web = 0;
    /** Canales que lo aplican de entrada, separados por comas: `shopping,email,social`. */
    public $auto_canal = '';
    /** 0 = no se enseña en la ficha (solo entra en la cesta). */
    public $en_ficha = 1;
    /** 1 = las combinaciones con Special Price se quedan fuera. */
    public $excluir_especial = 1;
    /** Los packs del mismo producto heredan la promoción sin tener que ponerlos (regla de Kompy). */
    public $incluir_packs = 1;
    /**
     * Los tamaños a los que llega, uno por línea («saco 12 kg», ver `KpcFormatos`); vacío = todos.
     * Y los niveles de pack: «1» solo sueltos, «2» solo packs de 2, «1,2»…; vacío = todos.
     */
    public $tamanos = '';
    public $packs = '';
    /** Para la herramienta de precios: mientras dure el cupón, PriceShape no toca ese producto. */
    public $bloquear_priceshape = 0;
    /** 0 = no se anuncia en Google (un cupón solo para el email, por ejemplo). */
    public $en_merchant = 1;
    /** Identificador y título de la promoción en Google Merchant. */
    public $merchant_id = '';
    public $merchant_titulo = '';
    /** La lista de artículos tal como se pegó (EAN, referencias, ids…) y cómo leerla. */
    public $lista = '';
    public $lista_tipo = 'auto';
    /** Lo que se deja fuera, igual. */
    public $excluir = '';
    /** Lo deja escrito `KpcReparto`: a cuántos artículos llega y qué ha soltado. */
    public $formatos = 0;
    public $aviso = '';
    public $id_cart_rule = 0;
    public $id_shop = 1;
    /**
     * Solo en un regalo (ver `KpcRegalo`): cómo se elige («pequeno»: el formato pequeño de la misma
     * ficha; «mismo»: el mismo artículo para todos), cuántos («unidad»: uno por cada unidad comprada;
     * «pedido»: como mucho uno), el tope de regalos de la campaña (0 = sin tope), el artículo cuando es
     * el mismo para todos («8299-92468») y el de reserva, y las parejas puestas a mano (JSON
     * clave → {r, s}).
     */
    public $regalo_modo = 'pequeno';
    public $regalo_por = 'unidad';
    public $regalo_tope = 0;
    /** 1 = los packs también regalan (un pack de 2 regala dos); 0 = solo los sueltos. */
    public $regalo_packs = 0;
    public $regalo_mismo = '';
    public $regalo_reserva = '';
    public $regalo_parejas = '';
    /** Lo que va detrás del «+» en la tarjeta de la ficha si el regalo es otra cosa («Regalo», «Snacks», «Pack latas»); vacío = se decide solo. */
    public $regalo_corto = '';
    /**
     * El **cupo** (ver `KpcCupo`): cuántas unidades se venden con la promoción (0 = sin cupo). Al
     * llegar, queda agotada (`cupo_agotado`, la fecha) y deja de estar en vigor; `cupo_ficha` dice si
     * la ficha enseña cuántas quedan.
     */
    public $cupo = 0;
    public $cupo_agotado;
    public $cupo_ficha = 1;
    /** Solo en un Special Price: cómo se llama en la tienda. Vacío = «Special Price»; `liquidacion`; `bajada` (de precio). */
    public $etiqueta = '';
    /** 1 = las unidades van en la lista, artículo a artículo (`kpy_coupon_scope.unidades`). */
    public $cupo_lista = 0;

    /** @var array<int, array> caché de `enVigor()` por tienda */
    private static array $enVigor = [];

    public static $definition = [
        'table' => 'kpy_coupon',
        'primary' => 'id_kpy_coupon',
        'fields' => [
            'codigo' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 32],
            'nombre' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 128],
            'tipo' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 16],
            'modo_precio' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 16],
            'sin_outlet' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'porcentaje' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'],
            'activo' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'fecha_desde' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'fecha_hasta' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'auto_web' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'auto_canal' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'en_ficha' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'excluir_especial' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'incluir_packs' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'tamanos' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'packs' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'bloquear_priceshape' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'en_merchant' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'merchant_id' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'merchant_titulo' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 160],
            'lista' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'lista_tipo' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 16],
            'excluir' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'formatos' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'aviso' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'id_cart_rule' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'regalo_modo' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 16],
            'regalo_por' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 8],
            'regalo_tope' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'regalo_packs' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'regalo_mismo' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 24],
            'regalo_reserva' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 24],
            'regalo_parejas' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'],
            'regalo_corto' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 24],
            'cupo' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'cupo_agotado' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'allow_null' => true],
            'cupo_ficha' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'etiqueta' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 16],
            'cupo_lista' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    /** @return array<int, array{tipo: string, id: int}> */
    public function ambitos(): array
    {
        return $this->id ? (self::ambitosDe([(int) $this->id])[(int) $this->id] ?? []) : [];
    }

    /**
     * Los ámbitos de varios cupones de una sola consulta.
     *
     * @param int[] $ids
     *
     * @return array<int, array<int, array{tipo: string, id: int}>>
     */
    public static function ambitosDe(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_kpy_coupon, tipo, id_ref, valor, unidades, agotado FROM ' . _DB_PREFIX_ . 'kpy_coupon_scope
             WHERE id_kpy_coupon IN (' . implode(',', $ids) . ')'
        ) ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']][] = [
                'tipo' => (string) $f['tipo'],
                'id' => (int) $f['id_ref'],
                'valor' => $f['valor'] !== null ? (float) $f['valor'] : null,
                // Las unidades de ese artículo (cupo por artículo) y cuándo vendió las suyas.
                'unidades' => isset($f['unidades']) && $f['unidades'] !== null ? (int) $f['unidades'] : null,
                'agotado' => self::fechaValida($f['agotado'] ?? '') ?? '',
            ];
        }

        return $salida;
    }

    /**
     * Guarda los ámbitos de golpe: `[['tipo' => 'marca', 'id' => 178], …]`. Los de la lista pueden
     * llevar `valor`: el % o el precio final de ese artículo (Special Price por artículo).
     */
    public function guardarAmbitos(array $ambitos): void
    {
        $db = Db::getInstance();
        // Los artículos de la lista que ya vendieron sus unidades siguen agotados, salvo que la lista
        // nueva les dé más unidades que las vendidas (entonces vuelven).
        $agotados = [];
        foreach ($db->executeS('SELECT tipo, id_ref, agotado FROM ' . _DB_PREFIX_ . 'kpy_coupon_scope WHERE id_kpy_coupon = ' . (int) $this->id . ' AND agotado IS NOT NULL') ?: [] as $f) {
            $agotados[$f['tipo'] . '-' . (int) $f['id_ref']] = (string) $f['agotado'];
        }
        $vendidas = $agotados !== [] ? KpcCupo::vendidasPorClave((int) $this->id) : [];
        $db->delete('kpy_coupon_scope', 'id_kpy_coupon = ' . (int) $this->id);
        $unicos = [];
        foreach ($ambitos as $a) {
            $tipo = (string) ($a['tipo'] ?? '');
            $id = (int) ($a['id'] ?? 0);
            if ($id <= 0 || !in_array($tipo, KpcElegibles::AMBITOS, true)) {
                continue;
            }
            $valor = isset($a['valor']) && $a['valor'] !== null ? (float) $a['valor'] : null;
            $unidades = isset($a['unidades']) && $a['unidades'] !== null ? max(0, (int) $a['unidades']) : null;
            $agotado = 'NULL';
            if ($unidades !== null && isset($agotados[$tipo . '-' . $id])
                && $unidades <= (int) ($vendidas[KpcCupo::claveDeAmbito($tipo, $id)] ?? 0)) {
                $agotado = "'" . pSQL($agotados[$tipo . '-' . $id]) . "'";
            }
            // Repetido: vale el último que traiga valor o unidades.
            if (!isset($unicos[$tipo . '-' . $id]) || $valor !== null || $unidades !== null) {
                $unicos[$tipo . '-' . $id] = '(' . (int) $this->id . ", '" . pSQL($tipo) . "', " . $id . ', '
                    . ($valor !== null ? $valor : 'NULL') . ', ' . ($unidades !== null ? $unidades : 'NULL') . ', ' . $agotado . ')';
            }
        }
        foreach (array_chunk(array_values($unicos), 500) as $trozo) {
            $db->execute('INSERT INTO ' . _DB_PREFIX_ . 'kpy_coupon_scope (id_kpy_coupon, tipo, id_ref, valor, unidades, agotado) VALUES ' . implode(',', $trozo));
        }
        KpcCupo::olvidarCache();
    }

    /**
     * Cupones en marcha de una tienda, con sus ámbitos; **el que más descuenta, primero**. Una con
     * el cupo agotado no está en marcha (ver `KpcCupo`).
     *
     * El orden manda en todas partes (ficha, etiqueta y cesta), así que se compara el descuento de
     * verdad: una «2ª unidad al 30 %» son como mucho un 15 %, no un 30 %.
     */
    public static function enVigor(int $idTienda): array
    {
        if (isset(self::$enVigor[$idTienda])) {
            return self::$enVigor[$idTienda];
        }
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM ' . _DB_PREFIX_ . 'kpy_coupon c
             WHERE c.activo = 1 AND c.id_shop = ' . (int) $idTienda . '
               AND (c.fecha_desde IS NULL OR c.fecha_desde = "0000-00-00 00:00:00" OR c.fecha_desde <= NOW())
               AND (c.fecha_hasta IS NULL OR c.fecha_hasta = "0000-00-00 00:00:00" OR c.fecha_hasta >= NOW())
               AND ' . KpcCupo::sqlVivo('c')
        ) ?: [];
        usort($filas, [KpcElegibles::class, 'manda']);

        return self::$enVigor[$idTienda] = $filas;
    }

    /** Tras guardar o rehacer: que lo siguiente de esta misma petición lea lo nuevo. */
    public static function olvidarCache(): void
    {
        self::$enVigor = [];
        KpcCupo::olvidarCache();
    }

    /** La promoción de cesta (cupón o 2ª unidad) en marcha con ese código. Un Special Price no se pide por código. */
    public static function porCodigo(string $codigo, int $idTienda): ?array
    {
        foreach (self::enVigor($idTienda) as $c) {
            if (!KpcElegibles::esPrecio($c) && mb_strtoupper((string) $c['codigo']) === mb_strtoupper(trim($codigo))) {
                return $c;
            }
        }

        return null;
    }

    public static function hayTablaEspeciales(): bool
    {
        static $hay = null;
        if ($hay === null) {
            $hay = (bool) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
                 AND TABLE_NAME = '" . pSQL(_DB_PREFIX_ . 'kpy_special_price') . "'"
            );
        }

        return $hay;
    }

    /**
     * Crea o actualiza la regla de carrito que hace el descuento: un % sobre los artículos del
     * cupón (`reduction_product = -2`, «los productos seleccionados»).
     *
     * @param int[] $claves artículos del cupón (combinación, o producto en negativo si no tiene)
     * @param array<int, int> $productos clave => id de producto
     */
    public function sincronizarRegla(array $claves, array $productos): void
    {
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        $regla = $this->id_cart_rule ? new CartRule((int) $this->id_cart_rule) : new CartRule();
        if ($this->id_cart_rule && !Validate::isLoadedObject($regla)) {
            $regla = new CartRule(); // Alguien la borró a mano: se hace otra.
        }
        // El nombre viaja a `cart_rule_lang`, que en esta tienda no aguanta el menos tipográfico.
        $nombre = str_replace(['−', '–', '—'], '-', (string) ($this->nombre ?: $this->codigo));
        $regla->name = [$idioma => mb_substr($nombre, 0, 254)];
        $regla->code = $this->codigo;
        // Sin fecha (o con la «0000-00-00» que deja la base de datos): desde ya y sin fin a la vista.
        $regla->date_from = self::fechaValida($this->fecha_desde) ?? date('Y-m-d H:i:00');
        $regla->date_to = self::fechaValida($this->fecha_hasta) ?? date('Y-m-d H:i:s', strtotime('+2 years'));
        $regla->quantity = 1000000;
        $regla->quantity_per_user = 100;
        $regla->reduction_percent = (float) $this->porcentaje;
        $regla->reduction_product = -2;
        $regla->product_restriction = 1;
        $regla->partial_use = 0;
        $regla->active = (bool) $this->activo;
        $regla->highlight = 0;
        $regla->id_customer = 0;
        if (!($regla->id ? $regla->update() : $regla->add()) || !$regla->id) {
            throw new RuntimeException(sprintf('no se ha podido guardar la regla de carrito de %s', $this->codigo));
        }
        if ((int) $this->id_cart_rule !== (int) $regla->id) {
            $this->id_cart_rule = (int) $regla->id;
            Db::getInstance()->update('kpy_coupon', ['id_cart_rule' => (int) $regla->id], 'id_kpy_coupon = ' . (int) $this->id);
        }

        $this->restringirRegla((int) $regla->id, $claves, $productos);
    }

    /** Una fecha de verdad, o null si está vacía o es la «0000-00-00» de la base de datos. */
    public static function fechaValida($fecha): ?string
    {
        $fecha = trim((string) $fecha);

        return $fecha === '' || strpos($fecha, '0000-00-00') === 0 || strtotime($fecha) === false ? null : $fecha;
    }

    /** El cupón apagado o terminado: su regla, también (si no, el código seguiría valiendo en la cesta). */
    public function apagarRegla(): void
    {
        if ($this->id_cart_rule) {
            // Por la API del core, no con un UPDATE crudo de `cart_rule` (3.0.0).
            $regla = new CartRule((int) $this->id_cart_rule);
            if (Validate::isLoadedObject($regla) && (int) $regla->active !== 0) {
                $regla->active = 0;
                $regla->update();
            }
        }
    }

    /**
     * Deja la regla de carrito apuntando **exactamente** a estos artículos.
     *
     * PrestaShop 9 restringe por combinación (`combinations`), y es lo único exacto. Antes se hacía
     * con «estos productos» + «estos atributos», contando con que las dos se cruzaran (Y); pero en
     * PrestaShop 9 un grupo por defecto se conforma con **una** de sus reglas (O), y el atributo
     * «Saco de 15 Kg» lo tiene medio catálogo: así el cupón EUKA10 descontaba el Boske de 15 kg
     * (visto el 30/09/2026).
     *
     * Los productos **sin combinaciones** (Trixie y otros accesorios) van en el carrito con
     * combinación 0, que es la misma para todos. Si el cupón los tiene, el grupo lleva dos reglas y
     * las dos se tienen que cumplir: «estos productos» (los del cupón, con y sin combinaciones) y
     * «estas combinaciones» (las del cupón, más la 0). PrestaShop las aplica una detrás de otra, así
     * que solo pasa lo que está en las dos: un accesorio del cupón sí; uno que no es del cupón, no
     * (falla la primera); un formato de otra talla de un pienso del cupón, tampoco (falla la segunda).
     *
     * @param int[] $claves
     * @param array<int, int> $productos clave => id de producto
     */
    private function restringirRegla(int $idRegla, array $claves, array $productos): void
    {
        $db = Db::getInstance();
        $p = _DB_PREFIX_;
        $db->execute(
            "DELETE v FROM {$p}cart_rule_product_rule_value v
             INNER JOIN {$p}cart_rule_product_rule r ON r.id_product_rule = v.id_product_rule
             INNER JOIN {$p}cart_rule_product_rule_group g ON g.id_product_rule_group = r.id_product_rule_group
             WHERE g.id_cart_rule = " . $idRegla
        );
        $db->execute(
            "DELETE r FROM {$p}cart_rule_product_rule r
             INNER JOIN {$p}cart_rule_product_rule_group g ON g.id_product_rule_group = r.id_product_rule_group
             WHERE g.id_cart_rule = " . $idRegla
        );
        $db->delete('cart_rule_product_rule_group', 'id_cart_rule = ' . $idRegla);

        $claves = array_values(array_unique(array_filter(array_map('intval', $claves))));
        if ($claves === []) {
            // Sin artículos, sin selección: con «los productos seleccionados» no descuenta nada.
            return;
        }
        $ipas = array_values(array_filter($claves, static fn (int $c): bool => $c > 0));
        $sinFormatos = array_values(array_map(static fn (int $c): int => -$c, array_filter($claves, static fn (int $c): bool => $c < 0)));

        $reglas = [];
        if ($sinFormatos === []) {
            $reglas['combinations'] = $ipas;
        } elseif ($ipas === []) {
            // Solo productos sin combinaciones: «estos productos» ya es exacto.
            $reglas['products'] = $sinFormatos;
        } else {
            $reglas['products'] = array_values(array_unique(array_map(static fn (int $c): int => (int) ($productos[$c] ?? 0), $claves)));
            $reglas['combinations'] = array_merge($ipas, [0]);
        }

        // «Todas las reglas del grupo»: que ningún cambio de PrestaShop en el valor por defecto
        // vuelva a abrir la puerta.
        $db->insert('cart_rule_product_rule_group', ['id_cart_rule' => $idRegla, 'quantity' => 1, 'type' => 'all_product_rules']);
        $idGrupo = (int) $db->Insert_ID();
        foreach ($reglas as $tipo => $valores) {
            $db->insert('cart_rule_product_rule', ['id_product_rule_group' => $idGrupo, 'type' => $tipo]);
            $idReglaProducto = (int) $db->Insert_ID();
            foreach (array_chunk(array_values(array_unique($valores)), 500) as $trozo) {
                $db->execute(
                    "INSERT INTO {$p}cart_rule_product_rule_value (id_product_rule, id_item) VALUES "
                    . implode(',', array_map(static fn (int $v): string => '(' . $idReglaProducto . ', ' . $v . ')', $trozo))
                );
            }
        }
    }

    public function delete(): bool
    {
        // Un Special Price: sus precios fuera (los de nadie más).
        KpcEspecial::quitarTodo((int) $this->id, (int) $this->id_shop);
        if ($this->id_cart_rule) {
            $regla = new CartRule((int) $this->id_cart_rule);
            if (Validate::isLoadedObject($regla)) {
                $regla->delete();
            }
        }
        Db::getInstance()->delete('kpy_coupon_scope', 'id_kpy_coupon = ' . (int) $this->id);
        Db::getInstance()->delete('kpy_coupon_item', 'id_kpy_coupon = ' . (int) $this->id);
        // Un regalo: sus parejas y lo que tuviera en cestas abiertas (lo regalado en pedidos se queda:
        // es el sell-out). Las cestas lo recogen en su próximo guardado y quitan la línea.
        Db::getInstance()->delete('kpy_coupon_regalo', 'id_kpy_coupon = ' . (int) $this->id);
        Db::getInstance()->delete('kpy_coupon_regalo_cesta', 'id_kpy_coupon = ' . (int) $this->id);
        // Las reservas del cupo en cestas abiertas, fuera; lo vendido se queda (es historia).
        Db::getInstance()->delete('kpy_coupon_cupo_cesta', 'id_kpy_coupon = ' . (int) $this->id);

        return parent::delete();
    }
}
