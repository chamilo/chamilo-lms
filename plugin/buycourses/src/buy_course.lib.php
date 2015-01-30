<?php
/* For license terms, see /license.txt */
/**
 * Functions
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../../../main/inc/global.inc.php';
require_once '../config.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';

/**
 *
 */
function sync()
{
    $tableBuySessionRelCourse = Database::get_main_table(TABLE_BUY_SESSION_COURSE);
    $tableSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

    $sql = "UPDATE $tableBuySessionRelCourse SET sync = 0";
    Database::query($sql);

    $sql = "SELECT id_session, course_code, nbr_users FROM $tableSessionRelCourse";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $sql = "SELECT 1 FROM $tableBuySessionRelCourse WHERE id_session='" . $row['id_session'] . "';";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $sql = "UPDATE $tableBuySessionRelCourse SET sync = 1 WHERE id_session='" . $row['id_session'] . "';";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO $tableBuySessionRelCourse (id_session, course_code, nbr_users, sync)
            VALUES ('" . $row['id_session'] . "', '" . $row['course_code'] . "', '" . $row['nbr_users'] . "', 1);";
            Database::query($sql);
        }
    }
    $sql = "DELETE FROM $tableBuySessionRelCourse WHERE sync = 0;";
    Database::query($sql);

    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);

    $sql = "UPDATE $tableBuyCourse SET sync = 0";
    Database::query($sql);

    $sql = "SELECT id, code, title FROM $tableCourse";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $sql = "SELECT id_session FROM $tableBuySessionRelCourse
        WHERE course_code = '" . $row['code'] . "' LIMIT 1";
        $courseIdSession = Database::fetch_assoc(Database::query($sql))['id_session'];
        if (!is_numeric($courseIdSession)) {
            $courseIdSession = 0;
        }

        $sql = "SELECT 1 FROM $tableBuyCourse WHERE course_id='" . $row['id'] . "';";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $sql = "UPDATE $tableBuyCourse SET sync = 1, session_id = $courseIdSession WHERE course_id='" . $row['id'] . "';";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO $tableBuyCourse (session_id, course_id, code, title, visible, sync)
            VALUES ('" . $courseIdSession . "', '" . $row['id'] . "', '" .
            $row['code'] . "', '" . $row['title'] . "', 0, 1);";
            Database::query($sql);
        }
    }
    $sql = "DELETE FROM $tableBuyCourse WHERE sync = 0;";
    Database::query($sql);

    $tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
    $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);

    $sql = "UPDATE $tableBuySession SET sync = 0";
    Database::query($sql);

    $sql = "SELECT id, name, date_start, date_end FROM $tableSession";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $sql = "SELECT 1 FROM $tableBuySession WHERE session_id='" . $row['id'] . "';";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $sql = "UPDATE $tableBuySession SET sync = 1 WHERE session_id='" . $row['id'] . "';";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO $tableBuySession (session_id, name, date_start, date_end, visible, sync)
            VALUES ('" . $row['id'] . "', '" . $row['name'] . "', '" .
                $row['date_start'] .  "', '" . $row['date_end'] . "', 0, 1);";
            Database::query($sql);
        }
    }
    $sql = "DELETE FROM $tableBuySession WHERE sync = 0;";
    Database::query($sql);
}

/**
 * List sessions details from the buy-session table and the session table
 * @return array Results (list of session details)
 */
