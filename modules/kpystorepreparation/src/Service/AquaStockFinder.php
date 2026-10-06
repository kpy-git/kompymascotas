<?php

namespace PrestaShop\Module\KpyStorePreparation\Service;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;

class AquaStockFinder
{
    public function getStockByWarehouse(array $skus, string $warehouse = 'TIENDA'): array
    {
        if (empty($skus)) {
            return [];
        }

        return DbMssql::getInstance()->execute(
            "WITH PENDIENTES AS (
                SELECT MO.CODART, SUM(MO.UNIDADES - MO.INCORPORAD) AS UNIDADES
                    FROM DATOP03 OP WITH(NOLOCK)
                    INNER JOIN DATMO03 MO WITH(NOLOCK)
                        ON MO.NUMERO = OP.NUMERO AND MO.UNIDADES > MO.INCORPORAD
                    WHERE OP.TIPOOPER = 'C' AND OP.CENTRO = 'TIENDA' AND OP.PENDIENTES > 0
                    GROUP BY MO.CODART
            )
            SELECT RTRIM(A.CODIGO) AS SKU, A.EXISTENCIA - ISNULL(PENDIENTES.UNIDADES, 0) AS STOCK
            FROM DATAS03 A WITH(NOLOCK)
            LEFT JOIN PENDIENTES ON PENDIENTES.CODART = A.CODIGO
            WHERE A.ALMACEN = '$warehouse'
                AND (A.EXISTENCIA - ISNULL(PENDIENTES.UNIDADES, 0)) > 0
                AND A.CODIGO IN ('" . implode("','", $skus) . "')");

    }
}