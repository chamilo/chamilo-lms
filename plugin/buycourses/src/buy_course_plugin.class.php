<?php
/* For license terms, see /license.txt */
/**
 * Description of buy_courses_plugin
 * @package chamilo.plugin.buycourses
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
 * @author Imanol Losada      <imanol.losada@beeznest.com>
 * @author Alex Aragón      <alex.aragon@beeznest.com>
 */
/**
 * Plugin class for the BuyCourses plugin
 */
class BuyCoursesPlugin extends Plugin
{
    const PRODUCT_TYPE_COURSE = 1;
    const PRODUCT_TYPE_SESSION = 2;

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
            'Jose Angel Ruiz - NoSoloRed (original author),
            Francis Gonzales and Yannick Warnier - BeezNest (integration),
            Alex Aragón - BeezNest (Design icons and css styles),
            Imanol Losada - BeezNest (introduction of sessions purchase)',
            array(
                'show_main_menu_tab' => 'boolean',
                'include_sessions' => 'boolean',
                'paypal_enable' => 'boolean',
                'transfer_enable' => 'boolean',
                'unregistered_users_enable' => 'boolean'
            )
        );
    }

    /**
     * This method creates the tables required to this plugin
     */
    function install()
    {
        require_once api_get_path(SYS_PLUGIN_PATH) . 'buycourses/database.php';
    }

    /**
     * This method drops the plugin tables
     */
    function uninstall()
    {
        require_once __DIR__.'/../config.php';
        $tablesToBeDeleted = array(
            TABLE_BUY_SESSION,
            TABLE_BUY_SESSION_COURSE,
            TABLE_BUY_SESSION_TEMPORARY,
            TABLE_BUY_SESSION_SALE,
            TABLE_BUY_COURSE,
            TABLE_BUY_COURSE_COUNTRY,
            TABLE_BUY_COURSE_PAYPAL,
            TABLE_BUY_COURSE_TRANSFER,
            TABLE_BUY_COURSE_TEMPORAL,
            TABLE_BUY_COURSE_SALE
        );
        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

    /**
     * Get the currency for sales
     * @return array The selected currency. Otherwise return false
     */
    public function getSelectedCurrency()
    {
        return Database::select(
            '*',
            Database::get_main_table(BuyCoursesUtils::TABLE_CURRENCY),
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
            Database::get_main_table(BuyCoursesUtils::TABLE_CURRENCY)
        );
    }

    /**
     * Save the selected currency
     * @param int $selectedId The currency Id
     */
    public function selectCurrency($selectedId)
    {
        $currencyTable = Database::get_main_table(
            BuyCoursesUtils::TABLE_CURRENCY
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
            Database::get_main_table(BuyCoursesUtils::TABLE_PAYPAL),
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
            Database::get_main_table(BuyCoursesUtils::TABLE_PAYPAL),
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
            Database::get_main_table(BuyCoursesUtils::TABLE_TRANSFER),
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
            Database::get_main_table(BuyCoursesUtils::TABLE_TRANSFER)
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
            Database::get_main_table(BuyCoursesUtils::TABLE_TRANSFER),
            ['id = ?' => intval($id)]
        );
    }

    /**
     * List courses details from the buy-course table and the course table
     * @return array The courses. Otherwise return false
     */
    public function getCourses()
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $sessionCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $buyItemTable = Database::get_main_table(BuyCoursesUtils::TABLE_ITEM);
        $buyCurrencyTable = Database::get_main_table(BuyCoursesUtils::TABLE_CURRENCY);

        $currency = $this->getSelectedCurrency();

        $items = [];

        $fakeCourseFrom = "
            $courseTable c
            LEFT JOIN $sessionCourseTable sc
                ON c.id = sc.c_id
            WHERE sc.c_id IS NULL
        ";

        $courses = Database::select('c.*', $fakeCourseFrom);

        $fakeItemFrom = "
            $buyItemTable i
            INNER JOIN $buyCurrencyTable c
                ON i.currency_id = c.id
        ";

        foreach ($courses as $course) {
            $courseItem = [
                'course_id' => $course['id'],
                'course_visual_code' => $course['visual_code'],
                'course_code' => $course['code'],
                'course_title' => $course['title'],
                'course_visibility' => $course['visibility'],
                'visible' => false,
                'currency' =>  empty($currency) ? null : $currency['iso_code'],
                'price' => 0.00
            ];

            $item = Database::select(
                ['i.*', 'c.iso_code'],
                $fakeItemFrom,
                [
                    'where' => [
                        'i.product_id = ? AND ' => $course['id'],
                        'i.product_type = ?' => self::PRODUCT_TYPE_COURSE
                    ]
                ],
                'first'
            );

            if ($item !== false) {
                $courseItem['visible'] = true;
                $courseItem['currency'] = $item['iso_code'];
                $courseItem['price'] = $item['price'];
            }

            $items[] = $courseItem;
        }

        return $items;
    }

    /**
     * List sessions details from the buy-session table and the session table
     * @return array The sessions. Otherwise return false
     */
    public function getSessions()
    {
        $buyItemTable = Database::get_main_table(BuyCoursesUtils::TABLE_ITEM);
        $buyCurrencyTable = Database::get_main_table(BuyCoursesUtils::TABLE_CURRENCY);

        $auth = new Auth();
        $sessions = $auth->browseSessions();

        $currency = $this->getSelectedCurrency();

        $items = [];

        $fakeItemFrom = "
            $buyItemTable i
            INNER JOIN $buyCurrencyTable c
                ON i.currency_id = c.id
        ";

        foreach ($sessions as $session) {
            $sessionItem = [
                'session_id' => $session->getId(),
                'session_name' => $session->getName(),
                'session_visibility' => $session->getVisibility(),
                'session_display_start_date' => null,
                'session_display_end_date' => null,
                'visible' => false,
                'currency' =>  empty($currency) ? null : $currency['iso_code'],
                'price' => 0.00
            ];

            if (!empty($session->getDisplayStartDate())) {
                $sessionItem['session_display_start_date'] = api_format_date(
                    $session->getDisplayStartDate()->format('Y-m-d h:i:s')
                );
            }

            if (!empty($session->getDisplayEndDate())) {
                $sessionItem['session_display_end_date'] = api_format_date(
                    $session->getDisplayEndDate()->format('Y-m-d h:i:s')
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
                $sessionItem['visible'] = true;
                $sessionItem['currency'] = $item['iso_code'];
                $sessionItem['price'] = $item['price'];
            }

            $items[] = $sessionItem;
        }

        return $items;
    }

    /**
     * Lists current user session details, including each session course details
     * @return array
     */
    public function getUserSessionList()
    {
        $buySessionTable = Database::get_main_table(TABLE_BUY_SESSION);
        $buySessionTemporaryTable = Database::get_main_table(
            TABLE_BUY_SESSION_TEMPORARY
        );
        $entityManager = Database::getManager();
        $scRepo = $entityManager->getRepository(
            'ChamiloCoreBundle:SessionRelCourse'
        );
        $scuRepo = $entityManager->getRepository(
            'ChamiloCoreBundle:SessionRelCourseRelUser'
        );
        $currentUserId = api_get_user_id();

        // get existing sessions
        $sql = "
            SELECT a.session_id, a.visible, a.price
            FROM $buySessionTable a
            WHERE a.visible = 1";
        $resSessions = Database::query($sql);
        $sessions = array();
        // loop through all sessions
        while ($rowSession = Database::fetch_assoc($resSessions)) {
            $session = $entityManager->find(
                'ChamiloCoreBundle:Session',
                $rowSession['session_id']
            );

            $sessionData = [
                'id' => $session->getId(),
                'name' => $session->getName(),
                'dates' => SessionManager::parseSessionDates([
                    'display_start_date' => $session->getDisplayStartDate(),
                    'display_end_date' => $session->getDisplayEndDate(),
                    'access_start_date' => $session->getAccessStartDate(),
                    'access_end_date' => $session->getAccessEndDate(),
                    'coach_access_start_date' => $session->getCoachAccessStartDate(),
                    'coach_access_end_date' => $session->getCoachAccessEndDate()
                ]),
                'price' => number_format($rowSession['price'], 2),
                'courses' => [],
                'enrolled' => 'NO'
            ];

            $userCourseSubscription = $scuRepo->findBy([
                'session' => $session,
                'status' => Chamilo\CoreBundle\Entity\Session::COACH
            ]);
            if (!empty($userCourseSubscription)) {
                $sessionCourseUser = $userCourseSubscription[0];
                $course = $sessionCourseUser->getCourse();
                $coaches = [];

                foreach ($userCourseSubscription as $sessionCourseUser) {
                    $coach = $sessionCourseUser->getUser();
                    $coaches[] = $coach->getCompleteName();
                }

                $sessionData['courses'][] = [
                    'title' => $course->getTitle(),
                    'coaches' => $coaches
                ];
            } else {
                $sessionCourses =  $scRepo->findBy([
                    'session' => $session
                ]);

                foreach ($sessionCourses as $sessionCourse) {
                    $course = $sessionCourse->getCourse();

                    $sessionData['courses'][] = ['title' => $course->getTitle()];
                }
            }

            if ($currentUserId > 0) {
                $sql = "
                    SELECT 1 FROM $buySessionTemporaryTable
                    WHERE session_id ='{$session->getId()}' AND
                    user_id='{$currentUserId}'";

                $result = Database::query($sql);

                if (Database::affected_rows($result) > 0) {
                    $sessionData['enrolled'] = "TMP";
                }

                $userSubscription = $scuRepo->findBy([
                    'session' => $session,
                    'user' => $currentUserId
                ]);

                if (!empty($userSubscription)) {
                    $sessionData['enrolled'] = "YES";
                }
            }

            $sessions[] = $sessionData;
        }
        return $sessions;
    }

    /**
     * Lists current user course details
     * @return array
     */
    public function getUserCourseList()
    {
        $buyCourseTable = Database::get_main_table(TABLE_BUY_COURSE);
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserTable = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $buyCourseTemporalTable = Database::get_main_table(
            TABLE_BUY_COURSE_TEMPORAL
        );
        $entityManager = Database::getManager();
        $cuRepo = $entityManager->getRepository(
            'ChamiloCoreBundle:CourseRelUser'
        );
        $currentUserId = api_get_user_id();

        $sql = "
            SELECT a.course_id, a.visible, a.price, b.*
            FROM $buyCourseTable a, $courseTable b
            WHERE a.course_id = b.id AND a.session_id = 0 AND a.visible = 1;";
        $res = Database::query($sql);
        $courses = array();
        while ($row = Database::fetch_assoc($res)) {
            $course = $entityManager->find(
                'ChamiloCoreBundle:Course',
                $row['course_id']
            );
            $courseData = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'code' => $course->getCode(),
                'course_img' => Display::return_icon(
                    'session_default.png',
                    null,
                    null,
                    null,
                    null,
                    true
                ),
                'price' => number_format($row['price'], 2),
                'teachers' => [],
                'enrolled' => 'NO',
            ];

            foreach ($course->getTeachers() as $courseUser) {
                $teacher = $courseUser->getUser();
                $courseData['teachers'][] = $teacher->getCompleteName();
            }

            //check if the user is enrolled
            if ($currentUserId > 0) {
                $sql = "
                    SELECT 1 FROM $buyCourseTemporalTable
                    WHERE course_code='{$course->getCode()}'
                    AND user_id = {$currentUserId}";
                $result = Database::query($sql);

                if (Database::affected_rows($result) > 0) {
                    $courseData['enrolled'] = "TMP";
                }

                $userSubscription = $cuRepo->findBy([
                    'course' => $course,
                    'user' => $currentUserId
                ]);
                
                if (!empty($userSubscription)) {
                    $courseData['enrolled'] = 'YES';
                }
            }

            //check images
            $possiblePath = api_get_path(SYS_COURSE_PATH);
            $possiblePath .= $course->getDirectory();
            $possiblePath .= '/course-pic.png';

            if (file_exists($possiblePath)) {
                $courseData['course_img'] = api_get_path(WEB_COURSE_PATH);
                $courseData['course_img'] .= $course->getDirectory();
                $courseData['course_img'] .= '/course-pic.png';
            }

            $courses[] = $courseData;
        }

        return $courses;
    }

}
