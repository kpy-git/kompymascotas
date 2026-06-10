<?php

namespace PrestaShop\Module\KpyAquaOrders\Exception;

class KpyAquaSqlException extends \Exception
{
    public function __construct(string $message,
                                private readonly string $method,
                                private readonly string $lastSql,
                                private readonly string $sqlError)
    {
        parent::__construct($message);
    }

    public function getLastSql(): string
    {
        return $this->lastSql;
    }

    public function getSqlError(): string
    {
        return $this->sqlError;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

}