<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use Order;
use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;

class NewOrderCommand extends MacroCommand
{

    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        parent::__construct();

        $this->states = AquaOrderStateWarehouse::getInstance()->getAssociatedOrderStates(AquaOrderState::NEW_ORDER);

        $this->addCommand(new UpdateCustomerCommand());
        $this->addCommand(new UpdateAddressCommand());
        $this->addCommand(new NewOperationCommand());
    }

    /**
     * @throws KpyAquaOrderException
     */
    public function isSupported(Order $order, \Context $context): bool
    {
        $isCRM = in_array($order->module, ['cashondelivery', 'codfee', 'cashondeliveryplus']);

        if (parent::isSupported($order, $context)
            // los pedidos contra reembolso se ponen en 'Preparación en curso' al hacer un pedido nuevo
            || ($order->current_state === 3 && $isCRM)
        ) {
            // los contra reembolso que se echan para atrás y vuelven se vuelven a preparar lanzarían la excepción
            if (!$isCRM && $this->aquaOrderController->existsOrder($order->id)) {
                throw new KpyAquaOrderException(__METHOD__  . ': Intento de inserción de un pedido ya existente.');
            }

            return true;
        }

        return false;
    }
}