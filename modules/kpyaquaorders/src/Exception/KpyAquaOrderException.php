<?php

namespace PrestaShop\Module\KpyAquaOrders\Exception;


class KpyAquaOrderException extends \Exception
{
    private int $microtime;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        $this->microtime = time();
        parent::__construct($message, $code, $previous);
    }

    public function getFormatDate(string $format = DATE_ATOM): string
    {
        return date($format, $this->microtime);
    }
}