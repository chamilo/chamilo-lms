<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Plugin class for the BuyCourses plugin.
 *
 * @package chamilo.plugin.buycourses
 *
 * @author  Jose Angel Ruiz <jaruiz@nosolored.com>
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 * @author  Alex Aragón <alex.aragon@beeznest.com>
 * @author  Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author  José Loguercio Silva  <jose.loguercio@beeznest.com>
 * @author  Julio Montoya
 */
class BuyCoursesPlugin extends Plugin
{
    public const TABLE_PAYPAL = 'plugin_buycourses_paypal_account';
    public const TABLE_CURRENCY = 'plugin_buycourses_currency';
    public const TABLE_ITEM = 'plugin_buycourses_item';
    public const TABLE_ITEM_BENEFICIARY = 'plugin_buycourses_item_rel_beneficiary';
    public const TABLE_SALE = 'plugin_buycourses_sale';
    public const TABLE_TRANSFER = 'plugin_buycourses_transfer';
    public const TABLE_COMMISSION = 'plugin_buycourses_commission';
    public const TABLE_PAYPAL_PAYOUTS = 'plugin_buycourses_paypal_payouts';
    public const TABLE_SERVICES = 'plugin_buycourses_services';
    public const TABLE_SERVICES_SALE = 'plugin_buycourses_service_sale';
    public const TABLE_CULQI = 'plugin_buycourses_culqi';
    public const TABLE_GLOBAL_CONFIG = 'plugin_buycourses_global_config';
    public const TABLE_INVOICE = 'plugin_buycourses_invoices';
    public const TABLE_TPV_REDSYS = 'plugin_buycourses_tpvredsys_account';
    public const TABLE_COUPON = 'plugin_buycourses_coupon';
    public const TABLE_COUPON_ITEM = 'plugin_buycourses_coupon_rel_item';
    public const TABLE_COUPON_SERVICE = 'plugin_buycourses_coupon_rel_service';
    public const TABLE_SUBSCRIPTION = 'plugin_buycourses_subscription';
    public const TABLE_SUBSCRIPTION_SALE = 'plugin_buycourses_subscription_rel_sale';
    public const TABLE_SUBSCRIPTION_PERIOD = 'plugin_buycourses_subscription_period';
    public const TABLE_COUPON_SALE = 'plugin_buycourses_coupon_rel_sale';
    public const TABLE_COUPON_SERVICE_SALE = 'plugin_buycourses_coupon_rel_service_sale';
    public const TABLE_COUPON_SUBSCRIPTION_SALE = 'plugin_buycourses_coupon_rel_subscription_sale';
    public const TABLE_STRIPE = 'plugin_buycourses_stripe_account';
    public const TABLE_TPV_CECABANK = 'plugin_buycourses_cecabank_account';
    public const PRODUCT_TYPE_COURSE = 1;
    public const PRODUCT_TYPE_SESSION = 2;
    public const PRODUCT_TYPE_SERVICE = 3;
    public const PAYMENT_TYPE_PAYPAL = 1;
    public const PAYMENT_TYPE_TRANSFER = 2;
    public const PAYMENT_TYPE_CULQI = 3;
    public const PAYMENT_TYPE_TPV_REDSYS = 4;
    public const PAYMENT_TYPE_STRIPE = 5;
    public const PAYMENT_TYPE_TPV_CECABANK = 6;
    public const PAYOUT_STATUS_CANCELED = 2;
    public const PAYOUT_STATUS_PENDING = 0;
    public const PAYOUT_STATUS_COMPLETED = 1;
    public const SALE_STATUS_CANCELED = -1;
    public const SALE_STATUS_PENDING = 0;
    public const SALE_STATUS_COMPLETED = 1;
    public const SERVICE_STATUS_PENDING = 0;
    public const SERVICE_STATUS_COMPLETED = 1;
    public const SERVICE_STATUS_CANCELLED = -1;
    public const SERVICE_TYPE_USER = 1;
    public const SERVICE_TYPE_COURSE = 2;
    public const SERVICE_TYPE_SESSION = 3;
    public const SERVICE_TYPE_LP_FINAL_ITEM = 4;
    public const CULQI_INTEGRATION_TYPE = 'INTEG';
    public const CULQI_PRODUCTION_TYPE = 'PRODUC';
    public const TAX_APPLIES_TO_ALL = 1;
    public const TAX_APPLIES_TO_ONLY_COURSE = 2;
    public const TAX_APPLIES_TO_ONLY_SESSION = 3;
    public const TAX_APPLIES_TO_ONLY_SERVICES = 4;
    public const PAGINATION_PAGE_SIZE = 6;
    public const COUPON_DISCOUNT_TYPE_PERCENTAGE = 1;
    public const COUPON_DISCOUNT_TYPE_AMOUNT = 2;
    public const COUPON_STATUS_ACTIVE = 1;
    public const COUPON_STATUS_DISABLE = 0;

    public $isAdminPlugin = false;

    /**
     * BuyCoursesPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct(
            '7.1',
            "
                Jose Angel Ruiz - NoSoloRed (original author) <br/>
                Francis Gonzales and Yannick Warnier - BeezNest (integration) <br/>
                Alex Aragón - BeezNest (Design icons and css styles) <br/>
                Imanol Losada - BeezNest (introduction of sessions purchase) <br/>
                Angel Fernando Quiroz Campos - BeezNest (cleanup and new reports) <br/>
                José Loguercio Silva - BeezNest (Payouts and buy Services) <br/>
                Julio Montoya
            ",
            [
                'show_main_menu_tab' => 'boolean',
                'public_main_menu_tab' => 'boolean',
                'include_sessions' => 'boolean',
                'include_services' => 'boolean',
                'paypal_enable' => 'boolean',
                'transfer_enable' => 'boolean',
                'culqi_enable' => 'boolean',
                'commissions_enable' => 'boolean',
                'unregistered_users_enable' => 'boolean',
                'hide_free_text' => 'boolean',
                'hide_shopping_cart_from_course_catalogue' => 'boolean',
                'invoicing_enable' => 'boolean',
                'tax_enable' => 'boolean',
                'use_currency_symbol' => 'boolean',
                'tpv_redsys_enable' => 'boolean',
                'stripe_enable' => 'boolean',
                'cecabank_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return BuyCoursesPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Check if plugin is enabled.
     *
     * @param bool $checkEnabled Check if, additionnally to being installed, the plugin is enabled
     */
    public function isEnabled(bool $checkEnabled = false): bool
    {
        return $this->get('paypal_enable') || $this->get('transfer_enable') || $this->get('culqi_enable') || $this->get('stripe_enable') || $this->get('cecabank_enable');
    }

    /**
     * This method creates the tables required to this plugin.
     */
    public function install()
    {
        $tablesToBeCompared = [
            self::TABLE_PAYPAL,
            self::TABLE_TRANSFER,
            self::TABLE_CULQI,
            self::TABLE_ITEM_BENEFICIARY,
            self::TABLE_ITEM,
            self::TABLE_SALE,
            self::TABLE_CURRENCY,
            self::TABLE_COMMISSION,
            self::TABLE_PAYPAL_PAYOUTS,
            self::TABLE_SERVICES,
            self::TABLE_SERVICES_SALE,
            self::TABLE_GLOBAL_CONFIG,
            self::TABLE_INVOICE,
            self::TABLE_TPV_REDSYS,
            self::TABLE_COUPON,
            self::TABLE_COUPON_ITEM,
            self::TABLE_COUPON_SERVICE,
            self::TABLE_SUBSCRIPTION,
            self::TABLE_SUBSCRIPTION_SALE,
            self::TABLE_SUBSCRIPTION_PERIOD,
            self::TABLE_COUPON_SALE,
            self::TABLE_COUPON_SERVICE_SALE,
            self::TABLE_COUPON_SUBSCRIPTION_SALE,
            self::TABLE_STRIPE,
            self::TABLE_TPV_CECABANK,
        ];
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->getSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if ($tables) {
            return false;
        }

        require_once api_get_path(SYS_PLUGIN_PATH).'buycourses/database.php';
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        $tablesToBeDeleted = [
            self::TABLE_PAYPAL,
            self::TABLE_TRANSFER,
            self::TABLE_CULQI,
            self::TABLE_ITEM_BENEFICIARY,
            self::TABLE_ITEM,
            self::TABLE_SALE,
            self::TABLE_CURRENCY,
            self::TABLE_COMMISSION,
            self::TABLE_PAYPAL_PAYOUTS,
            self::TABLE_SERVICES_SALE,
            self::TABLE_SERVICES,
            self::TABLE_GLOBAL_CONFIG,
            self::TABLE_INVOICE,
            self::TABLE_TPV_REDSYS,
            self::TABLE_COUPON,
            self::TABLE_COUPON_ITEM,
            self::TABLE_COUPON_SERVICE,
            self::TABLE_SUBSCRIPTION,
            self::TABLE_SUBSCRIPTION_SALE,
            self::TABLE_SUBSCRIPTION_PERIOD,
            self::TABLE_COUPON_SALE,
            self::TABLE_COUPON_SERVICE_SALE,
            self::TABLE_COUPON_SUBSCRIPTION_SALE,
            self::TABLE_STRIPE,
        ];

        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

    public function update()
    {
        $table = self::TABLE_GLOBAL_CONFIG;
        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'global_tax_perc'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD (
                sale_email varchar(255) NOT NULL,
                global_tax_perc int unsigned NOT NULL,
                tax_applies_to int unsigned NOT NULL,
                tax_name varchar(255) NOT NULL,
                seller_name varchar(255) NOT NULL,
                seller_id varchar(255) NOT NULL,
                seller_address varchar(255) NOT NULL,
                seller_email varchar(255) NOT NULL,
                next_number_invoice int unsigned NOT NULL,
                invoice_series varchar(255) NOT NULL
            )";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'info_email_extra'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD (info_email_extra TEXT NOT NULL)";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $table = self::TABLE_ITEM;
        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'tax_perc'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD tax_perc int unsigned NULL";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $table = self::TABLE_SERVICES;
        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'tax_perc'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD tax_perc int unsigned NULL";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $table = self::TABLE_SALE;
        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'tax_perc'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD (
                price_without_tax decimal(10,2) NULL,
                tax_perc int unsigned NULL,
                tax_amount decimal(10,2) NULL,
                invoice int unsigned NULL
            )";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'price_without_discount'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD (
                price_without_discount decimal(10,2) NULL,
                discount_amount decimal(10,2) NULL
            )";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $table = self::TABLE_SERVICES_SALE;
        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'tax_perc'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD (
                price_without_tax decimal(10,2) NULL,
                tax_perc int unsigned NULL,
                tax_amount decimal(10,2) NULL,
                invoice int unsigned NULL
            )";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $sql = "SHOW COLUMNS FROM $table WHERE Field = 'price_without_discount'";
        $res = Database::query($sql);

        if (Database::num_rows($res) === 0) {
            $sql = "ALTER TABLE $table ADD (
                price_without_discount decimal(10,2) NULL,
                discount_amount decimal(10,2) NULL
            )";
            $res = Database::query($sql);
            if (!$res) {
                echo Display::return_message($this->get_lang('ErrorUpdateFieldDB'), 'warning');
            }
        }

        $table = self::TABLE_INVOICE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            sale_id int unsigned NOT NULL,
            is_service int unsigned NOT NULL,
            num_invoice int unsigned NOT NULL,
            year int(4) unsigned NOT NULL,
            serie varchar(255) NOT NULL,
            date_invoice datetime NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_TPV_REDSYS;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            merchantcode varchar(255) NOT NULL,
            terminal varchar(255) NOT NULL,
            currency varchar(255) NOT NULL,
            kc varchar(255) NOT NULL,
            url_redsys varchar(255) NOT NULL,
            url_redsys_sandbox varchar(255) NOT NULL,
            sandbox int unsigned NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $sql = "SELECT * FROM $table";
        $res = Database::query($sql);
        if (Database::num_rows($res) == 0) {
            Database::insert($table, [
                'url_redsys' => 'https://sis.redsys.es/sis/realizarPago',
                'url_redsys_sandbox' => 'https://sis-t.redsys.es:25443/sis/realizarPago',
            ]);
        }

