CREATE TABLE IF NOT EXISTS `PREFIX_kpy_faq_section` (
    `id_section` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `position` TINYINT UNSIGNED,
    `image` varchar(100),
    `active` TINYINT(1) DEFAULT '1',
    PRIMARY KEY (`id_section`)
)ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_faq_section_lang` (
    `id_section` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_lang` INTEGER UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_section`, `id_lang`)
)ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_faq_element` (
    `id_element` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_section` INTEGER UNSIGNED NOT NULL,
    `position`  TINYINT UNSIGNED,
    `active` TINYINT(1) DEFAULT '1',
    PRIMARY KEY (`id_element`)
)ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_faq_element_lang` (
    `id_element` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_lang` INTEGER UNSIGNED NOT NULL,
    `question` VARCHAR(100) NOT NULL,
    `answer` MEDIUMTEXT,
    `link_rewrite` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_element`, `id_lang`),
    UNIQUE KEY (`link_rewrite`)
)ENGINE = ENGINE_TYPE;