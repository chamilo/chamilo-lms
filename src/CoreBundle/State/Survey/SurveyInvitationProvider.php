<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyInvitation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
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
use Throwable;

/**
 * @implements ProviderInterface<SurveyInvitation>
 */
final readonly class SurveyInvitationProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;

    public const CSRF_TOKEN_ID = 'survey_invitation';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private CGroupRepository $groupRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyInvitation
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);

        return $this->buildResponse($survey, $course, $session);
    }

    public function buildResponse(CSurvey $survey, Course $course, ?Session $session, string $message = ''): SurveyInvitation
    {
        if (!$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage survey invitations in this context.');
        }

        $invitations = $this->getInvitations($survey, $course, $session);
        $counts = $this->buildCounts($survey, $invitations);

        $response = new SurveyInvitation();
        $response->surveyId = (int) $survey->getIid();
        $response->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $response->canManage = true;
        $response->message = $message;
        $response->survey = $this->normalizeSurvey($survey);
        $response->settings = $this->getSettings($survey);
        $response->counts = $counts;
        $response->users = $this->getAvailableUsers($course, $session, $invitations);
        $response->groups = $this->getAvailableGroups($course, $session, $invitations);
        $response->invitations = $this->normalizeInvitations($survey, $course, $session, $invitations);
        $response->mailSubject = $survey->getMailSubject();
        $response->mailText = '' !== trim($survey->getReminderMail()) ? $survey->getReminderMail() : $survey->getInviteMail();
        $response->anonymousLink = $this->buildAutoAnswerLink($survey, $course, $session);
        $response->selectedUserIds = $this->getSelectedUserIds($invitations);
        $response->selectedGroupIds = $this->getSelectedGroupIds($invitations);

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

    public function canManageSurveys(): bool
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

    /**
     * @return array<int, CSurveyInvitation>
     */
    public function getInvitations(CSurvey $survey, Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('invitation', 'user', 'invitationGroup')
            ->from(CSurveyInvitation::class, 'invitation')
            ->innerJoin('invitation.user', 'user')
            ->leftJoin('invitation.group', 'invitationGroup')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
            ->orderBy('user.lastname', 'ASC')
            ->addOrderBy('user.firstname', 'ASC')
            ->setParameter('surveyId', (int) $survey->getIid())
            ->setParameter('courseId', (int) $course->getId())
        ;

        if (null === $session) {
            $queryBuilder->andWhere('invitation.session IS NULL');
        } else {
            $queryBuilder
                ->andWhere('IDENTITY(invitation.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId())
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function isSurveyInContext(CSurvey $survey, Course $course, ?Session $session): bool
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
                ->setParameter('surveyId', (int) $survey->getIid())
            ;

            if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableUsers(Course $course, ?Session $session, array $invitations): array
    {
        $invitedUserIds = $this->getSelectedUserIds($invitations);
        $users = [];

        foreach ($this->getCourseStudentUsers($course, $session) as $user) {
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }

            $userId = (int) $user->getId();
            $users[$userId] = [
                'id' => $userId,
                'name' => $this->formatUserName($user),
                'email' => $user->getEmail(),
                'invited' => \in_array($userId, $invitedUserIds, true),
            ];
        }

        uasort($users, static fn (array $first, array $second): int => strcasecmp((string) $first['name'], (string) $second['name']));

        return array_values($users);
    }

    /**
     * @return array<int, User>
     */
    private function getCourseStudentUsers(Course $course, ?Session $session): array
    {
        if (null === $session) {
            return $this->getBaseCourseStudentUsers($course);
        }

        return $this->getSessionCourseStudentUsers($course, $session);
    }

    /**
     * @return array<int, User>
     */
    private function getBaseCourseStudentUsers(Course $course): array
    {
        $subscriptions = $this->entityManager->createQueryBuilder()
            ->select('subscription', 'u')
            ->from(CourseRelUser::class, 'subscription')
            ->innerJoin('subscription.user', 'u')
            ->andWhere('subscription.course = :course')
            ->andWhere('subscription.status = :student')
            ->andWhere('u.active = :active')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setParameter('course', (int) $course->getId())
            ->setParameter('student', CourseRelUser::STUDENT, Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        return $this->extractUsersFromSubscriptions($subscriptions);
    }

    /**
     * @return array<int, User>
     */
    private function getSessionCourseStudentUsers(Course $course, Session $session): array
    {
        $subscriptions = $this->entityManager->createQueryBuilder()
            ->select('subscription', 'u')
            ->from(SessionRelCourseRelUser::class, 'subscription')
            ->innerJoin('subscription.user', 'u')
            ->andWhere('subscription.course = :course')
            ->andWhere('subscription.session = :session')
            ->andWhere('subscription.status = :student')
            ->andWhere('u.active = :active')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setParameter('course', (int) $course->getId())
            ->setParameter('session', (int) $session->getId())
            ->setParameter('student', Session::STUDENT, Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        return $this->extractUsersFromSubscriptions($subscriptions);
    }

    /**
     * @param array<int, object> $subscriptions
     *
     * @return array<int, User>
     */
    private function extractUsersFromSubscriptions(array $subscriptions): array
    {
        $users = [];
        foreach ($subscriptions as $subscription) {
            if (!method_exists($subscription, 'getUser')) {
                continue;
            }

            $user = $subscription->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }

            $users[(int) $user->getId()] = $user;
        }

        return array_values($users);
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAvailableGroups(Course $course, ?Session $session, array $invitations): array
    {
        $selectedGroupIds = $this->getSelectedGroupIds($invitations);
        $groups = [];

        try {
            $courseGroups = $this->groupRepository->findAllByCourse($course, $session)->getQuery()->getResult();
        } catch (Throwable) {
            return [];
        }

        foreach ($courseGroups as $group) {
            if (!$group instanceof CGroup || null === $group->getIid()) {
                continue;
            }

            $groupId = (int) $group->getIid();
            $groups[] = [
                'id' => $groupId,
                'title' => $group->getTitle(),
                'memberCount' => $group->getMembers()->count(),
                'selected' => \in_array($groupId, $selectedGroupIds, true),
            ];
        }

        return $groups;
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeInvitations(CSurvey $survey, Course $course, ?Session $session, array $invitations): array
    {
        $items = [];
        $showAnsweredDetails = !$this->isAnonymous($survey) || $this->isSettingEnabled('survey.survey_anonymous_show_answered');

        foreach ($invitations as $invitation) {
            $user = $invitation->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }

            $group = $invitation->getGroup();
            $answered = 1 === (int) $invitation->getAnswered();
            $visibleAnswered = $showAnsweredDetails && $answered;

            $items[] = [
                'id' => $invitation->getIid(),
                'userId' => (int) $user->getId(),
                'userName' => $this->formatUserName($user),
                'email' => $user->getEmail(),
                'groupId' => $group?->getIid(),
                'groupTitle' => $group?->getTitle(),
                'answered' => $visibleAnswered,
                'answeredHidden' => false,
                'invitationDate' => $this->formatDate($invitation->getInvitationDate()),
                'reminderDate' => $this->formatDate($invitation->getReminderDate()),
                'answeredAt' => null,
                'invitationCode' => $invitation->getInvitationCode(),
                'answerUrl' => $this->buildModernAnswerLink($survey, $invitation->getInvitationCode(), $course, $session),
            ];
        }

        return $items;
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     *
     * @return array<string, int>
     */
    private function buildCounts(CSurvey $survey, array $invitations): array
    {
        $answered = 0;
        foreach ($invitations as $invitation) {
            if (1 === (int) $invitation->getAnswered()) {
                ++$answered;
            }
        }

        if ($this->isAnonymous($survey) && !$this->isSettingEnabled('survey.survey_anonymous_show_answered') && $answered < 2) {
            $answered = 0;
        }

        return [
            'invited' => \count($invitations),
            'answered' => $answered,
            'unanswered' => \count($invitations) - $answered,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSurvey(CSurvey $survey): array
    {
        return [
            'iid' => (int) $survey->getIid(),
            'code' => $survey->getCode(),
            'title' => $survey->getTitle(),
            'subtitle' => $survey->getSubtitle(),
            'anonymous' => $this->isAnonymous($survey),
            'surveyType' => $survey->getSurveyType(),
            'availableFrom' => $this->formatDate($survey->getAvailFrom()),
            'availableUntil' => $this->formatDate($survey->getAvailTill()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(CSurvey $survey): array
    {
        $anonymous = $this->isAnonymous($survey);
        $anonymousShowAnswered = $this->isSettingEnabled('survey.survey_anonymous_show_answered');

        return [
            'anonymous' => $anonymous,
            'anonymousShowAnswered' => $anonymousShowAnswered,
            'canShowAnsweredDetails' => !$anonymous || $anonymousShowAnswered,
            'canRemindUnanswered' => !$anonymous || $anonymousShowAnswered,
            'hideReportingButton' => $this->isSettingEnabled('survey.hide_survey_reporting_button'),
            'externalEmailsSupported' => false,
            'mailSendingSupported' => class_exists('MessageManager'),
        ];
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     *
     * @return array<int, int>
     */
    private function getSelectedUserIds(array $invitations): array
    {
        $ids = [];
        foreach ($invitations as $invitation) {
            $user = $invitation->getUser();
            if ($user instanceof User && null !== $user->getId()) {
                $ids[] = (int) $user->getId();
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, CSurveyInvitation> $invitations
     *
     * @return array<int, int>
     */
    private function getSelectedGroupIds(array $invitations): array
    {
        $ids = [];
        foreach ($invitations as $invitation) {
            $group = $invitation->getGroup();
            if (null !== $group?->getIid()) {
                $ids[] = (int) $group->getIid();
            }
        }

        return array_values(array_unique($ids));
    }

    private function isSettingEnabled(string $name): bool
    {
        return $this->isEnabledValue($this->settingsManager->getSetting($name, true));
    }

    private function formatUserName(User $user): string
    {
        $name = trim((string) $user->getFirstname().' '.(string) $user->getLastname());

        return '' !== $name ? $name : $user->getUsername();
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }

    private function isAnonymous(CSurvey $survey): bool
    {
        return $this->isEnabledValue($survey->getAnonymous());
    }

    private function isEnabledValue(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function buildAutoAnswerLink(CSurvey $survey, Course $course, ?Session $session): string
    {
        return $this->buildModernAnswerLink($survey, 'auto', $course, $session);
    }

    private function buildModernAnswerLink(CSurvey $survey, string $invitationCode, Course $course, ?Session $session): string
    {
        $nodeId = method_exists($survey, 'getResourceNode') && null !== $survey->getResourceNode()
            ? (int) $survey->getResourceNode()->getId()
            : (int) $course->getId();
        $route = 3 === $survey->getSurveyType() ? 'meeting' : 'answer';

        $query = [
            'invitationCode' => $invitationCode,
        ];

        if ($this->isAnonymous($survey)) {
            $query['publicCid'] = (int) $course->getId();
            $query['publicSid'] = (int) ($session?->getId() ?? 0);
            $query['publicGid'] = 0;
        } else {
            $query['cid'] = (int) $course->getId();
            $query['sid'] = (int) ($session?->getId() ?? 0);
            $query['gid'] = 0;
        }

        return \sprintf(
            '/resources/survey/%d/%d/%s?%s',
            $nodeId,
            (int) $survey->getIid(),
            $route,
            http_build_query($query),
        );
    }
}
