<?php

/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

require_once __DIR__.'/../global.inc.php';

$httpRequest = HttpRequest::createFromGlobals();

$action = $httpRequest->query->has('a') ? $httpRequest->query->get('a') : $httpRequest->request->get('a');

TrackingCourseLog::protectIfNotAllowed();

$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();

$httpResponse = HttpResponse::create();

if ($action == 'graph') {
    $content = TrackingCourseLog::returnCourseGraphicalReport($courseInfo, $sessionId);

    $httpResponse->setContent($content);
}

$httpResponse->send();
