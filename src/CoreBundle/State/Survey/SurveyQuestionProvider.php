<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<SurveyQuestion>
 */
final readonly class SurveyQuestionProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;

    public const CSRF_TOKEN_ID = 'survey_question';

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

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyQuestion
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

        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);

        return $this->buildResponse($survey, $course, $session);
    }

    public function buildResponse(CSurvey $survey, Course $course, ?Session $session): SurveyQuestion
    {
        $response = new SurveyQuestion();
        $response->surveyId = (int) $survey->getIid();
        $response->survey = $this->normalizeSurvey($survey, $course, $session);
        $response->settings = $this->getSettings();
        $response->choices = $this->getChoices($survey);
        $response->hasAnswers = $this->surveyHasAnswers($survey);
        $response->canEdit = $this->canWriteSurvey($survey);
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $response->questions = $this->getQuestions($survey, $response->canEdit, $response->hasAnswers);

        return $response;
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

    private function canWriteSurvey(CSurvey $survey): bool
    {
        if (3 === $survey->getSurveyType()) {
            return false;
        }

        if ($this->isSurveyEditionHidden($survey)) {
            return false;
        }

        if ($this->surveyHasAnswers($survey) && !$this->isSettingEnabled('survey.survey_allow_answered_question_edit')) {
            return false;
        }

        return true;
    }

    public function getSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
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
     * @return array<int, array<string, mixed>>
     */
    private function getQuestions(CSurvey $survey, bool $canEditSurvey, bool $hasAnswers): array
    {
        $questions = $this->entityManager->createQueryBuilder()
            ->select('question')
            ->addSelect('questionOption')
            ->from(CSurveyQuestion::class, 'question')
            ->leftJoin('question.options', 'questionOption')
            ->andWhere('IDENTITY(question.survey) = :surveyId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->orderBy('question.sort', 'ASC')
            ->addOrderBy('questionOption.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        $total = \count($questions);
        foreach ($questions as $index => $question) {
            if (!$question instanceof CSurveyQuestion) {
                continue;
            }

            $type = $question->getType();
            $isSupported = \in_array($type, self::SUPPORTED_TYPES, true);
            $canWriteQuestion = $canEditSurvey && $isSupported;
            $items[] = [
                'iid' => (int) $question->getIid(),
                'question' => $question->getSurveyQuestion(),
                'comment' => (string) $question->getSurveyQuestionComment(),
                'type' => $type,
                'typeLabel' => $this->getTypeLabel($type),
                'display' => $question->getDisplay(),
                'sort' => $question->getSort(),
                'maxValue' => $question->getMaxValue(),
                'isRequired' => $question->isMandatory(),
                'parentQuestionId' => null !== $question->getParent() ? (int) $question->getParent()->getIid() : 0,
                'parentOptionId' => null !== $question->getParentOption() ? (int) $question->getParentOption()->getIid() : 0,
                'optionCount' => $question->getOptions()->count(),
                'options' => $this->normalizeOptions($question),
                'isSupported' => $isSupported,
                'canEdit' => $canWriteQuestion,
                'canDelete' => $canWriteQuestion,
                'canCopy' => $canEditSurvey && $isSupported,
                'canMoveUp' => $canWriteQuestion && $index > 0,
                'canMoveDown' => $canWriteQuestion && $index < $total - 1,
                'lockedByAnswers' => $hasAnswers && !$this->isSettingEnabled('survey.survey_allow_answered_question_edit'),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptions(CSurveyQuestion $question): array
    {
        $options = [];
        foreach ($question->getOptions() as $option) {
            if (!$option instanceof CSurveyQuestionOption) {
                continue;
            }

            $optionText = $option->getOptionText();
            $options[] = [
                'iid' => (int) $option->getIid(),
                'text' => $optionText,
                'value' => $option->getValue(),
                'sort' => $option->getSort(),
                'isOther' => 'other' === trim(strtolower($optionText)),
            ];
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSurvey(CSurvey $survey, Course $course, ?Session $session): array
    {
        return [
            'iid' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'code' => (string) $survey->getCode(),
            'surveyType' => $survey->getSurveyType(),
            'surveyTypeLabel' => $this->getSurveyTypeLabel($survey->getSurveyType()),
            'configurationRoute' => [
                'name' => 'SurveyEdit',
                'params' => ['surveyId' => (int) $survey->getIid()],
                'query' => [
                    'cid' => (int) $course->getId(),
                    'sid' => null !== $session ? (int) $session->getId() : null,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'hideEdition' => $this->settingsManager->getSetting('survey.hide_survey_edition', true) ?: '',
            'markQuestionAsRequired' => $this->isSettingEnabled('survey.survey_mark_question_as_required'),
            'allowAnsweredQuestionEdit' => $this->isSettingEnabled('survey.survey_allow_answered_question_edit'),
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getChoices(CSurvey $survey): array
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
            'parentQuestions' => $this->getParentQuestionChoices($survey),
            'parentOptions' => $this->getParentOptionChoices($survey),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getParentQuestionChoices(CSurvey $survey): array
    {
        $choices = [
            ['value' => 0, 'label' => 'None', 'sort' => 0],
        ];

        foreach ($this->getOrderedQuestions($survey) as $question) {
            $type = $question->getType();
            if (!\in_array($type, ['yesno', 'multiplechoice', 'multipleresponse'], true)) {
                continue;
            }

            $choices[] = [
                'value' => (int) $question->getIid(),
                'label' => trim(strip_tags($question->getSurveyQuestion())) ?: $this->getTypeLabel($type),
                'sort' => $question->getSort(),
            ];
        }

        return $choices;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getParentOptionChoices(CSurvey $survey): array
    {
        $choices = [];

        foreach ($this->getOrderedQuestions($survey) as $question) {
            $questionId = (int) $question->getIid();
            $choices[$questionId] = [
                ['value' => 0, 'label' => 'None'],
            ];

            foreach ($question->getOptions() as $option) {
                if (!$option instanceof CSurveyQuestionOption) {
                    continue;
                }

                $choices[$questionId][] = [
                    'value' => (int) $option->getIid(),
                    'label' => trim(strip_tags($option->getOptionText())) ?: 'Option',
                ];
            }
        }

        return $choices;
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

    private function getSurveyTypeLabel(int $type): string
    {
        return match ($type) {
            3 => 'Doodle survey',
            1 => 'Personality survey',
            default => 'Regular survey',
        };
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
            'personality' => 'Personality',
            default => ucfirst($type),
        };
    }
}
