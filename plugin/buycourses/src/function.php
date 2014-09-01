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
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'course.lib.php';

$tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
$tableBuyCourseCountry = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
$tableBuyCoursePaypal = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);
$tableBuyCourseTransfer = Database::get_main_table(TABLE_BUY_COURSE_TRANSFER);
$tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
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
    echo json_encode(array("status" => "true", "content" => $content));
}

if ($_REQUEST['tab'] == 'courses_filter') {
    $course = Database::escape_string($_REQUEST['course']);
    $priceMin = Database::escape_string($_REQUEST['pricemin']);
    $priceMax = Database::escape_string($_REQUEST['pricemax']);
    $show = Database::escape_string($_REQUEST['show']);
    $category = Database::escape_string($_REQUEST['category']);
    $server = api_get_path(WEB_PATH);

    $filter = '';
    if ($course != '') {
        $filter .= "b.title LIKE '%" . $course . "%'";
    }
    if ($priceMin != '') {
        if ($filter == '') {
            $filter .= "a.price >= '" . $priceMin . "'";
        } else {
            $filter .= " AND a.price >= '" . $priceMin . "'";
        }
    }

    if ($priceMax != '') {
        if ($filter == '') {
            $filter .= "a.price <= '" . $priceMax . "'";
        } else {
            $filter .= " AND a.price <= '" . $priceMax . "'";
        }
    }

    if ($category != '') {
        if ($filter == '') {
            $filter .= "b.category_code='" . $category . "'";
        } else {
            $filter .= " AND b.category_code='" . $category . "'";
        }
    }

    if ($filter == '') {
        $sql = "SELECT a.course_id, a.visible, a.price, b.*
            FROM $tableBuyCourse a, $tableCourse b
            WHERE a.course_id = b.id
            AND a.visible = 1;";
    } else {
        $sql = "SELECT a.course_id, a.visible, a.price, b.*
            FROM $tableBuyCourse a, $tableCourse b
            WHERE a.course_id = b.id
            AND a.visible = 1 AND " . $filter . ";";
    }

    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        //Check teacher
        $sql = "SELECT lastname, firstname
            FROM $tableCourseRelUser a, $tableUser b
            WHERE a.course_code = '" . $row['code'] . "'
            AND a.role <> ''
            AND a.role <> 'NULL'
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

        if ($show == "YES" && $row['enrolled'] == "YES") {
            ;
        } else {
            $aux[] = $row;
        }

    }
    $currencyType = findCurrency();
    foreach ($aux as $course) {
        $content .= '<div class="well_border span8">';
        $content .= '<div class="row">';
        $content .= '<div class="span">';
        $content .= '<div class="thumbnail">';
        $content .= '<a class="ajax" rel="gb_page_center[778]" title="" href="' . $server . 'main/inc/ajax/course_home.ajax.php?a=show_course_information&code=' . $course['code'] . '">';
        $content .= '<img alt="" src="' . $server . $course['course_img'] . '">';
        $content .= '</a>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '<div class="span4">';
        $content .= '<div class="categories-course-description">';
        $content .= '<h3>' . $course['title'] . '</h3>';
        $content .= '<h5>' . get_lang('Teacher') . ': ' . $course['teacher'] . '</h5>';
        $content .= '</div>';
        if ($course['enrolled'] == "YES") {
            $content .= '<span class="label label-info">' .  $plugin->get_lang('TheUserIsAlreadyRegistered') . '</span>';
        }
        $content .= '</div>';
        $content .= '<div class="span right">';
        $content .= '<div class="sprice right">' . $course['price'] . ' ' . $currencyType . ' </div>';
        $content .= '<div class="cleared"></div>';
        $content .= '<div class="btn-toolbar right">';
        $content .= '<a class="ajax btn btn-primary" title="" href="' . $server . 'main/inc/ajax/course_home.ajax.php?a=show_course_information&code=' . $course['code'] . '">' . get_lang('Description') . '</a>&nbsp;';
        if ($course['enrolled'] != "YES") {
            $content .= '<a class="btn btn-success" title="" href="' . $server . 'plugin/buycourses/src/process.php?code=' . $course['id'] . '">' . $buy_name . '</a>';
        }
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';
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
    $_REQUEST['id'] = Database::escape_string($_REQUEST['id']);
    $idCourse = intval($_REQUEST['course_id']);
    $visible = ($_REQUEST['visible'] == "checked") ? 1 : 0;
    $price = Database::escape_string($_REQUEST['price']);

    $sql = "UPDATE $tableBuyCourse
        SET visible = " . $visible . ",
        price = '" . $price . "'
        WHERE course_id = '" . $idCourse . "';";

    $res = Database::query($sql);
    if (!res) {
        $content = $plugin->get_lang('ProblemToSaveTheMessage') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        echo json_encode(array("status" => "true", "course_id" => $idCourse));
    }
}

if ($_REQUEST['tab'] == 'unset_variables') {
    unset($_SESSION['bc_user_id']);
    unset($_SESSION['bc_registered']);
    unset($_SESSION['bc_course_code']);
    unset($_SESSION['bc_course_title']);
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
