<?php

namespace PrestaShop\Module\KpyOrderDispatcher\Repository;

class OrderRepository
{
    public function findOrdersToBeFinished(): array
    {
        $results = \Db::getInstance()->executeS(
            "select oh.id_order
                from " . _DB_PREFIX_ . "order_history oh
                where oh.id_order_state = 5
                    AND DATEDIFF(CURDATE(), DATE_FORMAT(oh.date_add, '%Y-%m-%d')) >= 14
                    AND NOT EXISTS (SELECT 1 from " . _DB_PREFIX_ . "orders o where o.id_order = oh.id_order and o.current_state = 22)"
        );

        return array_map(static fn (array $row): int => $row['id_order'], $results);
    }
}