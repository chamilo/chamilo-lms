<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Security\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

final class CasBadge implements BadgeInterface
{
    public function isResolved(): bool
    {
        return true;
    }
}
