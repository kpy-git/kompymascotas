<?php

namespace PrestaShop\Module\KpyOrderDispatcher\ConsoleCommand;

use PrestaShop\Module\KpyOrderDispatcher\Repository\OrderRepository;
use PrestaShop\PrestaShop\Adapter\LegacyContextLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpyorderdispatcher:update:orders-request-review')]
class OrdersRequestReview extends Command
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

        $orders = $orderRepository->findOrdersToRequestReview();

        if (empty($orders)) {
            $io->warning('No existen pedidos válidos para solicitar opiniones');
            return Command::SUCCESS;
        }

        $this->legacyContextLoader->loadGenericContext();
        $context = \Context::getContext();
        $context->employee->id = 0;
        $context->employee->id_profile = 1;
        $countOrderChanges = 0;

        foreach ($orders as $orderId => $dates) {
            if ($this->computeDaysToShipping($dates['date_add'], $dates['date_shipped']) > 3) {
                continue;
            }

            $order = new \Order($orderId);
            $order->setCurrentStateWithDate(39); // Solicitada valoración
            $countOrderChanges++;
        }

        $io->success($countOrderChanges . ' opiniones solicitadas');
        return Command::SUCCESS;
    }

    private function computeDaysToShipping(string $from, string $to): int
    {
        $start = (\DateTimeImmutable::createFromFormat('d-m-Y', $from)->setTime(0, 0))->getTimestamp();
        $end = (\DateTimeImmutable::createFromFormat('d-m-Y', $to)->setTime(0, 0))->getTimestamp();
        $workingDays = 0;

        while ($start < $end) {
            $start = strtotime('+1 day', $start);

            if ((int)date('N', $start) < 6) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}