<?php

namespace PrestaShop\Module\KpyHome\Repository;

use Db;
use PrestaShop\Module\KpyHome\Entity\KpyHomeSliderBanner;

class KpyHomeSliderBannerRepository
{
    public function getBannersActive(bool $mobile = false): array
    {
        $sql = "SELECT id_slider_banner, image, description, url 
            FROM " . _DB_PREFIX_ . "kpy_home_slider_banner 
            WHERE active = 1
            AND mobile = " . ($mobile ? 1 : 0) . " 
            AND (
                (date_from IS NULL AND date_to IS NULL)
                OR (NOW() BETWEEN date_from AND date_to) 
                OR (date_from IS NULL AND NOW() < date_to) 
                OR (date_to IS NULL AND NOW() > date_from)
            )
            ORDER BY position, id_slider_banner DESC";

        $results = Db::getInstance()->executeS($sql);

        if (empty($results)) {
            return [];
        }

        return array_map(static function (array $row) {
            return (new KpyHomeSliderBanner())
                ->setId($row['id_slider_banner'])
                ->setImage($row['image'])
                ->setDescription($row['description'])
                ->setUrl($row['url']);
        }, $results);
    }
}