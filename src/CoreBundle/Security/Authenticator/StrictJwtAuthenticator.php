<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Authenticator;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lexik's own JWTAuthenticator::supports() only checks that *some* bearer token is
 * present, not that it's actually shaped like a JWT. That's harmless on a firewall
 * where JWT is the only authenticator, but on `api` (which also accepts
 * ExternalApiKeyAuthenticator's bearer tokens on the same firewall) it's a real bug:
 * Symfony's AuthenticatorManager tries every authenticator whose supports() matches,
 * even after an earlier one already authenticated successfully — so Lexik's greedy
 * supports() steals every non-JWT bearer token, fails to parse it, and that failure
 * response wins over the other authenticator's already-successful one.
 *
 * This subclass narrows supports() to also require the JWT's structural shape
 * (three dot-separated segments) — JWT parsing/validation itself is completely
 * untouched. Wired in only for the `api` firewall via its `jwt: authenticator: ...`
 * config (see security.yaml); the `main` firewall keeps Lexik's original.
 *
 * @psalm-suppress UnimplementedInterfaceMethod authenticate() comes from Lexik's
 *                 ForwardCompatAuthenticatorTrait, whose body is built with eval() —
 *                 static analysis cannot see it. Survives --no-cache.
 */
final class StrictJwtAuthenticator extends JWTAuthenticator
{
    public function supports(Request $request): ?bool
    {
        if (false === parent::supports($request)) {
            return false;
        }

        $token = $this->getTokenExtractor()->extract($request);

        return \is_string($token) && 2 === substr_count($token, '.');
    }
}
