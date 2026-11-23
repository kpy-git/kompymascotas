<?php
/**
 * Lo que leen las páginas: el resumen que dejó escrito `KplSincronizar` (nunca se calcula nada en
 * una visita) y las direcciones de la landing.
 */
class KplPromos
{
    /**
     * La cookie de PrestaShop en la que `kpycoupons` apunta el cupón del cliente (contrato, 3.0.0):
     * `CODIGO` = lo lleva puesto; `!CODIGO` = lo quitó a mano; vacía = ninguno. Se lee del contexto,
     * nunca preguntando a kpycoupons. Y `?kpc=CODIGO` en cualquier dirección lo deja puesto (lo hace
     * kpycoupons al iniciar la petición, antes de que nuestras páginas se preparen).
     */
    public const COOKIE_CUPON = 'kpy_cupon';

    private static ?array $resumen = null;

    /** El cupón que el cliente lleva puesto, en mayúsculas; vacío si ninguno o si lo quitó a mano. */
    public static function cuponPuesto(): string
    {
        $cookie = Context::getContext()->cookie ?? null;
        if (!$cookie || !isset($cookie->{self::COOKIE_CUPON})) {
            return '';
        }
        $valor = trim((string) $cookie->{self::COOKIE_CUPON});

        return $valor === '' || $valor[0] === '!' ? '' : mb_strtoupper($valor);
    }

    /** El resumen de la landing, o uno vacío si aún no se ha sincronizado nunca. */
    public static function resumen(): array
    {
        if (self::$resumen === null) {
            $r = KplSincronizar::dato(KplSincronizar::DATO_RESUMEN);
            self::$resumen = is_array($r) && isset($r['claves']) ? $r : ['t' => 0, 'fecha' => '', 'tipos' => [], 'claves' => [], 'orden' => [], 'top' => ['todos' => [], 'perro' => [], 'gato' => []]];
        }

        return self::$resumen;
    }

    public static function olvidar(): void
    {
        self::$resumen = null;
        self::$productos = null;
        self::$heroes = null;
    }

    private static ?array $productos = null;

    /**
     * Lo que una campaña cambia en un producto: sus tipos de promoción que entran en ella, con el %
     * (`['special_price' => 15.0, 'cupon' => 10.0]`). Vacío si nada.
     *
     * @return array<string, float>
     */
    public static function enCampana(array $campana, int $idProducto): array
    {
        if (self::$productos === null) {
            $d = KplSincronizar::dato(KplSincronizar::DATO_PRODUCTOS);
            self::$productos = is_array($d) && isset($d['productos']) ? $d : ['productos' => [], 'marcadas' => []];
        }
        $suyas = self::$productos['productos'][$idProducto] ?? null;
        if (!$suyas) {
            return [];
        }
        $salida = [];
        foreach ($suyas as $tipo => $x) {
            foreach ((array) $x['promos'] as $idPromo) {
                if (KplCampanas::entra($campana, (int) $idPromo, (array) self::$productos['marcadas'])) {
                    $salida[$tipo] = (float) $x['pct'];
                    break;
                }
            }
        }

        return $salida;
    }

    /**
     * El tipo (de la landing) con el que entra una promoción de kpycoupons en un producto, o null:
     * `kilos_gratis` o `regalos` para un regalo, según lo que regala. Para reconocer una etiqueta
     * por su `kpc.id_kpy_coupon` sin adivinar por el texto.
     */
    public static function tipoDePromo(int $idProducto, int $idPromo): ?string
    {
        if (self::$productos === null) {
            self::enCampana([], 0);
        }
        foreach ((array) (self::$productos['productos'][$idProducto] ?? []) as $tipo => $x) {
            if (in_array($idPromo, array_map('intval', (array) ($x['promos'] ?? [])), true)) {
                return (string) $tipo;
            }
        }

        return null;
    }

    /** Las claves (del resumen) cuyas promociones entran en una campaña. */
    public static function deCampana(array $campana, array $claves): array
    {
        $salida = [];
        foreach ($claves as $k => $c) {
            foreach ((array) ($c['promos'] ?? []) as $idPromo) {
                if (self::promoEntra($campana, (int) $idPromo)) {
                    $salida[$k] = $c;
                    break;
                }
            }
        }

        return $salida;
    }

