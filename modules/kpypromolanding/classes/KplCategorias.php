<?php
/**
 * Las categorías de las promociones, en PrestaShop.
 *
 * Todas cuelgan de una raíz «Promociones» debajo de Inicio. Cada clave (`royal-canin-special-price-perro`)
 * tiene su categoría, que se crea la primera vez y **no se borra nunca**: se reutiliza mes a mes,
 * se llena con los productos de las promociones vivas de esa clave y se apaga (`active = 0`) cuando
 * se queda vacía. Las direcciones de emails y anuncios siguen vivas aunque la promoción haya
 * terminado (la página la contesta el módulo, no PrestaShop).
 *
 * Lo que se escribe de PrestaShop: `category`, `category_lang`, `category_shop`, `category_group`
 * (lo hace el ObjectModel al crear) y `category_product` (los productos). Nada más. Un producto
 * nunca cambia de categoría por defecto por estar aquí.
 */
class KplCategorias
{
    public const CONF_RAIZ = 'KPL_RAIZ';

    /** La raíz «Promociones»: se crea si no existe (o si alguien la borró). */
    public static function raiz(): int
    {
        $id = (int) Configuration::getGlobalValue(self::CONF_RAIZ);
        if ($id > 0 && self::existe($id)) {
            return $id;
        }
        $idioma = (int) Configuration::get('PS_LANG_DEFAULT');
        // ¿Ya hay una «promociones» debajo de Inicio (de una instalación anterior)?
        $id = (int) Db::getInstance()->getValue(
            'SELECT c.id_category FROM ' . _DB_PREFIX_ . 'category c
             INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON cl.id_category = c.id_category AND cl.id_lang = ' . $idioma . '
             WHERE c.id_parent = ' . (int) Configuration::get('PS_HOME_CATEGORY') . " AND cl.link_rewrite = 'promociones'
             ORDER BY c.id_category ASC"
        );
        if ($id <= 0) {
            $id = self::crear('promociones', 'Promociones', (int) Configuration::get('PS_HOME_CATEGORY'), true, 'Promociones de Kompy: Special Price, cupones, 2ª unidad, kilos gratis, regalos y outlet.');
        }
        Configuration::updateGlobalValue(self::CONF_RAIZ, $id);

        return $id;
    }

    /**
     * La categoría de una clave: la de siempre si existe, o una nueva. Devuelve su id.
     *
     * @param array{nombre: string, tipo: string, id_manufacturer?: int, mascota?: string, permanente?: bool, descripcion?: string, pct?: float} $datos
     *        `pct`: el % mayor de sus promociones vivas, con el que `KplBuscador` ordena los listados (1.4.0)
     */
    public static function asegurar(string $clave, array $datos): int
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $fila = $db->getRow("SELECT * FROM {$p}kpy_promo_categoria WHERE clave = '" . pSQL($clave) . "'");
        $idCategoria = $fila ? (int) $fila['id_category'] : 0;
        if ($idCategoria > 0 && !self::existe($idCategoria)) {
            $idCategoria = 0; // la borraron a mano: se vuelve a crear con la misma clave
        }
        if ($idCategoria <= 0) {
            $idCategoria = self::crear($clave, $datos['nombre'], self::raiz(), false, (string) ($datos['descripcion'] ?? ''));
        } else {
            self::renombrar($idCategoria, (string) $datos['nombre']);
        }
        $campos = [
            'clave' => pSQL($clave),
            'id_category' => $idCategoria,
            'id_manufacturer' => (int) ($datos['id_manufacturer'] ?? 0),
            'tipo' => pSQL((string) $datos['tipo']),
            'mascota' => pSQL((string) ($datos['mascota'] ?? '')),
            'permanente' => !empty($datos['permanente']) ? 1 : 0,
            'pct' => number_format(max(0.0, (float) ($datos['pct'] ?? 0)), 3, '.', ''),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        if ($fila) {
            $db->update('kpy_promo_categoria', $campos, 'id_kpy_promo_categoria = ' . (int) $fila['id_kpy_promo_categoria']);
        } else {
            $campos['date_add'] = $campos['date_upd'];
            $db->insert('kpy_promo_categoria', $campos);
        }

        return $idCategoria;
    }

