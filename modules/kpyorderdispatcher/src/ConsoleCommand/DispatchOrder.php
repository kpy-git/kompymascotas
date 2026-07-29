<?php

namespace PrestaShop\Module\KpyOrderDispatcher\ConsoleCommand;

use PrestaShop\Module\KpyOrderDispatcher\Config\Config;
use PrestaShop\Module\KpyOrderDispatcher\Service\OrderDispatcher;
use PrestaShop\Module\KpyOrderDispatcher\Trait\ContextInitializerTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpyorderdispatcher:dispatch')]
class DispatchOrder extends Command
{
    use ContextInitializerTrait;

    protected function configure()
    {
        $this->initializeContext();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orderDispatcher = new OrderDispatcher();

        $seconds = (int)(\Configuration::get(Config::KPY_ORDER_DISPATCHER_SECONDS_TTL) ?: '3600');

        $timelimit = strtotime("+{$seconds} seconds");

        while (time() < $timelimit) {
            $order = \Db::getInstance()->getRow("select `id_order` from " . _DB_PREFIX_ . "kpy_orders_pending_dispatch");

            if (empty($order)) {
                sleep(2);
                continue;
            }

            $orderDispatcher->dispatch($order['id_order']);
        }

        if (_PS_MODE_DEV_) {
            $io->success('Limite de tiempo alcanzado: ' . date('d/m/Y H:i:s', $timelimit));
        }

        return Command::SUCCESS;
    }
}