<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 *
 * @package chamilo.plugin.customcertificate
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = CustomCertificatePlugin::create();
$enable = $plugin->get('enable_plugin_customcertificate') == 'true';

if ($enable === false) {
    api_not_allowed();
}

$action = isset($_GET['a']) ? $_GET['a'] : null;

switch ($action) {
    case 'delete_certificate':
        $table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
        $courseId = isset($_POST['courseId']) ? (int) $_POST['courseId'] : 0;
        $sessionId = isset($_POST['sessionId']) ? (int) $_POST['sessionId'] : 0;
        $accessUrlId = isset($_POST['accessUrlId']) ? (int) $_POST['accessUrlId'] : 1;

        if (!empty($courseId)) {
            $sql = "DELETE FROM $table
                    WHERE
                        c_id = $courseId AND 
                        session_id = $sessionId AND
                        access_url_id = $accessUrlId";
            Database::query($sql);
            echo Display::return_message(
                get_plugin_lang('SuccessDelete', 'CustomCertificatePlugin'),
                'success'
            );
        } else {
            echo Display::return_message(
                get_plugin_lang('ProblemDelete', 'CustomCertificatePlugin'),
                'error',
                false
            );
        }

        break;
}
