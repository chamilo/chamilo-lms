<?php
require_once '../../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once '../lib/buy_course_plugin.class.php';
require_once api_get_path(LIBRARY_PATH) . 'course.lib.php';

$plugin = Buy_CoursesPlugin::create();
$buy_name = $plugin->get_lang('Buy');

function quitar_html($cadena)
{
    $txt = str_replace("<br />", chr(13) . chr(10), $cadena);
    $txt = str_replace("<br>", chr(13) . chr(10), $txt);
    $txt = str_replace("<li>&nbsp;", chr(13) . chr(10) . "    � ", $txt);
    $txt = str_replace("<li>", chr(13) . chr(10) . "� ", $txt);
    $txt = str_replace("<br/>", chr(13) . chr(10), $txt);
    $txt = str_replace("</p>", chr(13) . chr(10), $txt);
    $txt = str_replace("<p>", "", $txt);
    $txt = str_replace("</tr>", chr(13) . chr(10), $txt);
    $txt = str_replace("</td>", "  algo      ", $txt);
    $txt = str_replace("</table>", chr(13) . chr(10), $txt);
    $txt = strip_tags($txt);
    $txt = str_replace("&nbsp;", " ", $txt);
    $txt = str_replace("&Aacute;", "�", $txt);
    $txt = str_replace("&aacute;", "�", $txt);
    $txt = str_replace("&Eacute;", "�", $txt);
    $txt = str_replace("&eacute;", "�", $txt);
    $txt = str_replace("&Iacute;", "�", $txt);
    $txt = str_replace("&iacute;", "�", $txt);
    $txt = str_replace("&Oacute;", "�", $txt);
    $txt = str_replace("&oacute;", "�", $txt);
    $txt = str_replace("&Uacute;", "�", $txt);
    $txt = str_replace("&uacute;", "�", $txt);
    $txt = str_replace("&Ntilde;", "�", $txt);
    $txt = str_replace("&ntilde;", "�", $txt);
    $txt = str_replace("&quot;", '"', $txt);
    $txt = str_replace("&ordf;", '�', $txt);
    $txt = str_replace("&ordm;", '�', $txt);
    $txt = str_replace("&amp;", '&', $txt);
    $txt = str_replace("&bull;", '�', $txt);
    $txt = str_replace("&euro;", '�', $txt);

    return $txt;
}

if ($_REQUEST['tab'] == 'sincronizar') {
    $sql = "SELECT code,title FROM course;";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux_code .= $row['code'];
        $aux_title .= $row['title'];
    }
    echo json_encode(array("status" => "true", "contenido" => $contenido));
}

