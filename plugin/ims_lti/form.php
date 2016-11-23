<?php
/* For license terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Course;

require_once __DIR__.'/../../main/inc/global.inc.php';
require './OAuthSimple.php';

api_protect_course_script();

$toolId = isset($_GET['id']) ? intval($_GET['id']) : 0;

$em = Database::getManager();
/** @var ImsLtiPlugin $imsLtiPlugin */
$imsLtiPlugin = ImsLtiPlugin::create();

/** @var ImsLtiTool $tool */
$tool = ImsLtiTool::fetch($toolId);
/** @var Course $course */
$course = $em->find('ChamiloCoreBundle:Course', api_get_course_int_id());
/** @var User $user */
$user = $em->find('ChamiloUserBundle:User', api_get_user_id());

$siteName = api_get_setting('siteName');
$institution = api_get_setting('Institution');
$toolUserId = "$siteName - $institution - {$user->getId()}";
$toolUserId = api_replace_dangerous_char($toolUserId);

$params = [
    'lti_message_type' => 'basic-lti-launch-request',
    'lti_version' => 'LTI-1p0',

    'resource_link_id' => $tool->getId(),
    'resource_link_title' => $tool->getName(),

    'user_id' => $toolUserId,
    'roles' => api_is_teacher() ? 'Instructor' : 'Student',

    'lis_person_name_given' => $user->getFirstname(),
    'lis_person_name_family' => $user->getLastname(),
    'lis_person_name_full' => $user->getCompleteName(),
    'lis_person_contact_email_primary' => $user->getEmail(),

    'context_id' => $course->getId(),
    'context_label' => $course->getCode(),
    'context_title' => $course->getTitle(),

    'launch_presentation_locale' => api_get_language_isocode(),
    'launch_presentation_document_target' => 'embed',

    'tool_consumer_info_product_family_code' => 'Chamilo LMS',
    'tool_consumer_info_version' => api_get_version(),
    'tool_consumer_instance_guid' => api_get_setting('InstitutionUrl'),
    'tool_consumer_instance_name' => $siteName,
    'tool_consumer_instance_url' => api_get_path(WEB_PATH),
    'tool_consumer_instance_contact_email' => api_get_setting('emailAdministrator'),

    'resource_link_description' => 'A quick revision PowerPoint about the Water cycle. Make sure you\'re clear about it!',
];

$oauth = new OAuthSimple(
    $tool->getConsumerKey(),
    $tool->getSharedSecret()
);
$oauth->setAction('post');
$oauth->setSignatureMethod('HMAC-SHA1');
$oauth->setParameters($params);
$result = $oauth->sign(array(
    'path' => $tool->getLaunchUrl(),
    'parameters' => array(
        'oauth_callback' => 'about:blank'
    )
));
?>
<!DOCTYPE html>
<html>
    <head>
        <title>title</title>
    </head>
    <body>
        <form action="<?php echo $result['signed_url'] ?>" name="ltiLaunchForm" method="post" encType="application/x-www-form-urlencoded">
            <input type="submit" value="Press to continue to external tool"/>
        </form>

        <script language="javascript">
            document.ltiLaunchForm.submit();
        </script>
    </body>
</html>
