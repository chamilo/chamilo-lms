<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\Platform;
use Firebase\JWT\JWT;

require_once __DIR__.'/../../main/inc/global.inc.php';

//api_protect_course_script(false);
api_block_anonymous_users(false);

$scope = empty($_REQUEST['scope']) ? '' : trim($_REQUEST['scope']);
$responseType = empty($_REQUEST['response_type']) ? '' : trim($_REQUEST['response_type']);
$responseMode = empty($_REQUEST['response_mode']) ? '' : trim($_REQUEST['response_mode']);
$prompt = empty($_REQUEST['prompt']) ? '' : trim($_REQUEST['prompt']);
$clientId = empty($_REQUEST['client_id']) ? '' : trim($_REQUEST['client_id']);
$redirectUri = empty($_REQUEST['redirect_uri']) ? '' : trim($_REQUEST['redirect_uri']);
$state = empty($_REQUEST['state']) ? '' : trim($_REQUEST['state']);
$nonce = empty($_REQUEST['nonce']) ? '' : trim($_REQUEST['nonce']);
$loginHint = empty($_REQUEST['login_hint']) ? '' : trim($_REQUEST['login_hint']);
$ltiMessageHint = empty($_REQUEST['lti_message_hint']) ? '' : trim($_REQUEST['lti_message_hint']);

$em = Database::getManager();

try {
    if (empty($scope) || empty($responseType) || empty($clientId) || empty($redirectUri) || empty($loginHint) ||
        empty($nonce)
    ) {
        throw LtiAuthException::invalidRequest();
    }

    if ($scope !== 'openid') {
        throw LtiAuthException::invalidScope();
    }

    if ($responseType !== 'id_token') {
        throw LtiAuthException::unsupportedResponseType();
    }

    if (empty($responseMode)) {
        throw LtiAuthException::missingResponseMode();
    }

    if ($responseMode !== 'form_post') {
        throw LtiAuthException::invalidRespondeMode();
    }

    if ($prompt !== 'none') {
        throw LtiAuthException::invalidPrompt();
    }

    $ltiToolLogin = ChamiloSession::read('lti_tool_login');

    if ($ltiToolLogin != $ltiMessageHint) {
        throw LtiAuthException::invalidRequest();
    }

    /** @var ImsLtiTool $tool */
    $tool = $em
        ->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $ltiToolLogin);

    if (empty($tool)) {
        throw LtiAuthException::invalidRequest();
    }

    if ($tool->getClientId() != $clientId) {
        throw LtiAuthException::unauthorizedClient();
    }

    $user = api_get_user_entity(api_get_user_id());

    if ($user->getId() != $loginHint) {
        throw LtiAuthException::accessDenied();
    }

    if ($redirectUri !== $tool->getRedirectUrl()) {
        throw LtiAuthException::unregisteredRedirectUri();
    }

    /** @var Platform|null $platform */
    $platform = $em
        ->getRepository('ChamiloPluginBundle:ImsLti\Platform')
        ->findOneBy([]);
    $session = api_get_session_entity(api_get_session_id());
    $course = api_get_course_entity(api_get_course_int_id());

    $toolUserId = ImsLtiPlugin::generateToolUserId($user->getId());
    $platformDomain = str_replace(['https://', 'http://'], '', api_get_setting('InstitutionUrl'));

    $jwtContent = [];
    $jwtContent['iss'] = ImsLtiPlugin::getIssuerUrl();
    $jwtContent['sub'] = (string) $user->getId();
    $jwtContent['aud'] = $tool->getClientId();
    $jwtContent['iat'] = time();
    $jwtContent['exp'] = time() + 60;
    $jwtContent['nonce'] = md5(microtime().mt_rand());

    // User info
    if ($tool->isSharingName()) {
        $jwtContent['name'] = $user->getFullname();
        $jwtContent['given_name'] = $user->getFirstname();
        $jwtContent['family_name'] = $user->getLastname();
    }

    if ($tool->isSharingPicture()) {
        $jwtContent['picture'] = UserManager::getUserPicture($user->getId());
    }

    if ($tool->isSharingEmail()) {
        $jwtContent['email'] = $user->getEmail();
    }

    // Course (context) info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/context'] = [
        'id' => (string) $course->getId(),
        'title' => $course->getTitle(),
        'label' => $course->getCode(),
        'type' => ['http://purl.imsglobal.org/vocab/lis/v2/course#CourseSection'],
    ];

    // Deployment info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/deployment_id'] = $session
        ? "{$session->getId()}-{$course->getId()}-{$tool->getId()}"
        : "{$course->getId()}-{$tool->getId()}";

    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/target_link_uri'] = $tool->getLaunchUrl();

    // Resource link
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/resource_link'] = [
        'id' => (string) $tool->getId(),
        'title' => $tool->getName(),
        'description' => $tool->getDescription(),
    ];

    // Platform info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/tool_platform'] = [
        'guid' => $platformDomain,
        'contact_email' => api_get_setting('emailAdministrator'),
        'name' => api_get_setting('siteName'),
        'family_code' => 'Chamilo LMS',
        'version' => api_get_version(),
    ];

    // Launch info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/launch_presentation'] = [
        'locale' => api_get_language_isocode($user->getLanguage()),
        'document_target' => 'iframe',
        //'height' => 320,
        //'wdith' => 240,
        //'return_url' => api_get_course_url(),
    ];

    // LIS info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/lis'] = [
        'person_sourcedid' => "$platformDomain:$toolUserId",
        'course_offering_sourcedid' => "$platformDomain:{$course->getId()}"
            .($session ? ":{$session->getId()}" : ''),
    ];

    // LTI info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/version'] = '1.3.0';

    // Roles info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/roles'] = ImsLtiPlugin::getRoles($user);

    // Message type info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/message_type'] = 'LtiResourceLinkRequest';

    // Custom params info
    $customParams = $tool->getCustomParamsAsArray();

    if (!empty($customParams)) {
        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/custom'] = $customParams;
    }

    // Sign
    $jwt = JWT::encode(
        $jwtContent,
        $platform->getPrivateKey(),
        'RS256',
        $platform->getKid()
    );

    $params = [
        'id_token' => $jwt,
        'state' => $state,
    ];
} catch (LtiAuthException $authException) {
    $params = [
        'error' => $authException->getType(),
        'error_description' => $authException->getMessage(),
    ];
}
?>
<!DOCTYPE html>
<html>
<form action="<?php echo $tool->getLaunchUrl() ?>" name="ltiLaunchForm" method="post">
    <input type="hidden" name="id_token" value="<?php echo $jwt ?>">
    <input type="hidden" name="state" value="<?php echo $state ?>">
</form>
<script>document.ltiLaunchForm.submit();</script>
</html>
