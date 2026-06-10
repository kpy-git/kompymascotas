<?php

class KpyCancellationRequestHandlerModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $this->ajax = true;
    }

    public function postProcess()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Method not allowed');
        }

        if (!$this->context->customer->isLogged()) {
            http_response_code(403);
            die('Forbidden');
        }

        if (!Tools::getIsset('customer') || !Tools::getIsset('order') || !Tools::getIsset('status')) {
            http_response_code(400);
            die('Bad request');
        }

        $newOrderStatus = Validate::isUnsignedInt(Tools::getValue('status')) ? (int)Tools::getValue('status') : 0;

        if ($newOrderStatus !== (int)Configuration::get(KpyCancellationRequest::CANCELLATION_REQUEST)
            && $newOrderStatus !== (int)Configuration::get(KpyCancellationRequest::CANCEL_CANCELLATION_REQUEST)) {
            http_response_code(400);
            die('Bad request');
        }

        if ($this->context->customer->id !== (int)Tools::getValue('customer')) {
            http_response_code(401);
            die('Unauthorized');
        }

        $order = new Order((int)Tools::getValue('order'));

        if (!Validate::isLoadedObject($order)) {
            http_response_code(422);
            die('Unprocessable entity');
        }

        $order->setCurrentStateWithDate($newOrderStatus);

        ob_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(json_encode([
            'status' => 200,
            'message' => $this->getMessageByOrderStatus($newOrderStatus),
        ]));
    }

    private function getMessageByOrderStatus(int $orderStatus): string
    {
        if ($orderStatus === (int)Configuration::get(KpyCancellationRequest::CANCELLATION_REQUEST)) {
            return $this->trans('Cancellation request has been processed successfully. The order has been on hold and will not shipped.', [], 'Modules.Kpycancellationrequest.Shop');
        }

        return $this->trans('Cancellation request han been canceled successfully. The order will be shipped normally.', [], 'Modules.Kpycancellationrequest.Shop');
    }
}