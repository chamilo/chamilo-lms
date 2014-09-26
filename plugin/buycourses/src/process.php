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
    $codeType = "COURSE";
    $code = (int)$_GET['code'];
} else if (!empty($_GET['scode'])) {
    $codeType = "SESSION";
    $code = (int)$_GET['scode'];
} else {
    $code = $_SESSION['bc_code'];
}

$result = array_shift(
    Database::select(
        'selected_value', 
        Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT), 
        array('where'=> array('variable = ?' => array('buycourses_include_sessions')))
    )
);

if ($codeType === 'SESSION' && $result['selected_value'] === 'true') {    
    $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
    $sql = "SELECT a.session_id, a.name, a.date_start, a.date_end, a.price
    FROM $tableBuySession a, $tableSession b
    WHERE a.session_id = " . $code . "
    AND a.session_id = b.id;";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);    
    $_SESSION['bc_title'] = $row['name'];
    $_SESSION['bc_codetext'] = 'THIS_IS_A_SESSION';
    $tpl->assign('session', sessionInfo($code));
    $tpl->assign('isSession', 'YES');
} else {
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $sql = "SELECT a.price, a.title, b.code
    FROM $tableBuyCourse a, $tableCourse b
    WHERE a.course_id = " . $code . "
    AND a.course_id = b.id;";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    $_SESSION['bc_title'] = $row['title'];
    $_SESSION['bc_codetext'] = $row['code'];
    $tpl->assign('course', courseInfo($code));
}
$_SESSION['Payment_Amount'] = number_format($row['price'], 2, '.', '');
$_SESSION['bc_code'] = $code;

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

$currencyType = findCurrency();

$paypalEnable = $plugin->get('paypal_enable');
$transferEnable = $plugin->get('transfer_enable');


if (checkUserBuy($_SESSION['bc_codetext'], $_SESSION['bc_user_id'], $codeType)) {
    $_SESSION['bc_success'] = false;
    $_SESSION['bc_message'] = 'AlreadyBuy';
    header('Location: list.php');
}

if (checkUserBuyTransfer($_SESSION['bc_codetext'], $_SESSION['bc_user_id'], $codeType)) {
    $_SESSION['bc_success'] = false;
    $_SESSION['bc_message'] = 'bc_tmp_registered';
    header('Location: list.php');
}

$tpl->assign('server', $_configuration['root_web']);
$tpl->assign('paypal_enable', $paypalEnable);
$tpl->assign('transfer_enable', $transferEnable);
$tpl->assign('title', $_SESSION['bc_title']);
$tpl->assign('price', $_SESSION['Payment_Amount']);
$tpl->assign('currency', $currencyType);

$listing_tpl = 'buycourses/view/process.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
