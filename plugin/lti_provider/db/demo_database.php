<?php
/* For license terms, see /license.txt */

require_once __DIR__ . '/../vendor/autoload.php';
use \IMSGlobal\LTI;

class Demo_Database implements LTI\Database
{
    public function find_registration_by_issuer($iss) {

        $lti_customers = $this->get_lti_connection();
        if (empty($lti_customers[$iss])) {
            return false;
        }

        return LTI\LTI_Registration::new()
            ->set_auth_login_url($lti_customers[$iss]['auth_login_url'])
            ->set_auth_token_url($lti_customers[$iss]['auth_token_url'])
            ->set_auth_server($lti_customers[$iss]['auth_token_url'])
            ->set_client_id($lti_customers[$iss]['client_id'])
            ->set_key_set_url($lti_customers[$iss]['key_set_url'])
            ->set_kid($lti_customers[$iss]['kid'])
            ->set_issuer($iss)
            ->set_tool_private_key(file_get_contents($lti_customers[$iss]['private_key_file']));
    }

    public function find_deployment($iss, $deployment_id) {
        if (!in_array($deployment_id, $_SESSION['iss'][$iss]['deployment'])) {
            return false;
        }
        return LTI\LTI_Deployment::new()
            ->set_deployment_id($deployment_id);
    }

    private function get_lti_connection() {

        $keyPath = api_get_path(SYS_PLUGIN_PATH).'/lti_provider/keys/private.key';

        $em = Database::getManager();
        $platforms = $em->getRepository('ChamiloPluginBundle:LtiProvider\Platform')->findAll();

        $lti_customers = [];
        foreach ($platforms as $platform) {
            $issuer = $platform->getIssuer();
            $lti_customers[$issuer] = [
              'client_id' => $platform->getClientId(),
              'auth_login_url' => $platform->getAuthLoginUrl(),
              'auth_token_url' => $platform->getAuthTokenUrl(),
              'key_set_url' => $platform->getKeySetUrl(),
              'private_key_file' => $keyPath,
              'kid' => $platform->getKid(),
              'deployment' => [$platform->getDeploymentId()]
            ];
        }
        $_SESSION['iss'] = [];
        if (!empty($lti_customers)) {
            $_SESSION['iss'] = array_merge($_SESSION['iss'], $lti_customers);
        }
        return $lti_customers;
    }

    private function private_key($iss) {
        return file_get_contents($_SESSION['iss'][$iss]['private_key_file']);
    }
}
