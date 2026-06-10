CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer` (
  `id_manufacturer` int unsigned NOT NULL,
  `id_category_related` int unsigned NOT NULL,
  `link_rewrite` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_manufacturer`)
) ENGINE=ENGINE_TYPE;

CREATE UNIQUE INDEX `PREFIX_kpy_manufacturer_link_rewrite_uindex`
    ON `PREFIX_kpy_manufacturer` (link_rewrite);

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer_landing` (
    `id_landing` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_manufacturer` INTEGER UNSIGNED NOT NULL,
    `products` VARCHAR(255),
    PRIMARY KEY (`id_landing`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer_landing_lang` (
    `id_landing` INTEGER UNSIGNED NOT NULL,
    `id_lang` TINYINT(2) UNSIGNED NOT NULL DEFAULT (1),
    `title` VARCHAR(100),
    `subtitle` MEDIUMTEXT,
    PRIMARY KEY (`id_landing`, `id_lang`)
)ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer_landing_pill` (
    `id_pill` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_lang` TINYINT(2) UNSIGNED NOT NULL DEFAULT (1),
    `pill` VARCHAR(255) NOT NULL,
    `id_landing` INTEGER UNSIGNED NOT NULL,
    PRIMARY KEY (`id_pill`)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer_landing_category` (
    `id_landing_category` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_landing` INTEGER UNSIGNED NOT NULL,
    `id_category` INTEGER UNSIGNED NOT NULL,
    `pet` ENUM('DOG', 'CAT', 'ALL'),
    `title` VARCHAR(100),
    `subtitle` VARCHAR(255),
    PRIMARY KEY (`id_landing_category`)
)ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer_landing_banner` (
    `id_landing_banner` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_landing` INTEGER UNSIGNED NOT NULL,
    `id_shop` INTEGER UNSIGNED NOT NULL,
    `image` VARCHAR(100) NOT NULL,
    `description` VARCHAR(100),
    `url` MEDIUMTEXT,
    PRIMARY KEY (`id_landing_banner`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_manufacturer_landing_video` (
    `id_landing_video` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_landing` INTEGER UNSIGNED NOT NULL,
    `id_shop` INTEGER UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255),
    `subtitle` VARCHAR(255),
    `url` MEDIUMTEXT NOT NULL,
    PRIMARY KEY (`id_landing_video`)
) ENGINE=ENGINE_TYPE;