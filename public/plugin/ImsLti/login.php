<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\LtiBundle\Entity\ExternalTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$em = Container::getEntityManager();

/** @var ExternalTool|null $tool */
$tool = isset($_GET['id'])
    ? $em->find(ExternalTool::class, (int) $_GET['id'])
    : null;

if (!$tool) {
    api_not_allowed(true);
}

$user = api_get_user_entity(api_get_user_id());

if (!$user) {
    api_not_allowed(true);
}

$courseId = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
$groupId = isset($_GET['gid']) ? (int) $_GET['gid'] : 0;
$gradebook = isset($_GET['gradebook']) ? (int) $_GET['gradebook'] : 0;

/** @var Course|null $course */
$course = $courseId > 0 ? $em->find(Course::class, $courseId) : null;

if (!$course) {
    api_not_allowed(true);
}

$isPlatformAdmin = api_is_platform_admin_by_id($user->getId());
$isSubscribed = CourseManager::is_user_subscribed_in_course($user->getId(), $course->getCode());

if (!$isPlatformAdmin && !$isSubscribed) {
    api_not_allowed(true);
}

ChamiloSession::write('lti_tool_login', $tool->getId());
ChamiloSession::write('ims_lti_launch_context', [
    'cid' => $courseId,
    'sid' => $sessionId,
    'gid' => $groupId,
    'gradebook' => $gradebook,
]);

$params = [
    'iss' => ImsLtiPlugin::getIssuerUrl(),
    'target_link_uri' => $tool->getLaunchUrl(),
    'login_hint' => ImsLtiPlugin::getLaunchUserIdClaim($tool, $user),
    'lti_message_hint' => (string) $tool->getId(),
    'client_id' => $tool->getClientId(),
];
?>
<!DOCTYPE html>
<html>
<body>
<form
    action="<?php echo Security::remove_XSS($tool->getLoginUrl()); ?>"
    method="post"
    name="lti_1p3_login"
    id="lti_1p3_login"
    enctype="application/x-www-form-urlencoded"
>
    <?php foreach ($params as $name => $value) { ?>
        <input
            type="hidden"
            name="<?php echo Security::remove_XSS($name); ?>"
            value="<?php echo Security::remove_XSS($value); ?>"
        >
    <?php } ?>
</form>

<script>
    document.getElementById('lti_1p3_login').submit();
</script>
</body>
</html>
