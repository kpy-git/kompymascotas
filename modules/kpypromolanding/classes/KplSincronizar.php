<?php
/**
 * El corazón del módulo: deja la landing y las categorías como dicen las promociones.
 *
 * Se ejecuta cuando `kpycoupons` rehace el reparto (al guardar una promoción y cuando una empieza
 * o termina: hook `actionKpyCouponsReparto`), al instalar y desde la pantalla («Sincronizar
 * ahora»). Es **idempotente**: se puede repetir cuantas veces haga falta y deja siempre el mismo
 * resultado. No calcula a qué productos llega una promoción ni en qué estado está (eso es de
 * `kpycoupons`): lo pregunta por su hook de consulta `actionKpyCouponsPromociones`
 * (`KplPromociones`, una llamada por sincronización) y lo coloca. De `kpycoupons` no se lee
 * ninguna tabla; si nadie contesta al hook, no se toca nada.
 *
 * Qué hace, por pasos:
 *   1. Pregunta las promociones (con su estado, su cajón, sus artículos y sus parejas de regalo) y
 *      completa sus artículos con la marca, la mascota y las ventas de cada producto (catálogo).
 *   2. Calcula la clave de cada promoción (o respeta la puesta a mano en el paso 6).
 *   3. Se asegura de que existe la categoría de cada clave viva o programada.
 *   4. Llena cada categoría con los productos de las promociones **vivas** de su clave (la unión,
 *      si hay dos), la enciende si tiene algo y la apaga si no. Las categorías que ya no alimenta
 *      ninguna promoción viva se vacían y se apagan; no se borran.
 *   5. Las categorías permanentes (kilos gratis, regalos, outlet) se llenan por sus reglas.
 *   6. Recorta las imágenes que necesiten los banners (logo y saco de cada promoción viva).
 *   7. Escribe el resumen de la landing (`kpy_promo_dato`, nombre `resumen`): tipos con sus
 *      cuentas por mascota, marcas por tipo, banners con sus textos e imágenes, lo más rebajado.
 */
class KplSincronizar
{
    public const CONF_FEATURE = 'KPL_FEATURE_MASCOTA';
    public const CONF_PERRO = 'KPL_VALOR_PERRO';
    public const CONF_GATO = 'KPL_VALOR_GATO';
    public const DATO_RESUMEN = 'resumen';
    /** Por producto, sus promociones vivas por tipo (para las etiquetas de campaña). */
    public const DATO_PRODUCTOS = 'productos_promo';

    /** @var array<string, string> lo que ha pasado, para la pantalla y el registro */
    private array $avisos = [];

    /**
     * Todo, de una vez. Devuelve un resumen de lo hecho.
     *
     * @return array{claves: int, productos: int, imagenes: int, avisos: string[]}
     */
    public static function todo(int $idTienda, bool $conImagenes = true): array
    {
        $s = new self();

        return $s->ejecutar($idTienda, $conImagenes);
    }

    /** Como `todo()`, con cerrojo en la base de datos: dos visitas a la vez no lo hacen dos veces. */
    public static function conCerrojo(int $idTienda, bool $conImagenes = true): ?array
    {
        $db = Db::getInstance();
        $nombre = pSQL(_DB_NAME_ . '_kpypromolanding');
        if (!(int) $db->getValue("SELECT GET_LOCK('{$nombre}', 0)")) {
            return null;
        }
        try {
            return self::todo($idTienda, $conImagenes);
        } finally {
            $db->getValue("SELECT RELEASE_LOCK('{$nombre}')");
        }
    }