if ($_REQUEST['tab'] == 'filtro_cursos') {
    $curso = $_REQUEST['curso'];
    $priceMin = $_REQUEST['pricemin'];
    $priceMax = $_REQUEST['pricemax'];
    $mostrar = $_REQUEST['mostrar'];
    $categoria = $_REQUEST['categoria'];
    $server = $_configuration['root_web'];

    $filtro = '';
    if ($curso != '') {
        $filtro .= "b.title LIKE '%" . $curso . "%'";
    }
    if ($priceMin != '') {
        if ($filtro == '') {
            $filtro .= "a.price >= '" . $priceMin . "'";
        } else {
            $filtro .= " AND a.price >= '" . $priceMin . "'";
        }
    }

    if ($priceMax != '') {
        if ($filtro == '') {
            $filtro .= "a.price <= '" . $priceMax . "'";
        } else {
            $filtro .= " AND a.price <= '" . $priceMax . "'";
        }
    }

    if ($categoria != '') {
        if ($filtro == '') {
            $filtro .= "b.category_code='" . $categoria . "'";
        } else {
            $filtro .= " AND b.category_code='" . $categoria . "'";
        }
    }

    if ($filtro == '') {
        $sql = "SELECT a.id_course, a.visible, a.price, b.* FROM plugin_buycourses a, course b WHERE a.id_course=b.id AND a.visible='SI';";
    } else {
        $sql = "SELECT a.id_course, a.visible, a.price, b.* FROM plugin_buycourses a, course b WHERE a.id_course=b.id AND a.visible='SI' AND " . $filtro . ";";
    }

    //echo $sql;
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        //Comprobamos profesor
        $sql = "SELECT lastname,firstname FROM course_rel_user a, user b WHERE a.course_code='" . $row['code'] . "' AND a.role<>'' AND a.role<>'NULL' AND a.user_id=b.user_id;";
        $tmp = Database::query($sql);
        $fila = Database::fetch_assoc($tmp);
        $row['profesor'] = $fila['firstname'] . ' ' . $fila['lastname'];
        //Comprobamos si el alumno est� matriculado
        if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
            $sql = "SELECT 1 FROM course_rel_user WHERE course_code='" . $row['code'] . "' AND user_id='" . $_SESSION['_user']['user_id'] . "';";
            $tmp = Database::query($sql);
            if (Database::affected_rows() > 0) {
                $row['matriculado'] = "SI";
            } else {
                $row['matriculado'] = "NO";
            }
        } else {
            $row['matriculado'] = "NO";
        }
        //Comprobamos imagen
        if (file_exists("../../../courses/" . $row['code'] . "/course-pic85x85.png")) {
            $row['imagen_curso'] = "courses/" . $row['code'] . "/course-pic85x85.png";
        } else {
            $row['imagen_curso'] = "main/img/without_picture.png";
        }

        if ($mostrar == "SI" && $row['matriculado'] == "SI") {
            //No hacemos nada
        } else {
            $aux[] = $row;
        }

    }


    foreach ($aux as $curso) { //{% for curso in cursos %}
        $contenido .= '<div class="well_border span8">';
        $contenido .= '<div class="row">';
        $contenido .= '<div class="span">';
        $contenido .= '<div class="thumbnail">';
        $contenido .= '<a class="ajax" rel="gb_page_center[778]" title="" href="' . $server . 'main/inc/ajax/course_home.ajax.php?a=show_course_information&code=' . $curso['code'] . '">';
        $contenido .= '<img alt="" src="' . $server . $curso['imagen_curso'] . '">';
        $contenido .= '</a>';
        $contenido .= '</div>';
        $contenido .= '</div>';
        $contenido .= '<div class="span4">';
        $contenido .= '<div class="categories-course-description">';
        $contenido .= '<h3>' . $curso['title'] . '</h3>';
        $contenido .= '<h5>Profesor: ' . $curso['profesor'] . '</h5>';
        $contenido .= '</div>';
        if ($curso['matriculado'] == "SI") { //{% if curso.matriculado == "SI" %}
            $contenido .= '<span class="label label-info">Ya se encuentra registrado en el curso</span>';
        } //{% endif %}
        $contenido .= '</div>';
        $contenido .= '<div class="span right">';
        $contenido .= '<div class="sprice right">' . $curso['price'] . ' &euro; </div>';
        $contenido .= '<div class="cleared"></div>';
        $contenido .= '<div class="btn-toolbar right">';
        $contenido .= '<a class="ajax btn btn-primary" title="" href="' . $server . 'main/inc/ajax/course_home.ajax.php?a=show_course_information&code=' . $curso['code'] . '">' . get_lang('Description') . '</a>&nbsp;';
        if ($curso['matriculado'] != "SI") { //{% if curso.matriculado != "SI" %}
            $contenido .= '<a class="btn btn-success" title="" href="' . $server . 'plugin/buy_courses/process.php?code=' . $curso['id'] . '">' . $buy_name . '</a>';
        } //{% endif %}
        $contenido .= '</div>';
        $contenido .= '</div>';
        $contenido .= '</div>';
        $contenido .= '</div>';
    } //{% endfor %}


    echo json_encode(array("status" => "true", "contenido" => $contenido));

}

