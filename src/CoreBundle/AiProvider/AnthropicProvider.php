<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

final class AnthropicProvider extends ClaudeProvider
{
    protected function getProviderKey(): string
    {
        return 'anthropic';
    }

    protected function getProviderLabel(): string
    {
        return 'Anthropic';
    }
}