    private function ejecutar(int $idTienda, bool $conImagenes): array
    {
        $mascota = self::caracteristicaMascota();
        $promos = $this->promociones($idTienda);
        if ($promos === null) {
            // Nadie contesta al hook (kpycoupons apagado, o anterior a la 3.0.0): no se toca nada.
            // Una lista vacía de verdad sí vacía las categorías; una ausencia de respuesta, nunca.
            $aviso = 'kpycoupons no contesta a ' . KplPromociones::HOOK . ' (¿apagado, o anterior a la 3.0.0?): la landing se deja como estaba';
            $this->avisos['kpycoupons'] = $aviso;
            PrestaShopLogger::addLog('kpypromolanding: ' . $aviso, 2, null, 'KplSincronizar', 0, true);

            return ['claves' => 0, 'productos' => 0, 'imagenes' => 0, 'avisos' => array_values($this->avisos)];
        }
        $articulos = $this->articulos($promos, $idTienda, $mascota);

        // 2. La clave y los datos de cada promoción (marca, mascota, productos).
        $nuestras = $this->nuestras();
        $porClave = [];   // clave => datos agregados de las promociones de esa clave
        $cajon = ['productos' => [], 'vivas' => [], 'programadas' => [], 'terminadas' => []];
        foreach ($promos as $id => $promo) {
            $suyos = $articulos[$id] ?? ['productos' => [], 'marcas' => [], 'mascotas' => []];
            if (!empty($promo['cajon'])) {
                // Al cajón «Outlet y liquidación» (abajo, con las permanentes): sin clave ni banner propios.
                $claveCajon = KplClaves::PERMANENTES['outlet'];
                $promos[$id] += ['clave' => $claveCajon, 'id_manufacturer' => 0, 'marca' => '', 'mascota' => '', 'productos' => $suyos['productos'], 'n_mascota' => $suyos['n_mascota'] ?? []];
                $this->apuntarPromo($id, $claveCajon, $nuestras[$id] ?? null);
                $cajon[$promo['estado'] === 'viva' ? 'vivas' : ($promo['estado'] === 'programada' ? 'programadas' : 'terminadas')][] = $id;
                if ($promo['estado'] === 'viva') {
                    foreach ($suyos['productos'] as $idProducto => $datos) {
                        if (!isset($cajon['productos'][$idProducto]) || $datos['pct'] > $cajon['productos'][$idProducto]['pct']) {
                            $cajon['productos'][$idProducto] = $datos;
                        }
                    }
                }
                continue;
            }
            $idMarca = count($suyos['marcas']) === 1 ? (int) array_key_first($suyos['marcas']) : $this->marcaDelAmbito($promo['ambitos']);
            $nombreMarca = $idMarca > 0 ? ($suyos['marcas'][$idMarca] ?? $this->nombreMarca($idMarca)) : '';
            $mascotaPromo = count($suyos['mascotas']) === 1 && $suyos['productos'] !== [] ? (string) array_key_first($suyos['mascotas']) : '';
            $propia = $nuestras[$id] ?? null;
            $clave = $propia && !empty($propia['clave_manual']) && KplClaves::claveValida((string) $propia['clave'])
                ? (string) $propia['clave']
                : KplClaves::clave($idMarca > 0 ? KplClaves::slug($nombreMarca) : null, (string) $promo['tipo'], $mascotaPromo, (string) $promo['codigo']);
            $promos[$id] += ['clave' => $clave, 'id_manufacturer' => $idMarca, 'marca' => $nombreMarca, 'mascota' => $mascotaPromo, 'productos' => $suyos['productos'], 'n_mascota' => $suyos['n_mascota'] ?? []];
            $this->apuntarPromo($id, $clave, $propia);

            if (!isset($porClave[$clave])) {
                $porClave[$clave] = [
                    'clave' => $clave, 'tipo' => (string) $promo['tipo'], 'id_manufacturer' => $idMarca, 'marca' => $nombreMarca,
                    'mascota' => $mascotaPromo, 'productos' => [], 'vivas' => [], 'programadas' => [], 'terminadas' => [], 'orden' => 0, 'listar' => 1,
                    'titulo' => '', 'texto' => '', 'imagen' => '',
                ];
            }
            $c = &$porClave[$clave];
            if ($mascotaPromo !== $c['mascota']) {
                $c['mascota'] = ''; // dos promos de la misma clave con mascotas distintas: para los dos
            }
            if ($promo['estado'] === 'viva') {
                $c['vivas'][] = $id;
                foreach ($suyos['productos'] as $idProducto => $datos) {
                    if (!isset($c['productos'][$idProducto]) || $datos['pct'] > $c['productos'][$idProducto]['pct']) {
                        $c['productos'][$idProducto] = $datos;
                    }
                }
            } elseif ($promo['estado'] === 'programada') {
                $c['programadas'][] = $id;
            } else {
                $c['terminadas'][] = $id;
            }
            if ($propia) {
                $c['orden'] = max($c['orden'], (int) $propia['orden']);
                $c['listar'] = $c['listar'] && (int) $propia['listar'] === 1 ? 1 : 0;
                foreach (['titulo', 'texto', 'imagen'] as $campo) {
                    if ($c[$campo] === '' && (string) $propia[$campo] !== '') {
                        $c[$campo] = (string) $propia[$campo];
                    }
                }
            }
            unset($c);
        }

        // 3 y 4. Categorías de las claves con promoción viva o programada; las demás, vacías y apagadas.
        $nProductos = 0;
        $tocadas = [];
        $nuestrasCategorias = KplCategorias::todas();
        foreach ($porClave as $clave => $c) {
            if ($c['vivas'] === [] && $c['programadas'] === []) {
                continue; // terminadas: su categoría se vacía abajo, con las demás
            }
            if ($c['productos'] === [] && $c['programadas'] === [] && !isset($nuestrasCategorias[$clave])) {
                continue; // viva pero sin ningún producto (se los llevó otra): sin categoría hasta que tenga algo
            }
            $idCategoria = KplCategorias::asegurar($clave, [
                'nombre' => KplClaves::nombreCategoria($c['marca'], $c['tipo'], $c['mascota']),
                'tipo' => $c['tipo'],
                'id_manufacturer' => $c['id_manufacturer'],
                'mascota' => $c['mascota'],
                'descripcion' => KplTextos::titulo(['tipo' => $c['tipo'], 'marca' => $c['marca'], 'mascota' => $c['mascota'], 'pct' => $this->pctDe($c, $promos), 'codigo' => $this->codigoDe($c, $promos), 'kpc' => true]),
                // El % con el que `KplBuscador` ordena los listados (el mayor de sus promociones vivas): se
                // deja escrito aquí para no cruzar nada de kpycoupons en cada visita.
                'pct' => $this->pctVivas($c, $promos),
            ]);
            $ordenados = $c['productos'];
            uasort($ordenados, static fn (array $a, array $b): int => [$b['pct'], $b['ventas']] <=> [$a['pct'], $a['ventas']]);
            $nProductos += KplCategorias::llenar($clave, $idCategoria, array_keys($ordenados));
            $tocadas[$clave] = true;
            $porClave[$clave]['id_category'] = $idCategoria;
        }

        // 5. Las permanentes.
        foreach (KplClaves::PERMANENTES as $tipo => $clave) {
            $idCategoria = KplCategorias::asegurar($clave, [
                'nombre' => KplClaves::nombreCategoria('', $tipo, ''),
                'tipo' => $tipo, 'permanente' => true,
                'descripcion' => KplTextos::titulo(['tipo' => $tipo, 'marca' => '', 'mascota' => '', 'pct' => 0, 'codigo' => '']),
                // En el cajón «Outlet y liquidación», el % mayor de sus liquidaciones y outlets vivos; 0 en lo demás.
                'pct' => $tipo === 'outlet' ? $this->pctVivas(['vivas' => $cajon['vivas']], $promos) : 0.0,
            ]);
            $productos = $this->permanente($tipo, $idTienda);
            $datos = $this->datosDe($productos, $mascota);
            $vivas = $productos !== [] ? [0] : [];
            if ($tipo === 'outlet' && $cajon['productos'] !== []) {
                // Las liquidaciones y outlets de kpycoupons delante (más rebaja primero, luego más
                // vendido), y después los formatos OUTLET del catálogo.
                $deCajon = $cajon['productos'];
                uasort($deCajon, static fn (array $a, array $b): int => [$b['pct'], $b['ventas']] <=> [$a['pct'], $a['ventas']]);
                $datos = $deCajon + $datos;
                $productos = array_keys($datos);
                $vivas = array_merge([0], $cajon['vivas']);
            }
            $nProductos += KplCategorias::llenar($clave, $idCategoria, $productos);
            $tocadas[$clave] = true;
            $porClave[$clave] = [
                'clave' => $clave, 'tipo' => $tipo, 'id_manufacturer' => 0, 'marca' => '', 'mascota' => '', 'id_category' => $idCategoria,
                'productos' => $datos,
                'vivas' => $vivas, 'programadas' => $tipo === 'outlet' ? $cajon['programadas'] : [], 'terminadas' => $tipo === 'outlet' ? $cajon['terminadas'] : [],
                'orden' => 0, 'listar' => 1, 'titulo' => '', 'texto' => '', 'imagen' => '', 'permanente' => true,
            ];
        }

        // La foto de cada producto en las tarjetas (un solo saco de pie, nunca una foto de transición).
        if ($conImagenes) {
            $enPromo = [];
            foreach ($porClave as $c) {
                if ($c['vivas'] !== []) {
                    $enPromo += array_fill_keys(array_keys($c['productos']), true);
                }
            }
            try {
                self::guardarDato(KplHeroes::DATO, KplHeroes::deProductos(array_keys($enPromo)));
            } catch (Throwable $e) {
                $this->avisos['heroes'] = 'fotos de las tarjetas: ' . $e->getMessage();
                PrestaShopLogger::addLog('kpypromolanding, fotos de las tarjetas: ' . $e->getMessage(), 2, null, 'KplSincronizar', 0, true);
            }
        }

        // Las categorías nuestras que ya no alimenta nada: vacías y apagadas (nunca borradas).
        foreach (KplCategorias::todas() as $clave => $f) {
            if (!isset($tocadas[$clave]) && KplCategorias::existe((int) $f['id_category'])) {
                KplCategorias::llenar((string) $clave, (int) $f['id_category'], []);
            }
        }

        // Lo nuestro de promociones que ya no existen en Kompy · Promociones, fuera. Las que existen son
        // todas las que contesta el hook, encendidas o no, se vean o no: un cupón oculto o solo por canal no
        // está en `$promos` (no va a la landing) pero conserva lo suyo del paso 6 (título, foto, campañas…)
        // para cuando vuelva a «en toda la tienda» (revisión del 26/10/2026).
        $idsPromo = array_map('intval', array_keys((array) KplPromociones::todas($idTienda)));
        Db::getInstance()->execute(
            'DELETE FROM ' . _DB_PREFIX_ . 'kpy_promo_promo' . ($idsPromo !== [] ? ' WHERE id_kpy_coupon NOT IN (' . implode(',', $idsPromo) . ')' : '')
        );

        // 6 y 7. Imágenes y resumen.
        $nImagenes = 0;
        $resumen = $this->resumen($porClave, $promos, $mascota, $conImagenes, $nImagenes);
        self::guardarDato(self::DATO_RESUMEN, $resumen);
        // Y lo que necesitan las etiquetas de campaña: de cada producto, sus promociones vivas por tipo.
        self::guardarDato(self::DATO_PRODUCTOS, $this->productosPromo($promos, $porClave, $nuestras));
        KplCategorias::limpiarCache();

        return ['claves' => count($tocadas), 'productos' => $nProductos, 'imagenes' => $nImagenes, 'avisos' => array_values($this->avisos)];
    }

