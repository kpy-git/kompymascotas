<?php

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

/**
 * Unos productos (por id) tal como los pinta la tarjeta del tema (`catalog/_partials/miniatures/product.tpl`):
 * con su precio, su Special Price, sus etiquetas y su foto. Es lo mismo que hace PrestaShop en un
 * listado, para los bloques de la landing («lo más rebajado», los productos de una marca) que no
 * son un listado entero.
 */
class KplPresentador
{
    /**
     * @param int[] $ids en el orden en que se quieren
     *
     * @return array los productos presentados, listos para la plantilla
     */
    public static function productos(array $ids, int $maximo = 8): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        $ids = array_slice($ids, 0, $maximo);
        $contexto = Context::getContext();
        $idTienda = (int) $contexto->shop->id;
        $p = _DB_PREFIX_;
        // Solo los que se venden, en el orden pedido.
        $filas = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "SELECT pr.id_product FROM {$p}product pr
             INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = {$idTienda}
             WHERE pr.id_product IN (" . implode(',', $ids) . ") AND ps.active = 1 AND ps.visibility IN ('both', 'catalog')
             ORDER BY FIELD(pr.id_product, " . implode(',', $ids) . ')'
        ) ?: [];
        if ($filas === []) {
            return [];
        }
        $assembler = new ProductAssembler($contexto);
        $factoria = new ProductPresenterFactory($contexto);
        $ajustes = $factoria->getPresentationSettings();
        $presentador = new ProductListingPresenter(
            new ImageRetriever($contexto->link),
            $contexto->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $contexto->getTranslator()
        );
        $crudos = array_map(static fn (array $f): array => ['id_product' => (int) $f['id_product'], 'id_product_attribute' => 0], $filas);
        if (method_exists($assembler, 'assembleProducts')) {
            $crudos = $assembler->assembleProducts($crudos);
        } else {
            $crudos = array_map(static fn (array $c): array => $assembler->assembleProduct($c), $crudos);
        }
        $salida = [];
        foreach ($crudos as $crudo) {
            try {
                $salida[] = $presentador->present($ajustes, $crudo, $contexto->language);
            } catch (Throwable $e) {
                // Un producto raro no tira la landing: se salta.
            }
        }

        return $salida;
    }
}
