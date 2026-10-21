<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateInstantiateProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyTemplateInstantiate',
    operations: [
        new Post(
            uriTemplate: '/survey/templates/{templateId}/instantiate',
            requirements: ['templateId' => '\d+'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            read: false,
            name: 'post_survey_template_instantiate',
            processor: SurveyTemplateInstantiateProcessor::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Current course identifier for teacher instantiation',
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Current session identifier for teacher instantiation',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['survey_template_instantiate:read']],
    denormalizationContext: ['groups' => ['survey_template_instantiate:write']],
)]
final class SurveyTemplateInstantiate
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_template_instantiate:read'])]
    public ?int $templateId = null;

    #[ApiProperty(identifier: false)]
    #[Groups(['survey_template_instantiate:read'])]
    public ?int $surveyId = null;

    #[Groups(['survey_template_instantiate:write'])]
    public ?int $targetCourseId = null;

    #[Groups(['survey_template_instantiate:write'])]
    public ?int $targetSessionId = null;

    #[Groups(['survey_template_instantiate:read'])]
    public int $questionCount = 0;

    #[Groups(['survey_template_instantiate:read'])]
    public string $questionUrl = '';

    #[Groups(['survey_template_instantiate:read'])]
    public string $message = '';
}
