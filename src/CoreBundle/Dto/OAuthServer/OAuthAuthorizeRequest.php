<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Dto\OAuthServer;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * A validated, session-stashed /oauth/authorize request. Once this exists,
 * client_id and redirect_uri have already been verified against the
 * registered client — the consent POST reads this back from the session
 * only, never re-trusting resubmitted query parameters.
 *
 * Plain value object, always constructed manually — never a service. #[Exclude]
 * keeps the Chamilo\CoreBundle\: services.yml glob from trying to autowire its
 * scalar constructor args.
 */
#[Exclude]
final readonly class OAuthAuthorizeRequest
{
    public function __construct(
        public string $clientId,
        public string $redirectUri,
        public string $state,
        public string $codeChallenge,
        public string $codeChallengeMethod,
        public ?string $resource,
        public int $createdAt,
    ) {}

    public function isStale(int $now, int $ttlSeconds): bool
    {
        return $this->createdAt + $ttlSeconds < $now;
    }
}
