<?php
/* For license terms, see /license.txt */
/**
 * Functions for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';
require_once 'buy_course.lib.php';

$tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
$tableBuySessionTemporal = Database::get_main_table(TABLE_BUY_SESSION_TEMPORARY);
$tableBuySessionRelCourse = Database::get_main_table(TABLE_BUY_SESSION_COURSE);
$tableSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
$tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);

$itemTable = Database::get_main_table(BuyCoursesUtils::TABLE_ITEM);

$plugin = BuyCoursesPlugin::create();
$buy_name = $plugin->get_lang('Buy');
$currency = $plugin->getSelectedCurrency();

if ($_REQUEST['tab'] == 'sessions_filter') {
    $session = isset($_REQUEST['name']) ? Database::escape_string($_REQUEST['name']) : '';
    $priceMin = isset($_REQUEST['pricemin']) ? floatval($_REQUEST['pricemin']) : 0;
    $priceMax = isset($_REQUEST['pricemax']) ? floatval($_REQUEST['pricemax']) : 0;
    //$category = isset($_REQUEST['category']) ? Database::escape_string($_REQUEST['category']) : '';
    $server = api_get_path(WEB_PATH);

    $sql = "SELECT a.session_id, a.visible, a.price, b.*
            FROM $tableBuySession a, $tableSession b
            WHERE a.session_id = b.id AND a.visible = 1;";

    $filter = "";
    if (!empty($session)) {
        $filter .= " AND b.name LIKE '%".$session."%'";
    }

    if ($priceMin > 0) {
        $filter .= " AND a.price >= ".$priceMin;
    }

    if ($priceMax > 0) {
        $filter .= " AND a.price <= ".$priceMax;
    }

    /*if (!empty($category)) {
        $filter .= " AND b.category_code = '".$category."'";
    }*/

    if (!empty($filter)) {
        $sql = substr_replace($sql, $filter.";", -1);
    }

    $resSessions = Database::query($sql);
    $auxSessions = array();

    // loop through all sessions
    while ($rowSession = Database::fetch_assoc($resSessions)) {
        // get courses of current session
        $sqlSessionCourse = "SELECT DISTINCT a.session_id, a.course_code, a.nbr_users
        FROM $tableBuySessionRelCourse a, $tableSessionRelCourse b
        WHERE a.session_id = b.session_id AND a.session_id = " . $rowSession['session_id'] . ";";
        $resSessionCourse = Database::query($sqlSessionCourse);
        $aux = array();
        // loop through courses of current session
        while ($rowSessionCourse = Database::fetch_assoc($resSessionCourse)) {
            // get course of current session
            $sql = "SELECT a.course_id, a.session_id, a.visible, a.price, b.*
            FROM $tableBuyCourse a, $tableCourse b
            WHERE a.code = b.code AND a.code = '" . $rowSessionCourse['course_code'] . "';";
            $res = Database::query($sql);
            // loop inside a course of current session
            while ($row = Database::fetch_assoc($res)) {
                //check teacher
                $sql = "SELECT lastname, firstname
                FROM course_rel_user a, user b
                WHERE a.c_id=" . $row['id'] . "
                AND a.status <> 6
                AND a.user_id=b.id;";
                $tmp = Database::query($sql);
                $rowTmp = Database::fetch_assoc($tmp);
                $row['teacher'] = $rowTmp['firstname'] . ' ' . $rowTmp['lastname'];
                //check images
                if (file_exists(api_get_path(SYS_COURSE_PATH) . $row['code'] . "/course-pic.png")) {
                    $row['course_img'] = "courses/" . $row['code'] . "/course-pic.png";
                } else {
                    $row['course_img'] = "main/img/session_default.png";
                }
                $row['price'] = number_format($row['price'], 2, '.', ' ');
                $aux[] = $row;
            }
        }
        //check if the user is enrolled in the current session
        if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
            $sql = "SELECT 1 FROM $tableSessionRelUser
                WHERE session_id ='".$rowSession['session_id']."' AND
                user_id ='" . api_get_user_id() . "'";
            $result = Database::query($sql);
            if (Database::affected_rows($result) > 0) {
                $rowSession['enrolled'] = "YES";
            } else {
                $sql = "SELECT 1 FROM $tableBuySessionTemporal
                    WHERE session_id ='".$rowSession['session_id']."' AND
                    user_id='" . api_get_user_id() . "'";
                $result = Database::query($sql);
                if (Database::affected_rows($result) > 0) {
                    $rowSession['enrolled'] = "TMP";
                } else {
                    $rowSession['enrolled'] = "NO";
                }
            }
        } else {
            $sql = "SELECT 1 FROM $tableBuySessionTemporal
                WHERE session_id ='".$rowSession['session_id']."' AND
                user_id='" . api_get_user_id() . "'";
            $result = Database::query($sql);
            if (Database::affected_rows($result) > 0) {
                $rowSession['enrolled'] = "TMP";
            } else {
                $rowSession['enrolled'] = "NO";
            }
        }
        // add courses to current session
        $rowSession['courses'] = $aux;
        // add the current whole session
        $auxSessions[] = $rowSession;
    }

    $currencyType = findCurrency();
    $content = '';
    foreach ($auxSessions as $session) {
        $content .= '<div class="span8 well-course">
            <div class="row">
                <div class="span4 ">
                    <div class="categories-course-description">
                        <h3>'.$session['name'].'</h3>
                        <h5>'.get_lang('From').' '.$session['access_start_date'].
                        ' '.get_lang('Until').' '.$session['access_end_date'].'</h5>';
        if ($session['enrolled'] == "YES") {
            $content .= '<span class="label label-info">'.$plugin->get_lang('TheUserIsAlreadyRegisteredInTheSession').'</span>';
        }
        if ($session['enrolled'] == "TMP") {
            $content .= '<span class="label label-warning">'.$plugin->get_lang('WaitingToReceiveThePayment').'</span>';
        }
        $content .= '</div>
                </div>
            <div class="span right">
                <div class="sprice right">'.
                    $session['price'].' '.$currencyType.'
                </div>
                <div class="cleared">
                </div>
                <div class="btn-group right">';
        if ($session['enrolled'] == "NO") {
            $content .= '<a class="btn btn-success" title="" href="'.$server.
                        'plugin/buycourses/src/process.php?scode='.$session['session_id'].'">'.
                            $buy_name.
                        '</a>';
        }
        $content .= '</div>
            </div>
        </div>';
        $courses = $session['courses'];
        foreach ($courses as $course) {
            $content .= '<div class="row">
                <div class="span">
                    <div class="thumbnail">
                        <a class="ajax" rel="gb_page_center[778]" title=""
                        href="'.$server.'plugin/buycourses/src/ajax.php?
                        a=show_course_information&code='.$course['code'].'">
                            <img alt="" src="' . $server . $course['course_img'] . '">
                        </a>
                    </div>
                </div>
                <div class="span4">
                    <div class="categories-course-description">
                        <h3>'.$course['title'].'</h3>
                        <h5>'.get_lang('Teacher').': '.$course['teacher'].'</h5>
                    </div>
                </div>
                <div class="span right">
                    <div class="cleared">
                    </div>
                    <div class="btn-group right">
                        <a class="ajax btn btn-primary" title=""
                        href="'.$server.'plugin/buycourses/src/ajax.php?
                        a=show_course_information&code='.$course['code'].'">'.get_lang('Description').'</a>
                    </div>
                </div>
            </div>';
        }
        $content .= '</div>';
    }

    echo json_encode(array("status" => "true", "content" => $content));
}

