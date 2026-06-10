CREATE TABLE IF NOT EXISTS `PREFIX_kpy_benefit_product` (
    `id_benefit` INTEGER unsigned NOT NULL,
    `id_product` INTEGER unsigned NOT NULL,
    PRIMARY KEY (`id_benefit`, `id_product`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_benefits` (
    `id_benefit` int unsigned NOT NULL AUTO_INCREMENT,
    `image` varchar(100),
    `protege` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id_benefit`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_benefit_lang` (
    `id_benefit` int unsigned NOT NULL AUTO_INCREMENT,
    `id_lang` int unsigned NOT NULL,
    `name` varchar(100),
    PRIMARY KEY (`id_benefit`, `id_lang`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_product_ingredients` (
    `id_product` integer unsigned not null,
    `id_lang` integer unsigned not null,
    `text` mediumtext not null,
    PRIMARY KEY (`id_product`, `id_lang`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_components` (
    `id_component` INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (`id_component`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_component_lang` (
    `id_component` INTEGER UNSIGNED NOT NULL,
    `id_lang` INTEGER UNSIGNED NOT NULL,
    `name` varchar(255),
   PRIMARY KEY (`id_component`, `id_lang`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_product_component` (
    `id_component` INTEGER UNSIGNED NOT NULL,
    `id_product` INTEGER UNSIGNED NOT NULL,
    `percentage` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_component`, `id_product`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_dosis_table` (
    `id_product` INTEGER UNSIGNED NOT NULL,
    `table` mediumtext NOT NULL,
    PRIMARY KEY (`id_product`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE `PREFIX_kpy_product_rations_table` (
    `id_product` int unsigned NOT NULL,
    `table` mediumtext NOT NULL,
    PRIMARY KEY (`id_product`)
) ENGINE=ENGINE_TYPE;