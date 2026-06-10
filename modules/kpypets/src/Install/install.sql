CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_customer` INT UNSIGNED DEFAULT NULL,
    `size` INT  DEFAULT 0,
    `sex` char(1)  DEFAULT "M",
    `neutered` boolean  DEFAULT FALSE,
    `long_hair` boolean  DEFAULT FALSE,
    `sleeps_out` boolean  DEFAULT FALSE,
    `name` varchar(255) NOT NULL,
    `hair_color` varchar(255),
    `id_kind` smallint unsigned NOT NULL,
    `id_adquisition` SMALLINT UNSIGNED NOT NULL,
    `id_habitat` SMALLINT UNSIGNED NOT NULL,
    `id_race` smallint unsigned DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    `birth_date` DATE NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_habitats` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_habitats_lang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_habitat` int NOT NULL,
  `id_lang` int NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
)ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_adquisition` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_adquisition_lang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_lang` int NOT NULL,
  `id_adquisition` int NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_diseases` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_diseases_lang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_lang` int NOT NULL,
  `id_disease` int NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_disease_relation` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pet` INT UNSIGNED DEFAULT NULL,
  `id_disease` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_kinds` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_kinds_lang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_kind` int NOT NULL,
  `id_lang` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `nameplural` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_races` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_kind` SMALLINT UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_pet_races_lang` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_race` int NOT NULL,
  `id_lang` int NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=ENGINE_TYPE;


CREATE TABLE  IF NOT EXISTS `PREFIX_kpy_pet_sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(5) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=ENGINE_TYPE;