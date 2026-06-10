CREATE TABLE IF NOT EXISTS `PREFIX_kpy_flag`
(
    `id_flag`  int unsigned not null,
    `type`     varchar(100),
    `icon`     varchar(100),
    `color`    varchar(20),
    `bg_color` varchar(20),
    PRIMARY KEY (`id_flag`)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_flag_lang`
(
    `id_flag` int unsigned        not null,
    `id_lang` tinyint(2) unsigned not null default ('1'),
    `name`    varchar(100)        not null,
    primary key (`id_flag`, `id_lang`)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_kpy_product_flag`
(
    `id_product`           int unsigned        not null,
    `id_product_attribute` int unsigned        not null,
    `id_flag`              int unsigned        not null,
    `date_begin`           datetime            null,
    `date_end`             datetime            null,
    `active`               tinyint(1) unsigned null,
    primary key (`id_product`, `id_product_attribute`, `id_flag`),
    foreign key (`id_flag`) references `PREFIX_kpy_flag` (`id_flag`)
) ENGINE = ENGINE_TYPE;

insert into `PREFIX_kpy_flag` (`id_flag`, `type`, `color`, `bg_color`)
values (1, 'Bajada de Precio', '#ffffff', '#DD3737'),
       (2, 'kg Gratis', '#ffffff', '#7F4AFF'),
       (3, 'Popular', '#ffffff', '#22B573'),
       (4, 'Tendencia', '#ffffff', '#FF6D40'),
       (5, 'Top Ventas', '#ffffff', '#1CABEA'),
       (6, 'Envío gratis', '#ffffff', '#14C6B0'),
       (7, 'Innovación', '#FFD45A', '#1F40FF');

insert into `PREFIX_kpy_flag_lang` (`id_flag`, `name`, `id_lang`)
values (1, 'Bajada de Precio', 1),
       (2, 'kg Gratis', 1),
       (3, 'Popular', 1),
       (4, 'Tendencia', 1),
       (5, 'Top Ventas', 1),
       (6, 'Envío gratis', 1),
       (7, 'Innovación', 1);
