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
use Chamilo\CoreBundle\State\Survey\SurveyCopyProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyCopyProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyCopy',
    operations: [
        new Get(
            uriTemplate: '/survey/actions/{surveyId}/copy',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Survey copy form data and target course list',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'q', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_copy',
            provider: SurveyCopyProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/actions/{surveyId}/copy',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Copy a survey to a target course or session',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_copy',
            processor: SurveyCopyProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_copy:read']],
    denormalizationContext: ['groups' => ['survey_copy:write']],
)]
final class SurveyCopy
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_copy:read', 'survey_copy:write'])]
    public ?int $surveyId = null;

    #[Groups(['survey_copy:read'])]
    public bool $canCopy = false;

    #[Groups(['survey_copy:read'])]
    public bool $success = false;

    #[Groups(['survey_copy:read'])]
    public string $message = '';

    #[Groups(['survey_copy:read'])]
    public ?int $newSurveyId = null;

    #[Groups(['survey_copy:read', 'survey_copy:write'])]
    public string $csrfToken = '';

    #[Groups(['survey_copy:write'])]
    public ?int $targetCourseId = null;

    #[Groups(['survey_copy:write'])]
    public ?int $targetSessionId = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_copy:read'])]
    public array $survey = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_copy:read'])]
    public array $targets = [];

    public function getSurveyId(): ?int
    {
        return $this->surveyId;
    }
}