if ($_REQUEST['tab'] == 'courses_filter') {
    $course = isset($_REQUEST['name']) ? Database::escape_string($_REQUEST['name']) : '';
    $priceMin = isset($_REQUEST['pricemin']) ? floatval($_REQUEST['pricemin']) : 0;
    $priceMax = isset($_REQUEST['pricemax']) ? floatval($_REQUEST['pricemax']) : 0;
    /**
     * Deprecated since 2014-10-14
     */
    //$show = Database::escape_string($_REQUEST['show']);
    //$category = Database::escape_string($_REQUEST['category']);
    $server = api_get_path(WEB_PATH);

    $sql = "SELECT a.course_id, a.visible, a.price, b.*
            FROM $tableBuyCourse a, $tableCourse b
            WHERE a.course_id = b.id AND a.session_id = 0
            AND a.visible = 1;";

    $filter = "";
    if (!empty($course)) {
        $filter .= " AND b.title LIKE '%".$course."%'";
    }

    if ($priceMin > 0) {
        $filter .= " AND a.price >= ".$priceMin;
    }

    if ($priceMax > 0) {
        $filter .= " AND a.price <= ".$priceMax;
    }

    /*if (!empty($category)) {
        $filter .= " AND b.category_code = '".$category."'";
    }*/

    if (!empty($filter)) {
        $sql = substr_replace($sql, $filter.";", -1);
    }

    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        //Check teacher
        $sql = "SELECT lastname, firstname
            FROM $tableCourseRelUser a, $tableUser b
            WHERE a.c_id = " . $row['id'] . "
            AND a.status <> 6
            AND a.user_id = b.id;";

        $tmp = Database::query($sql);
        $rowTmp = Database::fetch_assoc($tmp);
        $row['teacher'] = $rowTmp['firstname'] . ' ' . $rowTmp['lastname'];
        //Check if the student is enrolled
        if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
            $sql = "SELECT 1 FROM $tableCourseRelUser
                WHERE c_id = " . $row['id'] . "
                AND user_id = " . intval($_SESSION['_user']['user_id']) . ";";

            $tmp = Database::query($sql);
            if (Database::affected_rows($tmp) > 0) {
                $row['enrolled'] = "YES";
            } else {
                $row['enrolled'] = "NO";
            }
        } else {
            $row['enrolled'] = "NO";
        }
        // Check img
        if (file_exists(api_get_path(SYS_COURSE_PATH) . $row['directory'] . "/course-pic.png")) {
            $row['course_img'] = "courses/" . $row['directory'] . "/course-pic.png";
        } else {
            $row['course_img'] = "main/img/session_default.png";
        }

        $aux[] = $row;
        /**
         * Deprecated since 2014-10-14
         */
        /*if ($show == "YES" && $row['enrolled'] == "YES") {
            ;
        } else {
          $aux[] = $row;
        }*/

    }
    $currencyType = findCurrency();
    $content = '';
    foreach ($aux as $course) {
        $content .= '
            <div class="span8">
                <div class="row well-course">
                    <div class="span1 icon-course">
                        <div class="thumbnail">
                            <a class="ajax" rel="gb_page_center[778]" title=""
                            href="'.$server.'plugin/buycourses/src/ajax.php?
                            a=show_course_information&code='.$course['code'].'">
                                <img alt="" src="'.$server.$course['course_img'].'">
                            </a>
                        </div>
                    </div>
                    <div class="span3">
                        <div class="categories-course-description">
                            <h3>'.$course['title'].'</h3>
                            <h5>'.get_lang('Teacher').': '.$course['teacher'].'</h5>
                        </div>';
        if ($course['enrolled'] == "YES") {
            $content .= '<span class="label label-info">'.$plugin->get_lang('TheUserIsAlreadyRegisteredInTheCourse').'</span>';
        }
        if ($course['enrolled'] == "TMP") {
            $content .= '<span class="label label-warning">'.$plugin->get_lang('WaitingToReceiveThePayment').'</span>';
        }
        $content .= '</div>
                    <div class="span3 right">
                        <div class="sprice right">'.
                            $course['price'].' '.$currencyType.'
                        </div>
                        <div class="cleared">
                        </div>
                        <div class="btn-group right">
                            <a class="ajax btn btn-primary" title=""
                            href="'.$server.'plugin/buycourses/src/ajax.php?
                            a=show_course_information&code='.$course['code'].'">'.
                                get_lang('Description').
                            '</a>';
        if ($course['enrolled'] != "YES") {
            $content .= '<a class="btn btn-success" title=""
                            href="'.$server.'plugin/buycourses/src/process.php?code='.$course['id'].'">'.
                                $buy_name.
                            '</a>';
        }
        $content .= '</div>
                    </div>
                </div>
            </div>';
    }
    echo json_encode(array("status" => "true", "content" => $content));
}

