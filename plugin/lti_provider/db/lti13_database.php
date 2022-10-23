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
        if (!isset($clientId)) {
            $clientId = $this->getClientIdByIssuer($iss);
        }

        $ltiCustomers = $this->getLtiConnection();
        if (empty($ltiCustomers[$clientId])) {
            return false;
        }

        return LtiRegistration::new()
            ->setAuthLoginUrl($ltiCustomers[$clientId]['auth_login_url'])
            ->setAuthTokenUrl($ltiCustomers[$clientId]['auth_token_url'])
            ->setClientId($clientId)
            ->setKeySetUrl($ltiCustomers[$clientId]['key_set_url'])
            ->setKid($ltiCustomers[$clientId]['kid'])
            ->setIssuer($iss)
            ->setToolPrivateKey($this->getPrivateKey());
    }

    public function findDeployment($iss, $deploymentId, $clientId = null)
    {
        $issSession = Session::read('iss');
        if (!in_array($deploymentId, $issSession[$clientId]['deployment'])) {
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
            $clientId = $platform->getClientId();
            $ltiCustomers[$clientId] = [
                'client_id' => $clientId,
                'issuer' => $platform->getIssuer(),
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

    private function getClientIdByIssuer($issuer)
    {
        $clientId = '';
        $platform = Database::getManager()
            ->getRepository('ChamiloPluginBundle:LtiProvider\Platform')
            ->findOneBy(['issuer' => $issuer]);

        if ($platform) {
            $clientId = $platform->getClientId();
        }

        return $clientId;
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
