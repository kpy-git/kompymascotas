<?php

require_once dirname ( __FILE__ ) . '/../../redsyspur.php';



class RefundPaymentController extends AdminController {

	public function initContent()
	{   
        $order = new Order($_POST['orderId']);

        $orderDetails = Redsys_Order::getOrderDetails($_POST['orderId']);
        $redsyspur = new Redsyspur();
        $gatewayParameters = $redsyspur->getGatewayParameters($orderDetails['method']);
        $amount = floatval($_POST['amount']);

        $response = Redsys_Order::refund($gatewayParameters, $_POST['orderId'], $amount);

        die(json_encode($response));
	}

    public function viewAccess($disable = false)
	{
		return true;
	}
}