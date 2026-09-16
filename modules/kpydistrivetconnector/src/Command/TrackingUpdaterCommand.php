<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Command;

use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetShipmentNotFoundException;
use PrestaShop\Module\KpyDistrivetConnector\Repository\OrderRepository;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetClient;
use PrestaShop\PrestaShop\Adapter\LegacyContextLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpydistrivetconnector:tracking:update', description: 'Update the tracking number provided by Distrivet')]
class TrackingUpdaterCommand extends Command
{
    public function __construct(
        private readonly LegacyContextLoader $legacyContextLoader,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('order', InputArgument::OPTIONAL, 'Id del pedido', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $idOrder = $input->getArgument('order');

        // si es false el parámetro order no se ha introducido
        if ($idOrder !== false && !is_numeric($idOrder)) {
            $io->error('Id del pedido incorrecto');
            return Command::FAILURE;
        }

        try {
            $orderRepository = new OrderRepository();

            if ($idOrder !== false) {
                $distrivetOrderId = $orderRepository->getDistrivetOrderId($idOrder);

                if (empty($distrivetOrderId)) {
                    $io->warning('El pedido no existe o no es un pedido gestionado por Distrivet');
                    return Command::SUCCESS;
                }

                $distrivetOrders = [$distrivetOrderId];

            } else {
                $distrivetOrders = $orderRepository->getOrdersPendingFulfillment();
            }

            foreach ($distrivetOrders as $distrivetOrder) {
                $distrivetClient = new DistrivetClient();

                $trackingNumber = $distrivetClient->getTrackingNumber($distrivetOrder);

                $orderRepository->saveTrackingNumber($idOrder, $trackingNumber->getTrackingNumber());
                $orderRepository->saveShipmentId($idOrder, $trackingNumber->getShipmentId());

                $this->legacyContextLoader->loadEmployeeContext();
                $context = \Context::getContext();
                $context->employee->id = 0;
                $context->employee->id_profile = 1;

                $order = new \Order($idOrder);
                $order->setCurrentStateWithDate(35); // Preparado para el envío

                $io->writeln(sprintf("Tracking number updated successfully %s [%d]", $trackingNumber->getTrackingNumber(), $idOrder));
            }

            return Command::SUCCESS;

        } catch (KpyDistrivetShipmentNotFoundException $exception) {
            $io->warning($exception->getMessage());
            return Command::SUCCESS;

        } catch (KpyDistrivetException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}