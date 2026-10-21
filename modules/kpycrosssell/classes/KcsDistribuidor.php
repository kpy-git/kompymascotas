<?php
/**
 * De quién viene cada marca, que es lo que decide si dos productos se pueden ofrecer juntos.
 *
 * Kompy no sirve todo desde el mismo sitio: hay tres proveedores de dropshipping y el almacén
 * propio. Sugerir un pienso de uno con un húmedo de otro parte el pedido en dos envíos, así que
 * **una sugerencia tiene que salir del mismo sitio que el producto que se está mirando**.
 *
 *   Neftys        Eukanuba, Schesir, Specific, Boske y la farmacología (Ceva, VetPlus, Stangest…).
 *                 Es el mejor socio: una marca que sirvan Neftys y Distrivet, se pide a Neftys
 *   Distrivet     Farmina, Hill's, Lenda, Acana, Orijen, Taste of the Wild, Artero, Kong…
 *   Evolution     Natural Greatness (y Natural Woodland, Farm Nature), Alpha Spirit, Primal Spirit
 *   Tienda        todo lo demás: Royal Canin, Ownat, Advance, Dingo… Sale del almacén propio,
 *                 que también tiene Boske, así que Boske combina con ellas sin partir el pedido
 *
 * **El dato sale de las tablas de stock de cada distribuidor** (`neftys_stock` y
 * `kpy_distrivet_stock`), que son las que mantienen los conectores: una marca con productos en una
 * de ellas es de ese distribuidor. Se decide por marca y no por producto porque esas tablas solo
 * traen lo que el conector ha sincronizado (de Schesir, 17 de 178), y dos fichas de la misma marca
 * no pueden salir de sitios distintos. Por este orden:
 *
 *   1. Evolution, por nombre: no tiene tabla de stock en la tienda.
 *   2. Lo corregido a mano en la pantalla del módulo (`KCS_DISTRIBUIDORES`).
 *   3. Neftys, si la marca tiene algo en `neftys_stock`.
 *   4. Distrivet, si lo tiene en `kpy_distrivet_stock`.
 *   5. Si no, tienda.
 *
 * Sin esas tablas (otra instalación) se usa la lista de partida de aquí abajo.
 */
class KcsDistribuidor
{
    public const PROPIO = 'propio';

    /** Los nombres que se enseñan. */
    public const NOMBRES = [
        'neftys' => 'Neftys',
        'distrivet' => 'Distrivet',
        'evolution' => 'Evolution Pet',
        self::PROPIO => 'Tienda',
    ];

    /** Las marcas de Evolution Pet: no tienen tabla de stock en la tienda, así que van por nombre. */
    public const EVOLUTION = [
        'Natural Greatness', 'Natural Woodland by Natural Greatness', 'Farm Nature by Natural Greatness',
        'Alpha Spirit', 'Primal Spirit',
    ];

    /**
     * El reparto que dio Rafa, por nombre de marca. Mandan las tablas de stock; esta lista cubre las
     * marcas que las tablas no traen (Lily's Kitchen, IAMS y Applaws no están en ninguna, y sin
     * esto pasaban por marcas de la tienda) y cualquier instalación sin tablas.
     */
    public const DE_PARTIDA = [
        'distrivet' => [
            'Acana', 'Orijen', 'Farmina', 'Lenda', 'Applaws', 'Taste of the Wild', 'Pedigree', "Hill's",
        ],
        // Boske es nuestra marca y hay stock en tienda, pero **el almacén principal es Neftys**: es
        // de donde sale más cómodo. Por eso al Boske de gato se le ofrece Schesir o Lily's. Aun
        // así entra en cualquier ficha, por ser la marca de la casa (ver `compatibles()`).
        'neftys' => [
            'Eukanuba', 'Schesir', "Lily's Kitchen", 'IAMS', 'Specific', 'Boske',
        ],
        'evolution' => self::EVOLUTION,
        self::PROPIO => [
            'Royal Canin', 'Ownat', 'Advance', 'Pienso y Mascotas', 'Dingo Nature Diet', 'Dingonatura',
        ],
    ];

    /**
     * Las marcas que se sirven en pack de seis latas, como Boske. La unidad suelta no se ofrece
     * cuando la marca tiene pack: es lo que de verdad se vende.
     */
    public const EN_PACK_DE_SEIS = ['Boske', 'Natural Greatness'];

