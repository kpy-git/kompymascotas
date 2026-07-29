<?php

use PrestaShop\Module\KpyOrderDispatcher\Config\Config;

class KpyOrderDispatcherUpdateOrderStatusModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->ajax = true;

        $idOrder = (int) Tools::getValue('id_order');
        $orderStatus = (int) Tools::getValue('order_status');
        $expires = (int) Tools::getValue('expires');
        $employee = Tools::getIsset('employee') ? (int) Tools::getValue('employee') : 0;
        $timestamp = Tools::getIsset('timestamp') ? (int) Tools::getValue('timestamp') : time();
        $providedToken = Tools::getValue('token');

        if (!$idOrder || !$orderStatus || !$expires || !$providedToken) {
            header('HTTP/1.1 400 Bad Request');
            $this->ajaxRender('Parámetros insuficientes');
            die();
        }

        if (time() > $expires) {
            header('HTTP/1.1 403 Forbidden');
            $this->ajaxRender('El enlace ha expirado');
            die();
        }

        if (!$this->checkToken($providedToken, $idOrder, $orderStatus, $expires)) {
            $this->ajaxRender('Forbidden');
            die();
        }

        $order = new \Order($idOrder);

        if ($orderStatus === $order->current_state) {
            $order->setCurrentStateWithDate(
                36, // Estado vacío
                date('Y-m-d H:i:s', $timestamp)
            );
        }

        $order->setCurrentStateWithDate(
            $orderStatus,
            date('Y-m-d H:i:s', $timestamp),
            $employee,
        );

        header('HTTP/1.1 200 OK');
        $this->ajaxRender(['status' => 'OK']);
    }

    private function checkToken(string $providedToken, int $idOrder, int $orderStatus, int $expires): bool
    {
        $secret = \Configuration::get(Config::KPY_ORDER_DISPATCHER_SECRET);
        $dataToSign = sprintf('id_order=%d&order_status=%d&expires=%d', $idOrder, $orderStatus, $expires);
        $expectedToken = hash_hmac('sha256', $dataToSign, $secret);

        return hash_equals($providedToken, $expectedToken);
    }
}