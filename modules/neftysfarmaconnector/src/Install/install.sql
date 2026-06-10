CREATE TABLE IF NOT EXISTS `NEFTYS_ORDER_TABLE` (
	`id_order` int unsigned NOT NULL,
	`date_sync` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`neftys_albaran` VARCHAR(50),
	`neftys_albaran_date` DATETIME,
	`neftys_tracking_number` VARCHAR(50),
	`date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id_order`)
)ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `NEFTYS_STOCK_TABLE` (
	`id_product` int unsigned NOT NULL,
	`id_product_attribute` int unsigned NOT NULL,
	`stock` int unsigned NOT NULL,
	`date_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id_product`, `id_product_attribute`)
)ENGINE=ENGINE_TYPE;