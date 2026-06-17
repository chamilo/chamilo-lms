<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyAnswer;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use DateTimeInterface;
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
 * @implements ProviderInterface<SurveyAnswer>
 */
final readonly class SurveyAnswerProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyProfileFieldsTrait;

    public const CSRF_TOKEN_ID = 'survey_answer';

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyAnswer
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->getSurvey($surveyId);
        $course = $this->getCourse($request, $survey);
        $session = $this->getSession($request);
        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);
        $preview = $request->query->getBoolean('preview');

        return $this->buildResponse($survey, $course, $session, $preview, $request);
    }

    public function buildResponse(
        CSurvey $survey,
        Course $course,
        ?Session $session,
        bool $preview,
        Request $request,
        bool $isFinished = false,
        string $message = ''
    ): SurveyAnswer {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting poll answers must be opened in the meeting view.');
        }

        $user = $this->getCurrentUserOrNull();
        $invitation = null;
        if (!$preview) {
            $this->assertSurveyIsAvailable($survey);
            $invitation = $this->getInvitation($survey, $course, $session, $user, $request);
        } elseif (!$this->canPreviewSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to preview this survey.');
        }

        $response = new SurveyAnswer();
        $response->surveyId = (int) $survey->getIid();
        $response->preview = $preview;
        $response->survey = $this->normalizeSurvey($survey, $course, $session);
        $response->questions = $this->getQuestions($survey);
        $response->profileFields = $user instanceof User ? $this->getSurveyAnswerProfileFields($survey, $user) : [];
        $response->pages = $this->buildPages($survey, $response->questions);
        $response->settings = $this->getSettings();
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $response->isFinished = $isFinished;
        $response->message = $message;

        if (null !== $invitation) {
            $response->invitationCode = $invitation->getInvitationCode();
            $response->isAnswered = 1 === (int) $invitation->getAnswered();
            $response->canSubmit = !$response->isAnswered || $this->isSettingEnabled('survey.survey_allow_answered_question_edit');
            $response->answers = $this->getExistingAnswers($survey, $this->getAnswerUserKey($survey, $user, $request), $request);
        } else {
            $response->canSubmit = false;
        }

        if ($preview) {
            $response->canSubmit = false;
        }

        return $response;
    }

    public function getSurvey(int $surveyId): CSurvey
    {
        $survey = $this->surveyRepository->find($surveyId);
        if (!$survey instanceof CSurvey) {
            throw new NotFoundHttpException('The requested survey was not found.');
        }

        return $survey;
    }

    public function getCourse(Request $request, ?CSurvey $survey = null): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            $courseId = $request->query->getInt('publicCid');
        }

        if ($courseId <= 0 && $survey instanceof CSurvey) {
            $course = $this->getCourseFromSurveyResource($survey);
            if ($course instanceof Course) {
                return $course;
            }
        }

        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    public function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            $sessionId = $request->query->getInt('publicSid');
        }

        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    public function getCurrentUserOrNull(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    public function getCurrentUser(): User
    {
        $user = $this->getCurrentUserOrNull();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    public function getSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
    {
        $survey = $this->getSurvey($surveyId);

        if ($this->isSurveyInContext($survey, $course, $session)) {
            return $survey;
        }

        throw new AccessDeniedHttpException('The requested survey does not belong to the current course context.');
    }

    public function getInvitation(CSurvey $survey, Course $course, ?Session $session, ?User $user, Request $request): CSurveyInvitation
    {
        $invitationCode = $this->getInvitationCode($request);
        if ('auto' === $invitationCode) {
            if ('1' === (string) $survey->getAnonymous() && !$user instanceof User) {
                return $this->getOrCreateAnonymousAutoInvitation($survey, $course, $session, $request);
            }

            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('A valid user is required.');
            }

            return $this->getOrCreateAutoInvitation($survey, $course, $session, $user);
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation')
            ->from(CSurveyInvitation::class, 'invitation')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->andWhere('invitation.lpItemId = :lpItemId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('lpItemId', $request->query->getInt('lpItemId'), Types::INTEGER)
        ;

        if ('' !== $invitationCode) {
            $queryBuilder
                ->andWhere('invitation.invitationCode = :invitationCode')
                ->setParameter('invitationCode', $invitationCode)
            ;
        }

        if ('1' !== (string) $survey->getAnonymous()) {
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('A valid user is required.');
            }

            $queryBuilder
                ->andWhere('IDENTITY(invitation.user) = :userId')
                ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
            ;
        } elseif ('' === $invitationCode) {
            throw new AccessDeniedHttpException('A valid survey invitation code is required.');
        }

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        $invitation = $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$invitation instanceof CSurveyInvitation) {
            throw new AccessDeniedHttpException('No valid survey invitation was found for this user.');
        }

        return $invitation;
    }

    public function getAnswerUserKey(CSurvey $survey, ?User $user, Request $request): string
    {
        if ('1' !== (string) $survey->getAnonymous()) {
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException('A valid user is required.');
            }

            return (string) $user->getId();
        }

        $session = $request->getSession();
        $sessionKey = 'survey_answer_user_'.(int) $survey->getIid();
        $answerUser = (string) $session->get($sessionKey, '');
        if ('' === $answerUser) {
            $answerUser = 'surveyuser_'.sha1(uniqid('', true));
            $session->set($sessionKey, $answerUser);
        }

        return $answerUser;
    }

    private function getCourseFromSurveyResource(CSurvey $survey): ?Course
    {
        if (!method_exists($survey, 'getResourceNode') || null === $survey->getResourceNode()) {
            return null;
        }

        foreach ($survey->getResourceNode()->getResourceLinks() as $resourceLink) {
            if (method_exists($resourceLink, 'getDeletedAt') && null !== $resourceLink->getDeletedAt()) {
                continue;
            }

            if (method_exists($resourceLink, 'getCourse') && $resourceLink->getCourse() instanceof Course) {
                return $resourceLink->getCourse();
            }
        }

        return null;
    }

    /**
     * @return array<int, CSurveyQuestion>
     */
    public function getOrderedQuestions(CSurvey $survey): array
    {
        return $this->entityManager->getRepository(CSurveyQuestion::class)->findBy(
            ['survey' => $survey],
            ['sort' => 'ASC'],
        );
    }

    /**
     * @return array<int, CSurveyQuestionOption>
     */
    public function getSortedOptions(CSurveyQuestion $question): array
    {
        $options = [];
        foreach ($question->getOptions() as $option) {
            if ($option instanceof CSurveyQuestionOption) {
                $options[] = $option;
            }
        }

        usort($options, static fn (CSurveyQuestionOption $first, CSurveyQuestionOption $second): int => $first->getSort() <=> $second->getSort());

        return $options;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestions(CSurvey $survey): array
    {
        $items = [];
        foreach ($this->getOrderedQuestions($survey) as $question) {
            $type = $question->getType();
            $items[] = [
                'iid' => (int) $question->getIid(),
                'question' => $question->getSurveyQuestion(),
                'comment' => (string) $question->getSurveyQuestionComment(),
                'type' => $type,
                'typeLabel' => $this->getTypeLabel($type),
                'display' => $question->getDisplay(),
                'sort' => $question->getSort(),
                'maxValue' => $question->getMaxValue(),
                'isRequired' => $this->isRequiredQuestionType($type) && $question->isMandatory(),
                'parentQuestionId' => null !== $question->getParent() ? (int) $question->getParent()->getIid() : 0,
                'parentOptionId' => null !== $question->getParentOption() ? (int) $question->getParentOption()->getIid() : 0,
                'options' => $this->normalizeOptions($question),
                'isSupported' => \in_array($type, self::SUPPORTED_TYPES, true),
            ];
        }

        return $items;
    }

    private function isRequiredQuestionType(string $type): bool
    {
        return \in_array($type, ['yesno', 'multiplechoice'], true);
    }

    private function normalizeOptions(CSurveyQuestion $question): array
    {
        $options = [];
        foreach ($this->getSortedOptions($question) as $option) {
            $optionText = $option->getOptionText();
            $options[] = [
                'iid' => (int) $option->getIid(),
                'text' => $optionText,
                'label' => trim(strip_tags($optionText)) ?: 'Option',
                'value' => $option->getValue(),
                'sort' => $option->getSort(),
                'isOther' => 'other' === trim(strtolower(strip_tags($optionText))),
            ];
        }

        return $options;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     *
     * @return array<int, array<int, int>>
     */
    private function buildPages(CSurvey $survey, array $questions): array
    {
        $pages = [];
        $currentPage = [];

        foreach ($questions as $question) {
            $questionId = (int) $question['iid'];
            if ('pagebreak' === $question['type']) {
                if ([] !== $currentPage) {
                    $pages[] = $currentPage;
                    $currentPage = [];
                }

                continue;
            }

            if ($survey->getOneQuestionPerPage()) {
                $pages[] = [$questionId];

                continue;
            }

            $currentPage[] = $questionId;
        }

        if ([] !== $currentPage) {
            $pages[] = $currentPage;
        }

        return [] === $pages ? [[]] : $pages;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSurvey(CSurvey $survey, Course $course, ?Session $session): array
    {
        return [
            'iid' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'subtitle' => $survey->getSubtitle(),
            'code' => (string) $survey->getCode(),
            'intro' => (string) $survey->getIntro(),
            'thanks' => (string) $survey->getSurveythanks(),
            'anonymous' => '1' === (string) $survey->getAnonymous(),
            'oneQuestionPerPage' => $survey->getOneQuestionPerPage(),
            'shuffle' => $survey->getShuffle(),
            'displayQuestionNumber' => $survey->isDisplayQuestionNumber(),
            'availableFrom' => $this->formatDate($survey->getAvailFrom()),
            'availableUntil' => $this->formatDate($survey->getAvailTill()),
            'surveyType' => $survey->getSurveyType(),
            'listRoute' => [
                'name' => 'SurveyList',
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
            'backwardsEnabled' => $this->isSettingEnabled('survey.survey_backwards_enable'),
            'allowAnsweredQuestionEdit' => $this->isSettingEnabled('survey.survey_allow_answered_question_edit'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExistingAnswers(CSurvey $survey, string $answerUserKey, Request $request): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CSurveyAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->andWhere('answer.user = :answerUser')
            ->andWhere('answer.lpItemId = :lpItemId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('answerUser', $answerUserKey)
            ->setParameter('lpItemId', $request->query->getInt('lpItemId'), Types::INTEGER)
        ;

        $sessionId = $request->query->getInt('sid');
        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('answer.sessionId IS NULL');
        }

        $answers = [];
        foreach ($queryBuilder->getQuery()->getResult() as $answer) {
            if (!$answer instanceof CSurveyAnswer) {
                continue;
            }

            $questionId = (string) $answer->getQuestion()->getIid();
            $type = $answer->getQuestion()->getType();
            $optionId = $answer->getOptionId();
            $otherText = '';
            if (str_contains($optionId, '@:@')) {
                [$optionId, $otherText] = explode('@:@', $optionId, 2);
            }

            if ('multipleresponse' === $type) {
                $answers[$questionId] ??= [];
                $answers[$questionId][] = (int) $optionId;
            } elseif ('score' === $type) {
                $answers[$questionId] ??= [];
                $answers[$questionId][(string) $optionId] = $answer->getValue();
            } elseif ('open' === $type || 'comment' === $type) {
                $answers[$questionId] = $optionId;
            } else {
                $answers[$questionId] = (int) $optionId;
                if ('' !== $otherText) {
                    $answers['other_'.$questionId] = $otherText;
                }
            }
        }

        return $answers;
    }

    private function getInvitationCode(Request $request): string
    {
        return trim((string) ($request->query->get('invitationCode') ?? $request->query->get('invitationcode') ?? ''));
    }

    private function getOrCreateAnonymousAutoInvitation(CSurvey $survey, Course $course, ?Session $session, Request $request): CSurveyInvitation
    {
        $sessionStorage = $request->getSession();
        $sessionKey = 'survey_auto_invitation_'.(int) $survey->getIid().'_'.$request->query->getInt('lpItemId');
        $code = (string) $sessionStorage->get($sessionKey, '');

        if ('' === $code) {
            $code = 'auto-ANONY_'.sha1(uniqid('', true)).'-'.(string) $survey->getCode();
            $sessionStorage->set($sessionKey, $code);
        }

        $request->query->set('invitationCode', $code);

        try {
            return $this->getInvitation($survey, $course, $session, null, $request);
        } catch (AccessDeniedHttpException) {
            $this->entityManager->getConnection()->insert('c_survey_invitation', [
                'c_id' => (int) $course->getId(),
                'survey_id' => (int) $survey->getIid(),
                'user_id' => null,
                'session_id' => null !== $session ? (int) $session->getId() : null,
                'invitation_code' => $code,
                'answered' => 0,
                'invitation_date' => (new DateTime())->format('Y-m-d H:i:s'),
                'c_lp_item_id' => $request->query->getInt('lpItemId'),
            ]);

            return $this->getInvitation($survey, $course, $session, null, $request);
        }
    }

    private function getOrCreateAutoInvitation(CSurvey $survey, Course $course, ?Session $session, User $user): CSurveyInvitation
    {
        $code = 'auto-'.(int) $user->getId().'-'.(string) $survey->getCode();
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        $request->query->set('invitationCode', $code);

        try {
            return $this->getInvitation($survey, $course, $session, $user, $request);
        } catch (AccessDeniedHttpException) {
            $invitation = new CSurveyInvitation();
            $invitation
                ->setSurvey($survey)
                ->setCourse($course)
                ->setSession($session)
                ->setUser($user)
                ->setInvitationCode($code)
                ->setAnswered(0)
                ->setLpItemId($request->query->getInt('lpItemId'))
            ;
            $this->entityManager->persist($invitation);
            $this->entityManager->flush();

            return $invitation;
        }
    }

    private function assertSurveyIsAvailable(CSurvey $survey): void
    {
        $now = new DateTime();
        $availableFrom = $survey->getAvailFrom();
        $availableUntil = $survey->getAvailTill();

        if (null !== $availableFrom && $availableFrom > $now) {
            throw new AccessDeniedHttpException('This survey is not open yet.');
        }

        if (null !== $availableUntil && $availableUntil < $now) {
            throw new AccessDeniedHttpException('This survey is already closed.');
        }
    }

    private function canPreviewSurveys(): bool
    {
        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return false;
        }

        return $this->isSettingEnabled('survey.extend_rights_for_coach_on_survey');
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

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
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

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }
}
