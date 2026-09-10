<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Command;

use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetShipmentNotFoundException;
use PrestaShop\Module\KpyDistrivetConnector\Repository\OrderRepository;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('kpydistrivetconnector:order:tracking-update', description: 'Update the tracking number provided by Distrivet')]
class TrackingUpdaterCommand extends Command
{
    use ContextInitializerTrait;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->initializeContext();
    }

    protected function configure(): void
    {
        $this->addArgument('order', InputArgument::OPTIONAL, 'Id del pedido');
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
            $orderRepository = new OrderRepository();
            $distrivetOrderId = $orderRepository->getDistrivetOrderId($idOrder);

            if (empty($distrivetOrderId)) {
                $io->warning('El pedido no existe o no es un pedido gestionado por Distrivet');
                return Command::SUCCESS;
            }

            $distrivetClient = new DistrivetClient();

            $trackingNumber = $distrivetClient->getTrackingNumber($distrivetOrderId);

            $order = new \Order($idOrder);
            //$order->setCurrentState(35); // Preparado para el envío

            $io->success(sprintf("Tracking number updated successfully %s [%d]", $trackingNumber->getTrackingNumber(), $idOrder));
            $orderRepository->saveTrackingNumber($idOrder, $trackingNumber->getTrackingNumber());
            $orderRepository->saveShipmentId($idOrder, $trackingNumber->getShipmentId());

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