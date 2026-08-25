<?php

namespace PrestaShop\Module\KpyDistrivetConnector\Repository;

use PrestaShop\Module\KpyAquaOrders\Db\DbMssql;
use PrestaShop\Module\KpyDistrivetConnector\DTO\DistrivetStockProductDTO;
use PrestaShop\Module\KpyDistrivetConnector\Exception\KpyDistrivetProductNotFoundException;

class StockRepository
{
    public function save(array $stockDistrivet): void
    {
        if (empty($stockDistrivet)) {
            return;
        }

        \Db::getInstance()->execute("TRUNCATE TABLE `" . _DB_PREFIX_ . "kpy_distrivet_stock`");

        \Db::getInstance()->insert('kpy_distrivet_stock', array_map(
            static function (DistrivetStockProductDTO $product): array {
                return [
                    'id_product' => $product->getProductId(),
                    'id_product_attribute' => $product->getProductAttributeId(),
                    'stock' => $product->getStock(),
                    'distrivet_id' => $product->getDistrivetId(),
                    'date_update' => $product->getUpdatedAt()->format('Y-m-d H:i:s'),
                    'distrivet_name' => $product->getDistrivetName(),
                ];
            }, $stockDistrivet));

        \Db::getInstance()->execute(
            "UPDATE " . _DB_PREFIX_ . "stock_available sa
                    INNER JOIN " . _DB_PREFIX_ . "kpy_distrivet_stock ds 
                        on ds.id_product=sa.id_product 
                            and ds.id_product_attribute=sa.id_product_attribute
                    SET sa.quantity = ds.stock"
        );

        \Db::getInstance()->execute(
            "UPDATE " . _DB_PREFIX_ . "stock_available sa
                INNER JOIN (
                    SELECT id_product, SUM(stock) AS total_stock
                    FROM " . _DB_PREFIX_ . "kpy_distrivet_stock
                    GROUP BY id_product
                ) ds_total ON sa.id_product = ds_total.id_product
                SET sa.quantity = ds_total.total_stock
                WHERE sa.id_product_attribute = 0"
        );

        $aqua = DbMssql::getInstance();
        $stmt = $aqua->prepare("UPDATE DATAS03 SET EXISTENCIA=? WHERE ALMACEN='DISTRIVET' AND CODIGO=?");
        $stmtGeneral = $aqua->prepare("UPDATE DATIN03 SET EXISTENCIA=(SELECT SUM(EXISTENCIA) FROM DATAS03 WHERE CODIGO=?) WHERE CODIGO=?");

        /** @var DistrivetStockProductDTO $stock */
        foreach ($stockDistrivet as $product) {
            if ($product->isPack()) {
                continue;
            }
            $stmt->bindValue(1, $product->getStock(), \PDO::PARAM_INT);
            $stmt->bindValue(2, $product->getSku());
            $stmt->execute();

            $stmtGeneral->bindValue(1, $product->getSku());
            $stmtGeneral->bindValue(2, $product->getSku());
            $stmtGeneral->execute();
        }
    }

    public function findAllProductsByEan(): array
    {
        $results = \Db::getInstance()->executeS(
            "SELECT p.id_product, 
                IFNULL(pa.id_product_attribute, 0) as attr, 
                IFNULL(pa.ean13, p.ean13) as ean,
                p.id_manufacturer
				FROM " . _DB_PREFIX_ . "product p
				LEFT JOIN " . _DB_PREFIX_ . "product_attribute pa
					ON pa.id_product = p.id_product
				WHERE p.visibility = 'both'
	                AND p.active = 1 
	                AND NOT EXISTS (SELECT 1 
						FROM " . _DB_PREFIX_ . "kpy_packs kp 
						WHERE kp.id_product_pack = CONCAT_WS('-', p.id_product, pa.id_product_attribute))"
        );

        $productsByEan = [];

        foreach ($results as $product) {
            if ($product['ean'] === null || strlen($product['ean']) < 7) {
                continue;
            }

            $productsByEan[$product['ean']] = [
                'id_product' => (int)$product['id_product'],
                'id_product_attribute' => (int)$product['attr'],
                'manufacturer' => $product['id_manufacturer'],
            ];
        }

        return $productsByEan;
    }

    /**
     * @throws KpyDistrivetProductNotFoundException
     */
    public function findBySKUOrFail(string $sku): DistrivetStockProductDTO
    {
        [$id, $attr] = explode('-', $sku);

        $result = \Db::getInstance()->getRow(
            "SELECT id_product, id_product_attribute, stock, distrivet_id, distrivet_name, date_update 
                FROM " . _DB_PREFIX_ . "kpy_distrivet_stock 
                WHERE id_product_attribute = " . $attr . " AND id_product = " . $id
        );

        if (empty($result)) {
            throw new KpyDistrivetProductNotFoundException('No existe en Distrivet ningún producto con sku ' . $sku);
        }

        return new DistrivetStockProductDTO(
            $result['id_product'],
            $result['id_product_attribute'],
            $result['distrivet_id'],
            $result['distrivet_name'],
            $result['stock'],
            new \DateTimeImmutable($result['date_update']),
            str_starts_with($result['distrivet_name'], 'Pack')
        );
    }
}