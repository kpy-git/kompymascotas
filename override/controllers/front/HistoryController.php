<?php
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Util\ColorBrightnessCalculator;
class HistoryController extends HistoryControllerCore
{
    /*
    * module: kpycustomerhistory
    * date: 2026-02-02 17:15:29
    * version: 1.0.0
    */
    /*
    * module: kpycustomerhistory
    * date: 2026-06-03 13:43:25
    * version: 1.0.0
    */
    public function getTemplateVarOrders(): array
    {
        $year = (int)Tools::getValue('year');
        $month = (int)Tools::getValue('month');
        /**
         * La primera vez que entramos en el historial se cargarán los pedidos del mismo mes y año del último pedido
         */
        if (!$year || !$month) {
            $lastOrderDate = Db::getInstance()->getValue(
                'SELECT o.`date_add` 
                    FROM ' . _DB_PREFIX_ . 'orders o 
                    WHERE o.id_customer = ' . $this->context->customer->id .' 
                    ORDER BY o.`date_add` DESC'
            );
            if (!$lastOrderDate) {
                return [];
            }
            $year = (int)date('Y', strtotime($lastOrderDate));
            $month = (int)date('m', strtotime($lastOrderDate));
        }
        $customerOrders = Order::getCustomerOrdersFromYearAndMonth($this->context->customer->id, $year, $month);
        if (empty($customerOrders)) {
            return [];
        }
        $priceFormatter = new PriceFormatter();
        foreach ($customerOrders as &$customerOrder) {
            $customerOrder['date_order'] = Tools::displayDate($customerOrder['date_add']);
            $customerOrder['contrast'] = (new ColorBrightnessCalculator())->isBright($customerOrder['order_state_color']) ? 'dark' : 'bright';
            $customerOrder['total_price'] = $priceFormatter->format(
                $customerOrder['total_paid'],
                Currency::getCurrencyInstance((int) $customerOrder['id_currency'])
            );
            $customerOrder['details_url'] = Context::getContext()->link->getPageLink('order-detail', null, null, 'id_order=' . $customerOrder['id_order']);
        }
        return $customerOrders;
    }
}