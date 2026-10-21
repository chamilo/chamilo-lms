<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\SupportBot;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\SupportBot\SupportBotTreeListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SupportBotTreeList',
    operations: [
        new Get(
            uriTemplate: '/support-bot/trees',
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_support_bot_tree_list',
            provider: SupportBotTreeListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_tree_list:read']],
)]
final class SupportBotTreeList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_tree_list:read'])]
    public string $id = 'support_bot_tree_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['support_bot_tree_list:read'])]
    public array $trees = [];

    public function getId(): string
    {
        return $this->id;
    }
}
