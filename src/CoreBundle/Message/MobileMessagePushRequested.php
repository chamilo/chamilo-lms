<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Message;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class MobileMessagePushRequested
{
    public function __construct(
        public int $messageId,
        public int $accessUrlId,
    ) {}
}
