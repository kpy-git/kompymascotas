<?php

namespace PrestaShop\Module\KpyAquaOrders\Service;

use Db;
use Mail;
use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;

class Checker
{
    private array $psOrderAmounts;
    private array $aquaOrderAmounts;

    public function __construct(private readonly DbMssql $aqua)
    {
    }

    public function checkOrder(int $orderId, int $customerId): void
    {
        $this->loadOrderAmounts($orderId);

        $errors = $this->getOrderErrors();

        if (!empty($errors)) {
            $this->sendMailWithErrors($customerId, $orderId, $errors);
        }
    }

    public function loadOrderAmounts(int $orderId): void
    {
        $sql_ps = "SELECT (o.total_paid_tax_incl - o.total_paid_tax_excl) as total_iva, o.total_paid_tax_excl, o.total_paid_tax_incl
            FROM " . _DB_PREFIX_ . "orders o
            WHERE o.id_order = " . $orderId;
        $this->psOrderAmounts = Db::getInstance()->getRow($sql_ps);

        $sql_aqua = "SELECT TOTIVA,TOTAL AS TOTAL_SIN_IVA,TOTIVA+TOTAL AS TOTAL_CON_IVA
            FROM DATOP03 WITH(NOLOCK)
            WHERE NUMERO_DOC = '{$orderId}' AND TIPOOPER = 'C'";
        $this->aquaOrderAmounts = $this->aqua->getRow($sql_aqua);
    }

    public function getOrderErrors(): array
    {
        $errors = [];

        if ($this->psOrderAmounts['total_iva'] !== $this->aquaOrderAmounts['TOTIVA']) {
            $diff = abs($this->psOrderAmounts['total_iva'] - $this->aquaOrderAmounts['TOTIVA']);
            if ($diff > 0.015) {
                $errors[] = 'Descuadra IVA';
            }
        }

        if ($this->psOrderAmounts['total_paid_tax_excl'] !== $this->aquaOrderAmounts['TOTAL_SIN_IVA']) {
            $diff = abs($this->psOrderAmounts['total_paid_tax_excl'] - $this->aquaOrderAmounts['TOTAL_SIN_IVA']);
            if ($diff > 0.015) {
                $errors[] = 'Descuadra total base imponible';
            }
        }

        if ($this->psOrderAmounts['total_paid_tax_incl'] !== $this->aquaOrderAmounts['TOTAL_CON_IVA']) {
            $diff = abs($this->psOrderAmounts['total_paid_tax_incl'] - $this->aquaOrderAmounts['TOTAL_CON_IVA']);
            if ($diff > 0.015) {
                $errors[] = 'Descuadra total';
            }
        }

        return $errors;
    }

    public function sendMailWithErrors(int $customerId, int $orderId, array $errors): void
    {
        if (empty($errors)) {
            return;
        }

        $receivers = ["administracion@piensoymascotas.com", "programacion@piensoymascotas.com",];

        if (in_array($customerId, [4, 9316, 83052, 30340, 32502])) {
            // los clientes de prueba no mandará emails a administración
            $receivers = ["programacion@piensoymascotas.com"];
        }

        $msg = 'El pedido <a href="https://atc.piensoymascotas.com/order/' . $orderId . '"> ' . $orderId . '</a> ha sufrido los siguientes descuadres: <br/>';
        foreach ($errors as $e) {
            $msg .= "<li>$e</li>";
        }
        $msg .= '<hr /> Datos: ';
        $msg .= "<br />Total Iva PS: " . $this->psOrderAmounts['total_iva'] . " | Total Iva Aqua: " . $this->aquaOrderAmounts['TOTIVA'];
        $msg .= "<br />Total Sin Iva PS: " . $this->psOrderAmounts['total_paid_tax_excl'] . " | Total Sin Iva Aqua: " . $this->aquaOrderAmounts['TOTAL_SIN_IVA'];
        $msg .= "<br />Total Con Iva PS: " . $this->psOrderAmounts['total_paid_tax_incl'] . " | Total Con Iva Aqua: " . $this->aquaOrderAmounts['TOTAL_CON_IVA'];

        Mail::Send(
            3,
            'generic',
            'Descuadre pedido AQUA: ' . $orderId,
            array(
                '{email}' => 'no-reply@piensoymascotas.com',
                '{message}' => $msg,
                '{order_name}' => $orderId,
                '{attached_file}' => '',
            ),
            $receivers
        );
    }
}