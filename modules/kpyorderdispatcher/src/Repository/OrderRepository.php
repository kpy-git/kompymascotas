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

    public function findOrdersToRequestReview(): array
    {
        // pedidos entregados en menos de 5 días desde que se realiza el pedido
        $results = \Db::getInstance()->executeS(
            "select o.id_order, DATE_FORMAT(oh.date_add, '%d-%m-%Y') as `date_shipped`, DATE_FORMAT(o.date_add, '%d-%m-%Y') as `date_add`
                    from " . _DB_PREFIX_ . "orders o
                    inner join " . _DB_PREFIX_ . "order_history oh
                        on oh.id_order = o.id_order and o.current_state = oh.id_order_state
                        and DATEDIFF(now(), oh.date_add) < 7
                    where o.id_order > 866180
                      and o.current_state = 5
                      and o.total_paid > 0 and DATEDIFF(oh.date_add, o.date_add) <= 5"
        );

        return array_reduce($results, static function (array $carry, array $row): array {
            $carry[$row['id_order']] = [
                'date_add' => $row['date_add'],
                'date_shipped' => $row['date_shipped'],
            ];
            return $carry;
        }, []);
    }
}