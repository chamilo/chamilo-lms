<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Firebase\JWT\JWT;

/**
 * Class LtiTokenRequest.
 */
class LtiTokenRequest
{
    /**
     * Validate the request's client assertion. Return the right tool.
     *
     * @param string $clientAssertion
     *
     * @throws Exception
     *
     * @return ImsLtiTool
     */
    public function validateClientAssertion($clientAssertion)
    {
        $parts = explode('.', $clientAssertion);

        if (count($parts) !== 3) {
            throw new Exception('invalid_request');
        }

        $payload = JWT::urlsafeB64Decode($parts[1]);
        $claims = json_decode($payload, true);

        if (empty($claims) || empty($claims['sub'])) {
            throw new Exception('invalid_request');
        }

        /** @var ImsLtiTool $tool */
        $tool = Database::getManager()
            ->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool')
            ->findOneBy(['clientId' => $claims['sub']]);

        if (!$tool || empty($tool->publicKey)) {
            throw new Exception('invalid_client');
        }

        return $tool;
    }

    /**
     * Validate the request' scope. Return the allowed scopes in services.
     *
     * @param string     $scope
     * @param ImsLtiTool $tool
     *
     * @throws Exception
     *
     * @return array
     */
    public function validateScope($scope, ImsLtiTool $tool)
    {
        if (empty($scope)) {
            throw new Exception('invalid_request');
        }

        $services = ImsLti::getAdvantageServices($tool);

        $requested = explode(' ', $scope);
        $allowed = [];

        /** @var LtiAdvantageService $service */
        foreach ($services as $service) {
            $allowed = array_merge($allowed, $service->getAllowedScopes());
        }

        $intersect = array_intersect($requested, $allowed);

        if (empty($intersect)) {
            throw new Exception('invalid_scope');
        }

        return $intersect;
    }
}
