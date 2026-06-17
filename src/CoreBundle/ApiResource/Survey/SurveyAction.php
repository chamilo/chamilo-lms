<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyAction',
    operations: [
        new Post(
            uriTemplate: '/survey/actions/bulk-delete',
            status: 200,
            openapi: new Operation(
                summary: 'Delete selected surveys from the current course context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_bulk_delete',
            processor: SurveyActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/actions/{surveyId}/duplicate',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Duplicate a survey inside the current course context',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_duplicate',
            processor: SurveyActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/actions/{surveyId}/empty',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Delete survey answers and invitations in the current context',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_empty',
            processor: SurveyActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/actions/{surveyId}/multiplicate',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Generate multiplied survey questions from class and student placeholders',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_multiplicate',
            processor: SurveyActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/actions/{surveyId}/remove-multiplicate',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Remove generated multiplied survey questions',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_remove_multiplicate',
            processor: SurveyActionProcessor::class,
        ),

        new Post(
            uriTemplate: '/survey/actions/{surveyId}/send-to-tutors',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Publish a group survey for the tutors of the linked group',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_send_to_tutors',
            processor: SurveyActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/actions/{surveyId}/delete',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a survey from the current course context',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_action_delete',
            processor: SurveyActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_action:read']],
    denormalizationContext: ['groups' => ['survey_action:write']],
)]
final class SurveyAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_action:read', 'survey_action:write'])]
    public ?int $surveyId = null;

    #[Groups(['survey_action:read'])]
    public bool $success = false;

    #[Groups(['survey_action:read'])]
    public string $message = '';

    #[Groups(['survey_action:read'])]
    public ?int $newSurveyId = null;

    #[Groups(['survey_action:read'])]
    public int $deletedCount = 0;

    /**
     * @var array<int, int>
     */
    #[Groups(['survey_action:write'])]
    public array $surveyIds = [];

    #[Groups(['survey_action:write'])]
    public string $csrfToken = '';

    public function getSurveyId(): ?int
    {
        return $this->surveyId;
    }
}
