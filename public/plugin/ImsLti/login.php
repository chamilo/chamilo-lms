<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

$em = Database::getManager();

/** @var ImsLtiTool $tool */
$tool = isset($_GET['id'])
    ? $em->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', $_GET['id'])
    : null;

if (!$tool) {
    api_not_allowed(true);
}

$user = api_get_user_entity(api_get_user_id());

ChamiloSession::write('lti_tool_login', $tool->getId());

$params = [
    'iss' => ImsLtiPlugin::getIssuerUrl(),
    'target_link_uri' => $tool->getLaunchUrl(),
    'login_hint' => ImsLtiPlugin::getLaunchUserIdClaim($tool, $user),
    'lti_message_hint' => $tool->getId(),
    'client_id' => $tool->getClientId(),
];
?>
<!DOCTYPE html>
<body>
<form action="<?php echo $tool->getLoginUrl(); ?>" method="post" name="lti_1p3_login" id="lti_1p3_login"
      enctype="application/x-www-form-urlencoded" class="form-horizontal">
    <?php foreach ($params as $name => $value) { ?>
        <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">
    <?php } ?>
</form>

<script>document.lti_1p3_login.submit();</script>
</body>