function listSessions()
{
    $tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
    $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $sql = "SELECT a.session_id, a.visible, a.price, b.*
        FROM $tableBuySession a, $tableSession b
        WHERE a.session_id = b.id;";

    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

/**
 * List courses details from the buy-course table and the course table
 * @return array Results (list of courses details)
 */
function listCourses()
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $sql = "SELECT a.course_id, a.visible, a.price, b.*
        FROM $tableBuyCourse a, $tableCourse b
        WHERE a.course_id = b.id AND a.session_id = 0;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * Lists current user session details, including each session course details
 * @return array Sessions details list
 */
function userSessionList()
{
    $tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
    $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $tableBuySessionRelCourse = Database::get_main_table(TABLE_BUY_SESSION_COURSE);
    $tableSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tableSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tableBuySessionTemporal = Database::get_main_table(TABLE_BUY_SESSION_TEMPORARY);
    $currentUserId = api_get_user_id();

    // get existing sessions
    $sql = "SELECT a.session_id, a.visible, a.price, b.*
        FROM $tableBuySession a, $tableSession b
        WHERE a.session_id = b.id AND a.visible = 1;";
    $resSessions = Database::query($sql);
    $auxSessions = array();
    // loop through all sessions
    while ($rowSession = Database::fetch_assoc($resSessions)) {
        // get courses of current session
        $sqlSessionCourse = "SELECT DISTINCT a.id_session, a.course_code, a.nbr_users
        FROM $tableBuySessionRelCourse a, $tableSessionRelCourse b
        WHERE a.id_session = b.id_session AND a.id_session = " . $rowSession['session_id'] . ";";
        $resSessionCourse = Database::query($sqlSessionCourse);
        $aux = array();
        // loop through courses of current session
        while ($rowSessionCourse = Database::fetch_assoc($resSessionCourse)) {
            // get course of current session
            $sql = "SELECT a.course_id, a.session_id, a.visible, a.price, b.*
            FROM $tableBuyCourse a, $tableCourse b
            WHERE a.code = b.code AND a.code = '" . $rowSessionCourse['course_code'] . "';";
            $res = Database::query($sql);
            // loop inside a course of current session
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
                //check images
                if (file_exists(api_get_path(SYS_COURSE_PATH) . $row['directory'] . "/course-pic85x85.png")) {
                    $row['course_img'] = "courses/" . $row['directory'] . "/course-pic85x85.png";
                } else {
                    $row['course_img'] = "main/img/without_picture.png";
                }
                $row['price'] = number_format($row['price'], 2, '.', ' ');
                $aux[] = $row;
            }
        }
        //check if the user is enrolled in the current session
        if ($currentUserId > 0) {
            $sql = "SELECT 1 FROM $tableSessionRelUser
                WHERE id_session ='".$rowSession['session_id']."' AND
                id_user = $currentUserId";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $rowSession['enrolled'] = "YES";
            } else {
                $sql = "SELECT 1 FROM $tableBuySessionTemporal
                    WHERE session_id ='".$rowSession['session_id']."' AND
                    user_id='" . $currentUserId . "';";
                Database::query($sql);
                if (Database::affected_rows() > 0) {
                    $rowSession['enrolled'] = "TMP";
                } else {
                    $rowSession['enrolled'] = "NO";
                }
            }
        } else {
            $sql = "SELECT 1 FROM $tableBuySessionTemporal
                WHERE session_id ='".$rowSession['session_id']."' AND
                user_id='" . $currentUserId . "';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $rowSession['enrolled'] = "TMP";
            } else {
                $rowSession['enrolled'] = "NO";
            }
        }
        // add courses to current session
        $rowSession['courses'] = $aux;
        // add the current whole session
        $auxSessions[] = $rowSession;
    }
    return $auxSessions;
}

/**
 * Lists current user course details
 * @return array Course details list
 */
