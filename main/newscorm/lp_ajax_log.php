<?php
/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This script, in particular, enables the process of SCORM messages logging.
 * It stores the SCORM interaction logs right into a temporary file on disk.
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Name of the language file that needs to be included.
$language_file[] = 'learnpath';

require_once 'back_compat.inc.php';

/**
 * Write a log with the current message
 * @param   string Message
 * @param   int    Debug level (if 0, do not log)
 */
function lp_ajax_log($msg, $level) {
    $debug = 0;
    $return = '';
    if ($debug > 0) {error_log('In log('.$msg.')', 0); }
    if ($level == 0) {
        //error_log('Logging level too low, not writing files in '.__FILE__);
        return $return;
    }
    $msg = str_replace('<br />', "\r\n", $msg);
    $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.session_id().'.'.date('Ymd').'-'.api_get_user_id().'.scorm.log';
    $fh = @fopen($file, 'a');
    @fwrite($fh,'['.date('Y-m-d H:m:s').'] '.$msg."\r\n");
    @fclose($fh);
    return $return;
}

echo lp_ajax_log($_POST['msg'], $_POST['debug']);
