<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Service;

use FTP\Connection;
use PrestaShop\Module\NeftysFarmaConnector\Config\NeftysFarmaConfig;
use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;

class FTPManager
{
    public const string NEFTYS_ORDERS_PATH = 'Entrada/';
    public const string NEFTYS_STOCK_FILE = 'Stock/stock.csv';

    private Connection $connection;

    /**
     * @throws NeftysFarmaException
     */
    private function __construct(string $user, string $pass, string $server, int $port)
    {
        $this->connection = ftp_connect($server, $port);

        if (!$this->connection) {
            throw new NeftysFarmaException('ERROR: FTP connection failed.');
        }

        if (!ftp_login($this->connection, $user, $pass)) {
            ftp_close($this->connection);
            throw new NeftysFarmaException('ERROR: login error. ' . $server);
        }

        // Activa el modo pasivo. De ese forma el servidor no se conecta a nosotros.
        // En el modo normal el servidor tiene que poder conectarse a nosotros para enviar datos
        // El servidor solo escucha, el cliente gestiona la conexión
        ftp_pasv($this->connection, true);
    }

    /**
     * @throws NeftysFarmaException
     */
    public static function getNeftysFarmaConnection(): self
    {
        return new self(\Configuration::get(NeftysFarmaConfig::SFTP_USER) ?? '',
            \Configuration::get(NeftysFarmaConfig::SFTP_PASSWORD) ?? '',
            \Configuration::get(NeftysFarmaConfig::SFTP_SERVER) ?? '',
            (int)\Configuration::get(NeftysFarmaConfig::SFTP_PORT));

    }

    /**
     * @throws NeftysFarmaException
     */
    public function uploadOrderFile(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new NeftysFarmaException('ERROR: no existe el fichero. ' . $filename);
        }

        ftp_put($this->connection, self::NEFTYS_ORDERS_PATH . basename($filename), $filename, FTP_BINARY);

        ftp_close($this->connection);
    }

    public function downloadStockFileAs(string $filename): void
    {
        ftp_get($this->connection, $filename, self::NEFTYS_STOCK_FILE);

        ftp_close($this->connection);
    }

    public function list(): string
    {
        $output = print_r(ftp_nlist($this->connection, '.'), true);
        ftp_close($this->connection);

        return $output;
    }
}