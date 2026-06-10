<?php

use PrestaShop\Module\NeftysFarmaConnector\Service\FTPManager;
use PrestaShop\Module\NeftysFarmaConnector\Logger\NeftysFarmaLogger;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\Service\NeftysFarmaStockSynchronizer;

class NeftysFarmaConnectorStockSyncModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->ajax = true;

        if (\Configuration::get(NeftysFarmaConfig::TOKEN) !== Tools::getValue('token')) {
            header('HTTP/1.1 403 Forbidden');
            $this->ajaxRender('Forbidden');
            die();
        }

        try {

            $stockFile = $this->module->getLocalPath() . 'stock/stock.csv';

            FTPManager::getNeftysFarmaConnection()->downloadStockFileAs($stockFile);

            $stockSynchronizer = new NeftysFarmaStockSynchronizer();
            $stockSynchronizer->stockSync($stockFile);

            $output = $stockSynchronizer->getCountEansSynchronized() . ' productos sincronizados<br />' . PHP_EOL;

            if ($stockSynchronizer->existsMissingEans()) {
                $output .= 'Missings EANs: ' . count($stockSynchronizer->getMissingEans()) . PHP_EOL;

                /*foreach ($stockSynchronizer->getMissingEans() as $ean) {
                    $output .= $ean . PHP_EOL;
                }*/

            }
            
            $this->ajaxRender($output);

        } catch (NeftysFarmaException $ex) {
            NeftysFarmaLogger::log($ex->getMessage());
        }
    }
}