if ($_REQUEST['tab'] == 'guardar_moneda') {
    $id = $_REQUEST['moneda'];
    $sql = "UPDATE plugin_buycourses_countries SET status='0';";
    $res = Database::query($sql);
    $sql = "UPDATE plugin_buycourses_countries SET status='1' WHERE id_country='" . $id . "';";
    $res = Database::query($sql);
    if (!res) {
        $contenido = 'Problema al guardar el tipo de moneda: ' . Database::error();
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    } else {
        $contenido = 'Guardado';
        echo json_encode(array("status" => "true", "contenido" => $contenido));
    }
}

if ($_REQUEST['tab'] == 'guardar_paypal') {
    $username = mysql_real_escape_string($_REQUEST['username']);
    $password = mysql_real_escape_string($_REQUEST['password']);
    $signature = mysql_real_escape_string($_REQUEST['signature']);
    $sandbox = mysql_real_escape_string($_REQUEST['sandbox']);
    $sql = "UPDATE plugin_bc_paypal SET sandbox='" . $sandbox . "', username='" . $username . "', password='" . $password . "', signature='" . $signature . "' WHERE id='1';";
    //echo $sql;
    $res = Database::query($sql);
    if (!res) {
        $contenido = 'Problema al guardar los parametros de paypal: ' . Database::error();
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    } else {
        $contenido = 'Guardado';
        echo json_encode(array("status" => "true", "contenido" => $contenido));
    }
}

if ($_REQUEST['tab'] == 'add_account') {
    $name = mysql_real_escape_string($_REQUEST['name']);
    $account = mysql_real_escape_string($_REQUEST['account']);
    $swift = mysql_real_escape_string($_REQUEST['swift']);
    $sql = "INSERT INTO plugin_bc_transf (name, account, swift) VALUES ('" . $name . "','" . $account . "', '" . $swift . "');";
    //echo $sql;


    $res = Database::query($sql);
    if (!res) {
        $contenido = 'Problema al insertar nueva cuenta: ' . Database::error();
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    } else {
        $contenido = 'Guardado';
        echo json_encode(array("status" => "true", "contenido" => $contenido));
    }
}

if ($_REQUEST['tab'] == 'delete_account') {
    $id = substr($_REQUEST['id'], 6);
    $sql = "DELETE FROM plugin_bc_transf WHERE id='" . $id . "';";
    //echo $sql;
    $res = Database::query($sql);
    if (!res) {
        $contenido = 'Problema al borrar la cuenta: ' . Database::error();
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    } else {
        $contenido = 'Guardado';
        echo json_encode(array("status" => "true", "contenido" => $contenido));
    }
}

if ($_REQUEST['tab'] == 'guardar_mod') {
    $id = substr($_REQUEST['id'], 5);
    $visible = ($_REQUEST['visible'] == "checked") ? ('SI') : ('NO');
    $price = mysql_real_escape_string($_REQUEST['price']);
    $obj = $_REQUEST['obj'];


    $sql = "UPDATE plugin_buycourses SET visible='" . $visible . "', price='" . $price . "' WHERE id_course='" . $id . "';";
    $res = Database::query($sql);
    if (!res) {
        $contenido = 'Problema al guardar el mensaje: ' . Database::error();
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    } else {
        echo json_encode(array("status" => "true", "id" => $id));
    }
}

if ($_REQUEST['tab'] == 'borrar_variables') {
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
    unset($_SESSION['bc_url']);
}

if ($_REQUEST['tab'] == 'borrar_pedido') {
    $id = substr($_REQUEST['id'], 6);
    $sql = "DELETE FROM plugin_bc_temporal WHERE cod='" . $id . "';";
    //echo $sql;
    $res = Database::query($sql);
    if (!res) {
        $contenido = 'Problema al borrar la cuenta: ' . Database::error();
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    } else {
        $contenido = 'Guardado';
        echo json_encode(array("status" => "true", "contenido" => $contenido));
    }
}

