<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestionAction',
    operations: [
        new Post(
            uriTemplate: '/exercise/questions/{exerciseId}/action',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Run an exercise question action',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_question_action',
            processor: ExerciseQuestionActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question_action:read']],
    denormalizationContext: ['groups' => ['exercise_question_action:write']],
)]
final class ExerciseQuestionAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question_action:read', 'exercise_question_action:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_question_action:read', 'exercise_question_action:write'])]
    public string $action = '';

    #[Groups(['exercise_question_action:read', 'exercise_question_action:write'])]
    public ?int $questionId = null;

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_question_action:read', 'exercise_question_action:write'])]
    public array $questionIds = [];

    #[Groups(['exercise_question_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['exercise_question_action:read'])]
    public bool $success = false;

    #[Groups(['exercise_question_action:read'])]
    public string $message = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
