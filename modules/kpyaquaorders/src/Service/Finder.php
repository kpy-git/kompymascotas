<?php

namespace PrestaShop\Module\KpyAquaOrders\Service;

use Db;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Type\Discount;

class Finder
{
    private Db $ps;

    public function __construct(private readonly DbMssql $aqua)
    {
        $this->ps = Db::getInstance();
    }

    public function getLogisticProductInfoBySku(string $sku): AquaLogisticProductInfo
    {
        $results = $this->aqua->getRow(
            "SELECT PESO, VOLUMEN, DESCRIPCIO FROM DATIN03 WITH(NOLOCK) WHERE CODIGO='{$sku}'");

        return new AquaLogisticProductInfo(
            (float)$results['PESO'],
            (float)$results['VOLUMEN'],
            trim($results['DESCRIPCIO'])
        );
    }

    public function numeroDePedidosDeFabricante(int $fabricante, int $cliente): int
    {
        return (int)$this->ps->getValue(
            "select count(distinct o.id_order)
                from " . _DB_PREFIX_ . "orders o
                inner join " . _DB_PREFIX_ . "order_detail od 
                    on od.id_order = o.id_order
                inner join " . _DB_PREFIX_ . "product p 
                    on p.id_product = od.product_id and p.id_manufacturer = $fabricante
                where o.id_customer = $cliente 
                    and o.module != 'free_order' 
                    and o.id_carrier != 249"
        );
    }

    public function pedidoConProductosDeFabricante(int $pedido, int $fabricante): bool
    {
        return (int)$this->ps->getValue(
                "select count(*)
                from " . _DB_PREFIX_ . "orders o
                inner join " . _DB_PREFIX_ . "order_detail od 
                    on od.id_order = o.id_order
                inner join " . _DB_PREFIX_ . "product p 
                    on p.id_product = od.product_id and p.id_manufacturer = $fabricante
                where o.id_order = $pedido
                    and o.module != 'free_order' 
                    and o.id_carrier != 249"
            ) > 0;
    }

    public function esPrimeraCompraDeProductosRCSeleccionadosParaCliente(int $customerId): bool
    {
        return (int)$this->ps->getValue(
                "select count(distinct o.id_order)
            from " . _DB_PREFIX_ . "orders o
            inner join " . _DB_PREFIX_ . "order_detail od
                on od.id_order = o.id_order and od.product_weight >= 8 and od.product_weight <= 15
            inner join " . _DB_PREFIX_ . "product p on
                p.id_product = od.product_id and p.id_manufacturer=3
            where o.valid = 1 and o.module != 'free_order' o.id_carrier != 249
                and EXISTS (select * 
                    from " . _DB_PREFIX_ . "category_product cp 
                    where cp.id_product = od.product_id 
                        and cp.id_category in (1678, 1679, 1669, 1684))
                and o.id_customer = $customerId"
            ) === 1;
    }

    public function getIdReferenceByCarrierId(int $id_carrier): int
    {
        return (int)$this->ps->getValue(
            "SELECT id_reference FROM " . _DB_PREFIX_ . "carrier WHERE id_carrier = $id_carrier");
    }

    public function getOrderWeight(int $id_order): float
    {
        return (float)$this->ps->getValue(
            "SELECT sum(product_weight*product_quantity) FROM " . _DB_PREFIX_ . "order_detail WHERE id_order = $id_order");
    }

    public function getDiscountType(int $orderId): Discount
    {
        $type =  (int)$this->ps->getValue(
            "SELECT DISTINCT crpr.`type`
                    FROM " . _DB_PREFIX_ . "order_cart_rule ocr
                    inner join " . _DB_PREFIX_ . "cart_rule_product_rule_group crg
                        on crg.id_cart_rule = ocr.id_cart_rule
                    inner join " . _DB_PREFIX_ . "cart_rule_product_rule crpr
                        on crpr.id_product_rule_group = crg.id_product_rule_group
                    where ocr.id_order = $orderId 
                        and crpr.`type` in ('products', 'manufacturers', 'skus', 'categories')"
        );

        return Discount::tryFrom($type) ?? Discount::NONE;
    }

    public function getOrderDiscounts(int $id)
    {
        return Db::getInstance()->executeS(
            "SELECT SUM(ocr.value) as descuento, ocr.free_shipping, cr.gift_product, cr.product_restriction
                FROM " . _DB_PREFIX_ . "order_cart_rule ocr
                INNER JOIN " . _DB_PREFIX_ . "cart_rule cr ON ocr.id_cart_rule = cr.id_cart_rule
                WHERE ocr.id_order = $id
                GROUP BY ocr.free_shipping, cr.gift_product, cr.product_restriction
                ORDER BY ocr.free_shipping DESC");
    }
}