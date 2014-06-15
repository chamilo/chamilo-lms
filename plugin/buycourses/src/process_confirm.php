<?php
/* For license terms, see /license.txt */
/**
 * Process purchase confirmation script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';
require_once '../../../main/inc/lib/mail.lib.inc.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';

if ($_POST['payment_type'] == '') {
    header('Location:process.php');
}

$tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
$tableBuyCoursePaypal = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);

if (isset($_POST['Confirm'])) {
    // Save the user, course and reference in a tmp table
    $user_id = $_SESSION['bc_user_id'];
    $course_code = $_SESSION['bc_course_codetext'];
    $reference = calculateReference();

    reset($_POST);
    while (list ($param, $val) = each($_POST)) {
        $asignacion = "\$" . $param . "=mysql_real_escape_string(\$_POST['" . $param . "']);";
        eval($asignacion);
    }

    $sql = "INSERT INTO $tableBuyCourseTemporal (user_id, name, course_code, title, reference, price)
        VALUES ('" . $user_id . "', '" . $name . "','" . $course_code . "','" . $title . "','" . $reference . "','" . $price . "');";
    $res = Database::query($sql);

    // Notify the user and send the bank info

    $accountsList = listAccounts();
    $text = '<div align="center"><table style="width:70%"><tr><th style="text-align:center"><h3>Datos Bancarios</h3></th></tr>';
    foreach ($accountsList as $account) {
        $text .= '<tr>';
        $text .= '<td>';
        $text .= '<font color="#0000FF"><strong>' . htmlspecialchars($account['name']) . '</strong></font><br />';
        if ($account['swift'] != '') {
            $text .= 'SWIFT: <strong>' . htmlspecialchars($account['swift']) . '</strong><br />';
        }
        $text .= 'Cuenta Bancaria: <strong>' . htmlspecialchars($account['account']) . '</strong><br />';
        $text .= '</td></tr>';
    }
    $text .= '</table></div>';

    $plugin = BuyCoursesPlugin::create();
    $asunto = utf8_encode($plugin->get_lang('bc_subject'));


    if (!isset($_SESSION['_user'])) {
        $name = $_SESSION['bc_user']['firstName'] . ' ' . $_SESSION['bc_user']['lastName'];
        $email = $_SESSION['bc_user']['mail'];
    } else {
        $name = $_SESSION['bc_user']['firstname'] . ' ' . $_SESSION['bc_user']['lastname'];
        $email = $_SESSION['bc_user']['email'];
    }

    $courseInfo = courseInfo($_SESSION['bc_course_code']);
    $title_course = $courseInfo['title'];

    $message = $plugin->get_lang('bc_message');
    $message = str_replace("{{name}}", $name, $message);
    $message = str_replace("{{course}}", $title_course, $message);
    $message = str_replace("{{reference}}", $reference, $message);
    $message .= $text;

    api_mail($name, $email, $asunto, $message);
    // Return to course list
    header('Location:list.php');
}


$currencyType = $_POST['currency_type'];
$_SESSION['bc_currency_type'] = $currencyType;
$server = $_POST['server'];

if ($_POST['payment_type'] == "PayPal") {
    $sql = "SELECT * FROM $tableBuyCoursePaypal WHERE id='1';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    $pruebas = ($row['sandbox'] == "YES") ? true: false;
    $paypalUsername = $row['username'];
    $paypalPassword = $row['password'];
    $paypalSignature = $row['signature'];
    require_once("paypalfunctions.php");
    // PayPal Express Checkout Module
    $paymentAmount = $_SESSION["Payment_Amount"];
    $currencyCodeType = $currencyType;
    $paymentType = "Sale";
    $returnURL = $server . "plugin/buycourses/src/success.php";
    $cancelURL = $server . "plugin/buycourses/src/error.php";

    $courseInfo = courseInfo($_SESSION['bc_course_code']);
    $courseTitle = $courseInfo['title'];
    $i = 0;
    $extra = "&L_PAYMENTREQUEST_0_NAME" . $i . "=" . $courseTitle;
    $extra .= "&L_PAYMENTREQUEST_0_AMT" . $i . "=" . $paymentAmount;
    $extra .= "&L_PAYMENTREQUEST_0_QTY" . $i . "=1";

    $resArray = CallShortcutExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $extra);
    $ack = strtoupper($resArray["ACK"]);

    if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
        RedirectToPayPal($resArray["TOKEN"]);
    } else {
        $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

        echo "<br />SetExpressCheckout API call failed. ";
        echo "<br />Detailed Error Message: " . $ErrorLongMsg;
        echo "<br />Short Error Message: " . $ErrorShortMsg;
        echo "<br />Error Code: " . $ErrorCode;
        echo "<br />Error Severity Code: " . $ErrorSeverityCode;
    }
}

if ($_POST['payment_type'] == "Transfer") {
    $_cid = 0;
    $templateName = $plugin->get_lang('PaymentMethods');
    $interbreadcrumb[] = array("url" => "list.php", "name" => $plugin->get_lang('CourseListOnSale'));

    $tpl = new Template($templateName);

    $code = $_SESSION['bc_course_code'];
    $courseInfo = courseInfo($code);

    $tpl->assign('course', $courseInfo);
    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('title', $_SESSION['bc_course_title']);
    $tpl->assign('price', $_SESSION['Payment_Amount']);
    $tpl->assign('currency', $_SESSION['bc_currency_type']);
    if (!isset($_SESSION['_user'])) {
        $tpl->assign('name', $_SESSION['bc_user']['firstName'] . ' ' . $_SESSION['bc_user']['lastName']);
        $tpl->assign('email', $_SESSION['bc_user']['mail']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    } else {
        $tpl->assign('name', $_SESSION['bc_user']['firstname'] . ' ' . $_SESSION['bc_user']['lastname']);
        $tpl->assign('email', $_SESSION['bc_user']['email']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    }

    //Get bank list account
    $accountsList = listAccounts();
    $tpl->assign('accounts', $accountsList);

    $listing_tpl = 'buycourses/view/process_confirm.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