    // ---------------------------------------------------------------------------------------

    /**
     * Las promociones de Kompy · Promociones, por el hook de consulta de kpycoupons
     * (`KplPromociones`), con lo que la landing añade a cada una: su estado reducido a viva,
     * programada o terminada (una agotada o apagada ha terminado: su categoría se vacía), su tipo en
     * la landing y si va al cajón.
     *
     * @return ?array<int, array> null si nadie contesta (entonces no se sincroniza nada)
     */
    private function promociones(int $idTienda): ?array
    {
        $filas = KplPromociones::todas($idTienda, true);
        if ($filas === null) {
            return null;
        }
        $salida = [];
        foreach ($filas as $id => $f) {
            $f['estado'] = in_array($f['estado'], ['viva', 'programada'], true) ? $f['estado'] : 'terminada';
            // Lo que no se enseña a todo el mundo no está en la landing (kpycoupons 3.1.0): un cupón
            // oculto (solo por enlace) o solo para quien llega de un canal (Google, email, redes). El
            // «para todos» (auto_web) sí: lo ve cualquiera en la ficha de sus productos.
            if (!KplPromociones::enLaLanding($f)) {
                continue;
            }
            $f['desde'] = $f['fecha_desde'];
            $f['hasta'] = $f['fecha_hasta'];
            // El «regalo con la compra» entra en la landing por lo que regala (lo dice kpycoupons en
            // `regalo_clase`): si regala más de lo mismo (el saco pequeño de su ficha, «12 kg + 3 kg
            // gratis»), como «Kilos gratis» («Boske · Kilos gratis», `/promociones/kilos-gratis`); si
            // regala otra cosa (un accesorio, unas latas, un snack), como «Regalos» («Boske · Regalos»).
            if ($f['tipo'] === 'regalo') {
                $f['tipo_kpc'] = 'regalo';
                $f['tipo'] = $f['regalo_clase'] === 'regalo' ? 'regalos' : 'kilos_gratis';
            }
            // Una Liquidación o un Outlet no es una promoción con banner: va al cajón «Outlet y
            // liquidación», con todas las marcas, sin categoría ni banner propios (`cajon`, lo decide kpycoupons).
            $esCajon = $f['tipo'] === 'special_price' && $f['cajon'] !== '';
            if ($esCajon) {
                $f['tipo_kpc'] = 'special_price';
                $f['tipo'] = 'outlet';
            }
            $f['cajon'] = $esCajon;
            $salida[(int) $id] = $f;
        }

        return $salida;
    }

    /**
     * Los tamaños de un regalo de kpycoupons para el texto del banner: «15 kg + 3 kg, 12 kg + 3 kg y
     * 10 kg + 2,5 kg» (hasta tres parejas distintas, de la más grande a la más pequeña). Solo en
     * «kilos gratis» (el pequeño de la misma ficha); vacío en lo demás. Las parejas llegan en la
     * fila pública (`parejas`); los nombres de los formatos, del catálogo.
     */
    private function ejemplosRegalo(array $c, array $promos): string
    {
        if (($c['tipo'] ?? '') !== 'kilos_gratis' || !empty($c['permanente'])) {
            return '';
        }
        $parejas = [];
        foreach ((array) ($c['vivas'] ?? []) as $id) {
            $promo = $promos[(int) $id] ?? null;
            if ($promo === null || ($promo['tipo_kpc'] ?? '') !== 'regalo') {
                continue;
            }
            foreach ((array) ($promo['parejas'] ?? []) as $pr) {
                if ($pr['clave'] > 0 && $pr['r_ipa'] > 0 && $pr['n'] === 1 && $pr['r_id_product'] === $pr['id_product']) {
                    $parejas[] = $pr;
                }
            }
        }
        if ($parejas === []) {
            return '';
        }
        $nombres = $this->nombresDeFormatos(array_merge(array_column($parejas, 'clave'), array_column($parejas, 'r_ipa')));
        $pares = [];
        foreach ($parejas as $pr) {
            $nombreDe = (string) ($nombres[$pr['clave']] ?? '');
            $de = KplTextos::kilos($nombreDe);
            $da = KplTextos::kilos((string) ($nombres[$pr['r_ipa']] ?? ''));
            if ($de !== '' && $da !== '' && stripos($nombreDe, 'pack') === false) {
                $pares[$de . ' + ' . $da] = (float) str_replace(',', '.', $de);
            }
        }
        arsort($pares);

        return KplTextos::lista(array_slice(array_keys($pares), 0, 3));
    }

