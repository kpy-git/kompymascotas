<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Context;
use Order;

abstract class AbstractCommand implements CommandInterface
{
    protected array $states = [];
    /**
     * @inheritDoc
     */
    abstract public function exec(Order $order): void;

    public function isSupported(Order $order, Context $context): bool
    {
        return in_array($order->current_state, $this->states, true);
    }
}