<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Push;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class MobilePushDelivery
{
    public function __construct(
        public bool $delivered,
        public bool $invalidToken = false,
    ) {}
}