    /**
     * Los nombres de unos formatos («Saco 12 Kg»: sus atributos juntos, en el idioma de la tienda),
     * por id_product_attribute.
     *
     * @param int[] $ipas
     *
     * @return array<int, string>
     */
    private function nombresDeFormatos(array $ipas): array
    {
        $ipas = array_values(array_unique(array_filter(array_map('intval', $ipas))));
        if ($ipas === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $salida = [];
        foreach (Db::getInstance()->executeS(
            "SELECT pac.id_product_attribute, GROUP_CONCAT(al.name SEPARATOR ' ') AS nombre
             FROM {$p}product_attribute_combination pac
             INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = " . (int) Configuration::get('PS_LANG_DEFAULT') . '
             WHERE pac.id_product_attribute IN (' . implode(',', $ipas) . ')
             GROUP BY pac.id_product_attribute'
        ) ?: [] as $f) {
            $salida[(int) $f['id_product_attribute']] = (string) $f['nombre'];
        }

        return $salida;
    }

    /**
     * Los productos de cada promoción (sus `items`: el reparto de `kpycoupons`, que llega en la fila
     * pública), con marca, mascota y ventas del catálogo. Solo los que se venden: activos y visibles.
     *
     * @param array<int, array> $promos
     *
     * @return array<int, array{productos: array<int, array>, marcas: array<int, string>, mascotas: array<string, int>, n_mascota: array<string, int>}>
     */
    private function articulos(array $promos, int $idTienda, array $mascota): array
    {
        $ids = [];
        foreach ($promos as $promo) {
            foreach ((array) ($promo['items'] ?? []) as $idProducto) {
                $ids[(int) $idProducto] = true;
            }
        }
        unset($ids[0]);
        if ($ids === []) {
            return [];
        }
        $p = _DB_PREFIX_;
        $filas = Db::getInstance()->executeS(
            "SELECT p.id_product, MAX(p.id_manufacturer) AS id_manufacturer, MAX(m.name) AS marca,
                    MAX(IFNULL(s.quantity, 0)) AS ventas, MAX(fp.id_feature_value) AS mascota
             FROM {$p}product p
             INNER JOIN {$p}product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = " . (int) $idTienda . "
             LEFT JOIN {$p}manufacturer m ON m.id_manufacturer = p.id_manufacturer
             LEFT JOIN {$p}product_sale s ON s.id_product = p.id_product
             LEFT JOIN {$p}feature_product fp ON fp.id_product = p.id_product AND fp.id_feature = " . (int) $mascota['feature'] . '
                  AND fp.id_feature_value IN (' . (int) $mascota['perro'] . ', ' . (int) $mascota['gato'] . ')
             WHERE p.id_product IN (' . implode(',', array_keys($ids)) . ")
               AND ps.active = 1 AND ps.visibility IN ('both', 'catalog')
             GROUP BY p.id_product"
        ) ?: [];
        $datos = [];
        foreach ($filas as $f) {
            $datos[(int) $f['id_product']] = $f;
        }
        $salida = [];
        foreach ($promos as $id => $promo) {
            $suyos = array_values(array_unique(array_map('intval', (array) ($promo['items'] ?? []))));
            sort($suyos);
            foreach ($suyos as $idProducto) {
                $f = $datos[$idProducto] ?? null;
                if ($f === null) {
                    continue; // no se vende (inactivo o fuera del catálogo)
                }
                $m = (int) $f['mascota'] === (int) $mascota['perro'] ? 'perro' : ((int) $f['mascota'] === (int) $mascota['gato'] ? 'gato' : '');
                $salida[$id] ??= ['productos' => [], 'marcas' => [], 'mascotas' => [], 'n_mascota' => ['perro' => 0, 'gato' => 0]];
                $salida[$id]['productos'][$idProducto] = [
                    'pct' => (float) $promo['porcentaje'],
                    'ventas' => (int) $f['ventas'],
                    'mascota' => $m,
                    'id_manufacturer' => (int) $f['id_manufacturer'],
                ];
                if ((int) $f['id_manufacturer'] > 0) {
                    $salida[$id]['marcas'][(int) $f['id_manufacturer']] = (string) $f['marca'];
                }
                if ($m !== '') {
                    $salida[$id]['mascotas'][$m] = ($salida[$id]['mascotas'][$m] ?? 0) + 1;
                    ++$salida[$id]['n_mascota'][$m];
                }
            }
        }

        return $salida;
    }

    /**
     * La marca de una promoción por sus ámbitos: si pide una sola marca, esa; si va por lista de
     * productos o formatos y todos son de la misma marca, esa (así una promoción que se ha quedado
     * sin productos, porque se los llevó un Special Price, conserva su clave de marca).
     */
    private function marcaDelAmbito(array $ambitos): int
    {
        $marcas = [];
        $productos = [];
        $combinaciones = [];
        foreach ($ambitos as $a) {
            $tipo = (string) ($a['tipo'] ?? '');
            if ($tipo === 'marca') {
                $marcas[(int) $a['id']] = true;
            } elseif ($tipo === 'producto') {
                $productos[] = (int) $a['id'];
            } elseif ($tipo === 'combinacion') {
                $combinaciones[] = (int) $a['id'];
            }
        }
        if ($marcas !== []) {
            return count($marcas) === 1 ? (int) array_key_first($marcas) : 0;
        }
        if ($productos === [] && $combinaciones === []) {
            return 0;
        }
        $p = _DB_PREFIX_;
        $partes = [];
        if ($productos !== []) {
            $partes[] = "SELECT DISTINCT id_manufacturer FROM {$p}product WHERE id_product IN (" . implode(',', $productos) . ')';
        }
        if ($combinaciones !== []) {
            $partes[] = "SELECT DISTINCT pr.id_manufacturer FROM {$p}product_attribute pa INNER JOIN {$p}product pr ON pr.id_product = pa.id_product WHERE pa.id_product_attribute IN (" . implode(',', $combinaciones) . ')';
        }
        $ids = array_unique(array_map('intval', array_column(Db::getInstance()->executeS(implode(' UNION ', $partes)) ?: [], 'id_manufacturer')));

        return count($ids) === 1 ? (int) reset($ids) : 0;
    }

    private function nombreMarca(int $idMarca): string
    {
        return (string) Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'manufacturer WHERE id_manufacturer = ' . (int) $idMarca);
    }

    /** Lo nuestro de cada promoción (`kpy_promo_promo`), por id. */
    private function nuestras(): array
    {
        $salida = [];
        foreach (Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_promo') ?: [] as $f) {
            $salida[(int) $f['id_kpy_coupon']] = $f;
        }

        return $salida;
    }

    /** Deja escrita la clave calculada de una promoción (sin pisar la puesta a mano). */
    private function apuntarPromo(int $id, string $clave, ?array $propia): void
    {
        $db = Db::getInstance();
        if ($propia === null) {
            $db->insert('kpy_promo_promo', ['id_kpy_coupon' => $id, 'clave' => pSQL($clave), 'date_upd' => date('Y-m-d H:i:s')]);
        } elseif (empty($propia['clave_manual']) && (string) $propia['clave'] !== $clave) {
            $db->update('kpy_promo_promo', ['clave' => pSQL($clave), 'date_upd' => date('Y-m-d H:i:s')], 'id_kpy_coupon = ' . $id);
        }
    }

    /**
     * Los productos de una categoría permanente:
     *   - kilos gratis: algún formato a la venta cuyo nombre diga «gratis» («Saco 15 kg + 3 kg Gratis»);
     *   - regalos: algún pack con regalo (`kpy_packs.is_gift = 1`) a la venta;
     *   - outlet: algún formato a la venta cuyo nombre diga «outlet».
     * Siempre productos activos y visibles.
     *
     * @return int[]
     */
    private function permanente(string $tipo, int $idTienda): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $base = "INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = " . (int) $idTienda . "
                 AND ps.active = 1 AND ps.visibility IN ('both', 'catalog')";
        $activoFormato = $this->hayColumna('kpy_product_attribute', 'active')
            ? " AND NOT EXISTS (SELECT 1 FROM {$p}kpy_product_attribute kpa WHERE kpa.id_product_attribute = pa.id_product_attribute AND kpa.active = 0)"
            : '';
        switch ($tipo) {
            case 'kilos_gratis':
                $sql = "SELECT DISTINCT pr.id_product FROM {$p}product pr {$base}
                        INNER JOIN {$p}product_attribute pa ON pa.id_product = pr.id_product
                        INNER JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                        INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = " . (int) Configuration::get('PS_LANG_DEFAULT') . "
                        WHERE (al.name LIKE '%gratis%' OR al.name LIKE '%regalo%') AND al.name NOT LIKE '%outlet%'{$activoFormato}";
                break;
            case 'regalos':
                if (!$this->hayTabla('kpy_packs')) {
                    return [];
                }
                $sql = "SELECT DISTINCT pr.id_product FROM {$p}product pr {$base}
                        INNER JOIN {$p}product_attribute pa ON pa.id_product = pr.id_product
                        INNER JOIN {$p}kpy_packs k ON k.id_product_pack = CONCAT(pa.id_product, '-', pa.id_product_attribute) AND k.is_gift = 1
                        WHERE 1{$activoFormato}";
                break;
            case 'outlet':
                $sql = "SELECT DISTINCT pr.id_product FROM {$p}product pr {$base}
                        INNER JOIN {$p}product_attribute pa ON pa.id_product = pr.id_product
                        INNER JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                        INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = " . (int) Configuration::get('PS_LANG_DEFAULT') . "
                        WHERE al.name LIKE '%outlet%'{$activoFormato}";
                break;
            default:
                return [];
        }
        try {
            $ids = array_map('intval', array_column($db->executeS($sql) ?: [], 'id_product'));
        } catch (Throwable $e) {
            $this->avisos[$tipo] = 'categoría ' . $tipo . ': ' . $e->getMessage();

            return [];
        }
        if ($ids === []) {
            return [];
        }
        // Los más vendidos primero.
        $ventas = [];
        foreach ($db->executeS("SELECT id_product, quantity FROM {$p}product_sale WHERE id_product IN (" . implode(',', $ids) . ')') ?: [] as $f) {
            $ventas[(int) $f['id_product']] = (int) $f['quantity'];
        }
        usort($ids, static fn (int $a, int $b): int => ($ventas[$b] ?? 0) <=> ($ventas[$a] ?? 0) ?: $a <=> $b);

        return $ids;
    }

