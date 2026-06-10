<?php

declare(strict_types=1);

class Category extends CategoryCore
{
    public bool $is_main_category;

    public bool $has_image_fixed;

    public ?int $id_product_image_cover;

    public ?string $image_link;

    public ?int $id_twin_category;

    public bool $is_landing;

    public function __construct($idCategory = null, $idLang = null, $idShop = null)
    {
        self::$definition['fields']['is_main_category'] = ['type' => self::TYPE_BOOL, 'validate' => 'isBool'];
        self::$definition['fields']['is_landing'] = ['type' => self::TYPE_BOOL, 'validate' => 'isBool'];
        self::$definition['fields']['has_image_fixed'] = ['type' => self::TYPE_BOOL, 'validate' => 'isBool'];
        self::$definition['fields']['id_product_image_cover'] = ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'];
        self::$definition['fields']['id_twin_category'] = ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'];
        self::$definition['fields']['image_link'] = ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 255];

        parent::__construct($idCategory, $idLang, $idShop);
    }

    /*
     * Obtiene el producto (activo) más vendido de la categoría, si no hay ninguno se devuelve uno aleatorio
     */
    public static function getCoverProductId(int $id_category, int $id_shop = 1): int
    {
        $sqlBestSeller = "SELECT od.product_id 
            FROM " . _DB_PREFIX_ . "order_detail od
            INNER JOIN " . _DB_PREFIX_ . "orders o 
                ON o.id_order = od.id_order
            INNER JOIN " . _DB_PREFIX_ . "category_product cp 
                ON cp.id_product = od.product_id 
                AND cp.id_category = $id_category
            WHERE o.valid = 1 
                AND o.date_add >= date_sub(curdate(), interval 12 month) 
                AND o.id_shop = $id_category
                AND od.total_price_tax_incl > 0 
                AND EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "product_shop ps WHERE ps.id_shop = o.id_shop and ps.active = 1 and ps.id_product = od.product_id)
            GROUP BY od.product_id
            ORDER BY SUM(od.product_quantity) DESC";

        $id = (int)Db::getInstance()->getValue($sqlBestSeller);

        if ($id) {
            return $id;
        }

        $sqlProducts = "SELECT cp.id_product
            FROM " . _DB_PREFIX_ . "category_product cp
            WHERE cp.id_category = $id_category
                AND EXISTS (SELECT 1 FROM " . _DB_PREFIX_ . "product_shop ps WHERE ps.id_shop = $id_shop and ps.active = 1 and ps.id_product = cp.id_product)";

        $results = Db::getInstance()->executeS($sqlProducts);

        if (empty($results)) {
            return 0;
        }

        $products = array_map(function (array $row) {
            return (int)$row['id_product'];
        }, $results);

        return $products[array_rand($products)];
    }
}
