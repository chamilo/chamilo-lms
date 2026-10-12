<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyTemplateQuestion;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestion;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestionOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<SurveyTemplateQuestion, SurveyTemplateQuestion>
 */
final readonly class SurveyTemplateQuestionProcessor implements ProcessorInterface
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

    /**
     * @var array<int, string>
     */
    private const array OPTION_TYPES = [
        'yesno',
        'multiplechoice',
        'multipleresponse',
        'dropdown',
        'score',
        'percentage',
        'multiplechoiceother',
        'selectivedisplay',
    ];

    /**
     * @var array<int, string>
     */
    private const array DISPLAY_TYPES = [
        'yesno',
        'multiplechoice',
        'multipleresponse',
        'multiplechoiceother',
        'selectivedisplay',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private SurveyTemplateConfigurationProvider $configurationProvider,
        private SurveyTemplateQuestionProvider $questionProvider,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyTemplateQuestion
    {
        $templateId = isset($uriVariables['templateId']) ? (int) $uriVariables['templateId'] : 0;
        if ($templateId <= 0) {
            throw new BadRequestHttpException('A valid survey template id is required.');
        }

        $template = $this->configurationProvider->getTemplate($templateId);
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $payload = $this->getPayload($request, $data);
        $questionId = isset($uriVariables['questionId']) ? (int) $uriVariables['questionId'] : 0;
        $operationName = (string) $operation->getName();

        if ('post_survey_template_question' === $operationName) {
            $this->createQuestion($template, $payload);
        } elseif ('patch_survey_template_question' === $operationName) {
            $this->updateQuestion($template, $questionId, $payload);
        } elseif ('delete_survey_template_question' === $operationName) {
            $this->deleteQuestion($template, $questionId);
        } elseif ('post_survey_template_question_move' === $operationName) {
            $this->moveQuestion($template, $questionId, (string) ($payload['direction'] ?? ''));
        } elseif ('post_survey_template_question_copy' === $operationName) {
            $this->copyQuestion($template, $questionId);
        } else {
            throw new BadRequestHttpException('Unsupported survey template question operation.');
        }

        $this->normalizeSortOrder($template);
        $template->touch();
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        return $this->questionProvider->buildResponse($template);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request, mixed $data): array
    {
        if ($data instanceof SurveyTemplateQuestion) {
            return [
                'question' => $data->question,
                'comment' => $data->comment,
                'type' => $data->type,
                'display' => $data->display,
                'isRequired' => $data->isRequired,
                'maxValue' => $data->maxValue,
                'parentQuestionId' => $data->parentQuestionId,
                'parentOptionId' => $data->parentOptionId,
                'direction' => $data->direction,
                'options' => $data->options,
            ];
        }

        $content = trim($request->getContent());
        if ('' === $content) {
            return [];
        }

        $payload = json_decode($content, true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createQuestion(CSurveyTemplate $template, array $payload): void
    {
        $this->validateQuestionPayload($payload);
        $type = (string) $payload['type'];
        $question = (new CSurveyTemplateQuestion())
            ->setTemplate($template)
            ->setQuestion((string) $payload['question'])
            ->setComment((string) ($payload['comment'] ?? ''))
            ->setType($type)
            ->setDisplay($this->getDisplayValue($type, (string) ($payload['display'] ?? 'vertical')))
            ->setMaxValue($this->getMaxValue($type, $payload))
            ->setSort($this->getNextSort($template))
            ->setIsRequired(true === ($payload['isRequired'] ?? false))
        ;
        $this->applyParentDependency($template, $question, $payload);
        $this->entityManager->persist($question);
        $this->entityManager->flush();
        $this->saveOptions($question, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateQuestion(CSurveyTemplate $template, int $questionId, array $payload): void
    {
        $question = $this->getQuestion($template, $questionId);
        $payload['type'] = $question->getType();
        $this->validateQuestionPayload($payload);
        $type = $question->getType();

        $question
            ->setQuestion((string) $payload['question'])
            ->setComment((string) ($payload['comment'] ?? ''))
            ->setDisplay($this->getDisplayValue($type, (string) ($payload['display'] ?? 'vertical')))
            ->setMaxValue($this->getMaxValue($type, $payload))
            ->setIsRequired(true === ($payload['isRequired'] ?? false))
        ;
        $this->applyParentDependency($template, $question, $payload);
        $this->entityManager->persist($question);
        $this->saveOptions($question, $payload);
    }

    private function deleteQuestion(CSurveyTemplate $template, int $questionId): void
    {
        $this->entityManager->remove($this->getQuestion($template, $questionId));
    }

    private function copyQuestion(CSurveyTemplate $template, int $questionId): void
    {
        $source = $this->getQuestion($template, $questionId);
        $question = (new CSurveyTemplateQuestion())
            ->setTemplate($template)
            ->setQuestion($source->getQuestion())
            ->setComment($source->getComment())
            ->setType($source->getType())
            ->setDisplay($source->getDisplay())
            ->setMaxValue($source->getMaxValue())
            ->setSort($this->getNextSort($template))
            ->setIsRequired($source->isRequired())
            ->setParent($source->getParent())
            ->setParentOption($source->getParentOption())
        ;
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        foreach ($source->getOptions() as $sourceOption) {
            if (!$sourceOption instanceof CSurveyTemplateQuestionOption) {
                continue;
            }

            $option = (new CSurveyTemplateQuestionOption())
                ->setQuestion($question)
                ->setOptionText($sourceOption->getOptionText())
                ->setSort($sourceOption->getSort())
                ->setValue($sourceOption->getValue())
            ;
            $this->entityManager->persist($option);
        }
    }

    private function moveQuestion(CSurveyTemplate $template, int $questionId, string $direction): void
    {
        if (!\in_array($direction, ['up', 'down'], true)) {
            throw new BadRequestHttpException('The move direction is invalid.');
        }

        $questions = $this->getOrderedQuestions($template);
        $currentIndex = null;
        foreach ($questions as $index => $question) {
            if ((int) $question->getId() === $questionId) {
                $currentIndex = $index;

                break;
            }
        }
        if (null === $currentIndex) {
            throw new NotFoundHttpException('The requested survey template question was not found.');
        }

        $targetIndex = 'up' === $direction ? $currentIndex - 1 : $currentIndex + 1;
        if (!isset($questions[$targetIndex])) {
            return;
        }

        $target = $questions[$targetIndex];
        $sort = $questions[$currentIndex]->getSort();
        $questions[$currentIndex]->setSort($target->getSort());
        $target->setSort($sort);
        $this->entityManager->persist($questions[$currentIndex]);
        $this->entityManager->persist($target);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateQuestionPayload(array $payload): void
    {
        $type = (string) ($payload['type'] ?? '');
        if (!\in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new BadRequestHttpException('This survey template question type is not supported.');
        }
        if ('' === trim(strip_tags((string) ($payload['question'] ?? '')))) {
            throw new BadRequestHttpException('The question text is required.');
        }
        if (!\in_array($type, self::OPTION_TYPES, true) || 'percentage' === $type) {
            return;
        }
        if ('score' === $type && $this->getMaxValue($type, $payload) <= 0) {
            throw new BadRequestHttpException('The maximum score is required.');
        }

        $minimumOptions = 'score' === $type ? 1 : 2;
        if (\count($this->normalizePayloadOptions((array) ($payload['options'] ?? []), $type)) < $minimumOptions) {
            throw new BadRequestHttpException('Please fill all answer options.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyParentDependency(
        CSurveyTemplate $template,
        CSurveyTemplateQuestion $question,
        array $payload,
    ): void {
        if ('pagebreak' === $question->getType()) {
            $question->setParent(null)->setParentOption(null);

            return;
        }

        $parentQuestionId = (int) ($payload['parentQuestionId'] ?? 0);
        $parentOptionId = (int) ($payload['parentOptionId'] ?? 0);
        if ($parentQuestionId <= 0 || $parentOptionId <= 0) {
            $question->setParent(null)->setParentOption(null);

            return;
        }

        $parentQuestion = $this->getQuestion($template, $parentQuestionId);
        if ((int) $parentQuestion->getId() === (int) $question->getId()) {
            throw new BadRequestHttpException('A question cannot depend on itself.');
        }
        if (!\in_array($parentQuestion->getType(), ['yesno', 'multiplechoice', 'multipleresponse'], true)) {
            throw new BadRequestHttpException('The selected parent question type is invalid.');
        }
        if (null !== $question->getId() && $parentQuestion->getSort() >= $question->getSort()) {
            throw new BadRequestHttpException('The selected parent question must be placed before this question.');
        }

        foreach ($parentQuestion->getOptions() as $option) {
            if ($option instanceof CSurveyTemplateQuestionOption && (int) $option->getId() === $parentOptionId) {
                $question->setParent($parentQuestion)->setParentOption($option);

                return;
            }
        }

        throw new BadRequestHttpException('The selected parent option is invalid.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function saveOptions(CSurveyTemplateQuestion $question, array $payload): void
    {
        $type = $question->getType();
        if (!\in_array($type, self::OPTION_TYPES, true)) {
            foreach ($question->getOptions() as $option) {
                $this->entityManager->remove($option);
            }

            return;
        }

        $payloadOptions = 'percentage' === $type
            ? $this->buildPercentageOptions()
            : $this->normalizePayloadOptions((array) ($payload['options'] ?? []), $type);

        $existing = [];
        foreach ($question->getOptions() as $option) {
            if ($option instanceof CSurveyTemplateQuestionOption && null !== $option->getId()) {
                $existing[$option->getId()] = $option;
            }
        }

        $kept = [];
        $sort = 1;
        foreach ($payloadOptions as $payloadOption) {
            $optionId = (int) ($payloadOption['iid'] ?? 0);
            $option = $optionId > 0 && isset($existing[$optionId])
                ? $existing[$optionId]
                : new CSurveyTemplateQuestionOption();
            $option
                ->setQuestion($question)
                ->setOptionText((string) $payloadOption['text'])
                ->setSort($sort)
                ->setValue((int) ($payloadOption['value'] ?? 0))
            ;
            $this->entityManager->persist($option);
            if ($optionId > 0) {
                $kept[$optionId] = true;
            }
            $sort++;
        }

        foreach ($existing as $optionId => $option) {
            if (!isset($kept[$optionId])) {
                $this->entityManager->remove($option);
            }
        }
    }

    /**
     * @param array<int, mixed> $rawOptions
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizePayloadOptions(array $rawOptions, string $type): array
    {
        $options = [];
        foreach ($rawOptions as $rawOption) {
            if (!\is_array($rawOption)) {
                continue;
            }
            $text = trim((string) ($rawOption['text'] ?? ''));
            if ('' === trim(strip_tags($text))) {
                continue;
            }
            if ('multiplechoiceother' === $type && 'other' === strtolower($text)) {
                continue;
            }

            $options[] = [
                'iid' => isset($rawOption['iid']) ? (int) $rawOption['iid'] : 0,
                'text' => $text,
                'value' => isset($rawOption['value']) ? (int) $rawOption['value'] : 0,
            ];
        }

        if ('multiplechoiceother' === $type) {
            $options[] = ['iid' => 0, 'text' => 'other', 'value' => 0];
        }

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPercentageOptions(): array
    {
        $options = [];
        for ($i = 1; $i <= 100; $i++) {
            $options[] = ['iid' => 0, 'text' => (string) $i, 'value' => 0];
        }

        return $options;
    }

    /**
     * @return array<int, CSurveyTemplateQuestion>
     */
    private function getOrderedQuestions(CSurveyTemplate $template): array
    {
        return $this->entityManager->getRepository(CSurveyTemplateQuestion::class)->findBy(
            ['template' => $template],
            ['sort' => 'ASC', 'id' => 'ASC'],
        );
    }

    private function normalizeSortOrder(CSurveyTemplate $template): void
    {
        $sort = 1;
        foreach ($this->getOrderedQuestions($template) as $question) {
            $question->setSort($sort);
            $this->entityManager->persist($question);
            $sort++;
        }
    }

    private function getQuestion(CSurveyTemplate $template, int $questionId): CSurveyTemplateQuestion
    {
        if ($questionId <= 0) {
            throw new BadRequestHttpException('A valid question id is required.');
        }

        $question = $this->entityManager->getRepository(CSurveyTemplateQuestion::class)->find($questionId);
        if (!$question instanceof CSurveyTemplateQuestion || (int) $question->getTemplate()->getId() !== (int) $template->getId()) {
            throw new NotFoundHttpException('The requested survey template question was not found.');
        }
        if (!\in_array($question->getType(), self::SUPPORTED_TYPES, true)) {
            throw new BadRequestHttpException('This survey template question type is not supported.');
        }

        return $question;
    }

    private function getNextSort(CSurveyTemplate $template): int
    {
        $maxSort = $this->entityManager->createQueryBuilder()
            ->select('MAX(question.sort)')
            ->from(CSurveyTemplateQuestion::class, 'question')
            ->andWhere('question.template = :template')
            ->setParameter('template', $template)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $maxSort + 1;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getMaxValue(string $type, array $payload): ?int
    {
        if ('score' !== $type) {
            return null;
        }

        return max(0, min(100, (int) ($payload['maxValue'] ?? 0)));
    }

    private function getDisplayValue(string $type, string $display): string
    {
        if (!\in_array($type, self::DISPLAY_TYPES, true)) {
            return '';
        }

        return 'horizontal' === $display ? 'horizontal' : 'vertical';
    }
}
