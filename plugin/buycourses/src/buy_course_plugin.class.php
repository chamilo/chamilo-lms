<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Course;

/**
 * Plugin class for the BuyCourses plugin
 * @package chamilo.plugin.buycourses
 * @author Jose Angel Ruiz <jaruiz@nosolored.com>
 * @author Imanol Losada <imanol.losada@beeznest.com>
 * @author Alex Aragón <alex.aragon@beeznest.com>
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author José Loguercio Silva  <jose.loguercio@beeznest.com>
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
    const PRODUCT_TYPE_COURSE = 1;
    const PRODUCT_TYPE_SESSION = 2;
    const PAYMENT_TYPE_PAYPAL = 1;
    const PAYMENT_TYPE_TRANSFER = 2;
    const PAYOUT_STATUS_CANCELED = 2;
    const PAYOUT_STATUS_PENDING = 0;
    const PAYOUT_STATUS_COMPLETED = 1;
    const SALE_STATUS_CANCELED = -1;
    const SALE_STATUS_PENDING = 0;
    const SALE_STATUS_COMPLETED = 1;

    /**
     *
     * @return StaticPlugin
     */
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            "
                Jose Angel Ruiz - NoSoloRed (original author) <br/>
                Francis Gonzales and Yannick Warnier - BeezNest (integration) <br/>
                Alex Aragón - BeezNest (Design icons and css styles) <br/>
                Imanol Losada - BeezNest (introduction of sessions purchase) <br/>
                Angel Fernando Quiroz Campos - BeezNest (cleanup and new reports) <br/>
                José Loguercio Silva - BeezNest (pay teachers and commissions)
            ",
            array(
                'show_main_menu_tab' => 'boolean',
                'include_sessions' => 'boolean',
                'paypal_enable' => 'boolean',
                'transfer_enable' => 'boolean',
                'commissions_enable' => 'boolean',
                'unregistered_users_enable' => 'boolean'
            )
        );
    }

    /**
     * This method creates the tables required to this plugin
     */
    function install()
    {
        $tablesToBeCompared = array(
            self::TABLE_PAYPAL,
            self::TABLE_TRANSFER,
            self::TABLE_ITEM_BENEFICIARY,
            self::TABLE_ITEM,
            self::TABLE_SALE,
            self::TABLE_CURRENCY,
            self::TABLE_COMMISSION,
            self::TABLE_PAYPAL_PAYOUTS
        );
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->getSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if ($tables) {
            return false;
        }

        require_once api_get_path(SYS_PLUGIN_PATH) . 'buycourses/database.php';
    }

    /**
     * This method drops the plugin tables
     */
    function uninstall()
    {
        $tablesToBeDeleted = array(
            self::TABLE_PAYPAL,
            self::TABLE_TRANSFER,
            self::TABLE_ITEM_BENEFICIARY,
            self::TABLE_ITEM,
            self::TABLE_SALE,
            self::TABLE_CURRENCY,
            self::TABLE_COMMISSION,
            self::TABLE_PAYPAL_PAYOUTS
        );

        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

    /**
     * This function verify if the plugin is enable and return the price info for a course or session in the new grid catalog
     * for 1.11.x , the main purpose is to show if a course or session is in sale it shows in the main platform course catalog
     * so the old buycourses plugin catalog can be deprecated.
     * @param int $productId course or session id
     * @param int $productType course or session type
     * @return mixed bool|string html
     */
    public function buyCoursesForGridCatalogVerificator($productId, $productType) {
        $return = [];
        $paypal = $this->get('paypal_enable') === 'true';
        $transfer = $this->get('transfer_enable') === 'true';

        if ($paypal || $transfer) {
            $item = $this->getItemByProduct(intval($productId), $productType);
            $return['html'] = '<div class="buycourses-price">';
            if ($item) {
                $return['html'] .= '<span class="label label-primary"><b>'. $item['iso_code'] .' ' . $item['price'] . '</b></span>';
                $return['verificator'] = true;
            } else {
                $return['html'] .= '<span class="label label-primary"><b>'. $this->get_lang('Free'). '</b></span>';
                $return['verificator'] = false;
            }
            $return['html'] .= '</div>';
        } else {
            return false;
        }

        return $return;
    }

    /**
     * Return the buyCourses plugin button to buy the course
     * @param int $productId
     * @param int $productType
     * @return string $html
     */
    public function returnBuyCourseButton($productId, $productType) {
        $url = api_get_path(WEB_PLUGIN_PATH) .
            'buycourses/src/process.php?i=' .
            intval($productId) .
            '&t=' .
            $productType
        ;

        $html = ' <a class="btn btn-success btn-sm" title="' . $this->get_lang('Buy') . '" href="' . $url . '">' .
            Display::returnFontAwesomeIcon('fa fa-shopping-cart') . '</a>';

        return $html;
    }

    /**
     * Get the currency for sales
     * @return array The selected currency. Otherwise return false
     */
    public function getSelectedCurrency()
    {
        return Database::select(
            '*',
            Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY),
            [
                'where' => ['status = ?' => true]
            ],
            'first'
        );
    }

    /**
     * Get a list of currencies
     * @return array The currencies. Otherwise return false
     */
    public function getCurrencies()
    {
        return Database::select(
            '*',
            Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY)
        );
    }

    /**
     * Save the selected currency
     * @param int $selectedId The currency Id
     */
    public function selectCurrency($selectedId)
    {
        $currencyTable = Database::get_main_table(
            BuyCoursesPlugin::TABLE_CURRENCY
        );

        Database::update(
            $currencyTable,
            ['status' => 0]
        );
        Database::update(
            $currencyTable,
            ['status' => 1],
            ['id = ?' => intval($selectedId)]
        );
    }

    /**
     * Save the PayPal configuration params
     * @param array $params
     * @return int Rows affected. Otherwise return false
     */
    public function savePaypalParams($params)
    {
        return Database::update(
            Database::get_main_table(BuyCoursesPlugin::TABLE_PAYPAL),
            [
                'username' => $params['username'],
                'password' => $params['password'],
                'signature' => $params['signature'],
                'sandbox' => isset($params['sandbox'])
            ],
            ['id = ?' => 1]
        );
    }

    /**
     * Gets the stored PayPal params
     * @return array
     */
    public function getPaypalParams()
    {
        return Database::select(
            '*',
            Database::get_main_table(BuyCoursesPlugin::TABLE_PAYPAL),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Save a transfer account information
     * @param array $params The transfer account
     * @return int Rows affected. Otherwise return false
     */
    public function saveTransferAccount($params)
    {
        return Database::insert(
            Database::get_main_table(self::TABLE_TRANSFER),
            [
                'name' => $params['tname'],
                'account' => $params['taccount'],
                'swift' => $params['tswift']
            ]
        );
    }

    /**
     * Get a list of transfer accounts
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
     * Remove a transfer account
     * @param int $id The transfer account ID
     * @return int Rows affected. Otherwise return false
     */
    public function deleteTransferAccount($id)
    {
        return Database::delete(
            Database::get_main_table(self::TABLE_TRANSFER),
            ['id = ?' => intval($id)]
        );
    }

    /**
     * Filter the registered courses for show in plugin catalog
     * @return array
     */
    private function getCourses()
    {
        $entityManager = Database::getManager();
        $query = $entityManager->createQueryBuilder();

        $courses = $query
            ->select('c')
            ->from('ChamiloCoreBundle:Course', 'c')
            ->leftJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'sc',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'c = sc.course'
            )
            ->where(
                $query->expr()->isNull('sc.course')
            )
            ->getQuery()
            ->getResult();

        return $courses;
    }

    /**
     * Get the item data
     * @param int $productId The item ID
     * @param int $itemType The item type
     * @return array
     */
    public function getItemByProduct($productId, $itemType)
    {
        $buyItemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);
        $buyCurrencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);

        $fakeItemFrom = "
            $buyItemTable i
            INNER JOIN $buyCurrencyTable c
                ON i.currency_id = c.id
        ";

        return Database::select(
            ['i.*', 'c.iso_code'],
            $fakeItemFrom,
            [
                'where' => [
                    'i.product_id = ? AND i.product_type = ?' => [
                        intval($productId),
                        intval($itemType)
                    ]
                ]
            ],
            'first'
        );
    }

    /**
     * List courses details from the configuration page
     * @return array
     */
    public function getCoursesForConfiguration()
    {
        $courses = $this->getCourses();

        if (empty($courses)) {
            return[];
        }

        $configurationCourses = [];
        $currency = $this->getSelectedCurrency();

        foreach ($courses as $course) {
            $configurationCourses[] = $this->getCourseForConfiguration($course, $currency);
        }

        return $configurationCourses;
    }

    /**
     * List sessions details from the buy-session table and the session table
     * @return array The sessions. Otherwise return false
     */
    public function getSessionsForConfiguration()
    {
        $auth = new Auth();
        $sessions = $auth->browseSessions();

        $currency = $this->getSelectedCurrency();

        $items = [];

        foreach ($sessions as $session) {
            $items[] = $this->getSessionForConfiguration($session, $currency);
        }

        return $items;
    }

    /**
     * Get the user status for the session
     * @param int $userId The user ID
     * @param Session $session The session
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
                        self::SALE_STATUS_PENDING
                    ]
                ]
            ],
            'first'
        );

        if ($sale['qty'] > 0) {
            return "TMP";
        }

        // Check if user is already subscribe to session
        $userSubscription = $scuRepo->findBy([
            'session' => $session,
            'user' => $userId
        ]);

        if (!empty($userSubscription)) {
            return 'YES';
        }

        return 'NO';
    }

    /**
     * Lists current user session details, including each session course details
     * @param string $name Optional. The name filter
     * @param int $min Optional. The minimum price filter
     * @param int $max Optional. The maximum price filter
     * @return array
     */
    public function getCatalogSessionList($name = null, $min = 0, $max = 0)
    {
        $sessions = $this->filterSessionList($name, $min, $max);

        $sessionCatalog = array();
        // loop through all sessions
        foreach ($sessions as $session) {
            $sessionCourses = $session->getCourses();

            if (empty($sessionCourses)) {
                continue;
            }

            $item = $this->getItemByProduct($session->getId(), self::PRODUCT_TYPE_SESSION);

            if (empty($item)) {
                continue;
            }

            $sessionData = $this->getSessionInfo($session->getId());
            $sessionData['coach'] = $session->getGeneralCoach()->getCompleteName();
            $sessionData['enrolled'] = $this->getUserStatusForSession(api_get_user_id(), $session);
            $sessionData['courses'] = array();

            foreach ($sessionCourses as $sessionCourse) {
                $course = $sessionCourse->getCourse();

                $sessionCourseData = [
                    'title' => $course->getTitle(),
                    'coaches' => []
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
     * Get the user status for the course
     * @param int $userId The user Id
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
                        self::SALE_STATUS_PENDING
                    ]
                ]
            ],
            'first'
        );

        if ($sale['qty'] > 0) {
            return "TMP";
        }

        // Check if user is already subscribe to course
        $userSubscription = $cuRepo->findBy([
            'course' => $course,
            'user' => $userId
        ]);

        if (!empty($userSubscription)) {
            return 'YES';
        }

        return 'NO';
    }

    /**
     * Lists current user course details
     * @param string $name Optional. The name filter
     * @param int $min Optional. The minimum price filter
     * @param int $max Optional. The maximum price filter
     * @return array
     */
    public function getCatalogCourseList($name = null, $min = 0, $max = 0)
    {
        $courses = $this->filterCourseList($name, $min, $max);

        if (empty($courses)) {
            return [];
        }

        $courseCatalog = [];

        foreach ($courses as $course) {
            $item = $this->getItemByProduct($course->getId(), self::PRODUCT_TYPE_COURSE);

            if (empty($item)) {
                continue;
            }

            $courseItem = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'course_img' => null,
                'price' => $item['price'],
                'currency' => $item['iso_code'],
                'teachers' => [],
                'enrolled' => $this->getUserStatusForCourse(api_get_user_id(), $course)
            ];

            foreach ($course->getTeachers() as $courseUser) {
                $teacher = $courseUser->getUser();
                $courseItem['teachers'][] = $teacher->getCompleteName();
            }

            //check images
            $possiblePath = api_get_path(SYS_COURSE_PATH);
            $possiblePath .= $course->getDirectory();
            $possiblePath .= '/course-pic.png';

            if (file_exists($possiblePath)) {
                $courseItem['course_img'] = api_get_path(WEB_COURSE_PATH)
                    . $course->getDirectory()
                    . '/course-pic.png';
            }

            $courseCatalog[] = $courseItem;
        }

        return $courseCatalog;
    }

    /**
     * Get course info
     * @param int $courseId The course ID
     * @return array
     */
    public function getCourseInfo($courseId)
    {
        $entityManager = Database::getManager();
        $course = $entityManager->find('ChamiloCoreBundle:Course', $courseId);

        if (empty($course)) {
            return [];
        }

        $item = $this->getItemByProduct($course->getId(), self::PRODUCT_TYPE_COURSE);

        if (empty($item)) {
            return [];
        }

        $courseInfo = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'code' => $course->getCode(),
            'visual_code' => $course->getVisualCode(),
            'teachers' => [],
            'price' => $item['price'],
            'currency' => $item['iso_code'],
            'course_img' => null
        ];

        $courseTeachers = $course->getTeachers();

        foreach ($courseTeachers as $teacher) {
            $courseInfo['teachers'][] = $teacher->getUser()->getCompleteName();
        }

        $possiblePath = api_get_path(SYS_COURSE_PATH);
        $possiblePath .= $course->getDirectory();
        $possiblePath .= '/course-pic.png';

        if (file_exists($possiblePath)) {
            $courseInfo['course_img'] = api_get_path(WEB_COURSE_PATH)
                . $course->getDirectory()
                . '/course-pic.png';
        }

        return $courseInfo;
    }

    /**
     * Get session info
     * @param array $sessionId The session ID
     * @return array
     */
    public function getSessionInfo($sessionId)
    {
        $entityManager = Database::getManager();
        $session = $entityManager->find('ChamiloCoreBundle:Session', $sessionId);

        if (empty($session)) {
            return [];
        }

        $item = $this->getItemByProduct($session->getId(), self::PRODUCT_TYPE_SESSION);

        if (empty($item)) {
            return [];
        }

        $sessionDates = SessionManager::parseSessionDates([
            'display_start_date' => $session->getDisplayStartDate(),
            'display_end_date' => $session->getDisplayEndDate(),
            'access_start_date' => $session->getAccessStartDate(),
            'access_end_date' => $session->getAccessEndDate(),
            'coach_access_start_date' => $session->getCoachAccessStartDate(),
            'coach_access_end_date' => $session->getCoachAccessEndDate()
        ]);

        $sessionInfo = [
            'id' => $session->getId(),
            'name' => $session->getName(),
            'dates' => $sessionDates,
            'courses' => [],
            'price' => $item['price'],
            'currency' => $item['iso_code'],
            'image' => null
        ];

        $fieldValue = new ExtraFieldValue('session');
        $sessionImage = $fieldValue->get_values_by_handler_and_field_variable(
            $session->getId(),
            'image'
        );

        if (!empty($sessionImage)) {
            $sessionInfo['image'] = api_get_path(WEB_UPLOAD_PATH) . $sessionImage['value'];
        }

        $sessionCourses = $session->getCourses();

        foreach ($sessionCourses as $sessionCourse) {
            $course = $sessionCourse->getCourse();

            $sessionCourseData = [
                'title' => $course->getTitle(),
                'coaches' => []
            ];

            $userCourseSubscriptions = $session->getUserCourseSubscriptionsByStatus(
                $course,
                Chamilo\CoreBundle\Entity\Session::COACH
            );

            foreach ($userCourseSubscriptions as $userCourseSubscription) {
                $user = $userCourseSubscription->getUser();
                $sessionCourseData['coaches'][] = $user->getCompleteName();
            }

            $sessionInfo['courses'][] = $sessionCourseData;
        }

        return $sessionInfo;
    }

    /**
     * Get registered item data
     * @param int $itemId The item ID
     * @return array
     */
    public function getItem($itemId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_ITEM),
            [
                'where' => ['id = ?' => intval($itemId)]
            ],
            'first'
        );
    }

    /**
     * Register a sale
     * @param int $itemId The product ID
     * @param int $paymentType The payment type
     * @return boolean
     */
    public function registerSale($itemId, $paymentType)
    {
        if (!in_array($paymentType, [self::PAYMENT_TYPE_PAYPAL, self::PAYMENT_TYPE_TRANSFER])) {
            return false;
        }

        $entityManager = Database::getManager();

        $item = $this->getItem($itemId);

        if (empty($item)) {
            return false;
        }

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
            'price' => $item['price'],
            'status' => self::SALE_STATUS_PENDING,
            'payment_type' => intval($paymentType)
        ];

        return Database::insert(self::TABLE_SALE, $values);
    }

    /**
     * Get sale data by ID
     * @param int $saleId The sale ID
     * @return array
     */
    public function getSale($saleId)
    {
        return Database::select(
            '*',
            Database::get_main_table(self::TABLE_SALE),
            [
                'where' => ['id = ?' => intval($saleId)]
            ],
            'first'
        );
    }

    /**
     * Get a list of sales by the payment type
     * @param int $paymentType The payment type to filter (default : Paypal)
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByPaymentType($paymentType = self::PAYMENT_TYPE_PAYPAL)
    {
        $saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => ['s.payment_type = ? AND s.status = ?' => [intval($paymentType), self::SALE_STATUS_COMPLETED]],
                'order' => 'id DESC'
            ]
        );
    }

    /**
     * Get currency data by ID
     * @param int $currencyId The currency ID
     * @return array
     */
    public function getCurrency($currencyId)
    {
        return Database::select(
            '*',
            Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY),
            [
                'where' => ['id = ?' => intval($currencyId)]
            ],
            'first'
        );
    }

    /**
     * Update the sale status
     * @param int $saleId The sale ID
     * @param int $newStatus The new status
     * @return boolean
     */
    private function updateSaleStatus($saleId, $newStatus = self::SALE_STATUS_PENDING)
    {
        $saleTable = Database::get_main_table(self::TABLE_SALE);

        return Database::update(
            $saleTable,
            ['status' => intval($newStatus)],
            ['id = ?' => intval($saleId)]
        );
    }

    /**
     * Complete sale process. Update sale status to completed
     * @param int $saleId The sale ID
     * @return boolean
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

                $saleIsCompleted = CourseManager::subscribe_user($sale['user_id'], $course['code']);
                break;
            case self::PRODUCT_TYPE_SESSION:
                SessionManager::subscribe_users_to_session(
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
        }

        return $saleIsCompleted;
    }

    /**
     * Update sale status to canceled
     * @param int $saleId The sale ID
     */
    public function cancelSale($saleId)
    {
        $this->updateSaleStatus($saleId, self::SALE_STATUS_CANCELED);
    }

    /**
     * Get payment types
     * @return array
     */
    public function getPaymentTypes()
    {
        return [
            self::PAYMENT_TYPE_PAYPAL => 'PayPal',
            self::PAYMENT_TYPE_TRANSFER => $this->get_lang('BankTransfer')
        ];
    }

    /**
     * Get a list of sales by the status
     * @param int $status The status to filter
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByStatus($status = self::SALE_STATUS_PENDING)
    {
        $saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $innerJoins = "
            INNER JOIN $currencyTable c ON s.currency_id = c.id
            INNER JOIN $userTable u ON s.user_id = u.id
        ";

        return Database::select(
            ['c.iso_code', 'u.firstname', 'u.lastname', 's.*'],
            "$saleTable s $innerJoins",
            [
                'where' => ['s.status = ?' => intval($status)],
                'order' => 'id DESC'
            ]
        );
    }

    /**
     * Get the statuses for sales
     * @return array
     */
    public function getSaleStatuses()
    {
        return [
            self::SALE_STATUS_CANCELED => $this->get_lang('SaleStatusCanceled'),
            self::SALE_STATUS_PENDING => $this->get_lang('SaleStatusPending'),
            self::SALE_STATUS_COMPLETED => $this->get_lang('SaleStatusCompleted')
        ];
    }

    /**
     * Get the statuses for Payouts
     * @return array
     */
    public function getPayoutStatuses()
    {
        return [
            self::PAYOUT_STATUS_CANCELED => $this->get_lang('PayoutStatusCanceled'),
            self::PAYOUT_STATUS_PENDING => $this->get_lang('PayoutStatusPending'),
            self::PAYOUT_STATUS_COMPLETED => $this->get_lang('PayoutStatusCompleted')
        ];
    }

    /**
     * Get the list of product types
     * @return array
     */
    public function getProductTypes()
    {
        return [
            self::PRODUCT_TYPE_COURSE => get_lang('Course'),
            self::PRODUCT_TYPE_SESSION => get_lang('Session')
        ];
    }

    /**
     * Search filtered sessions by name, and range of price
     * @param string $name Optional. The name filter
     * @param int $min Optional. The minimun price filter
     * @param int $max Optional. The maximum price filter
     * @return array
     */
    private function filterSessionList($name = null, $min = 0, $max = 0)
    {
        if (empty($name) && empty($min) && empty($max)) {
            $auth = new Auth();
            return $auth->browseSessions();
        }

        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $min = floatval($min);
        $max = floatval($max);

        $innerJoin = "$itemTable i ON s.id = i.product_id";
        $whereConditions = [
            'i.product_type = ? ' => self::PRODUCT_TYPE_SESSION
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

        $sessionIds = Database::select(
            's.id',
            "$sessionTable s INNER JOIN $innerJoin",
            ['where' => $whereConditions]
        );

        if (!$sessionIds) {
            return [];
        }

        $sessions = [];

        foreach ($sessionIds as $sessionId) {
            $sessions[] = Database::getManager()->find('ChamiloCoreBundle:Session', $sessionId);
        }

        return $sessions;
    }

    /**
     * Search filtered courses by name, and range of price
     * @param string $name Optional. The name filter
     * @param int $min Optional. The minimun price filter
     * @param int $max Optional. The maximum price filter
     * @return array
     */
    private function filterCourseList($name = null, $min = 0, $max = 0)
    {
        if (empty($name) && empty($min) && empty($max)) {
            return $this->getCourses();
        }

        $itemTable = Database::get_main_table(self::TABLE_ITEM);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

        $min = floatval($min);
        $max = floatval($max);

        $innerJoin = "$itemTable i ON c.id = i.product_id";
        $whereConditions = [
            'i.product_type = ? ' => self::PRODUCT_TYPE_COURSE
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

        $courseIds = Database::select(
            'c.id',
            "$courseTable c INNER JOIN $innerJoin",
            ['where' => $whereConditions]
        );

        if (!$courseIds) {
            return [];
        }

        $courses = [];

        foreach ($courseIds as $courseId) {
            $courses[] = Database::getManager()->find('ChamiloCoreBundle:Course', $courseId);
        }

        return $courses;
    }

    /**
     * Generates a random text (used for order references)
     * @param int $length Optional. Length of characters
     * @param boolean $lowercase Optional. Include lowercase characters
     * @param boolean $uppercase Optional. Include uppercase characters
     * @param boolean $numbers Optional. Include numbers
     * @return string
     */
    public static function randomText(
        $length = 6,
        $lowercase = true,
        $uppercase = true,
        $numbers = true
    )
    {
        $salt = $lowercase ? 'abchefghknpqrstuvwxyz' : '';
        $salt .= $uppercase ? 'ACDEFHKNPRSTUVWXYZ' : '';
        $salt .= $numbers ? (strlen($salt) ? '2345679' : '0123456789') : '';

        if (strlen($salt) == 0) {
            return '';
        }

        $str = '';

        srand((double)microtime() * 1000000);

        for ($i = 0; $i < $length; $i++) {
            $numbers = rand(0, strlen($salt) - 1);
            $str .= substr($salt, $numbers, 1);
        }

        return $str;
    }

    /**
     * Generates an order reference
     * @param int $userId The user ID
     * @param int $productType The course/session type
     * @param int $productId The course/session ID
     * @return string
     */
    public function generateReference($userId, $productType, $productId)
    {
        return vsprintf(
            "%d-%d-%d-%s",
            [$userId, $productType, $productId, self::randomText()]
        );
    }

    /**
     * Get a list of sales by the user
     * @param string $term The search term
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByUser($term)
    {
        $term = trim($term);

        if (empty($term)) {
            return [];
        }

        $saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
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
                    'u.username LIKE %?% OR ' => $term,
                    'u.lastname LIKE %?% OR ' => $term,
                    'u.firstname LIKE %?%' => $term
                ],
                'order' => 'id DESC'
            ]
        );
    }

    /**
     * Get a list of sales by the user id
     * @param int $id The user id
     * @return array The sale list. Otherwise return false
     */
    public function getSaleListByUserId($id)
    {

        if (empty($id)) {
            return [];
        }

        $saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
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
                    'u.id = ? AND s.status = ?' => [intval($id), BuyCoursesPlugin::SALE_STATUS_COMPLETED]
                ],
                'order' => 'id DESC'
            ]
        );
    }

    /**
     * Convert the course info to array with necessary course data for save item
     * @param Course $course
     * @param array $defaultCurrency Optional. Currency data
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
            'course_visibility' => $course->getVisibility(),
            'visible' => false,
            'currency' =>  empty($defaultCurrency) ? null : $defaultCurrency['iso_code'],
            'price' => 0.00
        ];

        $item = $this->getItemByProduct($course->getId(), self::PRODUCT_TYPE_COURSE);

        if ($item !== false) {
            $courseItem['item_id'] = $item['id'];
            $courseItem['visible'] = true;
            $courseItem['currency'] = $item['iso_code'];
            $courseItem['price'] = $item['price'];
        }

        return $courseItem;
    }

    /**
     * Convert the session info to array with necessary session data for save item
     * @param Session $session The session data
     * @param array $defaultCurrency Optional. Currency data
     * @return array
     */
    public function getSessionForConfiguration(Session $session, $defaultCurrency = null)
    {
        $buyItemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);
        $buyCurrencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);

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
            'currency' =>  empty($defaultCurrency) ? null : $defaultCurrency['iso_code'],
            'price' => 0.00
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
                    'i.product_type = ?' => self::PRODUCT_TYPE_SESSION
                ]
            ],
            'first'
        );

        if ($item !== false) {
            $sessionItem['item_id'] = $item['id'];
            $sessionItem['visible'] = true;
            $sessionItem['currency'] = $item['iso_code'];
            $sessionItem['price'] = $item['price'];
        }

        return $sessionItem;
    }

    /**
     * Get all beneficiaries for a item
     * @param int $itemId The item ID
     * @return array The beneficiries. Otherwise return false
     */
    public function getItemBeneficiaries($itemId)
    {
        $beneficiaryTable = Database::get_main_table(self::TABLE_ITEM_BENEFICIARY);

        return Database::select(
            '*',
            $beneficiaryTable,
            ['where' => [
                'item_id = ?' => intval($itemId)
            ]]
        );
    }

    /**
     * Delete a item with its beneficiaries
     * @param int $itemId The item ID
     * @return int The number of affected rows. Otherwise return false
     */
    public function deleteItem($itemId)
    {
        $itemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);

        $affectedRows = Database::delete(
            $itemTable,
            ['id = ?' => intval($itemId)]
        );

        if (!$affectedRows) {
            return false;
        }

        return $this->deleteItemBeneficiaries($itemId);
    }

    /**
     * Register a item
     * @param array $itemData The item data
     * @return int The item ID. Otherwise return false
     */
    public function registerItem(array $itemData)
    {
        $itemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);

        return Database::insert($itemTable, $itemData);
    }

    /**
     * Update the item data by product
     * @param array $itemData The item data to be updated
     * @param int $productId The product ID
     * @param int $productType The type of product
     * @return int The number of affected rows. Otherwise return false
     */
    public function updateItem(array $itemData, $productId, $productType)
    {
        $itemTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM);

        return Database::update(
            $itemTable,
            $itemData,
            [
                'product_id = ? AND ' => intval($productId),
                'product_type' => $productType
            ]
        );
    }

    /**
     * Remove all beneficiaries for a item
     * @param int $itemId The user ID
     * @return int The number of affected rows. Otherwise return false
     */
    public function deleteItemBeneficiaries($itemId)
    {
        $beneficiaryTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM_BENEFICIARY);

        return Database::delete(
            $beneficiaryTable,
            ['item_id = ?' => intval($itemId)]
        );
    }

    /**
     * Register the beneficiaries users with the sale of item
     * @param int $itemId The item ID
     * @param array $userIds The beneficiary user ID and Teachers commissions if enabled
     */
    public function registerItemBeneficiaries($itemId, array $userIds)
    {
        $beneficiaryTable = Database::get_main_table(BuyCoursesPlugin::TABLE_ITEM_BENEFICIARY);

        $this->deleteItemBeneficiaries($itemId);

        foreach ($userIds as $userId => $commissions) {
            Database::insert(
                $beneficiaryTable,
                [
                    'item_id' => intval($itemId),
                    'user_id' => intval($userId),
                    'commissions' => intval($commissions)
                ]
            );
        }
    }

    /**
     * Check if a course is valid for sale
     * @param Course $course The course
     * @return boolean
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
     * Gets the beneficiaries with commissions and current paypal accounts by sale
     * @param int $saleId The sale ID
     * @return array
     */
    public function getBeneficiariesBySale($saleId)
    {

        $userTable = Database::get_main_table(TABLE_MAIN_USER);

        $beneficiaries = [];
        $sale = $this->getSale($saleId);
        $item = $this->getItemByProduct($sale['product_id'], $sale['product_type']);
        $itemBeneficiaries = $this->getItemBeneficiaries($item['id']);
        return $itemBeneficiaries;

    }

    /**
     * gets all payouts
     * @param int $status - default 0 - pending
     * @param int $payoutId - for get an individual payout if want all then false
     * @return array
     */
    public function getPayouts($status = self::PAYOUT_STATUS_PENDING, $payoutId = false, $userId = false)
    {
        $condition = ($payoutId) ? 'AND p.id = '. intval($payoutId) : '';
        $condition2 = ($userId) ? ' AND p.user_id = ' . intval($userId) : '';
        $typeResult = ($condition) ? 'first' : 'all';
        $payoutsTable = Database::get_main_table(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS);
        $saleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SALE);
        $currencyTable = Database::get_main_table(BuyCoursesPlugin::TABLE_CURRENCY);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $extraFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $paypalExtraField = Database::select(
            "*",
            $extraFieldTable,
            [
                'where' => ['variable = ?' => 'paypal']
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
            AND field_id = " . intval($paypalExtraField['id']) . "
        ";

        $payouts = Database::select(
            "p.* , u.firstname, u.lastname, efv.value as paypal_account, s.reference as sale_reference, s.price as item_price, c.iso_code",
            "$payoutsTable p $innerJoins",
            [
                'where' => ['p.status = ? '.$condition . ' ' .$condition2 => $status]
            ],
            $typeResult
        );

        return $payouts;
    }

    /**
     * Verify if the beneficiary have a paypal account
     * @param int $userId
     * @return true if the user have a paypal account, false if not
     */
    public function verifyPaypalAccountByBeneficiary($userId)
    {
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $extraFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        $paypalExtraField = Database::select(
            "*",
            $extraFieldTable,
            [
                'where' => ['variable = ?' => 'paypal']
            ],
            'first'
        );

        if (!$paypalExtraField) {
            return false;
        }

        $paypalFieldId = $paypalExtraField['id'];

        $paypalAccount = Database::select(
            "value",
            $extraFieldValues,
            [
                'where' => ['field_id = ? AND item_id = ?' => [intval($paypalFieldId), intval($userId)]]
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
     * Register the users payouts
     * @param int $saleId The sale ID
     * @return array
     */
    public function storePayouts($saleId)
    {
        $payoutsTable = Database::get_main_table(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS);
        $platformCommission = $this->getPlatformCommission();

        $sale = $this->getSale($saleId);
        $teachersCommission = number_format((floatval($sale['price']) * intval($platformCommission['commission']))/100, 2);


        $beneficiaries = $this->getBeneficiariesBySale($saleId);
        foreach ($beneficiaries as $beneficiary) {
            Database::insert(
                $payoutsTable,
                [
                    'date' => $sale['date'],
                    'payout_date' => getdate(),
                    'sale_id' => intval($saleId),
                    'user_id' => $beneficiary['user_id'],
                    'commission' => number_format((floatval($teachersCommission) * intval($beneficiary['commissions']))/100, 2),
                    'status' => self::PAYOUT_STATUS_PENDING
                ]
            );
        }
    }

    /**
     * Register the users payouts
     * @param int $payoutId The payout ID
     * @param int $status The status to set (-1 to cancel, 0 to pending, 1 to completed)
     * @return array
     */
    public function setStatusPayouts($payoutId, $status)
    {
        $payoutsTable = Database::get_main_table(BuyCoursesPlugin::TABLE_PAYPAL_PAYOUTS);

        Database::update(
            $payoutsTable,
            ['status' => intval($status)],
            ['id = ?' => intval($payoutId)]
        );

    }

    /**
     * Gets the stored platform commission params
     * @return array
     */
    public function getPlatformCommission()
    {
        return Database::select(
            '*',
            Database::get_main_table(BuyCoursesPlugin::TABLE_COMMISSION),
            ['id = ?' => 1],
            'first'
        );
    }

    /**
     * Update the platform commission
     * @param int $params platform commission
     * @return int The number of affected rows. Otherwise return false
     */
    public function updateCommission($params)
    {
        $commissionTable = Database::get_main_table(BuyCoursesPlugin::TABLE_COMMISSION);

        return Database::update(
            $commissionTable,
            ['commission' => intval($params['commission'])]
        );
    }

}
