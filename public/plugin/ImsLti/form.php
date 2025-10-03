<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();
api_block_anonymous_users(false);

$em = Database::getManager();

/** @var ImsLtiTool $tool */
$tool = isset($_GET['id'])
    ? $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', (int) $_GET['id'])
    : null;

if (!$tool) {
    api_not_allowed(true);
}

$imsLtiPlugin = ImsLtiPlugin::create();
$session = api_get_session_entity();
$course = api_get_course_entity();
$user = api_get_user_entity(api_get_user_id());

$pluginPath = api_get_path(WEB_PLUGIN_PATH).'ims_lti/';
$toolUserId = ImsLtiPlugin::getLaunchUserIdClaim($tool, $user);
$platformDomain = str_replace(['https://', 'http://'], '', api_get_setting('InstitutionUrl'));

$params = [];
$params['lti_version'] = 'LTI-1p0';

if ($tool->isActiveDeepLinking()) {
    $params['lti_message_type'] = 'ContentItemSelectionRequest';
    $params['content_item_return_url'] = $pluginPath.'item_return.php';
    $params['accept_media_types'] = '*/*';
    $params['accept_presentation_document_targets'] = 'iframe,window';
    //$params['accept_unsigned'];
    //$params['accept_multiple'];
    //$params['accept_copy_advice'];
    //$params['auto_create']';
    $params['title'] = $tool->getName();
    $params['text'] = $tool->getDescription();
    $params['data'] = 'tool:'.$tool->getId();
} else {
    $params['lti_message_type'] = 'basic-lti-launch-request';
    $params['resource_link_id'] = $tool->getId();
    $params['resource_link_title'] = $tool->getName();
    $params['resource_link_description'] = $tool->getDescription();

    $toolEval = $tool->getGradebookEval();

    if (!empty($toolEval)) {
        $params['lis_result_sourcedid'] = json_encode(
            ['e' => $toolEval->getId(), 'u' => $user->getId(), 'l' => uniqid(), 'lt' => time()]
        );
        $params['lis_outcome_service_url'] = api_get_path(WEB_PATH).'lti/os';
        $params['lis_person_sourcedid'] = "$platformDomain:$toolUserId";
        $params['lis_course_section_sourcedid'] = ImsLti::getCourseSectionSourcedId($platformDomain, $course, $session);
    }
}

$params['user_id'] = $toolUserId;

if ($tool->isSharingPicture()) {
    $params['user_image'] = UserManager::getUserPicture($user->getId());
}

$params['roles'] = ImsLtiPlugin::getUserRoles($user);

if ($tool->isSharingName()) {
    $params['lis_person_name_given'] = $user->getFirstname();
    $params['lis_person_name_family'] = $user->getLastname();
    $params['lis_person_name_full'] = $user->getFirstname().' '.$user->getLastname();
}

if ($tool->isSharingEmail()) {
    $params['lis_person_contact_email_primary'] = $user->getEmail();
}

if (DRH === $user->getStatus()) {
    $scopeMentor = ImsLtiPlugin::getRoleScopeMentor($user, $tool);

    if (!empty($scopeMentor)) {
        $params['role_scope_mentor'] = $scopeMentor;
    }
}

$params['context_id'] = $course->getId();
$params['context_type'] = 'CourseSection';
$params['context_label'] = $course->getCode();
$params['context_title'] = $course->getTitle();
$params['launch_presentation_locale'] = api_get_language_isocode();
$params['launch_presentation_document_target'] = $tool->getDocumentTarget();
$params['tool_consumer_info_product_family_code'] = 'Chamilo LMS';
$params['tool_consumer_info_version'] = api_get_version();
$params['tool_consumer_instance_guid'] = $platformDomain;
$params['tool_consumer_instance_name'] = api_get_setting('siteName');
$params['tool_consumer_instance_url'] = api_get_path(WEB_PATH);
$params['tool_consumer_instance_contact_email'] = api_get_setting('emailAdministrator');
$params['oauth_callback'] = 'about:blank';

$customParams = $tool->parseCustomParams();
$imsLtiPlugin->trimParams($customParams);

$params += ImsLti::substituteVariablesInCustomParams(
    $params,
    $customParams,
    $user,
    $course,
    $session,
    $platformDomain,
    ImsLti::V_1P1,
    $tool
);

$imsLtiPlugin->trimParams($params);

if (!empty($tool->getConsumerKey()) && !empty($tool->getSharedSecret())) {
    $consumer = new OAuthConsumer(
        $tool->getConsumerKey(),
        $tool->getSharedSecret(),
        null
    );
    $hmacMethod = new OAuthSignatureMethod_HMAC_SHA1();

    $request = OAuthRequest::from_consumer_and_token(
        $consumer,
        '',
        'POST',
        $tool->getLaunchUrl(),
        $params
    );
    $request->sign_request($hmacMethod, $consumer, '');

    $params = $request->get_parameters();
}

$imsLtiPlugin->removeUrlParamsFromLaunchParams($tool, $params);
?>
<!DOCTYPE html>
<html>
<head>
    <title>title</title>
</head>
<body>
<form action="<?php echo $tool->getLaunchUrl(); ?>" name="ltiLaunchForm" method="post"
      encType="application/x-www-form-urlencoded">
    <?php foreach ($params as $key => $value) { ?>
        <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
    <?php } ?>
</form>
<script>document.ltiLaunchForm.submit();</script>
</body>
</html>
