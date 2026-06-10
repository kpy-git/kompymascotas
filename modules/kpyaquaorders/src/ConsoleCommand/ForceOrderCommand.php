<?php

namespace PrestaShop\Module\KpyAquaOrders\ConsoleCommand;

use PrestaShop\Module\KpyAquaOrders\CommandDispatcher\CommandDispatcher;
use PrestaShop\Module\KpyAquaOrders\Controller\AquaOrderController;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;
use PrestaShop\Module\KpyAquaOrders\Service\Mailer;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use PrestaShop\Module\NeftysFarmaConnector\Command\ContextInitializerTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kpyaquaorders:force-order', description: 'Fuerza la actualización de un pedido dado')]
class ForceOrderCommand extends Command
{
    use ContextInitializerTrait;

    protected function configure(): void
    {
        $this
            ->addArgument('Pedido', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $order = new \Order((int)$input->getArgument('Pedido'));

        if (!AquaOrderStateWarehouse::getInstance()->isSupported($order->current_state)) {
            $output->writeln('El estado del pedido no está soportado por ningún comando');
            return Command::SUCCESS;
        }

        try {
            $aqua = DbMssql::getInstance();

            if ($aqua->withError()) {
                $output->write($aqua->getSqlError());
                return Command::FAILURE;
            }

            $dispatcher = new CommandDispatcher(new AquaOrderController($aqua));

            $dispatcher->dispatchAction($order, $this->initializeContext());

            return Command::SUCCESS;

        } catch (KpyAquaSqlException $exception) {
            $output->writeln($exception->getMessage());

            $error = [
                'date' => date('d-m-Y H:i:s'),
                'method' => $exception->getMethod(),
                'error' => $exception->getMessage(),
                'sql' => $exception->getLastSql(),
                'sql_error' => $exception->getSqlError(),
            ];

            $filename = 'sql_error_' . time() . '.log';
            $filePath = _PS_MODULE_DIR_ . 'kpyaquaorders/log/' . $filename;

            file_put_contents($filePath, json_encode($error, JSON_PRETTY_PRINT));
            $mailer = new Mailer();
            $mailer->sendMailError(
                (int)$input->getArgument('Pedido'),
                "Ha ocurrido un error en la base de datos al importar el pedido {$input->getArgument('Pedido')}\n\nLog: " . $filename,
                $exception->getMessage(),
                ['programacion@piensoymascotas.com'],
                [
                    'content' => file_get_contents($filePath),
                    'name' => $filename,
                    'mime' => 'text/plain',
                ]
            );
            return Command::FAILURE;

        } catch (KpyAquaOrderException $ex) {
            $output->writeln($ex->getMessage());
            return Command::FAILURE;
        }
    }
}