CREATE TABLE IF NOT EXISTS `PREFIX_kpy_distrivet_stock` (
    `id_product` int unsigned NOT NULL,
    `id_product_attribute` int unsigned NOT NULL,
    `distrivet_id` VARCHAR(50) NOT NULL,
    `stock` int unsigned NOT NULL,
    `date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_product`, `id_product_attribute`)
)ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_distrivet_orders` (
    `id_order` int unsigned not null,
    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(`id_order`)
)ENGINE=ENGINE_TYPE;