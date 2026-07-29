<?php

namespace PrestaShop\Module\KpyProductSync\ConsoleCommand;

use PrestaShop\Module\KpyProductSync\Service\ProductSynchronizer;
use PrestaShop\Module\KpyProductSync\ValueObject\ProductCode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kpyproductsync:sync:all-products',
    description: 'Synchronize all products with app_db',
)]
class SyncAllProductCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('manufacturer', InputArgument::OPTIONAL, 'Id product manufacturer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {

            $productSynchronizer = new ProductSynchronizer();

            $io->success($productSynchronizer->syncProductsInBatch() . ' products synchronization successful');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}