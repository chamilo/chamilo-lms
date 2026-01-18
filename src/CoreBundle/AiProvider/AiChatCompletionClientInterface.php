<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

interface AiChatCompletionClientInterface
{
    /**
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    public function chat(string $provider, array $messages, array $options = []): string;

    /**
     * Same as chat() but may also return a provider conversation id (previous_id, conversation_id, etc).
     *
     * @param array<int, array{role:string,content:string}> $messages
     * @param array<string,mixed>                           $options
     */
    public function chatWithMeta(string $provider, array $messages, array $options = []): AiChatCompletionResult;
}
