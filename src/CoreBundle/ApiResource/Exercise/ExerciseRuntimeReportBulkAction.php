<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeReportBulkActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeReportBulkAction',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempts/action',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Run a migrated bulk action on exercise report attempts',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_runtime_report_bulk_action',
            processor: ExerciseRuntimeReportBulkActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_report_bulk_action:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_report_bulk_action:write']],
)]
final class ExerciseRuntimeReportBulkAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_report_bulk_action:read', 'exercise_runtime_report_bulk_action:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_runtime_report_bulk_action:read', 'exercise_runtime_report_bulk_action:write'])]
    public string $action = '';

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_report_bulk_action:read', 'exercise_runtime_report_bulk_action:write'])]
    public array $attemptIds = [];

    #[Groups(['exercise_runtime_report_bulk_action:read', 'exercise_runtime_report_bulk_action:write'])]
    public string $beforeDate = '';

    #[Groups(['exercise_runtime_report_bulk_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['exercise_runtime_report_bulk_action:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_report_bulk_action:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_report_bulk_action:read'])]
    public int $processedCount = 0;

    #[Groups(['exercise_runtime_report_bulk_action:read'])]
    public int $failedCount = 0;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
