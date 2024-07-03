<?php

/* For license terms, see /license.txt */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;

/**
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
$sm = $connection->getSchemaManager();

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
    "*",
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
            'extra_field_type' => 1,
            'field_type' => 1,
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
    ['AD', 'Andorra', 'EUR', 'AND'],
    ['AE', 'United Arab Emirates', 'AED', 'ARE'],
    ['AF', 'Afghanistan', 'AFN', 'AFG'],
    ['AG', 'Antigua and Barbuda', 'XCD', 'ATG'],
    ['AI', 'Anguilla', 'XCD', 'AIA'],
    ['AL', 'Albania', 'ALL', 'ALB'],
    ['AM', 'Armenia', 'AMD', 'ARM'],
    ['AO', 'Angola', 'AOA', 'AGO'],
    ['AR', 'Argentina', 'ARS', 'ARG'],
    ['AS', 'American Samoa', 'USD', 'ASM'],
    ['AT', 'Austria', 'EUR', 'AUT'],
    ['AU', 'Australia', 'AUD', 'AUS'],
    ['AW', 'Aruba', 'AWG', 'ABW'],
    ['AX', '&Aring;land', 'EUR', 'ALA'],
    ['AZ', 'Azerbaijan', 'AZN', 'AZE'],
    ['BA', 'Bosnia and Herzegovina', 'BAM', 'BIH'],
    ['BB', 'Barbados', 'BBD', 'BRB'],
    ['BD', 'Bangladesh', 'BDT', 'BGD'],
    ['BE', 'Belgium', 'EUR', 'BEL'],
    ['BF', 'Burkina Faso', 'XOF', 'BFA'],
    ['BG', 'Bulgaria', 'BGN', 'BGR'],
    ['BH', 'Bahrain', 'BHD', 'BHR'],
    ['BI', 'Burundi', 'BIF', 'BDI'],
    ['BJ', 'Benin', 'XOF', 'BEN'],
    ['BL', 'Saint Barth&eacute;lemy', 'EUR', 'BLM'],
    ['BM', 'Bermuda', 'BMD', 'BMU'],
    ['BN', 'Brunei', 'BND', 'BRN'],
    ['BO', 'Bolivia', 'BOB', 'BOL'],
    ['BQ', 'Bonaire', 'USD', 'BES'],
    ['BR', 'Brazil', 'BRL', 'BRA'],
    ['BS', 'Bahamas', 'BSD', 'BHS'],
    ['BT', 'Bhutan', 'BTN', 'BTN'],
    ['BV', 'Bouvet Island', 'NOK', 'BVT'],
    ['BW', 'Botswana', 'BWP', 'BWA'],
    ['BY', 'Belarus', 'BYR', 'BLR'],
    ['BZ', 'Belize', 'BZD', 'BLZ'],
    ['CA', 'Canada', 'CAD', 'CAN'],
    ['CC', 'Cocos [Keeling] Islands', 'AUD', 'CCK'],
    ['CD', 'Congo', 'CDF', 'COD'],
    ['CF', 'Central African Republic', 'XAF', 'CAF'],
    ['CG', 'Republic of the Congo', 'XAF', 'COG'],
    ['CH', 'Switzerland', 'CHF', 'CHE'],
    ['CI', 'Ivory Coast', 'XOF', 'CIV'],
    ['CK', 'Cook Islands', 'NZD', 'COK'],
    ['CL', 'Chile', 'CLP', 'CHL'],
    ['CM', 'Cameroon', 'XAF', 'CMR'],
    ['CN', 'China', 'CNY', 'CHN'],
    ['CO', 'Colombia', 'COP', 'COL'],
    ['CR', 'Costa Rica', 'CRC', 'CRI'],
    ['CU', 'Cuba', 'CUP', 'CUB'],
    ['CV', 'Cape Verde', 'CVE', 'CPV'],
    ['CW', 'Curacao', 'ANG', 'CUW'],
    ['CX', 'Christmas Island', 'AUD', 'CXR'],
    ['CY', 'Cyprus', 'EUR', 'CYP'],
    ['CZ', 'Czechia', 'CZK', 'CZE'],
    ['DE', 'Germany', 'EUR', 'DEU'],
    ['DJ', 'Djibouti', 'DJF', 'DJI'],
    ['DK', 'Denmark', 'DKK', 'DNK'],
    ['DM', 'Dominica', 'XCD', 'DMA'],
    ['DO', 'Dominican Republic', 'DOP', 'DOM'],
    ['DZ', 'Algeria', 'DZD', 'DZA'],
    ['EC', 'Ecuador', 'USD', 'ECU'],
    ['EE', 'Estonia', 'EUR', 'EST'],
    ['EG', 'Egypt', 'EGP', 'EGY'],
    ['EH', 'Western Sahara', 'MAD', 'ESH'],
    ['ER', 'Eritrea', 'ERN', 'ERI'],
    ['ES', 'Spain', 'EUR', 'ESP'],
    ['ET', 'Ethiopia', 'ETB', 'ETH'],
    ['FI', 'Finland', 'EUR', 'FIN'],
    ['FJ', 'Fiji', 'FJD', 'FJI'],
    ['FK', 'Falkland Islands', 'FKP', 'FLK'],
    ['FM', 'Micronesia', 'USD', 'FSM'],
    ['FO', 'Faroe Islands', 'DKK', 'FRO'],
    ['FR', 'France', 'EUR', 'FRA'],
    ['GA', 'Gabon', 'XAF', 'GAB'],
    ['GB', 'United Kingdom', 'GBP', 'GBR'],
    ['GD', 'Grenada', 'XCD', 'GRD'],
    ['GE', 'Georgia', 'GEL', 'GEO'],
    ['GF', 'French Guiana', 'EUR', 'GUF'],
    ['GG', 'Guernsey', 'GBP', 'GGY'],
    ['GH', 'Ghana', 'GHS', 'GHA'],
    ['GI', 'Gibraltar', 'GIP', 'GIB'],
    ['GL', 'Greenland', 'DKK', 'GRL'],
    ['GM', 'Gambia', 'GMD', 'GMB'],
    ['GN', 'Guinea', 'GNF', 'GIN'],
    ['GP', 'Guadeloupe', 'EUR', 'GLP'],
    ['GQ', 'Equatorial Guinea', 'XAF', 'GNQ'],
    ['GR', 'Greece', 'EUR', 'GRC'],
    ['GS', 'South Georgia and the South Sandwich Islands', 'GBP', 'SGS'],
    ['GT', 'Guatemala', 'GTQ', 'GTM'],
    ['GU', 'Guam', 'USD', 'GUM'],
    ['GW', 'Guinea-Bissau', 'XOF', 'GNB'],
    ['GY', 'Guyana', 'GYD', 'GUY'],
    ['HK', 'Hong Kong', 'HKD', 'HKG'],
    ['HM', 'Heard Island and McDonald Islands', 'AUD', 'HMD'],
    ['HN', 'Honduras', 'HNL', 'HND'],
    ['HR', 'Croatia', 'HRK', 'HRV'],
    ['HT', 'Haiti', 'HTG', 'HTI'],
    ['HU', 'Hungary', 'HUF', 'HUN'],
    ['ID', 'Indonesia', 'IDR', 'IDN'],
    ['IE', 'Ireland', 'EUR', 'IRL'],
    ['IL', 'Israel', 'ILS', 'ISR'],
    ['IM', 'Isle of Man', 'GBP', 'IMN'],
    ['IN', 'India', 'INR', 'IND'],
    ['IO', 'British Indian Ocean Territory', 'USD', 'IOT'],
    ['IQ', 'Iraq', 'IQD', 'IRQ'],
    ['IR', 'Iran', 'IRR', 'IRN'],
    ['IS', 'Iceland', 'ISK', 'ISL'],
    ['IT', 'Italy', 'EUR', 'ITA'],
    ['JE', 'Jersey', 'GBP', 'JEY'],
    ['JM', 'Jamaica', 'JMD', 'JAM'],
    ['JO', 'Jordan', 'JOD', 'JOR'],
    ['JP', 'Japan', 'JPY', 'JPN'],
    ['KE', 'Kenya', 'KES', 'KEN'],
    ['KG', 'Kyrgyzstan', 'KGS', 'KGZ'],
    ['KH', 'Cambodia', 'KHR', 'KHM'],
    ['KI', 'Kiribati', 'AUD', 'KIR'],
    ['KM', 'Comoros', 'KMF', 'COM'],
    ['KN', 'Saint Kitts and Nevis', 'XCD', 'KNA'],
    ['KP', 'North Korea', 'KPW', 'PRK'],
    ['KR', 'South Korea', 'KRW', 'KOR'],
    ['KW', 'Kuwait', 'KWD', 'KWT'],
    ['KY', 'Cayman Islands', 'KYD', 'CYM'],
    ['KZ', 'Kazakhstan', 'KZT', 'KAZ'],
    ['LA', 'Laos', 'LAK', 'LAO'],
    ['LB', 'Lebanon', 'LBP', 'LBN'],
    ['LC', 'Saint Lucia', 'XCD', 'LCA'],
    ['LI', 'Liechtenstein', 'CHF', 'LIE'],
    ['LK', 'Sri Lanka', 'LKR', 'LKA'],
    ['LR', 'Liberia', 'LRD', 'LBR'],
    ['LS', 'Lesotho', 'LSL', 'LSO'],
    ['LT', 'Lithuania', 'LTL', 'LTU'],
    ['LU', 'Luxembourg', 'EUR', 'LUX'],
    ['LV', 'Latvia', 'LVL', 'LVA'],
    ['LY', 'Libya', 'LYD', 'LBY'],
    ['MA', 'Morocco', 'MAD', 'MAR'],
    ['MC', 'Monaco', 'EUR', 'MCO'],
    ['MD', 'Moldova', 'MDL', 'MDA'],
    ['ME', 'Montenegro', 'EUR', 'MNE'],
    ['MF', 'Saint Martin', 'EUR', 'MAF'],
    ['MG', 'Madagascar', 'MGA', 'MDG'],
    ['MH', 'Marshall Islands', 'USD', 'MHL'],
    ['MK', 'Macedonia', 'MKD', 'MKD'],
    ['ML', 'Mali', 'XOF', 'MLI'],
    ['MM', 'Myanmar [Burma]', 'MMK', 'MMR'],
    ['MN', 'Mongolia', 'MNT', 'MNG'],
    ['MO', 'Macao', 'MOP', 'MAC'],
    ['MP', 'Northern Mariana Islands', 'USD', 'MNP'],
    ['MQ', 'Martinique', 'EUR', 'MTQ'],
    ['MR', 'Mauritania', 'MRO', 'MRT'],
    ['MS', 'Montserrat', 'XCD', 'MSR'],
    ['MT', 'Malta', 'EUR', 'MLT'],
    ['MU', 'Mauritius', 'MUR', 'MUS'],
    ['MV', 'Maldives', 'MVR', 'MDV'],
    ['MW', 'Malawi', 'MWK', 'MWI'],
    ['MX', 'Mexico', 'MXN', 'MEX'],
    ['MY', 'Malaysia', 'MYR', 'MYS'],
    ['MZ', 'Mozambique', 'MZN', 'MOZ'],
    ['NA', 'Namibia', 'NAD', 'NAM'],
    ['NC', 'New Caledonia', 'XPF', 'NCL'],
    ['NE', 'Niger', 'XOF', 'NER'],
    ['NF', 'Norfolk Island', 'AUD', 'NFK'],
    ['NG', 'Nigeria', 'NGN', 'NGA'],
    ['NI', 'Nicaragua', 'NIO', 'NIC'],
    ['NL', 'Netherlands', 'EUR', 'NLD'],
    ['NO', 'Norway', 'NOK', 'NOR'],
    ['NP', 'Nepal', 'NPR', 'NPL'],
    ['NR', 'Nauru', 'AUD', 'NRU'],
    ['NU', 'Niue', 'NZD', 'NIU'],
    ['NZ', 'New Zealand', 'NZD', 'NZL'],
    ['OM', 'Oman', 'OMR', 'OMN'],
    ['PA', 'Panama', 'PAB', 'PAN'],
    ['PE', 'Peru', 'PEN', 'PER'],
    ['PF', 'French Polynesia', 'XPF', 'PYF'],
    ['PG', 'Papua New Guinea', 'PGK', 'PNG'],
    ['PH', 'Philippines', 'PHP', 'PHL'],
    ['PK', 'Pakistan', 'PKR', 'PAK'],
    ['PL', 'Poland', 'PLN', 'POL'],
    ['PM', 'Saint Pierre and Miquelon', 'EUR', 'SPM'],
    ['PN', 'Pitcairn Islands', 'NZD', 'PCN'],
    ['PR', 'Puerto Rico', 'USD', 'PRI'],
    ['PS', 'Palestine', 'ILS', 'PSE'],
    ['PT', 'Portugal', 'EUR', 'PRT'],
    ['PW', 'Palau', 'USD', 'PLW'],
    ['PY', 'Paraguay', 'PYG', 'PRY'],
    ['QA', 'Qatar', 'QAR', 'QAT'],
    ['RE', 'R&eacute;union', 'EUR', 'REU'],
    ['RO', 'Romania', 'RON', 'ROU'],
    ['RS', 'Serbia', 'RSD', 'SRB'],
    ['RU', 'Russia', 'RUB', 'RUS'],
    ['RW', 'Rwanda', 'RWF', 'RWA'],
    ['SA', 'Saudi Arabia', 'SAR', 'SAU'],
    ['SB', 'Solomon Islands', 'SBD', 'SLB'],
    ['SC', 'Seychelles', 'SCR', 'SYC'],
    ['SD', 'Sudan', 'SDG', 'SDN'],
    ['SE', 'Sweden', 'SEK', 'SWE'],
    ['SG', 'Singapore', 'SGD', 'SGP'],
    ['SH', 'Saint Helena', 'SHP', 'SHN'],
    ['SI', 'Slovenia', 'EUR', 'SVN'],
    ['SJ', 'Svalbard and Jan Mayen', 'NOK', 'SJM'],
    ['SK', 'Slovakia', 'EUR', 'SVK'],
    ['SL', 'Sierra Leone', 'SLL', 'SLE'],
    ['SM', 'San Marino', 'EUR', 'SMR'],
    ['SN', 'Senegal', 'XOF', 'SEN'],
    ['SO', 'Somalia', 'SOS', 'SOM'],
    ['SR', 'Suriname', 'SRD', 'SUR'],
    ['SS', 'South Sudan', 'SSP', 'SSD'],
    ['ST', 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'STD', 'STP'],
    ['SV', 'El Salvador', 'USD', 'SLV'],
    ['SX', 'Sint Maarten', 'ANG', 'SXM'],
    ['SY', 'Syria', 'SYP', 'SYR'],
    ['SZ', 'Swaziland', 'SZL', 'SWZ'],
    ['TC', 'Turks and Caicos Islands', 'USD', 'TCA'],
    ['TD', 'Chad', 'XAF', 'TCD'],
    ['TF', 'French Southern Territories', 'EUR', 'ATF'],
    ['TG', 'Togo', 'XOF', 'TGO'],
    ['TH', 'Thailand', 'THB', 'THA'],
    ['TJ', 'Tajikistan', 'TJS', 'TJK'],
    ['TK', 'Tokelau', 'NZD', 'TKL'],
    ['TL', 'East Timor', 'USD', 'TLS'],
    ['TM', 'Turkmenistan', 'TMT', 'TKM'],
    ['TN', 'Tunisia', 'TND', 'TUN'],
    ['TO', 'Tonga', 'TOP', 'TON'],
    ['TR', 'Turkey', 'TRY', 'TUR'],
    ['TT', 'Trinidad and Tobago', 'TTD', 'TTO'],
    ['TV', 'Tuvalu', 'AUD', 'TUV'],
    ['TW', 'Taiwan', 'TWD', 'TWN'],
    ['TZ', 'Tanzania', 'TZS', 'TZA'],
    ['UA', 'Ukraine', 'UAH', 'UKR'],
    ['UG', 'Uganda', 'UGX', 'UGA'],
    ['UM', 'U.S. Minor Outlying Islands', 'USD', 'UMI'],
    ['US', 'United States', 'USD', 'USA'],
    ['UY', 'Uruguay', 'UYU', 'URY'],
    ['UZ', 'Uzbekistan', 'UZS', 'UZB'],
    ['VA', 'Vatican City', 'EUR', 'VAT'],
    ['VC', 'Saint Vincent and the Grenadines', 'XCD', 'VCT'],
    ['VE', 'Venezuela', 'VEF', 'VEN'],
    ['VG', 'British Virgin Islands', 'USD', 'VGB'],
    ['VI', 'U.S. Virgin Islands', 'USD', 'VIR'],
    ['VN', 'Vietnam', 'VND', 'VNM'],
    ['VU', 'Vanuatu', 'VUV', 'VUT'],
    ['WF', 'Wallis and Futuna', 'XPF', 'WLF'],
    ['WS', 'Samoa', 'WST', 'WSM'],
    ['XK', 'Kosovo', 'EUR', 'XKX'],
    ['YE', 'Yemen', 'YER', 'YEM'],
    ['YT', 'Mayotte', 'EUR', 'MYT'],
    ['ZA', 'South Africa', 'ZAR', 'ZAF'],
    ['ZM', 'Zambia', 'ZMK', 'ZMB'],
    ['ZW', 'Zimbabwe', 'ZWL', 'ZWE'],
];

foreach ($currencies as $currency) {
    $value = Database::select(
        "*",
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
