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
use Chamilo\CoreBundle\State\Exercise\ExerciseListProvider;
use Chamilo\CoreBundle\State\Exercise\ExerciseListActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseList',
    operations: [
        new Get(
            uriTemplate: '/exercise/list',
            openapi: new Operation(
                summary: 'Exercise list for a course',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_list',
            provider: ExerciseListProvider::class,
        ),
        new Post(
            uriTemplate: '/exercise/list/action',
            openapi: new Operation(
                summary: 'Run an exercise list action',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_list_action',
            processor: ExerciseListActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_list:read']],
    denormalizationContext: ['groups' => ['exercise_list:write']],
)]
final class ExerciseList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_list:read'])]
    public string $id = 'exercise_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_list:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_list:read'])]
    public array $categories = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_list:read'])]
    public array $settings = [];

    #[Groups(['exercise_list:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_list:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_list:read'])]
    public bool $canCreate = false;

    #[Groups(['exercise_list:read'])]
    public bool $usesLegacyActions = false;

    #[Groups(['exercise_list:read', 'exercise_list:write'])]
    public int $exerciseId = 0;

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_list:read', 'exercise_list:write'])]
    public array $exerciseIds = [];

    #[Groups(['exercise_list:read', 'exercise_list:write'])]
    public string $action = '';

    #[Groups(['exercise_list:read'])]
    public bool $success = false;

    #[Groups(['exercise_list:read'])]
    public string $message = '';

    #[Groups(['exercise_list:read'])]
    public int $processedCount = 0;

    #[Groups(['exercise_list:read'])]
    public int $skippedCount = 0;

    #[Groups(['exercise_list:read', 'exercise_list:write'])]
    public string $submittedCsrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
