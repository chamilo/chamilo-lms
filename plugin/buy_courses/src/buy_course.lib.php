<?php
/**
 * Functions
 * @package chamilo.plugin.notify
 */
require_once '../../../main/inc/global.inc.php';
require_once '../config.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';

function sincronizar()
{
    $sql = "UPDATE plugin_buycourses SET synchronized='NO'";
    Database::query($sql);

    $sql = "SELECT id FROM course";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $sql = "SELECT 1 FROM plugin_buycourses WHERE id_course='" . $row['id'] . "';";
        $tmp = Database::query($sql);
        if (Database::affected_rows() > 0) {
            $sql = "UPDATE plugin_buycourses SET synchronized='SI' WHERE id_course='" . $row['id'] . "';";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO plugin_buycourses (id_course,visible,synchronized) VALUES ('" . $row['id'] . "','NO','SI');";
            Database::query($sql);
        }
    }
    $sql = "DELETE FROM plugin_buycourses WHERE synchronized='NO';";
    Database::query($sql);
}

function listCourses()
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $sql = "SELECT a.id_course, a.visible, a.price, b.* FROM $tableBuyCourse a, $tableCourse b WHERE a.id_course = b.id;";

    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listado_cursos_user()
{
    $sql = "SELECT a.id_course, a.visible, a.price, b.* FROM plugin_buycourses a, course b WHERE a.id_course=b.id AND a.visible='SI';";
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
                $sql = "SELECT 1 FROM plugin_bc_temporal WHERE course_code='" . $row['code'] . "' AND user_id='" . $_SESSION['_user']['user_id'] . "';";
                $tmp2 = Database::query($sql);
                if (Database::affected_rows() > 0) {
                    $row['matriculado'] = "TMP";
                } else {
                    $row['matriculado'] = "NO";
                }
            }
        } else {
            $sql = "SELECT 1 FROM plugin_bc_temporal WHERE course_code='" . $row['code'] . "' AND user_id='" . $_SESSION['_user']['user_id'] . "';";
            $tmp2 = Database::query($sql);
            if (Database::affected_rows() > 0) {
                $row['matriculado'] = "TMP";
            } else {
                $row['matriculado'] = "NO";
            }
        }
        //Comprobamos imagen
        if (file_exists("../../courses/" . $row['code'] . "/course-pic85x85.png")) {
            $row['imagen_curso'] = "courses/" . $row['code'] . "/course-pic85x85.png";
        } else {
            $row['imagen_curso'] = "main/img/without_picture.png";
        }
        $row['price'] = number_format($row['price'], 2, '.', ' ');
        $aux[] = $row;
    }
    return $aux;
}

function comprueba_curso_user($course, $user)
{
    $sql = "SELECT 1 FROM course_rel_user WHERE course_code='" . $course . "' AND user_id='" . $user . "';";
    $tmp = Database::query($sql);
    if (Database::affected_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

function comprueba_curso_user_transf($course, $user)
{
    $sql = "SELECT 1 FROM plugin_bc_temporal WHERE course_code='" . $course . "' AND user_id='" . $user . "';";
    $tmp = Database::query($sql);
    if (Database::affected_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

function listado_categorias()
{
    $sql = "SELECT code, name FROM course_category";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * Return an icon representing the visibility of the course
 */
function get_course_visibility_icon($v)
{
    $style = 'margin-bottom:-5px;margin-right:5px;';
    switch ($v) {
        case 0:
            return Display::return_icon('bullet_red.gif', get_lang('CourseVisibilityClosed'), array('style' => $style));
            break;
        case 1:
            return Display::return_icon('bullet_orange.gif', get_lang('Private'), array('style' => $style));
            break;
        case 2:
            return Display::return_icon('bullet_green.gif', get_lang('OpenToThePlatform'), array('style' => $style));
            break;
        case 3:
            return Display::return_icon('bullet_blue.gif', get_lang('OpenToTheWorld'), array('style' => $style));
            break;
        default:
            return '';
    }
}

function listado_monedas()
{
    $sql = "SELECT * FROM plugin_buycourses_countries ORDER BY country_name ASC";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listAccounts()
{
    $sql = "SELECT * FROM plugin_bc_transf";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function parametros_paypal()
{
    $sql = "SELECT * FROM plugin_bc_paypal";
    $res = Database::query($sql);
    $aux = array();
    $row = Database::fetch_assoc($res);
    return $row;
}

function parametros_transf()
{
    $sql = "SELECT * FROM plugin_bc_transf";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function busca_moneda()
{
    $sql = "SELECT * FROM plugin_buycourses_countries WHERE status='1';";
    $res = Database::query($sql);
    $aux = array();
    $row = Database::fetch_assoc($res);
    return $row['currency_code'];
}

function info_curso($code)
{
    $sql = "SELECT a.id_course, a.visible, a.price, b.* FROM plugin_buycourses a, course b WHERE a.id_course=b.id AND a.visible='SI' AND b.id='" . $code . "';";
    $res = Database::query($sql);
    $aux = array();
    $row = Database::fetch_assoc($res);
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
    if (file_exists("../../courses/" . $row['code'] . "/course-pic85x85.png")) {
        $row['imagen_curso'] = "courses/" . $row['code'] . "/course-pic85x85.png";
    } else {
        $row['imagen_curso'] = "main/img/without_picture.png";
    }
    $row['price'] = number_format($row['price'], 2, '.', ' ');

    return $row;
}


/**
 * function texto_aleatorio (integer $long = 5, boolean $lestras_min = true, boolean $letras_max = true, boolean $num = true))
 *
 * Permite generar contrasenhas de manera aleatoria.
 *
 * @$long: Especifica la longitud de la contrasenha
 * @$letras_min: Podra usar letas en minusculas
 * @$letras_max: Podra usar letas en mayusculas
 * @$num: Podra usar numeros
 *
 * return string
 */
function texto_aleatorio($long = 6, $letras_min = true, $letras_max = true, $num = true)
{
    $salt = $letras_min ? 'abchefghknpqrstuvwxyz' : '';
    $salt .= $letras_max ? 'ACDEFHKNPRSTUVWXYZ' : '';
    $salt .= $num ? (strlen($salt) ? '2345679' : '0123456789') : '';

    if (strlen($salt) == 0) {
        return '';
    }

    $i = 0;
    $str = '';

    srand((double)microtime() * 1000000);

    while ($i < $long) {
        $num = rand(0, strlen($salt) - 1);
        $str .= substr($salt, $num, 1);
        $i++;
    }

    return $str;
}

function calculateReference()
{
    $sql = "SELECT MAX(cod) FROM plugin_bc_temporal";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    if ($row['MAX(cod)'] != '') {
        $reference = $row['MAX(cod)'];
    } else {
        $reference = '1';
    }
    $randomText = texto_aleatorio();
    $reference .= $randomText;
    return $reference;
}

function listado_pendientes()
{
    $sql = "SELECT * FROM plugin_bc_temporal;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}