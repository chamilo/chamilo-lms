<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyQuestionProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyQuestionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyQuestion',
    operations: [
        new Get(
            uriTemplate: '/survey/questions/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Survey questions for edition',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_questions',
            provider: SurveyQuestionProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/questions/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Create a survey question',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_question',
            processor: SurveyQuestionProcessor::class,
        ),
        new Put(
            uriTemplate: '/survey/questions/{surveyId}/{questionId}',
            requirements: ['surveyId' => '\d+', 'questionId' => '\d+'],
            openapi: new Operation(
                summary: 'Update a survey question',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'put_survey_question',
            processor: SurveyQuestionProcessor::class,
        ),
        new Delete(
            uriTemplate: '/survey/questions/{surveyId}/{questionId}',
            requirements: ['surveyId' => '\d+', 'questionId' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a survey question',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'delete_survey_question',
            processor: SurveyQuestionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/questions/{surveyId}/{questionId}/move',
            requirements: ['surveyId' => '\d+', 'questionId' => '\d+'],
            openapi: new Operation(
                summary: 'Move a survey question',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_question_move',
            processor: SurveyQuestionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/questions/{surveyId}/{questionId}/copy',
            requirements: ['surveyId' => '\d+', 'questionId' => '\d+'],
            openapi: new Operation(
                summary: 'Copy a survey question',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_question_copy',
            processor: SurveyQuestionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_question:read']],
    denormalizationContext: ['groups' => ['survey_question:write']],
)]
final class SurveyQuestion
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_question:read', 'survey_question:write'])]
    public ?int $surveyId = null;

    #[ApiProperty(identifier: false)]
    #[Groups(['survey_question:read', 'survey_question:write'])]
    public ?int $questionId = null;

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public string $question = '';

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public string $comment = '';

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public string $type = 'open';

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public string $display = 'vertical';

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public bool $isRequired = false;

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public ?int $maxValue = null;

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public ?int $parentQuestionId = null;

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public ?int $parentOptionId = null;

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public string $direction = '';

    #[Groups(['survey_question:read', 'survey_question:write'])]
    public string $csrfToken = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_question:read', 'survey_question:write'])]
    public array $options = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_question:read'])]
    public array $questions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_question:read'])]
    public array $survey = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_question:read'])]
    public array $settings = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    #[Groups(['survey_question:read'])]
    public array $choices = [];

    #[Groups(['survey_question:read'])]
    public bool $canEdit = false;

    #[Groups(['survey_question:read'])]
    public bool $hasAnswers = false;

    public function getSurveyId(): ?int
    {
        return $this->surveyId;
    }

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }
}
