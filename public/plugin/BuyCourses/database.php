<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/*
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php.
 *
 * Check if script can be called.
 */
if (!function_exists('api_get_path')) {
    exit('This script must be loaded through the Chamilo plugin installer sequence');
}

$entityManager = Database::getManager();
$pluginSchema = new Schema();
$connection = $entityManager->getConnection();
$platform = $connection->getDatabasePlatform();
$sm = $connection->createSchemaManager();

// Create tables
if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_PAYPAL)) {
    $paypalTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_PAYPAL);
    $paypalTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $paypalTable->addColumn('username', Types::STRING);
    $paypalTable->addColumn('password', Types::STRING);
    $paypalTable->addColumn('signature', Types::STRING);
    $paypalTable->addColumn('sandbox', Types::BOOLEAN);
    $paypalTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_TRANSFER)) {
    $transferTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_TRANSFER);
    $transferTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $transferTable->addColumn('name', Types::STRING);
    $transferTable->addColumn('account', Types::STRING);
    $transferTable->addColumn('swift', Types::STRING);
    $transferTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_TPV_REDSYS)) {
    $tpvRedsysTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_TPV_REDSYS);
    $tpvRedsysTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $tpvRedsysTable->addColumn('merchantcode', Types::STRING);
    $tpvRedsysTable->addColumn('terminal', Types::STRING);
    $tpvRedsysTable->addColumn('currency', Types::STRING);
    $tpvRedsysTable->addColumn('kc', Types::STRING);
    $tpvRedsysTable->addColumn('url_redsys', Types::STRING);
    $tpvRedsysTable->addColumn('url_redsys_sandbox', Types::STRING);
    $tpvRedsysTable->addColumn('sandbox', Types::BOOLEAN);
    $tpvRedsysTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_CURRENCY)) {
    $currencyTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_CURRENCY);
    $currencyTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $currencyTable->addColumn(
        'country_code',
        Types::STRING,
        ['length' => 2]
    );
    $currencyTable->addColumn(
        'country_name',
        Types::STRING,
        ['length' => 255]
    );
    $currencyTable->addColumn(
        'iso_code',
        Types::STRING,
        ['length' => 3]
    );
    $currencyTable->addColumn('status', Types::BOOLEAN);
    $currencyTable->addUniqueIndex(['country_code']);
    $currencyTable->addIndex(['iso_code']);
    $currencyTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_ITEM)) {
    $itemTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_ITEM);
    $itemTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $itemTable->addColumn('product_type', Types::INTEGER);
    $itemTable->addColumn(
        'product_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $itemTable->addColumn(
        'price',
        Types::DECIMAL,
        ['scale' => 2]
    );
    $itemTable->addColumn(
        'currency_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $itemTable->addColumn(
        'tax_perc',
        Types::INTEGER,
        ['unsigned' => true, 'notnull' => false]
    );
    $itemTable->setPrimaryKey(['id']);
    $itemTable->addForeignKeyConstraint(
        $currencyTable,
        ['currency_id'],
        ['id'],
        ['onDelete' => 'CASCADE']
    );
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_ITEM_BENEFICIARY)) {
    $itemBeneficiary = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_ITEM_BENEFICIARY);
    $itemBeneficiary->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $itemBeneficiary->addColumn(
        'item_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $itemBeneficiary->addColumn(
        'user_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $itemBeneficiary->addColumn(
        'commissions',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $itemBeneficiary->setPrimaryKey(['id']);
    $itemBeneficiary->addForeignKeyConstraint(
        $itemTable,
        ['item_id'],
        ['id'],
        ['onDelete' => 'CASCADE']
    );
}
if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COMMISSION)) {
    $commissions = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COMMISSION);
    $commissions->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $commissions->addColumn(
        'commission',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $commissions->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS)) {
    $saleCommissions = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS);
    $saleCommissions->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $saleCommissions->addColumn('date', Types::DATETIME_MUTABLE);
    $saleCommissions->addColumn('payout_date', Types::DATETIME_MUTABLE);
    $saleCommissions->addColumn(
        'sale_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $saleCommissions->addColumn(
        'user_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $saleCommissions->addColumn(
        'commission',
        Types::DECIMAL,
        ['scale' => 2]
    );
    $saleCommissions->addColumn(
        'status',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $saleCommissions->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SALE)) {
    $saleTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SALE);
    $saleTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $saleTable->addColumn('reference', Types::STRING);
    $saleTable->addColumn('date', Types::DATETIME_MUTABLE);
    $saleTable->addColumn(
        'user_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $saleTable->addColumn('product_type', Types::INTEGER);
    $saleTable->addColumn('product_name', Types::STRING);
    $saleTable->addColumn(
        'product_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $saleTable->addColumn(
        'price',
        Types::DECIMAL,
        ['scale' => 2]
    );
    $saleTable->addColumn(
        'price_without_tax',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $saleTable->addColumn(
        'tax_perc',
        Types::INTEGER,
        ['unsigned' => true, 'notnull' => false]
    );
    $saleTable->addColumn(
        'tax_amount',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $saleTable->addColumn(
        'currency_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $saleTable->addColumn('status', Types::INTEGER);
    $saleTable->addColumn('payment_type', Types::INTEGER);
    $saleTable->addColumn('invoice', Types::INTEGER);
    $saleTable->addColumn(
        'price_without_discount',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $saleTable->addColumn(
        'discount_amount',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $saleTable->setPrimaryKey(['id']);
    $saleTable->addForeignKeyConstraint(
        $currencyTable,
        ['currency_id'],
        ['id'],
        ['onDelete' => 'CASCADE']
    );
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SERVICES)) {
    $servicesTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SERVICES);
    $servicesTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $servicesTable->addColumn('name', Types::STRING);
    $servicesTable->addColumn('description', Types::TEXT);
    $servicesTable->addColumn(
        'price',
        Types::DECIMAL,
        ['scale' => 2]
    );
    $servicesTable->addColumn('duration_days', Types::INTEGER);
    $servicesTable->addColumn('applies_to', Types::INTEGER);
    $servicesTable->addColumn('owner_id', Types::INTEGER);
    $servicesTable->addColumn('visibility', Types::INTEGER);
    $servicesTable->addColumn('video_url', Types::STRING);
    $servicesTable->addColumn('image', Types::STRING);
    $servicesTable->addColumn('service_information', Types::TEXT);
    $servicesTable->addColumn('tax_perc', Types::INTEGER);
    $servicesTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SERVICES_SALE)) {
    $servicesNodeTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SERVICES_SALE);
    $servicesNodeTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $servicesNodeTable->addColumn(
        'service_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $servicesNodeTable->addColumn('reference', Types::STRING);
    $servicesNodeTable->addColumn('currency_id', Types::INTEGER);
    $servicesNodeTable->addColumn(
        'price',
        Types::DECIMAL,
        ['scale' => 2]
    );
    $servicesNodeTable->addColumn(
        'price_without_tax',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'tax_perc',
        Types::INTEGER,
        ['unsigned' => true, 'notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'tax_amount',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $servicesNodeTable->addColumn('node_type', Types::INTEGER);
    $servicesNodeTable->addColumn('node_id', Types::INTEGER);
    $servicesNodeTable->addColumn('buyer_id', Types::INTEGER);
    $servicesNodeTable->addColumn('buy_date', Types::DATETIME_MUTABLE);
    $servicesNodeTable->addColumn(
        'date_start',
        Types::DATETIME_MUTABLE,
        ['notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'date_end',
        Types::DATETIME_MUTABLE
    );
    $servicesNodeTable->addColumn('status', Types::INTEGER);
    $servicesNodeTable->addColumn('payment_type', Types::INTEGER);
    $servicesNodeTable->addColumn('invoice', Types::INTEGER);
    $servicesNodeTable->addColumn(
        'price_without_discount',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'discount_amount',
        Types::DECIMAL,
        ['scale' => 2, 'notnull' => false]
    );
    $servicesNodeTable->setPrimaryKey(['id']);
    $servicesNodeTable->addForeignKeyConstraint(
        $servicesTable,
        ['service_id'],
        ['id'],
        ['onDelete' => 'CASCADE']
    );
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_CULQI)) {
    $culqiTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_CULQI);
    $culqiTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $culqiTable->addColumn('commerce_code', Types::STRING);
    $culqiTable->addColumn('api_key', Types::STRING);
    $culqiTable->addColumn('integration', Types::INTEGER);
    $culqiTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_GLOBAL_CONFIG)) {
    $globalTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_GLOBAL_CONFIG);
    $globalTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $globalTable->addColumn('terms_and_conditions', Types::TEXT);
    $globalTable->addColumn('global_tax_perc', Types::INTEGER);
    $globalTable->addColumn('tax_applies_to', Types::INTEGER);
    $globalTable->addColumn('tax_name', Types::STRING);
    $globalTable->addColumn('seller_name', Types::STRING);
    $globalTable->addColumn('seller_id', Types::STRING);
    $globalTable->addColumn('seller_address', Types::STRING);
    $globalTable->addColumn('seller_email', Types::STRING);
    $globalTable->addColumn('next_number_invoice', Types::INTEGER);
    $globalTable->addColumn('invoice_series', Types::STRING);
    $globalTable->addColumn('sale_email', Types::STRING);
    $globalTable->addColumn('info_email_extra', Types::TEXT);
    $globalTable->setPrimaryKey(['id']);
} else {
    $globalTable = $pluginSchema->getTable(BuyCoursesPlugin::TABLE_GLOBAL_CONFIG);

    if (!$globalTable->hasColumn('info_email_extra')) {
        $globalTable->addColumn('info_email_extra', Types::TEXT);
    }
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_INVOICE)) {
    $invoiceTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_INVOICE);
    $invoiceTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $invoiceTable->addColumn('sale_id', Types::INTEGER);
    $invoiceTable->addColumn('is_service', Types::INTEGER);
    $invoiceTable->addColumn(
        'num_invoice',
        Types::INTEGER,
        ['unsigned' => true, 'notnull' => false]
    );
    $invoiceTable->addColumn(
        'year',
        Types::INTEGER,
        ['unsigned' => true, 'notnull' => false]
    );
    $invoiceTable->addColumn('serie', Types::STRING);
    $invoiceTable->addColumn('date_invoice', Types::DATETIME_MUTABLE);
    $invoiceTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COUPON)) {
    $couponTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COUPON);
    $couponTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $couponTable->addColumn('code', Types::STRING);
    $couponTable->addColumn('discount_type', Types::INTEGER);
    $couponTable->addColumn('discount_amount', Types::INTEGER);
    $couponTable->addColumn('valid_start', Types::DATETIME_MUTABLE);
    $couponTable->addColumn('valid_end', Types::DATETIME_MUTABLE);
    $couponTable->addColumn('delivered', Types::INTEGER);
    $couponTable->addColumn('active', Types::BOOLEAN);
    $couponTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COUPON_ITEM)) {
    $couponItemTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COUPON_ITEM);
    $couponItemTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $couponItemTable->addColumn('coupon_id', Types::INTEGER);
    $couponItemTable->addColumn('product_type', Types::INTEGER);
    $couponItemTable->addColumn('product_id', Types::INTEGER);
    $couponItemTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COUPON_SERVICE)) {
    $couponService = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COUPON_SERVICE);
    $couponService->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $couponService->addColumn('coupon_id', Types::INTEGER);
    $couponService->addColumn('service_id', Types::INTEGER);
    $couponService->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SUBSCRIPTION)) {
    $subscriptionTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SUBSCRIPTION);
    $subscriptionTable->addColumn(
        'product_type',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $subscriptionTable->addColumn(
        'product_id',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $subscriptionTable->addColumn(
        'duration',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $subscriptionTable->addColumn('currency_id', Types::INTEGER);
    $subscriptionTable->addColumn('price', Types::DECIMAL);
    $subscriptionTable->addColumn('tax_perc', Types::INTEGER);
    $subscriptionTable->setPrimaryKey(['product_type', 'product_id', 'duration']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SUBSCRIPTION_SALE)) {
    $subscriptionSaleTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SUBSCRIPTION_SALE);
    $subscriptionSaleTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $subscriptionSaleTable->addColumn('currency_id', Types::INTEGER);
    $subscriptionSaleTable->addColumn('reference', Types::STRING);
    $subscriptionSaleTable->addColumn('date', Types::DATETIME_MUTABLE);
    $subscriptionSaleTable->addColumn('user_id', Types::INTEGER);
    $subscriptionSaleTable->addColumn('product_type', Types::INTEGER);
    $subscriptionSaleTable->addColumn('product_name', Types::STRING);
    $subscriptionSaleTable->addColumn('product_id', Types::INTEGER);
    $subscriptionSaleTable->addColumn('price', Types::DECIMAL);
    $subscriptionSaleTable->addColumn('price_without_tax', Types::DECIMAL, ['notnull' => false]);
    $subscriptionSaleTable->addColumn('tax_perc', Types::INTEGER, ['notnull' => false]);
    $subscriptionSaleTable->addColumn('tax_amount', Types::DECIMAL, ['notnull' => false]);
    $subscriptionSaleTable->addColumn('status', Types::INTEGER);
    $subscriptionSaleTable->addColumn('payment_type', Types::INTEGER);
    $subscriptionSaleTable->addColumn('invoice', Types::INTEGER);
    $subscriptionSaleTable->addColumn('price_without_discount', Types::DECIMAL);
    $subscriptionSaleTable->addColumn('discount_amount', Types::DECIMAL);
    $subscriptionSaleTable->addColumn('subscription_end', Types::DATETIME_MUTABLE);
    $subscriptionSaleTable->addColumn('expired', Types::BOOLEAN);
    $subscriptionSaleTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SUBSCRIPTION_PERIOD)) {
    $subscriptionPeriodTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SUBSCRIPTION_PERIOD);
    $subscriptionPeriodTable->addColumn(
        'duration',
        Types::INTEGER,
        ['unsigned' => true]
    );
    $subscriptionPeriodTable->addColumn('name', Types::STRING);
    $subscriptionPeriodTable->setPrimaryKey(['duration']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COUPON_SALE)) {
    $couponSaleTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COUPON_SALE);
    $couponSaleTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $couponSaleTable->addColumn('coupon_id', Types::INTEGER);
    $couponSaleTable->addColumn('sale_id', Types::INTEGER);
    $couponSaleTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COUPON_SERVICE_SALE)) {
    $couponSaleTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COUPON_SERVICE_SALE);
    $couponSaleTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $couponSaleTable->addColumn('coupon_id', Types::INTEGER);
    $couponSaleTable->addColumn('service_sale_id', Types::INTEGER);
    $couponSaleTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_STRIPE)) {
    $stripeTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_STRIPE);
    $stripeTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $stripeTable->addColumn('account_id', Types::STRING);
    $stripeTable->addColumn('secret_key', Types::STRING);
    $stripeTable->addColumn('endpoint_secret', Types::STRING);
    $stripeTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_TPV_CECABANK)) {
    $tpvCecabankTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_TPV_CECABANK);
    $tpvCecabankTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $tpvCecabankTable->addColumn('crypto_key', Types::STRING);
    $tpvCecabankTable->addColumn('merchant_id', Types::STRING);
    $tpvCecabankTable->addColumn('acquirer_bin', Types::STRING);
    $tpvCecabankTable->addColumn('terminal_id', Types::STRING);
    $tpvCecabankTable->addColumn('cypher', Types::STRING);
    $tpvCecabankTable->addColumn('exponent', Types::STRING);
    $tpvCecabankTable->addColumn('supported_payment', Types::STRING);
    $tpvCecabankTable->addColumn('url', Types::STRING);
    $tpvCecabankTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_COUPON_SUBSCRIPTION_SALE)) {
    $couponSubscriptionSaleTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_COUPON_SUBSCRIPTION_SALE);
    $couponSubscriptionSaleTable->addColumn(
        'id',
        Types::INTEGER,
        ['autoincrement' => true, 'unsigned' => true]
    );
    $couponSubscriptionSaleTable->addColumn('coupon_id', Types::INTEGER);
    $couponSubscriptionSaleTable->addColumn('sale_id', Types::INTEGER);
    $couponSubscriptionSaleTable->setPrimaryKey(['id']);
}

