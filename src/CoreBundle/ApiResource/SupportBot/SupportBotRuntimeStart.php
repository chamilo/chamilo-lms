<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\SupportBot;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\SupportBot\SupportBotRuntimeProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SupportBotRuntimeStart',
    operations: [
        new Post(
            uriTemplate: '/support-bot/runtime/start',
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: self::OPERATION_START,
            processor: SupportBotRuntimeProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_runtime:read']],
)]
final class SupportBotRuntimeStart
{
    public const string OPERATION_START = 'post_support_bot_runtime_start';

    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_runtime:read'])]
    public ?int $conversationId = null;

    #[Groups(['support_bot_runtime:read'])]
    public ?int $treeId = null;

    #[Groups(['support_bot_runtime:read'])]
    public string $treeTitle = '';

    #[Groups(['support_bot_runtime:read'])]
    public string $outcome = '';

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['support_bot_runtime:read'])]
    public ?array $node = null;

    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }
}