    /**
     * Los distribuidores que de momento **solo se cruzan consigo mismos**: a un pienso suyo se le
     * ofrece húmedo de su propia marca, y ni suplementos ni caprichos de fuera.
     */
    public const SOLO_LO_SUYO = ['evolution'];

    /**
     * A qué marca se recurre cuando la del producto no tiene húmedo, **dentro de cada distribuidor**
     * y por orden de importancia. En gato manda Schesir, que es la que más variedad tiene.
     */
    public const RESERVA = [
        'distrivet' => [
            'Perro' => ['Farmina', 'Applaws', 'Acana', 'Orijen'],
            'Gato' => ['Applaws', 'Farmina', 'Acana'],
        ],
        'neftys' => [
            'Perro' => ["Lily's Kitchen", 'Schesir', 'Eukanuba', 'IAMS'],
            // En gato manda Schesir, que es la que más variedad tiene. Es también la reserva del
            // propio Boske, que sale de este mismo almacén.
            'Gato' => ['Schesir', "Lily's Kitchen", 'IAMS'],
        ],
        'evolution' => [
            'Perro' => ['Natural Greatness', 'Alpha Spirit', 'Primal Spirit', 'Natural Woodland by Natural Greatness'],
            'Gato' => ['Natural Greatness', 'Alpha Spirit', 'Primal Spirit'],
        ],
        // Boske no hace falta aquí: entra en todas las fichas por ser la marca de la casa.
        self::PROPIO => [
            'Perro' => ['Royal Canin', 'Advance', 'Ownat', 'Dingo Nature Diet'],
            'Gato' => ['Royal Canin', 'Advance', 'Ownat'],
        ],
    ];

