<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\SupportBot;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\SupportBot\SupportBotConversationDetailProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SupportBotConversationDetail',
    operations: [
        new Get(
            uriTemplate: '/support-bot/trees/{treeId}/conversations/{conversationId}',
            requirements: ['treeId' => '\d+', 'conversationId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_support_bot_conversation_detail',
            provider: SupportBotConversationDetailProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_conversation_detail:read']],
)]
final class SupportBotConversationDetail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_conversation_detail:read'])]
    public ?int $conversationId = null;

    #[Groups(['support_bot_conversation_detail:read'])]
    public ?int $treeId = null;

    #[Groups(['support_bot_conversation_detail:read'])]
    public string $treeTitle = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['support_bot_conversation_detail:read'])]
    public array $user = [];

    #[Groups(['support_bot_conversation_detail:read'])]
    public string $outcome = '';

    #[Groups(['support_bot_conversation_detail:read'])]
    public int $treeVersion = 1;

    #[Groups(['support_bot_conversation_detail:read'])]
    public string $startedAt = '';

    #[Groups(['support_bot_conversation_detail:read'])]
    public ?string $endedAt = null;

    #[Groups(['support_bot_conversation_detail:read'])]
    public ?int $ticketId = null;

    #[Groups(['support_bot_conversation_detail:read'])]
    public ?string $ticketUrl = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['support_bot_conversation_detail:read'])]
    public array $steps = [];

    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }
}
