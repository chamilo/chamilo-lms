<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateQuestion;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestion;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestionOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<SurveyTemplateQuestion>
 */
final readonly class SurveyTemplateQuestionProvider implements ProviderInterface
{
    /**
     * @var array<int, string>
     */
    private const array SUPPORTED_TYPES = [
        'yesno',
        'multiplechoice',
        'multipleresponse',
        'dropdown',
        'open',
        'comment',
        'pagebreak',
        'score',
        'percentage',
        'multiplechoiceother',
        'selectivedisplay',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SurveyTemplateConfigurationProvider $configurationProvider,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplateQuestion
    {
        $templateId = isset($uriVariables['templateId']) ? (int) $uriVariables['templateId'] : 0;
        if ($templateId <= 0) {
            throw new BadRequestHttpException('A valid survey template id is required.');
        }

        return $this->buildResponse($this->configurationProvider->getTemplate($templateId));
    }

    public function buildResponse(CSurveyTemplate $template): SurveyTemplateQuestion
    {
        $response = new SurveyTemplateQuestion();
        $response->templateId = (int) $template->getId();
        $response->questions = $this->getQuestions($template);
        $response->choices = $this->getChoices($template);

        return $response;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestions(CSurveyTemplate $template): array
    {
        $questions = $this->getOrderedQuestions($template);
        $items = [];
        $total = \count($questions);

        foreach ($questions as $index => $question) {
            $type = $question->getType();
            $isSupported = \in_array($type, self::SUPPORTED_TYPES, true);
            $items[] = [
                'id' => (int) $question->getId(),
                'question' => $question->getQuestion(),
                'comment' => (string) $question->getComment(),
                'type' => $type,
                'typeLabel' => $this->getTypeLabel($type),
                'display' => $question->getDisplay(),
                'sort' => $question->getSort(),
                'maxValue' => $question->getMaxValue(),
                'isRequired' => $question->isRequired(),
                'parentQuestionId' => null !== $question->getParent() ? (int) $question->getParent()->getId() : 0,
                'parentOptionId' => null !== $question->getParentOption() ? (int) $question->getParentOption()->getId() : 0,
                'options' => $this->normalizeOptions($question),
                'optionCount' => $question->getOptions()->count(),
                'isSupported' => $isSupported,
                'canEdit' => $isSupported,
                'canDelete' => $isSupported,
                'canCopy' => $isSupported,
                'canMoveUp' => $isSupported && $index > 0,
                'canMoveDown' => $isSupported && $index < $total - 1,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptions(CSurveyTemplateQuestion $question): array
    {
        $items = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CSurveyTemplateQuestionOption) {
                continue;
            }

            $text = $option->getOptionText();
            $items[] = [
                'id' => (int) $option->getId(),
                'iid' => (int) $option->getId(),
                'text' => $text,
                'value' => $option->getValue(),
                'sort' => $option->getSort(),
                'isOther' => 'other' === trim(strtolower($text)),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function getChoices(CSurveyTemplate $template): array
    {
        return [
            'types' => [
                ['value' => 'yesno', 'label' => 'Yes / No'],
                ['value' => 'multiplechoice', 'label' => 'Multiple choice'],
                ['value' => 'multipleresponse', 'label' => 'Multiple answers'],
                ['value' => 'dropdown', 'label' => 'Dropdown'],
                ['value' => 'open', 'label' => 'Open'],
                ['value' => 'comment', 'label' => 'Comment'],
                ['value' => 'pagebreak', 'label' => 'Page break'],
                ['value' => 'score', 'label' => 'Score'],
                ['value' => 'percentage', 'label' => 'Percentage'],
                ['value' => 'multiplechoiceother', 'label' => 'Multiple choice with free text'],
                ['value' => 'selectivedisplay', 'label' => 'Selective display'],
            ],
            'display' => [
                ['value' => 'vertical', 'label' => 'Vertical'],
                ['value' => 'horizontal', 'label' => 'Horizontal'],
            ],
            'parentQuestions' => $this->getParentQuestionChoices($template),
            'parentOptions' => $this->getParentOptionChoices($template),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getParentQuestionChoices(CSurveyTemplate $template): array
    {
        $items = [['value' => 0, 'label' => 'None', 'sort' => 0]];
        foreach ($this->getOrderedQuestions($template) as $question) {
            if (!\in_array($question->getType(), ['yesno', 'multiplechoice', 'multipleresponse'], true)) {
                continue;
            }

            $items[] = [
                'value' => (int) $question->getId(),
                'label' => trim(strip_tags($question->getQuestion())) ?: $this->getTypeLabel($question->getType()),
                'sort' => $question->getSort(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<int, array{value:int,label:string}>>
     */
    private function getParentOptionChoices(CSurveyTemplate $template): array
    {
        $items = [];
        foreach ($this->getOrderedQuestions($template) as $question) {
            $questionId = (int) $question->getId();
            $items[$questionId] = [['value' => 0, 'label' => 'None']];
            foreach ($question->getOptions() as $option) {
                if (!$option instanceof CSurveyTemplateQuestionOption) {
                    continue;
                }

                $items[$questionId][] = [
                    'value' => (int) $option->getId(),
                    'label' => trim(strip_tags($option->getOptionText())) ?: 'Option',
                ];
            }
        }

        return $items;
    }

    /**
     * @return array<int, CSurveyTemplateQuestion>
     */
    private function getOrderedQuestions(CSurveyTemplate $template): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('question', 'questionOption')
            ->from(CSurveyTemplateQuestion::class, 'question')
            ->leftJoin('question.options', 'questionOption')
            ->andWhere('question.template = :template')
            ->setParameter('template', $template)
            ->orderBy('question.sort', 'ASC')
            ->addOrderBy('question.id', 'ASC')
            ->addOrderBy('questionOption.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'yesno' => 'Yes / No',
            'multiplechoice' => 'Multiple choice',
            'multipleresponse' => 'Multiple answers',
            'dropdown' => 'Dropdown',
            'open' => 'Open',
            'comment' => 'Comment',
            'pagebreak' => 'Page break',
            'score' => 'Score',
            'percentage' => 'Percentage',
            'multiplechoiceother' => 'Multiple choice with free text',
            'selectivedisplay' => 'Selective display',
            default => $type,
        };
    }
}
