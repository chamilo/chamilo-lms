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
use Chamilo\CoreBundle\State\Survey\SurveyTemplateQuestionProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyTemplateQuestionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SurveyTemplateQuestion',
    operations: [
        new Get(
            uriTemplate: '/survey/templates/{templateId}/questions',
            requirements: ['templateId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_survey_template_questions',
            provider: SurveyTemplateQuestionProvider::class,
        ),
        new Post(
            uriTemplate: '/survey/templates/{templateId}/questions',
            requirements: ['templateId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            name: 'post_survey_template_question',
            processor: SurveyTemplateQuestionProcessor::class,
        ),
        new Patch(
            uriTemplate: '/survey/templates/{templateId}/questions/{questionId}',
            requirements: ['templateId' => '\d+', 'questionId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'patch_survey_template_question',
            processor: SurveyTemplateQuestionProcessor::class,
        ),
        new Delete(
            uriTemplate: '/survey/templates/{templateId}/questions/{questionId}',
            requirements: ['templateId' => '\d+', 'questionId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'delete_survey_template_question',
            processor: SurveyTemplateQuestionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/templates/{templateId}/questions/{questionId}/move',
            requirements: ['templateId' => '\d+', 'questionId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'post_survey_template_question_move',
            processor: SurveyTemplateQuestionProcessor::class,
        ),
        new Post(
            uriTemplate: '/survey/templates/{templateId}/questions/{questionId}/copy',
            requirements: ['templateId' => '\d+', 'questionId' => '\d+'],
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'post_survey_template_question_copy',
            processor: SurveyTemplateQuestionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['survey_template_question:read']],
    denormalizationContext: ['groups' => ['survey_template_question:write']],
)]
final class SurveyTemplateQuestion
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_template_question:read'])]
    public ?int $templateId = null;

    #[ApiProperty(identifier: false)]
    #[Groups(['survey_template_question:read', 'survey_template_question:write'])]
    public ?int $questionId = null;

    #[Groups(['survey_template_question:write'])]
    public string $question = '';

    #[Groups(['survey_template_question:write'])]
    public string $comment = '';

    #[Groups(['survey_template_question:write'])]
    public string $type = '';

    #[Groups(['survey_template_question:write'])]
    public string $display = 'vertical';

    #[Groups(['survey_template_question:write'])]
    public bool $isRequired = false;

    #[Groups(['survey_template_question:write'])]
    public ?int $maxValue = null;

    #[Groups(['survey_template_question:write'])]
    public int $parentQuestionId = 0;

    #[Groups(['survey_template_question:write'])]
    public int $parentOptionId = 0;

    #[Groups(['survey_template_question:write'])]
    public string $direction = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_template_question:write'])]
    public array $options = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_template_question:read'])]
    public array $questions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_template_question:read'])]
    public array $choices = [];

    public function getTemplateId(): ?int
    {
        return $this->templateId;
    }

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }
}
