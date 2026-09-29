<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookOverviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookOverview',
    operations: [
        new Get(
            uriTemplate: '/gradebook/overview',
            openapi: new Operation(
                summary: 'Gradebook overview for the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_overview',
            provider: GradebookOverviewProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_overview:read']],
)]
final class GradebookOverview
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_overview:read'])]
    public string $id = 'gradebook_overview';

    #[Groups(['gradebook_overview:read'])]
    public bool $hasGradebook = false;

    #[Groups(['gradebook_overview:read'])]
    public bool $canManage = false;

    #[Groups(['gradebook_overview:read'])]
    public bool $canViewAll = false;

    #[Groups(['gradebook_overview:read'])]
    public bool $canUnlock = false;

    #[Groups(['gradebook_overview:read'])]
    public bool $canSyncAchievements = false;

    #[Groups(['gradebook_overview:read'])]
    public ?int $rootCategoryId = null;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_overview:read'])]
    public ?array $currentCategory = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_overview:read'])]
    public array $categoryTrail = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_overview:read'])]
    public array $categoryOptions = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_overview:read'])]
    public array $items = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_overview:read'])]
    public ?array $scoreSummary = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_overview:read'])]
    public array $settings = [];

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_overview:read'])]
    public array $context = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['gradebook_overview:read'])]
    public array $controlledFallbacks = [];

    #[Groups(['gradebook_overview:read'])]
    public string $csrfToken = '';

    #[Groups(['gradebook_overview:read'])]
    public string $evaluationCsrfToken = '';

    #[Groups(['gradebook_overview:read'])]
    public string $linkCsrfToken = '';

    #[Groups(['gradebook_overview:read'])]
    public string $achievementCsrfToken = '';

    #[Groups(['gradebook_overview:read'])]
    public int $totalItems = 0;

    public function getId(): string
    {
        return $this->id;
    }
}
