<?php

namespace PrestaShop\Module\KpyAquaOrders\Service;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;

class OperationCounter
{
    public function __construct(private readonly DbMssql $aqua)
    {
    }

    public function getNewOperationNumber(string $empresa, string $ejercicio): int
    {
        $this->aqua->beginTransaction();

        $operacion = $this->aqua->getValue("SELECT NUMERO_OP
                        FROM DATINTERNALCOUNTER WITH (UPDLOCK)
                        WHERE COMPANY = '{$empresa}' AND EXERCISE = '{$ejercicio}'");

        $siguiente = $operacion + 1;

        $this->aqua->execute("UPDATE DATINTERNALCOUNTER
            SET DATINTERNALCOUNTER.NUMERO_OP = {$siguiente}
            WHERE COMPANY = '{$empresa}' AND EXERCISE = '{$ejercicio}'");

        $this->aqua->commit();

        return $operacion;
    }

    public function getNewRangeOperationNumber(string $empresa, string $ejercicio, int $count): array
    {
        $this->aqua->beginTransaction();

        $operacion = $this->aqua->getValue("SELECT NUMERO_OP
                        FROM DATINTERNALCOUNTER WITH (UPDLOCK)
                        WHERE COMPANY = '{$empresa}' AND EXERCISE = '{$ejercicio}'");

        $siguiente = $operacion + $count;

        $this->aqua->execute("UPDATE DATINTERNALCOUNTER
            SET DATINTERNALCOUNTER.NUMERO_OP = {$siguiente}
            WHERE COMPANY = '{$empresa}' AND EXERCISE = '{$ejercicio}'");

        $this->aqua->commit();

        return range($operacion, $siguiente - 1);
    }
}