$queries = $pluginSchema->toSql($platform);

foreach ($queries as $query) {
    Database::query($query);
}

// Insert data
$paypalTable = Database::get_main_table(BuyCoursesPlugin::TABLE_PAYPAL);
$currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
$itemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);
$saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
$commissionTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COMMISSION);
$extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
$culqiTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CULQI);
$globalTable = Database::get_main_table(BuyCoursesPlugin::TABLE_GLOBAL_CONFIG);
$tpvRedsysTable = Database::get_main_table(BuyCoursesPlugin::TABLE_TPV_REDSYS);
$stripeTable = Database::get_main_table(BuyCoursesPlugin::TABLE_STRIPE);

$paypalExtraField = Database::select(
    '*',
    $extraFieldTable,
    [
        'where' => ['variable = ?' => 'paypal'],
    ],
    'first'
);

if (!$paypalExtraField) {
    Database::insert(
        $extraFieldTable,
        [
            'item_type' => 1,
            'value_type' => 1,
            'variable' => 'paypal',
            'display_text' => 'Paypal',
            'default_value' => '',
            'field_order' => 0,
            'visible_to_self' => 1,
            'changeable' => 1,
            'filter' => 0,
            'created_at' => api_get_utc_datetime(),
        ]
    );
}

Database::insert(
    $paypalTable,
    [
        'username' => '',
        'password' => '',
        'signature' => '',
        'sandbox' => true,
    ]
);

