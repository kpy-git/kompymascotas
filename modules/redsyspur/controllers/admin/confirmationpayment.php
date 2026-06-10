<?php

require_once dirname ( __FILE__ ) . '/../../redsyspur.php';

class ConfirmationPaymentController extends AdminController {

    public function initContent()
	{   
        $order = new Order($_POST['orderId']);

        $shippingConfirmed = filter_var($_POST['shippingConfirmed'], FILTER_VALIDATE_BOOLEAN);
        $shippingAlreadyRefunded = filter_var($_POST['shippingPaid'], FILTER_VALIDATE_BOOLEAN);
        $transactionType = $_POST['transactionType'];

        if ($_POST['shippingCost'] == '0')
            $shippingCost = null;

        $orderDetails = Redsys_Order::getOrderDetails($_GET['id_order']);
        $redsyspur = new Redsyspur();
        $gatewayParameters = $redsyspur->getGatewayParameters($orderDetails['method']);
        $amount = floatval($_POST['amount']);

        $refundStatus = Redsys_Order::checkShipmentPaid($shippingConfirmed, $shippingAlreadyRefunded, true);

        $response = Redsys_Order::confirmation($gatewayParameters, $_GET['id_order'], $amount, null, $refundStatus, $transactionType);
        die(json_encode($response));
	}

    public function viewAccess($disable = false)
	{
		return true;
	}
}