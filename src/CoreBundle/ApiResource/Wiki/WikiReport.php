<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiReport',
    operations: [
        new Get(
            uriTemplate: '/wiki/report',
            openapi: new Operation(
                summary: 'Read Wiki page lists, search results and statistics',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'report', in: 'query', required: true, schema: ['type' => 'string']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sortBy', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'sortOrder', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'searchContent', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'allVersions', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'categoryIds', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'matchAllCategories', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'target', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'userId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_report',
            provider: WikiReportProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['wiki_report:read']],
)]
final class WikiReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_report:read'])]
    public string $id = 'wiki_report';

    #[Groups(['wiki_report:read'])]
    public int $courseId = 0;

    #[Groups(['wiki_report:read'])]
    public ?int $sessionId = null;

    #[Groups(['wiki_report:read'])]
    public ?int $groupId = null;

    #[Groups(['wiki_report:read'])]
    public int $nodeId = 0;

    #[Groups(['wiki_report:read'])]
    public string $report = 'all';

    #[Groups(['wiki_report:read'])]
    public string $title = '';

    #[Groups(['wiki_report:read'])]
    public bool $canManage = false;

    #[Groups(['wiki_report:read'])]
    public bool $canCreate = false;

    #[Groups(['wiki_report:read'])]
    public bool $studentView = false;

    #[Groups(['wiki_report:read'])]
    public int $page = 1;

    #[Groups(['wiki_report:read'])]
    public int $itemsPerPage = 20;

    #[Groups(['wiki_report:read'])]
    public int $totalItems = 0;

    #[Groups(['wiki_report:read'])]
    public string $sortBy = '';

    #[Groups(['wiki_report:read'])]
    public string $sortOrder = 'asc';

    #[Groups(['wiki_report:read'])]
    public string $search = '';

    #[Groups(['wiki_report:read'])]
    public bool $searchContent = false;

    #[Groups(['wiki_report:read'])]
    public bool $allVersions = false;

    /**
     * @var array<int, int>
     */
    #[Groups(['wiki_report:read'])]
    public array $categoryIds = [];

    #[Groups(['wiki_report:read'])]
    public bool $matchAllCategories = false;

    #[Groups(['wiki_report:read'])]
    public string $targetReflink = '';

    #[Groups(['wiki_report:read'])]
    public string $targetTitle = '';

    #[Groups(['wiki_report:read'])]
    public ?int $userId = null;

    #[Groups(['wiki_report:read'])]
    public string $userName = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['wiki_report:read'])]
    public array $items = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['wiki_report:read'])]
    public array $statistics = [];

    /**
     * @var array<int, array{value:string, label:string}>
     */
    #[Groups(['wiki_report:read'])]
    public array $availableReports = [];

    /**
     * @var array<int, array{id:int, title:string}>
     */
    #[Groups(['wiki_report:read'])]
    public array $categories = [];

    public function getId(): string
    {
        return $this->id;
    }
}