    /**
     * Marca, mascota y ventas de unos productos (los de una categoría permanente), como los de una
     * promoción: así también tienen su héroe de perro y de gato.
     *
     * @param int[] $ids
     *
     * @return array<int, array{pct: float, ventas: int, mascota: string, id_manufacturer: int}>
     */
    private function datosDe(array $ids, array $mascota): array
    {
        $salida = [];
        if ($ids === []) {
            return $salida;
        }
        $p = _DB_PREFIX_;
        $filas = Db::getInstance()->executeS(
            "SELECT p.id_product, MAX(p.id_manufacturer) AS id_manufacturer, MAX(IFNULL(s.quantity, 0)) AS ventas, MAX(fp.id_feature_value) AS mascota
             FROM {$p}product p
             LEFT JOIN {$p}product_sale s ON s.id_product = p.id_product
             LEFT JOIN {$p}feature_product fp ON fp.id_product = p.id_product AND fp.id_feature = " . (int) $mascota['feature'] . '
                  AND fp.id_feature_value IN (' . (int) $mascota['perro'] . ', ' . (int) $mascota['gato'] . ')
             WHERE p.id_product IN (' . implode(',', array_map('intval', $ids)) . ')
             GROUP BY p.id_product'
        ) ?: [];
        $datos = [];
        foreach ($filas as $f) {
            $datos[(int) $f['id_product']] = $f;
        }
        foreach ($ids as $id) {
            $f = $datos[$id] ?? null;
            $m = $f === null ? '' : ((int) $f['mascota'] === (int) $mascota['perro'] ? 'perro' : ((int) $f['mascota'] === (int) $mascota['gato'] ? 'gato' : ''));
            $salida[$id] = ['pct' => 0.0, 'ventas' => (int) ($f['ventas'] ?? 0), 'mascota' => $m, 'id_manufacturer' => (int) ($f['id_manufacturer'] ?? 0)];
        }

        return $salida;
    }

    /**
     * El resumen que lee la landing: todo lo que hace falta para pintarla sin calcular nada.
     *
     * @param array<string, array> $porClave
     */
    private function resumen(array $porClave, array $promos, array $mascota, bool $conImagenes, int &$nImagenes): array
    {
        $ahora = time();
        $claves = [];
        $tipos = [];
        foreach (KplClaves::ORDEN_TIPOS as $t) {
            $tipos[$t] = ['todos' => 0, 'perro' => 0, 'gato' => 0, 'marcas' => []];
        }
        $top = ['todos' => [], 'perro' => [], 'gato' => []];
        foreach ($porClave as $clave => $c) {
            $viva = $c['vivas'] !== [] && $c['productos'] !== [];
            $pct = $this->pctDe($c, $promos);
            $codigo = $this->codigoDe($c, $promos);
            $fechas = $this->fechasDe($c, $promos);
            $n = ['todos' => count($c['productos']), 'perro' => 0, 'gato' => 0];
            $heroes = ['perro' => null, 'gato' => null, '' => null];
            foreach ($c['productos'] as $idProducto => $d) {
                if ($d['mascota'] !== '') {
                    ++$n[$d['mascota']];
                }
                $h = &$heroes[$d['mascota']];
                if ($h === null || $d['ventas'] > $h['ventas'] || ($d['ventas'] === $h['ventas'] && $d['pct'] > $h['pct'])) {
                    $h = ['id_product' => (int) $idProducto, 'ventas' => $d['ventas'], 'pct' => $d['pct'], 'id_manufacturer' => $d['id_manufacturer']];
                }
                unset($h);
            }
            $tamanos = '';
            foreach ($c['vivas'] as $idPromo) {
                if ($idPromo > 0 && (string) ($promos[$idPromo]['tamanos'] ?? '') !== '') {
                    $tamanos = (string) $promos[$idPromo]['tamanos'];
                    break;
                }
            }
            $datosTexto = ['tipo' => $c['tipo'], 'marca' => $c['marca'], 'mascota' => $c['mascota'], 'pct' => $pct, 'codigo' => $codigo, 'tamanos' => $tamanos, 'n' => $n['todos'], 'kpc' => empty($c['permanente']),
                'ejemplos' => $this->ejemplosRegalo($c, $promos)];
            $estado = $viva ? 'viva' : ($c['programadas'] !== [] ? 'programada' : 'terminada');
            $imagenes = [];
            $logo = '';
            $logoK = 1.0;
            $lado = false;
            $porMascota = ['perro' => [], 'gato' => []];
            if ($viva && $conImagenes) {
                [$imagenes, $logo, $lado, $porMascota, $logoK] = $this->imagenesDe($c, $heroes, $nImagenes, $promos);
            } elseif ($viva && $c['id_manufacturer'] > 0) {
                $l = KplBiblioteca::logo($c['id_manufacturer']);
                $logo = (string) ($l['url'] ?? '');
                $logoK = $l !== null ? KplBiblioteca::factorLogo((int) $l['ancho'], (int) $l['alto']) : 1.0;
            }
            $claves[$clave] = [
                'clave' => $clave,
                'tipo' => $c['tipo'],
                'tipo_url' => KplClaves::TIPO_URL[$c['tipo']] ?? $c['tipo'],
                'nombre_tipo' => KplClaves::NOMBRE_TIPO[$c['tipo']] ?? $c['tipo'],
                'id_category' => (int) ($c['id_category'] ?? 0),
                'id_manufacturer' => (int) $c['id_manufacturer'],
                'marca' => $c['marca'],
                'marca_url' => $c['id_manufacturer'] > 0 ? $this->urlMarca($c['id_manufacturer']) : '',
                'mascota' => $c['mascota'],
                'pct' => $pct,
                'pct_texto' => KplTextos::pct($pct),
                'codigo' => $codigo,
                'desde' => $fechas['desde'],
                'hasta' => $fechas['hasta'],
                'hasta_texto' => KplTextos::hasta($fechas['hasta']),
                'hasta_corta' => KplTextos::fechaCorta($fechas['hasta']),
                'n' => $n,
                'titulo' => $c['titulo'] !== '' ? $c['titulo'] : KplTextos::titulo($datosTexto),
                'texto' => $c['texto'] !== '' ? $c['texto'] : KplTextos::texto($datosTexto),
                'imagenes' => $imagenes,
                // El saco de cada mascota por separado, para cuando la página filtra por perros o gatos.
                'imagenes_perro' => $porMascota['perro'],
                'imagenes_gato' => $porMascota['gato'],
                'lado' => $lado,
                'logo' => $logo,
                'logo_k' => $logoK,
                'orden' => (int) $c['orden'],
                'listar' => (int) $c['listar'],
                'estado' => $estado,
                'permanente' => !empty($c['permanente']),
                'promos' => array_values(array_filter($c['vivas'] !== [] ? $c['vivas'] : ($c['programadas'] !== [] ? $c['programadas'] : $c['terminadas']))),
            ];
            if ($viva && (int) $c['listar'] === 1 && isset($tipos[$c['tipo']])) {
                $tipos[$c['tipo']]['todos'] += $n['todos'];
                $tipos[$c['tipo']]['perro'] += $n['perro'];
                $tipos[$c['tipo']]['gato'] += $n['gato'];
                if (empty($c['permanente'])) {
                    $tipos[$c['tipo']]['marcas'][] = $clave;
                }
                foreach ($c['productos'] as $idProducto => $d) {
                    $top['todos'][] = [$idProducto, $d['pct'], $d['ventas']];
                    if ($d['mascota'] !== '') {
                        $top[$d['mascota']][] = [$idProducto, $d['pct'], $d['ventas']];
                    }
                }
            }
        }
        // Lo más rebajado: por % y, a igualdad, lo más vendido; sin repetir producto.
        foreach ($top as $m => $lista) {
            usort($lista, static fn (array $a, array $b): int => [$b[1], $b[2]] <=> [$a[1], $a[2]]);
            $vistos = [];
            $top[$m] = [];
            foreach ($lista as $x) {
                if (!isset($vistos[$x[0]])) {
                    $vistos[$x[0]] = true;
                    $top[$m][] = $x[0];
                }
                if (count($top[$m]) >= 12) {
                    break;
                }
            }
        }
        // El orden de los banners y de las marcas: orden puesto a mano (mayor primero), luego % y luego productos.
        $ordenar = static function (array $a, array $b): int {
            return [$b['orden'], $b['pct'], $b['n']['todos']] <=> [$a['orden'], $a['pct'], $a['n']['todos']];
        };
        $vivas = array_filter($claves, static fn (array $x): bool => $x['estado'] === 'viva' && $x['listar'] === 1 && !$x['permanente']);
        uasort($vivas, $ordenar);
        foreach ($tipos as $t => $d) {
            usort($tipos[$t]['marcas'], static fn (string $a, string $b): int => $ordenar($claves[$a], $claves[$b]));
        }

        return [
            't' => $ahora,
            'fecha' => date('Y-m-d H:i:s', $ahora),
            'mascota' => $mascota,
            'tipos' => $tipos,
            'claves' => $claves,
            'orden' => array_keys($vivas),
            'top' => $top,
        ];
    }

