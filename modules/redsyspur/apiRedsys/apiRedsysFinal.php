<?php

/**
 * NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
 * 
 * El uso de este software está sujeto a las Condiciones de uso de software que
 * se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
 * obtener una copia en la siguiente url:
 * http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
 * 
 * Redsys es titular de todos los derechos de propiedad intelectual e industrial
 * del software.
 * 
 * Quedan expresamente prohibidas la reproducción, la distribución y la
 * comunicación pública, incluida su modalidad de puesta a disposición con fines
 * distintos a los descritos en las Condiciones de uso.
 * 
 * Redsys se reserva la posibilidad de ejercer las acciones legales que le
 * correspondan para hacer valer sus derechos frente a cualquier infracción de
 * los derechos de propiedad intelectual y/o industrial.
 * 
 * Redsys Servicios de Procesamiento, S.L., CIF B85955367
 */

class RedsyspurAPI
{

	//////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////
	////////////	 				  UTILIDADES DE PARAMETROS						  ////////////
	//////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////

	/** TRATAMIENTO DE LOS PARAMETROS */
	var $vars_pay = array();

	public function existParameter($key)
	{
		return array_key_exists($key, $this->vars_pay);
	}

	public function setParameter($key, $value)
	{
		$this->vars_pay[$key] = $value;
	}

	public function getParameter($key)
	{
		if ($this->existParameter($key))
			return $this->vars_pay[$key];
		else
			return null;
	}

	/** TRATAMIENTO DE CADENAS EN BASE64 CON URL ENCODE */
	/** IMPLEMENTACIÓN POR BASE64.GURU */
	public static function base64url_encode($data)
	{
		$b64 = base64_encode($data);

		if ($b64 === false)
			return false;

		$url = strtr($b64, '+/', '-_');
		return rtrim($url, '=');
	}

	public static function base64url_decode($data, $strict = false)
	{
		$b64 = strtr($data, '-_', '+/');

		return base64_decode($b64, $strict);
	}


	//////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////
	////////////	   FIRMA DE LOS PARAMETROS DE LA PETICION O NOTIFICACION		  ////////////
	//////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////

	/** FUNCIONES AUXILIARES PARA LA FIRMA DE PARAMETROS */
	private static function fixKey($key)
	{
		if (strlen($key) < 16)
			return str_pad("$key", 16, "0");

		if (strlen($key) > 16)
			return substr($key, 0, 16);

		return $key;
	}

	private static function mac512($ent, $key)
	{
		return hash_hmac('sha512', $ent, $key, true);
	}

	private function encrypt_AES($message, $key)
	{
		$data = $message;
		$firma = base64_encode(openssl_encrypt($data, "aes-128-cbc", $this->fixKey($key), OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"));

		return $firma;
	}

	public static function getVersionClave()
	{
		return "HMAC_SHA512_V2";
	}

	/** FUNCIONES DE FIRMA DE PARAMETROS DE LA PETICION */
	private function getOrder()
	{
		$numPedido = "";
		if (empty($this->vars_pay['DS_MERCHANT_ORDER']))
			$numPedido = $this->vars_pay['Ds_Merchant_Order'];
		else
			$numPedido = $this->vars_pay['DS_MERCHANT_ORDER'];

		return $numPedido;
	}

	protected function arrayToJson()
	{
		$json = json_encode($this->vars_pay);
		return $json;
	}

	public function createMerchantParameters()
	{
		$json = $this->arrayToJson();
		return $this->base64url_encode($json);
	}

	public function createMerchantSignature($key)
	{
		$ent = $this->createMerchantParameters();

		$key = $this->encrypt_AES($this->getOrder(), $key);
		$res = $this->mac512($ent, $key);

		return $this->base64url_encode($res);
	}

	/** FUNCIONES DE FIRMA DE PARAMETROS DE LA NOTIFICACION */
	private function getOrderNotif()
	{
		$numPedido = "";
		if (empty($this->vars_pay['Ds_Order']))
			$numPedido = $this->vars_pay['DS_ORDER'];
		else
			$numPedido = $this->vars_pay['Ds_Order'];

		return $numPedido;
	}

	public function stringToArray($datosDecod)
	{
		$this->vars_pay = json_decode($datosDecod, true);
	}

	public function decodeMerchantParameters($datos)
	{
		$decodec = $this->base64url_decode($datos);
		$this->stringToArray($decodec);

		return $decodec;
	}

	public function createMerchantSignatureNotif($key, $datos)
	{
		$key = $this->encrypt_AES($this->getOrderNotif(), $key);
		$res = $this->mac512($datos, $key);

		return $this->base64url_encode($res);
	}
}
