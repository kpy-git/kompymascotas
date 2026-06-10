ALTER TABLE PREFIX_category ADD `is_main_category` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE PREFIX_category ADD `has_image_fixed` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE PREFIX_category ADD `id_product_image_cover` INTEGER UNSIGNED;

ALTER TABLE PREFIX_category ADD `id_twin_category` INTEGER UNSIGNED;

ALTER TABLE PREFIX_category ADD `is_landing` TINYINT(1) NOT NULL DEFAULT '0';

ALTER TABLE PREFIX_category ADD `image_link` VARCHAR(255);