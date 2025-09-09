<?php

/* For license terms, see /license.txt */

use Doctrine\DBAL\Schema\Schema;

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
$sm = $connection->createSchemaManager();

// Create tables
if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_PAYPAL)) {
    $paypalTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_PAYPAL);
    $paypalTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $paypalTable->addColumn('username', 'string');
    $paypalTable->addColumn('password', 'string');
    $paypalTable->addColumn('signature', 'string');
    $paypalTable->addColumn('sandbox', 'boolean');
    $paypalTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_TRANSFER)) {
    $transferTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_TRANSFER);
    $transferTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $transferTable->addColumn('title', 'string');
    $transferTable->addColumn('account', 'string');
    $transferTable->addColumn('swift', 'string');
    $transferTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_CURRENCY)) {
    $currencyTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_CURRENCY);
    $currencyTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $currencyTable->addColumn(
        'country_code',
        'string',
        ['length' => 2]
    );
    $currencyTable->addColumn(
        'country_name',
        'string',
        ['length' => 255]
    );
    $currencyTable->addColumn(
        'iso_code',
        'string',
        ['length' => 4]
    );
    $currencyTable->addColumn('status', 'boolean');
    $currencyTable->addUniqueIndex(['country_code']);
    $currencyTable->addIndex(['iso_code']);
    $currencyTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_ITEM)) {
    $itemTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_ITEM);
    $itemTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $itemTable->addColumn('product_type', 'integer');
    $itemTable->addColumn(
        'product_id',
        'integer',
        ['unsigned' => true]
    );
    $itemTable->addColumn(
        'price',
        'decimal',
        ['scale' => 2]
    );
    $itemTable->addColumn(
        'currency_id',
        'integer',
        ['unsigned' => true]
    );
    $itemTable->addColumn(
        'tax_perc',
        'integer',
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
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $itemBeneficiary->addColumn(
        'item_id',
        'integer',
        ['unsigned' => true]
    );
    $itemBeneficiary->addColumn(
        'user_id',
        'integer',
        ['unsigned' => true]
    );
    $itemBeneficiary->addColumn(
        'commissions',
        'integer',
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
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $commissions->addColumn(
        'commission',
        'integer',
        ['unsigned' => true]
    );
    $commissions->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS)) {
    $saleCommissions = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS);
    $saleCommissions->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $saleCommissions->addColumn('date', 'datetime');
    $saleCommissions->addColumn('payout_date', 'datetime');
    $saleCommissions->addColumn(
        'sale_id',
        'integer',
        ['unsigned' => true]
    );
    $saleCommissions->addColumn(
        'user_id',
        'integer',
        ['unsigned' => true]
    );
    $saleCommissions->addColumn(
        'commission',
        'decimal',
        ['scale' => 2]
    );
    $saleCommissions->addColumn(
        'status',
        'integer',
        ['unsigned' => true]
    );
    $saleCommissions->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SALE)) {
    $saleTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SALE);
    $saleTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $saleTable->addColumn('reference', 'string');
    $saleTable->addColumn('date', 'datetime');
    $saleTable->addColumn(
        'user_id',
        'integer',
        ['unsigned' => true]
    );
    $saleTable->addColumn('product_type', 'integer');
    $saleTable->addColumn('product_name', 'string');
    $saleTable->addColumn(
        'product_id',
        'integer',
        ['unsigned' => true]
    );
    $saleTable->addColumn(
        'price',
        'decimal',
        ['scale' => 2]
    );
    $saleTable->addColumn(
        'price_without_tax',
        'decimal',
        ['scale' => 2, 'notnull' => false]
    );
    $saleTable->addColumn(
        'tax_perc',
        'integer',
        ['unsigned' => true, 'notnull' => false]
    );
    $saleTable->addColumn(
        'tax_amount',
        'decimal',
        ['scale' => 2, 'notnull' => false]
    );
    $saleTable->addColumn(
        'currency_id',
        'integer',
        ['unsigned' => true]
    );
    $saleTable->addColumn('status', 'integer');
    $saleTable->addColumn('payment_type', 'integer');
    $saleTable->addColumn('invoice', 'integer');
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
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $servicesTable->addColumn('title', 'string');
    $servicesTable->addColumn('description', 'text');
    $servicesTable->addColumn(
        'price',
        'decimal',
        ['scale' => 2]
    );
    $servicesTable->addColumn('duration_days', 'integer');
    $servicesTable->addColumn('applies_to', 'integer');
    $servicesTable->addColumn('owner_id', 'integer');
    $servicesTable->addColumn('visibility', 'integer');
    $servicesTable->addColumn('video_url', 'string');
    $servicesTable->addColumn('image', 'string');
    $servicesTable->addColumn('service_information', 'text');
    $servicesTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_SERVICES_SALE)) {
    $servicesNodeTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_SERVICES_SALE);
    $servicesNodeTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $servicesNodeTable->addColumn(
        'service_id',
        'integer',
        ['unsigned' => true]
    );
    $servicesNodeTable->addColumn('reference', 'string');
    $servicesNodeTable->addColumn('currency_id', 'integer');
    $servicesNodeTable->addColumn(
        'price',
        'decimal',
        ['scale' => 2]
    );
    $servicesNodeTable->addColumn(
        'price_without_tax',
        'decimal',
        ['scale' => 2, 'notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'tax_perc',
        'integer',
        ['unsigned' => true, 'notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'tax_amount',
        'decimal',
        ['scale' => 2, 'notnull' => false]
    );
    $servicesNodeTable->addColumn('node_type', 'integer');
    $servicesNodeTable->addColumn('node_id', 'integer');
    $servicesNodeTable->addColumn('buyer_id', 'integer');
    $servicesNodeTable->addColumn('buy_date', 'datetime');
    $servicesNodeTable->addColumn(
        'date_start',
        'datetime',
        ['notnull' => false]
    );
    $servicesNodeTable->addColumn(
        'date_end',
        'datetime'
    );
    $servicesNodeTable->addColumn('status', 'integer');
    $servicesNodeTable->addColumn('payment_type', 'integer');
    $servicesNodeTable->addColumn('invoice', 'integer');
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
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $culqiTable->addColumn('commerce_code', 'string');
    $culqiTable->addColumn('api_key', 'string');
    $culqiTable->addColumn('integration', 'integer');
    $culqiTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_GLOBAL_CONFIG)) {
    $globalTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_GLOBAL_CONFIG);
    $globalTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $globalTable->addColumn('terms_and_conditions', 'text');
    $globalTable->addColumn('global_tax_perc', 'integer');
    $globalTable->addColumn('tax_applies_to', 'integer');
    $globalTable->addColumn('tax_name', 'string');
    $globalTable->addColumn('seller_name', 'string');
    $globalTable->addColumn('seller_id', 'string');
    $globalTable->addColumn('seller_address', 'string');
    $globalTable->addColumn('seller_email', 'string');
    $globalTable->addColumn('next_number_invoice', 'integer');
    $globalTable->addColumn('invoice_series', 'string');
    $globalTable->addColumn('sale_email', 'string');
    $globalTable->setPrimaryKey(['id']);
}

