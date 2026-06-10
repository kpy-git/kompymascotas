<?php

namespace PrestaShop\Module\NeftysFarmaConnector\Service;

use PrestaShop\Module\NeftysFarmaConnector\Exception\NeftysFarmaException;
use PrestaShop\Module\NeftysFarmaConnector\Repository\NeftysFarmaStockRepository;

class NeftysFarmaStockSynchronizer
{

    private array $missingEans = [];

    private int $countEanSynchronized = 0;

    private NeftysFarmaStockRepository $neftysFarmaStockRepository;

    private ProductFinder $productFinder;

    /**
     * @throws NeftysFarmaException
     */
    public function __construct()
    {
        $this->neftysFarmaStockRepository = new NeftysFarmaStockRepository();
        $this->productFinder = new ProductFinder();

    }

    public function stockSync(string $filename): void
    {
        $neftysFarmaStockByEan = $this->loadNeftysFarmaStock($filename);

        $kpyProductsByEan = $this->neftysFarmaStockRepository->findAllProductsByEan();

        $stockNeftys = [];

        foreach ($neftysFarmaStockByEan as $eanNeftysFarma => $stockNeftysFarma) {
            if (!isset($kpyProductsByEan[$eanNeftysFarma])) {
                $this->missingEans[] = $eanNeftysFarma;
                continue;
            }

            $stockNeftys[] = [
                'id_product' => $kpyProductsByEan[$eanNeftysFarma]['id_product'],
                'id_product_attribute' => $kpyProductsByEan[$eanNeftysFarma]['id_product_attribute'],
                'stock' => $stockNeftysFarma,
            ];
            $this->countEanSynchronized++;

            $productPacks = $this->productFinder->getMonoproductPacksByProduct(
                $kpyProductsByEan[$eanNeftysFarma]['id_product'],
                $kpyProductsByEan[$eanNeftysFarma]['id_product_attribute'],
            );

            foreach($productPacks as $pack) {
                    [$idPack, $attrPack] = explode('-', $pack['id_product_pack']);

                    $stockNeftys[] = [
                        'id_product' => (int)$idPack,
                        'id_product_attribute' => (int)$attrPack,
                        'stock' => (int)floor($stockNeftysFarma / (int)$pack['quantity']),
                    ];
                    $this->countEanSynchronized++;
            }

        }

        $this->neftysFarmaStockRepository->save($stockNeftys);
    }

    /**
     * @throws NeftysFarmaException
     */
    private function loadNeftysFarmaStock($filename): array
    {
        $file = fopen($filename, "rb");

        if (!$file) {
            throw new NeftysFarmaException('Ha ocurrido un error al abrir el fichero: ' . $filename);
        }

        $neftysFarmaStockByEan = [];

        while (($data = fgetcsv($file, 1000, "|")) !== false) {
            if (!is_numeric($data[1]) || empty(trim($data[0]))) {
                continue;
            }

            // pasamos a entero en EAN para así limpiar los 0 por la izquierda
            $neftysFarmaStockByEan[(int)trim($data[0])] = max(0, (int)$data[1]);
        }

        fclose($file);

        return $neftysFarmaStockByEan;
    }

    public function getMissingEans(): array
    {
        return $this->missingEans;
    }

    public function existsMissingEans(): bool
    {
        return !empty($this->missingEans);
    }

    public function getCountEansSynchronized(): int
    {
        return $this->countEanSynchronized;
    }
}