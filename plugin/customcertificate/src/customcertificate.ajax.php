<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 *
 * @package chamilo.plugin.customcertificate
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (api_is_anonymous()) {
    api_not_allowed(true);
}

$plugin = CustomCertificatePlugin::create();
$enable = $plugin->get('enable_plugin_customcertificate') == 'true';
$action = isset($_GET['a']) ? $_GET['a'] : null;

$em = Database::getManager();

switch ($action) {
    case 'delete_certificate':
        if (api_is_anonymous()) {
            break;
        }

        $table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
        $courseId = isset($_POST['courseId']) ? (int) $_POST['courseId'] : 0;
        $sessionId = isset($_POST['sessionId']) ? (int) $_POST['sessionId'] : 0;
        $accessUrlId = isset($_POST['accessUrlId']) ? (int) $_POST['accessUrlId'] : 1;
        if (!empty($courseId)) {
            $sql = "DELETE FROM $table
                    WHERE c_id=$courseId AND session_id=$sessionId AND access_url_id=$accessUrlId";
            $rs = Database::query($sql);
            echo Display::addFlash(
                    Display::return_message(
                        get_plugin_lang("SuccessDelete", "CustomCertificatePlugin"),
                        'success'
                    )
                );
        } else {
            echo Display::addFlash(
                    Display::return_message(
                        get_plugin_lang("ProblemDelete", "CustomCertificatePlugin"),
                        'error',
                        false
                    )
                );
        }

        break;
}
