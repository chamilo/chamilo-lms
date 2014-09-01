<?php
/* For license terms, see /license.txt */
/**
 * Process payments for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
require_once '../config.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';

$plugin = BuyCoursesPlugin::create();
$_cid = 0;
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = array("url" => "list.php", "name" => $plugin->get_lang('CourseListOnSale'));

$tpl = new Template($templateName);

if (!empty($_GET['code'])) {
    $code = (int)$_GET['code'];
} else {
    $code = $_SESSION['bc_course_code'];
}

$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);

$sql = "SELECT a.price, a.title, b.code
    FROM $tableBuyCourse a, $tableCourse b
    WHERE a.course_id = " . $code . "
    AND a.course_id = b.id;";
$res = Database::query($sql);
$row = Database::fetch_assoc($res);

$_SESSION['Payment_Amount'] = number_format($row['price'], 2, '.', '');
$_SESSION['bc_course_code'] = $code;
$_SESSION['bc_course_title'] = $row['title'];
$_SESSION['bc_course_codetext'] = $row['code'];

if (!isset($_SESSION['_user'])) {
    //Needs to be Registered
    if (!isset($_SESSION['bc_user'])) {
        header('Location:inscription.php');
        exit;
    } else {
        $_SESSION['bc_user_id'] = $_SESSION['bc_user']['user_id'];
        $tpl->assign('name', $_SESSION['bc_user']['firstName'] . ' ' . $_SESSION['bc_user']['lastName']);
        $tpl->assign('email', $_SESSION['bc_user']['mail']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    }
} else {
    $_SESSION['bc_user_id'] = $_SESSION['_user']['user_id'];
    $_SESSION['bc_user'] = $_SESSION['_user'];
    $tpl->assign('name', $_SESSION['bc_user']['firstname'] . ' ' . $_SESSION['bc_user']['lastname']);
    $tpl->assign('email', $_SESSION['bc_user']['email']);
    $tpl->assign('user', $_SESSION['bc_user']['username']);
}

if (checkUserCourse($_SESSION['bc_course_codetext'], $_SESSION['bc_user_id'])) {
    $_SESSION['bc_success'] = false;
    $_SESSION['bc_message'] = 'AlreadyBuy';
    header('Location: list.php');
}

if (checkUserCourseTransfer($_SESSION['bc_course_codetext'], $_SESSION['bc_user_id'])) {
    $_SESSION['bc_success'] = false;
    $_SESSION['bc_message'] = 'bc_tmp_registered';
    header('Location: list.php');
}

$currencyType = findCurrency();

$paypalEnable = $plugin->get('paypal_enable');
$transferEnable = $plugin->get('transfer_enable');

$courseInfo = courseInfo($code);

$tpl->assign('course', $courseInfo);
$tpl->assign('server', $_configuration['root_web']);
$tpl->assign('paypal_enable', $paypalEnable);
$tpl->assign('transfer_enable', $transferEnable);
$tpl->assign('title', $_SESSION['bc_course_title']);
$tpl->assign('price', $_SESSION['Payment_Amount']);
$tpl->assign('currency', $currencyType);

$listing_tpl = 'buycourses/view/process.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
