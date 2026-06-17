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
use Chamilo\CoreBundle\State\Survey\SurveyConfigurationProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyConfigurationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyConfiguration',
    operations: [
        new Get(
            uriTemplate: '/survey/configuration',
            openapi: new Operation(
                summary: 'Survey configuration defaults for creation',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_configuration_create',
            provider: SurveyConfigurationProvider::class,
        ),
        new Get(
            uriTemplate: '/survey/configuration/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Survey configuration for edition',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_configuration_edit',
            provider: SurveyConfigurationProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/configuration',
            openapi: new Operation(
                summary: 'Create a survey configuration',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_configuration',
            processor: SurveyConfigurationProcessor::class,
        ),
        new Put(
            uriTemplate: '/survey/configuration/{surveyId}',
            requirements: ['surveyId' => '\d+'],
            openapi: new Operation(
                summary: 'Update a survey configuration',
                parameters: [
                    new Parameter(name: 'surveyId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'put_survey_configuration',
            processor: SurveyConfigurationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_configuration:read']],
    denormalizationContext: ['groups' => ['survey_configuration:write']],
)]
final class SurveyConfiguration
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public ?int $surveyId = null;

    #[Groups(['survey_configuration:read'])]
    public string $mode = 'create';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $code = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $title = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $subtitle = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $surveyLanguage = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $resourceLanguage = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public ?string $availableFrom = null;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public ?string $availableUntil = null;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public bool $anonymous = false;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public int $visibleResults = 0;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $introduction = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $thanks = '';

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public int $surveyType = 0;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public ?int $parentId = null;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public bool $oneQuestionPerPage = false;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public bool $shuffle = false;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public bool $displayQuestionNumber = true;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public bool $showFormProfile = false;

    /**
     * @var array<int, string>
     */
    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public array $selectedProfileFields = [];

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public ?int $duration = null;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public bool $gradebookEnabled = false;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public ?int $gradebookCategoryId = null;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public float $gradebookWeight = 0.0;

    #[Groups(['survey_configuration:read', 'survey_configuration:write'])]
    public string $csrfToken = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_configuration:read'])]
    public array $settings = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    #[Groups(['survey_configuration:read'])]
    public array $options = [];

    #[Groups(['survey_configuration:read'])]
    public bool $canEdit = false;

    #[Groups(['survey_configuration:read'])]
    public bool $canCreate = false;

    #[Groups(['survey_configuration:read'])]
    public string $questionUrl = '';
}
