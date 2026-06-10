CREATE TABLE IF NOT EXISTS `PREFIX_kpy_account_verify` (
    `id_customer` INTEGER UNSIGNED NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `token` CHAR(40),
    `date_create` DATETIME,
    `date_confirmation` DATETIME,
    `resend_token` CHAR(40),
    `verified` TINYINT(1) UNSIGNED DEFAULT '0',
    PRIMARY KEY (`id_customer`)
)ENGINE = ENGINE_TYPE;

CREATE INDEX `kpy_account_verify_email` ON `PREFIX_kpy_account_verify`(`email`);

CREATE INDEX `kpy_account_verify_verified` ON `PREFIX_kpy_account_verify`(`verified`);