    /** ¿Entra una promoción de kpycoupons (por id) en una campaña? */
    public static function promoEntra(array $campana, int $idPromo): bool
    {
        if (self::$productos === null) {
            self::enCampana($campana, 0);
        }

        return KplCampanas::entra($campana, $idPromo, (array) (self::$productos['marcadas'] ?? []));
    }

    /** ¿Algún producto tiene una promoción de este tipo que entre en la campaña? */
    public static function tipoEnCampana(array $campana, string $tipo): bool
    {
        if (self::$productos === null) {
            self::enCampana($campana, 0);
        }
        foreach ((array) self::$productos['productos'] as $tipos) {
            foreach ((array) ($tipos[$tipo]['promos'] ?? []) as $idPromo) {
                if (self::promoEntra($campana, (int) $idPromo)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Las claves con el nombre de campaña en su etiqueta («Special Price» → «Black Friday») mientras
     * la campaña está viva y la promoción entra en ella. Sin el %: lo enseña la tarjeta al lado.
     * Las permanentes (kilos gratis, regalos, outlet) no son promociones de kpycoupons: entran
     * cuando entra todo (ninguna marcada en el paso 6). Marca `en_campana`.
     */
    public static function conCampana(array $claves, ?array $campana): array
    {
        if ($campana === null || ($campana['estado'] ?? '') !== 'viva') {
            return $claves;
        }
        foreach ($claves as $k => $c) {
            $entra = false;
            foreach ((array) ($c['promos'] ?? []) as $idPromo) {
                if (self::promoEntra($campana, (int) $idPromo)) {
                    $entra = true;
                    break;
                }
            }
            if (!$entra && !empty($c['permanente'])) {
                $entra = self::promoEntra($campana, 0);
            }
            if (!$entra) {
                continue;
            }
            $nombre = KplCampanas::etiquetaCorta($campana, (string) ($c['tipo'] ?? ''));
            if ($nombre !== '') {
                $claves[$k]['nombre_tipo'] = $nombre;
                // El título que se escribe solo empieza por el nombre del tipo («Special Price: −10 %
                // en toda la gama Boske»): durante la campaña, por el de la campaña. Uno escrito a
                // mano en el paso 6 no empieza así y se queda como está.
                $tipoNombre = (string) (KplClaves::NOMBRE_TIPO[$c['tipo'] ?? ''] ?? '');
                $titulo = (string) ($c['titulo'] ?? '');
                if ($tipoNombre !== '' && mb_stripos($titulo, $tipoNombre . ':') === 0) {
                    $claves[$k]['titulo'] = $nombre . mb_substr($titulo, mb_strlen($tipoNombre));
                }
            }
            $claves[$k]['en_campana'] = true;
        }

        return $claves;
    }

    /** Los tipos (de `tipos()`) con el nombre de campaña si la campaña viva los cambia. */
    public static function tiposConCampana(array $tipos, ?array $campana): array
    {
        if ($campana === null || ($campana['estado'] ?? '') !== 'viva') {
            return $tipos;
        }
        foreach ($tipos as $k => $t) {
            $tipo = (string) ($t['tipo'] ?? '');
            $nombre = KplCampanas::etiquetaCorta($campana, $tipo);
            if ($nombre !== '' && self::tipoEnCampana($campana, $tipo)) {
                $tipos[$k]['nombre'] = $nombre;
                $tipos[$k]['en_campana'] = true;
            }
        }

        return $tipos;
    }

    /**
     * Las marcas de la campaña con su logo (sin fondo), una vez cada una, en el orden de la campaña:
     * para el bloque de la portada de la tienda.
     *
     * @return array<int, array{marca: string, logo: string, url: string}>
     */
    public static function marcasDeCampana(array $campana, int $maximo = 8): array
    {
        $claves = KplCampanas::ordenar(self::deCampana($campana, self::vivas()), $campana);
        $salida = [];
        foreach ($claves as $c) {
            $id = (int) ($c['id_manufacturer'] ?? 0);
            if ($id <= 0 || isset($salida[$id])) {
                continue;
            }
            $salida[$id] = ['marca' => (string) $c['marca'], 'logo' => (string) ($c['logo'] ?? ''), 'logo_k' => (float) ($c['logo_k'] ?? 1), 'url' => self::urlClave((string) $c['clave'])];
            if (count($salida) >= $maximo) {
                break;
            }
        }

        return array_values($salida);
    }

    /**
     * Los productos de la campaña para la portada de la tienda. Primero los **elegidos** a mano en la
     * campaña (`elegidos()`), en su orden. Si faltan, lo automático: los que entran en la campaña,
     * activos y con stock, con las marcas prioritarias primero (el orden de marcas de la campaña y,
     * detrás, las marcas prioritarias de la pantalla: Boske, el buque de la web…) y dentro de cada
     * marca por **ventas de los últimos 90 días** (después por rebaja). Para que no sean todos de la
     * misma marca, cada una pone como mucho la mitad (contando los elegidos).
     *
     * @param string[] $prioridad claves de marca (`boske`), en orden
     *
     * @return int[]
     */
    public static function portada(array $campana, int $n, array $prioridad): array
    {
        if ($n <= 0) {
            return [];
        }
        if (self::$productos === null) {
            self::enCampana($campana, 0);
        }
        $rebaja = [];
        foreach ((array) self::$productos['productos'] as $id => $tipos) {
            $suyas = self::enCampana($campana, (int) $id);
            if ($suyas !== []) {
                $rebaja[(int) $id] = max(array_map('floatval', $suyas));
            }
        }
        if ($rebaja === []) {
            return [];
        }
        $elegidos = self::elegidos($campana);
        if (count($elegidos) >= $n) {
            return array_slice($elegidos, 0, $n);
        }
        // Lo automático, sin agotados ni apagados (antes salían por id, aunque no se vendieran).
        $vendibles = self::vendibles(array_keys($rebaja));
        $ids = array_values(array_filter(
            array_keys($rebaja),
            static fn (int $id): bool => isset($vendibles[$id]) && !in_array($id, $elegidos, true)
        ));
        if ($ids === []) {
            return $elegidos;
        }
        $orden = [];
        foreach (array_merge(KplCampanas::ordenMarcas($campana), $prioridad) as $slug) {
            if ($slug !== '' && !isset($orden[$slug])) {
                $orden[$slug] = count($orden);
            }
        }
        $marca = [];
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT p.id_product, m.name FROM ' . _DB_PREFIX_ . 'product p LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON m.id_manufacturer = p.id_manufacturer'
            . ' WHERE p.id_product IN (' . implode(',', array_merge($ids, $elegidos)) . ')'
        ) ?: [];
        foreach ($filas as $f) {
            $marca[(int) $f['id_product']] = KplClaves::slug((string) $f['name']);
        }
        $ventas = self::ventas90();
        usort($ids, static function (int $a, int $b) use ($rebaja, $marca, $orden, $ventas): int {
            return [$orden[$marca[$a] ?? ''] ?? 9999, -($ventas[$a] ?? 0), -$rebaja[$a], $a] <=> [$orden[$marca[$b] ?? ''] ?? 9999, -($ventas[$b] ?? 0), -$rebaja[$b], $b];
        });
        $maximoPorMarca = max(2, (int) ceil($n / 2));
        $salida = [];
        $porMarca = [];
        foreach ($elegidos as $id) {
            $m = $marca[$id] ?? '';
            $porMarca[$m] = ($porMarca[$m] ?? 0) + 1;
        }
        $sobrantes = [];
        $faltan = $n - count($elegidos);
        foreach ($ids as $id) {
            $m = $marca[$id] ?? '';
            if (($porMarca[$m] ?? 0) < $maximoPorMarca) {
                $salida[] = $id;
                $porMarca[$m] = ($porMarca[$m] ?? 0) + 1;
            } else {
                $sobrantes[] = $id;
            }
            if (count($salida) >= $faltan) {
                break;
            }
        }

        return array_slice(array_merge($elegidos, $salida, $sobrantes), 0, $n);
    }

    /**
     * Los «productos elegidos» de una campaña (Rafa, 19/11) que se pueden enseñar, en su orden:
     * activos, visibles, con stock y con una promoción que entra en la campaña.
     *
     * @return int[]
     */
    public static function elegidos(array $campana): array
    {
        $ids = array_values(array_filter(KplCampanas::elegidos($campana), static fn (int $id): bool => self::enCampana($campana, $id) !== []));
        if ($ids === []) {
            return [];
        }
        $vendibles = self::vendibles($ids);

        return array_values(array_filter($ids, static fn (int $id): bool => isset($vendibles[$id])));
    }

    /**
     * Para la pantalla: cada producto elegido con su nombre y, si no sale, por qué
     * (`no_existe`, `fuera` de la campaña, `sin_stock`: apagado o agotado).
     *
     * @return array<int, array{id: int, nombre: string, motivo: string}>
     */
    public static function estadoElegidos(array $campana): array
    {
        $ids = KplCampanas::elegidos($campana);
        if ($ids === []) {
            return [];
        }
        $contexto = Context::getContext();
        $nombres = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_product, name FROM ' . _DB_PREFIX_ . 'product_lang WHERE id_lang = ' . (int) $contexto->language->id
            . ' AND id_shop = ' . (int) $contexto->shop->id . ' AND id_product IN (' . implode(',', $ids) . ')'
        ) ?: [] as $f) {
            $nombres[(int) $f['id_product']] = (string) $f['name'];
        }
        $vendibles = self::vendibles($ids);
        $salida = [];
        foreach ($ids as $id) {
            if (!isset($nombres[$id])) {
                $motivo = 'no_existe';
            } elseif (self::enCampana($campana, $id) === []) {
                $motivo = 'fuera';
            } elseif (!isset($vendibles[$id])) {
                $motivo = 'sin_stock';
            } else {
                $motivo = '';
            }
            $salida[] = ['id' => $id, 'nombre' => $nombres[$id] ?? '', 'motivo' => $motivo];
        }

        return $salida;
    }

    /**
     * De una lista de productos, los que se pueden enseñar ahora: activos, visibles en el catálogo y
     * que se pueden comprar: con stock (el total del producto, `id_product_attribute = 0`), o que se
     * venden sin stock (`out_of_stock` = 1, o 2 con «pedidos sin stock» en la tienda), o todos si la
     * tienda no gestiona stock.
     *
     * @param int[] $ids
     *
     * @return array<int, true>
     */
    public static function vendibles(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $tienda = Context::getContext()->shop;
        $idTienda = (int) $tienda->id;
        $p = _DB_PREFIX_;
        if (!(int) Configuration::get('PS_STOCK_MANAGEMENT')) {
            $stock = '1';
        } else {
            $stock = 'sa.quantity > 0 OR sa.out_of_stock = 1'
                . ((int) Configuration::get('PS_ORDER_OUT_OF_STOCK') ? ' OR sa.out_of_stock = 2' : '');
        }
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT ps.id_product FROM {$p}product_shop ps
             LEFT JOIN {$p}stock_available sa ON sa.id_product = ps.id_product AND sa.id_product_attribute = 0
                AND (sa.id_shop = {$idTienda} OR (sa.id_shop = 0 AND sa.id_shop_group = " . (int) $tienda->id_shop_group . "))
             WHERE ps.id_shop = {$idTienda} AND ps.active = 1 AND ps.visibility IN ('both', 'catalog') AND ({$stock})
               AND ps.id_product IN (" . implode(',', $ids) . ')'
        ) ?: [] as $f) {
            $salida[(int) $f['id_product']] = true;
        }

        return $salida;
    }

