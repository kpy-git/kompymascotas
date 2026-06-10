<?php

namespace PrestaShop\Module\KpyAquaOrders\ConsoleCommand;

use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kpyaquaorders:unblock-orders-related',
    description: 'Desbloquea los pedidos relacionados que tienen todo el stock reservado para que puedan salir')]
class UnblockOrdersRelatedCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $aqua = DbMssql::getInstance();

            if ($aqua->withError()) {
                $output->writeln($aqua->getSqlError());
                return Command::FAILURE;
            }

            $results = $aqua->execute(
                "SELECT RTRIM(ORG.NUMERO_DOC) AS ORIGINAL, RTRIM(OP.NUMERO_DOC) AS RELACIONADO
            FROM DATPYMORDERSRELATED03 O WITH(NOLOCK)
            INNER JOIN DATOP03 ORG WITH(NOLOCK)
                ON O.ORIGINAL = ORG.NUMERO AND ORG.BLOQUEADO = 1 AND ORG.PENDIENTES > 0
            INNER JOIN DATOP03 OP WITH(NOLOCK)
                ON O.RELACIONADO = OP.NUMERO AND OP.BLOQUEADO = 1 AND OP.PENDIENTES > 0"
            );

            if (empty($results)) {
                $output->writeln('No se ha encontrado ningún pedido relacionado.');
                return Command::SUCCESS;
            }

            $ordersBlocked = [];

            foreach ($results as $pedido) {
                if (!array_key_exists($pedido['ORIGINAL'], $ordersBlocked)) {
                    $ordersBlocked[$pedido['ORIGINAL']] = [];
                }

                $ordersBlocked[$pedido['ORIGINAL']][] = $pedido['RELACIONADO'];
            }

            $aquaOrderController = new AquaOrderController($aqua);
            $unblockOrdersCount = 0;

            foreach ($ordersBlocked as $originalOrder => $relatedOrders) {
                $orders = [$originalOrder, ...$relatedOrders];

                if (array_all($orders, static fn($order) => $aquaOrderController->orderWithStockCompletelyReserved($order))) {
                    foreach ($orders as $order) {
                        $aquaOrderController->orderUnblock($order);
                    }
                    $unblockOrdersCount++;
                }
            }

            $output->writeln("Pedidos encontrados: " . count($ordersBlocked));
            $output->writeln("Pedidos desbloqueados: $unblockOrdersCount");

            return Command::SUCCESS;

        } catch (KpyAquaSqlException $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }
    }
}