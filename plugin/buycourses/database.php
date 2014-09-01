<?php
/* For license terms, see /license.txt */
/**
 * Plugin database installation script. Can only be executed if included
 * inside another script loading global.inc.php
 * @package chamilo.plugin.buycourses
 */
/**
 * Check if script can be called
 */
if (!function_exists('api_get_path')) {
    die('This script must be loaded through the Chamilo plugin installer sequence');
}
/**
 * Create the script context, then execute database queries to enable
 */
$objPlugin = BuyCoursesPlugin::create();

$table = Database::get_main_table(TABLE_BUY_COURSE);
$sql = "CREATE TABLE IF NOT EXISTS $table (
    id INT unsigned NOT NULL auto_increment PRIMARY KEY,
    course_id INT unsigned NOT NULL DEFAULT '0',
    code VARCHAR(40),
    title VARCHAR(250),
    visible int,
    price FLOAT(11,2) NOT NULL DEFAULT '0',
    sync int)";
Database::query($sql);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$sql = "SELECT id, code, title FROM $tableCourse";
$res = Database::query($sql);
while ($row = Database::fetch_assoc($res)) {
    $presql = "INSERT INTO $table (course_id, code, title, visible) VALUES ('" . $row['id'] . "','" . $row['code'] . "','" . $row['title'] . "','NO')";
    Database::query($presql);
}

$table = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
$sql = "CREATE TABLE IF NOT EXISTS $table (
    country_id int NOT NULL AUTO_INCREMENT,
    country_code char(2) NOT NULL DEFAULT '',
    country_name varchar(45) NOT NULL DEFAULT '',
    currency_code char(3) DEFAULT NULL,
    iso_alpha3 char(3) DEFAULT NULL,
    status int DEFAULT '0',
    PRIMARY KEY (country_id)
    ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;";
Database::query($sql);

$sql = "CREATE UNIQUE INDEX index_country ON $table (country_code)";
Database::query($sql);

