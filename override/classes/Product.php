<?php

class Product extends ProductCore
{
    public static function isPack($idPack)
    {
        $sql = "SELECT COUNT(*) FROM " . _DB_PREFIX_ . "kpy_packs WHERE id_product_pack = '$idPack'";

        return Db::getInstance()->getValue($sql) > 0;
    }

    public static function getProductsInPack(string $idPack): array
    {
        $sql = "SELECT id_product_item as id, id_product_attribute_item as attr, quantity, is_gift 
        	FROM " . _DB_PREFIX_ . "kpy_packs 
        	WHERE id_product_pack='" . $idPack . "'";

        $productsInPack = Db::getInstance()->executeS($sql);


        return array_map(function (array $row) {
            return [
                'sku' => $row['id'] . "-" . $row['attr'],
                'quantity' => (int)$row['quantity'],
                'is_gift' => (int)$row['is_gift'] > 0,
            ];
        }, $productsInPack);
    }

    public static function getPacksArrayBySku($id, $attr): array
    {
        $sql = "SELECT id_product_pack
                from " . _DB_PREFIX_ . "kpy_packs
                where id_product_item={$id} and id_product_attribute_item={$attr} and is_gift = 0";

        $results = Db::getInstance()->executeS($sql);
        $packs = [];

        if (is_array($results)) {
            foreach ($results as $pack) {
                $packs[] = $pack['id_product_pack'];
            }
        }

        return $packs;
    }



    public static function getProductsInMultipack(int $id, int $attr, int $shop = 1): array
    {
        return [];
    }

    public static function getEanBySku(string $sku): string
    {
        [$id, $attr] = explode('-', $sku);

        if ($attr === '0') {
            return Db::getInstance()->getValue(
                "SELECT ean13 FROM " . _DB_PREFIX_ . "product WHERE id_product=" . $id
            );
        }

        return Db::getInstance()->getValue(
            "SELECT ean13 FROM " . _DB_PREFIX_ . "product_attribute WHERE id_product_attribute=$attr"
        );
    }

    public static function isPienso(int $id_product): bool
    {
        return (int)Db::getInstance()->getValue(
                "SELECT COUNT(*)
            FROM `" . _DB_PREFIX_ . "category_product`
            WHERE id_category IN (1002, 1171, 5681, 1279) and id_product = $id_product",
            ) > 0;
    }

    public static function getWeight(int $id_product, int $id_product_attribute): float
    {
        if ($id_product_attribute > 0) {
            return (float)Db::getInstance()->getValue(
                "SELECT p.weight + pa.weight AS weight
                        FROM " . _DB_PREFIX_ . "product p 
                        INNER JOIN " . _DB_PREFIX_ . "product_attribute pa 
                            ON p.id_product=pa.id_product
                        WHERE p.id_product=$id_product and pa.id_product_attribute=$id_product_attribute"
            );
        }

        return (float)Db::getInstance()->getValue(
            "SELECT weight FROM " . _DB_PREFIX_ . "product WHERE id_product=$id_product"
        );
    }

    /** Modifica la consulta para agrupar los valores (separados por ",") de una misma característica */
    public static function getFrontFeaturesStatic($id_lang, $id_product)
    {
        if (!Feature::isFeatureActive()) {
            return array();
        }
        if (!array_key_exists($id_product . '-' . $id_lang, self::$_frontFeaturesCache)) {
            self::$_frontFeaturesCache[$id_product . '-' . $id_lang] = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                SELECT name, GROUP_CONCAT(value SEPARATOR ", ") AS value, pf.id_feature, f.position
                FROM ' . _DB_PREFIX_ . 'feature_product pf
                LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = ' . (int) $id_lang . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'feature f ON (f.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
                ' . Shop::addSqlAssociation('feature', 'f') . '
                WHERE pf.id_product = ' . (int) $id_product . '
                GROUP BY name
                ORDER BY f.position ASC'
            );
        }

        return self::$_frontFeaturesCache[$id_product . '-' . $id_lang];
    }
}