<?php

namespace PrestaShop\Module\KpyAquaOrders\ConsoleCommand;

use PrestaShop\Module\KpyAquaOrders\Config\AquaOrderState;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyAquaOrders\DTO\OutsyncOrder;
use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaOrderException;
use PrestaShop\Module\KpyAquaOrders\Warehouse\AquaOrderStateWarehouse;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'kpyaquaorders:process-orders', description: 'Importa los pedidos pendientes al no haber conexión con AQUA')]
class ProcessOutSyncOrdersCommand extends Command
{
    private AquaOrderStateWarehouse $aquaOrderStateWarehouse;

    private string $outsyncOrdersFilename;

    private array $errors;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->errors = [];
        $this->aquaOrderStateWarehouse = new AquaOrderStateWarehouse();
        $this->outsyncOrdersFilename = \Module::getInstanceByName('kpyaquaorders')->getOutsyncOrdersFilename();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!is_readable($this->outsyncOrdersFilename)) {
            $output->writeln("No hay ningún pedido pendiente de sincronizar");
            return Command::SUCCESS;
        }

        $aqua = DbMssql::getInstance();

        if ($aqua->withError()) {
            $output->write($aqua->getSqlError());
            return Command::FAILURE;
        }

        try {
            // si vuelve a ocurrir algún error al resincronizar, volvería a escribir en el fichero...
            // primero se leen todos y luego se borra el fichero
            $outsyncOrders = $this->loadOutsyncOrders();

            /** @var OutsyncOrder $outsyncOrder */
            foreach ($outsyncOrders as $outsyncOrder) {
                $this->processOutsyncOrder($outsyncOrder);
                $output->writeln(sprintf("Pedido %d procesado", $outsyncOrder->getId()));
            }

            $output->writeln(count($outsyncOrders) . " pedidos sincronizados");

            if (!empty($this->errors)) {
                $output->write(implode("\n", $this->errors));
            }

            return Command::SUCCESS;

        } catch (\RuntimeException $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @throws KpyAquaOrderException
     * @throws \RuntimeException
     */
    private function loadOutsyncOrders(): array
    {
        $outsyncFile = fopen($this->outsyncOrdersFilename, 'rb');

        if (!$outsyncFile) {
            throw new \RuntimeException('ERROR: no se puede abrir el archivo para lectura');
        }

        $outsyncOrders = [];

        while (($data = fgets($outsyncFile)) !== false) {
            try {
                $outsyncOrders[] = OutsyncOrder::deserialize($data);

            } catch (KpyAquaOrderException $e) {
                $this->errors[]  = $e->getMessage() . "\t" . $data;
            }
        }

        fclose($outsyncFile);
        unlink($this->outsyncOrdersFilename);

        return $outsyncOrders;
    }

    private function processOutsyncOrder(OutsyncOrder $outsyncOrder): void
    {
        $order = new \Order($outsyncOrder->getId());

        $order->setCurrentStateWithDate(
            $this->aquaOrderStateWarehouse->getOrderStateId(AquaOrderState::PENDING_SYNC),
            date('Y-m-d H:i:s', $outsyncOrder->getTimestampOutsync())
        );

        $order->setCurrentStateWithDate(
            $outsyncOrder->getOrderStatus(),
            date('Y-m-d H:i:s'),
        );
    }
}