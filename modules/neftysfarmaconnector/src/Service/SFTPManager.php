<?php

declare(strict_types=1);

namespace PrestaShop\Module\NeftysFarmaConnector\Service;

use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use phpseclib3\Net\SFTP;

class SFTPManager
{
    public const string NEFTYS_ORDERS_PATH = 'Entrada/';

    private string $user;

    private string $pass;

    private string $server;

    private int $port;

    public function __construct(string $user, string $pass, string $server, int $port = 22)
    {
        $this->user = $user;
        $this->pass = $pass;
        $this->server = $server;
        $this->port = $port;
    }

    public static function connectNeftysFarma(): self
    {
        return new self(\Configuration::get(NeftysFarmaConfig::SFTP_USER) ?? '',
            \Configuration::get(NeftysFarmaConfig::SFTP_PASSWORD) ?? '',
            \Configuration::get(NeftysFarmaConfig::SFTP_SERVER) ?? '',
            (int)\Configuration::get(NeftysFarmaConfig::SFTP_PORT)
        );
    }

    /**
     * @throws NeftysFarmaException
     */
    public function uploadOrderFile(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new NeftysFarmaException('ERROR: no existe el fichero. ' . $filename);
        }

        $sftp = new SFTP($this->server, $this->port);

        if (!$sftp->login($this->user, $this->pass)) {
            throw new NeftysFarmaException('ERROR: no se puede conectar con ' . $this->server);
        }

        $sftp->put(self::NEFTYS_ORDERS_PATH . basename($filename), $filename, SFTP::SOURCE_LOCAL_FILE);

        $sftp->disconnect();
    }
}