if (false === $sm->tablesExist(BuyCoursesPlugin::TABLE_INVOICE)) {
    $invoiceTable = $pluginSchema->createTable(BuyCoursesPlugin::TABLE_INVOICE);
    $invoiceTable->addColumn(
        'id',
        'integer',
        ['autoincrement' => true, 'unsigned' => true]
    );
    $invoiceTable->addColumn('sale_id', 'integer');
    $invoiceTable->addColumn('is_service', 'integer');
    $invoiceTable->addColumn(
        'num_invoice',
        'integer',
        ['unsigned' => true, 'notnull' => false]
    );
    $invoiceTable->addColumn(
        'year',
        'integer',
        ['unsigned' => true, 'notnull' => false]
    );
    $invoiceTable->addColumn('serie', 'string');
    $invoiceTable->addColumn('date_invoice', 'datetime');
    $invoiceTable->setPrimaryKey(['id']);
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
        'global_tax_perc' => 0,
        'tax_applies_to' => 0,
        'tax_name' => '',
        'seller_name' => '',
        'seller_id' => '',
        'seller_address' => '',
        'seller_email' => '',
        'next_number_invoice' => 1,
        'invoice_series' => '',
        'sale_email' => '',
    ]
);

Database::insert(
    $commissionTable,
    [
        'commission' => 0,
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
