<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Service;

use PrestaShop\Module\NeftysFarmaConnector\Entity\NeftysFarmaOrder;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;

class FileWriter
{
    public const string SEPARATOR = '|';

    private array $header = [
        'NUMEROPEDIDO',
        'FECHAPEDIDO',
        'NOMBRE',
        'DIRECCION',
        'CODIGOPOSTAL',
        'POBLACION',
        'PROVINCIA',
        'TELEFONO',
        'EAN',
        'UNIDADES',
        'EMAIL',
        'CRM',
        'TOTAL',
        //'observaciones',
    ];

    /**
     * @throws NeftysFarmaException
     */
    public function writeOrder(NeftysFarmaOrder $order, string $filename): void
    {
        $file = fopen($filename, 'wb');

        if (!$file) {
            throw new NeftysFarmaException("ERROR: no se puede crear el fichero del pedido " . $order->getIdOrder() . PHP_EOL);
        }

        //fputcsv($file, $this->header, self::SEPARATOR, "\0");
        fwrite($file, implode(self::SEPARATOR, $this->header));

        foreach ($order->getProductsQuantityByEan() as $ean => $quantity) {
            $fullFields = array_merge(
                $order->toArray(),
                $order->getReceiver()->toArray(), [
                'EAN' => $ean,
                'UNIDADES' => (int)$quantity,
            ]);

            $orderedFields = [];
            foreach ($this->header as $field) {
                if (isset($fullFields[$field])) {
                    $orderedFields[] = mb_convert_encoding(
                        trim(str_replace(self::SEPARATOR, '', (string)$fullFields[$field])),
                        'Windows-1252',
                        'UTF-8'
                    );
                }
            }

            //fputcsv($file, $fields, self::SEPARATOR, "\0");
            fwrite($file, "\r\n" . implode(self::SEPARATOR, $orderedFields));
        }

        fclose($file);
    }
}