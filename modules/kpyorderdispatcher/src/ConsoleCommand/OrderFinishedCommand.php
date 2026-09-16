<?php

namespace PrestaShop\Module\KpyOrderDispatcher\ConsoleCommand;

use PrestaShop\Module\KpyOrderDispatcher\Repository\OrderRepository;
use PrestaShop\PrestaShop\Adapter\LegacyContextLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpyorderdispatcher:update:orders-finished', description: 'Finaliza los pedidos entregados desde haca 14 días')]
class OrderFinishedCommand extends Command
{
    public function __construct(
        private readonly LegacyContextLoader $legacyContextLoader,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orderRepository = new OrderRepository();

        $orders = $orderRepository->findOrdersToBeFinished();

        if (empty($orders)) {
            $io->success('No existen pedidos pendientes de finalizar');
            return Command::SUCCESS;
        }

        $this->legacyContextLoader->loadGenericContext();
        $context = \Context::getContext();
        $context->employee->id = 0;
        $context->employee->id_profile = 1;

        foreach ($orders as $orderPending) {
            $order = new \Order($orderPending);

            $order->setCurrentStateWithDate(22); // Pedido finalizado
        }

        $io->success(count($orders) . ' pedidos finalizados');

        return Command::SUCCESS;
    }
}