<?php
/* For license terms, see /license.txt */
/**
 * Functions
 * @package chamilo.plugin.test2pdf
 */
/**
 * Init
 */
require_once '../../../main/inc/global.inc.php';
require_once '../config.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';

$letters = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];

/**
 * List exercises
 * @param int $course_id Course ID
 * @param int $session_id Session ID
 * @return array Results (list of exercice details)
 * @throws Exception
 */
function showExerciseCourse($course_id, $session_id = 0)
{
    $course_id = (int) $course_id;
    $session_id = (int) $session_id;
    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $tableLpItem = Database::get_course_table(TABLE_LP_ITEM);
    $sql = "SELECT a.* 
      FROM $tableQuiz a 
        LEFT JOIN $tableLpItem b 
        ON a.iid = b.path AND a.c_id = b.c_id 
      WHERE a.c_id = $course_id
        AND (a.session_id = $session_id OR a.session_id IS NULL) 
        AND (a.active = 1 OR (item_type = 'quiz' AND b.c_id = $course_id)) 
        ORDER BY a.title ASC;";
    $res = Database::query($sql);
    if (!$res) {
        die("Error Database $tableQuiz");
    }
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List quiz details
 * @return array Results (list of quiz details)
 * @throws Exception
 */
function getInfoQuiz($c_id, $id)
{
    $c_id = (int) $c_id;
    $id = (int) $id;
    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $sql = "SELECT * FROM $tableQuiz WHERE c_id = $c_id AND iid = $id";
    $res = Database::query($sql);
    if (!$res) {
        die("Error Database $tableQuiz");
    }
    $row = Database::fetch_assoc($res);
    return $row;
}

/**
 * List question_id
 * @return array Results (list question ID)
 * @throws Exception
 */
function getQuestions($c_id, $quizId)
{
    $c_id = (int) $c_id;
    $quizId = (int) $quizId;
    $tableQuizQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $sql = "SELECT a.question_id AS question_id 
        FROM $tableQuizQuestion a 
        INNER JOIN $tableQuestion b ON a.question_id = b.iid 
        WHERE a.c_id = $c_id AND b.c_id = a.c_id AND a.exercice_id = $quizId 
        AND (b.type IN (1, 2, 9, 10, 11, 12, 14))
        ORDER BY question_order ASC;";
    $res = Database::query($sql);
    if (!$res) {
        die("Error Database $tableQuizQuestion");
    }
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row['question_id'];
    }
    return $aux;
}

/**
 * List question details
 * @return array Results (list of question details)
 * @throws Exception
 */
function getInfoQuestion($c_id, $id)
{
    $c_id = (int) $c_id;
    $id = (int) $id;
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $sql = "SELECT * FROM $tableQuestion 
        WHERE c_id = $c_id 
        AND iid = $id
        AND (type IN (1, 2, 9, 10, 11, 12, 14))";
    $res = Database::query($sql);
    if (!$res) {
        die("Error Database $tableQuestion");
    }
    $row = Database::fetch_assoc($res);
    return $row;
}

/**
 * List answer details
 * @return array Results (list of answer by question_id)
 * @throws Exception
 */
