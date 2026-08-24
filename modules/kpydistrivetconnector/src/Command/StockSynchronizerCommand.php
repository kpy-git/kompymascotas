<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Command;

use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetClient;
use PrestaShop\Module\KpyDistrivetConnector\Service\StockSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'kpydistrivetconnector:stock:sync', description: 'Sincroniza el stock con la API de Distrivet')]
class StockSynchronizerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {

            $distrivetClient = new DistrivetClient();
            $stock = $distrivetClient->getStock();

            $stockSynchronizer = new StockSynchronizer();
            $stockSynchronizer->synchronize($stock['stocks']);

            $io->success(sprintf(
                'Productos sincronizados: %d de %d en total',
                $stockSynchronizer->getCountProductsSynchronized(),
                $stock['total_count']
            ));

            return Command::SUCCESS;

        } catch (KpyDistrivetException $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
    }
}