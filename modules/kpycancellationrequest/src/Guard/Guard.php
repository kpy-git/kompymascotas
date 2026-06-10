<?php

namespace PrestaShop\Module\KpyCancellationRequest\Guard;

use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderLazyArray;

class Guard
{
    public static function isOrderCancelable(OrderLazyArray $order): bool
    {
        $currentState = (int)$order->getHistory()['current']['id_order_state'];

        $restrictedStates = [4, 5, 6, 21, 30, (int)\Configuration::get(\KpyCancellationRequest::CANCELLATION_REQUEST)];

        return !in_array($currentState, $restrictedStates);
    }

    public static function canBeCancelledCancellationRequest(OrderLazyArray $order): bool
    {
        return $order->getHistory()['current']['id_order_state'] === (int)\Configuration::get(\KpyCancellationRequest::CANCELLATION_REQUEST);
    }
}