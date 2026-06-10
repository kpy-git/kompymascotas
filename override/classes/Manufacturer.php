<?php
declare(strict_types=1);
class Manufacturer extends ManufacturerCore
{
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public function getCategoryRelated(): int
    {
        return self::getCategoryRelatedByManufacturer($this->id);
    }
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public static function getCategoryRelatedByManufacturer(int $idManufacturer): int
    {
        return (int)Db::getInstance()->getValue(
            'SELECT id_category_related 
                    FROM ' . _DB_PREFIX_ . 'kpy_manufacturer 
                    WHERE id_manufacturer=' . $idManufacturer);
    }
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public static function getNameByRewrite(string $rewrite): string
    {
        return Db::getInstance()->getValue(
            "SELECT name 
                    FROM `" . _DB_PREFIX_ . "manufacturer` 
                    WHERE id_manufacturer = " . self::getIDByRewrite($rewrite)
        ) ?? '';
    }
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public static function getIDByRewrite(string $rewrite): int
    {
        return (int)Db::getInstance()->getValue(
            "SELECT id_manufacturer 
                    FROM " . _DB_PREFIX_ . "kpy_manufacturer 
                    WHERE link_rewrite = '$rewrite'"
        );
    }
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public function getLinkRewrite(): string
    {
        return self::getLinkRewriteById($this->id);
    }
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public static function getLinkRewriteById(int $idManufacturer): string
    {
        return Db::getInstance()->getValue(
            "SELECT link_rewrite 
                FROM " . _DB_PREFIX_ . "kpy_manufacturer 
                WHERE id_manufacturer = {$idManufacturer}"
        ) ?? '';
    }
    /*
    * module: kpymanufacturer
    * date: 2026-06-03 13:45:11
    * version: 1.0.0
    */
    public static function getAllManufacturersLinkRewriteById(bool $only_active = true): array
    {
        $sql = "SELECT m.name, m.id_manufacturer, km.link_rewrite
            FROM " . _DB_PREFIX_ . "manufacturer m
            INNER JOIN " . _DB_PREFIX_ . "kpy_manufacturer km
                ON m.id_manufacturer = km.id_manufacturer";
        if ($only_active) {
            $sql .= " WHERE m.active = 1 AND exists (SELECT 1
                FROM " . _DB_PREFIX_ . "product p
                WHERE p.id_manufacturer = m.id_manufacturer AND p.active=1)";
        }
        $sql .= " ORDER BY m.name";
        return array_map(function (array $manufacturer) {
            return [
                'id' => $manufacturer['id_manufacturer'],
                'name' => $manufacturer['name'],
                'link_rewrite' => $manufacturer['link_rewrite'],
            ];
        }, Db::getInstance()->executeS($sql));
    }
}