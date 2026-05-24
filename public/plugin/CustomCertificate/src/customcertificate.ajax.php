<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

$cidReset = true;

require_once __DIR__.'/../config.php';

api_block_anonymous_users();

$plugin = CustomCertificatePlugin::create();

if (!$plugin->isEnabled(true)) {
    api_not_allowed();
}

if (!api_is_platform_admin() && !api_is_teacher()) {
    api_not_allowed();
}

if (!Security::check_token('post')) {
    api_not_allowed();
}

$action = $_GET['a'] ?? null;

switch ($action) {
    case 'delete_certificate':
        $table = Database::get_main_table(CustomCertificatePlugin::TABLE_CUSTOMCERTIFICATE);
        $courseId = isset($_POST['courseId']) ? (int) $_POST['courseId'] : 0;
        $sessionId = isset($_POST['sessionId']) ? (int) $_POST['sessionId'] : 0;
        $accessUrlId = isset($_POST['accessUrlId']) ? (int) $_POST['accessUrlId'] : api_get_current_access_url_id();

        if (!empty($courseId)) {
            Database::delete(
                $table,
                [
                    'c_id = ? AND session_id = ? AND access_url_id = ?' => [
                        $courseId,
                        $sessionId,
                        $accessUrlId,
                    ],
                ]
            );

            echo Display::return_message(
                $plugin->get_lang('SuccessDelete'),
                'success'
            );
        } else {
            echo Display::return_message(
                $plugin->get_lang('ProblemDelete'),
                'error',
                false
            );
        }

        break;
}
