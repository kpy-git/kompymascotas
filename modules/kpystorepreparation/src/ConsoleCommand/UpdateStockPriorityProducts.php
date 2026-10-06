<?php

namespace PrestaShop\Module\KpyStorePreparation\ConsoleCommand;

use PrestaShop\Module\KpyStorePreparation\Service\PriorityProductsUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpystorepreparation:update:priority-products', description: 'Actualiza el stock de los productos que hay en tienda para las marcas que tienen prioridad de preparación en tienda')]
class UpdateStockPriorityProducts extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $priorityProductsUpdater = new PriorityProductsUpdater();

        $io->success(sprintf('Actualizado stock de %d productos', $priorityProductsUpdater->updateStock()));

        return Command::SUCCESS;
    }
}