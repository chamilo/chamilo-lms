<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Security\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

readonly class OAuth2Badge implements BadgeInterface
{
    public function __construct(private string $authentication)
    {}

    public function getAuthentication(): string
    {
        return $this->authentication;
    }

    /**
     * @inheritDoc
     */
    public function isResolved(): bool
    {
        return true;
    }
}
