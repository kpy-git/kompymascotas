<?php

namespace PrestaShop\Module\KpyOrderMaker\Service;

use Context;
use Db;

class TaxRateFinder
{
    private int $countryId;

    private Context $context;

    public function __construct(int $countryId = 6, ?Context $context = null)
    {
        $this->countryId = $countryId;
        $this->context = $context ?? Context::getContext();
    }

    public function getProductTaxRate(?int $productId): float
    {
        return (float)Db::getInstance()->getValue(
            "SELECT (1 + (rate/100))
                FROM " . _DB_PREFIX_ . "tax t
                WHERE id_tax = (
                    SELECT tr.id_tax
                    FROM " . _DB_PREFIX_ . "tax_rule tr
                    WHERE tr.id_country = " . $this->countryId . " AND
                          tr.id_tax_rules_group =  (
                                SELECT id_tax_rules_group 
                                FROM " . _DB_PREFIX_ . "product_shop ps 
                                WHERE ps.id_shop = " . $this->context->shop->id . " 
                                  and ps.id_product = $productId))"
        ) ?: 1.1;
    }


}