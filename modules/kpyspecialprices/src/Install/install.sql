CREATE TABLE IF NOT EXISTS `PREFIX_kpy_special_price`
(
    `id_product`           int unsigned                    not null,
    `id_product_attribute` int unsigned                    not null,
    `id_shop`              tinyint(2) unsigned             not null,
    `old_discount`         decimal(20, 6) default 0.000000 not null,
    `expire`               datetime                        not null,
    `special_discount`     decimal(20, 6)                  not null,
    `date_from`            datetime                        not null,
    primary key (id_product, id_product_attribute, id_shop, date_from)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_special_price_history`
(
    `id_special_price`     int unsigned auto_increment,
    `id_product`           int unsigned        not null,
    `id_product_attribute` int unsigned        not null,
    `id_shop`              tinyint(2) unsigned not null,
    `special_price`        decimal(20, 6),
    `special_discount`     decimal(20, 6),
    `units_sold`           int unsigned default '0',
    `date_to`              datetime            not null,
    `date_from`            datetime            not null,
    primary key (`id_special_price`),
    index (`id_product`, `id_product_attribute`),
    index (`date_from`, `date_to`)
) ENGINE = ENGINE_TYPE;