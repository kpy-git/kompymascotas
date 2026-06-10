<?php

/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2017 PrestaShop SA
 *  @version  Release: $Revision: 13573 $
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 *
 * @since 1.5.0
 */
require_once dirname ( __FILE__ ) . '/../../ApiRedsysREST/initRedsysApi.php';

class RedsyspurProcessPaymentModuleFrontController extends ModuleFrontController {
	public function initContent() {
		if (session_status () != PHP_SESSION_ACTIVE)
			session_start ();
		
		if (! $this->module->active || ! isset ( $_POST ["idCart"] )) {
			header ( $_SERVER ['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400 );
			die ( $_SERVER ['SERVER_PROTOCOL'] . ' 400 Bad Request: disabled payment module or incomplete request' );
		}
				
		if (! isset ( $_POST ["idOper"] )) {
			$_POST ["idOper"] = null;
		}
		
		if (! isset ( $_POST ["saveRef"] )) {
			$_POST ["saveRef"] = false;
		}

		if (! isset ( $_POST ["useRef"] )) {
			$_POST ["useRef"] = false;
		}

		$insiteParams = [
			'idOper' => $_POST["idOper"],
			'saveRef' => $_POST["saveRef"],
			'useRef' => $_POST["useRef"],
			'idCart' => $_POST["idCart"],
			'merchant_order' => $_POST["merchant_order"],
			'valores3DS' => $_POST["valores3DS"],
		];

		$_SESSION['REDSYS_insite_params'] = $insiteParams;
		
		$this->businessLogic ();
	}
	private function businessLogic() {
		$insiteParams = $_SESSION['REDSYS_insite_params'];
		
		// Para un nuevo pedido, creamos un nuevo idLog
		$idLog = generateIdLog( Configuration::get( 'REDSYS_LOG' ), Configuration::get( 'REDSYS_LOG_STRING'),  $insiteParams ["merchant_order"], Configuration::get( 'REDSYS_LOG_SIZE') );

		$cart = new Cart ( ltrim ( $insiteParams ["idCart"], "0" ) );
		$saveRef = $insiteParams ["saveRef"];
		$useRef = $insiteParams ["useRef"];
		$idOper = $insiteParams ["idOper"];
		$reference = null;

		if($useRef){
			escribirLog("INFO ", $idLog, "Procesando orden usando pago por referencia", null, __METHOD__);
			$reference = $this->module->getCustomerRef ( $cart->id_customer ) [0];
		}

		$Linkobj = new Link();
		$urlOK = Configuration::get( 'REDSYS_URLOK' );
		$urlKO = Configuration::get( 'REDSYS_URLKO' );
		$returnURL_KO = $Linkobj->getPageLink('order') . '?step=1';

		$response = array (
				"redir" => true,
				"url" => $urlKO ? $urlKO : $returnURL_KO 
		);
		
		$params = $this->createParameters ( $insiteParams ["merchant_order"], $insiteParams ["idCart"], $idOper, $reference, $saveRef );
		$ThreeDSParams = $insiteParams["valores3DS"];

		$customer = new Customer ( $cart->id_customer );

		$resultInit = $this->performRequestInit ( $params, $useRef, $idLog );

		if ($resultInit->getResult () == RESTConstants::$RESP_LITERAL_KO) {

			$resultCode = $resultInit->getResult ();
			$apiCode = $resultInit->getApiCode ();
			$authCode = $resultInit->getAuthCode ();
			
			$respuestaSIS = $this->module->checkRespuestaSIS($apiCode, $authCode);

			if(Configuration::get('REDSYS_MANTENER_CARRITO') == 0){
				$this->module->validateOrder($params ["idCart"], _PS_OS_CANCELED_, $params ["amount"]/100, $this->module->paymentMethodNameTarjeta, null);
			}
			$this->module->addPaymentInfo($params ["idCart"], $params ["merchant_order"], $respuestaSIS[1], $idLog);

			escribirLog("INFO ", $idLog, $respuestaSIS[0]);

			$response ["redir"] = true;
			$response ["url"] = $urlKO ? $urlKO : $returnURL_KO ;
		}else{
			$_SESSION ["REDSYS_insite_initial_result"] = $resultInit;

			$response ["redir"] = true;
			$response ["url"] = $this->module->_endpoint_threedsmethod;
		}
		
		die ( json_encode ( $response ) );
	}

	private function createParameters($merchant_order, $idCart, $idOper, $reference, $saveRef) {
		$params = array ();
		
		$cart = new Cart ( ltrim ( $idCart, "0" ) );
		$customer = new Customer ( $cart->id_customer );

		$address = new Address((int)$cart->id_address_invoice);

		/** Generamos los contextos necesarios */
		Context::getContext()->customer = $customer;
		Context::getContext()->country = new Country((int)$address->id_country);
		Context::getContext()->language = new Language((int)$cart->id_lang);
		Context::getContext()->currency = new Currency((int)$cart->id_currency);
		
		// Calculate Amount
		$currency = new Currency ( $cart->id_currency );
		$currency_decimals = is_array ( $currency ) ? ( int ) $currency ['decimals'] : ( int ) $currency->decimals;
		$cart_details = $cart->getSummaryDetails ( null, true );
		$decimals = $currency_decimals * Context::getContext()->getComputingPrecision();
		$shipping = $cart_details ['total_shipping_tax_exc'];
		$subtotal = $cart_details ['total_price_without_tax'] - $cart_details ['total_shipping_tax_exc'];
		$tax = $cart_details ['total_tax'];

		$total_price = (int) round(($cart->getOrderTotal(true, Cart::BOTH) * 100), 0);

		if (! empty (Configuration::get( 'REDSYS_CORRECTOR_IMPORTE' )) ) {
			$total_price *= (float)Configuration::get( 'REDSYS_CORRECTOR_IMPORTE' );
			$total_price = (int)$total_price;
		}

		// Product Description
		$products = $cart->getProducts ();
		$productsDesc = '';
		foreach ( $products as $product )
			$productsDesc .= $product ['quantity'] . ' ' . Tools::truncate ( $product ['name'], 50 ) . ' - ';
		$productsDesc = substr ( $productsDesc, 0, strlen ( $productsDesc ) - 3 );
		
		$cust_name="";
		if($customer != null) {

			$cust_name = createMerchantTitular( preg_replace ( "/[^ a-zA-Z0-9-]+/", "", $customer->firstname ), 
												preg_replace ( "/[^ a-zA-Z0-9-]+/", "", $customer->lastname ), 
												$customer->email
											);
		
		}
		
		$params ["merchant_order"] = $merchant_order;
		$params ["idCart"] = str_pad ( $idCart, 12, "0", STR_PAD_LEFT );
		$params ["reference"] = $reference;
		$params ["idOper"] = $idOper;
		$params ["amount"] = $total_price;
		$params ["currency"] = $currency->iso_code_num;
		$params ["idCurrency"] = $currency->id;
		$params ["decimals"] = $decimals;
		$params ["products"] = preg_replace ( "/[^ a-zA-Z0-9-]+/", "", $productsDesc );
		$params ["customer"] = $cust_name;
		$params ["requestRef"] = $saveRef;
		
		return $params;
	}

	private function performRequestInit($params, $useRef = false, $idLog) {

		escribirLog("DEBUG", $idLog, "Iniciando petición...", null, __METHOD__);

		$request = new RESTInitialRequestMessage();

		$request->setAmount ( ( int ) $params ["amount"] );
		$request->setCurrency ( $params ["currency"] );
		$request->setMerchant ( Configuration::get ( 'REDSYS_FUC_TARJETA_INSITE' ) );
		$request->setTerminal ( Configuration::get ( 'REDSYS_TERMINAL_TARJETA_INSITE' ) );
		$request->setOrder ( $params ["merchant_order"] );
		if($useRef){
			$request->useReference ( $params ["reference"] );
		}else{
			$request->setOperID ( $params ["idOper"] );
		}		
		$request->setTransactionType ( Configuration::get ( 'REDSYS_TIPOPAGO_TARJETA_INSITE' ) );
		$request->demandCardData();
		
		$service = new RESTInitialRequestService ( Configuration::get ( 'REDSYS_CLAVE_TARJETA_INSITE' ), Configuration::get ( 'REDSYS_URLTPV_INSITE' ) );
		$result = $service->sendOperation ( $request, $idLog );
		
		return $result;
	}
}
