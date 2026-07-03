<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyInvitation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use MessageManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/**
 * @implements ProcessorInterface<SurveyInvitation, SurveyInvitation>
 */
final readonly class SurveyInvitationProcessor implements ProcessorInterface
{
    use SurveyPersonalitySupportTrait;
    use SurveyCsrfTokenValidationTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CGroupRepository $groupRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SurveyInvitationProvider $surveyInvitationProvider,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SurveyInvitation
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->surveyInvitationProvider->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage survey invitations in this context.');
        }

        $course = $this->surveyInvitationProvider->getCourse($request);
        $session = $this->surveyInvitationProvider->getSession($request);
        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->surveyInvitationProvider->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);
        $payload = $this->getPayload($request, $data);
        $this->validateSubmittedCsrfToken($request, $this->csrfTokenManager, SurveyInvitationProvider::CSRF_TOKEN_ID, $payload);
        $additionalEmails = $this->normalizeStringList($payload['additionalEmails'] ?? []);
        if ([] !== $additionalEmails) {
            throw new BadRequestHttpException('External email invitations are not supported by the current survey invitation entity.');
        }

        $sendMail = true === ($payload['sendMail'] ?? false);
        $mailSubject = trim((string) ($payload['mailSubject'] ?? ''));
        $mailText = trim((string) ($payload['mailText'] ?? ''));
        if ($sendMail && ('' === $mailSubject || '' === $mailText)) {
            throw new BadRequestHttpException('Mail subject and message are required when sending mail.');
        }

        $selectedUserIds = $this->normalizeIntegerList($payload['selectedUserIds'] ?? []);
        $selectedGroupIds = $this->normalizeIntegerList($payload['selectedGroupIds'] ?? []);
        $resendToAll = true === ($payload['resendToAll'] ?? false);
        $canRemindUnanswered = '1' !== (string) $survey->getAnonymous()
            || $this->isSettingEnabled('survey.survey_anonymous_show_answered');
        $remindUnanswered = $canRemindUnanswered && true === ($payload['remindUnanswered'] ?? false);
        $hideLink = true === ($payload['hideLink'] ?? false);

        $survey->setMailSubject($mailSubject);
        if ($remindUnanswered && '' !== $mailText) {
            $survey->setReminderMail($mailText);
        } elseif ('' !== $mailText) {
            $survey->setInviteMail($mailText);
        }

        $availableUsers = $this->getAvailableUserMap($course, $session);
        $groups = $this->getGroupMap($course, $session, $selectedGroupIds);
        $targets = $this->buildTargetUsers($selectedUserIds, $selectedGroupIds, $availableUsers, $groups);
        $existingInvitations = $this->getExistingInvitations($survey, $course, $session);

        $created = 0;
        $updated = 0;
        $sent = 0;

        foreach ($targets as $userId => $target) {
            $invitation = $existingInvitations[$userId] ?? null;
            $isNewInvitation = !$invitation instanceof CSurveyInvitation;

            if ($isNewInvitation) {
                $invitation = $this->createInvitation(
                    $target['user'],
                    $survey,
                    $course,
                    $session,
                    $target['group'] ?? null,
                );
                $existingInvitations[$userId] = $invitation;
                ++$created;
            } elseif (isset($target['group']) && $target['group'] instanceof CGroup && null === $invitation->getGroup()) {
                $invitation->setGroup($target['group']);
                ++$updated;
            }

            if ($sendMail && $this->shouldSendMail($invitation, $isNewInvitation, $resendToAll, $remindUnanswered)) {
                if ($this->sendInvitationMessage($invitation, $survey, $course, $session, $mailSubject, $mailText, $hideLink)) {
                    ++$sent;
                }
                $invitation->setReminderDate(new DateTime());
            }
        }

        $this->updateSurveyCounters($survey, $course, $session);
        $this->entityManager->flush();

        $message = \sprintf('Survey published. Created: %d. Updated: %d. Messages sent: %d.', $created, $updated, $sent);

        return $this->surveyInvitationProvider->buildResponse($survey, $course, $session, $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request, mixed $data): array
    {
        if ($data instanceof SurveyInvitation) {
            return [
                'csrfToken' => $data->csrfToken,
                'mailSubject' => $data->mailSubject,
                'mailText' => $data->mailText,
                'sendMail' => $data->sendMail,
                'resendToAll' => $data->resendToAll,
                'remindUnanswered' => $data->remindUnanswered,
                'hideLink' => $data->hideLink,
                'selectedUserIds' => $data->selectedUserIds,
                'selectedGroupIds' => $data->selectedGroupIds,
                'additionalEmails' => $data->additionalEmails,
            ];
        }

        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        $payload = json_decode($content, true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        return $payload;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(SurveyInvitationProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    /**
     * @return array<int, User>
     */
    private function getAvailableUserMap(Course $course, ?Session $session): array
    {
        $users = [];
        foreach ($this->getCourseStudentUsers($course, $session) as $user) {
            if ($user instanceof User && null !== $user->getId()) {
                $users[(int) $user->getId()] = $user;
            }
        }

        return $users;
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

        return $users;
    }

    /**
     * @param array<int, int> $selectedGroupIds
     *
     * @return array<int, CGroup>
     */
    private function getGroupMap(Course $course, ?Session $session, array $selectedGroupIds): array
    {
        if ([] === $selectedGroupIds) {
            return [];
        }

        try {
            $courseGroups = $this->groupRepository->findAllByCourse($course, $session)->getQuery()->getResult();
        } catch (Throwable) {
            throw new BadRequestHttpException('Course groups could not be loaded for this survey.');
        }

        $allowedGroups = [];
        foreach ($courseGroups as $group) {
            if ($group instanceof CGroup && null !== $group->getIid()) {
                $allowedGroups[(int) $group->getIid()] = $group;
            }
        }

        $groups = [];
        foreach ($selectedGroupIds as $groupId) {
            if (!isset($allowedGroups[$groupId])) {
                throw new BadRequestHttpException('One selected group does not belong to the current course context.');
            }
            $groups[$groupId] = $allowedGroups[$groupId];
        }

        return $groups;
    }

    /**
     * @param array<int, int>    $selectedUserIds
     * @param array<int, int>    $selectedGroupIds
     * @param array<int, User>   $availableUsers
     * @param array<int, CGroup> $groups
     *
     * @return array<int, array{user: User, group?: CGroup}>
     */
    private function buildTargetUsers(array $selectedUserIds, array $selectedGroupIds, array $availableUsers, array $groups): array
    {
        $targets = [];
        foreach ($selectedUserIds as $userId) {
            if (!isset($availableUsers[$userId])) {
                throw new BadRequestHttpException('One selected user does not belong to the current course context.');
            }
            $targets[$userId] = ['user' => $availableUsers[$userId]];
        }

        foreach ($selectedGroupIds as $groupId) {
            $group = $groups[$groupId] ?? null;
            if (!$group instanceof CGroup) {
                continue;
            }

            foreach ($group->getMembers() as $member) {
                if (!$member instanceof CGroupRelUser) {
                    continue;
                }

                $user = $member->getUser();
                if (!$user instanceof User || null === $user->getId()) {
                    continue;
                }

                $userId = (int) $user->getId();
                if (!isset($availableUsers[$userId])) {
                    continue;
                }

                $targets[$userId] = ['user' => $availableUsers[$userId], 'group' => $group];
            }
        }

        return $targets;
    }

    /**
     * @return array<int, CSurveyInvitation>
     */
    private function getExistingInvitations(CSurvey $survey, Course $course, ?Session $session): array
    {
        $invitations = $this->surveyInvitationProvider->getInvitations($survey, $course, $session);
        $items = [];

        foreach ($invitations as $invitation) {
            $user = $invitation->getUser();
            if ($user instanceof User && null !== $user->getId()) {
                $items[(int) $user->getId()] = $invitation;
            }
        }

        return $items;
    }

    private function createInvitation(
        User $user,
        CSurvey $survey,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CSurveyInvitation {
        $invitation = new CSurveyInvitation();
        $invitation
            ->setUser($user)
            ->setSurvey($survey)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setInvitationCode(md5((string) $user->getId().microtime()))
            ->setReminderDate(new DateTime())
        ;

        $this->entityManager->persist($invitation);

        return $invitation;
    }

    private function shouldSendMail(
        CSurveyInvitation $invitation,
        bool $isNewInvitation,
        bool $resendToAll,
        bool $remindUnanswered,
    ): bool {
        if ($isNewInvitation) {
            return true;
        }

        if ($resendToAll) {
            return true;
        }

        return $remindUnanswered && 1 !== (int) $invitation->getAnswered();
    }

    private function sendInvitationMessage(
        CSurveyInvitation $invitation,
        CSurvey $survey,
        Course $course,
        ?Session $session,
        string $subject,
        string $content,
        bool $hideLink,
    ): bool {
        if (!class_exists(MessageManager::class)) {
            return false;
        }

        $user = $invitation->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            return false;
        }

        $userId = (int) $user->getId();

        $link = $this->buildModernAnswerLink($survey, $invitation->getInvitationCode(), $course, $session);
        $body = $this->buildMailBody($content, $link, $hideLink);

        $messageId = MessageManager::send_message_simple((int) $userId, $subject, $body);

        return false !== $messageId && (int) $messageId > 0;
    }

    private function buildMailBody(string $content, string $link, bool $hideLink): string
    {
        if ($hideLink) {
            return str_replace('**link**', '', $content);
        }

        $linkHtml = '<a href="'.$link.'">Click here to answer the survey</a><br><br>'.$link;
        $replaceCount = 0;
        $body = str_ireplace('**link**', $linkHtml, $content, $replaceCount);

        if ($replaceCount < 1) {
            $body .= '<br><br>'.$linkHtml;
        }

        return $body;
    }

    private function updateSurveyCounters(CSurvey $survey, Course $course, ?Session $session): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(invitation.iid) AS invitedCount')
            ->addSelect('SUM(CASE WHEN invitation.answered = 1 THEN 1 ELSE 0 END) AS answeredCount')
            ->from(CSurveyInvitation::class, 'invitation')
            ->andWhere('IDENTITY(invitation.survey) = :surveyId')
            ->andWhere('IDENTITY(invitation.course) = :courseId')
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

        $row = $queryBuilder->getQuery()->getSingleResult();
        $survey->setInvited((int) ($row['invitedCount'] ?? 0));
        $survey->setAnswered((int) ($row['answeredCount'] ?? 0));
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIntegerList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $items[] = $id;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $text = trim((string) $item);
            if ('' !== $text) {
                $items[] = $text;
            }
        }

        return array_values(array_unique($items));
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
        }

        return \sprintf(
            '/resources/survey/%d/%d/%s?%s',
            $nodeId,
            (int) $survey->getIid(),
            $route,
            http_build_query($query),
        );
    }

    private function isSettingEnabled(string $name): bool
    {
        return $this->isEnabledValue($this->settingsManager->getSetting($name, true));
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
}
