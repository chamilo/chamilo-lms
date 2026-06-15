<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseCategoryManagementProcessor;
use Chamilo\CoreBundle\State\Exercise\ExerciseCategoryManagementProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseCategoryManagement',
    operations: [
        new Get(
            uriTemplate: '/exercise/categories/{categoryType}',
            requirements: ['categoryType' => 'exercise|question'],
            openapi: new Operation(
                summary: 'Exercise category management data',
                parameters: [
                    new Parameter(name: 'categoryType', in: 'path', required: true, schema: ['type' => 'string']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_category_management',
            provider: ExerciseCategoryManagementProvider::class,
        ),
        new Post(
            uriTemplate: '/exercise/categories/{categoryType}/action',
            requirements: ['categoryType' => 'exercise|question'],
            openapi: new Operation(
                summary: 'Run an exercise category action',
                parameters: [
                    new Parameter(name: 'categoryType', in: 'path', required: true, schema: ['type' => 'string']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_category_management_action',
            processor: ExerciseCategoryManagementProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_category_management:read']],
    denormalizationContext: ['groups' => ['exercise_category_management:write']],
)]
final class ExerciseCategoryManagement
{
    #[ApiProperty(identifier: false)]
    #[Groups(['exercise_category_management:read'])]
    public string $id = 'exercise_categories';

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_category_management:read', 'exercise_category_management:write'])]
    public string $categoryType = '';

    #[Groups(['exercise_category_management:read'])]
    public string $title = '';

    #[Groups(['exercise_category_management:read', 'exercise_category_management:write'])]
    public string $action = '';

    #[Groups(['exercise_category_management:read', 'exercise_category_management:write'])]
    public ?int $categoryId = null;

    #[Groups(['exercise_category_management:read', 'exercise_category_management:write'])]
    public string $categoryTitle = '';

    #[Groups(['exercise_category_management:read', 'exercise_category_management:write'])]
    public string $description = '';

    #[Groups(['exercise_category_management:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['exercise_category_management:write'])]
    public string $csvContent = '';

    #[Groups(['exercise_category_management:read'])]
    public int $importedCount = 0;

    #[Groups(['exercise_category_management:read'])]
    public int $skippedCount = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_category_management:read'])]
    public array $items = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_category_management:read'])]
    public array $legacyUrls = [];

    #[Groups(['exercise_category_management:read'])]
    public string $csrfToken = '';

    #[Groups(['exercise_category_management:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_category_management:read'])]
    public bool $success = false;

    #[Groups(['exercise_category_management:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }

    public function getCategoryType(): string
    {
        return $this->categoryType;
    }
}
