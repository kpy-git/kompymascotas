<?php
/**
 * Qué categorías tienen producto. Una categoría «vacía» es la que no tiene ningún producto activo y
 * visible ni directamente ni en ninguna de sus subcategorías activas (el árbol se recorre con
 * nleft/nright, que PrestaShop mantiene al día).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

final class KseoCategorias
{
    /** @var array<int, int> */
    private static array $cache = [];

    public static function productosVisibles(int $idCategory, ?int $idShop = null): int
    {
        $idShop = $idShop ?? (int) Context::getContext()->shop->id;
        $clave = $idShop * 10000000 + $idCategory;
        if (!isset(self::$cache[$clave])) {
            $sql = 'SELECT COUNT(DISTINCT cp.id_product)
                FROM `' . _DB_PREFIX_ . 'category` c
                INNER JOIN `' . _DB_PREFIX_ . 'category` c2 ON c2.nleft >= c.nleft AND c2.nright <= c.nright
                INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs2 ON cs2.id_category = c2.id_category AND cs2.id_shop = ' . $idShop . '
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON cp.id_category = c2.id_category
                INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = cp.id_product AND ps.id_shop = ' . $idShop . '
                    AND ps.active = 1 AND ps.visibility <> \'none\'
                WHERE c.id_category = ' . $idCategory . ' AND c2.active = 1';
            self::$cache[$clave] = (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        return self::$cache[$clave];
    }

    public static function estaVacia(int $idCategory, ?int $idShop = null): bool
    {
        return self::productosVisibles($idCategory, $idShop) === 0;
    }

    /**
     * Las categorías activas con producto (propio o de sus hijas), sin la raíz ni la home.
     *
     * @return array<int, array{rewrite: string, date_upd: string}>
     */
    public static function conProducto(int $idLang, int $idShop): array
    {
        $raiz = (int) Configuration::get('PS_ROOT_CATEGORY');
        $home = (int) Configuration::get('PS_HOME_CATEGORY');
        $sql = 'SELECT c.id_category, c.nleft, c.nright, c.date_upd, cl.link_rewrite,
                (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'category_product` cp
                    INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.id_product = cp.id_product AND ps.id_shop = ' . $idShop . '
                        AND ps.active = 1 AND ps.visibility <> \'none\'
                    WHERE cp.id_category = c.id_category) AS n
            FROM `' . _DB_PREFIX_ . 'category` c
            INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON cs.id_category = c.id_category AND cs.id_shop = ' . $idShop . '
            INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = c.id_category AND cl.id_lang = ' . $idLang . ' AND cl.id_shop = ' . $idShop . '
            WHERE c.active = 1 AND c.id_category NOT IN (' . $raiz . ', ' . $home . ')
            ORDER BY c.nleft';
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql) ?: [];

        // nleft de las que tienen producto propio, para saber si una vacía tiene hijas con producto
        $conPropio = [];
        foreach ($filas as $fila) {
            if ((int) $fila['n'] > 0) {
                $conPropio[] = (int) $fila['nleft'];
            }
        }
        sort($conPropio);

        $resultado = [];
        foreach ($filas as $fila) {
            $tiene = (int) $fila['n'] > 0;
            if (!$tiene) {
                $izq = (int) $fila['nleft'];
                $der = (int) $fila['nright'];
                foreach ($conPropio as $nleft) {
                    if ($nleft > $der) {
                        break;
                    }
                    if ($nleft > $izq) {
                        $tiene = true;
                        break;
                    }
                }
            }
            if ($tiene) {
                $resultado[(int) $fila['id_category']] = [
                    'rewrite' => (string) $fila['link_rewrite'],
                    'date_upd' => (string) $fila['date_upd'],
                ];
            }
        }

        return $resultado;
    }
}