Database::insert(
    $tpvRedsysTable,
    [
        'url_redsys' => 'https://sis.redsys.es/sis/realizarPago',
        'url_redsys_sandbox' => 'https://sis-t.redsys.es:25443/sis/realizarPago',
    ]
);

Database::insert(
    $culqiTable,
    [
        'commerce_code' => '',
        'api_key' => '',
        'integration' => 1,
    ]
);

Database::insert(
    $globalTable,
    [
        'terms_and_conditions' => '',
    ]
);

Database::insert(
    $commissionTable,
    [
        'commission' => 0,
    ]
);

Database::insert(
    $stripeTable,
    [
        'account_id' => '',
        'secret_key' => '',
        'endpoint_secret' => '',
    ]
);

$currencies = [
    ['AD', 'Andorra', 'EUR', 'AND', 0],
    ['AE', 'United Arab Emirates', 'AED', 'ARE', 0],
    ['AF', 'Afghanistan', 'AFN', 'AFG', 0],
    ['AG', 'Antigua and Barbuda', 'XCD', 'ATG', 0],
    ['AI', 'Anguilla', 'XCD', 'AIA', 0],
    ['AL', 'Albania', 'ALL', 'ALB', 0],
    ['AM', 'Armenia', 'AMD', 'ARM', 0],
    ['AO', 'Angola', 'AOA', 'AGO', 0],
    ['AR', 'Argentina', 'ARS', 'ARG', 0],
    ['AS', 'American Samoa', 'USD', 'ASM', 0],
    ['AT', 'Austria', 'EUR', 'AUT', 0],
    ['AU', 'Australia', 'AUD', 'AUS', 0],
    ['AW', 'Aruba', 'AWG', 'ABW', 0],
    ['AX', '&Aring;land', 'EUR', 'ALA', 0],
    ['AZ', 'Azerbaijan', 'AZN', 'AZE', 0],
    ['BA', 'Bosnia and Herzegovina', 'BAM', 'BIH', 0],
    ['BB', 'Barbados', 'BBD', 'BRB', 0],
    ['BD', 'Bangladesh', 'BDT', 'BGD', 0],
    ['BE', 'Belgium', 'EUR', 'BEL', 1],
    ['BF', 'Burkina Faso', 'XOF', 'BFA', 0],
    ['BG', 'Bulgaria', 'BGN', 'BGR', 0],
    ['BH', 'Bahrain', 'BHD', 'BHR', 0],
    ['BI', 'Burundi', 'BIF', 'BDI', 0],
    ['BJ', 'Benin', 'XOF', 'BEN', 0],
    ['BL', 'Saint Barth&eacute;lemy', 'EUR', 'BLM', 0],
    ['BM', 'Bermuda', 'BMD', 'BMU', 0],
    ['BN', 'Brunei', 'BND', 'BRN', 0],
    ['BO', 'Bolivia', 'BOB', 'BOL', 0],
    ['BQ', 'Bonaire', 'USD', 'BES', 0],
    ['BR', 'Brazil', 'BRL', 'BRA', 0],
    ['BS', 'Bahamas', 'BSD', 'BHS', 0],
    ['BT', 'Bhutan', 'BTN', 'BTN', 0],
    ['BV', 'Bouvet Island', 'NOK', 'BVT', 0],
    ['BW', 'Botswana', 'BWP', 'BWA', 0],
    ['BY', 'Belarus', 'BYR', 'BLR', 0],
    ['BZ', 'Belize', 'BZD', 'BLZ', 0],
    ['CA', 'Canada', 'CAD', 'CAN', 0],
    ['CC', 'Cocos [Keeling] Islands', 'AUD', 'CCK', 0],
    ['CD', 'Congo', 'CDF', 'COD', 0],
    ['CF', 'Central African Republic', 'XAF', 'CAF', 0],
    ['CG', 'Republic of the Congo', 'XAF', 'COG', 0],
    ['CH', 'Switzerland', 'CHF', 'CHE', 0],
    ['CI', 'Ivory Coast', 'XOF', 'CIV', 0],
    ['CK', 'Cook Islands', 'NZD', 'COK', 0],
    ['CL', 'Chile', 'CLP', 'CHL', 0],
    ['CM', 'Cameroon', 'XAF', 'CMR', 0],
    ['CN', 'China', 'CNY', 'CHN', 0],
    ['CO', 'Colombia', 'COP', 'COL', 0],
    ['CR', 'Costa Rica', 'CRC', 'CRI', 0],
    ['CU', 'Cuba', 'CUP', 'CUB', 0],
    ['CV', 'Cape Verde', 'CVE', 'CPV', 0],
    ['CW', 'Curacao', 'ANG', 'CUW', 0],
    ['CX', 'Christmas Island', 'AUD', 'CXR', 0],
    ['CY', 'Cyprus', 'EUR', 'CYP', 0],
    ['CZ', 'Czechia', 'CZK', 'CZE', 0],
    ['DE', 'Germany', 'EUR', 'DEU', 0],
    ['DJ', 'Djibouti', 'DJF', 'DJI', 0],
    ['DK', 'Denmark', 'DKK', 'DNK', 0],
    ['DM', 'Dominica', 'XCD', 'DMA', 0],
    ['DO', 'Dominican Republic', 'DOP', 'DOM', 0],
    ['DZ', 'Algeria', 'DZD', 'DZA', 0],
    ['EC', 'Ecuador', 'USD', 'ECU', 0],
    ['EE', 'Estonia', 'EUR', 'EST', 0],
    ['EG', 'Egypt', 'EGP', 'EGY', 0],
    ['EH', 'Western Sahara', 'MAD', 'ESH', 0],
    ['ER', 'Eritrea', 'ERN', 'ERI', 0],
    ['ES', 'Spain', 'EUR', 'ESP', 0],
    ['ET', 'Ethiopia', 'ETB', 'ETH', 0],
    ['FI', 'Finland', 'EUR', 'FIN', 0],
    ['FJ', 'Fiji', 'FJD', 'FJI', 0],
    ['FK', 'Falkland Islands', 'FKP', 'FLK', 0],
    ['FM', 'Micronesia', 'USD', 'FSM', 0],
    ['FO', 'Faroe Islands', 'DKK', 'FRO', 0],
    ['FR', 'France', 'EUR', 'FRA', 0],
    ['GA', 'Gabon', 'XAF', 'GAB', 0],
    ['GB', 'United Kingdom', 'GBP', 'GBR', 0],
    ['GD', 'Grenada', 'XCD', 'GRD', 0],
    ['GE', 'Georgia', 'GEL', 'GEO', 0],
    ['GF', 'French Guiana', 'EUR', 'GUF', 0],
    ['GG', 'Guernsey', 'GBP', 'GGY', 0],
    ['GH', 'Ghana', 'GHS', 'GHA', 0],
    ['GI', 'Gibraltar', 'GIP', 'GIB', 0],
    ['GL', 'Greenland', 'DKK', 'GRL', 0],
    ['GM', 'Gambia', 'GMD', 'GMB', 0],
    ['GN', 'Guinea', 'GNF', 'GIN', 0],
    ['GP', 'Guadeloupe', 'EUR', 'GLP', 0],
    ['GQ', 'Equatorial Guinea', 'XAF', 'GNQ', 0],
    ['GR', 'Greece', 'EUR', 'GRC', 0],
    ['GS', 'South Georgia and the South Sandwich Islands', 'GBP', 'SGS', 0],
    ['GT', 'Guatemala', 'GTQ', 'GTM', 0],
    ['GU', 'Guam', 'USD', 'GUM', 0],
    ['GW', 'Guinea-Bissau', 'XOF', 'GNB', 0],
    ['GY', 'Guyana', 'GYD', 'GUY', 0],
    ['HK', 'Hong Kong', 'HKD', 'HKG', 0],
    ['HM', 'Heard Island and McDonald Islands', 'AUD', 'HMD', 0],
    ['HN', 'Honduras', 'HNL', 'HND', 0],
    ['HR', 'Croatia', 'HRK', 'HRV', 0],
    ['HT', 'Haiti', 'HTG', 'HTI', 0],
    ['HU', 'Hungary', 'HUF', 'HUN', 0],
    ['ID', 'Indonesia', 'IDR', 'IDN', 0],
    ['IE', 'Ireland', 'EUR', 'IRL', 0],
    ['IL', 'Israel', 'ILS', 'ISR', 0],
    ['IM', 'Isle of Man', 'GBP', 'IMN', 0],
    ['IN', 'India', 'INR', 'IND', 0],
    ['IO', 'British Indian Ocean Territory', 'USD', 'IOT', 0],
    ['IQ', 'Iraq', 'IQD', 'IRQ', 0],
    ['IR', 'Iran', 'IRR', 'IRN', 0],
    ['IS', 'Iceland', 'ISK', 'ISL', 0],
    ['IT', 'Italy', 'EUR', 'ITA', 0],
    ['JE', 'Jersey', 'GBP', 'JEY', 0],
    ['JM', 'Jamaica', 'JMD', 'JAM', 0],
    ['JO', 'Jordan', 'JOD', 'JOR', 0],
    ['JP', 'Japan', 'JPY', 'JPN', 0],
    ['KE', 'Kenya', 'KES', 'KEN', 0],
    ['KG', 'Kyrgyzstan', 'KGS', 'KGZ', 0],
    ['KH', 'Cambodia', 'KHR', 'KHM', 0],
    ['KI', 'Kiribati', 'AUD', 'KIR', 0],
    ['KM', 'Comoros', 'KMF', 'COM', 0],
    ['KN', 'Saint Kitts and Nevis', 'XCD', 'KNA', 0],
    ['KP', 'North Korea', 'KPW', 'PRK', 0],
    ['KR', 'South Korea', 'KRW', 'KOR', 0],
    ['KW', 'Kuwait', 'KWD', 'KWT', 0],
    ['KY', 'Cayman Islands', 'KYD', 'CYM', 0],
    ['KZ', 'Kazakhstan', 'KZT', 'KAZ', 0],
    ['LA', 'Laos', 'LAK', 'LAO', 0],
    ['LB', 'Lebanon', 'LBP', 'LBN', 0],
    ['LC', 'Saint Lucia', 'XCD', 'LCA', 0],
    ['LI', 'Liechtenstein', 'CHF', 'LIE', 0],
    ['LK', 'Sri Lanka', 'LKR', 'LKA', 0],
    ['LR', 'Liberia', 'LRD', 'LBR', 0],
    ['LS', 'Lesotho', 'LSL', 'LSO', 0],
    ['LT', 'Lithuania', 'LTL', 'LTU', 0],
    ['LU', 'Luxembourg', 'EUR', 'LUX', 0],
    ['LV', 'Latvia', 'LVL', 'LVA', 0],
    ['LY', 'Libya', 'LYD', 'LBY', 0],
    ['MA', 'Morocco', 'MAD', 'MAR', 0],
    ['MC', 'Monaco', 'EUR', 'MCO', 0],
    ['MD', 'Moldova', 'MDL', 'MDA', 0],
    ['ME', 'Montenegro', 'EUR', 'MNE', 0],
    ['MF', 'Saint Martin', 'EUR', 'MAF', 0],
    ['MG', 'Madagascar', 'MGA', 'MDG', 0],
    ['MH', 'Marshall Islands', 'USD', 'MHL', 0],
    ['MK', 'Macedonia', 'MKD', 'MKD', 0],
    ['ML', 'Mali', 'XOF', 'MLI', 0],
    ['MM', 'Myanmar [Burma]', 'MMK', 'MMR', 0],
    ['MN', 'Mongolia', 'MNT', 'MNG', 0],
    ['MO', 'Macao', 'MOP', 'MAC', 0],
    ['MP', 'Northern Mariana Islands', 'USD', 'MNP', 0],
    ['MQ', 'Martinique', 'EUR', 'MTQ', 0],
    ['MR', 'Mauritania', 'MRO', 'MRT', 0],
    ['MS', 'Montserrat', 'XCD', 'MSR', 0],
    ['MT', 'Malta', 'EUR', 'MLT', 0],
    ['MU', 'Mauritius', 'MUR', 'MUS', 0],
    ['MV', 'Maldives', 'MVR', 'MDV', 0],
    ['MW', 'Malawi', 'MWK', 'MWI', 0],
    ['MX', 'Mexico', 'MXN', 'MEX', 0],
    ['MY', 'Malaysia', 'MYR', 'MYS', 0],
    ['MZ', 'Mozambique', 'MZN', 'MOZ', 0],
    ['NA', 'Namibia', 'NAD', 'NAM', 0],
    ['NC', 'New Caledonia', 'XPF', 'NCL', 0],
    ['NE', 'Niger', 'XOF', 'NER', 0],
    ['NF', 'Norfolk Island', 'AUD', 'NFK', 0],
    ['NG', 'Nigeria', 'NGN', 'NGA', 0],
    ['NI', 'Nicaragua', 'NIO', 'NIC', 0],
    ['NL', 'Netherlands', 'EUR', 'NLD', 0],
    ['NO', 'Norway', 'NOK', 'NOR', 0],
    ['NP', 'Nepal', 'NPR', 'NPL', 0],
    ['NR', 'Nauru', 'AUD', 'NRU', 0],
    ['NU', 'Niue', 'NZD', 'NIU', 0],
    ['NZ', 'New Zealand', 'NZD', 'NZL', 0],
    ['OM', 'Oman', 'OMR', 'OMN', 0],
    ['PA', 'Panama', 'PAB', 'PAN', 0],
    ['PE', 'Peru', 'PEN', 'PER', 0],
    ['PF', 'French Polynesia', 'XPF', 'PYF', 0],
    ['PG', 'Papua New Guinea', 'PGK', 'PNG', 0],
    ['PH', 'Philippines', 'PHP', 'PHL', 0],
    ['PK', 'Pakistan', 'PKR', 'PAK', 0],
    ['PL', 'Poland', 'PLN', 'POL', 0],
    ['PM', 'Saint Pierre and Miquelon', 'EUR', 'SPM', 0],
    ['PN', 'Pitcairn Islands', 'NZD', 'PCN', 0],
    ['PR', 'Puerto Rico', 'USD', 'PRI', 0],
    ['PS', 'Palestine', 'ILS', 'PSE', 0],
    ['PT', 'Portugal', 'EUR', 'PRT', 0],
    ['PW', 'Palau', 'USD', 'PLW', 0],
    ['PY', 'Paraguay', 'PYG', 'PRY', 0],
    ['QA', 'Qatar', 'QAR', 'QAT', 0],
    ['RE', 'R&eacute;union', 'EUR', 'REU', 0],
    ['RO', 'Romania', 'RON', 'ROU', 0],
    ['RS', 'Serbia', 'RSD', 'SRB', 0],
    ['RU', 'Russia', 'RUB', 'RUS', 0],
    ['RW', 'Rwanda', 'RWF', 'RWA', 0],
    ['SA', 'Saudi Arabia', 'SAR', 'SAU', 0],
    ['SB', 'Solomon Islands', 'SBD', 'SLB', 0],
    ['SC', 'Seychelles', 'SCR', 'SYC', 0],
    ['SD', 'Sudan', 'SDG', 'SDN', 0],
    ['SE', 'Sweden', 'SEK', 'SWE', 0],
    ['SG', 'Singapore', 'SGD', 'SGP', 0],
    ['SH', 'Saint Helena', 'SHP', 'SHN', 0],
    ['SI', 'Slovenia', 'EUR', 'SVN', 0],
    ['SJ', 'Svalbard and Jan Mayen', 'NOK', 'SJM', 0],
    ['SK', 'Slovakia', 'EUR', 'SVK', 0],
    ['SL', 'Sierra Leone', 'SLL', 'SLE', 0],
    ['SM', 'San Marino', 'EUR', 'SMR', 0],
    ['SN', 'Senegal', 'XOF', 'SEN', 0],
    ['SO', 'Somalia', 'SOS', 'SOM', 0],
    ['SR', 'Suriname', 'SRD', 'SUR', 0],
    ['SS', 'South Sudan', 'SSP', 'SSD', 0],
    ['ST', 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'STD', 'STP', 0],
    ['SV', 'El Salvador', 'USD', 'SLV', 0],
    ['SX', 'Sint Maarten', 'ANG', 'SXM', 0],
    ['SY', 'Syria', 'SYP', 'SYR', 0],
    ['SZ', 'Swaziland', 'SZL', 'SWZ', 0],
    ['TC', 'Turks and Caicos Islands', 'USD', 'TCA', 0],
    ['TD', 'Chad', 'XAF', 'TCD', 0],
    ['TF', 'French Southern Territories', 'EUR', 'ATF', 0],
    ['TG', 'Togo', 'XOF', 'TGO', 0],
    ['TH', 'Thailand', 'THB', 'THA', 0],
    ['TJ', 'Tajikistan', 'TJS', 'TJK', 0],
    ['TK', 'Tokelau', 'NZD', 'TKL', 0],
    ['TL', 'East Timor', 'USD', 'TLS', 0],
    ['TM', 'Turkmenistan', 'TMT', 'TKM', 0],
    ['TN', 'Tunisia', 'TND', 'TUN', 0],
    ['TO', 'Tonga', 'TOP', 'TON', 0],
    ['TR', 'Turkey', 'TRY', 'TUR', 0],
    ['TT', 'Trinidad and Tobago', 'TTD', 'TTO', 0],
    ['TV', 'Tuvalu', 'AUD', 'TUV', 0],
    ['TW', 'Taiwan', 'TWD', 'TWN', 0],
    ['TZ', 'Tanzania', 'TZS', 'TZA', 0],
    ['UA', 'Ukraine', 'UAH', 'UKR', 0],
    ['UG', 'Uganda', 'UGX', 'UGA', 0],
    ['UM', 'U.S. Minor Outlying Islands', 'USD', 'UMI', 0],
    ['US', 'United States', 'USD', 'USA', 1],
    ['UY', 'Uruguay', 'UYU', 'URY', 0],
    ['UZ', 'Uzbekistan', 'UZS', 'UZB', 0],
    ['VA', 'Vatican City', 'EUR', 'VAT', 0],
    ['VC', 'Saint Vincent and the Grenadines', 'XCD', 'VCT', 0],
    ['VE', 'Venezuela', 'VEF', 'VEN', 0],
    ['VG', 'British Virgin Islands', 'USD', 'VGB', 0],
    ['VI', 'U.S. Virgin Islands', 'USD', 'VIR', 0],
    ['VN', 'Vietnam', 'VND', 'VNM', 0],
    ['VU', 'Vanuatu', 'VUV', 'VUT', 0],
    ['WF', 'Wallis and Futuna', 'XPF', 'WLF', 0],
    ['WS', 'Samoa', 'WST', 'WSM', 0],
    ['XB', 'World (cryptocurrency)', 'BTC', 'BTC', 0],
    ['XD', 'World (cryptocurrency)', 'DOGE', 'DOGE', 0],
    ['XK', 'Kosovo', 'EUR', 'XKX', 0],
    ['YE', 'Yemen', 'YER', 'YEM', 0],
    ['YT', 'Mayotte', 'EUR', 'MYT', 0],
    ['ZA', 'South Africa', 'ZAR', 'ZAF', 0],
    ['ZM', 'Zambia', 'ZMK', 'ZMB', 0],
    ['ZW', 'Zimbabwe', 'ZWL', 'ZWE', 0],
];

foreach ($currencies as $currency) {
    $value = Database::select(
        '*',
        $currencyTable,
        [
            'where' => ['country_code = ?' => $currency[0]],
        ],
        'first'
    );

    if (!empty($value)) {
        continue;
    }

    Database::insert(
        $currencyTable,
        [
            'country_code' => $currency[0],
            'country_name' => $currency[1],
            'iso_code' => $currency[2],
            'status' => $currency[4],
        ]
    );
}

$fieldlabel = 'buycourses_company';
$fieldtype = '1';
$fieldtitle = BuyCoursesPlugin::get_lang('Company');
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'buycourses_vat';
$fieldtype = '1';
$fieldtitle = BuyCoursesPlugin::get_lang('VAT');
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

$fieldlabel = 'buycourses_address';
$fieldtype = '1';
$fieldtitle = BuyCoursesPlugin::get_lang('Address');
$fielddefault = '';
$field_id = UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);
