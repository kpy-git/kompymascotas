CREATE TABLE IF NOT EXISTS `TABLE_PENDING_ORDERS`
(
    id_order INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (id_order)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `TABLE_SUPPLIERS`
(
    codigo_proveedor              INT(10) UNSIGNED NOT NULL,
    importe_acumulado_en_negativo DECIMAL(20, 6)   NOT NULL DEFAULT 0.00,
    importe_pedido_minimo         DECIMAL(20, 6)   NOT NULL DEFAULT 0.00,
    dias_de_servicio              INT(2) UNSIGNED           DEFAULT 0,
    dias_de_procesamiento         INT(2) UNSIGNED           DEFAULT 0,
    dias_maximos_entre_pedidos    INT(2) UNSIGNED           DEFAULT 0,
    fecha_ultimo_pedido           DATE,
    PRIMARY KEY (codigo_proveedor)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `TABLE_SUPPLIER_MANUFACTURER`
(
    id_manufacturer INT UNSIGNED NOT NULL,
    id_supplier     INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_manufacturer, id_supplier),
    FOREIGN KEY (id_supplier) REFERENCES `TABLE_SUPPLIERS` (codigo_proveedor)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `TABLE_PRODUCT_ARRIVAL_DATE`
(
    `id_product`           int unsigned NOT NULL,
    `id_product_attribute` int unsigned NOT NULL,
    `arrival_date`         date         NOT NULL,
    PRIMARY KEY (`id_product`, `id_product_attribute`)
) ENGINE = ENGINE_TYPE;

CREATE TABLE IF NOT EXISTS `TABLE_SUPPLIER_PRODUCT`
(
    id_product           INT UNSIGNED NOT NULL,
    id_product_attribute INT UNSIGNED NOT NULL,
    supplier             INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_product, id_product_attribute)
) ENGINE = ENGINE_TYPE;