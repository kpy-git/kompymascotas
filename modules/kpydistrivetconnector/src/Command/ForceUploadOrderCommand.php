<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Command;

use PrestaShop\Module\KpyDistrivetConnector\Config\Config;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetException;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetProductNotFoundException;
use PrestaShop\Module\KpyDistrivetConnector\Logger\DistrivetLogger;
use PrestaShop\Module\KpyDistrivetConnector\Repository\OrderRepository;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetClient;
use PrestaShop\Module\KpyDistrivetConnector\Service\DistrivetOrderBuilder;
use PrestaShop\Module\KpyDistrivetConnector\Service\ProductFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'kpydistrivetconnector:upload-order', description: 'Fuerza la subida de un pedido a la API de Distrivet')]
class ForceUploadOrderCommand extends Command
{
    use ContextInitializerTrait;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->initializeContext();
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
            $output->writeln('<error>Id del pedido incorrecto</error>');
            return Command::FAILURE;
        }

        try {
            $order = new \Order($idOrder);

            if (!\Validate::isLoadedObject($order)) {
                throw new KpyDistrivetException('Pedido no encontrado');
            }

            $productFinder = new ProductFinder();

            $productsWithoutPacks = $productFinder->getProductsOrderWithoutPacks($order);

            $distrivetOrder = DistrivetOrderBuilder::from($order, $productsWithoutPacks);

            $distrivetClient = new DistrivetClient();
            $distrivetOrderId = $distrivetClient->createOrder($distrivetOrder);

            if (Config::DEBUG_MODE) {
                DistrivetLogger::logOrder($distrivetOrder, \Module::getInstanceByName('kpydistrivetconnector')->getLocalPath());
            }

            $order->setCurrentState(\Configuration::get(Config::DISTRIVET_OS));

            $orderRepository = new OrderRepository();
            $orderRepository->save($distrivetOrder, $distrivetOrderId);

            $io->success('Pedido actualizado correctamente');
            return Command::SUCCESS;

        } catch (KpyDistrivetProductNotFoundException $exception) {
            $io->error("Pedido no gestionable por Distrivet. " . $exception->getMessage());
            return Command::FAILURE;


        } catch (KpyDistrivetException $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }
    }
}