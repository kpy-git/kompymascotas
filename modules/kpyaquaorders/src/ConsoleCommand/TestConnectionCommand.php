<?php

namespace PrestaShop\Module\KpyAquaOrders\ConsoleCommand;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kpyaquaorders:test-connection', description: 'Test SQL Server connection')]
class TestConnectionCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $aqua = DbMssql::getInstance();

            $lastOrder = $aqua->getValue("SELECT TOP 1 RTRIM(NUMERO_DOC) AS PEDIDO FROM DATOP01 WITH(NOLOCK) WHERE TIPOOPER = 'C' ORDER BY NUMERO DESC");

            $output->writeln("<info>Testing order number $lastOrder</info>");

            return Command::SUCCESS;

        } catch (KpyAquaSqlException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
