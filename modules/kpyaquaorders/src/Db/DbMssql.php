<?php

namespace PrestaShop\Module\KpyAquaOrders\Db;

use PrestaShop\Module\KpyAquaOrders\Exception\KpyAquaSqlException;

class DbMssql
{
    public const int MAX_VALUES_INSERT = 500;

    private \PDO $link;

    private string $lastSql; // guarda la última consulta que ejecuta la clase para poder devolverla

    private string $sqlError;

    private static ?self $instance = null;

    /**
     * @throws KpyAquaSqlException
     */
    private function __construct()
    {
        try {
            $config = new DbConfig();

            $this->link = new \PDO($config->getDsn(), $config->getUsername(), $config->getPassword());

            $this->link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->link->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->sqlError = '';

        } catch (\PDOException $e) {
            $this->sqlError = $e->getMessage();
            throw new KpyAquaSqlException($e->getMessage(), __METHOD__, '', '');
        }
    }

    /**
     * @throws KpyAquaSqlException
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function execute(string $sql, int $mode = \PDO::FETCH_ASSOC): bool|array
    {
        $this->sqlError = '';
        $this->lastSql = $sql;

        if ($this->isDML($sql)) {
            // podría devolver 0 filas afectadas, por eso se compara con false para evitar falsos errores
            return $this->link->exec($sql) !== false;
        }

        return $this->link->query($sql)->fetchAll($mode);
    }

    private function isDML(string $sql): bool
    {
        $sqlSanitized = mb_strtoupper(trim($sql));

        return str_starts_with($sqlSanitized, 'UPDATE')
            || str_starts_with($sqlSanitized, 'INSERT')
            || str_starts_with($sqlSanitized, 'DELETE');
    }

    public function getRow(string $sql): array
    {
        if ($this->isDML($sql)) {
            return [];
        }

        $this->sqlError = '';
        $this->lastSql = $sql;

        return $this->link->query($sql)->fetch(\PDO::FETCH_ASSOC);
    }

    public function withError(): bool
    {
        return $this->sqlError !== '' || $this->link->errorCode() !== '00000';
    }

    public function insert(string $table, array $data): bool
    {
        return $this->multiInsert($table, [$data]);
    }

    public function multiInsert(string $table, array $data, int $maxValueInsert = self::MAX_VALUES_INSERT): int
    {
        //Will contain SQL snippets.
        $rowsSQL = array();

        //Will contain the values that we need to bind.
        $toBind = array();

        //Get a list of column names to use in the SQL statement.
        $columnNames = array_keys($data[0]);

        //Loop through our $data array.
        foreach ($data as $arrayIndex => $row) {
            $params = array();

            foreach ($row as $columnName => $columnValue) {
                $param = ":" . $columnName . $arrayIndex;
                $params[] = $param;
                $toBind[$param] = $columnValue;
            }
            $rowsSQL[] = "(" . implode(", ", $params) . ")";
        }

        $chunks = array_chunk($rowsSQL, $maxValueInsert);

        foreach ($chunks as $values) {
            $sql = "INSERT INTO {$table} (" . implode(", ", $columnNames) . ") VALUES " . implode(", ", $values);
            $this->lastSql = $sql;
            $stmt = $this->prepare($sql);

            foreach ($toBind as $param => $val) {
                $stmt->bindValue($param, $val);
            }

            $stmt->execute();
        }

        return count($rowsSQL);
    }

    /**
     * Obtiene el valor de la primera fila y la primera columna de los resultados de la consulta
     */
    public function getValue($sql): mixed
    {
        return $this->link->query($sql, \PDO::FETCH_NUM)->fetchColumn();
    }

    public function getSqlError(): string
    {
        return $this->sqlError;
    }

    public function getLastSql(): string
    {
        return preg_replace('/\s\s+/', ' ', $this->lastSql);
    }

    public function beginTransaction(): void
    {
        $this->link->beginTransaction();
    }

    public function commit(): void
    {
        $this->link->commit();
    }

    public function rollback(): void
    {
        $this->link->rollback();
    }

    public function prepare(string $sql, array $options = []): \PDOStatement
    {
        $this->lastSql = $sql;
        $this->sqlError = '';
        return $this->link->prepare($sql, $options);
    }

    public function prepareForSelect(string $sql): \PDOStatement
    {
        return $this->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
    }
}