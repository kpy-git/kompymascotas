<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Command;

use PrestaShop\Module\NeftysFarmaConnector\Builder\NeftysOrderBuilder;
use PrestaShop\Module\NeftysFarmaConnector\Entity\NeftysFarmaOrder;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use PrestaShop\Module\NeftysFarmaConnector\Logger\NeftysFarmaLogger;
use PrestaShop\Module\NeftysFarmaConnector\Service\NeftysFarmaOrderUploader;
use PrestaShop\Module\NeftysFarmaConnector\Service\ProductFinder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'neftysfarmaconnector:upload-order', description: 'Fuerza la subida de un pedido al FTP de Neftys')]
class ForceUpdateOrderCommand extends Command
{
    use ContextInitializerTrait;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->initializeContext();
    }

    protected function configure()
    {
        $this->addArgument('order', InputArgument::REQUIRED, 'Id del pedido');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idOrder = $input->getArgument('order');

        if (!is_numeric($idOrder)) {
            $output->writeln('<error>Id del pedido incorrecto</error>');
            return Command::FAILURE;
        }

        try {
            $order = new \Order((int) $idOrder);

            if (!\Validate::isLoadedObject($order)) {
                throw new NeftysFarmaException('No existe ningún pedido con el ID introducido');
            }

            $productsWithoutPacks = (new ProductFinder())->getProductsOrderWithoutPacks($order);

            if (!NeftysFarmaOrder::isNeftysFarmaOrder($productsWithoutPacks)) {
                $output->writeln('El pedido no es procesable por Neftys Farma');
                NeftysFarmaLogger::logOrder($order, $productsWithoutPacks);
                return Command::SUCCESS;
            }

            $neftysOrder = NeftysOrderBuilder::from($order, $productsWithoutPacks);

            $uploader = new NeftysFarmaOrderUploader();
            $uploader->uploadNeftysOrder($neftysOrder, false);

            return Command::SUCCESS;

        } catch (NeftysFarmaException $ex) {
            $output->writeln('<error>' . $ex->getMessage() . '</error>');

            return Command::FAILURE;
        }
    }
}