if ($_REQUEST['tab'] == 'save_mod') {
    if (isset($_REQUEST['course_id'])) {
        $productId = $_REQUEST['course_id'];
        $productType = BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
    } else {
        $productId = $_REQUEST['session_id'];
        $productType = BuyCoursesPlugin::PRODUCT_TYPE_SESSION;
    }

    $affectedRows = false;

    if ($_POST['visible'] == 1) {
        $item = Database::select(
            'COUNT(1) AS qty',
            $itemTable,
            [
                'where' => [
                    'product_id = ? AND ' => intval($productId),
                    'product_type = ?' => $productType
                ]
            ],
            'first'
        );

        if ($item['qty'] > 0) {
            $affectedRows = Database::update(
                $itemTable,
                ['price' => floatval($_POST['price'])],
                [
                    'product_id = ? AND ' => intval($productId),
                    'product_type' => $productType
                ]
            );
        } else {
            $affectedRows = Database::insert(
                $itemTable,
                [
                    'currency_id' => $currency['id'],
                    'product_type' => $productType,
                    'product_id' => intval($productId),
                    'price' => floatval($_POST['price'])
                ]
            );
        }
    } else {
        $affectedRows = Database::delete(
            $itemTable,
            [
                'product_id = ? AND ' => intval($productId),
                'product_type = ?' => $productType
            ]
        );
    }

    if ($affectedRows > 0) {
        $jsonResult = [
            "status" => true,
            "itemId" => $productId
        ];
    } else {
        $jsonResult = [
            "status" => false,
            "content" => $plugin->get_lang('ProblemToSaveTheMessage')
        ];
    }

    echo json_encode($jsonResult);
    exit;
}
