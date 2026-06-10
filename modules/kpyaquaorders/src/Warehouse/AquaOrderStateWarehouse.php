<?php

namespace PrestaShop\Module\KpyAquaOrders\Warehouse;

use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;

class AquaOrderStateWarehouse
{
    private const string FILE_STATES = __DIR__ . '/order_states.json';

    private array $orderStates;

    private static ?self $instance = null;

    public function __construct()
    {
        $this->orderStates = json_decode(file_get_contents(self::FILE_STATES), true);
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getOrderStateId(AquaOrderState $orderState): int
    {
        return $this->orderStates[$orderState->value]['id_order_state'] ?? 0;
    }

    public function saveOrderState(AquaOrderState $orderState, int $orderStateId): void
    {
        $this->orderStates[$orderState->value] = [
            'id_order_state' => $orderStateId,
            'estados_asociados' => [$orderStateId],
        ];

        file_put_contents(self::FILE_STATES, json_encode($this->orderStates, JSON_PRETTY_PRINT));
    }

    public function getAssociatedOrderStates(AquaOrderState $orderState): array
    {
        return $this->orderStates[$orderState->value]['estados_asociados'] ?? [];
    }

    public function isSupported(int $orderStateId): bool
    {
        return array_any($this->orderStates, static fn (array $orderState) => in_array($orderStateId, $orderState['estados_asociados']));
    }
}