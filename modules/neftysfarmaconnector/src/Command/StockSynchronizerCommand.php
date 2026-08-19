<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Command;

use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use PrestaShop\Module\NeftysFarmaConnector\Logger\NeftysFarmaLogger;
use PrestaShop\Module\NeftysFarmaConnector\Service\FTPManager;
use PrestaShop\Module\NeftysFarmaConnector\Service\NeftysFarmaStockSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'neftysfarmaconnector:stock:sync', description: 'Sincroniza el stock con el fichero del FTP de Neftys')]
class StockSynchronizerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $module = \Module::getInstanceByName('neftysfarmaconnector');

            if (!$module->active) {
                $io->warning("El módulo neftysfarmaconnector está desactivado, se interrumpe la ejecución");
                return Command::FAILURE;
            }

            $stockFile = $module->getLocalPath() . 'stock/stock.csv';

            FTPManager::getNeftysFarmaConnection()->downloadStockFileAs($stockFile);

            $stockSynchronizer = new NeftysFarmaStockSynchronizer();
            $stockSynchronizer->stockSync($stockFile);

            $io->writeln($stockSynchronizer->getCountEansSynchronized() . ' productos sincronizados');

            if ($stockSynchronizer->existsMissingEans()) {
                $io->writeln('Missings EANs: ' . count($stockSynchronizer->getMissingEans()));

                /*foreach ($stockSynchronizer->getMissingEans() as $ean) {
                    $output .= $ean . PHP_EOL;
                }*/

            }

            return Command::SUCCESS;

        } catch (NeftysFarmaException $ex) {
            NeftysFarmaLogger::log($ex->getMessage());
            $io->error($ex->getMessage());
            return Command::FAILURE;
        }
    }
}