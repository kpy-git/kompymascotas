<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Command;

use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Repository\StockRepository;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetCostSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpydistrivetconnector:prices:sync', description: 'Sincroniza el precio neto de todos los productos servidos por Distrivet')]
class PriceSynchronizerCommand extends Command
{
    protected function configure()
    {
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Número máximo de productos para actualizar');
        $this->addOption('manufacturer', null, InputOption::VALUE_OPTIONAL, 'Actualiza los productos del fabricante indicado');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $costSynchronizer = new DistrivetCostSynchronizer();

            if ($input->getOption('manufacturer') && !is_numeric($input->getOption('manufacturer'))) {
                $io->error('El fabricante debe ser un entero');
                return Command::FAILURE;
            }

            $productsCost = $input->getOption('manufacturer')
                ? $costSynchronizer->getProductsCostByManufacturer(
                    (int)$input->getOption('manufacturer'),
                    $input->getOption('limit') ?? 0)
                : $costSynchronizer->getAllProductsCost($input->getOption('limit') ?? 0);

            $stockRepository = new StockRepository();
            $stockRepository->saveProductCostPrice($productsCost);

            $io->success($costSynchronizer->getCountSynchronizedProducts() . " productos sincronizados");

            return Command::SUCCESS;

        } catch (KpyDistrivetException $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
    }
}