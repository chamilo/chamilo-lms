<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\SupportBot;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\SupportBot\SupportBotRuntimeContextProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SupportBotRuntimeContext',
    operations: [
        new Get(
            uriTemplate: '/support-bot/runtime',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_support_bot_runtime_context',
            provider: SupportBotRuntimeContextProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_runtime_context:read']],
)]
final class SupportBotRuntimeContext
{
    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_runtime_context:read'])]
    public string $id = 'support_bot_runtime';

    #[Groups(['support_bot_runtime_context:read'])]
    public bool $enabled = false;

    #[Groups(['support_bot_runtime_context:read'])]
    public string $title = '';

    public function getId(): string
    {
        return $this->id;
    }
}
