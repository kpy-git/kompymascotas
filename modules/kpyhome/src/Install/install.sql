CREATE TABLE IF NOT EXISTS `PREFIX_kpy_home_slider_banner` (
    `id_slider_banner` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image` VARCHAR(100) NOT NULL,
    `description` varchar(255),
    `url` varchar(255),
    `position` TINYINT UNSIGNED NOT NULL,
    `date_from` DATETIME,
    `date_to` DATETIME,
    `mobile` TINYINT(1) DEFAULT '0',
    `active` TINYINT(1) DEFAULT '0',
    PRIMARY KEY(`id_slider_banner`)
)ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_home_category` (
    `id_home_category` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_category` INT UNSIGNED NOT NULL,
    `image` VARCHAR(100) NOT NULL,
    `title` VARCHAR(100),
    `subtitle` VARCHAR(100),
    `position` TINYINT UNSIGNED NOT NULL,
    `mobile` TINYINT(1) DEFAULT '0',
    PRIMARY KEY (`id_home_category`),
    UNIQUE KEY(`id_category`, `mobile`)
)ENGINE=ENGINE_TYPE;