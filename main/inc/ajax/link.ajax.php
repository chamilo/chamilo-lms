<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

require_once __DIR__.'/../global.inc.php';

api_protect_course_script(true);

$action = $_REQUEST['a'];

switch ($action) {
    case 'check_url':
        if (api_is_allowed_to_edit(null, true)) {
            $url = $_REQUEST['url'];
            // Check if curl is available.
            if (!in_array('curl', get_loaded_extensions())) {
                echo '';
                exit;
            }

            // set URL and other appropriate options
            $defaults = array(
                CURLOPT_URL => $url,
                CURLOPT_FOLLOWLOCATION => true, // follow redirects accept youtube.com
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 4
            );
            // Create a new cURL resource
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);

            // grab URL and pass it to the browser
            ob_start();
            $result = curl_exec($ch);
            ob_get_clean();

            // close cURL resource, and free up system resources
            curl_close($ch);

            if ($result) {
                echo Display::return_icon(
                    'check-circle.png',
                    get_lang('Ok'),
                    null,
                    ICON_SIZE_TINY
                );
            } else {
                echo Display::return_icon(
                    'closed-circle.png',
                    get_lang('Wrong'),
                    null,
                    ICON_SIZE_TINY
                );
            }
        }
        break;
    default:
        echo '';
}
exit;
