<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/../../../main/inc/global.inc.php';

try {
    api_block_anonymous_users();

    $request = Container::getRequest();
    $user = api_get_user_entity();

    if (null === $user) {
        $response = new JsonResponse(
            ['error' => 'User not found.'],
            Response::HTTP_UNAUTHORIZED
        );
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->send();
        exit;
    }

    if ('POST' !== $request->getMethod()) {
        $response = new JsonResponse(
            ['error' => 'Method not allowed.'],
            Response::HTTP_METHOD_NOT_ALLOWED
        );
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->send();
        exit;
    }

    $launchSessionId = trim((string) $request->query->get('session'));

    if ('' === $launchSessionId) {
        $response = new JsonResponse(
            ['error' => 'Missing launch session identifier.'],
            Response::HTTP_BAD_REQUEST
        );
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->send();
        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    // Only a launch that cmi5/launch.php started for this user gets a token, limited to that launch.
    $launch = $_SESSION['xapi_cmi5_launches'][$launchSessionId] ?? null;

    if (!is_array($launch)
        || (int) ($launch['user_id'] ?? 0) !== (int) $user->getId()
        || (int) ($launch['expires_at'] ?? 0) < time()
    ) {
        $response = new JsonResponse(
            ['error' => 'Unknown launch session.'],
            Response::HTTP_FORBIDDEN
        );
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->send();
        exit;
    }

    $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

    if (!isset($_SESSION['xapi_cmi5_tokens']) || !is_array($_SESSION['xapi_cmi5_tokens'])) {
        $_SESSION['xapi_cmi5_tokens'] = [];
    }

    $_SESSION['xapi_cmi5_tokens'][$launchSessionId] = [
        'token' => $token,
        'user_id' => (int) $user->getId(),
        'course_id' => (int) $request->query->getInt('cid', 0),
        'session_id' => (int) $request->query->getInt('sid', 0),
        'group_id' => (int) $request->query->getInt('gid', 0),
        'agent' => $launch['agent'],
        'activity' => (string) $launch['activity'],
        'registration' => (string) $launch['registration'],
        'created_at' => time(),
        'expires_at' => time() + 3600,
    ];

    $response = new JsonResponse(
        ['auth-token' => $token],
        Response::HTTP_OK
    );
    $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $response->send();
    exit;
} catch (Throwable $exception) {
    $response = new JsonResponse(
        [
            'error' => 'Token generation failed.',
            'message' => $exception->getMessage(),
        ],
        Response::HTTP_INTERNAL_SERVER_ERROR
    );
    $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    $response->send();
    exit;
}
