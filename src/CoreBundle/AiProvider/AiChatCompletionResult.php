<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class AiChatCompletionResult
{
    public function __construct(
        public readonly string $text,
        public readonly ?string $conversationId = null
    ) {}
}
