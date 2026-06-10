<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once $_SERVER['DOCUMENT_ROOT'] . '/config/config.inc.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/init.php';

include_once __DIR__ . '/classes/orderAqua.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/dbMssql.php';


// saca los pedidos que llevan mas de 10 días con el estado 'Solicitud de cancelación'
$sql = "SELECT o.id_order, max(oh.date_add) fecha_solicitud, o.payment, IF(o.invoice_date != 0, 'si', 'no') as pagado
		from ps_orders o
		inner join ps_order_history oh on oh.id_order = o.id_order and oh.id_order_state = o.current_state
		where o.current_state = 107 and not EXISTS (select * from ps_customer c where c.id_customer = o.id_customer and c.email like '%piensoymascotas%')
		group by o.id_order
		HAVING DATEDIFF(NOW(), max(oh.date_add)) >= 10";

$orders = Db::getInstance()->executeS($sql);

if (empty($orders)) {
	exit('No hay ningún pedido para eliminar');
}

echo count($orders) . " pedidos para eliminar<br /><hr /><br />";
$skusLiberados = [];

// elimina el pedido de AQUA (si existe y se puede modificar) y sus relacionados (si tiene)
foreach ($orders as $order_row) {
	// los pedidos desbloqueados ATC los ha desbloqueado por que el cliente lo quiere recibir
	if (!OrderAqua::modificable($order_row['id_order']) && !OrderAqua::estaBloqueado($order_row['id_order'])) {
		echo "El pedido {$order_row['id_order']} no es modificable<br />";
		continue;
	}

	$order = new Order((int) $order_row['id_order']);

	// si el pedido no está pagado o es CRM se cancela automáticamente
	if ($order_row['pagado'] === 'no' || strpos($order_row['payment'], 'reembolso') > 0) {
		echo "Pedido {$order_row['id_order']} cancelado<br />";
		$order->setCurrentStateWithDate(6, date('Y-m-d H:i:s'));
		continue;
	}

	$skusLiberadosPedido = getSkusArrayByOrder($order_row['id_order']);
	foreach ($skusLiberadosPedido as $sku => $unidades) {
		if (array_key_exists($sku, $skusLiberados)) {
			$skusLiberados[$sku] += $unidades;
		} else {
			$skusLiberados[$sku] = $unidades;
		}
	}

	OrderAqua::eliminar($order_row['id_order']);

	// cambia el estado en prestashop a 'Pedido eliminado de AQUA para liberar mercancía'
	$order->setCurrentStateWithDate(136, date('Y-m-d H:i:s'));
}

// volver a reservar la mercancía que se haya quedado disponible para los pedidos pendientes
foreach ($skusLiberados as $sku => $unidades) {
	$remainingUnits = $unidades;
	$pendingsOrders = getPendingsOrdersConSku($sku);

	while ($remainingUnits > 0 && count($pendingsOrders) > 0) {
		$orderForReserve = array_shift($pendingsOrders);
		echo "Reservando {$orderForReserve['unidades']} unidades para el pedido {$orderForReserve['pedido']}<br />";

		OrderAqua::reservaMercancia(
			$orderForReserve['operacion'],
			$orderForReserve['posicion'],
			$orderForReserve['sku'],
			$orderForReserve['unidades']
		);

		$remainingUnits -= $orderForReserve['unidades'];
	}
}

function getSkusArrayByOrder($pedido)
{
	$productos = DbPymMssql::getInstance()->consulta(
		"SELECT RTRIM(MO.CODART) AS SKU, SUM(MO.UNIDADES) AS UNIDADES, ISNULL(SUM(MPA.UNITS),0) AS RESERVADAS
			FROM DATOP01 OP WITH(NOLOCK)
			INNER JOIN DATMO01 MO WITH(NOLOCK) ON OP.NUMERO = MO.NUMERO
			LEFT JOIN DATCHAOTICMOVPALET01 MPA WITH(NOLOCK) ON MPA.NUMBER = MO.NUMERO
				AND MPA.[POSITION] = MO.POSICION
			WHERE OP.TIPOOPER = 'C' AND OP.NUMERO_DOC = '{$pedido}'
				AND EXISTS (SELECT * FROM DATIN01 P WITH(NOLOCK) WHERE P.CODIGO = MO.CODART AND P.CONTROLADO=1)
			GROUP BY MO.CODART"
	);

	return array_reduce($productos, static function($carry, $row) {
		if ((int)$row['RESERVADAS'] > 0) {
			$carry[$row['SKU']] = (int)$row['RESERVADAS'];
		}
		return $carry;
	}, []);
}

function getPendingsOrdersConSku($sku)
{
	$results = DbPymMssql::getInstance()->consulta(
		"SELECT RTRIM(NUMERO_DOC) AS PEDIDO, MO.NUMERO, MO.POSICION, MO.UNIDADES - MO.INCORPORAD AS REQUERIDAS, ISNULL(MPA.UNITS, 0) AS RESERVADAS
		FROM DATMO01 MO WITH(NOLOCK)
		INNER JOIN DATOP01 OP WITH(NOLOCK) ON OP.NUMERO = MO.NUMERO
			AND OP.CENTRO = 'RABANALES' AND OP.TIPOOPER ='C' AND OP.PENDIENTES > 0
		LEFT JOIN DATCHAOTICMOVPALET01 MPA WITH(NOLOCK) ON MPA.[NUMBER] = MO.NUMERO AND MPA.[POSITION] = MO.POSICION
		WHERE CODART = '{$sku}' AND (MO.UNIDADES - MO.INCORPORAD) > ISNULL(MPA.UNITS, 0)
		ORDER BY OP.FECHA"
	);

	if (empty($results)) {
		return [];
	}

	return array_map(static function($row) use($sku) {
		return [
			'pedido' => $row['PEDIDO'],
			'operacion' => (int)$row['NUMERO'],
			'posicion' => (int)$row['POSICION'],
			'sku' => $sku,
			'unidades' => (int)$row['REQUERIDAS'] - (int)$row['RESERVADAS'],
		];
	}, $results);
}


