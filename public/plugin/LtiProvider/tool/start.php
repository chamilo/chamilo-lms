<?php
/* For license terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Entity\User;
use Chamilo\LtiBundle\Security\LtiProviderLaunchToken;
use ChamiloSession as Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../src/LtiProvider.php';
require_once __DIR__.'/../LtiProviderPlugin.php';

/* Force iframe-compatible PHP session cookie for LTI launches. */
if (PHP_SESSION_NONE === session_status()) {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'None');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ]);
}

/**
 * Store a value in both Chamilo legacy session and raw PHP session.
 */
function lti_provider_write_session_value(string $key, mixed $value): void
{
    Session::write($key, $value);
    $_SESSION[$key] = $value;
}

/**
 * Get the application secret used to sign the temporary LTI launch token.
 */
function lti_provider_get_signing_secret(): string
{
    $secret = (string) ($_ENV['APP_SECRET'] ?? $_SERVER['APP_SECRET'] ?? '');

    if ('' === trim($secret)) {
        throw new RuntimeException('APP_SECRET is not available for LTI launch token signing.');
    }

    return $secret;
}

/**
 * Resolve the effective Chamilo user after validateUser() completed.
 *
 * Resolution order:
 * 1. Stable LTI username (issuer + subject)
 * 2. Existing user by email
 */
function lti_provider_resolve_user_entity(array $launchData): ?User
{
    $issuer = trim((string) ($launchData['iss'] ?? ''));
    $subject = trim((string) ($launchData['sub'] ?? ''));
    $email = trim((string) ($launchData['email'] ?? ''));
    $authSource = defined('IMS_LTI_SOURCE') ? IMS_LTI_SOURCE : 'lti_provider';

    if ('' === $issuer || '' === $subject) {
        return null;
    }

    $username = md5($issuer.'_'.$subject);

    $userInfo = api_get_user_info_from_username($username, $authSource);
    if (!empty($userInfo['user_id'])) {
        $user = api_get_user_entity((int) $userInfo['user_id']);

        if ($user instanceof User) {
            return $user;
        }
    }

    if ('' !== $email) {
        $userByEmail = Database::getManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($userByEmail instanceof User) {
            return $userByEmail;
        }
    }

    return null;
}

/**
 * Create a Symfony security token and legacy course context in session.
 */
function lti_provider_store_authenticated_user_in_session(User $user, array $courseInfo): void
{
    $securityToken = new UsernamePasswordToken(
        $user,
        'main',
        $user->getRoles()
    );

    lti_provider_write_session_value('_security_main', serialize($securityToken));
    lti_provider_write_session_value('_uid', (int) $user->getId());
    lti_provider_write_session_value('_user', api_get_user_info((int) $user->getId()));
    lti_provider_write_session_value('_cid', (string) $courseInfo['code']);
    lti_provider_write_session_value('_real_cid', (int) $courseInfo['real_id']);
    lti_provider_write_session_value('_course', $courseInfo);
    lti_provider_write_session_value('is_allowed_in_course', true);

    lti_provider_write_session_value('is_platformAdmin', false);
    lti_provider_write_session_value('is_allowedCreateCourse', false);
}

$provider = LtiProvider::create();
$launch = $provider->launch();

$launchData = $launch->getLaunchData();

$issuer = (string) ($launchData['iss'] ?? '');
$subject = (string) ($launchData['sub'] ?? '');
$audience = (string) ($launchData['aud'] ?? '');
$launchId = (string) $launch->getLaunchId();

$plugin = LtiProviderPlugin::create();
$toolVars = $plugin->getToolProviderVars($audience);

$courseCode = (string) ($toolVars['courseCode'] ?? '');
$toolName = (string) ($toolVars['toolName'] ?? '');
$toolId = (string) ($toolVars['toolId'] ?? '');

if ('' === $courseCode || '' === $toolName || '' === $toolId) {
    throw new RuntimeException('The LTI tool provider mapping is invalid.');
}

$courseInfo = api_get_course_info($courseCode);
if (empty($courseInfo) || empty($courseInfo['real_id'])) {
    throw new RuntimeException('The target course could not be resolved.');
}

$login = $provider->validateUser($launchData, $courseCode, $toolName);
if (!$login) {
    throw new RuntimeException('LTI user validation/login failed.');
}

$resolvedUser = lti_provider_resolve_user_entity($launchData);
if (!$resolvedUser instanceof User) {
    throw new RuntimeException('The LTI user could not be resolved after validation.');
}

$provider->logout($toolName);
lti_provider_store_authenticated_user_in_session($resolvedUser, $courseInfo);

$signingSecret = lti_provider_get_signing_secret();
$tokenHelper = new LtiProviderLaunchToken($signingSecret);

$launchToken = $tokenHelper->createToken([
    'user_id' => (int) $resolvedUser->getId(),
    'course_id' => (int) $courseInfo['real_id'],
    'course_code' => (string) $courseCode,
    'tool_name' => (string) $toolName,
    'tool_id' => (string) $toolId,
    'launch_id' => (string) $launchId,
    'exp' => time() + 600,
]);

$values = [
    'issuer' => $issuer,
    'user_id' => (int) $resolvedUser->getId(),
    'client_uid' => $subject,
    'course_code' => $courseCode,
    'tool_id' => $toolId,
    'tool_name' => $toolName,
    'lti_launch_id' => $launchId,
];

$plugin->saveResult($values);

$ltiSession = $values;
$ltiSession['lti_provider_token'] = $launchToken;

$courseId = (int) $courseInfo['real_id'];
$sessionId = 0;

if ('lp' === $toolName) {
    $launchUrl = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.http_build_query(
            [
                'cid' => $courseId,
                'sid' => $sessionId,
                'action' => 'view',
                'lp_id' => $toolId,
                'isStudentView' => 'true',
                'origin' => 'embeddable',
                'lti_launch_id' => $launchId,
                'lti_provider_token' => $launchToken,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );
} else {
    $launchUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.http_build_query(
            [
                'cid' => $courseId,
                'sid' => $sessionId,
                'origin' => 'embeddable',
                'exerciseId' => $toolId,
                'learnpath_id' => 0,
                'learnpath_item_id' => 0,
                'learnpath_item_view_id' => 0,
                'lti_launch_id' => $launchId,
                'lti_provider_token' => $launchToken,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );
}

$ltiSession['launch_url'] = $launchUrl;
Session::write('_ltiProvider', $ltiSession);

header('Location: '.$launchUrl);
exit;
