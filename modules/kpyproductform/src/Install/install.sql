CREATE TABLE IF NOT EXISTS PREFIX_kpy_product_attribute(
    `id_product_attribute` INT UNSIGNED NOT NULL,
    `active` TINYINT UNSIGNED DEFAULT '0',
    PRIMARY KEY (`id_product_attribute`)
) ENGINE = ENGINE_TYPE;