function getAnswers($c_id, $id)
{
    $c_id = (int) $c_id;
    $id = (int) $id;
    $tableQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $sql = "SELECT * FROM $tableQuizAnswer
	    WHERE c_id = $c_id AND question_id = $id
	    ORDER BY position ASC;";
    $res = Database::query($sql);
    if (!$res) {
        die("Error Database $tableQuizAnswer");
    }
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * Remove all html tag
 * @param string $string The string to be stripped of HTML
 * @return string clean of html tag
 */
function removeHtml($string)
{
    $txt=str_replace("<html>", "", $string);
    $txt=str_replace("<head>", "", $txt);
    $txt=str_replace("<title>", "", $txt);
    $txt=str_replace("</title>", "", $txt);
    $txt=str_replace("</head>", "", $txt);
    $txt=str_replace("<body>", "", $txt);
    $txt=str_replace("</body>", "", $txt);
    $txt=str_replace("</html>", "", $txt);
    $txt=strip_tags($txt);
    $txt=str_replace(chr(13).chr(10), "", $txt);

    /*
    $txt=str_replace("&nbsp;"," ",$txt);
    $txt=str_replace("&Aacute;","Á",$txt);
    $txt=str_replace("&aacute;","á",$txt);
    $txt=str_replace("&Eacute;","É",$txt);
    $txt=str_replace("&eacute;","é",$txt);
    $txt=str_replace("&Iacute;","Í",$txt);
    $txt=str_replace("&iacute;","í",$txt);
    $txt=str_replace("&Oacute;","Ó",$txt);
    $txt=str_replace("&oacute;","ó",$txt);
    $txt=str_replace("&Uacute;","Ú",$txt);
    $txt=str_replace("&uacute;","ú",$txt);
    $txt=str_replace("&Ntilde;","Ñ",$txt);
    $txt=str_replace("&ntilde;","ñ",$txt);
    $txt=str_replace("&agrave;","à",$txt);
    $txt=str_replace("&Agrave;","À",$txt);
    $txt=str_replace("&iexcl;","¡",$txt);
    $txt=str_replace("&middot;","·",$txt);
    $txt=str_replace("&Ccedil;","Ç",$txt);
    $txt=str_replace("&ccedil;","ç",$txt);
    $txt=str_replace("&quot;",'"',$txt);
    $txt=str_replace("&ordf;",'ª',$txt);
    $txt=str_replace("&ordm;",'º',$txt);
    $txt=str_replace("&amp;",'&',$txt);
    $txt=str_replace("&bull;",'•',$txt);
    $txt=str_replace("&iquest;",'¿',$txt);
    $txt=str_replace("&euro;",'EUR',$txt);
    $txt=str_replace("&uuml;",'ü',$txt);
    $txt=str_replace("&Uuml;",'Ü',$txt);
    $txt=str_replace("&uml;",'¨',$txt);*/
    
    return $txt;
}

/**
 * Remove all html tag
 * @param string $string The string to be stripped of accents
 * @return string clean of html tag
 */
function removeQuotes($string)
{
    //$txt=strip_tags($cadena);
    $txt=str_replace("&nbsp;", " ", $string);
    $txt=str_replace("&Aacute;", "Á", $txt);
    $txt=str_replace("&aacute;", "á", $txt);
    $txt=str_replace("&Eacute;", "É", $txt);
    $txt=str_replace("&eacute;", "é", $txt);
    $txt=str_replace("&Iacute;", "Í", $txt);
    $txt=str_replace("&iacute;", "í", $txt);
    $txt=str_replace("&Oacute;", "Ó", $txt);
    $txt=str_replace("&oacute;", "ó", $txt);
    $txt=str_replace("&Uacute;", "Ú", $txt);
    $txt=str_replace("&uacute;", "ú", $txt);
    $txt=str_replace("&Ntilde;", "Ñ", $txt);
    $txt=str_replace("&ntilde;", "ñ", $txt);
    $txt=str_replace("&quot;", '"', $txt);
    $txt=str_replace("&ordf;", 'ª', $txt);
    $txt=str_replace("&ordm;", 'º', $txt);
    $txt=str_replace("&amp;", '&', $txt);
    $txt=str_replace("&bull;", '•', $txt);
    $txt=str_replace("&iquest; &", '¿', $txt);
    $txt=str_replace("&agrave;", "à", $txt);
    $txt=str_replace("&Agrave;", "À", $txt);
    $txt=str_replace("&iexcl;", "¡", $txt);
    $txt=str_replace("&middot;", "·", $txt);
    $txt=str_replace("&Ccedil;", "Ç", $txt);
    $txt=str_replace("&ccedil;", "ç", $txt);
    $txt=str_replace("&euro;", 'EUR', $txt);
    $txt=str_replace("&uuml;", 'ü', $txt);
    $txt=str_replace("&Uuml;", 'Ü', $txt);
    $txt=str_replace("uml;", '¨', $txt);
    return $txt;
}

/**
 * Returns an associative array (keys: R,G,B) from a hex html code (e.g. #3FE5AA)
 * @param string $color
 * @return array
 */
function hex2dec($color = '#000000')
{
    $R = substr($color, 1, 2);
    $rouge = hexdec($R);
    $V = substr($color, 3, 2);
    $vert = hexdec($V);
    $B = substr($color, 5, 2);
    $bleu = hexdec($B);
    $tbl_couleur = [];
    $tbl_couleur['R'] = $rouge;
    $tbl_couleur['V'] = $vert;
    $tbl_couleur['B'] = $bleu;
    return $tbl_couleur;
}

/**
 * Conversion of pixel -> millimeter at 72 dpi
 * @param $px
 * @return float|int
 */
function px2mm($px)
{
    return $px*25.4/72;
}

/**
 * Transform from HTML to text
 * @param string $html HTML string
 * @return string Pure text version of the given string
 */
function txtentities($html)
{
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}
