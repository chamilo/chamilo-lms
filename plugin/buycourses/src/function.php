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
$tableBuyCourseCountry = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
$tableBuyCoursePaypal = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);
$tableBuyCourseTransfer = Database::get_main_table(TABLE_BUY_COURSE_TRANSFER);
$tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
$tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);

$plugin = BuyCoursesPlugin::create();
$buy_name = $plugin->get_lang('Buy');

if ($_REQUEST['tab'] == 'sync') {
    $sql = "SELECT code, title FROM $tableCourse;";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux_code .= $row['code'];
        $aux_title .= $row['title'];
    }
    $sql = "SELECT name, date_start, date_end FROM $tableSession;";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux_name .= $row['name'];
        $aux_date_start .= $row['date_start'];
        $aux_date_end .= $row['date_end'];
    }
    echo json_encode(array("status" => "true", "content" => $content));
}

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
        $sqlSessionCourse = "SELECT DISTINCT a.id_session, a.course_code, a.nbr_users
        FROM $tableBuySessionRelCourse a, $tableSessionRelCourse b
        WHERE a.id_session = b.id_session AND a.id_session = " . $rowSession['session_id'] . ";";
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
                WHERE a.course_code='" . $row['code'] . "'
                AND a.role<>'' AND a.role<>'NULL'
                AND a.user_id=b.user_id;";
                $tmp = Database::query($sql);
                $rowTmp = Database::fetch_assoc($tmp);
                $row['teacher'] = $rowTmp['firstname'] . ' ' . $rowTmp['lastname'];
                //check images
                if (file_exists(api_get_path(SYS_COURSE_PATH) . $row['code'] . "/course-pic85x85.png")) {
                    $row['course_img'] = "courses/" . $row['code'] . "/course-pic85x85.png";
                } else {
                    $row['course_img'] = "main/img/without_picture.png";
                }
                $row['price'] = number_format($row['price'], 2, '.', ' ');
                $aux[] = $row;
            }
        }
        //check if the user is enrolled in the current session
        if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
            $sql = "SELECT 1 FROM $tableSessionRelUser
                WHERE id_session='".$rowSession['session_id']."' AND
                id_user ='" . $_SESSION['_user']['user_id'] . "';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $rowSession['enrolled'] = "YES";
            } else {
                $sql = "SELECT 1 FROM $tableBuySessionTemporal
                    WHERE session_id ='".$rowSession['session_id']."' AND
                    user_id='" . $_SESSION['_user']['user_id'] . "';";
                Database::query($sql);
                if (Database::affected_rows() > 0) {
                    $rowSession['enrolled'] = "TMP";
                } else {
                    $rowSession['enrolled'] = "NO";
                }
            }
        } else {
            $sql = "SELECT 1 FROM $tableBuySessionTemporal
                WHERE session_id ='".$rowSession['session_id']."' AND
                user_id='" . $_SESSION['_user']['user_id'] . "';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
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
                        <h5>'.get_lang('From').' '.$session['date_start'].
                        ' '.get_lang('Until').' '.$session['date_end'].'</h5>';
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
            WHERE a.course_code = '" . $row['code'] . "'
            AND a.role <> ''
            AND a.role IS NOT NULL
            AND a.user_id = b.user_id;";

        $tmp = Database::query($sql);
        $rowTmp = Database::fetch_assoc($tmp);
        $row['teacher'] = $rowTmp['firstname'] . ' ' . $rowTmp['lastname'];
        //Check if the student is enrolled
        if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
            $sql = "SELECT 1 FROM $tableCourseRelUser
                WHERE course_code = '" . $row['code'] . "'
                AND user_id = " . intval($_SESSION['_user']['user_id']) . ";";

            $tmp = Database::query($sql);
            if (Database::affected_rows() > 0) {
                $row['enrolled'] = "YES";
            } else {
                $row['enrolled'] = "NO";
            }
        } else {
            $row['enrolled'] = "NO";
        }
        // Check img
        if (file_exists("../../../courses/" . $row['code'] . "/course-pic85x85.png")) {
            $row['course_img'] = "courses/" . $row['code'] . "/course-pic85x85.png";
        } else {
            $row['course_img'] = "main/img/without_picture.png";
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

if ($_REQUEST['tab'] == 'save_currency') {
    $id = Database::escape_string($_REQUEST['currency']);
    $sql = "UPDATE $tableBuyCourseCountry SET status='0';";
    $res = Database::query($sql);
    $sql = "UPDATE $tableBuyCourseCountry SET status='1' WHERE country_id='" . $id . "';";
    $res = Database::query($sql);
    if (!res) {
        $content = $plugin->get_lang('ProblemToSaveTheCurrencyType') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = get_lang('Saved');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'save_paypal') {
    $username = Database::escape_string($_REQUEST['username']);
    $password = Database::escape_string($_REQUEST['password']);
    $signature = Database::escape_string($_REQUEST['signature']);
    $sandbox = Database::escape_string($_REQUEST['sandbox']);
    $sql = "UPDATE $tableBuyCoursePaypal
        SET sandbox = '" . $sandbox . "',
        username = '" . $username . "',
        password = '" . $password . "',
        signature = '" . $signature . "'
        WHERE id = '1';";

    $res = Database::query($sql);
    if (!res) {
        $content = $plugin->get_lang('ProblemToSaveThePaypalParameters') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = get_lang('Saved');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'add_account') {
    $name = Database::escape_string($_REQUEST['name']);
    $account = Database::escape_string($_REQUEST['account']);
    $swift = Database::escape_string($_REQUEST['swift']);
    $sql = "INSERT INTO $tableBuyCourseTransfer (name, account, swift)
        VALUES ('" . $name . "','" . $account . "', '" . $swift . "');";

    $res = Database::query($sql);
    if (!res) {
        $content = $plugin->get_lang('ProblemToInsertANewAccount') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = get_lang('Saved');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'delete_account') {
    $id = intval($_REQUEST['id']);

    $sql = "DELETE FROM $tableBuyCourseTransfer WHERE id='" . $id . "';";
    $res = Database::query($sql);
    if (!res) {
        $content = $plugin->get_lang('ProblemToDeleteTheAccount') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = get_lang('Saved');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'save_mod') {

    $id;
    $tableBuy;
    $tableField;

    if (isset($_REQUEST['course_id'])) {
        $id = intval($_REQUEST['course_id']);
        $tableBuy = $tableBuyCourse;
        $tableField = 'course_id';
    } else {
        $id = intval($_REQUEST['session_id']);
        $tableBuy = $tableBuySession;
        $tableField = 'session_id';
    }

    $visible = (isset($_REQUEST['visible'])) ? 1 : 0;
    $price = Database::escape_string($_REQUEST['price']);

    $sql = "UPDATE $tableBuy
        SET visible = " . $visible . ",
        price = '" . $price . "'
        WHERE " . $tableField . " = '" . $id . "';";

    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToSaveTheMessage') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        echo json_encode(array("status" => "true", "course_id" => $id));
    }
}

if ($_REQUEST['tab'] == 'unset_variables') {
    unset($_SESSION['bc_user_id']);
    unset($_SESSION['bc_registered']);
    unset($_SESSION['bc_code']);
    unset($_SESSION['bc_title']);
    unset($_SESSION["Payment_Amount"]);
    unset($_SESSION["currencyCodeType"]);
    unset($_SESSION["PaymentType"]);
    unset($_SESSION["nvpReqArray"]);
    unset($_SESSION['TOKEN']);
    $_SESSION['bc_success'] = false;
    $_SESSION['bc_message'] = 'CancelOrder';
    unset($_SESSION['bc_url']);
}

if ($_REQUEST['tab'] == 'clear_order') {
    $id = substr(intval($_REQUEST['id']), 6);
    $sql = "DELETE FROM $tableBuyCourseTemporal WHERE cod='" . $id . "';";

    $res = Database::query($sql);
    if (!res) {
        $content = $plugin->get_lang('ProblemToDeleteTheAccount') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = get_lang('Saved');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'confirm_order') {
    $id = substr(intval($_REQUEST['id']), 6);
    $sql = "SELECT * FROM $tableBuyCourseTemporal WHERE cod='" . $id . "';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    $isAllowed = false;
    $user_id = $row['user_id'];
    $course_code = $row['course_code'];
    $all_course_information = CourseManager::get_course_information($course_code);

    if (CourseManager::subscribe_user($user_id, $course_code)) {
        $isAllowed = true;
    } else {
        $isAllowed = false;
    }
    //Activate user account
    if ($isAllowed) {
        // 1. set account inactive
        $sql = "UPDATE $tableUser SET active = '1' WHERE user_id = " . intval($_SESSION['bc_user_id']) . "";
        Database::query($sql);

        $sql = "DELETE FROM $tableBuyCourseTemporal WHERE cod='" . $id . "';";
        $res = Database::query($sql);

        $content = $plugin->get_lang('TheSubscriptionAndActivationWereDoneSuccessfully');
        echo json_encode(array("status" => "true", "content" => $content));
    } else {
        $content = $plugin->get_lang('ProblemToSubscribeTheUser');
        echo json_encode(array("status" => "false", "content" => $content));
    }
}
