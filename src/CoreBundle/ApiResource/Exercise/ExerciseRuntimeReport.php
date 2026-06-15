<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeReport',
    operations: [
        new Get(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempts',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise learner attempts report',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'firstName', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lastName', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'status', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_runtime_report',
            provider: ExerciseRuntimeReportProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_report:read']],
)]
final class ExerciseRuntimeReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_report:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_runtime_report:read'])]
    public string $title = '';

    #[Groups(['exercise_runtime_report:read'])]
    public string $description = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime_report:read'])]
    public array $attempts = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime_report:read'])]
    public array $filters = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_runtime_report:read'])]
    public array $actionUrls = [];

    #[Groups(['exercise_runtime_report:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_runtime_report:read'])]
    public bool $canManage = true;

    #[Groups(['exercise_runtime_report:read'])]
    public bool $lockedByGradebook = false;

    #[Groups(['exercise_runtime_report:read'])]
    public bool $canBulkDelete = false;

    #[Groups(['exercise_runtime_report:read'])]
    public bool $canCleanResults = false;

    #[Groups(['exercise_runtime_report:read'])]
    public bool $canBulkRecalculate = false;

    #[Groups(['exercise_runtime_report:read'])]
    public bool $showOfficialCode = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime_report:read'])]
    public array $extraFields = [];

    #[Groups(['exercise_runtime_report:read'])]
    public string $bulkActionToken = '';

    #[Groups(['exercise_runtime_report:read'])]
    public string $emailActionToken = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
