<?php

namespace PrestaShop\Module\KpyAquaOrders\Command;

use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class MacroCommand extends AbstractCommand
{

    /** @var AbstractCommand[] */
    private array $commands;

    public function __construct()
    {
        $this->commands = [];
    }

    public function addCommand(AbstractCommand $command): self
    {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * @throws KpyAquaOrderException
     * @throws KpyAquaSqlException
     */
    public function exec(\Order $order): void
    {
        foreach ($this->commands as $command) {
            $command->exec($order);
        }
    }
}