    /**
     * Las imágenes del banner de una clave: el logo de la marca y el saco (o sacos) sin fondo.
     *
     *   - Si la promoción es de perros y gatos, el más vendido de cada uno (perro primero), apilados.
     *   - Kilos gratis: la foto del saco promocional (el Jirafa), que es la que está en ese formato
     *     y en ningún otro; si no tiene foto propia, la portada.
     *   - Regalos: el saco grande y, al lado, el producto de regalo del pack.
     *   - Si en el paso 6 se eligió otra cosa (`producto:ID`, `biblioteca:ID`), eso.
     *   - Un saco (o una lata) subido a mano para la marca y la mascota va antes que la foto del
     *     catálogo; si lleva gama, solo si la promoción la nombra (`KplBiblioteca::subida()`).
     *
     * Una foto de transición (saco viejo, flecha, saco nuevo) nunca sale de héroe: la biblioteca
     * la descarta y pasa a la siguiente foto de la ficha (`KplBiblioteca::producto()`).
     *
     * @return array{0: string[], 1: string, 2: bool, 3: array{perro: string[], gato: string[]}, 4: float}
     *         urls, url del logo, ¿al lado?, el saco de cada mascota por separado y el factor de tamaño del logo
     */
    private function imagenesDe(array $c, array $heroes, int &$nImagenes, array $promos): array
    {
        $urls = [];
        $lado = false;
        $logo = '';
        $logoK = 1.0;
        $porMascota = ['perro' => [], 'gato' => []];
        try {
            if ($c['id_manufacturer'] > 0) {
                $l = KplBiblioteca::logo($c['id_manufacturer']);
                if ($l !== null) {
                    $logo = (string) $l['url'];
                    $logoK = KplBiblioteca::factorLogo((int) $l['ancho'], (int) $l['alto']);
                    ++$nImagenes;
                }
            }
            $elegida = (string) $c['imagen'];
            if (preg_match('/^producto:(\d+)$/', $elegida, $m)) {
                $f = KplBiblioteca::producto((int) $m[1], $c['id_manufacturer'], '', $this->formatosPreferidos((string) $c['tipo'], (int) $m[1]));
                if ($f !== null) {
                    $urls[] = (string) $f['url'];
                    ++$nImagenes;
                }
            } elseif (preg_match('/^biblioteca:(\d+)$/', $elegida, $m)) {
                $f = KplBiblioteca::fila((int) $m[1]);
                if ($f !== null) {
                    $urls[] = (string) $f['url'];
                }
            }
            if ($urls === []) {
                // Los sacos (o latas) subidos a mano para esta marca y cada mascota van antes que la
                // foto del catálogo; con gama, solo si la promoción la nombra.
                $subidos = [];
                if ($c['id_manufacturer'] > 0) {
                    $texto = KplBiblioteca::conGama((int) $c['id_manufacturer']) ? $this->textoDe($c, $promos) : '';
                    foreach ($c['mascota'] === '' ? ['perro', 'gato'] : [$c['mascota']] as $m) {
                        $s = KplBiblioteca::subida((int) $c['id_manufacturer'], 'saco', $m, $texto) ?? KplBiblioteca::subida((int) $c['id_manufacturer'], 'lata', $m, $texto);
                        if ($s !== null) {
                            $subidos[$m] = (string) $s['url'];
                            $urls[] = (string) $s['url'];
                            $porMascota[$m][] = (string) $s['url'];
                        }
                    }
                }
                $candidatos = [];
                if ($c['mascota'] === '' && $heroes['perro'] !== null && $heroes['gato'] !== null) {
                    $candidatos = [$heroes['perro'], $heroes['gato']];
                } else {
                    foreach (['perro', 'gato', ''] as $m) {
                        if ($heroes[$m] !== null) {
                            $candidatos = [$heroes[$m]];
                            break;
                        }
                    }
                }
                foreach ($candidatos as $h) {
                    $idProducto = (int) $h['id_product'];
                    $mascotaHeroe = $h === $heroes['perro'] ? 'perro' : ($h === $heroes['gato'] ? 'gato' : '');
                    // La mascota que ya tiene saco subido no necesita la foto del catálogo (y con un
                    // saco subido, tampoco el héroe sin mascota: sería un segundo saco de más).
                    if (isset($subidos[$mascotaHeroe]) || ($subidos !== [] && $mascotaHeroe === '')) {
                        continue;
                    }
                    // La foto de un formato concreto (el saco Jirafa en kilos gratis, el pack con regalo)
                    // solo en las categorías de siempre. En un regalo de kpycoupons se vende el saco de
                    // siempre: su foto de siempre.
                    $preferidos = empty($c['permanente']) ? [] : $this->formatosPreferidos((string) $c['tipo'], $idProducto);
                    $f = KplBiblioteca::producto($idProducto, (int) $h['id_manufacturer'], $mascotaHeroe, $preferidos);
                    if ($f !== null) {
                        $urls[] = (string) $f['url'];
                        ++$nImagenes;
                        if ($mascotaHeroe !== '') {
                            $porMascota[$mascotaHeroe][] = (string) $f['url'];
                        }
                    }
                    // Regalos: al lado del saco, lo que se regala (solo con un héroe, para que quepa).
                    if ($c['tipo'] === 'regalos' && count($candidatos) === 1 && $f !== null) {
                        $idRegalo = $this->productoRegalo($idProducto, $promos);
                        $r = $idRegalo > 0 ? KplBiblioteca::producto($idRegalo, (int) $h['id_manufacturer'], $mascotaHeroe) : null;
                        if ($r !== null) {
                            $urls[] = (string) $r['url'];
                            ++$nImagenes;
                            $lado = true;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $this->avisos['imagen-' . $c['clave']] = 'imágenes de ' . $c['clave'] . ': ' . $e->getMessage();
            PrestaShopLogger::addLog('kpypromolanding, imágenes de ' . $c['clave'] . ': ' . $e->getMessage(), 2, null, 'KplSincronizar', 0, true);
        }
        // Una promoción de una sola mascota: su saco es el de esa mascota (también si en el paso 6
        // se eligió una imagen). En las de perros y gatos, cada héroe ya quedó apuntado arriba y la
        // imagen elegida a mano vale para los dos.
        if ($c['mascota'] !== '' && $urls !== []) {
            $porMascota[$c['mascota']] = $urls;
        }

        return [$urls, $logo, $lado, $porMascota, $logoK];
    }

    /**
     * Todo lo que nombra una promoción, para saber si es de una gama (Mature, Puppy…): los nombres
     * de sus promociones en Kompy · Promociones, el título del banner y los nombres de sus productos.
     */
    private function textoDe(array $c, array $promos): string
    {
        $trozos = [(string) ($c['titulo'] ?? '')];
        foreach (array_merge((array) ($c['vivas'] ?? []), (array) ($c['programadas'] ?? [])) as $idPromo) {
            $trozos[] = (string) ($promos[$idPromo]['nombre'] ?? '');
        }
        $ids = array_filter(array_map('intval', array_keys((array) ($c['productos'] ?? []))));
        if ($ids !== []) {
            foreach (Db::getInstance()->executeS(
                'SELECT name FROM ' . _DB_PREFIX_ . 'product_lang WHERE id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT')
                . ' AND id_product IN (' . implode(',', $ids) . ') GROUP BY id_product'
            ) ?: [] as $f) {
                $trozos[] = (string) $f['name'];
            }
        }

        return implode(' ', $trozos);
    }

    /**
     * Los formatos de un producto cuya foto propia debe salir en el banner: en kilos gratis, los
     * formatos «+ N kg gratis» (el saco Jirafa); en regalos, los packs con regalo. En lo demás, ninguno.
     *
     * @return int[]
     */
    private function formatosPreferidos(string $tipo, int $idProducto): array
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        if ($tipo === 'kilos_gratis') {
            $filas = $db->executeS(
                "SELECT DISTINCT pa.id_product_attribute FROM {$p}product_attribute pa
                 INNER JOIN {$p}product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
                 INNER JOIN {$p}attribute_lang al ON al.id_attribute = pac.id_attribute AND al.id_lang = " . (int) Configuration::get('PS_LANG_DEFAULT') . '
                 WHERE pa.id_product = ' . (int) $idProducto . " AND (al.name LIKE '%gratis%' OR al.name LIKE '%regalo%') AND al.name NOT LIKE '%outlet%'"
            ) ?: [];

            return array_map('intval', array_column($filas, 'id_product_attribute'));
        }
        if ($tipo === 'regalos' && $this->hayTabla('kpy_packs')) {
            $filas = $db->executeS("SELECT DISTINCT id_product_pack FROM {$p}kpy_packs WHERE is_gift = 1 AND id_product_pack LIKE '" . (int) $idProducto . "-%'") ?: [];
            $ids = [];
            foreach ($filas as $f) {
                $ids[] = (int) substr((string) $f['id_product_pack'], strlen((string) $idProducto) + 1);
            }

            return array_values(array_filter($ids));
        }

        return [];
    }

    /**
     * El producto que se regala con un producto, o 0: el de su promoción de regalo de kpycoupons en
     * marcha (si regala otro producto; la primera promoción por id y su primera pareja) o, si no, el
     * primer artículo de sus packs con regalo que no es él mismo.
     *
     * @param array<int, array> $promos
     */
    private function productoRegalo(int $idProducto, array $promos): int
    {
        foreach ($promos as $promo) {
            if (($promo['tipo_kpc'] ?? '') !== 'regalo' || ($promo['estado'] ?? '') !== 'viva') {
                continue;
            }
            foreach ((array) ($promo['parejas'] ?? []) as $pr) {
                if ($pr['id_product'] === $idProducto && $pr['r_id_product'] > 0 && $pr['r_id_product'] !== $idProducto) {
                    return $pr['r_id_product'];
                }
            }
        }
        if (!$this->hayTabla('kpy_packs')) {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            'SELECT id_product_item FROM ' . _DB_PREFIX_ . "kpy_packs WHERE is_gift = 1 AND id_product_pack LIKE '" . (int) $idProducto . "-%' AND id_product_item <> " . (int) $idProducto . ' ORDER BY id_product_pack, id_product_item'
        );
    }

    /**
     * De cada producto con promoción viva, por tipo: el % mayor y las promociones (ids de kpycoupons;
     * 0 = la categoría permanente de kilos gratis). Más las campañas marcadas en el paso 6 de cada
     * promoción. Es lo que lee `KplPromos::enCampana()` al pintar las etiquetas.
     *
     * @return array{productos: array<int, array<string, array{pct: float, promos: int[]}>>, marcadas: array<int, string>}
     */
    private function productosPromo(array $promos, array $porClave, array $nuestras): array
    {
        $productos = [];
        foreach ($promos as $id => $promo) {
            if (($promo['estado'] ?? '') !== 'viva') {
                continue;
            }
            $tipo = (string) $promo['tipo'];
            foreach ((array) ($promo['productos'] ?? []) as $idProducto => $d) {
                $x = &$productos[(int) $idProducto][$tipo];
                $x ??= ['pct' => 0.0, 'promos' => []];
                $x['pct'] = max($x['pct'], (float) $d['pct']);
                $x['promos'][] = (int) $id;
                unset($x);
            }
        }
        $kg = $porClave[KplClaves::PERMANENTES['kilos_gratis']] ?? null;
        if ($kg !== null) {
            foreach (array_keys((array) $kg['productos']) as $idProducto) {
                $productos[(int) $idProducto]['kilos_gratis'] = ['pct' => 0.0, 'promos' => [0]];
            }
        }
        $marcadas = [];
        foreach ($nuestras as $id => $f) {
            // Solo las que están en la landing: la marca de campaña de un cupón oculto o por canal no decide
            // quién entra en la campaña (`KplCampanas::entra()`: con alguna marcada, solo las marcadas).
            if (isset($promos[(int) $id]) && trim((string) ($f['campanas'] ?? '')) !== '') {
                $marcadas[(int) $id] = trim((string) $f['campanas']);
            }
        }

        return ['productos' => $productos, 'marcadas' => $marcadas];
    }

    /** El % con el que se ordenan los listados de una clave (`kpy_promo_categoria.pct`): el mayor de sus promociones vivas; 0 sin ninguna. */
    private function pctVivas(array $c, array $promos): float
    {
        $pct = 0.0;
        foreach (array_filter(array_map('intval', (array) ($c['vivas'] ?? []))) as $id) {
            $pct = max($pct, (float) ($promos[$id]['porcentaje'] ?? 0));
        }

        return $pct;
    }

    /** El % que se anuncia de una clave: el mayor de sus promociones vivas (o programadas, o terminadas). */
    private function pctDe(array $c, array $promos): float
    {
        $pct = 0.0;
        foreach ($this->promosDe($c) as $id) {
            $pct = max($pct, (float) ($promos[$id]['porcentaje'] ?? 0));
        }

        return $pct;
    }

    /** El código del cupón (el de la primera promoción viva de tipo cupón). */
    private function codigoDe(array $c, array $promos): string
    {
        foreach ($this->promosDe($c) as $id) {
            if (($promos[$id]['tipo'] ?? '') === 'cupon') {
                return (string) $promos[$id]['codigo'];
            }
        }

        return '';
    }

    /** @return array{desde: ?string, hasta: ?string} el principio más temprano y el fin más tardío */
    private function fechasDe(array $c, array $promos): array
    {
        $desde = null;
        $hasta = null;
        $sinFin = false;
        foreach ($this->promosDe($c) as $id) {
            $d = $promos[$id]['desde'] ?? null;
            $h = $promos[$id]['hasta'] ?? null;
            if ($d !== null && ($desde === null || $d < $desde)) {
                $desde = $d;
            }
            if ($h === null) {
                $sinFin = true;
            } elseif ($hasta === null || $h > $hasta) {
                $hasta = $h;
            }
        }

        return ['desde' => $desde, 'hasta' => $sinFin ? null : $hasta];
    }

    /** Las promociones que mandan en una clave: las vivas; si no hay, las programadas; si no, las terminadas. */
    private function promosDe(array $c): array
    {
        $ids = $c['vivas'] !== [] ? $c['vivas'] : ($c['programadas'] !== [] ? $c['programadas'] : $c['terminadas']);

        return array_values(array_filter(array_map('intval', $ids)));
    }

    /** La dirección de la landing de la marca (`/marcas/{rewrite}`), si la tiene; si no, vacía. */
    private function urlMarca(int $idManufacturer): string
    {
        try {
            if (method_exists('Manufacturer', 'getLinkRewriteById')) {
                $rewrite = (string) Manufacturer::getLinkRewriteById($idManufacturer);
                if ($rewrite !== '') {
                    return rtrim(Context::getContext()->link->getBaseLink(), '/') . '/marcas/' . $rewrite;
                }
            }
        } catch (Throwable $e) {
            // Sin landing de marca: el banner no enlaza a ella.
        }

        return '';
    }

    // ---------------------------------------------------------------------------------------

    /**
     * La característica «Mascota» y sus valores «Perro» y «Gato», por nombre, guardados en la
     * configuración la primera vez (en pruebas: 14, 1000 y 1001).
     *
     * @return array{feature: int, perro: int, gato: int}
     */
    public static function caracteristicaMascota(): array
    {
        $f = (int) Configuration::getGlobalValue(self::CONF_FEATURE);
        $perro = (int) Configuration::getGlobalValue(self::CONF_PERRO);
        $gato = (int) Configuration::getGlobalValue(self::CONF_GATO);
        if ($f > 0 && $perro > 0 && $gato > 0) {
            return ['feature' => $f, 'perro' => $perro, 'gato' => $gato];
        }
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        // (getValue() añade su propio LIMIT 1: no se pone aquí.)
        $f = (int) $db->getValue("SELECT id_feature FROM {$p}feature_lang WHERE LOWER(name) = 'mascota' ORDER BY id_feature");
        if ($f > 0) {
            $perro = (int) $db->getValue("SELECT fv.id_feature_value FROM {$p}feature_value fv INNER JOIN {$p}feature_value_lang fvl ON fvl.id_feature_value = fv.id_feature_value WHERE fv.id_feature = {$f} AND LOWER(fvl.value) = 'perro' ORDER BY fv.id_feature_value");
            $gato = (int) $db->getValue("SELECT fv.id_feature_value FROM {$p}feature_value fv INNER JOIN {$p}feature_value_lang fvl ON fvl.id_feature_value = fv.id_feature_value WHERE fv.id_feature = {$f} AND LOWER(fvl.value) = 'gato' ORDER BY fv.id_feature_value");
        }
        Configuration::updateGlobalValue(self::CONF_FEATURE, $f);
        Configuration::updateGlobalValue(self::CONF_PERRO, $perro);
        Configuration::updateGlobalValue(self::CONF_GATO, $gato);

        return ['feature' => $f, 'perro' => $perro, 'gato' => $gato];
    }

    /** Guarda un dato nuestro (JSON) en `kpy_promo_dato`. */
    public static function guardarDato(string $nombre, $valor): void
    {
        $json = json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        Db::getInstance()->execute(
            'INSERT INTO ' . _DB_PREFIX_ . "kpy_promo_dato (nombre, valor, date_upd) VALUES ('" . pSQL($nombre) . "', '" . pSQL((string) $json, true) . "', NOW())
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), date_upd = VALUES(date_upd)"
        );
    }

    /** Lee un dato nuestro, o null. */
    public static function dato(string $nombre)
    {
        $json = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT valor FROM ' . _DB_PREFIX_ . "kpy_promo_dato WHERE nombre = '" . pSQL($nombre) . "'");
        if (!is_string($json) || $json === '') {
            return null;
        }
        $valor = json_decode($json, true);

        return is_array($valor) ? $valor : null;
    }

    private static function fecha($valor): ?string
    {
        $valor = (string) $valor;

        return $valor === '' || strpos($valor, '0000-00-00') === 0 ? null : $valor;
    }

    private function hayTabla(string $tabla): bool
    {
        static $cache = [];
        if (!isset($cache[$tabla])) {
            $cache[$tabla] = (bool) Db::getInstance()->getValue(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . pSQL(_DB_PREFIX_ . $tabla) . "'"
            );
        }

        return $cache[$tabla];
    }

    private function hayColumna(string $tabla, string $columna): bool
    {
        static $cache = [];
        $k = $tabla . '.' . $columna;
        if (!isset($cache[$k])) {
            $cache[$k] = $this->hayTabla($tabla) && (bool) Db::getInstance()->getValue(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . pSQL(_DB_PREFIX_ . $tabla) . "' AND COLUMN_NAME = '" . pSQL($columna) . "'"
            );
        }

        return $cache[$k];
    }
}
