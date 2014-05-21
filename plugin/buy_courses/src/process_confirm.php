<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require_once '../../../main/inc/global.inc.php';
require_once '../../../main/inc/lib/mail.lib.inc.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once 'lib/buy_course_plugin.class.php';

function completar($valor, $digitos)
{
    $resultado = '';
    if (strlen($valor) < $digitos) {
        $ceros = $digitos - strlen(ceil($valor));
        for ($i = 0; $i < $ceros; $i++) {
            $resultado .= '0';
        }
    }
    $resultado .= $valor;
    return $resultado;
}

if ($_POST['payment_type'] == '') {
    header('Location:process.php');
}

if (isset($_POST['Aceptar'])) {
    //Almacenamos usuario, curso, referencia en tabla temporal
    $user_id = $_SESSION['bc_user_id'];
    $course_code = $_SESSION['bc_curso_codetext'];
    $reference = calculateReference();

    reset($_POST);
    while (list ($param, $val) = each($_POST)) {
        $asignacion = "\$" . $param . "=mysql_real_escape_string(\$_POST['" . $param . "']);";
        eval($asignacion);
    }

    $sql = "INSERT INTO plugin_bc_temporal (user_id, name, course_code, title, reference, price) VALUES ('" . $user_id . "', '" . $name . "','" . $course_code . "','" . $title . "','" . $reference . "','" . $price . "');";
    $res = Database::query($sql);

    //Notificamos al usuario y enviamos datos bancarios

    $accountsList = listAccounts();
    $texto = '<div align="center"><table style="width:70%"><tr><th style="text-align:center"><h3>Datos Bancarios</h3></th></tr>';
    foreach ($accountsList as $account) {
        $texto .= '<tr>';
        $texto .= '<td>';
        $texto .= '<font color="#0000FF"><strong>' . htmlspecialchars($account['name']) . '</strong></font><br />';
        if ($account['swift'] != '') {
            $texto .= 'SWIFT: <strong>' . htmlspecialchars($account['swift']) . '</strong><br />';
        }
        $texto .= 'Cuenta Bancaria: <strong>' . htmlspecialchars($account['account']) . '</strong><br />';
        $texto .= '</td></tr>';
    }
    $texto .= '</table></div>';

    $plugin = Buy_CoursesPlugin::create();
    $asunto = utf8_encode($plugin->get_lang('bc_subject'));


    if (!isset($_SESSION['_user'])) {
        $name = $_SESSION['bc_user']['firstName'] . ' ' . $_SESSION['bc_user']['lastName'];
        $email = $_SESSION['bc_user']['mail'];
    } else {
        $name = $_SESSION['bc_user']['firstname'] . ' ' . $_SESSION['bc_user']['lastname'];
        $email = $_SESSION['bc_user']['email'];
    }

    $datos_curso = info_curso($_SESSION['bc_curso_code']);
    $title_curso = $datos_curso['title'];

    $message = utf8_encode($plugin->get_lang('bc_message'));
    $message = str_replace("{{name}}", $name, $message);
    $message = str_replace("{{curso}}", $title_curso, $message);
    $message = str_replace("{{reference}}", $reference, $message);
    $message .= $texto;

    api_mail($name, $email, $asunto, $message);
    // Volvemos al listado de cursos
    header('Location:list.php');
}


$tipomoneda = $_POST['tipomoneda'];
$_SESSION['bc_tipomoneda'] = $tipomoneda;
$server = $_POST['server'];

if ($_POST['payment_type'] == "PayPal") {
    $sql = "SELECT * FROM plugin_bc_paypal WHERE id='1';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    $pruebas = ($row['sandbox'] == "SI") ? (true) : (false);
    $paypal_username = $row['username'];
    $paypal_password = $row['password'];
    $paypal_firma = $row['signature'];
    require_once("function/paypalfunctions.php");
    // ==================================
    // PayPal Express Checkout Module
    // ==================================
    $paymentAmount = $_SESSION["Payment_Amount"];
    $currencyCodeType = $tipomoneda;
    $paymentType = "Sale";
    $returnURL = $server . "plugin/buy_courses/success.php";
    $cancelURL = $server . "plugin/buy_courses/error.php";

    $datos_curso = info_curso($_SESSION['bc_curso_code']);
    $title_curso = $datos_curso['title'];
    $i = 0;
    $extra = "&L_PAYMENTREQUEST_0_NAME" . $i . "=" . $title_curso;
    $extra .= "&L_PAYMENTREQUEST_0_AMT" . $i . "=" . $paymentAmount;
    $extra .= "&L_PAYMENTREQUEST_0_QTY" . $i . "=1";

    $resArray = CallShortcutExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $extra);
    $ack = strtoupper($resArray["ACK"]);

    if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
        RedirectToPayPal($resArray["TOKEN"]);
    } else {
        //Mostrar errores
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

if ($_POST['payment_type'] == "Transferencia") {
    $_cid = 0;
    $interbreadcrumb[] = array("url" => "list.php", "name" => 'Listado de cursos a la venta');

    $tpl = new Template('Tipo de pago');

    $code = $_SESSION['bc_curso_code'];
    $infocurso = info_curso($code);

    $tpl->assign('curso', $infocurso);
    $tpl->assign('server', $_configuration['root_web']);
    $tpl->assign('title', $_SESSION['bc_curso_title']);
    $tpl->assign('price', $_SESSION['Payment_Amount']);
    $tpl->assign('moneda', $_SESSION['bc_tipomoneda']);
    if (!isset($_SESSION['_user'])) {
        $tpl->assign('name', $_SESSION['bc_user']['firstName'] . ' ' . $_SESSION['bc_user']['lastName']);
        $tpl->assign('email', $_SESSION['bc_user']['mail']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    } else {
        $tpl->assign('name', $_SESSION['bc_user']['firstname'] . ' ' . $_SESSION['bc_user']['lastname']);
        $tpl->assign('email', $_SESSION['bc_user']['email']);
        $tpl->assign('user', $_SESSION['bc_user']['username']);
    }

    //Obtenemos el listado de cuentas bancarias.
    $accountsList = listAccounts();
    $tpl->assign('accounts', $accountsList);

    $listing_tpl = 'buy_courses/view/process_confirm.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}

?>
