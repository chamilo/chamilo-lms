<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestion',
    operations: [
        new Get(
            uriTemplate: '/exercise/questions/{exerciseId}',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise question type selector and current questions',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_questions',
            provider: ExerciseQuestionProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question:read']],
)]
final class ExerciseQuestion
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_question:read'])]
    public string $title = '';

    #[Groups(['exercise_question:read'])]
    public int $questionCount = 0;

    #[Groups(['exercise_question:read'])]
    public float $totalScore = 0.0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question:read'])]
    public array $questionTypes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question:read'])]
    public array $questions = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_question:read'])]
    public array $legacyUrls = [];

    #[Groups(['exercise_question:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_question:read'])]
    public bool $usesLegacyQuestionEditors = true;

    #[Groups(['exercise_question:read'])]
    public bool $isLinkedToLearningPath = false;

    #[Groups(['exercise_question:read'])]
    public bool $isReadOnlyFromLearningPath = false;

    #[Groups(['exercise_question:read'])]
    public string $learningPathReadOnlyMessage = '';

    #[Groups(['exercise_question:read'])]
    public string $csrfToken = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