$sql = "INSERT INTO $table (country_code, country_name, currency_code, iso_alpha3) VALUES
	('AD', 'Andorra', 'EUR', 'AND'),
	('AE', 'United Arab Emirates', 'AED', 'ARE'),
	('AF', 'Afghanistan', 'AFN', 'AFG'),
	('AG', 'Antigua and Barbuda', 'XCD', 'ATG'),
	('AI', 'Anguilla', 'XCD', 'AIA'),
	('AL', 'Albania', 'ALL', 'ALB'),
	('AM', 'Armenia', 'AMD', 'ARM'),
	('AO', 'Angola', 'AOA', 'AGO'),
	('AR', 'Argentina', 'ARS', 'ARG'),
	('AS', 'American Samoa', 'USD', 'ASM'),
	('AT', 'Austria', 'EUR', 'AUT'),
	('AU', 'Australia', 'AUD', 'AUS'),
	('AW', 'Aruba', 'AWG', 'ABW'),
	('AX', '&Aring;land', 'EUR', 'ALA'),
	('AZ', 'Azerbaijan', 'AZN', 'AZE'),
	('BA', 'Bosnia and Herzegovina', 'BAM', 'BIH'),
	('BB', 'Barbados', 'BBD', 'BRB'),
	('BD', 'Bangladesh', 'BDT', 'BGD'),
	('BE', 'Belgium', 'EUR', 'BEL'),
	('BF', 'Burkina Faso', 'XOF', 'BFA'),
	('BG', 'Bulgaria', 'BGN', 'BGR'),
	('BH', 'Bahrain', 'BHD', 'BHR'),
	('BI', 'Burundi', 'BIF', 'BDI'),
	('BJ', 'Benin', 'XOF', 'BEN'),
	('BL', 'Saint Barth&eacute;lemy', 'EUR', 'BLM'),
	('BM', 'Bermuda', 'BMD', 'BMU'),
	('BN', 'Brunei', 'BND', 'BRN'),
	('BO', 'Bolivia', 'BOB', 'BOL'),
	('BQ', 'Bonaire', 'USD', 'BES'),
	('BR', 'Brazil', 'BRL', 'BRA'),
	('BS', 'Bahamas', 'BSD', 'BHS'),
	('BT', 'Bhutan', 'BTN', 'BTN'),
	('BV', 'Bouvet Island', 'NOK', 'BVT'),
	('BW', 'Botswana', 'BWP', 'BWA'),
	('BY', 'Belarus', 'BYR', 'BLR'),
	('BZ', 'Belize', 'BZD', 'BLZ'),
	('CA', 'Canada', 'CAD', 'CAN'),
	('CC', 'Cocos [Keeling] Islands', 'AUD', 'CCK'),
	('CD', 'Congo', 'CDF', 'COD'),
	('CF', 'Central African Republic', 'XAF', 'CAF'),
	('CG', 'Republic of the Congo', 'XAF', 'COG'),
	('CH', 'Switzerland', 'CHF', 'CHE'),
	('CI', 'Ivory Coast', 'XOF', 'CIV'),
	('CK', 'Cook Islands', 'NZD', 'COK'),
	('CL', 'Chile', 'CLP', 'CHL'),
	('CM', 'Cameroon', 'XAF', 'CMR'),
	('CN', 'China', 'CNY', 'CHN'),
	('CO', 'Colombia', 'COP', 'COL'),
	('CR', 'Costa Rica', 'CRC', 'CRI'),
	('CU', 'Cuba', 'CUP', 'CUB'),
	('CV', 'Cape Verde', 'CVE', 'CPV'),
	('CW', 'Curacao', 'ANG', 'CUW'),
	('CX', 'Christmas Island', 'AUD', 'CXR'),
	('CY', 'Cyprus', 'EUR', 'CYP'),
	('CZ', 'Czechia', 'CZK', 'CZE'),
	('DE', 'Germany', 'EUR', 'DEU'),
	('DJ', 'Djibouti', 'DJF', 'DJI'),
	('DK', 'Denmark', 'DKK', 'DNK'),
	('DM', 'Dominica', 'XCD', 'DMA'),
	('DO', 'Dominican Republic', 'DOP', 'DOM'),
	('DZ', 'Algeria', 'DZD', 'DZA'),
	('EC', 'Ecuador', 'USD', 'ECU'),
	('EE', 'Estonia', 'EUR', 'EST'),
	('EG', 'Egypt', 'EGP', 'EGY'),
	('EH', 'Western Sahara', 'MAD', 'ESH'),
	('ER', 'Eritrea', 'ERN', 'ERI'),
	('ES', 'Spain', 'EUR', 'ESP'),
	('ET', 'Ethiopia', 'ETB', 'ETH'),
	('FI', 'Finland', 'EUR', 'FIN'),
	('FJ', 'Fiji', 'FJD', 'FJI'),
	('FK', 'Falkland Islands', 'FKP', 'FLK'),
	('FM', 'Micronesia', 'USD', 'FSM'),
	('FO', 'Faroe Islands', 'DKK', 'FRO'),
	('FR', 'France', 'EUR', 'FRA'),
	('GA', 'Gabon', 'XAF', 'GAB'),
	('GB', 'United Kingdom', 'GBP', 'GBR'),
	('GD', 'Grenada', 'XCD', 'GRD'),
	('GE', 'Georgia', 'GEL', 'GEO'),
	('GF', 'French Guiana', 'EUR', 'GUF'),
	('GG', 'Guernsey', 'GBP', 'GGY'),
	('GH', 'Ghana', 'GHS', 'GHA'),
	('GI', 'Gibraltar', 'GIP', 'GIB'),
	('GL', 'Greenland', 'DKK', 'GRL'),
	('GM', 'Gambia', 'GMD', 'GMB'),
	('GN', 'Guinea', 'GNF', 'GIN'),
	('GP', 'Guadeloupe', 'EUR', 'GLP'),
	('GQ', 'Equatorial Guinea', 'XAF', 'GNQ'),
	('GR', 'Greece', 'EUR', 'GRC'),
	('GS', 'South Georgia and the South Sandwich Islands', 'GBP', 'SGS'),
	('GT', 'Guatemala', 'GTQ', 'GTM'),
	('GU', 'Guam', 'USD', 'GUM'),
	('GW', 'Guinea-Bissau', 'XOF', 'GNB'),
	('GY', 'Guyana', 'GYD', 'GUY'),
	('HK', 'Hong Kong', 'HKD', 'HKG'),
	('HM', 'Heard Island and McDonald Islands', 'AUD', 'HMD'),
	('HN', 'Honduras', 'HNL', 'HND'),
	('HR', 'Croatia', 'HRK', 'HRV'),
	('HT', 'Haiti', 'HTG', 'HTI'),
	('HU', 'Hungary', 'HUF', 'HUN'),
	('ID', 'Indonesia', 'IDR', 'IDN'),
	('IE', 'Ireland', 'EUR', 'IRL'),
	('IL', 'Israel', 'ILS', 'ISR'),
	('IM', 'Isle of Man', 'GBP', 'IMN'),
	('IN', 'India', 'INR', 'IND'),
	('IO', 'British Indian Ocean Territory', 'USD', 'IOT'),
	('IQ', 'Iraq', 'IQD', 'IRQ'),
	('IR', 'Iran', 'IRR', 'IRN'),
	('IS', 'Iceland', 'ISK', 'ISL'),
	('IT', 'Italy', 'EUR', 'ITA'),
	('JE', 'Jersey', 'GBP', 'JEY'),
	('JM', 'Jamaica', 'JMD', 'JAM'),
	('JO', 'Jordan', 'JOD', 'JOR'),
	('JP', 'Japan', 'JPY', 'JPN'),
	('KE', 'Kenya', 'KES', 'KEN'),
	('KG', 'Kyrgyzstan', 'KGS', 'KGZ'),
	('KH', 'Cambodia', 'KHR', 'KHM'),
	('KI', 'Kiribati', 'AUD', 'KIR'),
	('KM', 'Comoros', 'KMF', 'COM'),
	('KN', 'Saint Kitts and Nevis', 'XCD', 'KNA'),
	('KP', 'North Korea', 'KPW', 'PRK'),
	('KR', 'South Korea', 'KRW', 'KOR'),
	('KW', 'Kuwait', 'KWD', 'KWT'),
	('KY', 'Cayman Islands', 'KYD', 'CYM'),
	('KZ', 'Kazakhstan', 'KZT', 'KAZ'),
	('LA', 'Laos', 'LAK', 'LAO'),
	('LB', 'Lebanon', 'LBP', 'LBN'),
	('LC', 'Saint Lucia', 'XCD', 'LCA'),
	('LI', 'Liechtenstein', 'CHF', 'LIE'),
	('LK', 'Sri Lanka', 'LKR', 'LKA'),
	('LR', 'Liberia', 'LRD', 'LBR'),
	('LS', 'Lesotho', 'LSL', 'LSO'),
	('LT', 'Lithuania', 'LTL', 'LTU'),
	('LU', 'Luxembourg', 'EUR', 'LUX'),
	('LV', 'Latvia', 'LVL', 'LVA'),
	('LY', 'Libya', 'LYD', 'LBY'),
	('MA', 'Morocco', 'MAD', 'MAR'),
	('MC', 'Monaco', 'EUR', 'MCO'),
	('MD', 'Moldova', 'MDL', 'MDA'),
	('ME', 'Montenegro', 'EUR', 'MNE'),
	('MF', 'Saint Martin', 'EUR', 'MAF'),
	('MG', 'Madagascar', 'MGA', 'MDG'),
	('MH', 'Marshall Islands', 'USD', 'MHL'),
	('MK', 'Macedonia', 'MKD', 'MKD'),
	('ML', 'Mali', 'XOF', 'MLI'),
	('MM', 'Myanmar [Burma]', 'MMK', 'MMR'),
	('MN', 'Mongolia', 'MNT', 'MNG'),
	('MO', 'Macao', 'MOP', 'MAC'),
	('MP', 'Northern Mariana Islands', 'USD', 'MNP'),
	('MQ', 'Martinique', 'EUR', 'MTQ'),
	('MR', 'Mauritania', 'MRO', 'MRT'),
	('MS', 'Montserrat', 'XCD', 'MSR'),
	('MT', 'Malta', 'EUR', 'MLT'),
	('MU', 'Mauritius', 'MUR', 'MUS'),
	('MV', 'Maldives', 'MVR', 'MDV'),
	('MW', 'Malawi', 'MWK', 'MWI'),
	('MX', 'Mexico', 'MXN', 'MEX'),
	('MY', 'Malaysia', 'MYR', 'MYS'),
	('MZ', 'Mozambique', 'MZN', 'MOZ'),
	('NA', 'Namibia', 'NAD', 'NAM'),
	('NC', 'New Caledonia', 'XPF', 'NCL'),
	('NE', 'Niger', 'XOF', 'NER'),
	('NF', 'Norfolk Island', 'AUD', 'NFK'),
	('NG', 'Nigeria', 'NGN', 'NGA'),
	('NI', 'Nicaragua', 'NIO', 'NIC'),
	('NL', 'Netherlands', 'EUR', 'NLD'),
	('NO', 'Norway', 'NOK', 'NOR'),
	('NP', 'Nepal', 'NPR', 'NPL'),
	('NR', 'Nauru', 'AUD', 'NRU'),
	('NU', 'Niue', 'NZD', 'NIU'),
	('NZ', 'New Zealand', 'NZD', 'NZL'),
	('OM', 'Oman', 'OMR', 'OMN'),
	('PA', 'Panama', 'PAB', 'PAN'),
	('PE', 'Peru', 'PEN', 'PER'),
	('PF', 'French Polynesia', 'XPF', 'PYF'),
	('PG', 'Papua New Guinea', 'PGK', 'PNG'),
	('PH', 'Philippines', 'PHP', 'PHL'),
	('PK', 'Pakistan', 'PKR', 'PAK'),
	('PL', 'Poland', 'PLN', 'POL'),
	('PM', 'Saint Pierre and Miquelon', 'EUR', 'SPM'),
	('PN', 'Pitcairn Islands', 'NZD', 'PCN'),
	('PR', 'Puerto Rico', 'USD', 'PRI'),
	('PS', 'Palestine', 'ILS', 'PSE'),
	('PT', 'Portugal', 'EUR', 'PRT'),
	('PW', 'Palau', 'USD', 'PLW'),
	('PY', 'Paraguay', 'PYG', 'PRY'),
	('QA', 'Qatar', 'QAR', 'QAT'),
	('RE', 'R&eacute;union', 'EUR', 'REU'),
	('RO', 'Romania', 'RON', 'ROU'),
	('RS', 'Serbia', 'RSD', 'SRB'),
	('RU', 'Russia', 'RUB', 'RUS'),
	('RW', 'Rwanda', 'RWF', 'RWA'),
	('SA', 'Saudi Arabia', 'SAR', 'SAU'),
	('SB', 'Solomon Islands', 'SBD', 'SLB'),
	('SC', 'Seychelles', 'SCR', 'SYC'),
	('SD', 'Sudan', 'SDG', 'SDN'),
	('SE', 'Sweden', 'SEK', 'SWE'),
	('SG', 'Singapore', 'SGD', 'SGP'),
	('SH', 'Saint Helena', 'SHP', 'SHN'),
	('SI', 'Slovenia', 'EUR', 'SVN'),
	('SJ', 'Svalbard and Jan Mayen', 'NOK', 'SJM'),
	('SK', 'Slovakia', 'EUR', 'SVK'),
	('SL', 'Sierra Leone', 'SLL', 'SLE'),
	('SM', 'San Marino', 'EUR', 'SMR'),
	('SN', 'Senegal', 'XOF', 'SEN'),
	('SO', 'Somalia', 'SOS', 'SOM'),
	('SR', 'Suriname', 'SRD', 'SUR'),
	('SS', 'South Sudan', 'SSP', 'SSD'),
	('ST', 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'STD', 'STP'),
	('SV', 'El Salvador', 'USD', 'SLV'),
	('SX', 'Sint Maarten', 'ANG', 'SXM'),
	('SY', 'Syria', 'SYP', 'SYR'),
	('SZ', 'Swaziland', 'SZL', 'SWZ'),
	('TC', 'Turks and Caicos Islands', 'USD', 'TCA'),
	('TD', 'Chad', 'XAF', 'TCD'),
	('TF', 'French Southern Territories', 'EUR', 'ATF'),
	('TG', 'Togo', 'XOF', 'TGO'),
	('TH', 'Thailand', 'THB', 'THA'),
	('TJ', 'Tajikistan', 'TJS', 'TJK'),
	('TK', 'Tokelau', 'NZD', 'TKL'),
	('TL', 'East Timor', 'USD', 'TLS'),
	('TM', 'Turkmenistan', 'TMT', 'TKM'),
	('TN', 'Tunisia', 'TND', 'TUN'),
	('TO', 'Tonga', 'TOP', 'TON'),
	('TR', 'Turkey', 'TRY', 'TUR'),
	('TT', 'Trinidad and Tobago', 'TTD', 'TTO'),
	('TV', 'Tuvalu', 'AUD', 'TUV'),
	('TW', 'Taiwan', 'TWD', 'TWN'),
	('TZ', 'Tanzania', 'TZS', 'TZA'),
	('UA', 'Ukraine', 'UAH', 'UKR'),
	('UG', 'Uganda', 'UGX', 'UGA'),
	('UM', 'U.S. Minor Outlying Islands', 'USD', 'UMI'),
	('US', 'United States', 'USD', 'USA'),
	('UY', 'Uruguay', 'UYU', 'URY'),
	('UZ', 'Uzbekistan', 'UZS', 'UZB'),
	('VA', 'Vatican City', 'EUR', 'VAT'),
	('VC', 'Saint Vincent and the Grenadines', 'XCD', 'VCT'),
	('VE', 'Venezuela', 'VEF', 'VEN'),
	('VG', 'British Virgin Islands', 'USD', 'VGB'),
	('VI', 'U.S. Virgin Islands', 'USD', 'VIR'),
	('VN', 'Vietnam', 'VND', 'VNM'),
	('VU', 'Vanuatu', 'VUV', 'VUT'),
	('WF', 'Wallis and Futuna', 'XPF', 'WLF'),
	('WS', 'Samoa', 'WST', 'WSM'),
	('XK', 'Kosovo', 'EUR', 'XKX'),
	('YE', 'Yemen', 'YER', 'YEM'),
	('YT', 'Mayotte', 'EUR', 'MYT'),
	('ZA', 'South Africa', 'ZAR', 'ZAF'),
	('ZM', 'Zambia', 'ZMK', 'ZMB'),
	('ZW', 'Zimbabwe', 'ZWL', 'ZWE')";
