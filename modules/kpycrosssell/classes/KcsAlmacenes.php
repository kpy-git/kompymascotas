<?php
/**
 * El puente con el reparto de Julián (`kpyorderdispatcher`), que es el **único** sitio donde se decide
 * de qué almacén sale un producto. Aquí no se calcula ningún almacén: se le pregunta a su clase
 * `WarehouseFinder`, con su misma regla, y se guarda la respuesta durante la petición.
 *
 * Lo que se usa de él (desde el 19/11/2026, sus funciones ya no llevan el id del carrito, solo los productos):
 *   - `hasMultiWarehouse(array $products)`: si la cesta implica más de un almacén. Si dice que no (también
 *     cuando cada almacén pasa por sí solo del envío gratis y se parte el pedido), este módulo no hace nada
 *     en la cesta. Recibe los productos de la cesta presentada (`CartProductLazyArray`: suma su `total_wt`),
 *     los mismos que le pasa su `kpyproductavailabilitymessages` (`productosDeCesta`).
 *   - `getWarehousesByCart(array $products)`: los almacenes de una lista de productos; con un solo
 *     producto, el almacén de ese producto. Es lo que se usa para saber de dónde sale cada línea y cada
 *     candidato. Lee de cada producto `id_product`, `id_product_attribute`, `id_manufacturer` y `quantity`
 *     (las unidades en la cesta: la prioridad de tienda depende de ellas), con `->`: vale un objeto mínimo
 *     (`objeto`). A un candidato se le pregunta con las unidades de la línea que sustituiría.
 *
 * Sus clases no están cargadas hasta que PrestaShop instancia sus módulos (comprobado: en una petición
 * donde ninguno de ellos ha pintado nada, `class_exists` da falso). Por eso, antes de usarlas, se
 * instancian los módulos de los que depende `WarehouseFinder`, que es como PrestaShop registra sus
 * autoloaders. Si falta alguno o algo falla, el módulo se queda callado: ninguna sugerencia, ningún
 * error en la cesta, y una línea en el registro de PrestaShop.
 *
 * **La memoria** (`kpy_cross_sell_almacen`): cada respuesta suya por producto y formato se apunta con su
 * hora y vale unas horas (`VIGENCIA`). Así se puede mirar el almacén de ciento cincuenta candidatos sin
 * preguntarle ciento cincuenta veces en cada cesta. No es calcular nada: es recordar lo que él dijo. Y lo
 * que se le va a enseñar al cliente se confirma siempre en vivo (`deProducto`), por si cambió, con las
 * unidades de la línea (la memoria no las guarda: de ellas solo depende la prioridad de tienda).
 */
class KcsAlmacenes
{
    /** El módulo de Julián que decide. Sin él (desinstalado o apagado), aquí no se hace nada. */
    public const MODULO = 'kpyorderdispatcher';

    /** La memoria de sus respuestas, por producto y formato. */
    public const TABLA = 'kpy_cross_sell_almacen';

    /** Cuánto vale una respuesta recordada (segundos). Lo que se enseña se confirma en vivo igualmente. */
    public const VIGENCIA = 6 * 3600;

    /** @var array<string, ?string> «producto-formato» => almacén recordado (de la tabla o de esta petición) */
    private static array $recordados = [];

    private const CLASE = 'PrestaShop\Module\KpyOrderDispatcher\Service\WarehouseFinder';

    /**
     * Los módulos cuyas clases usa `WarehouseFinder` por dentro (las listas de marcas de Evolution, el
     * stock de Neftys, la prioridad de tienda, Distrivet). Se cargan todos para que no falte ninguno a
     * medio cálculo.
     */
    private const DEPENDE_DE = ['kpyorderdispatcher', 'kpyevolutionpets', 'neftysfarmaconnector', 'kpystorepreparation', 'kpydistrivetconnector'];

    /** Lo que se le dice al cliente del almacén: **nada**. Estos nombres son solo para los registros y la pantalla. */
    public const NOMBRES = ['TIENDA' => 'Tienda', 'NEFTYS' => 'Neftys', 'DISTRIVET' => 'Distrivet', 'EVOLUTION' => 'Evolution Pet'];

    private static ?bool $disponible = null;

    private static ?object $reparto = null;

    /** @var array<string, string> «producto-formato-marca-unidades» => almacén */
    private static array $cache = [];