    /** Las marcas de reserva de ese distribuidor y esa mascota, ya en ids y en orden. @return int[] */
    public static function reserva(int $idMarca, string $mascota): array
    {
        $suyo = self::deLaMarca($idMarca);
        $nombres = self::RESERVA[$suyo][$mascota] ?? self::RESERVA[$suyo]['Perro'] ?? [];
        $ids = [];
        foreach ($nombres as $nombre) {
            $id = (int) self::porNombre($nombre);
            if ($id > 0 && $id !== $idMarca) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /** De qué distribuidor es una marca. */
    public static function deLaMarca(int $idMarca): string
    {
        $mapa = self::mapa();

        return $mapa[$idMarca] ?? self::PROPIO;
    }

    /**
     * Las marcas que se pueden ofrecer al lado de esta, ya en ids.
     *
     * Son las de su mismo distribuidor. La marca de la casa entra siempre, salvo que el
     * distribuidor sea de los que solo se cruzan consigo mismos.
     *
     * @return int[]
     */
    public static function compatibles(int $idMarca): array
    {
        $suyo = self::deLaMarca($idMarca);
        $ids = [];
        foreach (self::mapa() as $id => $clave) {
            if ($clave === $suyo) {
                $ids[] = (int) $id;
            }
        }
        // Las marcas que nadie ha repartido todavía son del almacén, como el resto de las de casa.
        if ($suyo === self::PROPIO) {
            $ids = array_merge($ids, self::sinRepartir());
        }
        $propia = (int) Configuration::get('KCS_MARCA_PROPIA');
        if ($propia > 0 && !in_array($suyo, self::SOLO_LO_SUYO, true)) {
            $ids[] = $propia;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Las marcas que salen de la tienda (almacén propio), más Boske, que también tiene stock allí.
     * Es el último recurso para no dejar un hueco vacío.
     *
     * @return int[]
     */
    public static function deTienda(): array
    {
        $ids = [];
        foreach (self::mapa() as $id => $clave) {
            if ($clave === self::PROPIO) {
                $ids[] = (int) $id;
            }
        }
        $ids = array_merge($ids, self::sinRepartir());
        $propia = (int) Configuration::get('KCS_MARCA_PROPIA');
        if ($propia > 0) {
            $ids[] = $propia;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** Las marcas de un distribuidor, en ids. @return int[] */
    public static function marcasDe(string $clave): array
    {
        return array_map('intval', array_keys(array_filter(self::mapa(), static fn (string $c): bool => $c === $clave)));
    }

    /** ¿A esta marca solo se le ofrece lo suyo? */
    public static function soloLoSuyo(int $idMarca): bool
    {
        return in_array(self::deLaMarca($idMarca), self::SOLO_LO_SUYO, true);
    }

    /** ¿Esta marca se ofrece en pack de seis? */
    public static function enPackDeSeis(int $idMarca): bool
    {
        foreach (self::EN_PACK_DE_SEIS as $nombre) {
            if ((int) self::porNombre($nombre) === $idMarca) {
                return true;
            }
        }

        return false;
    }

    /**
     * Marca → distribuidor, en ids. Se calcula una vez por petición.
     *
     * @return array<int, string>
     */
    public static function mapa(): array
    {
        static $mapa = null;
        if ($mapa !== null) {
            return $mapa;
        }
        $mapa = [];
        $p = _DB_PREFIX_;
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);

        // 1. Evolution, por nombre: no tiene tabla de stock en la tienda.
        foreach (self::EVOLUTION as $nombre) {
            $id = self::porNombre($nombre);
            if ($id > 0) {
                $mapa[$id] = 'evolution';
            }
        }

        // 2. Lo corregido a mano en la pantalla manda sobre lo que digan las tablas.
        $aMano = json_decode((string) Configuration::get('KCS_DISTRIBUIDORES'), true);
        foreach (is_array($aMano) ? $aMano : [] as $id => $clave) {
            if ((int) $id > 0 && isset(self::NOMBRES[(string) $clave]) && !isset($mapa[(int) $id])) {
                $mapa[(int) $id] = (string) $clave;
            }
        }

        // 3 y 4. Las tablas de stock de cada distribuidor, Neftys primero: si una marca la sirven los
        // dos, se pide a Neftys, que es el mejor socio.
        foreach (['neftys' => 'neftys_stock', 'distrivet' => 'kpy_distrivet_stock'] as $clave => $tabla) {
            try {
                $marcas = $db->executeS(
                    "SELECT DISTINCT pr.id_manufacturer
                     FROM {$p}{$tabla} s
                     INNER JOIN {$p}product pr ON pr.id_product = s.id_product
                     WHERE pr.id_manufacturer > 0"
                ) ?: [];
            } catch (Throwable $e) {
                $marcas = [];
            }
            foreach ($marcas as $m) {
                $id = (int) $m['id_manufacturer'];
                if (!isset($mapa[$id])) {
                    $mapa[$id] = $clave;
                }
            }
        }

        // 5. Las marcas que las tablas no traen, con el reparto de Rafa.
        foreach (self::DE_PARTIDA as $clave => $nombres) {
            foreach ($nombres as $nombre) {
                $id = self::porNombre($nombre);
                if ($id > 0 && !isset($mapa[$id])) {
                    $mapa[$id] = $clave;
                }
            }
        }

        // 6. Lo que no está en ningún sitio es de la tienda: no hace falta apuntarlo.
        return $mapa;
    }

    /** Las marcas que todavía no tienen distribuidor. @return int[] */
    public static function sinRepartir(): array
    {
        $mapa = self::mapa();
        $p = _DB_PREFIX_;
        $todas = array_map('intval', array_column(Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT DISTINCT id_manufacturer FROM {$p}product WHERE active = 1 AND id_manufacturer > 0"
        ) ?: [], 'id_manufacturer'));

        return array_values(array_diff($todas, array_keys($mapa)));
    }

    /** Guarda el reparto hecho a mano. @param array<int, string> $reparto */
    public static function guardar(array $reparto): void
    {
        $limpio = [];
        foreach ($reparto as $id => $clave) {
            if ((int) $id > 0 && isset(self::NOMBRES[(string) $clave])) {
                $limpio[(int) $id] = (string) $clave;
            }
        }
        Configuration::updateValue('KCS_DISTRIBUIDORES', json_encode($limpio));
    }

    /** El id de una marca por su nombre. */
    public static function porNombre(string $nombre): int
    {
        static $cache = [];
        if (isset($cache[$nombre])) {
            return $cache[$nombre];
        }

        return $cache[$nombre] = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT id_manufacturer FROM ' . _DB_PREFIX_ . "manufacturer WHERE name = '" . pSQL($nombre) . "'"
        );
    }
}
