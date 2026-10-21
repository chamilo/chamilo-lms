<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateConfigurationProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateConfigurationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyTemplateConfiguration',
    operations: [
        new Get(
            uriTemplate: '/survey/templates/configuration',
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_survey_template_configuration_create',
            provider: SurveyTemplateConfigurationProvider::class,
        ),
        new Get(
            uriTemplate: '/survey/templates/{templateId}',
            requirements: ['templateId' => '\d+'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_survey_template_configuration_edit',
            provider: SurveyTemplateConfigurationProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier for teacher template selection',
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier for teacher template selection',
                ),
            ],
        ),
        new Post(
            uriTemplate: '/survey/templates',
            security: "is_granted('ROLE_ADMIN')",
            name: 'post_survey_template_configuration',
            processor: SurveyTemplateConfigurationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/survey/templates/{templateId}',
            requirements: ['templateId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'patch_survey_template_configuration',
            processor: SurveyTemplateConfigurationProcessor::class,
        ),
        new Delete(
            uriTemplate: '/survey/templates/{templateId}',
            requirements: ['templateId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'delete_survey_template_configuration',
            processor: SurveyTemplateConfigurationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_template_configuration:read']],
    denormalizationContext: ['groups' => ['survey_template_configuration:write']],
)]
final class SurveyTemplateConfiguration
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public ?int $templateId = null;

    #[Groups(['survey_template_configuration:read'])]
    public string $mode = 'create';

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public string $title = '';

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public string $subtitle = '';

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public string $introduction = '';

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public string $thanks = '';

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public bool $anonymous = false;

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public bool $shuffle = false;

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public bool $oneQuestionPerPage = false;

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public bool $displayQuestionNumber = true;

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public bool $mandatory = false;

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public ?int $duration = null;

    #[Groups(['survey_template_configuration:read', 'survey_template_configuration:write'])]
    public bool $visible = false;

    #[Groups(['survey_template_configuration:read'])]
    public int $questionCount = 0;

    #[Groups(['survey_template_configuration:read'])]
    public ?string $createdAt = null;

    #[Groups(['survey_template_configuration:read'])]
    public ?string $updatedAt = null;
}
