<?php
/* For license terms, see /license.txt */

declare(strict_types=1);

use ChamiloSession as Session;

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/../../src/LtiProvider.php';
require_once __DIR__.'/../../LtiProviderPlugin.php';

header('Content-Type: application/json');

try {
    $launchId = $_REQUEST['lti_launch_id'] ?? $_REQUEST['launch_id'] ?? null;
    $launchId = is_string($launchId) ? trim($launchId) : '';

    if ('' === $launchId) {
        $ltiSession = Session::read('_ltiProvider');

        if (is_array($ltiSession) && !empty($ltiSession['lti_launch_id'])) {
            $launchId = (string) $ltiSession['lti_launch_id'];
        }
    }

    $toolName = $_REQUEST['lti_tool'] ?? null;
    $toolName = is_string($toolName) ? trim($toolName) : '';

    if ('' === $toolName) {
        $ltiSession = Session::read('_ltiProvider');

        if (is_array($ltiSession) && !empty($ltiSession['tool_name'])) {
            $toolName = (string) $ltiSession['tool_name'];
        }
    }

    $resultId = $_REQUEST['lti_result_id'] ?? null;
    $resultId = is_scalar($resultId) ? (int) $resultId : 0;

    $courseCode = $_REQUEST['cidReq'] ?? null;
    $courseCode = is_string($courseCode) ? trim($courseCode) : '';

    $result = LtiProvider::create()->publishScoreToPlatform(
        $launchId,
        $toolName,
        $resultId,
        $courseCode,
        (int) api_get_user_id(),
        (int) api_get_session_id()
    );

    echo json_encode([
            'success' => true,
        ] + $result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $exception) {
    error_log('[LtiProvider score] Score request failed. | '.json_encode([
            'message' => $exception->getMessage(),
            'launch_id' => $_REQUEST['lti_launch_id'] ?? $_REQUEST['launch_id'] ?? null,
            'tool' => $_REQUEST['lti_tool'] ?? null,
            'result_id' => $_REQUEST['lti_result_id'] ?? null,
            'course_code' => $_REQUEST['cidReq'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
