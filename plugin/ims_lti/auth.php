<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\Platform;
use Firebase\JWT\JWT;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();
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

$webPath = api_get_path(WEB_PATH);
$webPluginPath = api_get_path(WEB_PLUGIN_PATH);

$tool = null;

try {
    if (
        empty($scope) ||
        empty($responseType) ||
        empty($clientId) ||
        empty($redirectUri) ||
        empty($loginHint) ||
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

    try {
        /** @var ImsLtiTool $tool */
        $tool = $em
            ->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $ltiToolLogin);
    } catch (\Exception $e) {
        api_not_allowed(true);
    }

    if ($tool->getClientId() != $clientId) {
        throw LtiAuthException::unauthorizedClient();
    }

    $user = api_get_user_entity(api_get_user_id());

    if (ImsLtiPlugin::getLaunchUserIdClaim($tool, $user) != $loginHint) {
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

    $platformDomain = str_replace(['https://', 'http://'], '', api_get_setting('InstitutionUrl'));

    $jwtContent = [];
    $jwtContent['iss'] = ImsLtiPlugin::getIssuerUrl();
    $jwtContent['sub'] = ImsLtiPlugin::getLaunchUserIdClaim($tool, $user);
    $jwtContent['aud'] = $tool->getClientId();
    $jwtContent['iat'] = time();
    $jwtContent['exp'] = time() + 60;
    $jwtContent['nonce'] = $nonce;

    if (empty($nonce)) {
        $jwtContent['nonce'] = md5(microtime().mt_rand());
    }

    // User info
    if ($tool->isSharingName()) {
        $jwtContent['name'] = $user->getFullname();
        $jwtContent['given_name'] = $user->getFirstname();
        $jwtContent['family_name'] = $user->getLastname();
    }

    if (DRH === $user->getStatus()) {
        $roleScopeMentor = ImsLtiPlugin::getRoleScopeMentor($user, $tool);

        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/role_scope_mentor'] = $roleScopeMentor;
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
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/deployment_id'] = $tool->getParent()
        ? (string) $tool->getParent()->getId()
        : (string) $tool->getId();

    // Platform info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/tool_platform'] = [
        'guid' => $platformDomain,
        'contact_email' => api_get_setting('emailAdministrator'),
        'name' => api_get_setting('siteName'),
        'family_code' => 'Chamilo LMS',
        'version' => api_get_version(),
        'url' => $webPath,
    ];

    // Launch info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/launch_presentation'] = [
        'locale' => api_get_language_isocode($user->getLanguage()),
        'document_target' => $tool->getDocumentTarget(),
        //'height' => 320,
        //'wdith' => 240,
        //'return_url' => api_get_course_url(),
    ];

    // LTI info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/version'] = '1.3.0';

    // Roles info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/roles'] = ImsLtiPlugin::getRoles($user);

    // Message type info
    $jwtContent['https://purl.imsglobal.org/spec/lti/claim/target_link_uri'] = $tool->getLaunchUrl();

    if ($tool->isActiveDeepLinking()) {
        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/message_type'] = 'LtiDeepLinkingRequest';
        $jwtContent['https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings'] = [
            'accept_types' => ['ltiResourceLink'],
            'accept_media_types' => implode(
                ',',
                ['*/*', ':::asterisk:::/:::asterisk:::']
            ),
            'accept_presentation_document_targets' => ['iframe', 'window'],
            'accept_multiple' => true,
            'auto_create' => true,
            'title' => $tool->getName(),
            'text' => $tool->getDescription(),
            'data' => "tool:{$tool->getId()}",
            'deep_link_return_url' => $webPluginPath.'ims_lti/item_return2.php',
        ];
    } else {
        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/message_type'] = 'LtiResourceLinkRequest';

        // Resource link
        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/resource_link'] = [
            'id' => (string) $tool->getId(),
            'title' => $tool->getName(),
            'description' => $tool->getDescription(),
        ];

        // LIS info
        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/lis'] = [
            'person_sourcedid' => ImsLti::getPersonSourcedId($platformDomain, $user),
            'course_section_sourcedid' => ImsLti::getCourseSectionSourcedId($platformDomain, $course, $session),
        ];

        $advServices = $tool->getAdvantageServices();

        if (!empty($advServices)) {
            if (LtiAssignmentGradesService::AGS_NONE !== $advServices['ags']) {
                $agsClaim = [
                    'scope' => [
                        LtiAssignmentGradesService::SCOPE_LINE_ITEM_READ,
                        LtiAssignmentGradesService::SCOPE_RESULT_READ,
                        LtiAssignmentGradesService::SCOPE_SCORE_WRITE,
                    ],
                ];

                if (LtiAssignmentGradesService::AGS_FULL === $advServices['ags']) {
                    $agsClaim['scope'][] = LtiAssignmentGradesService::SCOPE_LINE_ITEM;
                }

                $agsClaim['lineitems'] = LtiAssignmentGradesService::getLineItemsUrl(
                    $course->getId(),
                    $tool->getId()
                );

                if ($tool->getLineItems()->count() === 1) {
                    $agsClaim['lineitem'] = LtiAssignmentGradesService::getLineItemUrl(
                        $course->getId(),
                        $tool->getLineItems()->first()->getId(),
                        $tool->getId()
                    );
                }

                $jwtContent['https://purl.imsglobal.org/spec/lti-ags/claim/endpoint'] = $agsClaim;
            }

            if (LtiNamesRoleProvisioningService::NRPS_NONE !== $advServices['nrps'] &&
                api_is_allowed_to_edit(false, false, true)
            ) {
                $nrpsClaim = [
                    'context_memberships_url' => LtiNamesRoleProvisioningService::getUrl(
                        $tool->getId(),
                        $course->getId(),
                        $session ? $session->getId() : 0
                    ),
                    'service_versions' => ['2.0'],
                ];

                $jwtContent['https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice'] = $nrpsClaim;
            }
        }
    }

    // Custom params info
    $customParams = $tool->getCustomParamsAsArray();

    if (!empty($customParams)) {
        $jwtContent['https://purl.imsglobal.org/spec/lti/claim/custom'] = ImsLti::substituteVariablesInCustomParams(
            $jwtContent,
            $customParams,
            $user,
            $course,
            $session,
            $platformDomain,
            ImsLti::V_1P3,
            $tool
        );
    }

    array_walk_recursive(
        $jwtContent,
        function (&$value) {
            if (gettype($value) === 'string') {
                $value = preg_replace('/\s+/', ' ', $value);
                $value = trim($value);
            }
        }
    );

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

if (!$tool) {
    exit;
}

$formActionUrl = $tool->isActiveDeepLinking() ? $tool->getRedirectUrl() : $tool->getLaunchUrl();
?>
<!DOCTYPE html>
<html>
<form action="<?php echo $formActionUrl; ?>" name="ltiLaunchForm" method="post">
    <?php foreach ($params as $name => $value) { ?>
    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <?php } ?>
</form>
<script>document.ltiLaunchForm.submit();</script>
</html>
