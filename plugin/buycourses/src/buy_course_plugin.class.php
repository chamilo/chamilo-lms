<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Query\Expr\Join;

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
    const TABLE_PAYPAL = 'plugin_buycourses_paypal_account';
    const TABLE_CURRENCY = 'plugin_buycourses_currency';
    const TABLE_ITEM = 'plugin_buycourses_item';
    const TABLE_ITEM_BENEFICIARY = 'plugin_buycourses_item_rel_beneficiary';
    const TABLE_SALE = 'plugin_buycourses_sale';
    const TABLE_TRANSFER = 'plugin_buycourses_transfer';
    const TABLE_COMMISSION = 'plugin_buycourses_commission';
    const TABLE_PAYPAL_PAYOUTS = 'plugin_buycourses_paypal_payouts';
    const TABLE_SERVICES = 'plugin_buycourses_services';
    const TABLE_SERVICES_SALE = 'plugin_buycourses_service_sale';
    const TABLE_CULQI = 'plugin_buycourses_culqi';
    const TABLE_GLOBAL_CONFIG = 'plugin_buycourses_global_config';
    const TABLE_INVOICE = 'plugin_buycourses_invoices';
    const TABLE_TPV_REDSYS = 'plugin_buycourses_tpvredsys_account';
    const PRODUCT_TYPE_COURSE = 1;
    const PRODUCT_TYPE_SESSION = 2;
    const PAYMENT_TYPE_PAYPAL = 1;
    const PAYMENT_TYPE_TRANSFER = 2;
    const PAYMENT_TYPE_CULQI = 3;
    const PAYMENT_TYPE_TPV_REDSYS = 4;
    const PAYOUT_STATUS_CANCELED = 2;
    const PAYOUT_STATUS_PENDING = 0;
    const PAYOUT_STATUS_COMPLETED = 1;
    const SALE_STATUS_CANCELED = -1;
    const SALE_STATUS_PENDING = 0;
    const SALE_STATUS_COMPLETED = 1;
    const SERVICE_STATUS_PENDING = 0;
    const SERVICE_STATUS_COMPLETED = 1;
    const SERVICE_STATUS_CANCELLED = -1;
    const SERVICE_TYPE_USER = 1;
    const SERVICE_TYPE_COURSE = 2;
    const SERVICE_TYPE_SESSION = 3;
    const SERVICE_TYPE_LP_FINAL_ITEM = 4;
    const CULQI_INTEGRATION_TYPE = 'INTEG';
    const CULQI_PRODUCTION_TYPE = 'PRODUC';
    const TAX_APPLIES_TO_ALL = 1;
    const TAX_APPLIES_TO_ONLY_COURSE = 2;
    const TAX_APPLIES_TO_ONLY_SESSION = 3;
    const TAX_APPLIES_TO_ONLY_SERVICES = 4;
    const PAGINATION_PAGE_SIZE = 6;

    public $isAdminPlugin = true;

    /**
     * BuyCoursesPlugin constructor.
     */
    public function __construct()
    {
        parent::__construct(
            '5.0',
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
                'invoicing_enable' => 'boolean',
                'tax_enable' => 'boolean',
                'use_currency_symbol' => 'boolean',
                'tpv_redsys_enable' => 'boolean',
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
     *
     * @return bool
     */
    public function isEnabled($checkEnabled = false)
    {
        return $this->get('paypal_enable') || $this->get('transfer_enable') || $this->get('culqi_enable');
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
    public function buyCoursesForGridCatalogValidator($productId, $productType)
    {
        $return = [];
        $paypal = $this->get('paypal_enable') === 'true';
        $transfer = $this->get('transfer_enable') === 'true';
        $hideFree = $this->get('hide_free_text') === 'true';

        if ($paypal || $transfer) {
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
     * @param int $productId
     * @param int $productType
     *
     * @return string $html
     */
    public function returnBuyCourseButton($productId, $productType)
    {
        $productId = (int) $productId;
        $productType = (int) $productType;
        $url = api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process.php?i='.$productId.'&t='.$productType;
        $html = '<a class="btn btn-success btn-sm" title="'.$this->get_lang('Buy').'" href="'.$url.'">'.
            Display::returnFontAwesomeIcon('shopping-cart').'</a>';

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
    public function saveCurrency($selectedId)
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
            ['id = ?' => (int) $selectedId]
        );
    }

    /**
     * Save the PayPal configuration params.
     *
     * @param array $params
     *
     * @return int Rows affected. Otherwise return false
     */
    public function savePaypalParams($params)
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
     * @param array $params
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveTpvRedsysParams($params)
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
     * Save a transfer account information.
     *
     * @param array $params The transfer account
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveTransferAccount($params)
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
    public function saveTransferInfoEmail($params)
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
    public function deleteTransferAccount($id)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_TRANSFER),
            ['id = ?' => (int) $id]
        );
    }

    /**
     * Get registered item data.
     *
     * @param int $itemId The item ID
     *
     * @return array
     */
    public function getItem($itemId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_ITEM),
            [
                'where' => ['id = ?' => (int) $itemId],
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
    public function getItemByProduct($productId, $itemType)
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
                        (int) $productId,
                        (int) $itemType,
                    ],
                ],
            ],
            'first'
        );

        if (empty($product)) {
            return false;
        }

        $this->setPriceSettings($product, self::TAX_APPLIES_TO_ONLY_COURSE);

        return $product;
    }

    /**
     * List courses details from the configuration page.
     *
     * @return array
     */
    public function getCourseList($first, $maxResults)
    {
        return $this->getCourses($first, $maxResults);
    }

    /**
     * Lists current user session details, including each session course details.
     *
     * It can return the number of rows when $typeResult is 'count'.
     *
     * @param int    $start
     * @param int    $end
     * @param string $name       Optional. The name filter.
     * @param int    $min        Optional. The minimum price filter.
     * @param int    $max        Optional. The maximum price filter.
     * @param string $typeResult Optional. 'all', 'first' or 'count'.
     *
     * @return array|int
     */
    public function getCatalogSessionList($start, $end, $name = null, $min = 0, $max = 0, $typeResult = 'all', $sessionCategory = 0)
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
     * @return array
     */
    public function getCatalogCourseList($first, $pageSize, $name = null, $min = 0, $max = 0, $typeResult = 'all')
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
     * @param $price
     * @param $isoCode
     *
     * @return string
     */
    public function getPriceWithCurrencyFromIsoCode($price, $isoCode)
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
     * @param int $courseId The course ID
     *
     * @return array
     */
    public function getCourseInfo($courseId)
    {
        $entityManager = Database::getManager();
        $course = $entityManager->find('ChamiloCoreBundle:Course', $courseId);

        if (empty($course)) {
            return [];
        }

        $item = $this->getItemByProduct(
            $course->getId(),
            self::PRODUCT_TYPE_COURSE
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
    public function getSessionInfo($sessionId)
    {
        $entityManager = Database::getManager();
        $session = $entityManager->find('ChamiloCoreBundle:Session', $sessionId);

        if (empty($session)) {
            return [];
        }

        $item = $this->getItemByProduct(
            $session->getId(),
            self::PRODUCT_TYPE_SESSION
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
     * @param int $itemId      The product ID
     * @param int $paymentType The payment type
     *
     * @return bool
     */
    public function registerSale($itemId, $paymentType)
    {
        if (!in_array(
                $paymentType,
                [
                    self::PAYMENT_TYPE_PAYPAL,
                    self::PAYMENT_TYPE_TRANSFER,
                    self::PAYMENT_TYPE_CULQI,
                    self::PAYMENT_TYPE_TPV_REDSYS,
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
            'payment_type' => (int) $paymentType,
        ];

        return Database::insert(self::TABLE_SALE, $values);
    }

    /**
     * Get sale data by ID.
     *
     * @param int $saleId The sale ID
     *
     * @return array
     */
    public function getSale($saleId)
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
     * Get a list of sales by the payment type.
     *
     * @param int $paymentType The payment type to filter (default : Paypal)
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByPaymentType($paymentType = self::PAYMENT_TYPE_PAYPAL)
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
                        (int) $paymentType,
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
    public function getDataSaleInvoice($saleId, $isService)
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
    public function getDataInvoice($saleId, $isService)
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
    public function getNumInvoice($saleId, $isService)
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
    public function getCurrency($currencyId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_CURRENCY),
            [
                'where' => ['id = ?' => (int) $currencyId],
            ],
            'first'
        );
    }

    /**
     * Complete sale process. Update sale status to completed.
     *
     * @param int $saleId The sale ID
     *
     * @return bool
     */
    public function completeSale($saleId)
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
    public function cancelSale($saleId)
    {
        $this->updateSaleStatus($saleId, self::SALE_STATUS_CANCELED);
    }

    /**
     * Get payment types.
     *
     * @return array
     */
    public function getPaymentTypes()
    {
        return [
            self::PAYMENT_TYPE_PAYPAL => 'PayPal',
            self::PAYMENT_TYPE_TRANSFER => $this->get_lang('BankTransfer'),
            self::PAYMENT_TYPE_CULQI => 'Culqi',
            self::PAYMENT_TYPE_TPV_REDSYS => $this->get_lang('TpvPayment'),
        ];
    }

    /**
     * Register a invoice.
     *
     * @param int $saleId    The sale ID
     * @param int $isService The service type to filter (default : 0)
     */
    public function setInvoice($saleId, $isService = 0)
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
    public function getSaleListByStatus($status = self::SALE_STATUS_PENDING)
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
                'where' => ['s.status = ?' => (int) $status],
                'order' => 'id DESC',
            ]
        );
    }

    /**
     * Get the list statuses for sales.
     *
     * @param string $dateStart
     * @param string $dateEnd
     *
     * @throws Exception
     *
     * @return array
     */
    public function getSaleListReport($dateStart = null, $dateEnd = null)
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
     * Generates a random text (used for order references).
     *
     * @param int  $length    Optional. Length of characters
     * @param bool $lowercase Optional. Include lowercase characters
     * @param bool $uppercase Optional. Include uppercase characters
     * @param bool $numbers   Optional. Include numbers
     *
     * @return string
     */
    public static function randomText(
        $length = 6,
        $lowercase = true,
        $uppercase = true,
        $numbers = true
    ) {
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
     *
     * @return string
     */
    public function generateReference($userId, $productType, $productId)
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
    public function getSaleListByUser($term)
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
    public function getSaleListByUserId($id)
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
     * @param string $dateStart
     * @param string $dateEnd
     *
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByDate($dateStart, $dateEnd)
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
    public function getSaleListByEmail($term)
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
    public function getCourseForConfiguration(Course $course, $defaultCurrency = null)
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
    public function getSessionForConfiguration(Session $session, $defaultCurrency = null)
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
    public function getItemBeneficiaries($itemId)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        return Database::select(
            '*',
            $beneficiaryTable,
            [
                'where' => [
                    'item_id = ?' => (int) $itemId,
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
    public function deleteItem($itemId)
    {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $affectedRows = Database::delete(
            $itemTable,
            ['id = ?' => (int) $itemId]
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
    public function updateItem(array $itemData, $productId, $productType)
    {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);

        return Database::update(
            $itemTable,
            $itemData,
            [
                'product_id = ? AND ' => (int) $productId,
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
    public function deleteItemBeneficiaries($itemId)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        return Database::delete(
            $beneficiaryTable,
            ['item_id = ?' => (int) $itemId]
        );
    }

    /**
     * Register the beneficiaries users with the sale of item.
     *
     * @param int   $itemId  The item ID
     * @param array $userIds The beneficiary user ID and Teachers commissions if enabled
     */
    public function registerItemBeneficiaries($itemId, array $userIds)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        $this->deleteItemBeneficiaries($itemId);

        foreach ($userIds as $userId => $commissions) {
            Database::insert(
                $beneficiaryTable,
                [
                    'item_id' => (int) $itemId,
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
    public function getBeneficiariesBySale($saleId)
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
     * @param int $userId
     *
     * @return array
     */
    public function getPayouts(
        $status = self::PAYOUT_STATUS_PENDING,
        $payoutId = false,
        $userId = false
    ) {
        $condition = ($payoutId) ? 'AND p.id = '.((int) $payoutId) : '';
        $condition2 = ($userId) ? ' AND p.user_id = '.((int) $userId) : '';
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
     * @param int $userId
     *
     * @return true if the user have a paypal account, false if not
     */
    public function verifyPaypalAccountByBeneficiary($userId)
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
                'where' => ['field_id = ? AND item_id = ?' => [(int) $paypalFieldId, (int) $userId]],
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
     * @param int $saleId The sale ID
     *
     * @return array
     */
    public function storePayouts($saleId)
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
                    'payout_date' => getdate(),
                    'sale_id' => (int) $saleId,
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
    public function setStatusPayouts($payoutId, $status)
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
     * @param int $params platform commission
     *
     * @return int The number of affected rows. Otherwise return false
     */
    public function updateCommission($params)
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
    public function storeService($service)
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
                ['id = ?' => (int) $return]
            );
        }

        return $return;
    }

    /**
     * update a service.
     *
     * @param array $service
     * @param int   $id
     *
     * @return mixed response
     */
    public function updateService($service, $id)
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
            ['id = ?' => (int) $id]
        );
    }

    /**
     * Remove a service.
     *
     * @param int $id The transfer account ID
     *
     * @return int Rows affected. Otherwise return false
     */
    public function deleteService($id)
    {
        Database::delete(
            Database::get_main_table(self::TABLE_SERVICES_SALE),
            ['service_id = ?' => (int) $id]
        );

        return Database::delete(
            Database::get_main_table(self::TABLE_SERVICES),
            ['id = ?' => (int) $id]
        );
    }

    /**
     * @param array $product
     * @param int   $productType
     *
     * @return bool
     */
    public function setPriceSettings(&$product, $productType)
    {
        if (empty($product)) {
            return false;
        }

        $taxPerc = null;
        $priceWithoutTax = $product['price'];
        $product['total_price'] = $product['price'];
        $product['tax_amount'] = 0;
        $precision = 2;
        if ($this->checkTaxEnabledInProduct($productType)) {
            if (is_null($product['tax_perc'])) {
                $globalParameters = $this->getGlobalParameters();
                $globalTaxPerc = $globalParameters['global_tax_perc'];
                $taxPerc = $globalTaxPerc;
            } else {
                $taxPerc = $product['tax_perc'];
            }
            //$taxPerc = is_null($product['tax_perc']) ? $globalTaxPerc : $product['tax_perc'];

            $taxAmount = round($priceWithoutTax * $taxPerc / 100, $precision);
            $product['tax_amount'] = $taxAmount;
            $priceWithTax = $priceWithoutTax + $taxAmount;
            $product['total_price'] = $priceWithTax;
        }

        $product['tax_perc_show'] = $taxPerc;
        $product['price_formatted'] = $this->getPriceWithCurrencyFromIsoCode(
            number_format($product['price'], $precision),
            $product['iso_code']
        );

        $product['tax_amount_formatted'] = number_format($product['tax_amount'], $precision);

        $product['total_price_formatted'] = $this->getPriceWithCurrencyFromIsoCode(
            number_format($product['total_price'], $precision),
            $product['iso_code']
        );
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getService($id)
    {
        $id = (int) $id;

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

        $this->setPriceSettings($service, self::TAX_APPLIES_TO_ONLY_SERVICES);

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
    public function getServices($start, $end, $typeResult = 'all')
    {
        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $start = (int) $start;
        $end = (int) $end;

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
        $buyerId = 0,
        $status = 0,
        $nodeType = 0,
        $nodeId = 0
    ) {
        $conditions = null;
        $groupBy = '';
        $buyerId = (int) $buyerId;
        $status = (int) $status;
        $nodeType = (int) $nodeType;
        $nodeId = (int) $nodeId;

        $defaultOrder = 'ss.id ASC';

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

        $servicesTable = Database::get_main_table(self::TABLE_SERVICES);
        $servicesSaleTable = Database::get_main_table(self::TABLE_SERVICES_SALE);

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
    public function getServiceSale($id)
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
    public function cancelServiceSale($serviceSaleId)
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
    public function completeServiceSale($serviceSaleId)
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
     * @param string $name      Optional. The name filter
     * @param int    $min       Optional. The minimum price filter
     * @param int    $max       Optional. The maximum price filter
     * @param mixed  $appliesTo optional
     *
     * @return array
     */
    public function getCatalogServiceList($start, $end, $name = null, $min = 0, $max = 0, $appliesTo = '', $typeResult = 'all')
    {
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

        $start = (int) $start;
        $end = (int) $end;

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
    public function registerServiceSale($serviceId, $paymentType, $infoSelect)
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
            'node_id' => (int) $infoSelect,
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
            'payment_type' => (int) $paymentType,
        ];

        $returnedServiceSaleId = Database::insert(self::TABLE_SERVICES_SALE, $values);

        return $returnedServiceSaleId;
    }

    /**
     * Save Culqi configuration params.
     *
     * @param array $params
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveCulqiParameters($params)
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
     * Save Global Parameters.
     *
     * @param array $params
     *
     * @return int Rows affected. Otherwise return false
     */
    public function saveGlobalParameters($params)
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
     * @param int $productType
     *
     * @return bool
     */
    public function checkTaxEnabledInProduct($productType)
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
     * Filter the registered courses for show in plugin catalog.
     */
    private function getCourses($first, $maxResults)
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
    private function getUserStatusForSession($userId, Session $session)
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
    private function getUserStatusForCourse($userId, Course $course)
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
    private function updateSaleStatus($saleId, $newStatus = self::SALE_STATUS_PENDING)
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
     * @param int    $start
     * @param int    $end
     * @param string $name            Optional. The name filter
     * @param int    $min             Optional. The minimun price filter
     * @param int    $max             Optional. The maximum price filter
     * @param string $max             Optional. all and count
     * @param int    $sessionCategory Optional. Session category id
     *
     * @return array
     */
    private function filterSessionList($start, $end, $name = null, $min = 0, $max = 0, $typeResult = 'all', $sessionCategory = 0)
    {
        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $min = floatval($min);
        $max = floatval($max);
        $sessionCategory = (int) $sessionCategory;

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

        $start = (int) $start;
        $end = (int) $end;

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
    private function filterCourseList($start, $end, $name = '', $min = 0, $max = 0, $typeResult = 'all')
    {
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
        $start = (int) $start;
        $end = (int) $end;

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
     * Update the service sale status.
     *
     * @param int $serviceSaleId The service sale ID
     * @param int $newStatus     The new status
     *
     * @return bool
     */
    private function updateServiceSaleStatus(
        $serviceSaleId,
        $newStatus = self::SERVICE_STATUS_PENDING
    ) {
        $serviceSaleTable = Database::get_main_table(self::TABLE_SERVICES_SALE);

        return Database::update(
            $serviceSaleTable,
            ['status' => (int) $newStatus],
            ['id = ?' => (int) $serviceSaleId]
        );
    }
}
