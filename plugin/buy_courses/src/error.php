<?php
$course_plugin = 'buy_courses';
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once 'lib/buy_course_plugin.class.php';

unset($_SESSION['bc_user_id']);
unset($_SESSION['bc_registrado']);
unset($_SESSION['bc_curso_code']);
unset($_SESSION['bc_curso_title']);
unset($_SESSION["Payment_Amount"]);
unset($_SESSION["currencyCodeType"]);
unset($_SESSION["PaymentType"]);
unset($_SESSION["nvpReqArray"]);
unset($_SESSION['TOKEN']);
$_SESSION['bc_exito'] = false;
$_SESSION['bc_mensaje'] = 'Cancelacionpedido';
header('Location:list.php');
?>