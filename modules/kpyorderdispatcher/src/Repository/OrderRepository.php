<?php

namespace PrestaShop\Module\KpyOrderDispatcher\Repository;

class OrderRepository
{
    public function findOrdersToBeFinished(): array
    {
        $results = \Db::getInstance()->executeS(
            "select o.id_order
                from " . _DB_PREFIX_ . "orders o
                inner join " . _DB_PREFIX_ . "order_history oh
                    on oh.id_order = o.id_order and oh.id_order_state = o.current_state
                where o.current_state = 5
                    AND DATEDIFF(CURDATE() , DATE_FORMAT(oh.date_add, '%Y-%m-%d')) >= 14"
        );

        return array_map(static fn (array $row): int => $row['id_order'], $results);
    }
}