    /** ¿Se puede preguntar? Solo si el módulo de Julián está instalado y encendido, y su clase carga. */
    public static function disponible(): bool
    {
        if (self::$disponible !== null) {
            return self::$disponible;
        }
        try {
            if (!Module::isEnabled(self::MODULO)) {
                return self::$disponible = false;
            }
            if (!class_exists(self::CLASE)) {
                foreach (self::DEPENDE_DE as $nombre) {
                    if (Module::isInstalled($nombre)) {
                        Module::getInstanceByName($nombre);
                    }
                }
            }
            if (!class_exists(self::CLASE)) {
                self::avisar('no se encuentra la clase ' . self::CLASE);

                return self::$disponible = false;
            }
            $clase = self::CLASE;
            self::$reparto = new $clase();

            return self::$disponible = method_exists(self::$reparto, 'hasMultiWarehouse') && method_exists(self::$reparto, 'getWarehousesByCart');
        } catch (Throwable $e) {
            self::avisar($e->getMessage());

            return self::$disponible = false;
        }
    }

    /**
     * Los almacenes que implica una lista de productos, con la regla de Julián. `null` si no se puede saber.
     *
     * @param object[] $productos objetos con id_product, id_product_attribute, id_manufacturer y quantity
     *
     * @return string[]|null
     */
    public static function almacenes(array $productos): ?array
    {
        if ($productos === [] || !self::disponible()) {
            return null;
        }
        try {
            return array_values(array_map('strval', self::$reparto->getWarehousesByCart(array_values($productos))));
        } catch (Throwable $e) {
            self::callar($e);

            return null;
        }
    }

    /**
     * ¿Implica la cesta más de un almacén? Es la puerta de todo el módulo en la cesta. `null` si no se puede saber.
     *
     * @param object[] $productos los de la cesta presentada (`productosDeCesta`)
     */
    public static function multi(array $productos): ?bool
    {
        if ($productos === [] || !self::disponible()) {
            return null;
        }
        try {
            return (bool) self::$reparto->hasMultiWarehouse(array_values($productos));
        } catch (Throwable $e) {
            self::callar($e);

            return null;
        }
    }

    /**
     * Los productos de la cesta como se los pasa PrestaShop a los módulos (`CartProductLazyArray`, con sus
     * unidades y su total): lo que espera `hasMultiWarehouse`. Se presenta la cesta como la presenta la
     * tienda en cada página (`FrontController`, con los regalos aparte). `null` si no se puede.
     *
     * @return object[]|null
     */
    public static function productosDeCesta(Cart $cart): ?array
    {
        try {
            $presentada = (new PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter())->present($cart, true);

            return array_values((array) $presentada['products']);
        } catch (Throwable $e) {
            self::callar($e);

            return null;
        }
    }

    /**
     * El almacén de un producto concreto con unas unidades (la prioridad de tienda depende de ellas). Vale
     * para las líneas y para los candidatos que aún no están en la cesta (con las unidades de la línea).
     */
    public static function deProducto(int $idProducto, int $ipa, int $idMarca, int $cantidad = 1): ?string
    {
        $cantidad = max(1, $cantidad);
        $clave = $idProducto . '-' . $ipa . '-' . $idMarca . '-' . $cantidad;
        if (array_key_exists($clave, self::$cache)) {
            return self::$cache[$clave];
        }
        $almacenes = self::almacenes([self::objeto($idProducto, $ipa, $idMarca, $cantidad)]);
        $almacen = $almacenes === null || $almacenes === [] ? null : (string) $almacenes[0];
        if ($almacen !== null) {
            self::apuntar($idProducto, $ipa, $almacen);
        }

        return self::$cache[$clave] = $almacen;
    }

    /**
     * El almacén de un producto-formato **recordado**: de esta petición, de la memoria si aún vale, y si
     * no, preguntando (y se apunta). Para mirar muchos candidatos. Lo que se vaya a enseñar se confirma
     * después con `deProducto`.
     */
    public static function recordado(int $idProducto, int $ipa, int $idMarca, int $cantidad = 1): ?string
    {
        $clave = $idProducto . '-' . $ipa;
        if (array_key_exists($clave, self::$recordados)) {
            return self::$recordados[$clave];
        }

        return self::$recordados[$clave] = self::deProducto($idProducto, $ipa, $idMarca, $cantidad);
    }

