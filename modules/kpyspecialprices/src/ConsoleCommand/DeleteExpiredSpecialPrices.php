<?php

namespace PrestaShop\Module\KpySpecialPrices\ConsoleCommand;

use PrestaShop\Module\KpySpecialPrices\Service\SpecialPriceCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpyspecialprices:delete:expired', description: 'Delete expired special prices')]
class DeleteExpiredSpecialPrices extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $cleaner = new SpecialPriceCleaner();

            $io->success(sprintf('Eliminados %d precios especiales', $cleaner->deleteExpired()));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}