        $table = self::TABLE_COUPON;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            code varchar(255) NOT NULL,
            discount_type int unsigned NOT NULL,
            discount_amount decimal(10, 2) NOT NULL,
            valid_start datetime NOT NULL,
            valid_end datetime NOT NULL,
            delivered varchar(255) NOT NULL,
            active tinyint NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_COUPON_ITEM;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            coupon_id int unsigned NOT NULL,
            product_type int unsigned NOT NULL,
            product_id int unsigned NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_COUPON_SERVICE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            coupon_id int unsigned NOT NULL,
            service_id int unsigned NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_SUBSCRIPTION;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            product_type int unsigned NOT NULL,
            product_id int unsigned NOT NULL,
            duration int unsigned NOT NULL,
            currency_id int unsigned NOT NULL,
            price decimal(10, 2) NOT NULL,
            tax_perc int unsigned,
            PRIMARY KEY (product_type, product_id, duration)
        )";
        Database::query($sql);

        $table = self::TABLE_SUBSCRIPTION_SALE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            currency_id int unsigned NOT NULL,
            reference varchar(255) NOT NULL,
            date datetime NOT NULL,
            user_id int unsigned NOT NULL,
            product_type int NOT NULL,
            product_name varchar(255) NOT NULL,
            product_id int unsigned NOT NULL,
            price decimal(10,2) NOT NULL,
            price_without_tax decimal(10,2) NULL,
            tax_perc int unsigned NULL,
            tax_amount decimal(10,2) NULL,
            status int NOT NULL,
            payment_type int NOT NULL,
            invoice int NOT NULL,
            price_without_discount decimal(10,2),
            discount_amount decimal(10,2),
            subscription_end datetime NOT NULL,
            expired tinyint NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_SUBSCRIPTION_PERIOD;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            duration int unsigned NOT NULL,
            name varchar(50) NOT NULL,
            PRIMARY KEY (duration)
        )";
        Database::query($sql);

        $table = self::TABLE_COUPON_SALE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            coupon_id int unsigned NOT NULL,
            sale_id int unsigned NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_COUPON_SERVICE_SALE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            coupon_id int unsigned NOT NULL,
            service_sale_id int unsigned NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_COUPON_SUBSCRIPTION_SALE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            coupon_id int unsigned NOT NULL,
            sale_id int unsigned NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $table = self::TABLE_STRIPE;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            account_id varchar(255) NOT NULL,
            secret_key varchar(255) NOT NULL,
            endpoint_secret varchar(255) NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        $sql = "SELECT * FROM $table";
        $res = Database::query($sql);
        if (Database::num_rows($res) == 0) {
            Database::insert($table, [
                'account_id' => '',
                'secret_key' => '',
                'endpoint_secret' => '',
            ]);
        }

        $table = self::TABLE_TPV_CECABANK;
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id int unsigned NOT NULL AUTO_INCREMENT,
            crypto_key varchar(255) NOT NULL,
            merchant_id varchar(255) NOT NULL,
            acquirer_bin varchar(255) NOT NULL,
            terminal_id varchar(255) NOT NULL,
            cypher varchar(255) NOT NULL,
            exponent varchar(255) NOT NULL,
            supported_payment varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            PRIMARY KEY (id)
        )";
        Database::query($sql);

        Display::addFlash(
            Display::return_message(
                $this->get_lang('Updated'),
                'info',
                false
            )
        );

        $fieldlabel = 'buycourses_company';
        $fieldtype = '1';
        $fieldtitle = $this->get_lang('Company');
        $fielddefault = '';
        UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

        $fieldlabel = 'buycourses_vat';
        $fieldtype = '1';
        $fieldtitle = $this->get_lang('VAT');
        $fielddefault = '';
        UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

        $fieldlabel = 'buycourses_address';
        $fieldtype = '1';
        $fieldtitle = $this->get_lang('Address');
        $fielddefault = '';
        UserManager::create_extra_field($fieldlabel, $fieldtype, $fieldtitle, $fielddefault);

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses');
        exit;
    }

    /**
     * This function verify if the plugin is enable and return the price info for a course or session in the new grid
     * catalog for 1.11.x , the main purpose is to show if a course or session is in sale it shows in the main platform
     * course catalog so the old buycourses plugin catalog can be deprecated.
     *
     * @param int $productId   course or session id
     * @param int $productType course or session type
     *
     * @return mixed bool|string html
     */
    public function buyCoursesForGridCatalogValidator(int $productId, int $productType)
    {
        $return = [];
        $paypal = $this->get('paypal_enable') === 'true';
        $transfer = $this->get('transfer_enable') === 'true';
        $stripe = $this->get('stripe_enable') === 'true';
        $culqi = $this->get('culqi_enable') === 'true';
        $cecabank = $this->get('cecabank_enable') === 'true';
        $tpv_redsys = $this->get('tpv_redsys_enable') === 'true';
        $hideFree = $this->get('hide_free_text') === 'true';

        if ($paypal || $transfer || $stripe || $culqi || $cecabank || $tpv_redsys) {
            $item = $this->getItemByProduct($productId, $productType);
            $html = '<div class="buycourses-price">';
            if ($item) {
                $html .= '<span class="label label-primary label-price">
                            <strong>'.$item['total_price_formatted'].'</strong>
                          </span>';
                $return['verificator'] = true;
            } else {
                if ($hideFree == false) {
                    $html .= '<span class="label label-primary label-free">
                                <strong>'.$this->get_lang('Free').'</strong>
                              </span>';
                }
                $return['verificator'] = false;
            }
            $html .= '</div>';
            $return['html'] = $html;

            return $return;
        }

        return false;
    }

    /**
     * Return the buyCourses plugin button to buy the course.
     *
     * @return string $html
     */
    public function returnBuyCourseButton(int $productId, int $productType)
    {
        $productId = $productId;
        $productType = $productType;
        $url = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process.php?i='.$productId.'&t='.$productType;
        $buyButton = Display::returnFontAwesomeIcon('shopping-cart');
        if ($this->get('hide_shopping_cart_from_course_catalogue') === 'true') {
            $buyButton = Display::returnFontAwesomeIcon('check').PHP_EOL.get_lang('Subscribe');
        } 
        $html = '<a class="btn btn-success btn-sm" title="'.$this->get_lang('Buy').'" href="'.$url.'">'.
            $buyButton .'</a>';

        return $html;
    }

    /**
     * Get the currency for sales.
     *
     * @return array The selected currency. Otherwise return false
     */
    public function getSelectedCurrency()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_CURRENCY),
            [
                'where' => ['status = ?' => true],
            ],
            'first'
        );
    }

    /**
     * Get a list of currencies.
     *
     * @return array The currencies. Otherwise return false
     */
    public function getCurrencies()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_CURRENCY)
        );
    }

    /**
     * Save the selected currency.
     *
     * @param int $selectedId The currency Id
     */
    public function saveCurrency(int $selectedId)
    {
        $currencyTable = Database::get_main_table(
            self::TABLE_CURRENCY
        );

        Database::update(
            $currencyTable,
            ['status' => 0]
        );
        Database::update(
            $currencyTable,
            ['status' => 1],
            ['id = ?' => $selectedId]
        );
    }

    /**
     * Save the PayPal configuration params.
     *
     * @return int Rows affected. Otherwise return false
     */
    public function savePaypalParams(array $params)
    {
        return Database::update(
            Database::get_main_table(self::TABLE_PAYPAL),
            [
                'username' => $params['username'],
                'password' => $params['password'],
                'signature' => $params['signature'],
                'sandbox' => isset($params['sandbox']),
            ],
            ['id = ?' => 1]
        );
    }

    /**
     * Gets the stored PayPal params.
     *
     * @return array
     */
    public function getPaypalParams()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_PAYPAL),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Gets the stored TPV Redsys params.
     *
     * @return array
     */
    public function getTpvRedsysParams()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_TPV_REDSYS),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Save the tpv Redsys configuration params.
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveTpvRedsysParams(array $params)
    {
        return Database::update(
            Database::get_main_table(self::TABLE_TPV_REDSYS),
            [
                'merchantcode' => $params['merchantcode'],
                'terminal' => $params['terminal'],
                'currency' => $params['currency'],
                'kc' => $params['kc'],
                'url_redsys' => $params['url_redsys'],
                'url_redsys_sandbox' => $params['url_redsys_sandbox'],
                'sandbox' => isset($params['sandbox']),
            ],
            ['id = ?' => 1]
        );
    }

    /**
     * Save Stripe configuration params.
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveStripeParameters(array $params)
    {
        return Database::update(
            Database::get_main_table(self::TABLE_STRIPE),
            [
                'account_id' => $params['account_id'],
                'secret_key' => $params['secret_key'],
                'endpoint_secret' => $params['endpoint_secret'],
            ],
            ['id = ?' => 1]
        );
    }

    /**
     * Gets the stored Stripe params.
     *
     * @return array
     */
    public function getStripeParams()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_STRIPE),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Save a transfer account information.
     *
     * @param array $params The transfer account
     *
     * @return int Rows affected. Otherwise, return false
     */
    public function saveTransferAccount(array $params)
    {
        return Database::insert(
            Database::get_main_table(self::TABLE_TRANSFER),
            [
                'name' => $params['tname'],
                'account' => $params['taccount'],
                'swift' => $params['tswift'],
            ]
        );
    }

    /**
     * Save email message information in transfer.
     *
     * @param array $params The transfer message
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveTransferInfoEmail(array $params)
    {
        return Database::update(
            Database::get_main_table(self::TABLE_GLOBAL_CONFIG),
            ['info_email_extra' => $params['tinfo_email_extra']],
            ['id = ?' => 1]
        );
    }

    /**
     * Gets message information for transfer email.
     *
     * @return array
     */
    public function getTransferInfoExtra()
    {
        return Database::select(
            'info_email_extra AS tinfo_email_extra',
            Database::get_main_table(self::TABLE_GLOBAL_CONFIG),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Get a list of transfer accounts.
     *
     * @return array
     */
    public function getTransferAccounts()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_TRANSFER)
        );
    }

    /**
     * Remove a transfer account.
     *
     * @param int $id The transfer account ID
     *
     * @return int Rows affected. Otherwise return false
     */
    public function deleteTransferAccount(int $id)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_TRANSFER),
            ['id = ?' => $id]
        );
    }

    /**
     * Get registered item data.
     *
     * @param int $itemId The item ID
     *
     * @return array
     */
    public function getItem(int $itemId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_ITEM),
            [
                'where' => ['id = ?' => $itemId],
            ],
            'first'
        );
    }

    /**
     * Get the item data.
     *
     * @param int $productId The item ID
     * @param int $itemType  The item type
     *
     * @return array
     */
    public function getItemByProduct(int $productId, int $itemType, array $coupon = null)
    {
        $buyItemTable = Database::get_main_table(self::TABLE_ITEM);
        $buyCurrencyTable = Database::get_main_table(self::TABLE_CURRENCY);

        $fakeItemFrom = "
            $buyItemTable i
            INNER JOIN $buyCurrencyTable c
                ON i.currency_id = c.id
        ";

        $product = Database::select(
            ['i.*', 'c.iso_code'],
            $fakeItemFrom,
            [
                'where' => [
                    'i.product_id = ? AND i.product_type = ?' => [
                        $productId,
                        $itemType,
                    ],
                ],
            ],
            'first'
        );

        if (empty($product)) {
            return false;
        }

        $this->setPriceSettings($product, self::TAX_APPLIES_TO_ONLY_COURSE, $coupon);

        return $product;
    }

    /**
     * Get registered item data.
     *
     * @param int $itemId      The product ID
     * @param int $productType The product type
     *
     * @return array
     */
    public function getSubscriptionItem(int $itemId, int $productType)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION),
            [
                'where' => ['product_id = ? AND product_type = ?' => [
                        $itemId,
                        $productType,
                    ],
                ],
            ],
            'first'
        );
    }

    /**
     * Get the item data.
     *
     * @param int   $productId The item ID
     * @param int   $itemType  The item type
     * @param array $coupon    Array with at least 'discount_type' and 'discount_amount' elements
     *
     * @return array
     */
    public function getSubscriptionItemByProduct(int $productId, int $itemType, array $coupon = null)
    {
        $buySubscriptionItemTable = Database::get_main_table(self::TABLE_SUBSCRIPTION);
        $buyCurrencyTable = Database::get_main_table(self::TABLE_CURRENCY);

        $fakeItemFrom = "
            $buySubscriptionItemTable s
            INNER JOIN $buyCurrencyTable c
                ON s.currency_id = c.id
        ";

        $item = Database::select(
            ['s.*', 'c.iso_code'],
            $fakeItemFrom,
            [
                'where' => [
                    's.product_id = ? AND s.product_type = ?' => [
                        $productId,
                        $itemType,
                    ],
                ],
            ],
            'first'
        );

        if (empty($item)) {
            return false;
        }

        $this->setPriceSettings($item, self::TAX_APPLIES_TO_ONLY_COURSE, $coupon);

        return $item;
    }

    /**
     * Get the item data.
     *
     * @param int $productId The item ID
     * @param int $itemType  The item type
     *
     * @return array
     */
    public function getSubscriptionsItemsByProduct(int $productId, int $itemType)
    {
        $buySubscriptionItemTable = Database::get_main_table(self::TABLE_SUBSCRIPTION);
        $buyCurrencyTable = Database::get_main_table(self::TABLE_CURRENCY);

        $fakeItemFrom = "
            $buySubscriptionItemTable s
            INNER JOIN $buyCurrencyTable c
                ON s.currency_id = c.id
        ";

        $items = Database::select(
            ['s.*', 'c.iso_code'],
            $fakeItemFrom,
            [
                'where' => [
                    's.product_id = ? AND s.product_type = ?' => [
                        $productId,
                        $itemType,
                    ],
                ],
            ]
        );

        for ($i = 0; $i < count($items); $i++) {
            $this->setPriceSettings($items[$i], self::TAX_APPLIES_TO_ONLY_COURSE);
        }

        if (empty($items)) {
            return false;
        }

        return $items;
    }

    /**
     * Get registered item data by duration.
     *
     * @param int $duration The subscription duration
     *
     * @return array
     */
    public function getSubscriptionsItemsByDuration(int $duration)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION),
            [
                'where' => [
                    'duration = ?' => [$duration],
                ],
            ]
        );
    }

    /**
     * List courses details from the configuration page.
     *
     * @return array
     */
    public function getCourseList(int $first, int $maxResults)
    {
        return $this->getCourses($first, $maxResults);
    }

    /**
     * Lists current user session details, including each session course details.
     *
     * It can return the number of rows when $typeResult is 'count'.
     *
     * @param string $name       Optional. The name filter.
     * @param int    $min        Optional. The minimum price filter.
     * @param int    $max        Optional. The maximum price filter.
     * @param string $typeResult Optional. 'all', 'first' or 'count'.
     *
     * @return array|int
     */
    public function getCatalogSessionList(int $start, int $end, string $name = null, int $min = 0, int $max = 0, string $typeResult = 'all', $sessionCategory = 0)
    {
        $sessions = $this->filterSessionList($start, $end, $name, $min, $max, $typeResult, $sessionCategory);

        if ($typeResult === 'count') {
            return $sessions;
        }

        $sessionCatalog = [];
        // loop through all sessions
        foreach ($sessions as $session) {
            $sessionCourses = $session->getCourses();

            if (empty($sessionCourses)) {
                continue;
            }

            $item = $this->getItemByProduct(
                $session->getId(),
                self::PRODUCT_TYPE_SESSION
            );

            if (empty($item)) {
                continue;
            }

            $sessionData = $this->getSessionInfo($session->getId());
            $sessionData['coach'] = $session->getGeneralCoach()->getCompleteName();
            $sessionData['enrolled'] = $this->getUserStatusForSession(
                api_get_user_id(),
                $session
            );
            $sessionData['courses'] = [];

            foreach ($sessionCourses as $sessionCourse) {
                $course = $sessionCourse->getCourse();

                $sessionCourseData = [
                    'title' => $course->getTitle(),
                    'coaches' => [],
                ];

                $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus(
                    $course,
                    Chamilo\CoreBundle\Entity\Session::COACH
                );

                foreach ($userCourseSubscriptions as $userCourseSubscription) {
                    $user = $userCourseSubscription->getUser();
                    $sessionCourseData['coaches'][] = $user->getCompleteName();
                }
                $sessionData['courses'][] = $sessionCourseData;
            }

            $sessionCatalog[] = $sessionData;
        }

        return $sessionCatalog;
    }

    /**
     * Lists current user course details.
     *
     * @param string $name Optional. The name filter
     * @param int    $min  Optional. The minimum price filter
     * @param int    $max  Optional. The maximum price filter
     *
     * @return array|int
     */
    public function getCatalogCourseList(int $first, int $pageSize, string $name = null, int $min = 0, int $max = 0, string $typeResult = 'all')
    {
        $courses = $this->filterCourseList($first, $pageSize, $name, $min, $max, $typeResult);

        if ($typeResult === 'count') {
            return $courses;
        }

        if (empty($courses)) {
            return [];
        }

        $courseCatalog = [];
        foreach ($courses as $course) {
            $item = $this->getItemByProduct(
                $course->getId(),
                self::PRODUCT_TYPE_COURSE
            );

            if (empty($item)) {
                continue;
            }

            $courseItem = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'course_img' => null,
                'item' => $item,
                'teachers' => [],
                'enrolled' => $this->getUserStatusForCourse(api_get_user_id(), $course),
            ];

            foreach ($course->getTeachers() as $courseUser) {
                $teacher = $courseUser->getUser();
                $courseItem['teachers'][] = $teacher->getCompleteName();
            }

            // Check images
            $possiblePath = api_get_path(SYS_COURSE_PATH);
            $possiblePath .= $course->getDirectory();
            $possiblePath .= '/course-pic.png';

            if (file_exists($possiblePath)) {
                $courseItem['course_img'] = api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/course-pic.png';
            }
            $courseCatalog[] = $courseItem;
        }

        return $courseCatalog;
    }

    /**
     * Lists current user subscription session details, including each session course details.
     *
     * It can return the number of rows when $typeResult is 'count'.
     *
     * @param int    $start           Pagination start.
     * @param int    $end             Pagination end.
     * @param string $name            Optional. The name filter.
     * @param string $typeResult      Optional. 'all', 'first' or 'count'.
     * @param int    $sessionCategory Optional. Session category id
     *
     * @return array|int
     */
    public function getCatalogSubscriptionSessionList(int $start, int $end, string $name = null, string $typeResult = 'all', int $sessionCategory = 0)
    {
        $sessions = $this->filterSubscriptionSessionList($start, $end, $name, $typeResult, $sessionCategory);

        if ($typeResult === 'count') {
            return $sessions;
        }

        $sessionCatalog = [];
        // loop through all sessions
        foreach ($sessions as $session) {
            $sessionCourses = $session->getCourses();

            if (empty($sessionCourses)) {
                continue;
            }

            $item = $this->getSubscriptionItemByProduct(
                $session->getId(),
                self::PRODUCT_TYPE_SESSION
            );

            if (empty($item)) {
                continue;
            }

            $sessionData = $this->getSubscriptionSessionInfo($session->getId());
            $sessionData['coach'] = $session->getGeneralCoach()->getCompleteName();
            $sessionData['enrolled'] = $this->getUserStatusForSubscriptionSession(
                api_get_user_id(),
                $session
            );
            $sessionData['courses'] = [];

            foreach ($sessionCourses as $sessionCourse) {
                $course = $sessionCourse->getCourse();

                $sessionCourseData = [
                    'title' => $course->getTitle(),
                    'coaches' => [],
                ];

                $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus(
                    $course,
                    Chamilo\CoreBundle\Entity\Session::COACH
                );

                foreach ($userCourseSubscriptions as $userCourseSubscription) {
                    $user = $userCourseSubscription->getUser();
                    $sessionCourseData['coaches'][] = $user->getCompleteName();
                }
                $sessionData['courses'][] = $sessionCourseData;
            }

            $sessionCatalog[] = $sessionData;
        }

        return $sessionCatalog;
    }

    /**
     * Lists current user subscription course details.
     *
     * @param string $typeResult Optional. 'all', 'first' or 'count'.
     *
     * @return array|int
     */
    public function getCatalogSubscriptionCourseList(int $first, int $pageSize, string $name = null, string $typeResult = 'all')
    {
        $courses = $this->filterSubscriptionCourseList($first, $pageSize, $name, $typeResult);

        if ($typeResult === 'count') {
            return $courses;
        }

        if (empty($courses)) {
            return [];
        }

        $courseCatalog = [];
        foreach ($courses as $course) {
            $item = $this->getSubscriptionItemByProduct(
                $course->getId(),
                self::PRODUCT_TYPE_COURSE
            );

            if (empty($item)) {
                continue;
            }

            $courseItem = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'course_img' => null,
                'item' => $item,
                'teachers' => [],
                'enrolled' => $this->getUserStatusForSubscriptionCourse(api_get_user_id(), $course),
            ];

            foreach ($course->getTeachers() as $courseUser) {
                $teacher = $courseUser->getUser();
                $courseItem['teachers'][] = $teacher->getCompleteName();
            }

            // Check images
            $possiblePath = api_get_path(SYS_COURSE_PATH);
            $possiblePath .= $course->getDirectory();
            $possiblePath .= '/course-pic.png';

            if (file_exists($possiblePath)) {
                $courseItem['course_img'] = api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/course-pic.png';
            }
            $courseCatalog[] = $courseItem;
        }

        return $courseCatalog;
    }

    public function getPriceWithCurrencyFromIsoCode(float $price, string $isoCode): string
    {
        $useSymbol = $this->get('use_currency_symbol') === 'true';

        $result = $isoCode.' '.$price;
        if ($useSymbol) {
            if ($isoCode === 'BRL') {
                $symbol = 'R$';
            } else {
                $symbol = Symfony\Component\Intl\Intl::getCurrencyBundle()->getCurrencySymbol($isoCode);
            }
            $result = $symbol.' '.$price;
        }

        return $result;
    }

    /**
     * Get course info.
     *
     * @return array
     */
    public function getCourseInfo(int $courseId, array $coupon = null)
    {
        $entityManager = Database::getManager();
        $course = $entityManager->find('ChamiloCoreBundle:Course', $courseId);

        if (empty($course)) {
            return [];
        }

        $item = $this->getItemByProduct(
            $course->getId(),
            self::PRODUCT_TYPE_COURSE,
            $coupon
        );

        if (empty($item)) {
            return [];
        }

        $courseDescription = $entityManager->getRepository('ChamiloCourseBundle:CCourseDescription')
            ->findOneBy(
                [
                    'cId' => $course->getId(),
                    'sessionId' => 0,
                ],
                [
                    'descriptionType' => 'ASC',
                ]
            );

        $globalParameters = $this->getGlobalParameters();
        $courseInfo = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $courseDescription ? $courseDescription->getContent() : null,
            'code' => $course->getCode(),
            'visual_code' => $course->getVisualCode(),
            'teachers' => [],
            'item' => $item,
            'tax_name' => $globalParameters['tax_name'],
            'tax_enable' => $this->checkTaxEnabledInProduct(self::TAX_APPLIES_TO_ONLY_COURSE),
            'course_img' => null,
        ];

        $courseTeachers = $course->getTeachers();

        foreach ($courseTeachers as $teachers) {
            $user = $teachers->getUser();
            $teacher['id'] = $user->getId();
            $teacher['name'] = $user->getCompleteName();
            $courseInfo['teachers'][] = $teacher;
        }

        $possiblePath = api_get_path(SYS_COURSE_PATH);
        $possiblePath .= $course->getDirectory();
        $possiblePath .= '/course-pic.png';

        if (file_exists($possiblePath)) {
            $courseInfo['course_img'] = api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/course-pic.png';
        }

        return $courseInfo;
    }

    /**
     * Get session info.
     *
     * @return array
     */
    public function getSessionInfo(int $sessionId, array $coupon = null)
    {
        $entityManager = Database::getManager();
        $session = $entityManager->find('ChamiloCoreBundle:Session', $sessionId);

        if (empty($session)) {
            return [];
        }

        $item = $this->getItemByProduct(
            $session->getId(),
            self::PRODUCT_TYPE_SESSION,
            $coupon
        );

        if (empty($item)) {
            return [];
        }

        $sessionDates = SessionManager::parseSessionDates(
            [
                'display_start_date' => $session->getDisplayStartDate(),
                'display_end_date' => $session->getDisplayEndDate(),
                'access_start_date' => $session->getAccessStartDate(),
                'access_end_date' => $session->getAccessEndDate(),
                'coach_access_start_date' => $session->getCoachAccessStartDate(),
                'coach_access_end_date' => $session->getCoachAccessEndDate(),
            ]
        );

        $globalParameters = $this->getGlobalParameters();
        $sessionInfo = [
            'id' => $session->getId(),
            'name' => $session->getName(),
            'description' => $session->getDescription(),
            'dates' => $sessionDates,
            'courses' => [],
            'tax_name' => $globalParameters['tax_name'],
            'tax_enable' => $this->checkTaxEnabledInProduct(self::TAX_APPLIES_TO_ONLY_SESSION),
            'image' => null,
            'nbrCourses' => $session->getNbrCourses(),
            'nbrUsers' => $session->getNbrUsers(),
            'item' => $item,
            'duration' => $session->getDuration(),
        ];

        $fieldValue = new ExtraFieldValue('session');
        $sessionImage = $fieldValue->get_values_by_handler_and_field_variable(
            $session->getId(),
            'image'
        );

        if (!empty($sessionImage)) {
            $sessionInfo['image'] = api_get_path(WEB_UPLOAD_PATH).$sessionImage['value'];
        }

        $sessionCourses = $session->getCourses();
        foreach ($sessionCourses as $sessionCourse) {
            $course = $sessionCourse->getCourse();
            $sessionCourseData = [
                'title' => $course->getTitle(),
                'coaches' => [],
            ];

            $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus(
                $course,
                Chamilo\CoreBundle\Entity\Session::COACH
            );

            foreach ($userCourseSubscriptions as $userCourseSubscription) {
                $user = $userCourseSubscription->getUser();
                $coaches['id'] = $user->getUserId();
                $coaches['name'] = $user->getCompleteName();
                $sessionCourseData['coaches'][] = $coaches;
            }

            $sessionInfo['courses'][] = $sessionCourseData;
        }

        return $sessionInfo;
    }

    /**
     * Get course info.
     *
     * @return array
     */
    public function getSubscriptionCourseInfo(int $courseId, array $coupon = null)
    {
        $entityManager = Database::getManager();
        $course = $entityManager->find('ChamiloCoreBundle:Course', $courseId);

        if (empty($course)) {
            return [];
        }

        $item = $this->getSubscriptionItemByProduct(
            $course->getId(),
            self::PRODUCT_TYPE_COURSE,
            $coupon
        );

        if (empty($item)) {
            return [];
        }

        $courseDescription = $entityManager->getRepository('ChamiloCourseBundle:CCourseDescription')
            ->findOneBy(
                [
                    'cId' => $course->getId(),
                    'sessionId' => 0,
                ],
                [
                    'descriptionType' => 'ASC',
                ]
            );

        $globalParameters = $this->getGlobalParameters();
        $courseInfo = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $courseDescription ? $courseDescription->getContent() : null,
            'code' => $course->getCode(),
            'visual_code' => $course->getVisualCode(),
            'teachers' => [],
            'item' => $item,
            'tax_name' => $globalParameters['tax_name'],
            'tax_enable' => $this->checkTaxEnabledInProduct(self::TAX_APPLIES_TO_ONLY_COURSE),
            'course_img' => null,
        ];

        $courseTeachers = $course->getTeachers();

        foreach ($courseTeachers as $teachers) {
            $user = $teachers->getUser();
            $teacher['id'] = $user->getId();
            $teacher['name'] = $user->getCompleteName();
            $courseInfo['teachers'][] = $teacher;
        }

        $possiblePath = api_get_path(SYS_COURSE_PATH);
        $possiblePath .= $course->getDirectory();
        $possiblePath .= '/course-pic.png';

        if (file_exists($possiblePath)) {
            $courseInfo['course_img'] = api_get_path(WEB_COURSE_PATH).$course->getDirectory().'/course-pic.png';
        }

        return $courseInfo;
    }

    /**
     * Get session info.
     *
     * @param array $sessionId The session ID
     *
     * @return array
     */
    public function getSubscriptionSessionInfo(int $sessionId, array $coupon = null)
    {
        $entityManager = Database::getManager();
        $session = $entityManager->find('ChamiloCoreBundle:Session', $sessionId);

        if (empty($session)) {
            return [];
        }

        $item = $this->getSubscriptionItemByProduct(
            $session->getId(),
            self::PRODUCT_TYPE_SESSION,
            $coupon
        );

        if (empty($item)) {
            return [];
        }

        $sessionDates = SessionManager::parseSessionDates(
            [
                'display_start_date' => $session->getDisplayStartDate(),
                'display_end_date' => $session->getDisplayEndDate(),
                'access_start_date' => $session->getAccessStartDate(),
                'access_end_date' => $session->getAccessEndDate(),
                'coach_access_start_date' => $session->getCoachAccessStartDate(),
                'coach_access_end_date' => $session->getCoachAccessEndDate(),
            ]
        );

        $globalParameters = $this->getGlobalParameters();
        $sessionInfo = [
            'id' => $session->getId(),
            'name' => $session->getName(),
            'description' => $session->getDescription(),
            'dates' => $sessionDates,
            'courses' => [],
            'tax_name' => $globalParameters['tax_name'],
            'tax_enable' => $this->checkTaxEnabledInProduct(self::TAX_APPLIES_TO_ONLY_SESSION),
            'image' => null,
            'nbrCourses' => $session->getNbrCourses(),
            'nbrUsers' => $session->getNbrUsers(),
            'item' => $item,
            'duration' => $session->getDuration(),
        ];

        $fieldValue = new ExtraFieldValue('session');
        $sessionImage = $fieldValue->get_values_by_handler_and_field_variable(
            $session->getId(),
            'image'
        );

        if (!empty($sessionImage)) {
            $sessionInfo['image'] = api_get_path(WEB_UPLOAD_PATH).$sessionImage['value'];
        }

        $sessionCourses = $session->getCourses();
        foreach ($sessionCourses as $sessionCourse) {
            $course = $sessionCourse->getCourse();
            $sessionCourseData = [
                'title' => $course->getTitle(),
                'coaches' => [],
            ];

            $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus(
                $course,
                Chamilo\CoreBundle\Entity\Session::COACH
            );

            foreach ($userCourseSubscriptions as $userCourseSubscription) {
                $user = $userCourseSubscription->getUser();
                $coaches['id'] = $user->getUserId();
                $coaches['name'] = $user->getCompleteName();
                $sessionCourseData['coaches'][] = $coaches;
            }

            $sessionInfo['courses'][] = $sessionCourseData;
        }

        return $sessionInfo;
    }

    /**
     * Register a sale.
     *
     * @param int    $itemId      The product ID
     * @param int    $paymentType The payment type
     * @param string $couponId    The coupon ID
     *
     * @return bool
     */
    public function registerSale(int $itemId, int $paymentType, string $couponId = null)
    {
        if (!in_array(
                $paymentType,
                [
                    self::PAYMENT_TYPE_PAYPAL,
                    self::PAYMENT_TYPE_TRANSFER,
                    self::PAYMENT_TYPE_CULQI,
                    self::PAYMENT_TYPE_TPV_REDSYS,
                    self::PAYMENT_TYPE_STRIPE,
                    self::PAYMENT_TYPE_TPV_CECABANK,
                ]
            )
        ) {
            return false;
        }

        $entityManager = Database::getManager();
        $item = $this->getItem($itemId);

        if (empty($item)) {
            return false;
        }

        $productName = '';
        if ($item['product_type'] == self::PRODUCT_TYPE_COURSE) {
            $course = $entityManager->find('ChamiloCoreBundle:Course', $item['product_id']);

            if (empty($course)) {
                return false;
            }

            $productName = $course->getTitle();
        } elseif ($item['product_type'] == self::PRODUCT_TYPE_SESSION) {
            $session = $entityManager->find('ChamiloCoreBundle:Session', $item['product_id']);

            if (empty($session)) {
                return false;
            }

            $productName = $session->getName();
        }

        if ($couponId != null) {
            $coupon = $this->getCoupon($couponId, $item['product_type'], $item['product_id']);
        }

        $couponDiscount = 0;
        $priceWithoutDiscount = 0;
        if ($coupon != null) {
            if ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_AMOUNT) {
                $couponDiscount = $coupon['discount_amount'];
            } elseif ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_PERCENTAGE) {
                $couponDiscount = ($item['price'] * $coupon['discount_amount']) / 100;
            }
            $priceWithoutDiscount = $item['price'];
        }
        $item['price'] = $item['price'] - $couponDiscount;
        $price = $item['price'];
        $priceWithoutTax = null;
        $taxPerc = null;
        $taxAmount = 0;
        $taxEnable = $this->get('tax_enable') === 'true';
        $globalParameters = $this->getGlobalParameters();
        $taxAppliesTo = $globalParameters['tax_applies_to'];

        if ($taxEnable &&
            (
                $taxAppliesTo == self::TAX_APPLIES_TO_ALL ||
                ($taxAppliesTo == self::TAX_APPLIES_TO_ONLY_COURSE && $item['product_type'] == self::PRODUCT_TYPE_COURSE) ||
                ($taxAppliesTo == self::TAX_APPLIES_TO_ONLY_SESSION && $item['product_type'] == self::PRODUCT_TYPE_SESSION)
            )
        ) {
            $priceWithoutTax = $item['price'];
            $globalTaxPerc = $globalParameters['global_tax_perc'];
            $precision = 2;
            $taxPerc = is_null($item['tax_perc']) ? $globalTaxPerc : $item['tax_perc'];
            $taxAmount = round($priceWithoutTax * $taxPerc / 100, $precision);
            $price = $priceWithoutTax + $taxAmount;
        }

        $values = [
            'reference' => $this->generateReference(
                api_get_user_id(),
                $item['product_type'],
                $item['product_id']
            ),
            'currency_id' => $item['currency_id'],
            'date' => api_get_utc_datetime(),
            'user_id' => api_get_user_id(),
            'product_type' => $item['product_type'],
            'product_name' => $productName,
            'product_id' => $item['product_id'],
            'price' => $price,
            'price_without_tax' => $priceWithoutTax,
            'tax_perc' => $taxPerc,
            'tax_amount' => $taxAmount,
            'status' => self::SALE_STATUS_PENDING,
            'payment_type' => $paymentType,
            'price_without_discount' => $priceWithoutDiscount,
            'discount_amount' => $couponDiscount,
        ];

        return Database::insert(self::TABLE_SALE, $values);
    }

    /**
     * Update the sale reference.
     *
     * @return bool
     */
    public function updateSaleReference(int $saleId, string $saleReference)
    {
        $saleTable = Database::get_main_table(self::TABLE_SALE);

        return Database::update(
            $saleTable,
            ['reference' => $saleReference],
            ['id = ?' => $saleId]
        );
    }

    /**
     * Get sale data by ID.
     *
     * @return array
     */
    public function getSale(int $saleId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SALE),
            [
                'where' => ['id = ?' => (int) $saleId],
            ],
            'first'
        );
    }

    /**
     * Get sale data by reference.
     *
     * @return array
     */
    public function getSaleFromReference(string $reference)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SALE),
            [
                'where' => ['reference = ?' => $reference],
            ],
            'first'
        );
    }

    /**
     * Get a list of sales by the payment type.
     *
     * @param int $paymentType The payment type to filter (default : Paypal)
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByPaymentType(int $paymentType = self::PAYMENT_TYPE_PAYPAL)
    {
        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    's.payment_type = ? AND s.status = ?' => [
                        $paymentType,
                        self::SALE_STATUS_COMPLETED,
                    ],
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get data of sales.
     *
     * @param int $saleId    The sale id
     * @param int $isService Check if a service
     *
     * @return array The sale data
     */
    public function getDataSaleInvoice(int $saleId, int $isService)
    {
        if ($isService) {
            $sale = $this->getServiceSale($saleId);
            $sale['reference'] = $sale['reference'];
            $sale['product_name'] = $sale['service']['name'];
            $sale['payment_type'] = $sale['payment_type'];
            $sale['user_id'] = $sale['buyer']['id'];
            $sale['date'] = $sale['buy_date'];
        } else {
            $sale = $this->getSale($saleId);
        }

        return $sale;
    }

    /**
     * Get data of invoice.
     *
     * @param int $saleId    The sale id
     * @param int $isService Check if a service
     *
     * @return array The invoice data
     */
    public function getDataInvoice(int $saleId, int $isService)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_INVOICE),
            [
                'where' => [
                    'sale_id = ? AND ' => (int) $saleId,
                    'is_service = ?' => (int) $isService,
                ],
            ],
            'first'
        );
    }

    /**
     * Get invoice numbering.
     *
     * @param int $saleId    The sale id
     * @param int $isService Check if a service
     *
     * @return string
     */
    public function getNumInvoice(int $saleId, int $isService)
    {
        $dataInvoice = $this->getDataInvoice($saleId, $isService);
        if (empty($dataInvoice)) {
            return '-';
        }

        return $dataInvoice['serie'].$dataInvoice['year'].'/'.$dataInvoice['num_invoice'];
    }

    /**
     * Get currency data by ID.
     *
     * @param int $currencyId The currency ID
     *
     * @return array
     */
    public function getCurrency(int $currencyId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_CURRENCY),
            [
                'where' => ['id = ?' => $currencyId],
            ],
            'first'
        );
    }

    /**
     * Complete sale process. Update sale status to completed.
     *
     * @return bool
     */
    public function completeSale(int $saleId)
    {
        $sale = $this->getSale($saleId);

        if ($sale['status'] == self::SALE_STATUS_COMPLETED) {
            return true;
        }

        $saleIsCompleted = false;
        switch ($sale['product_type']) {
            case self::PRODUCT_TYPE_COURSE:
                $course = api_get_course_info_by_id($sale['product_id']);
                $saleIsCompleted = CourseManager::subscribeUser($sale['user_id'], $course['code']);
                break;
            case self::PRODUCT_TYPE_SESSION:
                SessionManager::subscribeUsersToSession(
                    $sale['product_id'],
                    [$sale['user_id']],
                    api_get_session_visibility($sale['product_id']),
                    false
                );

                $saleIsCompleted = true;
                break;
        }

        if ($saleIsCompleted) {
            $this->updateSaleStatus($sale['id'], self::SALE_STATUS_COMPLETED);
            if ($this->get('invoicing_enable') === 'true') {
                $this->setInvoice($sale['id']);
            }
        }

        return $saleIsCompleted;
    }

    /**
     * Update sale status to canceled.
     *
     * @param int $saleId The sale ID
     */
    public function cancelSale(int $saleId)
    {
        $this->updateSaleStatus($saleId, self::SALE_STATUS_CANCELED);
    }

    /**
     * Get payment types.
     */
    public function getPaymentTypes(bool $onlyActive = false): array
    {
        $types = [
            self::PAYMENT_TYPE_PAYPAL => 'PayPal',
            self::PAYMENT_TYPE_TRANSFER => $this->get_lang('BankTransfer'),
            self::PAYMENT_TYPE_CULQI => 'Culqi',
            self::PAYMENT_TYPE_TPV_REDSYS => $this->get_lang('TpvPayment'),
            self::PAYMENT_TYPE_STRIPE => 'Stripe',
            self::PAYMENT_TYPE_TPV_CECABANK => $this->get_lang('TpvCecabank'),
        ];

        if (!$onlyActive) {
            return $types;
        }

        if ($this->get('paypal_enable') !== 'true') {
            unset($types[BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL]);
        }

        if ($this->get('transfer_enable') !== 'true') {
            unset($types[BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER]);
        }

        if ($this->get('culqi_enable') !== 'true') {
            unset($types[BuyCoursesPlugin::PAYMENT_TYPE_CULQI]);
        }

        if ($this->get('tpv_redsys_enable') !== 'true'
            || !file_exists(api_get_path(SYS_PLUGIN_PATH).'buycourses/resources/apiRedsys.php')
        ) {
            unset($types[BuyCoursesPlugin::PAYMENT_TYPE_TPV_REDSYS]);
        }

        if ($this->get('stripe_enable') !== 'true') {
            unset($types[BuyCoursesPlugin::PAYMENT_TYPE_STRIPE]);
        }

        if ($this->get('cecabank_enable') !== 'true') {
            unset($types[BuyCoursesPlugin::PAYMENT_TYPE_TPV_CECABANK]);
        }

        return $types;
    }

    /**
     * Register a invoice.
     *
     * @param int $saleId    The sale ID
     * @param int $isService The service type to filter (default : 0)
     */
    public function setInvoice(int $saleId, int $isService = 0)
    {
        $invoiceTable = Database::get_main_table(self::TABLE_INVOICE);
        $year = date('Y');

        $globalParameters = $this->getGlobalParameters();
        $numInvoice = $globalParameters['next_number_invoice'];
        $serie = $globalParameters['invoice_series'];

        if (empty($numInvoice)) {
            $item = Database::select(
                ['MAX(num_invoice) AS num_invoice'],
                $invoiceTable,
                [
                    'where' => ['year = ?' => $year],
                ],
                'first'
            );

            $numInvoice = 1;
            if ($item !== false) {
                $numInvoice = (int) ($item['num_invoice'] + 1);
            }
        } else {
            Database::update(
                Database::get_main_table(self::TABLE_GLOBAL_CONFIG),
                ['next_number_invoice' => 0],
                ['id = ?' => 1]
            );
        }

        Database::insert(
            $invoiceTable,
            [
                'sale_id' => $saleId,
                'is_service' => $isService,
                'num_invoice' => $numInvoice,
                'year' => $year,
                'serie' => $serie,
                'date_invoice' => api_get_utc_datetime(),
            ]
        );

        // Record invoice in the sales table
        $table = Database::get_main_table(self::TABLE_SALE);
        if (!empty($isService)) {
            $table = Database::get_main_table(self::TABLE_SERVICES_SALE);
        }

        Database::update(
            $table,
            ['invoice' => 1],
            ['id = ?' => $saleId]
        );
    }

    /**
     * Get Tax's types.
     *
     * @return array
     */
    public function getTaxAppliesTo()
    {
        return [
            self::TAX_APPLIES_TO_ALL => $this->get_lang('AllCoursesSessionsAndServices'),
            self::TAX_APPLIES_TO_ONLY_COURSE => $this->get_lang('OnlyCourses'),
            self::TAX_APPLIES_TO_ONLY_SESSION => $this->get_lang('OnlySessions'),
            self::TAX_APPLIES_TO_ONLY_SERVICES => $this->get_lang('OnlyServices'),
        ];
    }

    /**
     * Get a list of sales by the status.
     *
     * @param int $status The status to filter
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByStatus(int $status = self::SALE_STATUS_PENDING)
    {
        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => ['s.status = ?' => $status],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get the list statuses for sales.
     *
     * @throws Exception
     *
     * @return array
     */
    public function getSaleListReport(string $dateStart = null, string $dateEnd = null)
    {
        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";
        $list = Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'order' => 'id DESC',
            ]
        );
        $listExportTemp = [];
        $listExport = [];
        $textStatus = null;
        $paymentTypes = $this->getPaymentTypes();
        $productTypes = $this->getProductTypes();
        foreach ($list as $item) {
            $statusSaleOrder = $item['status'];
            switch ($statusSaleOrder) {
                case 0:
                    $textStatus = $this->get_lang('SaleStatusPending');
                    break;
                case 1:
                    $textStatus = $this->get_lang('SaleStatusCompleted');
                    break;
                case -1:
                    $textStatus = $this->get_lang('SaleStatusCanceled');
                    break;
            }
            $dateFilter = new DateTime($item['date']);
            $listExportTemp[] = [
                'id' => $item['id'],
                'reference' => $item['reference'],
                'status' => $textStatus,
                'status_filter' => $item['status'],
                'date' => $dateFilter->format('Y-m-d'),
                'order_time' => $dateFilter->format('H:i:s'),
                'price' => $item['iso_code'].' '.$item['price'],
                'product_type' => $productTypes[$item['product_type']],
                'product_name' => $item['product_name'],
                'payment_type' => $paymentTypes[$item['payment_type']],
                'complete_user_name' => api_get_person_name($item['firstname'], $item['lastname']),
                'email' => $item['email'],
            ];
        }
        $listExport[] = [
            get_lang('Number'),
            $this->get_lang('OrderStatus'),
            $this->get_lang('OrderDate'),
            $this->get_lang('OrderTime'),
            $this->get_lang('PaymentMethod'),
            $this->get_lang('SalePrice'),
            $this->get_lang('ProductType'),
            $this->get_lang('ProductName'),
            $this->get_lang('UserName'),
            get_lang('Email'),
        ];
        //Validation Export
        $dateStart = strtotime($dateStart);
        $dateEnd = strtotime($dateEnd);
        foreach ($listExportTemp as $item) {
            $dateFilter = strtotime($item['date']);
            if (($dateFilter >= $dateStart) && ($dateFilter <= $dateEnd)) {
                $listExport[] = [
                    'id' => $item['id'],
                    'status' => $item['status'],
                    'date' => $item['date'],
                    'order_time' => $item['order_time'],
                    'payment_type' => $item['payment_type'],
                    'price' => $item['price'],
                    'product_type' => $item['product_type'],
                    'product_name' => $item['product_name'],
                    'complete_user_name' => $item['complete_user_name'],
                    'email' => $item['email'],
                ];
            }
        }

        return $listExport;
    }

    /**
     * Get the statuses for sales.
     *
     * @return array
     */
    public function getSaleStatuses()
    {
        return [
            self::SALE_STATUS_CANCELED => $this->get_lang('SaleStatusCanceled'),
            self::SALE_STATUS_PENDING => $this->get_lang('SaleStatusPending'),
            self::SALE_STATUS_COMPLETED => $this->get_lang('SaleStatusCompleted'),
        ];
    }

    /**
     * Get the statuses for Payouts.
     *
     * @return array
     */
    public function getPayoutStatuses()
    {
        return [
            self::PAYOUT_STATUS_CANCELED => $this->get_lang('PayoutStatusCanceled'),
            self::PAYOUT_STATUS_PENDING => $this->get_lang('PayoutStatusPending'),
            self::PAYOUT_STATUS_COMPLETED => $this->get_lang('PayoutStatusCompleted'),
        ];
    }

    /**
     * Get the list of product types.
     *
     * @return array
     */
    public function getProductTypes()
    {
        return [
            self::PRODUCT_TYPE_COURSE => get_lang('Course'),
            self::PRODUCT_TYPE_SESSION => get_lang('Session'),
        ];
    }

    /**
     * Get the list of service types.
     *
     * @return array
     */
    public function getServiceTypes()
    {
        return [
            self::SERVICE_TYPE_USER => get_lang('User'),
            self::SERVICE_TYPE_COURSE => get_lang('Course'),
            self::SERVICE_TYPE_SESSION => get_lang('Session'),
            self::SERVICE_TYPE_LP_FINAL_ITEM => get_lang('TemplateTitleCertificate'),
        ];
    }

    /**
     * Get the list of coupon status.
     *
     * @return array
     */
    public function getCouponStatuses()
    {
        return [
            self::COUPON_STATUS_ACTIVE => $this->get_lang('CouponActive'),
            self::COUPON_STATUS_DISABLE => $this->get_lang('CouponDisabled'),
        ];
    }

    /**
     * Get the list of coupon discount types.
     *
     * @return array
     */
    public function getCouponDiscountTypes()
    {
        return [
            self::COUPON_DISCOUNT_TYPE_PERCENTAGE => $this->get_lang('CouponPercentage'),
            self::COUPON_DISCOUNT_TYPE_AMOUNT => $this->get_lang('CouponAmount'),
        ];
    }

    /**
     * Generates a random text (used for order references).
     *
     * @param int  $length    Optional. Length of characters (defaults to 6)
     * @param bool $lowercase Optional. Include lowercase characters
     * @param bool $uppercase Optional. Include uppercase characters
     * @param bool $numbers   Optional. Include numbers
     */
    public static function randomText(
        int $length = 6,
        bool $lowercase = true,
        bool $uppercase = true,
        bool $numbers = true
    ): string {
        $salt = $lowercase ? 'abchefghknpqrstuvwxyz' : '';
        $salt .= $uppercase ? 'ACDEFHKNPRSTUVWXYZ' : '';
        $salt .= $numbers ? (strlen($salt) ? '2345679' : '0123456789') : '';

        if (strlen($salt) == 0) {
            return '';
        }

        $str = '';

        srand((float) microtime() * 1000000);

        for ($i = 0; $i < $length; $i++) {
            $numbers = rand(0, strlen($salt) - 1);
            $str .= substr($salt, $numbers, 1);
        }

        return $str;
    }

    /**
     * Generates an order reference.
     *
     * @param int $userId      The user ID
     * @param int $productType The course/session type
     * @param int $productId   The course/session ID
     */
    public function generateReference(int $userId, int $productType, int $productId): string
    {
        return vsprintf(
            '%d-%d-%d-%s',
            [$userId, $productType, $productId, self::randomText()]
        );
    }

    /**
     * Get a list of sales by the user.
     *
     * @param string $term The search term
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByUser(string $term)
    {
        $term = trim($term);

        if (empty($term)) {
            return [];
        }

        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    'u.username LIKE %?% OR ' => $term,
                    'u.lastname LIKE %?% OR ' => $term,
                    'u.firstname LIKE %?%' => $term,
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get a list of sales by the user id.
     *
     * @param int $id The user id
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByUserId(int $id)
    {
        if (empty($id)) {
            return [];
        }

        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    'u.id = ? AND s.status = ?' => [(int) $id, self::SALE_STATUS_COMPLETED],
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get a list of sales by date range.
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByDate(string $dateStart, string $dateEnd)
    {
        $dateStart = trim($dateStart);
        $dateEnd = trim($dateEnd);
        if (empty($dateStart)) {
            return [];
        }
        if (empty($dateEnd)) {
            return [];
        }
        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    's.date BETWEEN ? AND ' => $dateStart,
                    ' ? ' => $dateEnd,
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get a list of sales by the user Email.
     *
     * @param string $term The search term
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByEmail(string $term)
    {
        $term = trim($term);
        if (empty($term)) {
            return [];
        }
        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    'u.email LIKE %?% ' => $term,
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Convert the course info to array with necessary course data for save item.
     *
     * @param array $defaultCurrency Optional. Currency data
     *
     * @return array
     */
    public function getCourseForConfiguration(Course $course, array $defaultCurrency = null)
    {
        $courseItem = [
            'item_id' => null,
            'course_id' => $course->getId(),
            'course_visual_code' => $course->getVisualCode(),
            'course_code' => $course->getCode(),
            'course_title' => $course->getTitle(),
            'course_directory' => $course->getDirectory(),
            'course_visibility' => $course->getVisibility(),
            'visible' => false,
            'currency' => empty($defaultCurrency) ? null : $defaultCurrency['iso_code'],
            'price' => 0.00,
            'tax_perc' => null,
        ];

        $item = $this->getItemByProduct($course->getId(), self::PRODUCT_TYPE_COURSE);

        if ($item !== false) {
            $courseItem['item_id'] = $item['id'];
            $courseItem['visible'] = true;
            $courseItem['currency'] = $item['iso_code'];
            $courseItem['price'] = $item['price'];
            $courseItem['tax_perc'] = $item['tax_perc'];
        }

        return $courseItem;
    }

    /**
     * Convert the session info to array with necessary session data for save item.
     *
     * @param Session $session         The session data
     * @param array   $defaultCurrency Optional. Currency data
     *
     * @return array
     */
    public function getSessionForConfiguration(Session $session, array $defaultCurrency = null)
    {
        $buyItemTable = Database::get_main_table(self::TABLE_ITEM);
        $buyCurrencyTable = Database::get_main_table(self::TABLE_CURRENCY);

        $fakeItemFrom = "
            $buyItemTable i
            INNER JOIN $buyCurrencyTable c ON i.currency_id = c.id
        ";

        $sessionItem = [
            'item_id' => null,
            'session_id' => $session->getId(),
            'session_name' => $session->getName(),
            'session_visibility' => $session->getVisibility(),
            'session_display_start_date' => null,
            'session_display_end_date' => null,
            'visible' => false,
            'currency' => empty($defaultCurrency) ? null : $defaultCurrency['iso_code'],
            'price' => 0.00,
            'tax_perc' => null,
        ];

        $displayStartDate = $session->getDisplayStartDate();

        if (!empty($displayStartDate)) {
            $sessionItem['session_display_start_date'] = api_format_date(
                $session->getDisplayStartDate()->format('Y-m-d h:i:s')
            );
        }

        $displayEndDate = $session->getDisplayEndDate();

        if (!empty($displayEndDate)) {
            $sessionItem['session_display_end_date'] = api_format_date(
                $session->getDisplayEndDate()->format('Y-m-d h:i:s'),
                DATE_TIME_FORMAT_LONG_24H
            );
        }

        $item = Database::select(
            ['i.*', 'c.iso_code'],
            $fakeItemFrom,
            [
                'where' => [
                    'i.product_id = ? AND ' => $session->getId(),
                    'i.product_type = ?' => self::PRODUCT_TYPE_SESSION,
                ],
            ],
            'first'
        );

        if ($item !== false) {
            $sessionItem['item_id'] = $item['id'];
            $sessionItem['visible'] = true;
            $sessionItem['currency'] = $item['iso_code'];
            $sessionItem['price'] = $item['price'];
            $sessionItem['tax_perc'] = $item['tax_perc'];
        }

        return $sessionItem;
    }

    /**
     * Get all beneficiaries for a item.
     *
     * @param int $itemId The item ID
     *
     * @return array The beneficiaries. Otherwise return false
     */
    public function getItemBeneficiaries(int $itemId)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        return Database::select(
            '*',
            $beneficiaryTable,
            [
                'where' => [
                    'item_id = ?' => $itemId,
                ],
            ]
        );
    }

    /**
     * Delete a item with its beneficiaries.
     *
     * @param int $itemId The item ID
     *
     * @return int The number of affected rows. Otherwise return false
     */
    public function deleteItem(int $itemId)
    {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $affectedRows = Database::delete(
            $itemTable,
            ['id = ?' => $itemId]
        );

        if (!$affectedRows) {
            return false;
        }

        return $this->deleteItemBeneficiaries($itemId);
    }

    /**
     * Register a item.
     *
     * @param array $itemData The item data
     *
     * @return int The item ID. Otherwise return false
     */
    public function registerItem(array $itemData)
    {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);

        return Database::insert($itemTable, $itemData);
    }

    /**
     * Update the item data by product.
     *
     * @param array $itemData    The item data to be updated
     * @param int   $productId   The product ID
     * @param int   $productType The type of product
     *
     * @return int The number of affected rows. Otherwise return false
     */
    public function updateItem(array $itemData, int $productId, int $productType)
    {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);

        return Database::update(
            $itemTable,
            $itemData,
            [
                'product_id = ? AND ' => $productId,
                'product_type' => $productType,
            ]
        );
    }

    /**
     * Remove all beneficiaries for a item.
     *
     * @param int $itemId The user ID
     *
     * @return int The number of affected rows. Otherwise return false
     */
    public function deleteItemBeneficiaries(int $itemId)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        return Database::delete(
            $beneficiaryTable,
            ['item_id = ?' => $itemId]
        );
    }

    /**
     * Register the beneficiaries users with the sale of item.
     *
     * @param int   $itemId  The item ID
     * @param array $userIds The beneficiary user ID and Teachers commissions if enabled
     */
    public function registerItemBeneficiaries(int $itemId, array $userIds)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        $this->deleteItemBeneficiaries($itemId);

        foreach ($userIds as $userId => $commissions) {
            Database::insert(
                $beneficiaryTable,
                [
                    'item_id' => $itemId,
                    'user_id' => (int) $userId,
                    'commissions' => (int) $commissions,
                ]
            );
        }
    }

    /**
     * Check if a course is valid for sale.
     *
     * @param Course $course The course
     *
     * @return bool
     */
    public function isValidCourse(Course $course)
    {
        $courses = $this->getCourses();

        foreach ($courses as $_c) {
            if ($_c->getCode() === $course->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the beneficiaries with commissions and current paypal accounts by sale.
     *
     * @param int $saleId The sale ID
     *
     * @return array
     */
    public function getBeneficiariesBySale(int $saleId)
    {
        $sale = $this->getSale($saleId);
        $item = $this->getItemByProduct($sale['product_id'], $sale['product_type']);
        $itemBeneficiaries = $this->getItemBeneficiaries($item['id']);

        return $itemBeneficiaries;
    }

    /**
     * gets all payouts.
     *
     * @param int $status   - default 0 - pending
     * @param int $payoutId - for get an individual payout if want all then false
     *
     * @return array
     */
    public function getPayouts(
        int $status = self::PAYOUT_STATUS_PENDING,
        int $payoutId = 0,
        int $userId = 0
    ) {
        $condition = ($payoutId) ? 'AND p.id = '.($payoutId) : '';
        $condition2 = ($userId) ? ' AND p.user_id = '.($userId) : '';
        $typeResult = ($condition) ? 'first' : 'all';
        $payoutsTable = Database::get_main_table(self::TABLE_PAYPAL_PAYOUTS);
        $saleTable = Database::get_main_table(self::TABLE_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $extraFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $paypalExtraField = Database::select(
            "*",
            $extraFieldTable,
            [
                'where' => ['variable = ?' => 'paypal'],
            ],
            'first'
        );

        if (!$paypalExtraField) {
            return false;
        }

        $innerJoins = "
            INNER JOIN $userTable u ON p.user_id = u.id
            INNER JOIN $saleTable s ON s.id = p.sale_id
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            LEFT JOIN  $extraFieldValues efv ON p.user_id = efv.item_id
            AND field_id = ".((int) $paypalExtraField['id'])."
        ";

        $payouts = Database::select(
            "p.* , u.firstname, u.lastname, efv.value as paypal_account, s.reference as sale_reference, s.price as item_price, c.iso_code",
            "$payoutsTable p $innerJoins",
            [
                'where' => ['p.status = ? '.$condition.' '.$condition2 => $status],
            ],
            $typeResult
        );

        return $payouts;
    }

    /**
     * Verify if the beneficiary have a paypal account.
     *
     * @return true if the user have a paypal account, false if not
     */
    public function verifyPaypalAccountByBeneficiary(int $userId)
    {
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $extraFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $paypalExtraField = Database::select(
            '*',
            $extraFieldTable,
            [
                'where' => ['variable = ?' => 'paypal'],
            ],
            'first'
        );

        if (!$paypalExtraField) {
            return false;
        }

        $paypalFieldId = $paypalExtraField['id'];
        $paypalAccount = Database::select(
            'value',
            $extraFieldValues,
            [
                'where' => ['field_id = ? AND item_id = ?' => [(int) $paypalFieldId, $userId]],
            ],
            'first'
        );

        if (!$paypalAccount) {
            return false;
        }

        if ($paypalAccount['value'] === '') {
            return false;
        }

        return true;
    }

    /**
     * Register the users payouts.
     *
     * @return array
     */
    public function storePayouts(int $saleId)
    {
        $payoutsTable = Database::get_main_table(self::TABLE_PAYPAL_PAYOUTS);
        $platformCommission = $this->getPlatformCommission();

        $sale = $this->getSale($saleId);
        $commission = (int) $platformCommission['commission'];
        $teachersCommission = number_format(
            (floatval($sale['price']) * $commission) / 100,
            2
        );

        $beneficiaries = $this->getBeneficiariesBySale($saleId);
        foreach ($beneficiaries as $beneficiary) {
            $beneficiaryCommission = (int) $beneficiary['commissions'];
            Database::insert(
                $payoutsTable,
                [
                    'date' => $sale['date'],
                    'payout_date' => api_get_utc_datetime(),
                    'sale_id' => $saleId,
                    'user_id' => $beneficiary['user_id'],
                    'commission' => number_format(
                        (floatval($teachersCommission) * $beneficiaryCommission) / 100,
                        2
                    ),
                    'status' => self::PAYOUT_STATUS_PENDING,
                ]
            );
        }
    }

    /**
     * Register the users payouts.
     *
     * @param int $saleId The subscription sale ID
     *
     * @return array
     */
    public function storeSubscriptionPayouts(int $saleId)
    {
        $payoutsTable = Database::get_main_table(self::TABLE_PAYPAL_PAYOUTS);
        $platformCommission = $this->getPlatformCommission();

        $sale = $this->getSubscriptionSale($saleId);
        $commission = (int) $platformCommission['commission'];
        $teachersCommission = number_format(
            (floatval($sale['price']) * $commission) / 100,
            2
        );

        $beneficiaries = $this->getBeneficiariesBySale($saleId);
        foreach ($beneficiaries as $beneficiary) {
            $beneficiaryCommission = (int) $beneficiary['commissions'];
            Database::insert(
                $payoutsTable,
                [
                    'date' => $sale['date'],
                    'payout_date' => api_get_utc_datetime(),
                    'sale_id' => $saleId,
                    'user_id' => $beneficiary['user_id'],
                    'commission' => number_format(
                        (floatval($teachersCommission) * $beneficiaryCommission) / 100,
                        2
                    ),
                    'status' => self::PAYOUT_STATUS_PENDING,
                ]
            );
        }
    }

    /**
     * Register the users payouts.
     *
     * @param int $payoutId The payout ID
     * @param int $status   The status to set (-1 to cancel, 0 to pending, 1 to completed)
     *
     * @return array
     */
    public function setStatusPayouts(int $payoutId, int $status)
    {
        $payoutsTable = Database::get_main_table(self::TABLE_PAYPAL_PAYOUTS);

        Database::update(
            $payoutsTable,
            ['status' => (int) $status],
            ['id = ?' => (int) $payoutId]
        );
    }

    /**
     * Gets the stored platform commission params.
     *
     * @return array
     */
    public function getPlatformCommission()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_COMMISSION),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Update the platform commission.
     *
     * @param array $params platform commission
     *
     * @return int The number of affected rows. Otherwise return false
     */
    public function updateCommission(array $params)
    {
        $commissionTable = Database::get_main_table(self::TABLE_COMMISSION);

        return Database::update(
            $commissionTable,
            ['commission' => (int) $params['commission']]
        );
    }

    /**
     * Register additional service.
     *
     * @param array $service params
     *
     * @return mixed response
     */
    public function storeService(array $service)
    {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);

        $return = Database::insert(
            $servicesTable,
            [
                'name' => Security::remove_XSS($service['name']),
                'description' => Security::remove_XSS($service['description']),
                'price' => $service['price'],
                'tax_perc' => $service['tax_perc'] != '' ? (int) $service['tax_perc'] : null,
                'duration_days' => (int) $service['duration_days'],
                'applies_to' => (int) $service['applies_to'],
                'owner_id' => (int) $service['owner_id'],
                'visibility' => (int) $service['visibility'],
                'image' => '',
                'video_url' => $service['video_url'],
                'service_information' => $service['service_information'],
            ]
        );

        if ($return && !empty($service['picture_crop_image_base_64']) &&
            !empty($service['picture_crop_result'])
        ) {
            $img = str_replace('data:image/png;base64,', '', $service['picture_crop_image_base_64']);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            $file = api_get_path(SYS_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$return.'.png';
            file_put_contents($file, $data);

            Database::update(
                $servicesTable,
                ['image' => 'simg-'.$return.'.png'],
                ['id = ?' => $return]
            );
        }

        return $return;
    }

    /**
     * update a service.
     *
     * @return mixed response
     */
    public function updateService(array $service, int $id)
    {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        if (!empty($service['picture_crop_image_base_64'])) {
            $img = str_replace('data:image/png;base64,', '', $service['picture_crop_image_base_64']);
            $img = str_replace(' ', '+', $img);
            $data = base64_decode($img);
            $file = api_get_path(SYS_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$id.'.png';
            file_put_contents($file, $data);
        }

        return Database::update(
            $servicesTable,
            [
                'name' => Security::remove_XSS($service['name']),
                'description' => Security::remove_XSS($service['description']),
                'price' => $service['price'],
                'tax_perc' => $service['tax_perc'] != '' ? (int) $service['tax_perc'] : null,
                'duration_days' => (int) $service['duration_days'],
                'applies_to' => (int) $service['applies_to'],
                'owner_id' => (int) $service['owner_id'],
                'visibility' => (int) $service['visibility'],
                'image' => 'simg-'.$id.'.png',
                'video_url' => $service['video_url'],
                'service_information' => $service['service_information'],
            ],
            ['id = ?' => $id]
        );
    }

    /**
     * Remove a service.
     *
     * @param int $id The transfer account ID
     *
     * @return int Rows affected. Otherwise return false
     */
    public function deleteService(int $id)
    {
        Database::delete(
            Database::get_main_table(self::TABLE_SERVICES_SALE),
            ['service_id = ?' => $id]
        );

        return Database::delete(
            Database::get_main_table(self::TABLE_SERVICES),
            ['id = ?' => $id]
        );
    }

    /**
     * @param array|null $coupon Array with at least 'discount_type' and 'discount_amount' elements
     */
    public function setPriceSettings(array &$product, int $productType, array $coupon = null): bool
    {
        if (empty($product)) {
            return false;
        }

        $taxPerc = null;
        $product['has_coupon'] = $coupon != null ? true : false;
        $couponDiscount = 0;
        if ($coupon != null) {
            if ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_AMOUNT) {
                $couponDiscount = $coupon['discount_amount'];
            } elseif ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_PERCENTAGE) {
                $couponDiscount = ($product['price'] * $coupon['discount_amount']) / 100;
            }
            $product['price_without_discount'] = $product['price'];
        }
        $product['discount_amount'] = $couponDiscount;
        $product['price'] = $product['price'] - $couponDiscount;
        $priceWithoutTax = $product['price'];
        $product['total_price'] = $product['price'];
        $product['tax_amount'] = 0;

        if ($this->checkTaxEnabledInProduct($productType)) {
            if (is_null($product['tax_perc'])) {
                $globalParameters = $this->getGlobalParameters();
                $globalTaxPerc = $globalParameters['global_tax_perc'];
                $taxPerc = $globalTaxPerc;
            } else {
                $taxPerc = $product['tax_perc'];
            }
            //$taxPerc = is_null($product['tax_perc']) ? $globalTaxPerc : $product['tax_perc'];

            $taxAmount = round($priceWithoutTax * $taxPerc / 100, 2);
            $product['tax_amount'] = $taxAmount;
            $priceWithTax = $priceWithoutTax + $taxAmount;
            $product['total_price'] = $priceWithTax;
        }

        $product['tax_perc_show'] = $taxPerc;
        $product['price_formatted'] = $this->getPriceWithCurrencyFromIsoCode(
            $product['price'],
            $product['iso_code']
        );

        $product['tax_amount_formatted'] = number_format($product['tax_amount'], 2);

        $product['total_price_formatted'] = $this->getPriceWithCurrencyFromIsoCode(
            $product['total_price'],
            $product['iso_code']
        );

        if ($coupon != null) {
            $product['discount_amount_formatted'] = $this->getPriceWithCurrencyFromIsoCode(
                $product['discount_amount'],
                $product['iso_code']
            );

            $product['price_without_discount_formatted'] = $this->getPriceWithCurrencyFromIsoCode(
                $product['price_without_discount'],
                $product['iso_code']
            );
        }

        return true;
    }

    /**
     * @param array $coupon
     *
     * @return array
     */
    public function getService(int $id, array $coupon = null)
    {
        if (empty($id)) {
            return [];
        }

        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $conditions = ['WHERE' => ['s.id = ?' => $id]];
        $showData = 'first';
        $innerJoins = "INNER JOIN $userTable u ON s.owner_id = u.id";
        $currency = $this->getSelectedCurrency();
        $isoCode = $currency['iso_code'];
        $service = Database::select(
            "s.*, '$isoCode' as currency, u.firstname, u.lastname",
            "$servicesTable s $innerJoins",
            $conditions,
            $showData
        );

        $service['iso_code'] = $isoCode;
        $globalParameters = $this->getGlobalParameters();

        $this->setPriceSettings($service, self::TAX_APPLIES_TO_ONLY_SERVICES, $coupon);

        $service['tax_name'] = $globalParameters['tax_name'];
        $service['tax_enable'] = $this->checkTaxEnabledInProduct(self::TAX_APPLIES_TO_ONLY_SERVICES);
        $service['owner_name'] = api_get_person_name($service['firstname'], $service['lastname']);
        $service['image'] = !empty($service['image']) ? api_get_path(WEB_PLUGIN_PATH).'buycourses/uploads/services/images/'.$service['image'] : null;

        return $service;
    }

    /**
     * List additional services.
     *
     * @return array
     */
    public function getAllServices()
    {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "INNER JOIN $userTable u ON s.owner_id = u.id";
        $return = Database::select(
            's.id',
            "$servicesTable s $innerJoins",
            [],
            'all'
        );

        $services = [];
        foreach ($return as $index => $service) {
            $services[$index] = $this->getService($service['id']);
        }

        return $services;
    }

    /**
     * List additional services.
     *
     * @return array|int
     */
    public function getServices(int $start, int $end, string $typeResult = 'all')
    {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $conditions = ['limit' => "$start, $end"];
        $innerJoins = "INNER JOIN $userTable u ON s.owner_id = u.id";
        $return = Database::select(
            's.id',
            "$servicesTable s $innerJoins",
            $conditions,
            $typeResult
        );

        if ($typeResult === 'count') {
            return $return;
        }

        $services = [];
        foreach ($return as $index => $service) {
            $services[$index] = $this->getService($service['id']);
        }

        return $services;
    }

    /**
     * Get the statuses for sales.
     *
     * @return array
     */
    public function getServiceSaleStatuses()
    {
        return [
            self::SERVICE_STATUS_CANCELLED => $this->get_lang('SaleStatusCancelled'),
            self::SERVICE_STATUS_PENDING => $this->get_lang('SaleStatusPending'),
            self::SERVICE_STATUS_COMPLETED => $this->get_lang('SaleStatusCompleted'),
        ];
    }

    /**
     * List services sales.
     *
     * @param int $buyerId  buyer id
     * @param int $status   status
     * @param int $nodeType The node Type ( User = 1 , Course = 2 , Session = 3 )
     * @param int $nodeId   the nodeId
     *
     * @return array
     */
    public function getServiceSales(
        int $buyerId = 0,
        int $status = 0,
        int $nodeType = 0,
        int $nodeId = 0
    ) {
        $conditions = null;
        $groupBy = '';

        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $servicesSaleTable = Database::get_main_table(self::TABLE_SERVICES_SALE);

        $defaultOrder = 'id ASC';

        if (!empty($buyerId)) {
            $conditions = ['WHERE' => ['ss.buyer_id = ?' => $buyerId], 'ORDER' => $defaultOrder];
        }

        if (is_numeric($status)) {
            $conditions = ['WHERE' => ['ss.status = ?' => $status], 'ORDER' => $defaultOrder];
        }

        if ($buyerId) {
            $conditions = ['WHERE' => ['ss.buyer_id = ?' => [$buyerId]], 'ORDER' => $defaultOrder];
        }

        if ($nodeType && $nodeId) {
            $conditions = [
                'WHERE' => ['ss.node_type = ? AND ss.node_id = ?' => [$nodeType, $nodeId]],
                'ORDER' => $defaultOrder,
            ];
        }

        if ($nodeType && $nodeId && $buyerId && is_numeric($status)) {
            $conditions = [
                'WHERE' => [
                    'ss.node_type = ? AND ss.node_id = ? AND ss.buyer_id = ? AND ss.status = ?' => [
                        $nodeType,
                        $nodeId,
                        $buyerId,
                        $status,
                    ],
                ],
                'ORDER' => 'ss.service_id ASC',
            ];
        }

        $innerJoins = "INNER JOIN $servicesTable s ON ss.service_id = s.id $groupBy";
        $return = Database::select(
            'DISTINCT ss.id ',
            "$servicesSaleTable ss $innerJoins",
            $conditions
            //, "all", null, true
        );

        $list = [];
        foreach ($return as $service) {
            $list[] = $this->getServiceSale($service['id']);
        }

        return $list;
    }

    /**
     * @param int $id service sale id
     *
     * @return array
     */
    public function getServiceSale(int $id)
    {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $servicesSaleTable = Database::get_main_table(self::TABLE_SERVICES_SALE);

        if (empty($id)) {
            return [];
        }

        $conditions = ['WHERE' => ['ss.id = ?' => $id]];
        $innerJoins = "INNER JOIN $servicesTable s ON ss.service_id = s.id ";
        $currency = $this->getSelectedCurrency();
        $isoCode = $currency['iso_code'];

        $servicesSale = Database::select(
            'ss.*, s.name, s.description, s.price as service_price, s.duration_days, s.applies_to, s.owner_id, s.visibility, s.image',
            "$servicesSaleTable ss $innerJoins",
            $conditions,
            'first'
        );
        $owner = api_get_user_info($servicesSale['owner_id']);
        $buyer = api_get_user_info($servicesSale['buyer_id']);

        $servicesSale['service']['id'] = $servicesSale['service_id'];
        $servicesSale['service']['name'] = $servicesSale['name'];
        $servicesSale['service']['description'] = $servicesSale['description'];
        $servicesSale['service']['price'] = $servicesSale['service_price'];
        $servicesSale['service']['currency'] = $isoCode;

        $servicesSale['service']['total_price'] = $this->getPriceWithCurrencyFromIsoCode(
            $servicesSale['price'],
            $isoCode
        );

        $servicesSale['service']['duration_days'] = $servicesSale['duration_days'];
        $servicesSale['service']['applies_to'] = $servicesSale['applies_to'];
        $servicesSale['service']['owner']['id'] = $servicesSale['owner_id'];
        $servicesSale['service']['owner']['name'] = api_get_person_name($owner['firstname'], $owner['lastname']);
        $servicesSale['service']['visibility'] = $servicesSale['visibility'];
        $servicesSale['service']['image'] = $servicesSale['image'];
        $servicesSale['item'] = $this->getService($servicesSale['service_id']);
        $servicesSale['buyer']['id'] = $buyer['user_id'];
        $servicesSale['buyer']['name'] = api_get_person_name($buyer['firstname'], $buyer['lastname']);
        $servicesSale['buyer']['username'] = $buyer['username'];

        return $servicesSale;
    }

    /**
     * Update service sale status to cancelled.
     *
     * @param int $serviceSaleId The sale ID
     *
     * @return bool
     */
    public function cancelServiceSale(int $serviceSaleId)
    {
        $this->updateServiceSaleStatus(
            $serviceSaleId,
            self::SERVICE_STATUS_CANCELLED
        );

        return true;
    }

    /**
     * Complete service sale process. Update service sale status to completed.
     *
     * @param int $serviceSaleId The service sale ID
     *
     * @return bool
     */
    public function completeServiceSale(int $serviceSaleId)
    {
        $serviceSale = $this->getServiceSale($serviceSaleId);
        if ($serviceSale['status'] == self::SERVICE_STATUS_COMPLETED) {
            return true;
        }

        $this->updateServiceSaleStatus(
            $serviceSaleId,
            self::SERVICE_STATUS_COMPLETED
        );

        if ($this->get('invoicing_enable') === 'true') {
            $this->setInvoice($serviceSaleId, 1);
        }

        return true;
    }

    /**
     * Lists current service details.
     *
     * @return array|int
     */
    public function getCatalogServiceList(
        int $start,
        int $end,
        string $name = null,
        int $min = 0,
        int $max = 0,
        $appliesTo = '',
        string $typeResult = 'all'
    ) {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $whereConditions = [
            's.visibility <> ? ' => 0,
        ];

        if (!empty($name)) {
            $whereConditions['AND s.name LIKE %?%'] = $name;
        }

        if (!empty($min)) {
            $whereConditions['AND s.price >= ?'] = $min;
        }

        if (!empty($max)) {
            $whereConditions['AND s.price <= ?'] = $max;
        }

        if (!$appliesTo == '') {
            $whereConditions['AND s.applies_to = ?'] = $appliesTo;
        }

        $innerJoins = "INNER JOIN $userTable u ON s.owner_id = u.id";
        $return = Database::select(
            's.*',
            "$servicesTable s $innerJoins",
            ['WHERE' => $whereConditions, 'limit' => "$start, $end"],
            $typeResult
        );

        if ($typeResult === 'count') {
            return $return;
        }

        $services = [];
        foreach ($return as $index => $service) {
            $services[$index] = $this->getService($service['id']);
        }

        return $services;
    }

    /**
     * Register a Service sale.
     *
     * @param int $serviceId   The service ID
     * @param int $paymentType The payment type
     * @param int $infoSelect  The ID for Service Type
     *
     * @return bool
     */
    public function registerServiceSale(int $serviceId, int $paymentType, int $infoSelect, int $couponId = null)
    {
        if (!in_array(
            $paymentType,
            [self::PAYMENT_TYPE_PAYPAL, self::PAYMENT_TYPE_TRANSFER, self::PAYMENT_TYPE_CULQI]
        )
        ) {
            return false;
        }

        $userId = api_get_user_id();
        $service = $this->getService($serviceId);

        if (empty($service)) {
            return false;
        }

        if ($couponId != null) {
            $coupon = $this->getCouponService($couponId, $serviceId);
        }

        $couponDiscount = 0;
        $priceWithoutDiscount = 0;
        if ($coupon != null) {
            if ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_AMOUNT) {
                $couponDiscount = $coupon['discount_amount'];
            } elseif ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_PERCENTAGE) {
                $couponDiscount = ($service['price'] * $coupon['discount_amount']) / 100;
            }
            $priceWithoutDiscount = $service['price'];
        }
        $service['price'] = $service['price'] - $couponDiscount;
        $currency = $this->getSelectedCurrency();
        $price = $service['price'];
        $priceWithoutTax = null;
        $taxPerc = null;
        $taxEnable = $this->get('tax_enable') === 'true';
        $globalParameters = $this->getGlobalParameters();
        $taxAppliesTo = $globalParameters['tax_applies_to'];
        $taxAmount = 0;

        if ($taxEnable &&
            ($taxAppliesTo == self::TAX_APPLIES_TO_ALL || $taxAppliesTo == self::TAX_APPLIES_TO_ONLY_SERVICES)
        ) {
            $priceWithoutTax = $service['price'];
            $globalTaxPerc = $globalParameters['global_tax_perc'];
            $precision = 2;
            $taxPerc = is_null($service['tax_perc']) ? $globalTaxPerc : $service['tax_perc'];
            $taxAmount = round($priceWithoutTax * $taxPerc / 100, $precision);
            $price = $priceWithoutTax + $taxAmount;
        }

        $values = [
            'service_id' => $serviceId,
            'reference' => $this->generateReference(
                $userId,
                $service['applies_to'],
                $infoSelect
            ),
            'currency_id' => $currency['id'],
            'price' => $price,
            'price_without_tax' => $priceWithoutTax,
            'tax_perc' => $taxPerc,
            'tax_amount' => $taxAmount,
            'node_type' => $service['applies_to'],
            'node_id' => $infoSelect,
            'buyer_id' => $userId,
            'buy_date' => api_get_utc_datetime(),
            'date_start' => api_get_utc_datetime(),
            'date_end' => date_format(
                date_add(
                    date_create(api_get_utc_datetime()),
                    date_interval_create_from_date_string($service['duration_days'].' days')
                ),
                'Y-m-d H:i:s'
            ),
            'status' => self::SERVICE_STATUS_PENDING,
            'payment_type' => $paymentType,
            'price_without_discount' => $priceWithoutDiscount,
            'discount_amount' => $couponDiscount,
        ];

        $returnedServiceSaleId = Database::insert(self::TABLE_SERVICES_SALE, $values);

        return $returnedServiceSaleId;
    }

    /**
     * Save Culqi configuration params.
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveCulqiParameters(array $params)
    {
        return Database::update(
            Database::get_main_table(self::TABLE_CULQI),
            [
                'commerce_code' => $params['commerce_code'],
                'api_key' => $params['api_key'],
                'integration' => $params['integration'],
            ],
            ['id = ?' => 1]
        );
    }

    /**
     * Gets the stored Culqi params.
     *
     * @return array
     */
    public function getCulqiParams()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_CULQI),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Save Cecabank configuration params.
     *
     * @return array
     */
    public function saveCecabankParameters(array $params)
    {
        return Database::update(
            Database::get_main_table(self::TABLE_TPV_CECABANK),
            [
                'crypto_key' => $params['crypto_key'],
                'merchant_id' => $params['merchart_id'],
                'acquirer_bin' => $params['acquirer_bin'],
                'terminal_id' => $params['terminal_id'],
                'cypher' => $params['cypher'],
                'exponent' => $params['exponent'],
                'supported_payment' => $params['supported_payment'],
                'url' => $params['url'],
            ],
            ['id = ?' => 1]
        );
    }

    /**
     * Gets the stored Cecabank params.
     *
     * @return array
     */
    public function getCecabankParams()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_TPV_CECABANK),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Save Global Parameters.
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveGlobalParameters(array $params)
    {
        $sqlParams = [
            'terms_and_conditions' => $params['terms_and_conditions'],
            'sale_email' => $params['sale_email'],
        ];

        if ($this->get('tax_enable') === 'true') {
            $sqlParams['global_tax_perc'] = $params['global_tax_perc'];
            $sqlParams['tax_applies_to'] = $params['tax_applies_to'];
            $sqlParams['tax_name'] = $params['tax_name'];
        }

        if ($this->get('invoicing_enable') === 'true') {
            $sqlParams['seller_name'] = $params['seller_name'];
            $sqlParams['seller_id'] = $params['seller_id'];
            $sqlParams['seller_address'] = $params['seller_address'];
            $sqlParams['seller_email'] = $params['seller_email'];
            $sqlParams['next_number_invoice'] = $params['next_number_invoice'];
            $sqlParams['invoice_series'] = $params['invoice_series'];
        }

        return Database::update(
            Database::get_main_table(self::TABLE_GLOBAL_CONFIG),
            $sqlParams,
            ['id = ?' => 1]
        );
    }

    /**
     * get Global Parameters.
     *
     * @return array
     */
    public function getGlobalParameters()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_GLOBAL_CONFIG),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * @return bool
     */
    public function checkTaxEnabledInProduct(int $productType)
    {
        if (empty($this->get('tax_enable') === 'true')) {
            return false;
        }

        $globalParameters = $this->getGlobalParameters();
        $taxAppliesTo = $globalParameters['tax_applies_to'];
        if ($taxAppliesTo == self::TAX_APPLIES_TO_ALL) {
            return true;
        }

        if ($taxAppliesTo == $productType) {
            return true;
        }

        return false;
    }

    /**
     * Get the path.
     *
     * @param string $var path variable
     *
     * @return string path
     */
    public function getPath($var)
    {
        $pluginPath = api_get_path(WEB_PLUGIN_PATH).'buycourses/';
        $paths = [
            'SERVICE_IMAGES' => $pluginPath.'uploads/services/images/',
            'SRC' => $pluginPath.'src/',
            'VIEW' => $pluginPath.'view/',
            'UPLOADS' => $pluginPath.'uploads/',
            'LANGUAGES' => $pluginPath.'lang/',
            'RESOURCES' => $pluginPath.'resources/',
            'RESOURCES_IMG' => $pluginPath.'resources/img/',
            'RESOURCES_CSS' => $pluginPath.'resources/css/',
            'RESOURCES_JS' => $pluginPath.'resources/js/',
        ];

        return $paths[$var];
    }

    /**
     * @return array
     */
    public function getBuyCoursePluginPrice(Session $session)
    {
        // start buycourse validation
        // display the course price and buy button if the buycourses plugin is enabled and this course is configured
        $isThisCourseInSale = $this->buyCoursesForGridCatalogValidator($session->getId(), self::PRODUCT_TYPE_SESSION);
        $return = [];

        if ($isThisCourseInSale) {
            // set the Price label
            $return['html'] = $isThisCourseInSale['html'];
            // set the Buy button instead register.
            if ($isThisCourseInSale['verificator']) {
                $return['buy_button'] = $this->returnBuyCourseButton($session->getId(), self::PRODUCT_TYPE_SESSION);
            }
        }
        // end buycourse validation
        return $return;
    }

    /**
     * Register a coupon sale.
     *
     * @param int $saleId   The sale ID
     * @param int $couponId The coupon ID
     *
     * @return int
     */
    public function registerCouponSale(int $saleId, int $couponId)
    {
        $sale = $this->getSale($saleId);

        if (empty($sale)) {
            return false;
        }

        $values = [
            'coupon_id' => $couponId,
            'sale_id' => $saleId,
        ];

        return Database::insert(self::TABLE_COUPON_SALE, $values);
    }

    /**
     * Register a coupon service sale.
     *
     * @param int $saleId   The sale ID
     * @param int $couponId The coupon ID
     *
     * @return int
     */
    public function registerCouponServiceSale(int $saleId, int $couponId)
    {
        $sale = $this->getSale($saleId);

        if (empty($sale)) {
            return false;
        }

        $values = [
            'coupon_id' => $couponId,
            'service_sale_id' => $saleId,
        ];

        return Database::insert(self::TABLE_COUPON_SERVICE_SALE, $values);
    }

    /**
     * Register a coupon sale.
     *
     * @param int $saleId   The sale ID
     * @param int $couponId The coupon ID
     *
     * @return int
     */
    public function registerCouponSubscriptionSale(int $saleId, int $couponId)
    {
        $sale = $this->getSubscriptionSale($saleId);

        if (empty($sale)) {
            return false;
        }

        $values = [
            'coupon_id' => (int) $couponId,
            'sale_id' => (int) $saleId,
        ];

        return Database::insert(self::TABLE_COUPON_SUBSCRIPTION_SALE, $values);
    }

    /**
     * Add a new coupon.
     */
    public function addNewCoupon(array $coupon): bool
    {
        $couponId = $this->registerCoupon($coupon);
        if ($couponId) {
            if (isset($coupon['courses'])) {
                foreach ($coupon['courses'] as $course) {
                    $this->registerCouponItem($couponId, self::PRODUCT_TYPE_COURSE, $course);
                }
            }

            if (isset($coupon['sessions'])) {
                foreach ($coupon['sessions'] as $session) {
                    $this->registerCouponItem($couponId, self::PRODUCT_TYPE_SESSION, $session);
                }
            }

            if (isset($coupon['services'])) {
                foreach ($coupon['services'] as $service) {
                    $this->registerCouponService($couponId, $service);
                }
            }

            return true;
        } else {
            Display::addFlash(
                Display::return_message(
                    $this->get_lang('CouponErrorInsert'),
                    'error',
                    false
                )
            );

            return false;
        }
    }

    /**
     * Add a new coupon.
     *
     * @return bool
     */
    public function updateCouponData(array $coupon)
    {
        $this->updateCoupon($coupon);
        $this->deleteCouponItemsByCoupon(self::PRODUCT_TYPE_COURSE, $coupon['id']);
        $this->deleteCouponItemsByCoupon(self::PRODUCT_TYPE_SESSION, $coupon['id']);
        $this->deleteCouponServicesByCoupon($coupon['id']);

        if (isset($coupon['courses'])) {
            foreach ($coupon['courses'] as $course) {
                $this->registerCouponItem($coupon['id'], self::PRODUCT_TYPE_COURSE, $course);
            }
        }

        if (isset($coupon['sessions'])) {
            foreach ($coupon['sessions'] as $session) {
                $this->registerCouponItem($coupon['id'], self::PRODUCT_TYPE_SESSION, $session);
            }
        }

        if (isset($coupon['services'])) {
            foreach ($coupon['services'] as $service) {
                $this->registerCouponService($coupon['id'], $service);
            }
        }

        return true;
    }

    /**
     * Update coupons delivered.
     *
     * @param int $couponId The coupon ID
     *
     * @return bool
     */
    public function updateCouponDelivered(int $couponId)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);

        $sql = "UPDATE $couponTable
        SET delivered = delivered+1
        WHERE id = $couponId";

        Database::query($sql);
    }

    /**
     * Get coupon info.
     *
     * @param int $couponId The coupon ID
     *
     * @return array The coupon data
     */
    public function getCouponInfo(int $couponId)
    {
        $coupon = $this->getDataCoupon($couponId);

        $couponRelCourses = $this->getItemsCoupons($couponId, self::PRODUCT_TYPE_COURSE);
        $couponRelSessions = $this->getItemsCoupons($couponId, self::PRODUCT_TYPE_SESSION);
        $couponRelServices = $this->getServicesCoupons($couponId);

        $coupon['courses'] = $couponRelCourses;
        $coupon['sessions'] = $couponRelSessions;
        $coupon['services'] = $couponRelServices;

        return $coupon;
    }

    /**
     * Get a list of coupons.
     *
     * @param int $status The coupons activation status
     *
     * @return array Coupons data
     */
    public function getCouponsListByStatus(int $status)
    {
        $coupons = $this->getDataCoupons($status);

        return $coupons;
    }

    /**
     * Get the coupon data.
     *
     * @return array The coupon data
     */
    public function getCoupon(int $couponId, int $productType, int $productId)
    {
        $coupon = $this->getDataCoupon($couponId, $productType, $productId);

        return $coupon;
    }

    /**
     * Get data of the coupon code.
     *
     * @param string $couponCode  The coupon code
     * @param int    $productId   The product ID
     * @param int    $productType The product type
     *
     * @return array The coupon data
     */
    public function getCouponByCode(string $couponCode, int $productType = null, int $productId = null)
    {
        $coupon = $this->getDataCouponByCode($couponCode, $productType, $productId);

        return $coupon;
    }

    /**
     * Get data of the coupon code for a service.
     *
     * @param int $couponId  The coupon ID
     * @param int $serviceId The product ID
     *
     * @return array The coupon data
     */
    public function getCouponService(int $couponId, int $serviceId)
    {
        $coupon = $this->getDataCouponService($couponId, $serviceId);

        return $coupon;
    }

    /**
     * Get data of the coupon code for a service.
     *
     * @param string $couponCode The coupon code code
     * @param int    $serviceId  The product id
     *
     * @return array The coupon data
     */
    public function getCouponServiceByCode(string $couponCode, int $serviceId)
    {
        $coupon = $this->getDataCouponServiceByCode($couponCode, $serviceId);

        return $coupon;
    }

    /**
     * Get the coupon code of a item sale.
     *
     * @param int $saleId The sale ID
     *
     * @return string The coupon code
     */
    public function getSaleCouponCode(int $saleId)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);
        $couponSaleTable = Database::get_main_table(self::TABLE_COUPON_SALE);

        $couponFrom = "
            $couponTable c
            INNER JOIN $couponSaleTable s
                on c.id = s.coupon_id
        ";

        $couponCode = Database::select(
            ['c.code'],
            $couponFrom,
            [
                'where' => [
                    's.sale_id = ? ' => $saleId,
                ],
            ],
            'first'
        );

        return $couponCode['code'];
    }

    /**
     * Get the coupon code of a service sale.
     *
     * @param int $serviceSaleId The service sale ID
     *
     * @return string The coupon code
     */
    public function getServiceSaleCouponCode(int $serviceSaleId)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);
        $couponServiceSaleTable = Database::get_main_table(self::TABLE_COUPON_SERVICE_SALE);

        $couponFrom = "
            $couponTable c
            INNER JOIN $couponServiceSaleTable s
                on c.id = s.coupon_id
        ";

        $couponCode = Database::select(
            ['c.code'],
            $couponFrom,
            [
                'where' => [
                    's.service_sale_id = ? ' => $serviceSaleId,
                ],
            ],
            'first'
        );

        return $couponCode['code'];
    }

    /**
     * @return array
     */
    public function getCecabankSignature(string $saleReference, float $price)
    {
        $urlOk = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/cecabank_success.php';
        $urlKo = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/cecabank_cancel.php';

        $cecabankParams = $this->getCecabankParams();
        $signature = $cecabankParams['crypto_key']
        .$cecabankParams['merchant_id']
        .$cecabankParams['acquirer_bin']
        .$cecabankParams['terminal_id']
        .$saleReference
        .$price * 100
        .'978'
        .$cecabankParams['exponent']
        .$cecabankParams['cypher']
        .$urlOk
        .$urlKo;

        $sha256 = hash('sha256', $signature);
        $signature = strtolower($sha256);

        return $signature;
    }

    /**
     * Register a subscription sale.
     *
     * @param int $productId   The product ID
     * @param int $productType The product type
     * @param int $paymentType The payment type
     * @param int $duration    The subscription duration
     * @param int $couponId    The coupon ID
     *
     * @return int
     */
    public function registerSubscriptionSale(
        int $productId,
        int $productType,
        int $paymentType,
        int $duration,
        int $couponId = null
    ) {
        if (!in_array(
            $paymentType,
            [
                self::PAYMENT_TYPE_PAYPAL,
                self::PAYMENT_TYPE_TRANSFER,
                self::PAYMENT_TYPE_CULQI,
                self::PAYMENT_TYPE_TPV_REDSYS,
                self::PAYMENT_TYPE_STRIPE,
                self::PAYMENT_TYPE_TPV_CECABANK,
            ]
        )
        ) {
            return false;
        }

        $entityManager = Database::getManager();
        $item = $this->getSubscriptionItem($productId, $productType);

        if (empty($item)) {
            return false;
        }

        $productName = '';
        if ($item['product_type'] == self::PRODUCT_TYPE_COURSE) {
            $course = $entityManager->find('ChamiloCoreBundle:Course', $item['product_id']);

            if (empty($course)) {
                return false;
            }

            $productName = $course->getTitle();
        } elseif ($item['product_type'] == self::PRODUCT_TYPE_SESSION) {
            $session = $entityManager->find('ChamiloCoreBundle:Session', $item['product_id']);

            if (empty($session)) {
                return false;
            }

            $productName = $session->getName();
        }

        if ($couponId != null) {
            $coupon = $this->getCoupon($couponId, $item['product_type'], $item['product_id']);
        }

        $couponDiscount = 0;
        $priceWithoutDiscount = 0;
        if ($coupon != null) {
            if ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_AMOUNT) {
                $couponDiscount = $coupon['discount_amount'];
            } elseif ($coupon['discount_type'] == self::COUPON_DISCOUNT_TYPE_PERCENTAGE) {
                $couponDiscount = ($item['price'] * $coupon['discount_amount']) / 100;
            }
            $priceWithoutDiscount = $item['price'];
        }
        $item['price'] = $item['price'] - $couponDiscount;
        $price = $item['price'];
        $priceWithoutTax = null;
        $taxPerc = null;
        $taxAmount = 0;
        $taxEnable = $this->get('tax_enable') === 'true';
        $globalParameters = $this->getGlobalParameters();
        $taxAppliesTo = $globalParameters['tax_applies_to'];

        if ($taxEnable &&
            (
                $taxAppliesTo == self::TAX_APPLIES_TO_ALL ||
                ($taxAppliesTo == self::TAX_APPLIES_TO_ONLY_COURSE && $item['product_type'] == self::PRODUCT_TYPE_COURSE) ||
                ($taxAppliesTo == self::TAX_APPLIES_TO_ONLY_SESSION && $item['product_type'] == self::PRODUCT_TYPE_SESSION)
            )
        ) {
            $priceWithoutTax = $item['price'];
            $globalTaxPerc = $globalParameters['global_tax_perc'];
            $precision = 2;
            $taxPerc = is_null($item['tax_perc']) ? $globalTaxPerc : $item['tax_perc'];
            $taxAmount = round($priceWithoutTax * $taxPerc / 100, $precision);
            $price = $priceWithoutTax + $taxAmount;
        }

        $subscriptionEnd = date('y:m:d', strtotime('+'.$duration.' days'));

        $values = [
            'reference' => $this->generateReference(
                api_get_user_id(),
                $item['product_type'],
                $item['product_id']
            ),
            'currency_id' => $item['currency_id'],
            'date' => api_get_utc_datetime(),
            'user_id' => api_get_user_id(),
            'product_type' => $item['product_type'],
            'product_name' => $productName,
            'product_id' => $item['product_id'],
            'price' => $price,
            'price_without_tax' => $priceWithoutTax,
            'tax_perc' => $taxPerc,
            'tax_amount' => $taxAmount,
            'status' => self::SALE_STATUS_PENDING,
            'payment_type' => $paymentType,
            'price_without_discount' => $priceWithoutDiscount,
            'discount_amount' => $couponDiscount,
            'subscription_end' => $subscriptionEnd,
        ];

        return Database::insert(self::TABLE_SUBSCRIPTION_SALE, $values);
    }

    /**
     * Add a new subscription.
     *
     * @return bool
     */
    public function addNewSubscription(array $subscription)
    {
        $result = false;

        if (isset($subscription['frequencies'])) {
            foreach ($subscription['frequencies'] as $frequency) {
                $subscriptionDb = $this->getSubscription($subscription['product_type'], $subscription['product_id'], $frequency['duration']);

                if (!isset($subscriptionDb) || empty($subscription)) {
                    Display::addFlash(
                        Display::return_message(
                            $this->get_lang('SubscriptionAlreadyExists').' ('.$frequency['duration'].')',
                            'error',
                            false
                        )
                    );

                    return false;
                } else {
                    $subscriptionId = $this->registerSubscription($subscription, $frequency);
                    if ($subscriptionId) {
                        $result = true;
                    } else {
                        Display::addFlash(
                            Display::return_message(
                                $this->get_lang('SubscriptionErrorInsert'),
                                'error',
                                false
                            )
                        );

                        return false;
                    }
                }
            }
        } else {
            Display::addFlash(
                Display::return_message(
                    $this->get_lang('FrequenciesNotSetError'),
                    'error',
                    false
                )
            );

            return false;
        }

        return $result;
    }

    /**
     * Add a new subscription.
     *
     * @return bool
     */
    public function updateSubscriptions(int $productType, int $productId, int $taxPerc)
    {
        $this->updateSubscription($productType, $productId, $taxPerc);
    }

    /**
     * Delete a subscription.
     *
     * @return int
     */
    public function deleteSubscription(int $productType, int $productId, int $duration)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_SUBSCRIPTION),
            [
                'product_type = ? AND ' => (int) $productType,
                'product_id = ? AND ' => (int) $productId,
                'duration = ? ' => (int) $duration,
            ]
        );
    }

    /**
     * Get a list of subscriptions by product ID and type.
     *
     * @param string $productId   The product ID
     * @param int    $productType The product type
     *
     * @return array Subscriptions data
     */
    public function getSubscriptions($productType, $productId)
    {
        $subscriptions = $this->getDataSubscriptions($productType, $productId);

        return $subscriptions;
    }

    /**
     * Get data of the subscription.
     *
     * @return array The subscription data
     */
    public function getSubscription(int $productType, int $productId, int $duration, array $coupon = null)
    {
        $subscription = $this->getDataSubscription($productType, $productId, $duration);

        $currency = $this->getSelectedCurrency();
        $isoCode = $currency['iso_code'];

        $subscription['iso_code'] = $isoCode;

        $this->setPriceSettings($subscription, self::TAX_APPLIES_TO_ONLY_COURSE, $coupon);

        return $subscription;
    }

    /**
     * Get subscription sale data by ID.
     *
     * @param int $saleId The sale ID
     *
     * @return array
     */
    public function getSubscriptionSale(int $saleId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE),
            [
                'where' => ['id = ?' => $saleId],
            ],
            'first'
        );
    }

    /**
     * Complete subscription sale process. Update sale status to completed.
     *
     * @param int $saleId The subscription sale ID
     *
     * @return bool
     */
    public function completeSubscriptionSale(int $saleId)
    {
        $sale = $this->getSubscriptionSale($saleId);

        if ($sale['status'] == self::SALE_STATUS_COMPLETED) {
            return true;
        }

        $saleIsCompleted = false;
        switch ($sale['product_type']) {
            case self::PRODUCT_TYPE_COURSE:
                $course = api_get_course_info_by_id($sale['product_id']);
                $saleIsCompleted = CourseManager::subscribeUser($sale['user_id'], $course['code']);
                break;
            case self::PRODUCT_TYPE_SESSION:
                SessionManager::subscribeUsersToSession(
                    $sale['product_id'],
                    [$sale['user_id']],
                    api_get_session_visibility($sale['product_id']),
                    false
                );

                $saleIsCompleted = true;
                break;
        }

        if ($saleIsCompleted) {
            $this->updateSubscriptionSaleStatus($sale['id'], self::SALE_STATUS_COMPLETED);
            if ($this->get('invoicing_enable') === 'true') {
                $this->setInvoice($sale['id']);
            }
        }

        return $saleIsCompleted;
    }

    /**
     * Update subscription sale status to canceled.
     *
     * @param int $saleId The subscription sale ID
     */
    public function cancelSubscriptionSale(int $saleId)
    {
        $this->updateSubscriptionSaleStatus($saleId, self::SALE_STATUS_CANCELED);
    }

    /**
     * Get a list of subscription sales by the status.
     *
     * @param int $status The status to filter
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSubscriptionSaleListByStatus(int $status = self::SALE_STATUS_PENDING)
    {
        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => ['s.status = ?' => $status],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get the list statuses for subscriptions sales.
     *
     * @param string $dateStart
     * @param string $dateEnd
     *
     * @throws Exception
     *
     * @return array
     */
    public function getSubscriptionSaleListReport(string $dateStart = null, string $dateEnd = null)
    {
        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";
        $list = Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'order' => 'id DESC',
            ]
        );
        $listExportTemp = [];
        $listExport = [];
        $textStatus = null;
        $paymentTypes = $this->getPaymentTypes();
        $productTypes = $this->getProductTypes();
        foreach ($list as $item) {
            $statusSaleOrder = $item['status'];
            switch ($statusSaleOrder) {
                case 0:
                    $textStatus = $this->get_lang('SaleStatusPending');
                    break;
                case 1:
                    $textStatus = $this->get_lang('SaleStatusCompleted');
                    break;
                case -1:
                    $textStatus = $this->get_lang('SaleStatusCanceled');
                    break;
            }
            $dateFilter = new DateTime($item['date']);
            $listExportTemp[] = [
                'id' => $item['id'],
                'reference' => $item['reference'],
                'status' => $textStatus,
                'status_filter' => $item['status'],
                'date' => $dateFilter->format('Y-m-d'),
                'order_time' => $dateFilter->format('H:i:s'),
                'price' => $item['iso_code'].' '.$item['price'],
                'product_type' => $productTypes[$item['product_type']],
                'product_name' => $item['product_name'],
                'payment_type' => $paymentTypes[$item['payment_type']],
                'complete_user_name' => api_get_person_name($item['firstname'], $item['lastname']),
                'email' => $item['email'],
            ];
        }
        $listExport[] = [
            get_lang('Number'),
            $this->get_lang('OrderStatus'),
            $this->get_lang('OrderDate'),
            $this->get_lang('OrderTime'),
            $this->get_lang('PaymentMethod'),
            $this->get_lang('SalePrice'),
            $this->get_lang('ProductType'),
            $this->get_lang('ProductName'),
            $this->get_lang('UserName'),
            get_lang('Email'),
        ];
        //Validation Export
        $dateStart = strtotime($dateStart);
        $dateEnd = strtotime($dateEnd);
        foreach ($listExportTemp as $item) {
            $dateFilter = strtotime($item['date']);
            if (($dateFilter >= $dateStart) && ($dateFilter <= $dateEnd)) {
                $listExport[] = [
                    'id' => $item['id'],
                    'status' => $item['status'],
                    'date' => $item['date'],
                    'order_time' => $item['order_time'],
                    'payment_type' => $item['payment_type'],
                    'price' => $item['price'],
                    'product_type' => $item['product_type'],
                    'product_name' => $item['product_name'],
                    'complete_user_name' => $item['complete_user_name'],
                    'email' => $item['email'],
                ];
            }
        }

        return $listExport;
    }

    /**
     * Get a list of subscription sales by the user.
     *
     * @param string $term The search term
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSubscriptionSaleListByUser(string $term)
    {
        $term = trim($term);

        if (empty($term)) {
            return [];
        }

        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    'u.username LIKE %?% OR ' => $term,
                    'u.lastname LIKE %?% OR ' => $term,
                    'u.firstname LIKE %?%' => $term,
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get a list of subscription sales by the user id.
     *
     * @param int $id The user id
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSubscriptionSaleListByUserId(int $id)
    {
        if (empty($id)) {
            return [];
        }

        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    'u.id = ? AND s.status = ?' => [$id, self::SALE_STATUS_COMPLETED],
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get a list of subscription sales by date range.
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSubscriptionSaleListByDate(string $dateStart, string $dateEnd)
    {
        $dateStart = trim($dateStart);
        $dateEnd = trim($dateEnd);
        if (empty($dateStart)) {
            return [];
        }
        if (empty($dateEnd)) {
            return [];
        }
        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    's.date BETWEEN ? AND ' => $dateStart,
                    ' ? ' => $dateEnd,
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get a list of subscription sales by the user Email.
     *
     * @param string $term The search term
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSubscriptionSaleListByEmail(string $term)
    {
        $term = trim($term);
        if (empty($term)) {
            return [];
        }
        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);
        $currencyTable = Database::get_main_table(self::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 'u.email', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => [
                    'u.email LIKE %?% ' => $term,
                ],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get subscription sale data by ID.
     *
     * @param string $date The date
     *
     * @return array
     */
    public function getSubscriptionsDue(string $date)
    {
        return Database::select(
            'id, user_id, product_id, product_type',
            Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE),
            [
                'where' => ['subscription_end < ? AND status <> ? AND (expired is NULL OR expired <> ?)' => [
                    $date,
                    self::SALE_STATUS_COMPLETED,
                    1,
                    ],
                ],
            ],
            'first'
        );
    }

    /**
     * Get subscription sale data by ID.
     *
     * @param int $userId      The user ID
     * @param int $productId   The product ID
     * @param int $productType The product type
     *
     * @return array
     */
    public function checkItemSubscriptionActive(int $userId, int $productId, int $productType)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE),
            [
                'where' => ['subscription_end >= ? AND userId = ? AND productId = ? AND productType = ? AND status <> ?' => [
                    api_get_utc_datetime(),
                    $userId,
                    $productId,
                    $productType,
                    self::SALE_STATUS_COMPLETED,
                    ],
                ],
            ],
            'first'
        );
    }

    /**
     * Get subscription sale data by ID.
     *
     * @return array
     */
    public function updateSubscriptionSaleExpirationStatus(int $id)
    {
        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);

        return Database::update(
            $saleTable,
            ['expired' => 1],
            ['id = ?' => $id]
        );
    }

    /**
     * Get the list of frequencies discount types.
     *
     * @return array
     */
    public function getFrequencies()
    {
        $data = Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION_PERIOD),
            []
        );

        $frequenciesList = $this->getFrequenciesList();
        $frequencies = [];

        foreach ($data as $key => $items) {
            $frequencies[$items['duration']] = $items['name'];
        }

        return $frequencies;
    }

    /**
     * Get the list of frequencies discount types.
     *
     * @return array
     */
    public function getFrequenciesList()
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION_PERIOD),
            []
        );
    }

    /**
     * Get the a frequency.
     *
     * @param int $duration The duration of the frequency value
     *
     * @return array
     */
    public function selectFrequency(int $duration)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SUBSCRIPTION_PERIOD),
            [
                'where' => [
                    'duration = ?' => [
                        (int) $duration,
                    ],
                ],
            ],
            'first'
        );
    }

    /**
     * Add a new subscription frequency.
     *
     * @return array
     */
    public function addFrequency(int $duration, string $name)
    {
        $values = [
            'duration' => $duration,
            'name' => $name,
        ];

        return Database::insert(self::TABLE_SUBSCRIPTION_PERIOD, $values);
    }

    /**
     * Update a subscription frequency.
     *
     * @return array
     */
    public function updateFrequency(int $duration, string $name)
    {
        $periodTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_PERIOD);

        return Database::update(
            $periodTable,
            ['name' => $name],
            ['duration = ?' => $duration]
        );
    }

    /**
     * Delete a subscription frequency.
     *
     * @return array
     */
    public function deleteFrequency(int $duration)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_SUBSCRIPTION_PERIOD),
            [
                'duration = ?' => $duration,
            ]
        );
    }

    /**
     * @return string
     */
    public function getSubscriptionSuccessMessage(array $saleInfo)
    {
        switch ($saleInfo['product_type']) {
            case self::PRODUCT_TYPE_COURSE:
                $courseInfo = api_get_course_info_by_id($saleInfo['product_id']);
                $url = api_get_course_url($courseInfo['code']);
                break;
            case self::PRODUCT_TYPE_SESSION:
                $sessionId = (int) $saleInfo['product_id'];
                $url = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$sessionId;
                break;
            default:
                $url = '#';
        }

        return Display::return_message(
            sprintf(
                $this->get_lang('SubscriptionToCourseXSuccessful'),
                $url,
                $saleInfo['product_name']
            ),
            'success',
            false
        );
    }

    /**
     * @return string
     */
    public static function returnPagination(
        string $baseUrl,
        string $currentPage,
        string $pagesCount,
        string $totalItems,
        array $extraQueryParams = []
    ) {
        $queryParams = HttpRequest::createFromGlobals()->query->all();

        unset($queryParams['page']);

        $url = $baseUrl.'?'.http_build_query(
            array_merge($queryParams, $extraQueryParams)
        );

        return Display::getPagination($url, $currentPage, $pagesCount, $totalItems);
    }

    /**
     * Returns the javascript to set the sales report table for courses.
     */
    public static function getSalesReportScript(array $sales = [], bool $invoicingEnable = false)
    {
        $cols = "
    '".preg_replace("/'/", "\\'", get_plugin_lang('OrderReference', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('OrderStatus', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('OrderDate', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('PaymentMethod', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('Price', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('CouponDiscount', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('Coupon', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('ProductType', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_plugin_lang('Name', 'BuyCoursesPlugin'))."',
    '".preg_replace("/'/", "\\'", get_lang('UserName'))."',
    '".preg_replace("/'/", "\\'", get_lang('Email'))."',";
        $model = "
        {name:'reference', index:'reference', height:'auto', width:70, sorttype:'string', align:'center'},
        {name:'status', index:'status', height:'auto', width:70, sorttype:'string', align:'center'},
        {name:'date', index:'date', height:'auto', width:70, sorttype:'date', align:'center'},
        {name:'payment_type', index:'payment_type', height:'auto', width:70, sorttype:'string', align:'center'},
        {name:'total_price', index:'total_price', height:'auto', width:70, sorttype:'string', align:'center'},
        {name:'coupon_discount', index:'coupon_discount', height:'auto', width:40, sorttype:'string', align: 'center'},
        {name:'coupon', index:'coupon', height:'auto', width:60, sorttype:'string', align:'center'},
        {name:'product_type', index:'product_type', height:'auto', width:40, sorttype:'string'},
        {name:'product_name', index:'product_name', height:'auto', /*width:60,*/ sorttype:'string'},
        {name:'complete_user_name', index:'complete_user_name', height:'auto', width:70, sorttype:'string'},
        {name:'email', index:'email', height:'auto', /*width:60,*/ sorttype:'string'}, ";
        if ($invoicingEnable) {
            $model .= "{name:'invoice', index:'invoice', height:'auto', width:70, sorttype:'string'},";
            $cols .= "'".get_plugin_lang('Invoice', 'BuyCoursesPlugin')."',";
        }
        $cols .= "'".get_lang('Options')."',";
        $model .= "
        {name:'options', index:'options', height:'auto', width:60, sortable:false},";
        $data = '';
        foreach ($sales as $item) {
            $option = '';
            if (!isset($item['complete_user_name'])) {
                $item['complete_user_name'] = api_get_person_name($item['firstname'], $item['lastname']);
            }
            if ($item['invoice'] == 1) {
                if ($invoicingEnable) {
                    $item['invoice'] = "<a href='".api_get_path(WEB_PLUGIN_PATH).'buycourses/src/invoice.php?invoice='.$item['id']."&is_service=0"
                        ."' title='".get_plugin_lang('InvoiceView', 'BuyCoursesPlugin')."'>".
                        Display::return_icon('default.png', get_plugin_lang('InvoiceView', 'BuyCoursesPlugin'), '', ICON_SIZE_MEDIUM).
                        "<br/>".$item['num_invoice'].
                        "</a>";
                }
            } else {
                $item['invoice'] = null;
            }
            if ($item['status'] == BuyCoursesPlugin::SALE_STATUS_CANCELED) {
                $item['status'] = get_plugin_lang('SaleStatusCanceled', 'BuyCoursesPlugin');
            } elseif ($item['status'] == BuyCoursesPlugin::SALE_STATUS_PENDING) {
                $item['status'] = get_plugin_lang('SaleStatusPending', 'BuyCoursesPlugin');
                $option = "<div class='btn-group btn-group-xs' role='group'>".
                    "<a title='".get_plugin_lang('SubscribeUser', 'BuyCoursesPlugin')."'".
                    " href='".api_get_self()."?order=".$item['id']."&action=confirm'".
                    " class='btn btn-default'>".
                    Display::return_icon('user_subscribe_session.png', get_plugin_lang('SubscribeUser', 'BuyCoursesPlugin'), '', ICON_SIZE_SMALL)
                    ."</a>".
                    "<a title='".get_plugin_lang('DeleteOrder', 'BuyCoursesPlugin')."'".
                    " href='".api_get_self()."?order=".$item['id']."&action=cancel'".
                    " class='btn btn-default'>".
                    Display::return_icon('delete.png', get_plugin_lang('DeleteOrder', 'BuyCoursesPlugin'), '', ICON_SIZE_SMALL)
                    ."</a>".
                    "</div>";
            } elseif ($item['status'] == BuyCoursesPlugin::SALE_STATUS_COMPLETED) {
                $item['status'] = get_plugin_lang('SaleStatusCompleted', 'BuyCoursesPlugin');
            }
            $item['options'] = $option;
            $item['date'] = api_get_local_time($item['date']);
            $data .= json_encode($item).",";
        }

        return "
<script>
    $(window).load( function () {
        $('#table_report').jqGrid({
            height: '100%',
            autowidth: true,
            LoadOnce: true,
            rowNum:10,
            rowList: [10, 25, 50, 100],
            pager: 'tblGridPager',
            datatype: 'local',
            viewrecords: true,
            gridview: true,
            colNames:[ $cols ],
            colModel:[ $model ],
            caption: '".get_plugin_lang('SalesReport', 'BuyCoursesPlugin')."'
        });
        var mydata = [ $data ];
        for(var i=0;i<=mydata.length;i++){
            $('#table_report').jqGrid('addRowData',i+1,mydata[i]);
            if(i==mydata.length){
                $('#table_report').trigger('reloadGrid',[{page:1}])
            }
        }
    });
</script>";
    }

    /**
     * Filter the registered courses for show in plugin catalog.
     */
    private function getCourses(int $first, int $maxResults)
    {
        $em = Database::getManager();
        $urlId = api_get_current_access_url_id();

        $qb = $em->createQueryBuilder();
        $qb2 = $em->createQueryBuilder();
        $qb3 = $em->createQueryBuilder();

        $qb = $qb
            ->select('c')
            ->from('ChamiloCoreBundle:Course', 'c')
            ->where(
                $qb->expr()->notIn(
                    'c',
                    $qb2
                        ->select('course2')
                        ->from('ChamiloCoreBundle:SessionRelCourse', 'sc')
                        ->join('sc.course', 'course2')
                        ->innerJoin(
                            'ChamiloCoreBundle:AccessUrlRelSession',
                            'us',
                            Join::WITH,
                            'us.sessionId = sc.session'
                        )->where(
                            $qb->expr()->eq('us.accessUrlId ', $urlId)
                        )
                        ->getDQL()
                )
            )->andWhere(
                $qb->expr()->in(
                    'c',
                    $qb3
                        ->select('course3')
                        ->from('ChamiloCoreBundle:AccessUrlRelCourse', 'uc')
                        ->join('uc.course', 'course3')
                        ->where(
                            $qb3->expr()->eq('uc.url ', $urlId)
                        )
                        ->getDQL()
                )
            )
        ->setFirstResult($first)
        ->setMaxResults($maxResults);

        return $qb;
    }

    /**
     * Get the user status for the session.
     *
     * @param int     $userId  The user ID
     * @param Session $session The session
     *
     * @return string
     */
    private function getUserStatusForSession(int $userId, Session $session)
    {
        if (empty($userId)) {
            return 'NO';
        }

        $entityManager = Database::getManager();
        $scuRepo = $entityManager->getRepository('ChamiloCoreBundle:SessionRelCourseRelUser');

        $buySaleTable = Database::get_main_table(self::TABLE_SALE);

        // Check if user bought the course
        $sale = Database::select(
            'COUNT(1) as qty',
            $buySaleTable,
            [
                'where' => [
                    'user_id = ? AND product_type = ? AND product_id = ? AND status = ?' => [
                        $userId,
                        self::PRODUCT_TYPE_SESSION,
                        $session->getId(),
                        self::SALE_STATUS_PENDING,
                    ],
                ],
            ],
            'first'
        );

        if ($sale['qty'] > 0) {
            return 'TMP';
        }

        // Check if user is already subscribe to session
        $userSubscription = $scuRepo->findBy([
            'session' => $session,
            'user' => $userId,
        ]);

        if (!empty($userSubscription)) {
            return 'YES';
        }

        return 'NO';
    }

    /**
     * Get the user status for the course.
     *
     * @param int    $userId The user Id
     * @param Course $course The course
     *
     * @return string
     */
    private function getUserStatusForCourse(int $userId, Course $course)
    {
        if (empty($userId)) {
            return 'NO';
        }

        $entityManager = Database::getManager();
        $cuRepo = $entityManager->getRepository('ChamiloCoreBundle:CourseRelUser');
        $buySaleTable = Database::get_main_table(self::TABLE_SALE);

        // Check if user bought the course
        $sale = Database::select(
            'COUNT(1) as qty',
            $buySaleTable,
            [
                'where' => [
                    'user_id = ? AND product_type = ? AND product_id = ? AND status = ?' => [
                        $userId,
                        self::PRODUCT_TYPE_COURSE,
                        $course->getId(),
                        self::SALE_STATUS_PENDING,
                    ],
                ],
            ],
            'first'
        );

        if ($sale['qty'] > 0) {
            return 'TMP';
        }

        // Check if user is already subscribe to course
        $userSubscription = $cuRepo->findBy([
            'course' => $course,
            'user' => $userId,
        ]);

        if (!empty($userSubscription)) {
            return 'YES';
        }

        return 'NO';
    }

    /**
     * Update the sale status.
     *
     * @param int $saleId    The sale ID
     * @param int $newStatus The new status
     *
     * @return bool
     */
    private function updateSaleStatus(int $saleId, int $newStatus = self::SALE_STATUS_PENDING)
    {
        $saleTable = Database::get_main_table(self::TABLE_SALE);

        return Database::update(
            $saleTable,
            ['status' => (int) $newStatus],
            ['id = ?' => (int) $saleId]
        );
    }

    /**
     * Search filtered sessions by name, and range of price.
     *
     * @param string $name            Optional. The name filter
     * @param int    $min             Optional. The minimum price filter
     * @param int    $max             Optional. The maximum price filter
     * @param string $typeResult      Optional. 'all' and 'count'
     * @param int    $sessionCategory Optional. Session category id
     *
     * @return array
     */
    private function filterSessionList(
        int $start,
        int $end,
        string $name = null,
        int $min = 0,
        int $max = 0,
        string $typeResult = 'all',
        int $sessionCategory = 0
    ) {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $innerJoin = "$itemTable i ON s.id = i.product_id";
        $whereConditions = [
            'i.product_type = ? ' => self::PRODUCT_TYPE_SESSION,
        ];

        if (!empty($name)) {
            $whereConditions['AND s.name LIKE %?%'] = $name;
        }

        if (!empty($min)) {
            $whereConditions['AND i.price >= ?'] = $min;
        }

        if (!empty($max)) {
            $whereConditions['AND i.price <= ?'] = $max;
        }

        if ($sessionCategory != 0) {
            $whereConditions['AND s.session_category_id = ?'] = $sessionCategory;
        }

        $sessionIds = Database::select(
            's.id',
            "$sessionTable s INNER JOIN $innerJoin",
            ['where' => $whereConditions, 'limit' => "$start, $end"],
            $typeResult
        );

        if ($typeResult === 'count') {
            return $sessionIds;
        }

        if (!$sessionIds) {
            return [];
        }

        $sessions = [];

        foreach ($sessionIds as $sessionId) {
            $sessions[] = Database::getManager()->find(
                'ChamiloCoreBundle:Session',
                $sessionId
            );
        }

        return $sessions;
    }

    /**
     * Search filtered courses by name, and range of price.
     *
     * @param string $name Optional. The name filter
     * @param int    $min  Optional. The minimun price filter
     * @param int    $max  Optional. The maximum price filter
     *
     * @return array
     */
    private function filterCourseList(
        int $start,
        int $end,
        string $name = null,
        int $min = 0,
        int $max = 0,
        string $typeResult = 'all'
    ) {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $urlId = api_get_current_access_url_id();

        $min = floatval($min);
        $max = floatval($max);

        $whereConditions = [
            'i.product_type = ? ' => self::PRODUCT_TYPE_COURSE,
        ];

        if (!empty($name)) {
            $whereConditions['AND c.title LIKE %?%'] = $name;
        }

        if (!empty($min)) {
            $whereConditions['AND i.price >= ?'] = $min;
        }

        if (!empty($max)) {
            $whereConditions['AND i.price <= ?'] = $max;
        }

        $whereConditions['AND url.access_url_id = ?'] = $urlId;

        $courseIds = Database::select(
            'c.id',
            "$courseTable c
            INNER JOIN $itemTable i
            ON c.id = i.product_id
            INNER JOIN $urlTable url
            ON c.id = url.c_id
            ",
            ['where' => $whereConditions, 'limit' => "$start, $end"],
            $typeResult
        );

        if ($typeResult === 'count') {
            return $courseIds;
        }

        if (!$courseIds) {
            return [];
        }

        $courses = [];
        foreach ($courseIds as $courseId) {
            $courses[] = Database::getManager()->find(
                'ChamiloCoreBundle:Course',
                $courseId
            );
        }

        return $courses;
    }

    /**
     * Search filtered sessions by name, and range of price.
     *
     * @param string $name            Optional. The name filter
     * @param int    $sessionCategory Optional. Session category id
     *
     * @return array
     */
    private function filterSubscriptionSessionList(
        int $start,
        int $end,
        string $name = null,
        string $typeResult = 'all',
        int $sessionCategory = 0
    ) {
        $subscriptionTable = Database::get_main_table(self::TABLE_SUBSCRIPTION);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $innerJoin = "$subscriptionTable st ON s.id = st.product_id";
        $whereConditions = [
            'st.product_type = ? ' => self::PRODUCT_TYPE_SESSION,
        ];

        if (!empty($name)) {
            $whereConditions['AND s.name LIKE %?%'] = $name;
        }

        if ($sessionCategory != 0) {
            $whereConditions['AND s.session_category_id = ?'] = $sessionCategory;
        }

        $sessionIds = Database::select(
            'DISTINCT s.id',
            "$sessionTable s INNER JOIN $innerJoin",
            ['where' => $whereConditions, 'limit' => "$start, $end"],
            $typeResult
        );

        if ($typeResult === 'count') {
            return $sessionIds;
        }

        if (!$sessionIds) {
            return [];
        }

        $sessions = [];

        foreach ($sessionIds as $sessionId) {
            $sessions[] = Database::getManager()->find(
                'ChamiloCoreBundle:Session',
                $sessionId
            );
        }

        return $sessions;
    }

    /**
     * Search filtered subscriptions courses by name, and range of price.
     *
     * @param string $name Optional. The name filter
     *
     * @return array
     */
    private function filterSubscriptionCourseList(
        int $start,
        int $end,
        string $name = '',
        string $typeResult = 'all'
    ) {
        $subscriptionTable = Database::get_main_table(self::TABLE_SUBSCRIPTION);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $urlId = api_get_current_access_url_id();

        $whereConditions = [
            'st.product_type = ? ' => self::PRODUCT_TYPE_COURSE,
        ];

        if (!empty($name)) {
            $whereConditions['AND c.title LIKE %?%'] = $name;
        }

        $whereConditions['AND url.access_url_id = ?'] = $urlId;

        $courseIds = Database::select(
            'DISTINCT c.id',
            "$courseTable c
            INNER JOIN $subscriptionTable st
            ON c.id = st.product_id
            INNER JOIN $urlTable url
            ON c.id = url.c_id
            ",
            ['where' => $whereConditions, 'limit' => "$start, $end"],
            $typeResult
        );

        if ($typeResult === 'count') {
            return $courseIds;
        }

        if (!$courseIds) {
            return [];
        }

        $courses = [];
        foreach ($courseIds as $courseId) {
            $courses[] = Database::getManager()->find(
                'ChamiloCoreBundle:Course',
                $courseId
            );
        }

        return $courses;
    }

    /**
     * Update the service sale status.
     *
     * @param int $serviceSaleId The service sale ID
     * @param int $newStatus     The new status
     *
     * @return bool
     */
    private function updateServiceSaleStatus(
        int $serviceSaleId,
        int $newStatus = self::SERVICE_STATUS_PENDING
    ) {
        $serviceSaleTable = Database::get_main_table(self::TABLE_SERVICES_SALE);

        return Database::update(
            $serviceSaleTable,
            ['status' => $newStatus],
            ['id = ?' => $serviceSaleId]
        );
    }

    /**
     * Get the items (courses or sessions) of a coupon.
     *
     * @return array The item data
     */
    private function getItemsCoupons(int $couponId, int $productType)
    {
        $couponItemTable = Database::get_main_table(self::TABLE_COUPON_ITEM);

        if ($productType == self::PRODUCT_TYPE_COURSE) {
            $itemTable = Database::get_main_table(TABLE_MAIN_COURSE);
            $select = ['ci.product_id as id', 'it.title'];
        } elseif ($productType == self::PRODUCT_TYPE_SESSION) {
            $itemTable = Database::get_main_table(TABLE_MAIN_SESSION);
            $select = ['ci.product_id as id', 'it.name'];
        }

        $couponFrom = "
            $couponItemTable ci
            INNER JOIN $itemTable it
                on it.id = ci.product_id and ci.product_type = $productType
        ";

        return Database::select(
            $select,
            $couponFrom,
            [
                'where' => [
                    'ci.coupon_id = ? ' => $couponId,
                ],
            ]
        );
    }

    /**
     * Get the services of a coupon.
     *
     * @param int $couponId The coupon ID
     *
     * @return array The service data
     */
    private function getServicesCoupons(int $couponId)
    {
        $couponServiceTable = Database::get_main_table(self::TABLE_COUPON_SERVICE);
        $serviceTable = Database::get_main_table(self::TABLE_SERVICES);

        $couponFrom = "
            $couponServiceTable cs
            INNER JOIN $serviceTable s
                on s.id = cs.service_id
        ";

        return Database::select(
            ['cs.service_id as id', 's.name'],
            $couponFrom,
            [
                'where' => [
                    'cs.coupon_id = ? ' => $couponId,
                ],
            ]
        );
    }

    /**
     * Get an array of coupons filtered by their status.
     *
     * @param int $status The coupon activation status
     *
     * @return array Coupons data
     */
    private function getDataCoupons(int $status = null)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);

        if ($status != null) {
            return Database::select(
                ['*'],
                $couponTable,
                [
                    'where' => [
                        ' active = ? ' => (int) $status,
                    ],
                    'order' => 'id DESC',
                ]
            );
        } else {
            return Database::select(
                ['*'],
                $couponTable,
                [
                    'order' => 'id DESC',
                ]
            );
        }
    }

    /**
     * Get data of a coupon for a product (course or service) by the coupon ID.
     *
     * @param int $couponId    The coupon code code
     * @param int $productType The product type
     * @param int $productId   The product ID
     *
     * @return array The coupon data
     */
    private function getDataCoupon(int $couponId, int $productType = null, int $productId = null)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);

        if ($productType == null || $productId == null) {
            return Database::select(
                ['*'],
                $couponTable,
                [
                    'where' => [
                        'id = ? ' => $couponId,
                    ],
                ],
                'first'
            );
        } else {
            $couponItemTable = Database::get_main_table(self::TABLE_COUPON_ITEM);
            $dtmNow = api_get_utc_datetime();

            $couponFrom = "
                $couponTable c
                INNER JOIN $couponItemTable ci
                    on ci.coupon_id = c.id
            ";

            return Database::select(
                ['c.*'],
                $couponFrom,
                [
                    'where' => [
                        'c.id = ? AND ' => $couponId,
                        'c.valid_start <= ? AND ' => $dtmNow,
                        'c.valid_end >= ? AND ' => $dtmNow,
                        'ci.product_type = ? AND ' => $productType,
                        'ci.product_id = ?' => $productId,
                    ],
                ],
                'first'
            );
        }
    }

    /**
     * Get data of a coupon for a product (course or service) by the coupon code.
     *
     * @param string $couponCode  The coupon code code
     * @param int    $productType The product type
     * @param int    $productId   The product ID
     *
     * @return array The coupon data
     */
    private function getDataCouponByCode(string $couponCode, int $productType = null, int $productId = null)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);
        $couponItemTable = Database::get_main_table(self::TABLE_COUPON_ITEM);
        $dtmNow = api_get_utc_datetime();

        if ($productType == null || $productId == null) {
            return Database::select(
                ['*'],
                $couponTable,
                [
                    'where' => [
                        'code = ? ' => $couponCode,
                    ],
                ],
                'first'
            );
        } else {
            $couponFrom = "
                $couponTable c
                INNER JOIN $couponItemTable ci
                    on ci.coupon_id = c.id
            ";

            return Database::select(
                ['c.*'],
                $couponFrom,
                [
                    'where' => [
                        'c.code = ? AND ' => $couponCode,
                        'c.valid_start <= ? AND ' => $dtmNow,
                        'c.valid_end >= ? AND ' => $dtmNow,
                        'ci.product_type = ? AND ' => $productType,
                        'ci.product_id = ?' => $productId,
                    ],
                ],
                'first'
            );
        }
    }

    /**
     * Get data of a coupon for a service by the coupon ID.
     *
     * @param int $couponId  The coupon ID
     * @param int $serviceId The service ID
     *
     * @return array The coupon data
     */
    private function getDataCouponService(int $couponId, int $serviceId)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);
        $couponServiceTable = Database::get_main_table(self::TABLE_COUPON_SERVICE);
        $dtmNow = api_get_utc_datetime();

        $couponFrom = "
            $couponTable c
            INNER JOIN $couponServiceTable cs
                on cs.coupon_id = c.id
        ";

        return Database::select(
            ['c.*'],
            $couponFrom,
            [
                'where' => [
                    'c.id = ? AND ' => $couponId,
                    'c.valid_start <= ? AND ' => $dtmNow,
                    'c.valid_end >= ? AND ' => $dtmNow,
                    'cs.service_id = ?' => $serviceId,
                ],
            ],
            'first'
        );
    }

    /**
     * Get data of coupon for a service by the coupon code.
     *
     * @param string $couponCode The coupon code
     * @param int    $serviceId  The service ID
     *
     * @return array The coupon data
     */
    private function getDataCouponServiceByCode(string $couponCode, int $serviceId)
    {
        $couponTable = Database::get_main_table(self::TABLE_COUPON);
        $couponServiceTable = Database::get_main_table(self::TABLE_COUPON_SERVICE);
        $dtmNow = api_get_utc_datetime();

        $couponFrom = "
            $couponTable c
            INNER JOIN $couponServiceTable cs
                on cs.coupon_id = c.id
        ";

        return Database::select(
            ['c.*'],
            $couponFrom,
            [
                'where' => [
                    'c.code = ? AND ' => $couponCode,
                    'c.valid_start <= ? AND ' => $dtmNow,
                    'c.valid_end >= ? AND ' => $dtmNow,
                    'cs.service_id = ?' => $serviceId,
                ],
            ],
            'first'
        );
    }

    /**
     * Update a coupon.
     *
     * @return int
     */
    private function updateCoupon(array $coupon)
    {
        $couponExist = $this->getCouponByCode($coupon['code']);
        if (!$couponExist) {
            Display::addFlash(
                Display::return_message(
                    $this->get_lang('CouponNoExists'),
                    'error',
                    false
                )
            );

            return false;
        }

        $values = [
            'valid_start' => $coupon['valid_start'],
            'valid_end' => $coupon['valid_end'],
            'active' => $coupon['active'],
        ];

        return Database::update(
            self::TABLE_COUPON,
            $values,
            ['id = ?' => $coupon['id']]
        );
    }

    /**
     * Register a coupon.
     *
     * @return int
     */
    private function registerCoupon(array $coupon)
    {
        $couponExist = $this->getCouponByCode($coupon['code']);
        if ($couponExist) {
            Display::addFlash(
                Display::return_message(
                    $this->get_lang('CouponCodeUsed'),
                    'error',
                    false
                )
            );

            return false;
        }

        $values = [
            'code' => (string) $coupon['code'],
            'discount_type' => (int) $coupon['discount_type'],
            'discount_amount' => $coupon['discount_amount'],
            'valid_start' => $coupon['valid_start'],
            'valid_end' => $coupon['valid_end'],
            'delivered' => 0,
            'active' => $coupon['active'],
        ];

        return Database::insert(self::TABLE_COUPON, $values);
    }

    /**
     * Register a coupon item.
     *
     * @param int $couponId    The coupon ID
     * @param int $productType The product type
     * @param int $productId   The product ID
     *
     * @return int
     */
    private function registerCouponItem(int $couponId, int $productType, int $productId)
    {
        $coupon = $this->getDataCoupon($couponId);
        if (empty($coupon)) {
            Display::addFlash(
                Display::return_message(
                    $this->get_lang('CouponNoExists'),
                    'error',
                    false
                )
            );

            return false;
        }

        $values = [
            'coupon_id' => $couponId,
            'product_type' => $productType,
            'product_id' => $productId,
        ];

        return Database::insert(self::TABLE_COUPON_ITEM, $values);
    }

    /**
     * Remove all coupon items for a product type and coupon ID.
     *
     * @param int $productType The product type
     * @param int $couponId    The coupon ID
     *
     * @return int Rows affected. Otherwise return false
     */
    private function deleteCouponItemsByCoupon(int $productType, int $couponId)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_COUPON_ITEM),
            [
                'product_type = ? AND ' => $productType,
                'coupon_id = ?' => $couponId,
            ]
        );
    }

    /**
     * Register a coupon service.
     *
     * @param int $couponId  The coupon ID
     * @param int $serviceId The service ID
     *
     * @return int
     */
    private function registerCouponService(int $couponId, int $serviceId)
    {
        $coupon = $this->getDataCoupon($couponId);
        if (empty($coupon)) {
            Display::addFlash(
                Display::return_message(
                    $this->get_lang('CouponNoExists'),
                    'error',
                    false
                )
            );

            return false;
        }

        $values = [
            'coupon_id' => $couponId,
            'service_id' => $serviceId,
        ];

        return Database::insert(self::TABLE_COUPON_SERVICE, $values);
    }

    /**
     * Remove all coupon services for a product type and coupon ID.
     *
     * @return int Rows affected. Otherwise, return false
     */
    private function deleteCouponServicesByCoupon(int $couponId)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_COUPON_SERVICE),
            [
                'coupon_id = ?' => (int) $couponId,
            ]
        );
    }

    /**
     * Get an array of subscriptions.
     *
     * @return array Subscriptions data
     */
    private function getDataSubscriptions(int $productType, int $productId)
    {
        $subscriptionTable = Database::get_main_table(self::TABLE_SUBSCRIPTION);

        return Database::select(
            ['*'],
            $subscriptionTable,
            [
                'where' => [
                    'product_type = ? AND ' => (int) $productType,
                    'product_id = ?  ' => (int) $productId,
                ],
                'order' => 'duration ASC',
            ]
        );
    }

    /**
     * Get data of a subscription for a product (course or service) by the subscription ID.
     *
     * @param int $productType The product type
     * @param int $productId   The product ID
     * @param int $duration    The duration (in seconds)
     *
     * @return array The subscription data
     */
    private function getDataSubscription(int $productType, int $productId, int $duration)
    {
        $subscriptionTable = Database::get_main_table(self::TABLE_SUBSCRIPTION);

        return Database::select(
            ['*'],
            $subscriptionTable,
            [
                'where' => [
                    'product_type = ? AND ' => $productType,
                    'product_id = ? AND ' => $productId,
                    'duration = ? ' => $duration,
                ],
            ],
            'first'
        );
    }

    /**
     * Update a subscription.
     *
     * @return int
     */
    private function updateSubscription(int $productType, int $productId, int $taxPerc)
    {
        $values = [
            'tax_perc' => $taxPerc,
        ];

        return Database::update(
            self::TABLE_SUBSCRIPTION,
            $values,
            [
                'product_type = ? AND ' => $productType,
                'product_id = ?' => $productId,
            ]
        );

        return true;
    }

    /**
     * Register a subscription.
     *
     * @return int
     */
    private function registerSubscription(array $subscription, array $frequency)
    {
        $values = [
            'product_type' => (int) $subscription['product_type'],
            'product_id' => (int) $subscription['product_id'],
            'duration' => (int) $frequency['duration'],
            'currency_id' => (int) $subscription['currency_id'],
            'tax_perc' => (int) $subscription['tax_perc'],
            'price' => (float) $frequency['price'],
        ];

        Database::insert(self::TABLE_SUBSCRIPTION, $values);

        return true;
    }

    /**
     * Update the subscription sale status.
     *
     * @param int $saleId    The sale ID
     * @param int $newStatus The new status
     *
     * @return bool
     */
    private function updateSubscriptionSaleStatus(int $saleId, int $newStatus = self::SALE_STATUS_PENDING)
    {
        $saleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);

        return Database::update(
            $saleTable,
            ['status' => $newStatus],
            ['id = ?' => $saleId]
        );
    }

    /**
     * Get the user status for the subscription session.
     *
     * @param int     $userId  The user ID
     * @param Session $session The session
     *
     * @return string
     */
    private function getUserStatusForSubscriptionSession(int $userId, Session $session)
    {
        if (empty($userId)) {
            return 'NO';
        }

        $entityManager = Database::getManager();
        $scuRepo = $entityManager->getRepository('ChamiloCoreBundle:SessionRelCourseRelUser');

        $buySaleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);

        // Check if user bought the course
        $sale = Database::select(
            'COUNT(1) as qty',
            $buySaleTable,
            [
                'where' => [
                    'user_id = ? AND product_type = ? AND product_id = ? AND status = ? AND (expired is NULL OR expired <> ?)' => [
                        $userId,
                        self::PRODUCT_TYPE_SESSION,
                        $session->getId(),
                        self::SALE_STATUS_PENDING,
                        1,
                    ],
                ],
            ],
            'first'
        );

        if ($sale['qty'] > 0) {
            return 'TMP';
        }

        // Check if user is already subscribe to session
        $userSubscription = $scuRepo->findBy([
            'session' => $session,
            'user' => $userId,
        ]);

        if (!empty($userSubscription)) {
            return 'YES';
        }

        return 'NO';
    }

    /**
     * Get the user status for the subscription course.
     *
     * @param int    $userId The user Id
     * @param Course $course The course
     *
     * @return string
     */
    private function getUserStatusForSubscriptionCourse(int $userId, Course $course)
    {
        if (empty($userId)) {
            return 'NO';
        }

        $entityManager = Database::getManager();
        $cuRepo = $entityManager->getRepository('ChamiloCoreBundle:CourseRelUser');
        $buySaleTable = Database::get_main_table(self::TABLE_SUBSCRIPTION_SALE);

        // Check if user bought the course
        $sale = Database::select(
            'COUNT(1) as qty',
            $buySaleTable,
            [
                'where' => [
                    'user_id = ? AND product_type = ? AND product_id = ? AND status = ? AND (expired is NULL OR expired <> ?)' => [
                        $userId,
                        self::PRODUCT_TYPE_COURSE,
                        $course->getId(),
                        self::SALE_STATUS_PENDING,
                        1,
                    ],
                ],
            ],
            'first'
        );

        if ($sale['qty'] > 0) {
            return 'TMP';
        }

        // Check if user is already subscribe to course
        $userSubscription = $cuRepo->findBy([
            'course' => $course,
            'user' => $userId,
        ]);

        if (!empty($userSubscription)) {
            return 'YES';
        }

        return 'NO';
    }
}
