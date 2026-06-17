<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyMeeting;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
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
 * @implements ProviderInterface<SurveyMeeting>
 */
final readonly class SurveyMeetingProvider implements ProviderInterface
{
    public const CSRF_TOKEN_ID = 'survey_meeting';

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyMeeting
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;

        if ($surveyId <= 0) {
            if (!$this->canManageSurveys()) {
                throw new AccessDeniedHttpException('You are not allowed to create meeting polls.');
            }

            return $this->buildCreateResponse($course, $session);
        }

        $survey = $this->getMeetingSurveyFromCurrentContext($surveyId, $course, $session);
        $mode = (string) $request->query->get('mode', 'answer');
        $isEditMode = 'edit' === $mode;

        if ($isEditMode && !$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to edit this meeting poll.');
        }

        return $this->buildResponse($survey, $course, $session, $request, $isEditMode);
    }

    public function buildCreateResponse(Course $course, ?Session $session): SurveyMeeting
    {
        $now = new DateTime();
        $later = (clone $now)->modify('+1 month');
        $response = new SurveyMeeting();
        $response->mode = 'create';
        $response->title = '';
        $response->description = '';
        $response->surveyLanguage = $this->getCourseLanguage($course);
        $response->availableFrom = $this->formatDate($now);
        $response->availableUntil = $this->formatDate($later);
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $response->canEdit = true;
        $response->canSubmit = false;
        $response->slots = $this->getDefaultSlots();
        $response->survey = [
            'surveyType' => 3,
            'listRoute' => [
                'name' => 'SurveyList',
                'query' => [
                    'cid' => (int) $course->getId(),
                    'sid' => null !== $session ? (int) $session->getId() : null,
                ],
            ],
        ];

        return $response;
    }

    public function buildResponse(
        CSurvey $survey,
        Course $course,
        ?Session $session,
        Request $request,
        bool $isEditMode = false,
        bool $isFinished = false,
        string $message = ''
    ): SurveyMeeting {
        $user = $this->getCurrentUser();
        $canManage = $this->canManageSurveys();
        $invitation = null;
        $selectedSlots = [];

        if (!$canManage || '' !== $this->getInvitationCode($request)) {
            $this->assertSurveyIsAvailable($survey);
            $invitation = $this->getInvitation($survey, $course, $session, $user, $request);
            $selectedSlots = $this->getSelectedSlots($survey, $user, $request);
        }

        $response = new SurveyMeeting();
        $response->surveyId = (int) $survey->getIid();
        $response->mode = $isEditMode ? 'edit' : 'answer';
        $response->title = $survey->getTitle();
        $response->description = (string) $survey->getIntro();
        $response->surveyLanguage = (string) $survey->getLang();
        $response->availableFrom = $this->formatDate($survey->getAvailFrom());
        $response->availableUntil = $this->formatDate($survey->getAvailTill());
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $response->slots = $this->getSlots($survey);
        $response->selectedSlots = $selectedSlots;
        $response->participants = $this->getParticipants($survey, $course, $session);
        $response->matrix = $this->getMatrix($survey, $response->participants, $session);
        $response->canEdit = $canManage;
        $response->canSubmit = null !== $invitation;
        $response->isAnswered = null !== $invitation && 1 === (int) $invitation->getAnswered();
        $response->isFinished = $isFinished;
        $response->message = $message;
        $response->survey = [
            'iid' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'code' => (string) $survey->getCode(),
            'surveyType' => $survey->getSurveyType(),
            'listRoute' => [
                'name' => 'SurveyList',
                'query' => [
                    'cid' => (int) $course->getId(),
                    'sid' => null !== $session ? (int) $session->getId() : null,
                ],
            ],
        ];

        return $response;
    }

    public function getCourse(Request $request): Course
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

    public function getSession(Request $request): ?Session
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

    public function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    public function getMeetingSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
    {
        $survey = $this->surveyRepository->find($surveyId);
        if (!$survey instanceof CSurvey) {
            throw new NotFoundHttpException('The requested survey was not found.');
        }

        if (3 !== $survey->getSurveyType()) {
            throw new BadRequestHttpException('The requested survey is not a meeting poll.');
        }

        if ($this->isSurveyInContext($survey, $course, $session)) {
            return $survey;
        }

        throw new AccessDeniedHttpException('The requested survey does not belong to the current course context.');
    }

    public function getInvitation(CSurvey $survey, Course $course, ?Session $session, User $user, Request $request): CSurveyInvitation
    {
        $invitationCode = $this->getInvitationCode($request);
        if ('auto' === $invitationCode) {
            return $this->getOrCreateAutoInvitation($survey, $course, $session, $user);
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation')
            ->from(CSurveyInvitation::class, 'invitation')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->andWhere('IDENTITY(invitation.user) = :userId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('userId', (int) $user->getId(), Types::INTEGER)
        ;

        if ('' !== $invitationCode) {
            $queryBuilder
                ->andWhere('invitation.invitationCode = :invitationCode')
                ->setParameter('invitationCode', $invitationCode)
            ;
        }

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        $invitation = $queryBuilder->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$invitation instanceof CSurveyInvitation) {
            throw new AccessDeniedHttpException('No valid survey invitation was found for this user.');
        }

        return $invitation;
    }

    /**
     * @return array<int, CSurveyQuestion>
     */
    public function getOrderedSlots(CSurvey $survey): array
    {
        return $this->entityManager->getRepository(CSurveyQuestion::class)->findBy(
            ['survey' => $survey, 'type' => 'doodle'],
            ['sort' => 'ASC'],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSlots(CSurvey $survey): array
    {
        $slots = [];
        foreach ($this->getOrderedSlots($survey) as $question) {
            [$start, $end] = $this->parseSlotValue($question->getSurveyQuestion());
            $slots[] = [
                'id' => (int) $question->getIid(),
                'start' => $this->formatDate($start),
                'end' => $this->formatDate($end),
                'label' => $this->formatSlotLabel($start, $end),
                'sort' => $question->getSort(),
            ];
        }

        return $slots;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultSlots(): array
    {
        $base = new DateTime('tomorrow 09:00');
        $slots = [];
        for ($i = 0; $i < 3; $i++) {
            $start = (clone $base)->modify(\sprintf('+%d day', $i));
            $end = (clone $start)->modify('+1 hour');
            $slots[] = [
                'id' => null,
                'start' => $this->formatDate($start),
                'end' => $this->formatDate($end),
                'label' => $this->formatSlotLabel($start, $end),
                'sort' => $i + 1,
            ];
        }

        return $slots;
    }

    /**
     * @return array<int, int>
     */
    private function getSelectedSlots(CSurvey $survey, User $user, Request $request): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(answer.question) AS questionId')
            ->from(CSurveyAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->andWhere('answer.user = :userId')
            ->andWhere('answer.value = 1')
            ->andWhere('answer.lpItemId = :lpItemId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('userId', (string) $user->getId())
            ->setParameter('lpItemId', $request->query->getInt('lpItemId'), Types::INTEGER)
        ;

        $sessionId = $request->query->getInt('sid');
        if ($sessionId > 0) {
            $rows
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $rows->andWhere('answer.sessionId IS NULL');
        }

        return array_map('intval', array_column($rows->getQuery()->getArrayResult(), 'questionId'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getParticipants(CSurvey $survey, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation', 'user')
            ->from(CSurveyInvitation::class, 'invitation')
            ->innerJoin('invitation.user', 'user')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->orderBy('user.lastname', 'ASC')
            ->addOrderBy('user.firstname', 'ASC')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        $participants = [];
        foreach ($queryBuilder->getQuery()->getResult() as $invitation) {
            if (!$invitation instanceof CSurveyInvitation) {
                continue;
            }

            $user = $invitation->getUser();
            $participants[] = [
                'id' => (int) $user->getId(),
                'name' => $this->getUserName($user, $survey),
                'answered' => 1 === (int) $invitation->getAnswered(),
            ];
        }

        return $participants;
    }

    /**
     * @param array<int, array<string, mixed>> $participants
     *
     * @return array<string, mixed>
     */
    private function getMatrix(CSurvey $survey, array $participants, ?Session $session): array
    {
        $participantIds = array_map(static fn (array $participant): int => (int) $participant['id'], $participants);
        if ([] === $participantIds) {
            return [
                'answers' => [],
                'totals' => [],
            ];
        }

        $answers = [];
        $totals = [];
        foreach ($this->getOrderedSlots($survey) as $slot) {
            $totals[(int) $slot->getIid()] = 0;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CSurveyAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->andWhere('answer.user IN (:participantIds)')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->setParameter('participantIds', array_map('strval', $participantIds), ArrayParameterType::STRING)
        ;

        if (null === $session) {
            $queryBuilder->andWhere('answer.sessionId IS NULL');
        } else {
            $queryBuilder
                ->andWhere('answer.sessionId = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        }

        $rows = $queryBuilder->getQuery()->getResult();

        foreach ($rows as $answer) {
            if (!$answer instanceof CSurveyAnswer) {
                continue;
            }

            $userId = (int) $answer->getUser();
            $slotId = (int) $answer->getQuestion()->getIid();
            $value = (int) $answer->getValue();
            $answers[$userId] ??= [];
            $answers[$userId][$slotId] = $value;
            if (1 === $value) {
                $totals[$slotId] = ($totals[$slotId] ?? 0) + 1;
            }
        }

        return [
            'answers' => $answers,
            'totals' => $totals,
        ];
    }

    private function getUserName(User $user, CSurvey $survey): string
    {
        if ('1' === (string) $survey->getAnonymous()) {
            return 'Anonymous';
        }

        if (method_exists($user, 'getFullName')) {
            return (string) $user->getFullName();
        }

        return trim((string) $user->getFirstname().' '.(string) $user->getLastname()) ?: (string) $user->getUsername();
    }

    private function getInvitationCode(Request $request): string
    {
        return trim((string) ($request->query->get('invitationCode') ?? $request->query->get('invitationcode') ?? ''));
    }

    private function getOrCreateAutoInvitation(CSurvey $survey, Course $course, ?Session $session, User $user): CSurveyInvitation
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $code = 'auto-'.(int) $user->getId().'-'.(string) $survey->getCode();
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

    /**
     * @return array{0: DateTime, 1: DateTime}
     */
    private function parseSlotValue(string $value): array
    {
        $parts = explode('@@', $value, 2);
        $start = new DateTime($parts[0] ?? 'now');
        $end = new DateTime($parts[1] ?? ($parts[0] ?? 'now'));

        return [$start, $end];
    }

    private function formatSlotLabel(DateTimeInterface $start, DateTimeInterface $end): string
    {
        return $start->format('Y-m-d H:i').' - '.$end->format('H:i');
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }

    private function getCourseLanguage(Course $course): string
    {
        if (method_exists($course, 'getCourseLanguage')) {
            return (string) $course->getCourseLanguage();
        }

        return '';
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

    private function canManageSurveys(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

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
}
