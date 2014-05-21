<?php
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'lib/buy_course_plugin.class.php';

$_cid = 0;
$interbreadcrumb[] = array("url" => "list.php", "name" => 'Listado de cursos a la venta');

$tpl = new Template('Tipo de pago');

if (isset($_GET['code'])) {
    $code = (int)$_GET['code'];
} else {
    $code = $_SESSION['bc_curso_code'];
}
$sql = "SELECT price, title, code FROM plugin_buycourses a, course b WHERE a.id_course='" . $code . "' AND a.id_course=b.id;";
$res = Database::query($sql);
$row = Database::fetch_assoc($res);
$_SESSION['Payment_Amount'] = number_format($row['price'], 2, '.', '');
$_SESSION['bc_curso_code'] = $code;
$_SESSION['bc_curso_title'] = $row['title'];
$_SESSION['bc_curso_codetext'] = $row['code'];

if (!isset($_SESSION['_user'])) {
    //Necesita registro
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

if (comprueba_curso_user($_SESSION['bc_curso_codetext'], $_SESSION['bc_user_id'])) {
    $_SESSION['bc_exito'] = false;
    $_SESSION['bc_mensaje'] = 'AlreadyBuy';
    header('Location: list.php');
}

if (comprueba_curso_user_transf($_SESSION['bc_curso_codetext'], $_SESSION['bc_user_id'])) {
    $_SESSION['bc_exito'] = false;
    $_SESSION['bc_mensaje'] = 'bc_tmp_registrado';
    header('Location: list.php');
}
//echo var_dump($_SESSION);
//exit;
$tipo_moneda = busca_moneda();

$plugin = Buy_CoursesPlugin::create();
$paypal_enable = $plugin->get('paypal_enable');
$tarjeta_enable = $plugin->get('tarjet_credit_enable');
$transference_enable = $plugin->get('transference_enable');

$infocurso = info_curso($code);

$tpl->assign('curso', $infocurso);
$tpl->assign('server', $_configuration['root_web']);
$tpl->assign('paypal_enable', $paypal_enable);
$tpl->assign('tarjeta_enable', $tarjeta_enable);
$tpl->assign('transference_enable', $transference_enable);
$tpl->assign('title', $_SESSION['bc_curso_title']);
$tpl->assign('price', $_SESSION['Payment_Amount']);
$tpl->assign('moneda', $tipo_moneda);


$listing_tpl = 'buy_courses/view/process.tpl';
$content = $tpl->fetch($listing_tpl);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