if ($_REQUEST['tab'] == 'confirmar_pedido') {
    $id = substr($_REQUEST['id'], 6);
    $sql = "SELECT * FROM plugin_bc_temporal WHERE cod='" . $id . "';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    $seguir = false;
    $user_id = $row['user_id'];
    $course_code = $row['course_code'];
    $all_course_information = CourseManager::get_course_information($course_code);

    if (CourseManager::subscribe_user($user_id, $course_code)) {
        $seguir = true;
    } else {
        $seguir = false;
    }
    //Activamos al usuario su cuenta
    if ($seguir) {
        $TABLE_USER = Database::get_main_table(TABLE_MAIN_USER);
        // 1. set account inactive
        $sql = "UPDATE " . $TABLE_USER . " SET active='1' WHERE user_id='" . $_SESSION['bc_user_id'] . "'";
        Database::query($sql);

        $sql = "DELETE FROM plugin_bc_temporal WHERE cod='" . $id . "';";
        //echo $sql;
        $res = Database::query($sql);
        $contenido = 'Se ha realizado con exito la subscripcion y activacion del usuario';
        echo json_encode(array("status" => "true", "contenido" => $contenido));
    } else {
        $contenido = 'Problema subscribir al usuario ';
        echo json_encode(array("status" => "false", "contenido" => $contenido));
    }
}

if ($_REQUEST['tab'] == 'cargar_tpv_configuracion') {
    $cod = substr($_REQUEST['cod'], 3);

    $contenido = '';
    $sql = "SELECT * FROM plugin_bc_tpv WHERE cod='" . $cod . "';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    $parametros = explode(";", $row['parametros']);
    $valores = explode(";", $row['valores']);

    $i = 0;
    $contenido .= "<table>";
    $contenido .= "<tr><td>&nbsp;</td><td><strong>Configuraci&oacute;n TPV " . $row['title'] . ":</strong></td></tr>";
    $contenido .= "<tr><td style='text-align:right; width:30%'>URL TPV: </td><td><input type='text' id='action' value='" . $row['action'] . "' /></td></tr>";
    while ($i < count($parametros)) {
        $contenido .= "<tr><td style='text-align:right; width:30%'>" . $parametros[$i] . ": </td><td><input type='text' id='valor_tpv" . $i . "' value='" . $valores[$i] . "' /></td></tr>";
        $i++;
    }
    $contenido .= "<tr><td>&nbsp;</td>";
    $contenido .= "<td>";
    $contenido .= "<input type='hidden' id='conf_tpv' value='" . $cod . "' />";
    $contenido .= "<input type='hidden' id='num_parametros' value='" . $row['num_parametros'] . "' />";
    $contenido .= "<input type='button' id='guardar_datos_tpv' value='Guardar datos' class='btn btn-primary' />";
    $contenido .= "</td></tr>";
    $contenido .= "</table>";

    echo json_encode(array("contenido" => $contenido));
}

if ($_REQUEST['tab'] == 'cargar_tpv_configuracion') {
    $cod = $_REQUEST['cod'];
    $sql = "UDPATE plugin_bc_tpv SET activo='NO'";
    Database::query($sql);
    $sql = "UPDATE plugin_bc_tpv SET activo='SI' WHERE cod='" . $cod . "';";
    Database::query($sql);
}

if ($_REQUEST['tab'] == 'save_tpv') {
    $cod = $_REQUEST['cod'];
    $nump = $_REQUEST['nump'];
    $action = $_REQUEST['action'];
    $parametros = $_REQUEST['parametros'];

    $valores = implode(";", $parametros);
    $sql = "UPDATE plugin_bc_tpv SET action='" . $action . "', valores='" . $valores . "' WHERE cod='" . $cod . "';";
    $res = Database::query($sql);
    if (!$res) {
        $contenido = Database::error();
    } else {
        $contenido = "Guardado";
    }
    echo json_encode(array("contenido" => $contenido));
}