    /**
     * Trae de la memoria, de una vez, lo que recuerde (y siga valiendo) de una lista de «producto-formato»,
     * para que `recordado` no tenga que preguntar por ellos.
     *
     * @param string[] $claves «producto-formato»
     */
    public static function recordar(array $claves): void
    {
        $pares = [];
        foreach ($claves as $clave) {
            if (array_key_exists($clave, self::$recordados)) {
                continue;
            }
            [$id, $ipa] = array_map('intval', array_pad(explode('-', (string) $clave, 2), 2, 0));
            if ($id > 0) {
                $pares[$id . '-' . $ipa] = '(' . $id . ',' . $ipa . ')';
            }
        }
        if ($pares === []) {
            return;
        }
        try {
            $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                'SELECT id_product, id_product_attribute, almacen FROM ' . _DB_PREFIX_ . self::TABLA . '
                 WHERE (id_product, id_product_attribute) IN (' . implode(',', $pares) . ')
                   AND fecha > \'' . pSQL(date('Y-m-d H:i:s', time() - self::VIGENCIA)) . '\''
            ) ?: [];
        } catch (Throwable $e) {
            // Sin memoria se pregunta en vivo: más lento, no peor.
            return;
        }
        foreach ($filas as $f) {
            self::$recordados[(int) $f['id_product'] . '-' . (int) $f['id_product_attribute']] = (string) $f['almacen'];
        }
    }

    /** Apunta una respuesta de Julián en la memoria (o la renueva). Si la tabla falla, no pasa nada. */
    private static function apuntar(int $idProducto, int $ipa, string $almacen): void
    {
        self::$recordados[$idProducto . '-' . $ipa] = $almacen;
        try {
            Db::getInstance()->execute(
                'INSERT INTO ' . _DB_PREFIX_ . self::TABLA . ' (id_product, id_product_attribute, almacen, fecha)
                 VALUES (' . $idProducto . ', ' . $ipa . ", '" . pSQL(mb_substr($almacen, 0, 20)) . "', '" . pSQL(date('Y-m-d H:i:s')) . "')
                 ON DUPLICATE KEY UPDATE almacen = VALUES(almacen), fecha = VALUES(fecha)"
            );
            // De vez en cuando, fuera lo de hace más de una semana.
            if (mt_rand(1, 500) === 1) {
                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . self::TABLA . " WHERE fecha < '" . pSQL(date('Y-m-d H:i:s', time() - 7 * 86400)) . "'");
            }
        } catch (Throwable $e) {
            // Recordar no es tan importante como vender.
        }
    }

    public static function crearTabla(): bool
    {
        $p = _DB_PREFIX_;
        $motor = _MYSQL_ENGINE_;

        return (bool) Db::getInstance()->execute(
            "CREATE TABLE IF NOT EXISTS {$p}" . self::TABLA . " (
                id_product INT UNSIGNED NOT NULL,
                id_product_attribute INT UNSIGNED NOT NULL DEFAULT 0,
                almacen VARCHAR(20) NOT NULL,
                fecha DATETIME NOT NULL,
                PRIMARY KEY (id_product, id_product_attribute),
                KEY fecha (fecha)
            ) ENGINE={$motor} DEFAULT CHARSET=utf8mb4;"
        );
    }

    /** Cuántas respuestas recuerda la memoria y de cuándo es la más vieja que aún vale (para la pantalla). */
    public static function memoria(): array
    {
        try {
            $fila = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
                'SELECT COUNT(*) AS n, MIN(fecha) AS desde FROM ' . _DB_PREFIX_ . self::TABLA . "
                 WHERE fecha > '" . pSQL(date('Y-m-d H:i:s', time() - self::VIGENCIA)) . "'"
            );
        } catch (Throwable $e) {
            $fila = null;
        }

        return ['n' => (int) ($fila['n'] ?? 0), 'desde' => (string) ($fila['desde'] ?? '')];
    }

    /**
     * El objeto mínimo que entiende `getWarehousesByCart`: lo mismo que lee de una línea presentada de la
     * cesta, con sus unidades (`quantity`).
     */
    public static function objeto(int $idProducto, int $ipa, int $idMarca, int $cantidad = 1): object
    {
        return (object) [
            'id_product' => $idProducto,
            'id_product_attribute' => $ipa,
            'id_manufacturer' => $idMarca,
            'quantity' => max(1, $cantidad),
        ];
    }

    /**
     * Lo que el reparto de **pedidos** de Julián no deja salir de un almacén aunque el de la cesta diga que sí.
     * Hoy, una regla: «los snacks no se pueden enviar sueltos desde Evolution Pets» (su `EvolutionOrderGuard`,
     * que mira la categoría 5576 «Natural greatness snacks» al despachar el pedido); `getWarehousesByCart` va
     * por marca y a esos snacks les dice EVOLUTION. Es el ajuste `KCS_CESTA_NUNCA`: `EVOLUTION:5576` (varias
     * categorías con comas, otro almacén tras un punto y coma: `EVOLUTION:5576,5577; NEFTYS:12`). Nada de esas
     * categorías se ofrece como alternativa de ese almacén. No cambia lo que se le pregunta a Julián ni la
     * puerta (`hasMultiWarehouse`): solo lo que se recomienda (Rafa, 18/11 a mediodía, con Natural
     * Greatness + sus galletas delante: «esa categoría nunca va a Evolution Pet»).
     *
     * @return array<string, int[]> almacén => categorías
     */
    public static function leerVetos(string $texto): array
    {
        $vetos = [];
        foreach (preg_split('/[;\n]+/', $texto) ?: [] as $trozo) {
            if (!str_contains($trozo, ':')) {
                continue;
            }
            [$almacen, $lista] = explode(':', $trozo, 2);
            $almacen = strtoupper(trim($almacen));
            $ids = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $lista) ?: []), static fn (int $id): bool => $id > 0));
            if ($almacen === '' || $ids === []) {
                continue;
            }
            $vetos[$almacen] = array_values(array_unique(array_merge($vetos[$almacen] ?? [], $ids)));
        }

        return $vetos;
    }

    /** Lo contrario: el texto del ajuste a partir del mapa, para guardarlo limpio. */
    public static function escribirVetos(array $vetos): string
    {
        $trozos = [];
        foreach ($vetos as $almacen => $ids) {
            $trozos[] = $almacen . ':' . implode(',', $ids);
        }

        return implode('; ', $trozos);
    }

    /**
     * El **almacén de referencia** de una cesta mezclada: el que cubre más euros. A igual importe, el de la
     * línea más cara; y si aún empatan, el primero por nombre, para que la respuesta sea siempre la misma.
     *
     * Es la regla que se le propuso a Julián el 18/11 («el que cubre más euros de la cesta; a igual importe,
     * el de la línea más cara»). Cambiar una línea hacia el almacén de referencia nunca le hace perder, así
     * que la cesta no da bandazos mientras el cliente va cambiando.
     *
     * @param array<int, array{almacen: string, total: float}> $lineas
     */
    public static function referencia(array $lineas): string
    {
        $suma = [];
        $mayor = [];
        foreach ($lineas as $l) {
            $almacen = (string) ($l['almacen'] ?? '');
            if ($almacen === '') {
                continue;
            }
            $total = (float) ($l['total'] ?? 0);
            $suma[$almacen] = round(($suma[$almacen] ?? 0) + $total, 2);
            $mayor[$almacen] = max($mayor[$almacen] ?? 0, $total);
        }
        if ($suma === []) {
            return '';
        }
        $candidatos = array_keys($suma);
        usort($candidatos, static fn (string $a, string $b): int => [$suma[$b], $mayor[$b], $a] <=> [$suma[$a], $mayor[$a], $b]);

        return $candidatos[0];
    }

    /** Un fallo se apunta una vez por petición y el módulo se calla: la cesta tiene que seguir funcionando. */
    private static function callar(Throwable $e): void
    {
        self::$disponible = false;
        self::avisar($e->getMessage());
    }

    private static function avisar(string $mensaje): void
    {
        static $avisado = false;
        if ($avisado) {
            return;
        }
        $avisado = true;
        try {
            PrestaShopLogger::addLog('kpycrosssell, almacenes de la cesta: ' . $mensaje, 2, null, 'Cart', (int) (Context::getContext()->cart->id ?? 0), true);
        } catch (Throwable $e) {
            // Sin registro tampoco pasa nada.
        }
    }
}
