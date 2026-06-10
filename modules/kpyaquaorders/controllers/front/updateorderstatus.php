<?php

class KpyAquaOrdersUpdateOrderStatusModuleFrontController extends ModuleFrontController
{
    private string $secret;

    public function init()
    {
        parent::init();

        $this->secret = Configuration::get(KpyAquaOrders::SECRET_KEY);
    }

    public function initContent(): void
    {
        parent::initContent();

        $this->ajax = true;

        if (!Tools::getIsset('order') || !Tools::getIsset('status') || !Tools::getIsset('token')) {
            header('HTTP/1.1 400 Bad Request');
            die('Malformed request');
        }

        $orderId = (int)Tools::getValue('order');
        $status = (int)Tools::getValue('status');

        if (!$this->verifyToken(Tools::getValue('token'), $orderId, $status)) {
            header('HTTP/1.1 403 Forbidden');
            die('Unauthorized');
        }

        $employeeId = (int)Tools::getValue('employee', 0);
        $this->context->employee->id_profile = 1;

        if ($employeeId) {
            // el estado AQUA - Renombrar y volver a importar tiene una condición para ejecutarse en función del empleado
            $this->context->employee = new Employee($employeeId);
        }

        $order = new Order($orderId);

        $order->setCurrentStateWithDate(
            $status,
            date('Y-m-d H:i:s', Tools::getValue('time') ?: time()),
            $employeeId
        );

        ob_end_clean();

        header('Content-Type:application/json; charset=utf-8');
        $this->ajaxRender(json_encode([
            'status' => 200,
            'timestamp' => date(DATE_ATOM),
        ]));
    }

    private function verifyToken(string $token, int $order, int $status): bool
    {
        $expectedToken = hash_hmac('sha256', $order . '|' . $status, $this->secret);

        return hash_equals($expectedToken, $token);
    }
}