function userCourseList()
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tableBuyCourseTemporal = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
    $currentUserId = api_get_user_id();

    $sql = "SELECT a.course_id, a.visible, a.price, b.*
        FROM $tableBuyCourse a, $tableCourse b
        WHERE a.course_id = b.id AND a.session_id = 0 AND a.visible = 1;";
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
        if ($currentUserId > 0) {
            $sql = "SELECT 1 FROM $tableCourseRelUser
                WHERE course_code='" . $row['code'] . "'
                AND user_id='" . $currentUserId . "';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $row['enrolled'] = "YES";
            } else {
                $sql = "SELECT 1 FROM $tableBuyCourseTemporal
                    WHERE course_code='" . $row['code'] . "'
                    AND user_id='" . $currentUserId . "';";
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
                AND user_id='" . $currentUserId . "';";
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

/**
 * Checks if a session or a course is already bought
 * @param string Session id or course code
 * @param int User id
 * @param string What has to be checked
 * @return boolean True if it is already bought, and false otherwise
 */
function checkUserBuy($parameter, $user, $type = 'COURSE')
{
    $sql = "SELECT 1 FROM %s WHERE %s ='" . Database::escape_string($parameter) . "' AND id_user='" . intval($user) . "';";
    $sql = $type === 'SESSION' ?
        sprintf($sql, Database::get_main_table(TABLE_MAIN_SESSION_USER), 'id_session') :
        sprintf($sql, Database::get_main_table(TABLE_MAIN_COURSE_USER), 'course_code');
    Database::query($sql);
    if (Database::affected_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if a session or a course has already a transfer
 * @param string Session id or course code
 * @param int User id
 * @param string What has to be checked
 * @return boolean True if it has already a transfer, and false otherwise
 */
function checkUserBuyTransfer($parameter, $user, $type = 'COURSE')
{
    $sql = "SELECT 1 FROM %s WHERE %s ='" . Database::escape_string($parameter) . "' AND user_id='" . intval($user) . "';";
    $sql = $type === 'SESSION' ?
        sprintf($sql, Database::get_main_table(TABLE_BUY_SESSION_TEMPORARY), 'session_id') :
        sprintf($sql, Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL), 'course_code');
    Database::query($sql);
    if (Database::affected_rows() > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Returns an array with all the categories
 * @return array All the categories
 */
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
 * @param int $option The course visibility
 * @return string HTML string of the visibility icon
 */
function getCourseVisibilityIcon($option)
{
    $style = 'margin-bottom:-5px;margin-right:5px;';
    switch ($option) {
        case 0:
            return Display::return_icon('bullet_red.gif', get_plugin_lang('CourseVisibilityClosed', 'BuyCoursesPlugin'), array('style' => $style));
            break;
        case 1:
            return Display::return_icon('bullet_orange.gif', get_plugin_lang('Private', 'BuyCoursesPlugin'), array('style' => $style));
            break;
        case 2:
            return Display::return_icon('bullet_green.gif', get_plugin_lang('OpenToThePlatform', 'BuyCoursesPlugin'), array('style' => $style));
            break;
        case 3:
            return Display::return_icon('bullet_blue.gif', get_plugin_lang('OpenToTheWorld', 'BuyCoursesPlugin'), array('style' => $style));
            break;
        default:
            return Display::return_icon('bullet_grey.gif', get_plugin_lang('CourseVisibilityHidden', 'BuyCoursesPlugin'), array('style' => $style));
    }
}
/**
 * List the available currencies
 * @result array The list of currencies
 */
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
/**
 * Gets the list of accounts from the buy_course_transfer table
 * @return array The list of accounts
 */
function listAccounts()
{
    $tableBuyCourseTransfer = Database::get_main_table(TABLE_BUY_COURSE_TRANSFER);
    $sql = "SELECT * FROM $tableBuyCourseTransfer";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}
/**
 * Gets the stored PayPal params
 * @return array The stored PayPal params
 */
function paypalParameters()
{
    $tableBuyCoursePaypal = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);
    $sql = "SELECT * FROM $tableBuyCoursePaypal";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row;
}
/**
 * Gets the parameters for the bank transfers payment method
 * @result array Bank transfer payment parameters stored
 */
function transferParameters()
{
    $tableBuyCourseTransfer = Database::get_main_table(TABLE_BUY_COURSE_TRANSFER);
    $sql = "SELECT * FROM $tableBuyCourseTransfer";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}
/**
 * Find the first enabled currency (there should be only one)
 * @result string The code of the active currency
 */
function findCurrency()
{
    $tableBuyCourseCountry = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
    $sql = "SELECT * FROM $tableBuyCourseCountry WHERE status='1';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row['currency_code'];
}
/**
 * Extended information about the session (from the session table as well as
 * the buy_session table)
 * @param string $code The session code
 * @return array Info about the session
 */
function sessionInfo($code)
{
    $tableBuySession = Database::get_main_table(TABLE_BUY_SESSION);
    $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $tableBuySessionRelCourse = Database::get_main_table(TABLE_BUY_SESSION_COURSE);
    $tableSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tableSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tableBuySessionTemporal = Database::get_main_table(TABLE_BUY_SESSION_TEMPORARY);
    $currentUserId = api_get_user_id();

    $code = Database::escape_string($code);
    $sql = "SELECT a.session_id, a.visible, a.price, b.*
        FROM $tableBuySession a, $tableSession b
        WHERE a.session_id=b.id
        AND a.visible = 1
        AND b.id = '".$code."';";
    $res = Database::query($sql);
    $rowSession = Database::fetch_assoc($res);
    $sqlSessionCourse = "SELECT DISTINCT a.id_session, a.course_code, a.nbr_users
    FROM $tableBuySessionRelCourse a, $tableSessionRelCourse b
    WHERE a.id_session = b.id_session AND a.id_session = " . $rowSession['session_id'] . ";";
    $resSessionCourse = Database::query($sqlSessionCourse);
    $aux = array();
    // loop through courses of current session
    while ($rowSessionCourse = Database::fetch_assoc($resSessionCourse)) {
        // get course of current session
        $sql = "SELECT a.course_id, a.session_id, a.visible, a.price, b.*
        FROM $tableBuyCourse a, $tableCourse b
        WHERE a.code = b.code AND a.code = '".$rowSessionCourse['course_code']."' AND a.visible = 1;";
        $res = Database::query($sql);
        // loop inside a course of current session
        while ($row = Database::fetch_assoc($res)) {
            //check teacher
            $sql = "SELECT lastname, firstname
            FROM course_rel_user a, user b
            WHERE a.course_code='".$row['code']."'
            AND a.role<>'' AND a.role<>'NULL'
            AND a.user_id=b.user_id;";
            $tmp = Database::query($sql);
            $rowTmp = Database::fetch_assoc($tmp);
            $row['teacher'] = $rowTmp['firstname'].' '.$rowTmp['lastname'];
            //check images
            if (file_exists(api_get_path(SYS_COURSE_PATH).$row['directory']."/course-pic85x85.png")) {
                $row['course_img'] = "courses/".$row['directory']."/course-pic85x85.png";
            } else {
                $row['course_img'] = "main/img/without_picture.png";
            }
            $row['price'] = number_format($row['price'], 2, '.', ' ');
            $aux[] = $row;
        }
    }
    //check if the user is enrolled in the current session
    if ($currentUserId > 0) {
        $sql = "SELECT 1 FROM $tableSessionRelUser
            WHERE id_user = $currentUserId";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $rowSession['enrolled'] = "YES";
        } else {
            $sql = "SELECT 1 FROM $tableBuySessionTemporal
                WHERE user_id='".$currentUserId."';";
            Database::query($sql);
            if (Database::affected_rows() > 0) {
                $rowSession['enrolled'] = "TMP";
            } else {
                $rowSession['enrolled'] = "NO";
            }
        }
    } else {
        $sql = "SELECT 1 FROM $tableBuySessionTemporal
            WHERE user_id='".$currentUserId."';";
        Database::query($sql);
        if (Database::affected_rows() > 0) {
            $rowSession['enrolled'] = "TMP";
        } else {
            $rowSession['enrolled'] = "NO";
        }
    }
    // add courses to current session
    $rowSession['courses'] = $aux;
    return $rowSession;
}
/**
 * Extended information about the course (from the course table as well as
 * the buy_course table)
 * @param string $code The course code
 * @return array Info about the course
 */
function courseInfo($code)
{
    $tableBuyCourse = Database::get_main_table(TABLE_BUY_COURSE);
    $tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tableUser = Database::get_main_table(TABLE_MAIN_USER);
    $currentUserId = api_get_user_id();
    $code = Database::escape_string($code);
    $sql = "SELECT a.course_id, a.visible, a.price, b.*
        FROM $tableBuyCourse a, course b
        WHERE a.course_id=b.id
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
    if ($currentUserId > 0) {
        $sql = "SELECT 1 FROM $tableCourseRelUser
            WHERE course_code='" . $row['code'] . "'
            AND user_id='" . $currentUserId . "';";
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
/**
 * Generates a random text (used for order references)
 * @param int $long
 * @param bool $minWords
 * @param bool $maxWords
 * @param bool $number
 * @return string A random text
 */
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
/**
 * Generates an order reference
 * @result string A reference number
 */
function calculateReference($bcCodetext)
{
    $tableBuyTemporal = $bcCodetext === 'THIS_IS_A_SESSION' ?
        Database::get_main_table(TABLE_BUY_SESSION_TEMPORARY) :
        Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
    $sql = "SELECT MAX(cod) as cod FROM $tableBuyTemporal";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    $reference = ($row['cod'] != '') ? $row['cod'] : '1';
    $randomText = randomText();
    $reference .= $randomText;
    return $reference;
}
/**
 * Gets a list of pending orders
 * @result array List of orders
 * @todo Enable pagination
 */
function pendingList($bcCodetext)
{
    $tableBuyTemporal = $bcCodetext === 'THIS_IS_A_SESSION' ?
        Database::get_main_table(TABLE_BUY_SESSION_TEMPORARY) :
        Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
    $sql = "SELECT * FROM $tableBuyTemporal;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}
