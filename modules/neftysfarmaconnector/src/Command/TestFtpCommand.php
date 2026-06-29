<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Command;

use PrestaShop\Module\NeftysFarmaConnector\Service\FTPManager;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'neftysfarmaconnector:test-ftp', description: 'Test FTP Neftys')]
class TestFtpCommand extends Command
{
    use ContextInitializerTrait;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->initializeContext();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            $output->writeln(FTPManager::getNeftysFarmaConnection()->list());

            return Command::SUCCESS;

        } catch (NeftysFarmaException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
