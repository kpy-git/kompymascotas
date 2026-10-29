CREATE TABLE IF NOT EXISTS `PREFIX_kpy_google_feed_custom_labels` (
    `id_product` INT UNSIGNED NOT NULL,
    `id_product_attribute` INT UNSIGNED NOT NULL,
    `custom_label_0` VARCHAR(255),
    PRIMARY KEY (`id_product`,`id_product_attribute`)
) ENGINE=ENGINE_TYPE;