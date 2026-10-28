<?php

namespace PrestaShop\Module\KpyAquaOrders\Type;

enum Discount: string
{
    case PRODUCTS = 'products';
    case MANUFACTURERS = 'manufacturers';
    case CATEGORIES = 'categories';
    case SKUS = 'skus';
    case COMBINATIONS = 'combinations';
    case NONE = 'none';

    public function buildQuery(int $orderId, int $shopId): string
    {
        return match ($this) {
            self::COMBINATIONS => sprintf(
                "select t.rate
                from " . _DB_PREFIX_ . "order_cart_rule ocr
                inner join " . _DB_PREFIX_ . "cart_rule cr on cr.id_cart_rule = ocr.id_cart_rule
                inner join " . _DB_PREFIX_ . "kpy_coupon kc on kc.codigo = cr.code
                inner join " . _DB_PREFIX_ . "kpy_coupon_item kci on kci.id_kpy_coupon = kc.id_kpy_coupon
                inner join " . _DB_PREFIX_ . "order_detail od on od.id_order = ocr.id_order
                    and od.product_id = kci.id_product and od.product_attribute_id=kci.clave
                inner join " . _DB_PREFIX_ . "product_shop ps on ps.id_product = od.product_id and ps.id_shop=1
                inner join " . _DB_PREFIX_ . "tax_rule tr on tr.id_tax_rules_group = ps.id_tax_rules_group
                    and tr.id_country = 6
                inner join " . _DB_PREFIX_ . "tax t on t.id_tax = tr.id_tax
                where ocr.id_order = %d", $orderId
            ),
            self::SKUS => sprintf(
                "SELECT trg.alias
                        FROM " . _DB_PREFIX_ . "order_cart_rule ocr
                        inner join " . _DB_PREFIX_ . "pym_cupones pc 
                            on pc.id_cart_rule = ocr.id_cart_rule
                        inner join " . _DB_PREFIX_ . "order_detail od 
                            on od.id_order=ocr.id_order
                                and od.product_id = pc.id_product
                                and pc.id_product_attribute = od.product_attribute_id
                        inner join " . _DB_PREFIX_ . "product_shop ps 
                            on ps.id_product = od.product_id 
                                and ps.id_shop = %d
                        inner join " . _DB_PREFIX_ . "tax_rules_group trg 
                            on trg.id_tax_rules_group = ps.id_tax_rules_group
                        where ocr.id_order = %d",
                $shopId,
                $orderId
            ),
            self::PRODUCTS => sprintf(
                "SELECT trg.alias
                        FROM " . _DB_PREFIX_ . "order_cart_rule ocr
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule_group crg
                            on crg.id_cart_rule = ocr.id_cart_rule
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule crpr
                            on crpr.id_product_rule_group = crg.id_product_rule_group a
                                nd crpr.`type` = 'products'
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule_value crprv
                            on crprv.id_product_rule = crpr.id_product_rule
                        inner join " . _DB_PREFIX_ . "order_detail od
                            on od.product_id = crprv.id_item 
                                and od.id_order = ocr.id_order
                        inner join " . _DB_PREFIX_ . "product_shop ps 
                            on ps.id_product = od.product_id and ps.id_shop = %d
                        inner join " . _DB_PREFIX_ . "tax_rules_group trg 
                            on trg.id_tax_rules_group = ps.id_tax_rules_group
                        where ocr.id_order = %d",
                $shopId,
                $orderId
            ),
            self::MANUFACTURERS => sprintf(
                "SELECT trg.alias
                        FROM " . _DB_PREFIX_ . "order_cart_rule ocr
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule_group crg
                            on crg.id_cart_rule = ocr.id_cart_rule
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule crpr
                            on crpr.id_product_rule_group = crg.id_product_rule_group
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule_value crprv
                            on crprv.id_product_rule = crpr.id_product_rule
                        inner join " . _DB_PREFIX_ . "order_detail od
                            on od.id_order = ocr.id_order
                        inner join " . _DB_PREFIX_ . "product p
                            on p.id_product = od.product_id 
                                and p.id_manufacturer = crprv.id_item
                        inner join " . _DB_PREFIX_ . "product_shop ps
                            on ps.id_product = p.id_product and ps.id_shop = %d
                        inner join " . _DB_PREFIX_ . "tax_rules_group trg 
                            on trg.id_tax_rules_group = ps.id_tax_rules_group
                        where ocr.id_order = %d",
                $shopId,
                $orderId
            ),
            self::CATEGORIES => sprintf(
                "SELECT trg.alias
                        FROM " . _DB_PREFIX_ . "order_cart_rule ocr
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule_group crg
                            on crg.id_cart_rule = ocr.id_cart_rule
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule crpr
                            on crpr.id_product_rule_group = crg.id_product_rule_group 
                                and crpr.`type` = 'categories'
                        inner join " . _DB_PREFIX_ . "cart_rule_product_rule_value crprv
                            on crprv.id_product_rule = crpr.id_product_rule
                        inner join " . _DB_PREFIX_ . "category_product cp 
                            on cp.id_category = crprv.id_item
                        inner join " . _DB_PREFIX_ . "order_detail od 
                            on od.id_order = ocr.id_order and od.product_id = cp.id_product
                        inner join " . _DB_PREFIX_ . "product_shop ps 
                            on ps.id_product = od.product_id and ps.id_shop = %d
                        inner join " . _DB_PREFIX_ . "tax_rules_group trg 
                            on trg.id_tax_rules_group = ps.id_tax_rules_group
                        where ocr.id_order = %d",
                $shopId,
                $orderId
            ),
            default => '',
        } ?: 0;
    }
}
