<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeReportEmailProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeReportEmail',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempts/email',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Send reviewed exercise attempt result emails from the migrated report',
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
            name: 'post_exercise_runtime_report_email',
            processor: ExerciseRuntimeReportEmailProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_report_email:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_report_email:write']],
)]
final class ExerciseRuntimeReportEmail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_report_email:read', 'exercise_runtime_report_email:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_runtime_report_email:write'])]
    public string $node = '';

    #[Groups(['exercise_runtime_report_email:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['exercise_runtime_report_email:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_report_email:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_report_email:read'])]
    public int $totalCount = 0;

    #[Groups(['exercise_runtime_report_email:read'])]
    public int $sentCount = 0;

    #[Groups(['exercise_runtime_report_email:read'])]
    public int $skippedCount = 0;

    #[Groups(['exercise_runtime_report_email:read'])]
    public int $failedCount = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime_report_email:read'])]
    public array $failures = [];

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
