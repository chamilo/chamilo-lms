<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyMeetingProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyMeetingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyMeeting',
    operations: [
        new Get(
            uriTemplate: '/survey/meeting',
            openapi: new Operation(
                summary: 'Meeting poll creation defaults',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_meeting_create',
            provider: SurveyMeetingProvider::class,
        ),
        new Get(
            uriTemplate: '/survey/meeting/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Meeting poll data',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'invitationCode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'invitationcode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'mode', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_survey_meeting',
            provider: SurveyMeetingProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/meeting',
            openapi: new Operation(
                summary: 'Create a meeting poll',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_meeting',
            processor: SurveyMeetingProcessor::class,
        ),
        new Put(
            uriTemplate: '/survey/meeting/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Update a meeting poll',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'put_survey_meeting',
            processor: SurveyMeetingProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/meeting/{surveyId}/answer',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Submit meeting poll availability',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'invitationCode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'invitationcode', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_survey_meeting_answer',
            processor: SurveyMeetingProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_meeting:read']],
    denormalizationContext: ['groups' => ['survey_meeting:write']],
)]
final class SurveyMeeting
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public ?int $surveyId = null;

    #[Groups(['survey_meeting:read'])]
    public string $mode = 'answer';

    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public string $title = '';

    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public string $description = '';

    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public ?string $availableFrom = null;

    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public ?string $availableUntil = null;

    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public string $surveyLanguage = '';

    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public string $csrfToken = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public array $slots = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['survey_meeting:read', 'survey_meeting:write'])]
    public array $selectedSlots = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_meeting:read'])]
    public array $participants = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_meeting:read'])]
    public array $matrix = [];

    #[Groups(['survey_meeting:read'])]
    public bool $canEdit = false;

    #[Groups(['survey_meeting:read'])]
    public bool $canSubmit = false;

    #[Groups(['survey_meeting:read'])]
    public bool $isAnswered = false;

    #[Groups(['survey_meeting:read'])]
    public bool $isFinished = false;

    #[Groups(['survey_meeting:read'])]
    public string $message = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_meeting:read'])]
    public array $survey = [];
}
