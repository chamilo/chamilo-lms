<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Survey\SurveyPersonalityCategoryProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyPersonalityCategoryProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyPersonalityCategory',
    operations: [
        new Get(
            uriTemplate: '/survey/personality/{surveyId}/categories',
            requirements: ['surveyId' => '\d+'],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            provider: SurveyPersonalityCategoryProvider::class,
            name: 'get_survey_personality_categories',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
            ],
        ),
        new Post(
            uriTemplate: '/survey/personality/{surveyId}/categories',
            requirements: ['surveyId' => '\d+'],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            processor: SurveyPersonalityCategoryProcessor::class,
            name: 'post_survey_personality_category',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
            ],
        ),
        new Delete(
            uriTemplate: '/survey/personality/{surveyId}/categories/{categoryId}',
            requirements: ['surveyId' => '\d+', 'categoryId' => '\d+'],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            processor: SurveyPersonalityCategoryProcessor::class,
            name: 'delete_survey_personality_category',
            read: false,
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['survey_personality_category:read']],
    denormalizationContext: ['groups' => ['survey_personality_category:write']],
)]
final class SurveyPersonalityCategory
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_personality_category:read'])]
    public ?int $surveyId = null;

    #[ApiProperty(identifier: false)]
    public ?int $categoryId = null;

    #[Groups(['survey_personality_category:read'])]
    public array $categories = [];

    #[Groups(['survey_personality_category:write'])]
    public string $label = '';
}
