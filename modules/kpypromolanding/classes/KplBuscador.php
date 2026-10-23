<?php

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

/**
 * Los productos de una o varias categorías nuestras, con el filtro de mascota, para los listados
 * de la landing (la página de una promoción, la de un tipo y «todas»). Es lo que PrestaShop llama
 * un «proveedor de búsqueda»: el controlador de listado le pide una página y él devuelve los
 * productos crudos, que PrestaShop presenta con la tarjeta del tema.
 *
 * Orden por defecto: el mayor descuento primero y, a igualdad, lo más vendido. Después, los de
 * siempre: precio, nombre, ventas. El descuento de cada categoría es `kpy_promo_categoria.pct` (el
 * mayor de sus promociones vivas), que deja escrito la sincronización: en una visita no se cruza
 * nada de kpycoupons.
 */
class KplBuscador implements ProductSearchProviderInterface
{
    /** @var int[] */
    private array $categorias;
    private string $mascota;
    /** @var array{feature: int, perro: int, gato: int} */
    private array $caracteristica;

    /** @param int[] $categorias ids de categoría nuestras; `$mascota` 'perro', 'gato' o '' */
    public function __construct(array $categorias, string $mascota = '')
    {
        $this->categorias = array_values(array_unique(array_filter(array_map('intval', $categorias))));
        $this->mascota = in_array($mascota, ['perro', 'gato'], true) ? $mascota : '';
        $this->caracteristica = KplSincronizar::caracteristicaMascota();
    }

    public function runQuery(ProductSearchContext $context, ProductSearchQuery $query): ProductSearchResult
    {
        $result = new ProductSearchResult();
        $result->setAvailableSortOrders($this->ordenes());
        if ($this->categorias === []) {
            $result->setProducts([])->setTotalProductsCount(0);

            return $result;
        }
        $p = _DB_PREFIX_;
        $idTienda = (int) $context->getIdShop();
        $idIdioma = (int) $context->getIdLang();
        $desde = "FROM {$p}category_product cp
                  INNER JOIN {$p}product pr ON pr.id_product = cp.id_product
                  INNER JOIN {$p}product_shop ps ON ps.id_product = pr.id_product AND ps.id_shop = {$idTienda}
                  INNER JOIN {$p}kpy_promo_categoria kc ON kc.id_category = cp.id_category
                  LEFT JOIN {$p}product_sale s ON s.id_product = pr.id_product
                  LEFT JOIN {$p}product_lang pl ON pl.id_product = pr.id_product AND pl.id_lang = {$idIdioma} AND pl.id_shop = {$idTienda}
                  WHERE cp.id_category IN (" . implode(',', $this->categorias) . ")
                    AND ps.active = 1 AND ps.visibility IN ('both', 'catalog')";
        if ($this->mascota !== '' && $this->caracteristica['feature'] > 0) {
            $valor = $this->mascota === 'perro' ? (int) $this->caracteristica['perro'] : (int) $this->caracteristica['gato'];
            $desde .= " AND EXISTS (SELECT 1 FROM {$p}feature_product fp WHERE fp.id_product = pr.id_product AND fp.id_feature = " . (int) $this->caracteristica['feature'] . " AND fp.id_feature_value = {$valor})";
        }
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $total = (int) $db->getValue('SELECT COUNT(DISTINCT cp.id_product) ' . $desde);
        $orden = $this->orden($query->getSortOrder());
        $porPagina = max(1, (int) $query->getResultsPerPage());
        $pagina = max(1, (int) $query->getPage());
        $filas = $db->executeS(
            'SELECT cp.id_product, MAX(IFNULL(kc.pct, 0)) AS kpl_pct, MAX(IFNULL(s.quantity, 0)) AS kpl_ventas, MIN(cp.position) AS kpl_pos, MAX(pl.name) AS kpl_nombre, MAX(ps.price) AS kpl_precio '
            . $desde . ' GROUP BY cp.id_product ORDER BY ' . $orden . ' LIMIT ' . (($pagina - 1) * $porPagina) . ', ' . $porPagina
        ) ?: [];
        $productos = [];
        foreach ($filas as $f) {
            $productos[] = ['id_product' => (int) $f['id_product'], 'id_product_attribute' => 0];
        }
        $result->setProducts($productos)->setTotalProductsCount($total);

        return $result;
    }

    /** Los órdenes que se ofrecen en el desplegable. @return SortOrder[] */
    private function ordenes(): array
    {
        $t = Context::getContext()->getTranslator();

        return [
            (new SortOrder('product', 'position', 'desc'))->setLabel('Mayor descuento'),
            (new SortOrder('product', 'sales', 'desc'))->setLabel($t->trans('Best sales', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'price', 'asc'))->setLabel($t->trans('Price, low to high', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'price', 'desc'))->setLabel($t->trans('Price, high to low', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'name', 'asc'))->setLabel($t->trans('Name, A to Z', [], 'Shop.Theme.Catalog')),
            (new SortOrder('product', 'name', 'desc'))->setLabel($t->trans('Name, Z to A', [], 'Shop.Theme.Catalog')),
        ];
    }

    /** El ORDER BY de un orden pedido; cualquier cosa rara vuelve al de por defecto. */
    private function orden(?SortOrder $o): string
    {
        $campo = $o ? (string) $o->getField() : 'position';
        $sentido = $o && strtolower((string) $o->getDirection()) === 'asc' ? 'ASC' : 'DESC';
        switch ($campo) {
            case 'price':
                return 'kpl_precio ' . $sentido . ', kpl_ventas DESC';
            case 'name':
                return 'kpl_nombre ' . $sentido . ', cp.id_product ASC';
            case 'sales':
                return 'kpl_ventas DESC, kpl_pct DESC';
            case 'position':
            default:
                return 'kpl_pct DESC, kpl_ventas DESC, kpl_pos ASC, cp.id_product ASC';
        }
    }
}