Database::query($sql);

$table = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);
$sql = "CREATE TABLE IF NOT EXISTS $table (
    id INT unsigned NOT NULL auto_increment PRIMARY KEY,
    sandbox VARCHAR(5) NOT NULL DEFAULT 'YES',
    username VARCHAR(100) NOT NULL DEFAULT '',
    password VARCHAR(100) NOT NULL DEFAULT '',
    signature VARCHAR(100) NOT NULL DEFAULT '')";
Database::query($sql);

$sql = "INSERT INTO $table (id,username,password,signature) VALUES ('1', 'API_UserName', 'API_Password', 'API_Signature')";
Database::query($sql);

$table = Database::get_main_table(TABLE_BUY_COURSE_TRANSFER);
$sql = "CREATE TABLE IF NOT EXISTS $table (
    id INT unsigned NOT NULL auto_increment PRIMARY KEY,
    name VARCHAR(100) NOT NULL DEFAULT '',
    account VARCHAR(100) NOT NULL DEFAULT '',
    swift VARCHAR(100) NOT NULL DEFAULT '')";
Database::query($sql);

$table = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
$sql = "CREATE TABLE IF NOT EXISTS $table (
    cod INT unsigned NOT NULL auto_increment PRIMARY KEY,
    user_id INT unsigned NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT '',
    course_code VARCHAR(200) NOT NULL DEFAULT '',
    title VARCHAR(200) NOT NULL DEFAULT '',
    reference VARCHAR(20) NOT NULL DEFAULT '',
    price FLOAT(11,2) NOT NULL DEFAULT '0',
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)";
Database::query($sql);

$table = Database::get_main_table(TABLE_BUY_COURSE_SALE);
$sql = "CREATE TABLE IF NOT EXISTS $table (
    cod INT unsigned NOT NULL auto_increment PRIMARY KEY,
    user_id INT unsigned NOT NULL,
    course_code VARCHAR(200) NOT NULL DEFAULT '',
    price FLOAT(11,2) NOT NULL DEFAULT '0',
    payment_type VARCHAR(100) NOT NULL DEFAULT '',
    status VARCHAR(20) NOT NULL DEFAULT '',
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)";
Database::query($sql);

//Menu main tabs
$rsTab = $objPlugin->addTab('Buy Courses', 'plugin/buycourses/index.php');

if ($rsTab) {
    echo "<script>location.href = '" . Security::remove_XSS($_SERVER['REQUEST_URI']) . "';</script>";
}
