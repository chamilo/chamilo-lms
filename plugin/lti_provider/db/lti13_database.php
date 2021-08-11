<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;
use Packback\Lti1p3\Interfaces;
use Packback\Lti1p3\LtiDeployment;
use Packback\Lti1p3\LtiRegistration;

class Lti13Database implements Interfaces\Database
{
    public function findRegistrationByIssuer($iss, $clientId = null)
    {
        $ltiCustomers = $this->getLtiConnection();
        if (empty($ltiCustomers[$iss])) {
            return false;
        }

        if (!isset($clientId)) {
            $clientId = $ltiCustomers[$iss]['client_id'];
        }

        return LtiRegistration::new()
            ->setAuthLoginUrl($ltiCustomers[$iss]['auth_login_url'])
            ->setAuthTokenUrl($ltiCustomers[$iss]['auth_token_url'])
            ->setClientId($clientId)
            ->setKeySetUrl($ltiCustomers[$iss]['key_set_url'])
            ->setKid($ltiCustomers[$iss]['kid'])
            ->setIssuer($iss)
            ->setToolPrivateKey($this->getPrivateKey());
    }

    public function findDeployment($iss, $deploymentId, $clientId = null)
    {
        $issSession = Session::read('iss');
        if (!in_array($deploymentId, $issSession[$iss]['deployment'])) {
            return false;
        }

        return LtiDeployment::new()->setDeploymentId($deploymentId);
    }

    private function getLtiConnection(): array
    {
        $em = Database::getManager();
        $platforms = $em->getRepository('ChamiloPluginBundle:LtiProvider\Platform')->findAll();

        $ltiCustomers = [];
        foreach ($platforms as $platform) {
            $issuer = $platform->getIssuer();
            $ltiCustomers[$issuer] = [
                'client_id' => $platform->getClientId(),
                'auth_login_url' => $platform->getAuthLoginUrl(),
                'auth_token_url' => $platform->getAuthTokenUrl(),
                'key_set_url' => $platform->getKeySetUrl(),
                'kid' => $platform->getKid(),
                'deployment' => [$platform->getDeploymentId()],
            ];
        }
        Session::write('iss', $ltiCustomers);

        return $ltiCustomers;
    }

    private function getPrivateKey()
    {
        $privateKey = '';
        $platformKey = Database::getManager()
            ->getRepository('ChamiloPluginBundle:LtiProvider\PlatformKey')
            ->findOneBy([]);
        if ($platformKey) {
            $privateKey = $platformKey->getPrivateKey();
        }

        return $privateKey;
    }
}
