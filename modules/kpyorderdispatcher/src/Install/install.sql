CREATE TABLE IF NOT EXISTS `PREFIX_kpy_orders_pending_dispatch` (
    `id_order` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_order`)
)ENGINE = _MYSQL_ENGINE_;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_order_warehouse` (
    `id_order_warehouse` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `warehouse` VARCHAR(255),
    PRIMARY KEY (`id_order_warehouse`)
)ENGINE = _MYSQL_ENGINE_;