<?php

namespace PrestaShop\Module\KpyProductAvailabilityMessages\Config;

class Tables
{
    public const string TABLE_PENDING_ORDERS = _DB_PREFIX_ . 'kpy_orders_pending_review';
    public const string TABLE_SUPPLIERS = _DB_PREFIX_ . 'kpy_suppliers_bag';
    public const string TABLE_SUPPLIER_MANUFACTURER = _DB_PREFIX_ . 'kpy_supplier_manufacturer';
    public const string TABLE_SUPPLIER_PRODUCT = _DB_PREFIX_ . 'kpy_supplier_product';
    public const string TABLE_PRODUCT_ARRIVAL_DATE = _DB_PREFIX_ . 'kpy_product_arrival_date';
}