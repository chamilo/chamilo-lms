<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyTemplate',
    operations: [
        new Post(
            uriTemplate: '/survey/templates/training-satisfaction',
            openapi: new Operation(
                summary: 'Create a training satisfaction survey from the default template',
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_survey_training_satisfaction_template',
            processor: SurveyTemplateProcessor::class,
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
            ],
        ),
    ],
    normalizationContext: ['groups' => ['survey_template:read']],
    denormalizationContext: ['groups' => ['survey_template:write']],
)]
final class SurveyTemplate
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_template:read'])]
    public ?int $surveyId = null;

    #[Groups(['survey_template:write'])]
    public string $title = '';

    #[Groups(['survey_template:read'])]
    public int $questionCount = 0;

    #[Groups(['survey_template:read'])]
    public string $questionUrl = '';

    #[Groups(['survey_template:read'])]
    public string $message = '';
}
