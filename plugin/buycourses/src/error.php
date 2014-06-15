<?php
/* For license terms, see /license.txt */
/**
 * Errors management for the Buy Courses plugin - Redirects to list.php
 * @package chamilo.plugin.buycourses
 */
/**
 * Config
 */
$course_plugin = 'buycourses';
require_once 'buy_course.lib.php';
require_once 'buy_course_plugin.class.php';

unset($_SESSION['bc_user_id']);
unset($_SESSION['bc_registered']);
unset($_SESSION['bc_course_code']);
unset($_SESSION['bc_course_title']);
unset($_SESSION['Payment_Amount']);
unset($_SESSION['currencyCodeType']);
unset($_SESSION['PaymentType']);
unset($_SESSION['nvpReqArray']);
unset($_SESSION['TOKEN']);
$_SESSION['bc_success'] = false;
$_SESSION['bc_message'] = 'CancelOrder';

header('Location:list.php');
