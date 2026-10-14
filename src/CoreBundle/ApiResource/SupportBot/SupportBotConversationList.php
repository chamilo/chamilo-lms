<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\SupportBot;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\SupportBot\SupportBotConversationListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SupportBotConversationList',
    operations: [
        new Get(
            uriTemplate: '/support-bot/trees/{treeId}/conversations',
            requirements: ['treeId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_support_bot_conversation_list',
            provider: SupportBotConversationListProvider::class,
            parameters: [
                'outcome' => new QueryParameter(schema: ['type' => 'string']),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'endDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_conversation_list:read']],
)]
final class SupportBotConversationList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_conversation_list:read'])]
    public ?int $treeId = null;

    #[Groups(['support_bot_conversation_list:read'])]
    public string $treeTitle = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['support_bot_conversation_list:read'])]
    public array $conversations = [];

    public function getTreeId(): ?int
    {
        return $this->treeId;
    }
}