    public const DATO_VENTAS = 'ventas_90';

    private static ?array $ventas = null;

    /**
     * Unidades vendidas de cada producto en promoción en los últimos 90 días, para ordenar lo
     * automático de la portada. Solo se lee: lo calcula `calcularVentas90()` al sincronizar, al guardar
     * una campaña y al actualizar el módulo, nunca en una visita. Sin datos, vacío (se ordena por rebaja).
     *
     * @return array<int, int>
     */
    public static function ventas90(): array
    {
        if (self::$ventas === null) {
            $guardado = KplSincronizar::dato(self::DATO_VENTAS);
            self::$ventas = is_array($guardado['v'] ?? null) ? array_map('intval', $guardado['v']) : [];
        }

        return self::$ventas;
    }

    /**
     * Suma las ventas de 90 días (pedidos válidos) de los productos que están en alguna promoción y
     * las guarda en `kpy_promo_dato` (`ventas_90`). Para la sincronización, la pantalla y el upgrade.
     */
    public static function calcularVentas90(): void
    {
        $d = KplSincronizar::dato(KplSincronizar::DATO_PRODUCTOS);
        $ids = array_values(array_filter(array_map('intval', array_keys((array) ($d['productos'] ?? [])))));
        $v = [];
        if ($ids !== []) {
            $p = _DB_PREFIX_;
            foreach (Db::getInstance()->executeS(
                "SELECT od.product_id, SUM(od.product_quantity) AS n FROM {$p}order_detail od
                 INNER JOIN {$p}orders o ON o.id_order = od.id_order
                 WHERE o.valid = 1 AND o.date_add >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                   AND od.product_id IN (" . implode(',', $ids) . ')
                 GROUP BY od.product_id'
            ) ?: [] as $f) {
                $v[(int) $f['product_id']] = (int) $f['n'];
            }
        }
        KplSincronizar::guardarDato(self::DATO_VENTAS, ['t' => time(), 'v' => $v]);
        self::$ventas = $v;
    }

    /** Las marcas prioritarias de la pantalla (nombres separados por comas), como claves de marca. @return string[] */
    public static function marcasPrioritarias(): array
    {
        $salida = [];
        foreach (explode(',', (string) Configuration::getGlobalValue(Kpypromolanding::CONF_MARCAS)) as $nombre) {
            $s = KplClaves::slug(trim($nombre));
            if ($s !== '') {
                $salida[] = $s;
            }
        }

        return $salida;
    }

    private static ?array $heroes = null;

    /**
     * La foto que sale en la tarjeta de un producto (id de imagen), decidida al sincronizar
     * (`KplHeroes`); 0 = la portada de siempre.
     */
    public static function heroe(int $idProducto): int
    {
        if (self::$heroes === null) {
            $d = KplSincronizar::dato(KplHeroes::DATO);
            self::$heroes = is_array($d) ? $d : [];
        }

        return (int) (self::$heroes[$idProducto] ?? 0);
    }

    /** Una clave del resumen (viva, programada o terminada), o null si no la conoce. */
    public static function clave(string $clave): ?array
    {
        return self::resumen()['claves'][$clave] ?? null;
    }

    /** Las claves vivas que se listan, en orden, filtradas por mascota si se pide. */
    public static function vivas(string $mascota = '', string $tipo = ''): array
    {
        $r = self::resumen();
        $salida = [];
        foreach ($r['orden'] as $clave) {
            $c = $r['claves'][$clave] ?? null;
            if ($c === null || ($tipo !== '' && $c['tipo'] !== $tipo) || !self::sirveParaMascota($c, $mascota)) {
                continue;
            }
            $salida[$clave] = $c;
        }

        return $salida;
    }

    /** Las claves vivas de una marca (para su landing): la general primero, las concretas después. */
    public static function deMarca(int $idManufacturer): array
    {
        $salida = [];
        foreach (self::vivas() as $clave => $c) {
            if ((int) $c['id_manufacturer'] === $idManufacturer) {
                $salida[$clave] = $c;
            }
        }
        uasort($salida, static fn (array $a, array $b): int => [$b['n']['todos']] <=> [$a['n']['todos']]);

        return $salida;
    }

    /** ¿Tiene esta clave algo para esta mascota? ('' = para cualquiera) */
    public static function sirveParaMascota(array $c, string $mascota): bool
    {
        if ($mascota === '') {
            return true;
        }

        return (int) ($c['n'][$mascota] ?? 0) > 0;
    }

    /**
     * Los tipos con algo que enseñar para una mascota, en orden, con sus cuentas: los que no tienen
     * nada ese mes no salen (se esconden solos).
     *
     * @return array<string, array{tipo: string, n: int, url: string, nombre: string, marcas: string[]}>
     */
    public static function tipos(string $mascota = ''): array
    {
        $r = self::resumen();
        $salida = [];
        foreach (KplClaves::ORDEN_TIPOS as $tipo) {
            $d = $r['tipos'][$tipo] ?? null;
            if ($d === null) {
                continue;
            }
            $n = (int) ($d[$mascota !== '' ? $mascota : 'todos'] ?? 0);
            if ($n <= 0) {
                continue;
            }
            $salida[$tipo] = [
                'tipo' => $tipo,
                'n' => $n,
                'nombre' => KplClaves::NOMBRE_TIPO[$tipo],
                'url' => self::urlTipo($tipo, $mascota),
                'marcas' => array_values(array_filter((array) $d['marcas'], static fn (string $k) => isset($r['claves'][$k]) && self::sirveParaMascota($r['claves'][$k], $mascota))),
            ];
        }

        return $salida;
    }

    /** Cuántas promociones vivas hay para una mascota. */
    public static function cuantas(string $mascota = ''): int
    {
        return count(self::vivas($mascota));
    }

    /** Los ids de «lo más rebajado» para una mascota. @return int[] */
    public static function top(string $mascota = ''): array
    {
        $r = self::resumen();

        return array_map('intval', (array) ($r['top'][$mascota !== '' ? $mascota : 'todos'] ?? []));
    }

    // ---------- direcciones ----------

    public static function urlLanding(): string
    {
        return Context::getContext()->link->getModuleLink('kpypromolanding', 'landing', [], true);
    }

    public static function urlTodas(): string
    {
        return Context::getContext()->link->getModuleLink('kpypromolanding', 'todas', [], true);
    }

    public static function urlTipo(string $tipo, string $mascota = ''): string
    {
        $trozo = KplClaves::TIPO_URL[$tipo] ?? $tipo;
        $url = Context::getContext()->link->getModuleLink('kpypromolanding', 'tipo', ['tipo' => $trozo], true);
        if ($mascota !== '' && ($m = array_search($mascota, KplClaves::MASCOTAS, true)) !== false) {
            $url = rtrim($url, '/') . '/' . $m;
        }

        return $url;
    }

    public static function urlClave(string $clave): string
    {
        return Context::getContext()->link->getModuleLink('kpypromolanding', 'promo', ['clave' => $clave], true);
    }

    /**
     * Las claves con sus direcciones puestas: `url` (su página, con el filtro de mascota si lo hay)
     * y `url_cupon` (la misma con `?kpc=CODIGO`, que deja el cupón puesto al entrar).
     */
    public static function conUrls(array $claves, string $mascota = ''): array
    {
        foreach ($claves as $k => $c) {
            $url = self::urlClave((string) $c['clave']);
            $claves[$k]['url'] = $mascota !== '' && self::sirveParaMascota($c, $mascota) ? self::conMascota($url, $mascota) : $url;
            $claves[$k]['url_cupon'] = '';
            if (($c['tipo'] ?? '') === 'cupon' && (string) ($c['codigo'] ?? '') !== '') {
                $claves[$k]['url_cupon'] = $claves[$k]['url'] . (str_contains($claves[$k]['url'], '?') ? '&' : '?') . 'kpc=' . rawurlencode((string) $c['codigo']);
            }
            // Con el filtro de mascota, el saco de esa mascota (y no el de perro, o los dos apilados).
            if ($mascota !== '' && !empty($c['imagenes_' . $mascota])) {
                $claves[$k]['imagenes'] = (array) $c['imagenes_' . $mascota];
                $claves[$k]['lado'] = false;
            }
        }

        return $claves;
    }

    /** La imagen de una clave para una mascota (la suya si la hay; si no, la general), o vacía. */
    public static function imagenPara(array $c, string $mascota): string
    {
        if ($mascota !== '' && !empty($c['imagenes_' . $mascota][0])) {
            return (string) $c['imagenes_' . $mascota][0];
        }

        return (string) ($c['imagenes'][0] ?? '');
    }

    /** La misma dirección con el filtro de mascota (`?mascota=perro`) o sin él. */
    public static function conMascota(string $url, string $mascota): string
    {
        $partes = parse_url($url);
        $query = [];
        if (!empty($partes['query'])) {
            parse_str($partes['query'], $query);
        }
        unset($query['mascota'], $query['page']);
        if ($mascota !== '') {
            $query['mascota'] = $mascota;
        }
        $base = ($partes['scheme'] ?? 'https') . '://' . ($partes['host'] ?? '') . ($partes['path'] ?? '/');

        return $base . ($query !== [] ? '?' . http_build_query($query) : '');
    }

    /** La mascota pedida en la visita ('perro', 'gato' o ''), venga por la ruta o por `?mascota=`. */
    public static function mascotaPedida(): string
    {
        $m = (string) Tools::getValue('mascota', '');
        if (isset(KplClaves::MASCOTAS[$m])) {
            return KplClaves::MASCOTAS[$m];
        }

        return in_array($m, ['perro', 'gato'], true) ? $m : '';
    }
}
