<?php

namespace PrestaShop\Module\KpyAquaOrders\Db;

class DbConfig
{
    public const string AQUA_HOST = 'KPY_AQUAORDERS_HOST';
    public const string AQUA_USER = 'KPY_AQUAORDERS_USER';
    public const string AQUA_PASSWORD = 'KPY_AQUAORDERS_PASSWORD';
    public const string AQUA_DATABASE = 'KPY_AQUAORDERS_DATABASE';

    private array $parameters;

    public function __construct()
    {
        $this->parameters = [
            'user' => \Configuration::get(self::AQUA_USER),
            'password' => \Configuration::get(self::AQUA_PASSWORD),
            'host' => \Configuration::get(self::AQUA_HOST),
            'database' => \Configuration::get(self::AQUA_DATABASE),
        ];
    }

    public function getUsername(): string
    {
        return $this->parameters['user'];
    }

    public function getPassword(): string
    {
        return $this->parameters['password'];
    }

    public function getDsn(): string
    {
        return sprintf("sqlsrv:server=%s;database=%s;TrustServerCertificate=Yes",
            $this->parameters['host'],
            $this->parameters['database']);
    }
}