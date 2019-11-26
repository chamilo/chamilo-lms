<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * API event handler functions for AICC / CMIv4 in HACP communication mode.
 *
 * @author   Denes Nagy <darkden@freemail.hu>
 * @author   Yannick Warnier <ywarnier@beeznest.org>
 *
 * @version  v 1.0
 *
 * @package  chamilo.learnpath
 *
 * @license    GNU/GPL
 */

/**
 * This script is divided into three sections.
 * The first section (below) is the initialisation part.
 * The second section is the AICC object part
 * The third section defines the event handlers for Chamilo's internal messaging
 * and frames refresh.
 *
 * This script implements the HACP messaging for AICC. The API messaging is
 * made by another set of scripts.
 *
 * Rules for HACP processing of one AU
 * Rule #1 The first HACP message issued must be a GetParam
 * Rule #2 The last HACP message issued must be an ExitAU
 * Rule #3 At least one PutParam message must be issued prior to an ExitAU message
 * Rule #4 No HACP messages can be issued after a successfully issued ExitAU message
 *
 * Only suspend_data and core.lesson_location should be sent updated to a late GetParam
 * request. All other params should be as when the AU was launched.
 */

/* INIT SECTION */

$debug = 0;

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Use session ID as provided by the request.
if (!empty($_REQUEST['aicc_sid'])) {
    session_id($_REQUEST['aicc_sid']);
    if ($debug > 1) {
        error_log('New LP - '.__FILE__.','.__LINE__.' - reusing session ID '.$_REQUEST['aicc_sid']);
    }
} elseif (!empty($_REQUEST['session_id'])) {
    session_id($_REQUEST['session_id']);
    if ($debug > 1) {
        error_log('New LP - '.__FILE__.','.__LINE__.' - reusing session ID '.$_REQUEST['session_id']);
    }
}
//Load common libraries using a compatibility script to bridge between 1.6 and 1.8.
require_once __DIR__.'/../inc/global.inc.php';
if ($debug > 2) {
    error_log('New LP - '.__FILE__.','.__LINE__.' - Current session ID: '.session_id());
}

// Is this needed? This is probabaly done in the header file.
$file = Session::read('file');
/** @var learnpath $oLP */
$oLP = UnserializeApi::unserialize(
    'not_allowed_classes',
    Session::read('lpobject')
);
$oItem = &$oLP->items[$oLP->current];
if (!is_object($oItem)) {
    error_log('New LP - aicc_hacp - Could not load oItem item', 0);
    exit;
}
$autocomplete_when_80pct = 0;

$result = [
    'core' => [],
    'core_lesson' => [],
    'core_vendor' => [],
    'evaluation' => [],
    'student_data' => [],
];
$convert_enc = ['%25', '%0D', '%0A', '%09', '%20', '%2D', '%2F', '%3B', '%3F', '%7B', '%7D', '%7C', '%5C', '%5E', '%7E', '%5B', '%5D', '%60', '%23', '%3E', '%3C', '%22'];
$convert_dec = ['%', "\r", "\n", "\t", ' ', '-', '/', ';', '?', '{', '}', '|', '\\', '^', '~', '[', ']', '`', '#', '>', '<', '"'];
$crlf = "\r\n";
//$tab = "\t";
$tab = "";
$s_ec = 'error='; //string for error code
$s_et = 'error_text='; //string for error text
$s_ad = 'aicc_data='; //string for aicc_data

$errors = [0 => 'Successful', 1 => 'Invalid Command', 2 => 'Invalid AU password', 3 => 'Invalid Session ID'];

