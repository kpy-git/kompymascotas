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
    name: 'kpyproductsync:sync:product',
    description: 'Synchronize product with app_db',
)]
class SyncProductCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('sku', InputArgument::REQUIRED, 'SKU');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $productCode = ProductCode::fromSKU($input->getArgument('sku'));

            $productSynchronizer = new ProductSynchronizer();
            $productSynchronizer->syncProduct($productCode);

            $io->success($input->getArgument('sku') . ' product synchronization successful');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}