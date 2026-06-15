<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeAttemptEmailProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeAttemptEmail',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/email',
            requirements: [
                'exerciseId' => '\\d+',
                'attemptId' => '\\d+',
            ],
            openapi: new Operation(
                summary: 'Send one exercise attempt result email from the migrated report',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_runtime_attempt_email',
            processor: ExerciseRuntimeAttemptEmailProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_attempt_email:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_attempt_email:write']],
)]
final class ExerciseRuntimeAttemptEmail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_attempt_email:read', 'exercise_runtime_attempt_email:write'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_attempt_email:read', 'exercise_runtime_attempt_email:write'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_attempt_email:write'])]
    public ?string $node = null;

    #[Groups(['exercise_runtime_attempt_email:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_attempt_email:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_attempt_email:read'])]
    public ?int $recipientId = null;

    #[Groups(['exercise_runtime_attempt_email:read'])]
    public string $recipientName = '';

    #[Groups(['exercise_runtime_attempt_email:read'])]
    public string $recipientEmail = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