$error_code = 0;
$error_text = '';
$aicc_data = '';
$result = '';
// Get REQUEST
if (!empty($_REQUEST['command'])) {
    //error_log('In '.__FILE__.', '.__LINE__.' - request is '.$_REQUEST['command'], 0);
    switch (strtolower($_REQUEST['command'])) {
        case 'getparam':
            // Request for all available data to be printed out in the answer.
            if (!empty($_REQUEST['version'])) {
                $hacp_version = Database::escape_string($_REQUEST['version']);
            }
            if (!empty($_REQUEST['session_id'])) {
                $hacp_session_id = Database::escape_string($_REQUEST['session_id']);
            }
            $error_code = 0;
            $error_text = $errors[$error_code];
            //$result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad;
            $result .= '[Core]'.$crlf;
            $result .= $tab.'Student_ID='.$_user['user_id'].$crlf;
            $result .= $tab.'Student_Name='.api_get_person_name($_user['firstName'], $_user['lastName']).$crlf;
            $result .= $tab.'Lesson_Location='.$oItem->get_lesson_location().$crlf;
            $result .= $tab.'Credit='.$oItem->get_credit().$crlf;
            $result .= $tab.'Lesson_Status='.$oItem->get_status().$crlf;
            $result .= $tab.'Score='.$oItem->get_score().$crlf;
            $result .= $tab.'Time='.$oItem->get_scorm_time('js').$crlf;
            $result .= $tab.'Lesson_Mode='.$oItem->get_lesson_mode().$crlf;
            $result .= '[Core_Lesson]'.$crlf;
            $result .= $oItem->get_suspend_data().$crlf;
            $result .= '[Core_Vendor]'.$crlf;
            $result .= $oItem->get_launch_data.$crlf;
            $result .= '[Comments]'.$crlf;
            $result .= $crlf;
            $result .= '[Evaluation]'.$crlf;
            $result .= $tab.'Course_ID={'.api_get_course_id().'}'.$crlf;
            //$result .= '[Objectives_Status]'.$crlf;
            $result .= '[Student_Data]'.$crlf;
            $result .= $tab.'Mastery_Score='.$oItem->masteryscore.$crlf;
            //$result .= '[Student_Demographics]'.$crlf;
            //$result .= '[Student_Preferences]'.$crlf;

            //error_log('Returning message: '.$result,0);
            //$result = str_replace($convert_dec, $convert_enc, $result);
            //error_log('Returning message (encoded): '.$result,0);
            break;
        case 'putparam':
            $hacp_version = '';
            $hacp_session_id = '';
            $hacp_aicc_data = '';
            foreach ($_REQUEST as $name => $value) {
                //escape the value as described in the AICC documentation p170
                switch (strtolower($name)) {
                    case 'version':
                        $hacp_version = $value;
                        break;
                    case 'session_id':
                        $hacp_session_id = $value;
                        break;
                    case 'aicc_data':
                        //error_log('In '.__FILE__.', '.__LINE__.' - aicc data before translation is '.$value, 0);
                        $value = str_replace('+', ' ', $value);
                        $value = str_replace($convert_enc, $convert_dec, $value);
                        $hacp_aicc_data = $value;
                        break;
                }
            }
            // Treat the incoming request:
            $aicc = new aicc();
            $msg_array = $aicc->parse_ini_string_quotes_safe($hacp_aicc_data, ['core_lesson', 'core_vendor']);
            foreach ($msg_array as $key => $dummy) {
                switch (strtolower($key)) {
                    case 'core':
                        foreach ($msg_array[$key] as $subkey => $value) {
                            switch (strtolower($subkey)) {
                                case 'lesson_location':
                                    $oItem->set_lesson_location($value);
                                    break;
                                case 'lesson_status':
                                    // Sometimes values are sent abbreviated
                                    switch ($value) {
                                        case 'C':
                                            $value = 'completed';
                                            break;
                                        case 'I':
                                            $value = 'incomplete';
                                            break;
                                        case 'N':
                                        case 'NA':
                                            $value = 'not attempted';
                                            break;
                                        case 'P':
                                            $value = 'passed';
                                            break;
                                        case 'B':
                                            $value = 'browsed';
                                            break;
                                        default:
                                            break;
                                    }
                                    $oItem->set_status($value);
                                    break;
                                case 'score':
                                    $oItem->set_score($value);
                                    break;
                                case 'time':
                                    if (strpos($value, ':') !== false) {
                                        $oItem->set_time($value, 'scorm');
                                    } else {
                                        $oItem->set_time($value);
                                    }
                                    break;
                            }
                        }
                        break;
                    case 'core_lesson':
                        $oItem->current_data = $msg_array[$key];
                        break;
                    case 'comments':
                        break;
                    case 'objectives_status':
                        break;
                    case 'student_data':
                        break;
                    case 'student_preferences':
                        break;
                }
            }

            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            $oItem->save(false);
            break;
        case 'putcomments':
            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            break;
        case 'putobjectives':
            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            break;
        case 'putpath':
            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            break;
        case 'putinteractions':
            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            break;
        case 'putperformance':
            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            break;
        case 'exitau':
            $error_code = 0;
            $error_text = $errors[$error_code];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf.$s_ad.$crlf;
            break;
        default:
            $error_code = 1;
            $error_text = $errors[1];
            $result = $s_ec.$error_code.$crlf.$s_et.$error_text.$crlf;
    }
}

Session::write('lpobject', serialize($oLP));
Session::write('oLP', $oLP);
session_write_close();
// Content type must be text/plain.
header('Content-type: text/plain');
echo $result;
