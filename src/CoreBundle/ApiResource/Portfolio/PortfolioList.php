<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Portfolio\PortfolioListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioList',
    operations: [
        new Get(
            uriTemplate: '/portfolio/list',
            openapi: new Operation(
                summary: 'List portfolio items in course or personal context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'user', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'date', in: 'query', required: false, schema: ['type' => 'string', 'format' => 'date']),
                    new Parameter(name: 'text', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'tags', in: 'query', required: false, schema: ['type' => 'array', 'items' => ['type' => 'integer']]),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'subCategoryIds', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(
                        name: 'order',
                        in: 'query',
                        required: false,
                        schema: ['type' => 'string', 'enum' => ['chronological', 'alphabetical']],
                    ),
                    new Parameter(name: 'highlighted', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'get_portfolio_list',
            provider: PortfolioListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_list:read']],
)]
final class PortfolioList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_list:read'])]
    public string $id = 'portfolio_list';

    #[Groups(['portfolio_list:read'])]
    public string $mode = 'personal';

    #[Groups(['portfolio_list:read'])]
    public ?int $courseId = null;

    #[Groups(['portfolio_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['portfolio_list:read'])]
    public int $currentUserId = 0;

    #[Groups(['portfolio_list:read'])]
    public ?int $selectedUserId = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['portfolio_list:read'])]
    public ?array $selectedUser = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_list:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_list:read'])]
    public array $commentMatches = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_list:read'])]
    public array $categories = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_list:read'])]
    public array $tags = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_list:read'])]
    public array $authors = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['portfolio_list:read'])]
    public array $filters = [];

    #[Groups(['portfolio_list:read'])]
    public string $csrfToken = '';

    #[Groups(['portfolio_list:read'])]
    public float $maxScore = 0.0;

    #[Groups(['portfolio_list:read'])]
    public bool $canQualifyItems = false;

    #[Groups(['portfolio_list:read'])]
    public bool $canQualifyComments = false;

    #[Groups(['portfolio_list:read'])]
    public int $totalItems = 0;

    #[Groups(['portfolio_list:read'])]
    public bool $canCreate = false;

    #[Groups(['portfolio_list:read'])]
    public bool $canViewDetails = false;

    #[Groups(['portfolio_list:read'])]
    public bool $canManageCategories = false;

    #[Groups(['portfolio_list:read'])]
    public bool $canManageTags = false;

    #[Groups(['portfolio_list:read'])]
    public bool $advancedSharingEnabled = false;

    #[Groups(['portfolio_list:read'])]
    public bool $showBaseCoursePostsInSessions = false;

    public function getId(): string
    {
        return $this->id;
    }
}
