<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator\Cas;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

use const PHP_QUERY_RFC3986;

final readonly class CasLoginFlow
{
    public const string SESSION_KEY = 'chamilo.cas.pending';
    public const string LOGOUT_KEY = 'chamilo.cas.session';
    private const int TTL = 300;

    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function start(Request $request, AccessUrl $portal, array $config): string
    {
        $state = bin2hex(random_bytes(32));
        $service = $request->getSchemeAndHttpHost().$this->router->generate('chamilo.cas_check', ['state' => $state]);
        $session = $request->getSession();
        $session->set(self::SESSION_KEY, [
            'state' => $state,
            'service' => $service,
            'portal' => $portal->getId(),
            'configuration' => hash('sha256', serialize($config)),
            'created' => time(),
        ]);

        return $config['base'].'/login?'.http_build_query(['service' => $service], '', '&', PHP_QUERY_RFC3986);
    }

    public function consume(Request $request, AccessUrl $portal, array $config): string
    {
        if (!$request->hasSession()) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }
        // Consume before talking to CAS; never allow a callback to replay a login.
        $pending = $request->getSession()->remove(self::SESSION_KEY);
        $query = $request->query->all();
        $state = $query['state'] ?? null;
        if (!\is_array($pending) || !\is_string($state) || !hash_equals($pending['state'], $state)
            || $pending['portal'] !== $portal->getId() || time() > $pending['created'] + self::TTL
            || !hash_equals($pending['configuration'], hash('sha256', serialize($config)))
        ) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        // Exact same service value used at initiation, including the one-time state.
        $expected = $request->getSchemeAndHttpHost().$this->router->generate('chamilo.cas_check', ['state' => $state]);
        if (!hash_equals($pending['service'], $expected)) {
            throw new CustomUserMessageAuthenticationException('Authentication failed.');
        }

        return $pending['service'];
    }
}
