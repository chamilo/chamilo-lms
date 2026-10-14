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
    shortName: 'SupportBotRuntimeAdvance',
    operations: [
        new Post(
            uriTemplate: '/support-bot/runtime/{conversationId}/advance',
            requirements: ['conversationId' => '\d+'],
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: self::OPERATION_ADVANCE,
            processor: SupportBotRuntimeProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_runtime:read']],
    denormalizationContext: ['groups' => ['support_bot_runtime:write']],
)]
final class SupportBotRuntimeAdvance
{
    public const string OPERATION_ADVANCE = 'post_support_bot_runtime_advance';

    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_runtime:read'])]
    public ?int $conversationId = null;

    #[Groups(['support_bot_runtime:write'])]
    public ?int $optionId = null;

    #[Groups(['support_bot_runtime:read'])]
    public string $outcome = '';

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['support_bot_runtime:read'])]
    public ?array $node = null;

    #[Groups(['support_bot_runtime:read'])]
    public string $action = '';

    #[Groups(['support_bot_runtime:read'])]
    public ?string $targetUrl = null;

    #[Groups(['support_bot_runtime:read'])]
    public ?string $closingMessage = null;

    #[Groups(['support_bot_runtime:read'])]
    public ?int $ticketId = null;

    #[Groups(['support_bot_runtime:read'])]
    public ?string $ticketUrl = null;

    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }
}
