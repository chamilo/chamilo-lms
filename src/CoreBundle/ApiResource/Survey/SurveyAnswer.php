<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyAnswerProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyAnswerProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyAnswer',
    operations: [
        new Get(
            uriTemplate: '/survey/answer/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Survey answer or preview data',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'preview', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'invitationCode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'invitationcode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lpItemId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            name: 'get_survey_answer',
            provider: SurveyAnswerProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/answer/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Submit survey answers',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'invitationCode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'invitationcode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lpItemId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            name: 'post_survey_answer',
            processor: SurveyAnswerProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_answer:read']],
    denormalizationContext: ['groups' => ['survey_answer:write']],
)]
final class SurveyAnswer
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_answer:read', 'survey_answer:write'])]
    public ?int $surveyId = null;

    #[Groups(['survey_answer:read', 'survey_answer:write'])]
    public ?string $invitationCode = null;

    #[Groups(['survey_answer:read', 'survey_answer:write'])]
    public string $csrfToken = '';

    #[Groups(['survey_answer:read'])]
    public bool $preview = false;

    #[Groups(['survey_answer:read'])]
    public bool $canSubmit = false;

    #[Groups(['survey_answer:read'])]
    public bool $isAnswered = false;

    #[Groups(['survey_answer:read'])]
    public bool $isFinished = false;

    #[Groups(['survey_answer:read'])]
    public string $message = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_answer:read'])]
    public array $survey = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_answer:read'])]
    public array $questions = [];

    /**
     * @var array<int, array<int, int>>
     */
    #[Groups(['survey_answer:read'])]
    public array $pages = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_answer:read'])]
    public array $answers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_answer:read'])]
    public array $profileFields = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_answer:read'])]
    public array $settings = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_answer:write'])]
    public array $submittedAnswers = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_answer:write'])]
    public array $otherAnswers = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_answer:write'])]
    public array $profileValues = [];

    public function getSurveyId(): ?int
    {
        return $this->surveyId;
    }
}
