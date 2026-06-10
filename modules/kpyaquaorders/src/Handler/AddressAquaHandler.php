<?php

namespace PrestaShop\Module\KpyAquaOrders\Handler;

use Order;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Factory\AddressAquaFactory;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

readonly class AddressAquaHandler
{
    public function __construct(private AquaOrderController $aquaOrderController)
    {
    }

    /**
     * @throws KpyAquaSqlException
     */
    public function updateAddressAqua(Order $order): void
    {
        $addressAquaFactory = new AddressAquaFactory();
        $addressAqua = $addressAquaFactory->getAddressAquaByCarrier($order);

        $addressAqua->export();


        // Cuando se modifica una dirección desde el botón modificar del pedido, PrestaShop crea una nueva dirección no actualiza...
        // se cambia en el pedido de AQUA la dirección antigua por la nueva
        $this->aquaOrderController->updateAddress($order->id, $addressAqua->getId());


    }
}