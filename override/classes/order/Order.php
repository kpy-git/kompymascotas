<?php

class Order extends OrderCore
{
    public static function getCustomerOrdersFromYearAndMonth(int $id_customer, int $year, int $month, bool $show_hidden_status = false)
    {
        $orderStates = OrderState::getOrderStates(Context::getContext()->language->id, false);
        $indexedOrderStates = [];
        foreach ($orderStates as $orderState) {
            $indexedOrderStates[$orderState['id_order_state']] = $orderState;
        }

        $sql = '
            SELECT o.id_order, o.date_add, o.total_paid, o.id_currency,
              (SELECT oh.`id_order_state` FROM `' . _DB_PREFIX_ . 'order_history` oh
               LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
               WHERE oh.`id_order` = o.`id_order` ' .
            (!$show_hidden_status ? ' AND os.`hidden` != 1' : '') .
            ' ORDER BY oh.`date_add` DESC, oh.`id_order_history` DESC LIMIT 1) id_order_state
            FROM `' . _DB_PREFIX_ . 'orders` o
            WHERE o.`id_customer` = ' . $id_customer . ' 
                and year(o.`date_add`) = ' . $year . ' 
                and month(o.`date_add`) = ' . $month .
            Shop::addSqlRestriction(Shop::SHARE_ORDER) . '
            GROUP BY o.`id_order`
            ORDER BY o.`date_add` DESC, o.`id_order` DESC';
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$result) {
            return [];
        }

        foreach ($result as $key => $val) {
            // In case order creation crashed midway some data might be absent
            $orderState = !empty($val['id_order_state']) ? $indexedOrderStates[$val['id_order_state']] : null;
            $result[$key]['order_state'] = $orderState['name'] ?? null;
            $result[$key]['invoice'] = $orderState['invoice'] ?? null;
            $result[$key]['order_state_color'] = $orderState['color'] ?? null;
        }

        return $result;
    }

    public function setCurrentStateWithDate(int $newOrderStateId, ?string $date = null, int $id_employee = 0): void
    {
        $date = $date ?? date('Y-m-d H:i:s');

        $newHistory = new OrderHistory();
        $newHistory->id_order = $this->id;
        $newHistory->id_employee = $id_employee;
        $newHistory->date_add = $date;
        $newHistory->date_upd = $date;

        $newHistory->changeIdOrderState($newOrderStateId, $this, true);

        $newHistory->addWithemail(false);
    }
}
