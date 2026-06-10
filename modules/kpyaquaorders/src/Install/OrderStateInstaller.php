<?php

declare(strict_types=1);

namespace PrestaShop\Module\KpyAquaOrders\Install;

use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;

class OrderStateInstaller
{
    private AquaOrderStateWarehouse $aquaOrderStateWarehouse;

    public function __construct(private readonly \Module $module)
    {
        $this->aquaOrderStateWarehouse = new AquaOrderStateWarehouse();
    }

    public function install(AquaOrderState $orderState): void
    {
        if ($this->aquaOrderStateWarehouse->getOrderStateId($orderState)) {
            return;
        }

        $newOrderState = new \OrderState();

        $newOrderState->name = [];
        foreach (\Language::getLanguages() as $language) {
            $newOrderState->name[$language['id_lang']] = pSQL($orderState->getPrestaShopName());
        }

        $newOrderState->color = $orderState->getColor();
        $newOrderState->logable = true;
        $newOrderState->hidden = true;
        $newOrderState->module_name = $this->module->name;

        if ($newOrderState->add()) {
            $this->aquaOrderStateWarehouse->saveOrderState($orderState, (int)$newOrderState->id);
        }
    }
}