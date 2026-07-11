<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Services;

use Db;

class ProductAvailabilityMessagesHandler
{
    public function getProductMessageInStock(int $productId, int $productAttributeId, int $idLang): string
    {
        if ($productAttributeId) {
            return Db::getInstance()->getValue(
                "SELECT available_now FROM " . _DB_PREFIX_ . "product_attribute_lang 
            WHERE id_product_attribute = $productAttributeId 
                AND id_lang = $idLang",
            ) ?: '';
        }

        return Db::getInstance()->getValue(
            "SELECT available_now FROM " . _DB_PREFIX_ . "product_lang 
            WHERE id_product = $productId 
                AND id_lang = $idLang",
        ) ?: '';
    }

    public function getProductMessageOutStock(int $productId, int $productAttributeId, int $idLang): string
    {
        if ($productAttributeId) {
            return Db::getInstance()->getValue(
                "SELECT available_later FROM " . _DB_PREFIX_ . "product_attribute_lang 
            WHERE id_product_attribute = $productAttributeId 
                AND id_lang = $idLang",
            ) ?: '';
        }

        return Db::getInstance()->getValue(
            "SELECT available_later FROM " . _DB_PREFIX_ . "product_lang 
            WHERE id_product = $productId 
                AND id_lang = $idLang",
        ) ?: '';
    }
}