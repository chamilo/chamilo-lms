<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use ChamiloSession as Session;

/**
 * This script contains the server part of the AJAX interaction process.
 * The client part is located in lp_api.php or other APIs.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../../plugin/LtiProvider/src/LtiProvider.php';
require_once __DIR__.'/../../plugin/LtiProvider/LtiProviderPlugin.php';

api_protect_course_script();

$interactions = [];
if (isset($_REQUEST['interact']) && is_array($_REQUEST['interact'])) {
    foreach ($_REQUEST['interact'] as $idx => $interac) {
        $interactions[$idx] = preg_split('/,/', substr($interac, 1, -1));
        if (!isset($interactions[$idx][7])) {
            $interactions[$idx][7] = '';
        }
    }
}

$result = ScormApi::saveItem(
    $_REQUEST['lid'] ?? null,
    $_REQUEST['uid'] ?? null,
    $_REQUEST['vid'] ?? null,
    $_REQUEST['iid'] ?? null,
    $_REQUEST['s'] ?? null,
    $_REQUEST['max'] ?? null,
    $_REQUEST['min'] ?? null,
    $_REQUEST['status'] ?? null,
    $_REQUEST['t'] ?? null,
    $_REQUEST['suspend'] ?? null,
    $_REQUEST['loc'] ?? null,
    $interactions,
    $_REQUEST['core_exit'] ?? '',
    !empty($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null,
    !empty($_REQUEST['course_id']) ? (int) $_REQUEST['course_id'] : null,
    empty($_REQUEST['finish']) ? 0 : 1,
    empty($_REQUEST['userNavigatesAway']) ? 0 : 1,
    empty($_REQUEST['statusSignalReceived']) ? 0 : 1,
    $_REQUEST['switch_next'] ?? 0,
    $_REQUEST['load_nav'] ?? 0
);

echo $result;

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$ltiSession = Session::read('_ltiProvider');
if (!is_array($ltiSession) || ('lp' !== ($ltiSession['tool_name'] ?? ''))) {
    return;
}

$ltiLaunchId = trim((string) ($_REQUEST['lti_launch_id'] ?? $ltiSession['lti_launch_id'] ?? ''));
if ('' === $ltiLaunchId) {
    return;
}

$lpId = (int) ($_REQUEST['lid'] ?? $_REQUEST['lp_id'] ?? 0);
if ($lpId <= 0) {
    return;
}

$userId = (int) ($ltiSession['user_id'] ?? api_get_user_id());
if ($userId <= 0) {
    return;
}

$courseCode = trim((string) ($ltiSession['course_code'] ?? ''));
$courseId = $courseCode !== ''
    ? (int) api_get_course_int_id($courseCode)
    : (int) ($_REQUEST['course_id'] ?? api_get_course_int_id());

if ($courseId <= 0) {
    return;
}

if ('' === $courseCode) {
    $courseInfo = api_get_course_info_by_id($courseId);
    $courseCode = trim((string) ($courseInfo['code'] ?? ''));
}

if ('' === $courseCode) {
    return;
}

$sessionId = !empty($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : (int) api_get_session_id();
$status = (string) ($_REQUEST['status'] ?? '');
$finish = !empty($_REQUEST['finish']);

try {
    $provider = LtiProvider::create();

    if (!$provider->shouldPublishLpProgress(
        $ltiLaunchId,
        $lpId,
        $courseId,
        $userId,
        $sessionId,
        $status,
        $finish,
        5
    )) {
        return;
    }

    $provider->publishScoreToPlatform(
        $ltiLaunchId,
        'lp',
        $lpId,
        $courseCode,
        $userId,
        $sessionId
    );
} catch (Throwable) {
    // Do not break LP navigation if score sync fails.
}
