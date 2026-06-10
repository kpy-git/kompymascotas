<?php

require_once dirname ( __FILE__ ) . '/../../redsyspur.php';

class CancellationPaymentController extends AdminController {

    public function initContent()
	{   
        $order = new Order($_POST['orderId']);

        $orderDetails = Redsys_Order::getOrderDetails($_GET['id_order']);
        $redsyspur = new Redsyspur();
        $gatewayParameters = $redsyspur->getGatewayParameters($orderDetails['method']);
        $amount = floatval($_POST['amount']);
        
        $response = Redsys_Order::cancellation($gatewayParameters, $_GET['id_order'], $amount, null, 0);
        
        die(json_encode($response));
	}

    public function viewAccess($disable = false)
	{
		return true;
	}
}