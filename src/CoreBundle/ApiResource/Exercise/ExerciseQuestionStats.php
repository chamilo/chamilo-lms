<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionStatsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestionStats',
    operations: [
        new Get(
            uriTemplate: '/exercise/runtime/{exerciseId}/question-stats',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise question statistics',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_question_stats',
            provider: ExerciseQuestionStatsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question_stats:read']],
)]
final class ExerciseQuestionStats
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question_stats:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_question_stats:read'])]
    public string $title = '';

    #[Groups(['exercise_question_stats:read'])]
    public string $description = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_stats:read'])]
    public array $questions = [];

    /**
     * @var array<string, int|float>
     */
    #[Groups(['exercise_question_stats:read'])]
    public array $summary = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_question_stats:read'])]
    public array $actionUrls = [];

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
