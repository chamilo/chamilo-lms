<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\Token;
use Firebase\JWT\JWT;

/**
 * Class LtiTokenRequest.
 */
class LtiTokenRequest
{
    /**
     * @var ImsLtiTool
     */
    private $tool;

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
            throw new Exception();
        }

        $payload = JWT::urlsafeB64Decode($parts[1]);
        $claims = json_decode($payload, true);

        if (empty($claims) || empty($claims['sub'])) {
            throw new Exception();
        }

        $this->tool = Database::getManager()
            ->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool')
            ->findOneBy(['clientId' => $claims['sub']]);

        if (!$this->tool ||
            $this->tool->getVersion() !== ImsLti::V_1P3 ||
            empty($this->tool->publicKey)
        ) {
            throw new Exception();
        }
    }

    /**
     * Validate the request' scope. Return the allowed scopes in services.
     *
     * @param string $scope
     *
     * @throws Exception
     *
     * @return array
     */
    public function validateScope($scope)
    {
        if (empty($scope)) {
            throw new Exception();
        }

        $services = ImsLti::getAdvantageServices($this->tool);

        $requested = explode(' ', $scope);
        $allowed = [];

        /** @var LtiAdvantageService $service */
        foreach ($services as $service) {
            $allowed = array_merge($allowed, $service->getAllowedScopes());
        }

        $intersect = array_intersect($requested, $allowed);

        if (empty($intersect)) {
            throw new Exception();
        }

        return $intersect;
    }

    /**
     * @param $clientAssertion
     *
     * @throws Exception
     *
     * @return object
     */
    public function decodeJwt($clientAssertion)
    {
        return JWT::decode($clientAssertion, $this->tool->publicKey, ['RS256']);
    }

    /**
     * @return Token
     */
    public function generateToken(array $allowedScopes)
    {
        $now = api_get_utc_datetime(null, false, true)->getTimestamp();

        $token = new Token();
        $token
            ->generateHash()
            ->setTool($this->tool)
            ->setScope($allowedScopes)
            ->setCreatedAt($now)
            ->setExpiresAt($now + Token::TOKEN_LIFETIME);

        return $token;
    }
}
