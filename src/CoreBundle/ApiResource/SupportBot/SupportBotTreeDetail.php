<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\SupportBot;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\SupportBot\SupportBotTreeDetailProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SupportBotTreeDetail',
    operations: [
        new Get(
            uriTemplate: '/support-bot/trees/configuration',
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_support_bot_tree_configuration',
            provider: SupportBotTreeDetailProvider::class,
        ),
        new Get(
            uriTemplate: '/support-bot/trees/{treeId}',
            requirements: ['treeId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_support_bot_tree_detail',
            provider: SupportBotTreeDetailProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['support_bot_tree_detail:read']],
)]
final class SupportBotTreeDetail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['support_bot_tree_detail:read'])]
    public ?int $treeId = null;

    #[Groups(['support_bot_tree_detail:read'])]
    public string $mode = 'create';

    #[Groups(['support_bot_tree_detail:read'])]
    public string $title = '';

    #[Groups(['support_bot_tree_detail:read'])]
    public string $description = '';

    #[Groups(['support_bot_tree_detail:read'])]
    public string $status = 'draft';

    #[Groups(['support_bot_tree_detail:read'])]
    public int $version = 1;

    #[Groups(['support_bot_tree_detail:read'])]
    public ?string $entryNodeRef = null;

    #[Groups(['support_bot_tree_detail:read'])]
    public ?int $defaultTicketProjectId = null;

    #[Groups(['support_bot_tree_detail:read'])]
    public string $scope = 'portal';

    #[Groups(['support_bot_tree_detail:read'])]
    public string $scopeLabel = '';

    #[Groups(['support_bot_tree_detail:read'])]
    public bool $editable = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['support_bot_tree_detail:read'])]
    public array $nodes = [];

    public function getTreeId(): ?int
    {
        return $this->treeId;
    }
}
