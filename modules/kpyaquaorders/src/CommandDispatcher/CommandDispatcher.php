<?php

namespace PrestaShop\Module\KpyAquaOrders\CommandDispatcher;

use PrestaShop\Module\KpyAquaOrders\Command\AbstractCommand;
use PrestaShop\Module\KpyAquaOrders\Command\CancelOrderCommand;
use PrestaShop\Module\KpyAquaOrders\Command\CancelCancellationOrderRequest;
use PrestaShop\Module\KpyAquaOrders\Command\CancellationOrderRequest;
use PrestaShop\Module\KpyAquaOrders\Command\NeftysFarmaOrderCommand;
use PrestaShop\Module\KpyAquaOrders\Command\UpdateOrderCommand;
use PrestaShop\Module\KpyAquaOrders\Command\UpdateProductsCommand;
use PrestaShop\Module\KpyAquaOrders\Command\NewOperationCommand;
use PrestaShop\Module\KpyAquaOrders\Command\NewOrderCommand;
use PrestaShop\Module\KpyAquaOrders\Command\OrderRenameCommand;
use PrestaShop\Module\KpyAquaOrders\Command\UpdateAddressCommand;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class CommandDispatcher
{
    /** @var AbstractCommand[] */
    private array $commands;


    public function __construct(private readonly AquaOrderController $aquaOrderController)
    {
        $newOperationCommand = new NewOperationCommand();

        $this->commands = [
            new NewOrderCommand($this->aquaOrderController),
            new UpdateOrderCommand($this->aquaOrderController, new NewOrderCommand($this->aquaOrderController)),
            new UpdateAddressCommand(),
            new UpdateProductsCommand($this->aquaOrderController, $newOperationCommand),
            new OrderRenameCommand($this->aquaOrderController, $newOperationCommand),
            new CancelOrderCommand($this->aquaOrderController),
            new CancellationOrderRequest($this->aquaOrderController),
            new CancelCancellationOrderRequest($this->aquaOrderController),
            new NeftysFarmaOrderCommand($this->aquaOrderController),
        ];
    }

    /**
     * @throws KpyAquaOrderException
     * @throws KpyAquaSqlException
     */
    public function dispatchAction(\Order $order, ?\Context $context = null): void
    {
        if (null === $context) {
            $context = \Context::getContext();
        }

        foreach ($this->commands as $command) {
            if ($command->isSupported($order, $context)) {
                $command->exec($order);
                return;
            }
        }

        throw new KpyAquaOrderException('ERROR: No se ha podido encontrar ningún comando para el estado: ' . $order->current_state);
    }

}