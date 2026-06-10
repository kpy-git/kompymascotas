CREATE TABLE IF NOT EXISTS `PREFIX_kpy_vet_review` (
    `id_review` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INTEGER UNSIGNED NOT NULL,
    `id_lang` TINYINT(2) UNSIGNED NOT NULL DEFAULT ('1'),
    `title` VARCHAR(255),
    `review` MEDIUMTEXT,
    PRIMARY KEY (`id_review`),
    FOREIGN KEY (`id_product`) REFERENCES `PREFIX_product` (`id_product`) ON DELETE CASCADE
)ENGINE = ENGINE_TYPE;