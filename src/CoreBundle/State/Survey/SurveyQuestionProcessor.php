<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyQuestion;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<SurveyQuestion, SurveyQuestion>
 */
final readonly class SurveyQuestionProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyCsrfTokenValidationTrait;

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_TYPES = [
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
    private const OPTION_TYPES = [
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
    private const DISPLAY_TYPES = [
        'yesno',
        'multiplechoice',
        'multipleresponse',
        'multiplechoiceother',
        'selectivedisplay',
    ];

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SurveyQuestionProvider $surveyQuestionProvider,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyQuestion
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage surveys in this context.');
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $payload = $this->getPayload($request, $data);
        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, SurveyQuestionProvider::CSRF_TOKEN_ID, $payload);

        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);
        $this->assertCanWriteSurvey($survey);

        $operationName = (string) $operation->getName();
        $questionId = isset($uriVariables['questionId']) ? (int) $uriVariables['questionId'] : 0;

        if ('post_survey_question' === $operationName) {
            $this->createQuestion($survey, $payload);
        } elseif ('put_survey_question' === $operationName) {
            $this->updateQuestion($survey, $questionId, $payload);
        } elseif ('delete_survey_question' === $operationName) {
            $this->deleteQuestion($survey, $questionId);
        } elseif ('post_survey_question_move' === $operationName) {
            $this->moveQuestion($survey, $questionId, (string) ($payload['direction'] ?? ''));
        } elseif ('post_survey_question_copy' === $operationName) {
            $this->copyQuestion($survey, $questionId);
        } else {
            throw new BadRequestHttpException('Unsupported survey question operation.');
        }

        $this->normalizeSortOrder($survey);
        $this->entityManager->flush();

        return $this->surveyQuestionProvider->buildResponse($survey, $course, $session);
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function canManageSurveys(): bool
    {
        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return false;
        }

        return $this->isSettingEnabled('survey.extend_rights_for_coach_on_survey');
    }

    private function assertCanWriteSurvey(CSurvey $survey): void
    {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        if ($this->isSurveyEditionHidden($survey)) {
            throw new AccessDeniedHttpException('This survey cannot be edited because edition is disabled by configuration.');
        }

        if ($this->surveyHasAnswers($survey) && !$this->isSettingEnabled('survey.survey_allow_answered_question_edit')) {
            throw new AccessDeniedHttpException('This survey already has answers and question editing is disabled by configuration.');
        }
    }

    private function getSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
    {
        $survey = $this->surveyRepository->find($surveyId);
        if (!$survey instanceof CSurvey) {
            throw new NotFoundHttpException('The requested survey was not found.');
        }

        if ($this->isSurveyInContext($survey, $course, $session)) {
            return $survey;
        }

        throw new AccessDeniedHttpException('The requested survey does not belong to the current course context.');
    }

    private function isSurveyInContext(CSurvey $survey, Course $course, ?Session $session): bool
    {
        $contexts = [$session];
        if (null !== $session && $this->isSettingEnabled('survey.show_surveys_base_in_sessions')) {
            $contexts[] = null;
        }

        foreach ($contexts as $currentSession) {
            $queryBuilder = $this->surveyRepository->getResourcesByCourse(
                $course,
                $currentSession,
                null,
                null,
                false,
                true,
            );

            $queryBuilder
                ->andWhere('resource.iid = :surveyId')
                ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ;

            if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request, mixed $data): array
    {
        if ($data instanceof SurveyQuestion) {
            return [
                'questionId' => $data->questionId,
                'question' => $data->question,
                'comment' => $data->comment,
                'type' => $data->type,
                'display' => $data->display,
                'isRequired' => $data->isRequired,
                'maxValue' => $data->maxValue,
                'parentQuestionId' => $data->parentQuestionId,
                'parentOptionId' => $data->parentOptionId,
                'direction' => $data->direction,
                'csrfToken' => $data->csrfToken,
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

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(SurveyQuestionProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createQuestion(CSurvey $survey, array $payload): CSurveyQuestion
    {
        $this->validateQuestionPayload($payload, false);
        $type = (string) $payload['type'];

        $question = new CSurveyQuestion();
        $question
            ->setSurvey($survey)
            ->setSurveyQuestion((string) $payload['question'])
            ->setSurveyQuestionComment((string) ($payload['comment'] ?? ''))
            ->setType($type)
            ->setDisplay($this->getDisplayValue($type, (string) ($payload['display'] ?? 'vertical')))
            ->setMaxValue($this->getMaxValue($type, $payload))
            ->setSort($this->getNextSort($survey))
            ->setSharedQuestionId(0)
            ->setIsMandatory($this->getRequiredValue($payload))
        ;
        $this->applyParentDependency($survey, $question, $payload);

        $this->entityManager->persist($question);
        $this->entityManager->flush();
        $this->saveOptions($survey, $question, $payload);

        return $question;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateQuestion(CSurvey $survey, int $questionId, array $payload): CSurveyQuestion
    {
        $question = $this->getQuestion($survey, $questionId);
        $payload['type'] = $question->getType();
        $this->validateQuestionPayload($payload, true);
        $type = $question->getType();

        $question
            ->setSurveyQuestion((string) $payload['question'])
            ->setSurveyQuestionComment((string) ($payload['comment'] ?? ''))
            ->setDisplay($this->getDisplayValue($type, (string) ($payload['display'] ?? 'vertical')))
            ->setMaxValue($this->getMaxValue($type, $payload))
            ->setIsMandatory($this->getRequiredValue($payload))
        ;
        $this->applyParentDependency($survey, $question, $payload);

        $this->entityManager->persist($question);
        $this->saveOptions($survey, $question, $payload);

        return $question;
    }

    private function deleteQuestion(CSurvey $survey, int $questionId): void
    {
        $question = $this->getQuestion($survey, $questionId);
        $this->entityManager->remove($question);
    }

    private function copyQuestion(CSurvey $survey, int $questionId): void
    {
        $source = $this->getQuestion($survey, $questionId);
        $question = new CSurveyQuestion();
        $question
            ->setSurvey($survey)
            ->setSurveyQuestion($source->getSurveyQuestion())
            ->setSurveyQuestionComment((string) $source->getSurveyQuestionComment())
            ->setType($source->getType())
            ->setDisplay($source->getDisplay())
            ->setMaxValue((int) ($source->getMaxValue() ?? 0))
            ->setSort($this->getNextSort($survey))
            ->setSharedQuestionId(0)
            ->setIsMandatory($source->isMandatory())
            ->setParent($source->getParent())
            ->setParentOption($source->getParentOption())
        ;

        $this->entityManager->persist($question);
        $this->entityManager->flush();

        foreach ($source->getOptions() as $sourceOption) {
            if (!$sourceOption instanceof CSurveyQuestionOption) {
                continue;
            }

            $option = new CSurveyQuestionOption();
            $option
                ->setSurvey($survey)
                ->setQuestion($question)
                ->setOptionText($sourceOption->getOptionText())
                ->setValue($sourceOption->getValue())
                ->setSort($sourceOption->getSort())
            ;
            $this->entityManager->persist($option);
        }
    }

    private function moveQuestion(CSurvey $survey, int $questionId, string $direction): void
    {
        if (!\in_array($direction, ['up', 'down'], true)) {
            throw new BadRequestHttpException('The move direction is invalid.');
        }

        $questions = $this->getOrderedQuestions($survey);
        $currentIndex = null;
        foreach ($questions as $index => $question) {
            if ((int) $question->getIid() === $questionId) {
                $currentIndex = $index;

                break;
            }
        }

        if (null === $currentIndex) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        $targetIndex = 'up' === $direction ? $currentIndex - 1 : $currentIndex + 1;
        if (!isset($questions[$targetIndex])) {
            return;
        }

        $currentQuestion = $questions[$currentIndex];
        $targetQuestion = $questions[$targetIndex];
        $currentSort = $currentQuestion->getSort();
        $currentQuestion->setSort($targetQuestion->getSort());
        $targetQuestion->setSort($currentSort);
        $this->entityManager->persist($currentQuestion);
        $this->entityManager->persist($targetQuestion);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateQuestionPayload(array $payload, bool $isEdit): void
    {
        $type = (string) ($payload['type'] ?? '');
        if (!\in_array($type, self::SUPPORTED_TYPES, true)) {
            throw new BadRequestHttpException('This question type is not supported by the new survey editor yet.');
        }

        if ('' === trim(strip_tags((string) ($payload['question'] ?? '')))) {
            throw new BadRequestHttpException('The question text is required.');
        }

        if (!\in_array($type, self::OPTION_TYPES, true)) {
            return;
        }

        if ('percentage' === $type) {
            return;
        }

        if ('score' === $type && $this->getMaxValue($type, $payload) <= 0) {
            throw new BadRequestHttpException('The maximum score is required.');
        }

        $options = $this->normalizePayloadOptions((array) ($payload['options'] ?? []), $type);
        $minimumOptions = 'score' === $type ? 1 : 2;
        if (\count($options) < $minimumOptions) {
            throw new BadRequestHttpException('Please fill all answer options.');
        }

        if ($isEdit) {
            return;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyParentDependency(CSurvey $survey, CSurveyQuestion $question, array $payload): void
    {
        if ('pagebreak' === $question->getType()) {
            $question->setParent(null);
            $question->setParentOption(null);

            return;
        }

        $parentQuestionId = (int) ($payload['parentQuestionId'] ?? 0);
        $parentOptionId = (int) ($payload['parentOptionId'] ?? 0);
        if ($parentQuestionId <= 0 || $parentOptionId <= 0) {
            $question->setParent(null);
            $question->setParentOption(null);

            return;
        }

        $parentQuestion = $this->getAllowedParentQuestion($survey, $parentQuestionId, $question);
        $parentOption = $this->getAllowedParentOption($parentQuestion, $parentOptionId);
        $question->setParent($parentQuestion);
        $question->setParentOption($parentOption);
    }

    private function getAllowedParentQuestion(CSurvey $survey, int $parentQuestionId, CSurveyQuestion $currentQuestion): CSurveyQuestion
    {
        $parentQuestion = $this->entityManager->getRepository(CSurveyQuestion::class)->find($parentQuestionId);
        if (!$parentQuestion instanceof CSurveyQuestion || (int) $parentQuestion->getSurvey()->getIid() !== (int) $survey->getIid()) {
            throw new BadRequestHttpException('The selected parent question is invalid.');
        }

        if ((int) $parentQuestion->getIid() === (int) $currentQuestion->getIid()) {
            throw new BadRequestHttpException('A question cannot depend on itself.');
        }

        if (!\in_array($parentQuestion->getType(), ['yesno', 'multiplechoice', 'multipleresponse'], true)) {
            throw new BadRequestHttpException('The selected parent question type is invalid.');
        }

        if (null !== $currentQuestion->getIid() && $parentQuestion->getSort() >= $currentQuestion->getSort()) {
            throw new BadRequestHttpException('The selected parent question must be placed before this question.');
        }

        return $parentQuestion;
    }

    private function getAllowedParentOption(CSurveyQuestion $parentQuestion, int $parentOptionId): CSurveyQuestionOption
    {
        foreach ($parentQuestion->getOptions() as $option) {
            if ($option instanceof CSurveyQuestionOption && (int) $option->getIid() === $parentOptionId) {
                return $option;
            }
        }

        throw new BadRequestHttpException('The selected parent option is invalid.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function saveOptions(CSurvey $survey, CSurveyQuestion $question, array $payload): void
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

        $existingOptions = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CSurveyQuestionOption || null === $option->getIid()) {
                continue;
            }

            $existingOptions[(int) $option->getIid()] = $option;
        }

        $keptOptionIds = [];
        $sort = 1;
        foreach ($payloadOptions as $payloadOption) {
            $optionId = (int) ($payloadOption['iid'] ?? 0);
            $option = $optionId > 0 && isset($existingOptions[$optionId])
                ? $existingOptions[$optionId]
                : new CSurveyQuestionOption();

            $option
                ->setSurvey($survey)
                ->setQuestion($question)
                ->setOptionText((string) $payloadOption['text'])
                ->setValue((int) ($payloadOption['value'] ?? 0))
                ->setSort($sort)
            ;
            $this->entityManager->persist($option);

            if ($optionId > 0) {
                $keptOptionIds[$optionId] = true;
            }
            $sort++;
        }

        foreach ($existingOptions as $optionId => $option) {
            if (!isset($keptOptionIds[$optionId])) {
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
            $options[] = [
                'iid' => 0,
                'text' => 'other',
                'value' => 0,
            ];
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
            $options[] = [
                'iid' => 0,
                'text' => (string) $i,
                'value' => 0,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, CSurveyQuestion>
     */
    private function getOrderedQuestions(CSurvey $survey): array
    {
        return $this->entityManager->getRepository(CSurveyQuestion::class)->findBy(
            ['survey' => $survey],
            ['sort' => 'ASC'],
        );
    }

    private function normalizeSortOrder(CSurvey $survey): void
    {
        $sort = 1;
        foreach ($this->getOrderedQuestions($survey) as $question) {
            $question->setSort($sort);
            $this->entityManager->persist($question);
            $sort++;
        }
    }

    private function getQuestion(CSurvey $survey, int $questionId): CSurveyQuestion
    {
        if ($questionId <= 0) {
            throw new BadRequestHttpException('A valid question id is required.');
        }

        $question = $this->entityManager->getRepository(CSurveyQuestion::class)->find($questionId);
        if (!$question instanceof CSurveyQuestion || (int) $question->getSurvey()->getIid() !== (int) $survey->getIid()) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        if (!\in_array($question->getType(), self::SUPPORTED_TYPES, true)) {
            throw new BadRequestHttpException('This question type is not supported by the new survey editor yet.');
        }

        return $question;
    }

    private function getNextSort(CSurvey $survey): int
    {
        $maxSort = $this->entityManager->createQueryBuilder()
            ->select('MAX(question.sort)')
            ->from(CSurveyQuestion::class, 'question')
            ->andWhere('IDENTITY(question.survey) = :surveyId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $maxSort + 1;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getMaxValue(string $type, array $payload): int
    {
        if ('score' !== $type) {
            return 0;
        }

        $maxValue = (int) ($payload['maxValue'] ?? 0);

        return max(0, min(100, $maxValue));
    }

    private function getDisplayValue(string $type, string $display): string
    {
        if (!\in_array($type, self::DISPLAY_TYPES, true)) {
            return '';
        }

        return 'horizontal' === $display ? 'horizontal' : 'vertical';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getRequiredValue(array $payload): bool
    {
        if (isset($payload['isRequired'])) {
            return true === $payload['isRequired'];
        }

        return $this->isSettingEnabled('survey.survey_mark_question_as_required');
    }

    private function surveyHasAnswers(CSurvey $survey): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(answer.iid)')
            ->from(CSurveyAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function isSurveyEditionHidden(CSurvey $survey): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);
        if (true === $value || 'true' === $value) {
            return true;
        }

        if ('*' === $value) {
            return true;
        }

        $surveyCode = (string) $survey->getCode();
        if ('' === $surveyCode) {
            return false;
        }

        $hiddenCodes = array_filter(array_map('trim', explode(',', (string) $value)));

        return \in_array($surveyCode, $hiddenCodes, true);
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