    /**
     * Deja la categoría **exactamente** con estos productos (en este orden) y la enciende o la apaga
     * según tenga algo. Devuelve cuántos tiene.
     *
     * @param int[] $idProductos
     */
    public static function llenar(string $clave, int $idCategoria, array $idProductos): int
    {
        $p = _DB_PREFIX_;
        $db = Db::getInstance();
        $idProductos = array_values(array_unique(array_filter(array_map('intval', $idProductos))));
        $actuales = array_map('intval', array_column(
            $db->executeS("SELECT id_product FROM {$p}category_product WHERE id_category = " . $idCategoria) ?: [],
            'id_product'
        ));
        $quitar = array_diff($actuales, $idProductos);
        if ($quitar !== []) {
            $db->execute("DELETE FROM {$p}category_product WHERE id_category = {$idCategoria} AND id_product IN (" . implode(',', $quitar) . ')');
        }
        $posicion = 0;
        $valores = [];
        foreach ($idProductos as $idProducto) {
            $valores[] = '(' . $idCategoria . ', ' . $idProducto . ', ' . $posicion++ . ')';
        }
        foreach (array_chunk($valores, 500) as $trozo) {
            $db->execute(
                "INSERT INTO {$p}category_product (id_category, id_product, position) VALUES " . implode(',', $trozo)
                . ' ON DUPLICATE KEY UPDATE position = VALUES(position)'
            );
        }
        $n = count($idProductos);
        $activa = $n > 0 ? 1 : 0;
        $db->execute("UPDATE {$p}category SET active = {$activa}, date_upd = NOW() WHERE id_category = {$idCategoria} AND active <> {$activa}");
        $db->update('kpy_promo_categoria', ['productos' => $n, 'activa' => $activa, 'date_upd' => date('Y-m-d H:i:s')], "clave = '" . pSQL($clave) . "'");
        if ($quitar !== [] || $valores !== []) {
            self::limpiarCache();
        }

        return $n;
    }

    /** Si el nombre de una categoría nuestra cambia (una permanente que se rebautiza), en todos los idiomas. */
    private static function renombrar(int $idCategoria, string $nombre): void
    {
        $nombre = mb_substr(trim($nombre), 0, 128);
        if ($nombre === '') {
            return;
        }
        Db::getInstance()->execute(
            'UPDATE ' . _DB_PREFIX_ . "category_lang SET name = '" . pSQL($nombre) . "' WHERE id_category = " . $idCategoria . " AND name <> '" . pSQL($nombre) . "'"
        );
    }

    /** Las categorías nuestras, por clave. @return array<string, array> */
    public static function todas(): array
    {
        $salida = [];
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'kpy_promo_categoria ORDER BY clave') ?: [] as $f) {
            $salida[(string) $f['clave']] = $f;
        }

        return $salida;
    }

    /** Las categorías nuestras que están encendidas y con productos. @return int[] ids de categoría */
    public static function activas(string $tipo = ''): array
    {
        return array_map('intval', array_column(Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT id_category FROM ' . _DB_PREFIX_ . 'kpy_promo_categoria WHERE activa = 1 AND productos > 0'
            . ($tipo !== '' ? " AND tipo = '" . pSQL($tipo) . "'" : '')
        ) ?: [], 'id_category'));
    }

    /** Apaga y vacía todas las nuestras (al desinstalar): las direcciones y las filas se quedan. */
    public static function apagarTodas(): void
    {
        foreach (self::todas() as $clave => $f) {
            if (self::existe((int) $f['id_category'])) {
                self::llenar((string) $clave, (int) $f['id_category'], []);
            }
        }
    }

    /**
     * Borra de verdad todas las categorías nuestras y la raíz (solo si se pide expresamente, desde
     * la pantalla): PrestaShop quita los productos de ellas y sus filas de idioma, tienda y grupos.
     */
    public static function borrarTodas(): int
    {
        $n = 0;
        foreach (self::todas() as $f) {
            $c = new Category((int) $f['id_category']);
            if (Validate::isLoadedObject($c)) {
                $c->delete();
                ++$n;
            }
        }
        Db::getInstance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'kpy_promo_categoria');
        $raiz = (int) Configuration::getGlobalValue(self::CONF_RAIZ);
        if ($raiz > 0) {
            $c = new Category($raiz);
            if (Validate::isLoadedObject($c)) {
                $c->delete();
            }
            Configuration::deleteByName(self::CONF_RAIZ);
        }
        self::limpiarCache();

        return $n;
    }

    public static function existe(int $idCategoria): bool
    {
        return $idCategoria > 0 && (bool) Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category WHERE id_category = ' . $idCategoria);
    }

    /** Crea una categoría nuestra con el ObjectModel de PrestaShop (tienda y grupos incluidos). */
    private static function crear(string $rewrite, string $nombre, int $idPadre, bool $activa, string $descripcion): int
    {
        $c = new Category();
        $c->id_parent = $idPadre;
        $c->active = $activa;
        $c->id_shop_default = (int) Configuration::get('PS_SHOP_DEFAULT');
        $c->is_root_category = false;
        foreach (Language::getLanguages(false) as $idioma) {
            $id = (int) $idioma['id_lang'];
            $c->name[$id] = $nombre;
            $c->link_rewrite[$id] = $rewrite;
            $c->description[$id] = $descripcion;
            $c->meta_title[$id] = $nombre;
            $c->meta_description[$id] = $descripcion;
        }
        if (!$c->add()) {
            throw new RuntimeException('no se ha podido crear la categoría ' . $rewrite);
        }
        self::limpiarCache();

        return (int) $c->id;
    }

    /** Que lo siguiente de esta misma petición (y la caché de PrestaShop, si está activa) lea lo nuevo. */
    public static function limpiarCache(): void
    {
        try {
            Cache::clean('Category::*');
            Cache::clean('Product::getProductCategories*');
            Cache::clean('Product::getProductCategoriesFull*');
        } catch (Throwable $e) {
            // La caché no es imprescindible.
        }
    }
}
