<?php

namespace PrestaShop\Module\KpyHome\Repository;

use Db;

class KpyHomeCategoryRepository
{
    public function getHomeCategories(bool $mobile = false): array
    {
        $sql = "SELECT id_category, title, subtitle, image 
        FROM " . _DB_PREFIX_ . "kpy_home_category
        WHERE mobile = " . ($mobile ? 1 : 0) . "
        ORDER BY position";

        return Db::getInstance()->executeS($sql);
    }
}