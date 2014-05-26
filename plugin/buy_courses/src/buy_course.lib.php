<?php
/**
 * Functions
 * @package chamilo.plugin.notify
 */
require_once '../../../main/inc/global.inc.php';
require_once '../config.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';

function sync()
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);

    $sql = "UPDATE $tableBuyCourse SET sync = 0";
    Database::query($sql);

    $sql = "SELECT id FROM $tableCourse";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $sql = "SELECT 1 FROM $tableBuyCourse WHERE id_course='" . $row['id'] . "';";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $sql = "UPDATE $tableBuyCourse SET sync = 1 WHERE id_course='" . $row['id'] . "';";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO $tableBuyCourse (id_course, visible, sync) VALUES ('" . $row['id'] . "', 0, 1);";
            Database::query($sql);
        }
    }
    $sql = "DELETE FROM $tableBuyCourse WHERE sync = 0;";
    Database::query($sql);
}

function listCourses()
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $sql = "SELECT a.id_course, a.visible, a.price, b.*
        FROM $tableBuyCourse a, $tableCourse b
        WHERE a.id_course = b.id;";

    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function userCourseList()
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);

    $sql = "SELECT a.id_course, a.visible, a.price, b.*
        FROM $tableBuyCourse a, $tableCourse b
        WHERE a.id_course = b.id AND a.visible = 1;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        //check teacher
        $sql = "SELECT lastname, firstname
        FROM course_rel_user a, user b
        WHERE a.course_code='" . $row['code'] . "'
        AND a.role<>'' AND a.role<>'NULL'
        AND a.user_id=b.user_id;";

        $tmp = Database::query($sql);
        $rowTmp = Database::fetch_assoc($tmp);
        $row['teacher'] = $rowTmp['firstname'] . ' ' . $rowTmp['lastname'];
        //check if the user is enrolled
        if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
            $sql = "SELECT 1 FROM $tableCourseRelUser
                WHERE course_code='" . $row['code'] . "'
                AND user_id='" . $_SESSION['_user']['user_id'] . "';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $row['enrolled'] = "YES";
            } else {
                $sql = "SELECT 1 FROM $tableBuyCourseTemporal
                    WHERE course_code='" . $row['code'] . "'
                    AND user_id='" . $_SESSION['_user']['user_id'] . "';";
                Database::query($sql);
                if (Database::affected_rows() > 0) {
                    $row['enrolled'] = "TMP";
                } else {
                    $row['enrolled'] = "NO";
                }
            }
        } else {
            $sql = "SELECT 1 FROM $tableBuyCourseTemporal
                WHERE course_code='" . $row['code'] . "'
                AND user_id='" . $_SESSION['_user']['user_id'] . "';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $row['enrolled'] = "TMP";
            } else {
                $row['enrolled'] = "NO";
            }
        }
        //check images
        if (file_exists("../../courses/" . $row['code'] . "/course-pic85x85.png")) {
            $row['course_img'] = "courses/" . $row['code'] . "/course-pic85x85.png";
        } else {
            $row['course_img'] = "main/img/without_picture.png";
        }
        $row['price'] = number_format($row['price'], 2, '.', ' ');
        $aux[] = $row;
    }

    return $aux;
}

function checkUserCourse($course, $user)
{
    $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = "SELECT 1 FROM $tableCourseRelUser
        WHERE course_code='" . $course . "'
        AND user_id='" . $user . "';";
    Database::query($sql);
    if (Database::affected_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

function checkUserCourseTransference($course, $user)
{
    $tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
    $sql = "SELECT 1 FROM $tableBuyCourseTemporal
        WHERE course_code='" . $course . "'
        AND user_id='" . $user . "';";
    Database::query($sql);
    if (Database::affected_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

function listCategories()
{
    $tblCourseCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $sql = "SELECT code, name FROM $tblCourseCategory";
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
function getCourseVisibilityIcon($option)
{
    $style = 'margin-bottom:-5px;margin-right:5px;';
    switch ($option) {
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

function listCurrency()
{
    $tableBuyCourseCountry = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
    $sql = "SELECT * FROM $tableBuyCourseCountry
        ORDER BY country_name ASC";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function listAccounts()
{
    $tableBuyCourseTransference = Database::get_main_table(TABLE_BUY_COURSE_TRANSFERENCE);
    $sql = "SELECT * FROM $tableBuyCourseTransference";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function paypalParameters()
{
    $tableBuyCoursePaypal = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);
    $sql = "SELECT * FROM $tableBuyCoursePaypal";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row;
}

function transferenceParameters()
{
    $tableBuyCourseTransference = Database::get_main_table(TABLE_BUY_COURSE_TRANSFERENCE);
    $sql = "SELECT * FROM $tableBuyCourseTransference";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

function findCurrency()
{
    $tableBuyCourseCountry = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
    $sql = "SELECT * FROM $tableBuyCourseCountry WHERE status='1';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row['currency_code'];
}

function courseInfo($code)
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);

    $sql = "SELECT a.id_course, a.visible, a.price, b.*
        FROM $tableBuyCourse a, course b
        WHERE a.id_course=b.id
        AND a.visible = 1
        AND b.id = '" . $code . "';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    // Check teacher
    $sql = "SELECT lastname, firstname
        FROM $tableCourseRelUser a, $tableUser b
        WHERE a.course_code = '" . $row['code'] . "'
        AND a.role <> '' AND a.role <> 'NULL'
        AND a.user_id = b.user_id;";
    $tmp = Database::query($sql);
    $rowTmp = Database::fetch_assoc($tmp);
    $row['teacher'] = $rowTmp['firstname'] . ' ' . $rowTmp['lastname'];
    //Check if student is enrolled
    if (isset($_SESSION['_user']) || $_SESSION['_user']['user_id'] != '') {
        $sql = "SELECT 1 FROM $tableCourseRelUser
            WHERE course_code='" . $row['code'] . "'
            AND user_id='" . $_SESSION['_user']['user_id'] . "';";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $row['enrolled'] = "YES";
        } else {
            $row['enrolled'] = "NO";
        }
    } else {
        $row['enrolled'] = "NO";
    }
    //check img
    if (file_exists("../../courses/" . $row['code'] . "/course-pic85x85.png")) {
        $row['course_img'] = "courses/" . $row['code'] . "/course-pic85x85.png";
    } else {
        $row['course_img'] = "main/img/without_picture.png";
    }
    $row['price'] = number_format($row['price'], 2, '.', ' ');

    return $row;
}

function randomText($long = 6, $minWords = true, $maxWords = true, $number = true)
{
    $salt = $minWords ? 'abchefghknpqrstuvwxyz' : '';
    $salt .= $maxWords ? 'ACDEFHKNPRSTUVWXYZ' : '';
    $salt .= $number ? (strlen($salt) ? '2345679' : '0123456789') : '';

    if (strlen($salt) == 0) {
        return '';
    }

    $i = 0;
    $str = '';

    srand((double)microtime() * 1000000);

    while ($i < $long) {
        $number = rand(0, strlen($salt) - 1);
        $str .= substr($salt, $number, 1);
        $i++;
    }

    return $str;
}

function calculateReference()
{
    $tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
    $sql = "SELECT MAX(cod) as cod FROM $tableBuyCourseTemporal";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    if ($row['cod'] != '') {
        $reference = $row['cod'];
    } else {
        $reference = '1';
    }
    $randomText = randomText();
    $reference .= $randomText;

    return $reference;
}

function pendingList()
{
    $tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
    $sql = "SELECT * FROM $tableBuyCourseTemporal;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}