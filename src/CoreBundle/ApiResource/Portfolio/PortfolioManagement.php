<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Portfolio\PortfolioManagementProcessor;
use Chamilo\CoreBundle\State\Portfolio\PortfolioManagementProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioManagement',
    operations: [
        new Get(
            uriTemplate: '/portfolio/management',
            openapi: new Operation(summary: 'Read Portfolio category and tag management data'),
            security: "is_granted('ROLE_USER')",
            name: 'get_portfolio_management',
            provider: PortfolioManagementProvider::class,
        ),
        new Post(
            uriTemplate: '/portfolio/management/action',
            openapi: new Operation(summary: 'Manage Portfolio categories or tags'),
            security: "is_granted('ROLE_USER')",
            read: false,
            name: 'post_portfolio_management_action',
            processor: PortfolioManagementProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_management:read']],
    denormalizationContext: ['groups' => ['portfolio_management:write']],
)]
final class PortfolioManagement
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_management:read'])]
    public string $id = 'portfolio_management';

    #[Groups(['portfolio_management:write'])]
    public string $action = '';

    #[Groups(['portfolio_management:write'])]
    public string $csrfToken = '';

    #[Groups(['portfolio_management:write'])]
    public ?int $entityId = null;

    #[Groups(['portfolio_management:write'])]
    public ?int $parentId = null;

    #[Groups(['portfolio_management:write'])]
    public string $title = '';

    #[Groups(['portfolio_management:write'])]
    public string $description = '';

    #[Groups(['portfolio_management:write'])]
    public bool $visible = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_management:read'])]
    public array $categories = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_management:read'])]
    public array $tags = [];

    #[Groups(['portfolio_management:read'])]
    public bool $canManageCategories = false;

    #[Groups(['portfolio_management:read'])]
    public bool $canManageTags = false;

    #[Groups(['portfolio_management:read'])]
    public string $csrfTokenValue = '';

    #[Groups(['portfolio_management:read'])]
    public ?int $affectedId = null;

    public function getId(): string
    {
        return $this->id;
    }
}
