<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionFeedbackReportProcessor;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionFeedbackReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestionFeedbackReport',
    operations: [
        new Get(
            uriTemplate: '/exercise/questions/{questionId}/feedback-reports',
            requirements: ['questionId' => '\d+'],
            openapi: new Operation(
                summary: 'List learner feedback reports for an exercise question',
                parameters: [
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'exerciseId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_QUESTION_MANAGER') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_question_feedback_reports',
            provider: ExerciseQuestionFeedbackReportProvider::class,
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
                'exerciseId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Exercise identifier',
                ),
            ],
        ),
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/question/{questionId}/feedback-report',
            requirements: [
                'exerciseId' => '\d+',
                'attemptId' => '\d+',
                'questionId' => '\d+',
            ],
            openapi: new Operation(
                summary: 'Report a problem in an exercise question',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_question_feedback_report',
            processor: ExerciseQuestionFeedbackReportProcessor::class,
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question_feedback_report:read']],
    denormalizationContext: ['groups' => ['exercise_question_feedback_report:write']],
)]
final class ExerciseQuestionFeedbackReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question_feedback_report:read'])]
    public ?int $questionId = null;

    #[Groups(['exercise_question_feedback_report:write'])]
    public string $feedback = '';

    #[Groups(['exercise_question_feedback_report:read'])]
    public bool $success = false;

    #[Groups(['exercise_question_feedback_report:read'])]
    public string $message = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_feedback_report:read'])]
    public array $reports = [];
}
