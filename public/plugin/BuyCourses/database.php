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
        ['length' => 3]
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
