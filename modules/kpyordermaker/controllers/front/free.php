<?php

use PrestaShop\Module\KpyOrderMaker\OrderMaker\KpyOrder;

class KpyOrderMakerFreeModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function postProcess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        ob_end_clean();

        $content = json_decode(file_get_contents('php://input'), true);

        if (!isset($content['token'])) {
            http_response_code(401);
            $this->ajaxRender('Unauthorized');
            exit;
        }

        $mandatoryParams = ['id_customer', 'id_address', 'products', 'carrier_reference', 'id_shop', 'id_lang'];

        if (!empty(array_diff($mandatoryParams, array_keys($content)))) {
            http_response_code(400);
            $this->ajaxRender('No se puede realizar la petición, faltan parámetros obligatorios: ' . implode(',', array_diff($mandatoryParams, array_keys($content))));
            exit;
        }

        $rawToken = ((int)$content['id_customer'] + (int)$content['id_address']) * (int)$content['carrier_reference'];
        if (!password_verify($rawToken, $content['token'])) {
            http_response_code(401);
            $this->ajaxRender('Unauthorized');
            exit;
        }

        try {
            $orderMaker = new KpyOrder((int)$content['id_shop'], (int)$content['id_lang']);

            $orderMaker->setCustomer((int)$content['id_customer']);
            $orderMaker->setAddressDelivery((int)$content['id_address']);
            $orderMaker->setCarrier((int)$content['carrier_reference']);
            $orderMaker->makeCart();

            foreach ($content['products'] as $product) {
                $orderMaker->addProduct(
                    $product['sku'],
                    $product['quantity'],
                    round($product['quantity'] * $product['unit_price'], 2)
                );
            }

            // lo hago aquí por si ocurre una excepción el contenido de la petición no será json y se verá el html
            $id_order = $orderMaker->makeFreeOrder();

            header('Content-type: application/json');
            $this->ajaxRender(json_encode([
                'id_order' => $id_order,
            ]));

        } catch (PrestaShopException $e) {
            $e->displayMessage();
        }
    }
}