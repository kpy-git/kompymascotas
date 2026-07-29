<?php

namespace PrestaShop\Module\KpyProductSync\ConsoleCommand;

use PrestaShop\Module\KpyProductSync\Database\AppDb;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kpyproductsync:test-connection',
    description: 'Test connection with app_db',
)]
class TestConnectionCommand extends Command
{

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $appDb = AppDb::getInstance();

            $appDb->execute("SELECT NOW()");

            $io->success('Successfully connected to database');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}