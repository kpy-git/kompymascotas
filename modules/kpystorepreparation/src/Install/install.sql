CREATE TABLE IF NOT EXISTS `PREFIX_kpy_store_priority_product_stock` (
       `id_product` int unsigned NOT NULL,
       `id_product_attribute` int unsigned NOT NULL,
       `stock` int unsigned NOT NULL,
       PRIMARY KEY (`id_product`, `id_product_attribute`)
)ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_store_priority_product` (
   `id_product` int unsigned NOT NULL,
   `id_product_attribute` int unsigned NOT NULL,
   PRIMARY KEY (`id_product`, `id_product_attribute`)
)ENGINE=ENGINE_TYPE;