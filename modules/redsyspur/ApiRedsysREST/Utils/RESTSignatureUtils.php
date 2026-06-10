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

if (!class_exists('RESTSignatureUtils')) {
	class RESTSignatureUtils
	{

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

		private static function encrypt_AES($message, $key)
		{
			$data = $message;
			$firma = base64_encode(openssl_encrypt($data, "aes-128-cbc", RESTSignatureUtils::fixKey($key), OPENSSL_RAW_DATA, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"));

			return $firma;
		}

		/** FUNCIONES DE FIRMA DE PARAMETROS DE LA PETICION */
		private static function getOrder($datos)
		{
			$vars = json_decode(RedsyspurAPI::base64url_decode($datos), true);
			$numPedido = "";

			if (empty($vars['DS_MERCHANT_ORDER']))
				$numPedido = $vars['Ds_Merchant_Order'];
			else
				$numPedido = $vars['DS_MERCHANT_ORDER'];

			return $numPedido;
		}

		public static function createMerchantSignature($key, $ent)
		{
			$key = RESTSignatureUtils::encrypt_AES(RESTSignatureUtils::getOrder($ent), $key);
			$res = RESTSignatureUtils::mac512($ent, $key);

			return RedsyspurAPI::base64url_encode($res);
		}

		/** FUNCIONES DE FIRMA DE PARAMETROS DE LA RESPUESTA */
		private static function getOrderResponse($datos)
		{
			$vars = json_decode(RedsyspurAPI::base64url_decode($datos), true);
			$numPedido = "";

			if (empty($vars['Ds_Order']))
				$numPedido = $vars['DS_ORDER'];
			else
				$numPedido = $vars['Ds_Order'];

			return $numPedido;
		}

		public static function createMerchantSignatureResponse($key, $ent)
		{
			$key = RESTSignatureUtils::encrypt_AES(RESTSignatureUtils::getOrderResponse($ent), $key);
			$res = RESTSignatureUtils::mac512($ent, $key);

			return RedsyspurAPI::base64url_encode($res);
		}

		/** FUNCIONES DE FIRMA DE PARAMETROS DE LA NOTIFICACION */
		private static function getOrderNotif($datos)
		{
			$vars = json_decode(RedsyspurAPI::base64url_decode($datos), true);
			$numPedido = "";

			if (empty($vars['Ds_Order']))
				$numPedido = $vars['DS_ORDER'];
			else
				$numPedido = $vars['Ds_Order'];

			return $numPedido;
		}

		public static function createMerchantSignatureNotif($key, $datos)
		{
			$key = RESTSignatureUtils::encrypt_AES(RESTSignatureUtils::getOrderNotif($datos), $key);
			$res = RESTSignatureUtils::mac512($datos, $key);

			return RedsyspurAPI::base64url_encode($res);
		}
	}
}
