<?php

namespace PrestaShop\Module\KpyOrderMargin\ConsoleCommand;

use PrestaShop\Module\KpyOrderMargin\Exception\KpyOrderMarginException;
use PrestaShop\Module\KpyOrderMargin\Service\Client;
use PrestaShop\PrestaShop\Adapter\LegacyContextLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpyordermargin:calculate-margin', description: 'Calcular el margen del pedido indicado')]
class CalculateOrderMarginConsoleCommand extends Command
{
    public function __construct(
        private readonly LegacyContextLoader $legacyContextLoader,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('order', InputArgument::REQUIRED, 'Id del pedido');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $idOrder = $input->getArgument('order');

        if (!is_numeric($idOrder)) {
            $io->error('Id del pedido incorrecto');
            return Command::FAILURE;
        }

        try {
            $this->legacyContextLoader->loadGenericContext();

            $client = new Client();
            $client->calculateMargin($idOrder);

            return Command::SUCCESS;

        } catch (KpyOrderMarginException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}