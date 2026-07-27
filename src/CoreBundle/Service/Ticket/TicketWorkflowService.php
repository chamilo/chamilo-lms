<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Ticket;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketAssignedLog;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketCategoryRelUser;
use Chamilo\CoreBundle\Entity\TicketMessage;
use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\TicketProjectHelper;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\TicketRelUserRepository;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use SessionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TicketManager;
use UserManager;

use const COURSEMANAGERLOWSECURITY;
use const DATE_ATOM;
use const ENT_QUOTES;
use const FILTER_VALIDATE_EMAIL;
use const STR_PAD_LEFT;

final readonly class TicketWorkflowService
{
    public const string CSRF_TOKEN_ID = 'ticket_workflow';
    public const int MAX_ATTACHMENTS = 6;

    private const int PRIORITY_NORMAL = 1;
    private const string SOURCE_PLATFORM = 'PLA';
    private const int STATUS_PENDING = 2;
    private const int STATUS_UNCONFIRMED = 3;
    private const int STATUS_CLOSED = 4;
    private const int STATUS_FORWARDED = 5;
    private const int STATUS_NEW = 1;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private AccessUrlHelper $accessUrlHelper,
        private TicketProjectHelper $ticketProjectHelper,
        private TicketRelUserRepository $ticketRelUserRepository,
        private TicketMessageAttachmentRepository $attachmentRepository,
        private UploadFilenamePolicy $uploadFilenamePolicy,
    ) {}

    public function assertCanCreate(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ('true' !== $this->settingsManager->getSetting('ticket.ticket_allow_student_add')) {
            throw new AccessDeniedHttpException('You are not allowed to create tickets.');
        }
    }

    public function getTicketForCurrentAccessUrl(int $ticketId): Ticket
    {
        $ticket = $this->entityManager->getRepository(Ticket::class)->find($ticketId);
        if (!$ticket instanceof Ticket) {
            throw new NotFoundHttpException('The requested ticket was not found.');
        }

        $accessUrl = $this->getCurrentAccessUrl();
        if ($ticket->getAccessUrl()?->getId() !== $accessUrl->getId()) {
            throw new NotFoundHttpException('The requested ticket was not found for this access URL.');
        }

        return $ticket;
    }

    public function assertCanView(Ticket $ticket, User $user): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $userId = (int) $user->getId();
        $projectId = (int) $ticket->getProject()->getId();
        $isCreator = $userId === $ticket->getInsertUserId();
        $isAssignee = $userId === (int) ($ticket->getAssignedLastUser()?->getId() ?? 0);

        if ($this->ticketProjectHelper->userIsAllowInProject($projectId) || $isCreator || $isAssignee) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to access this ticket.');
    }

    public function assertCanReply(Ticket $ticket, User $user): void
    {
        $this->assertCanView($ticket, $user);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $userId = (int) $user->getId();
        $isCreator = $userId === $ticket->getInsertUserId();
        $isAssignee = $userId === (int) ($ticket->getAssignedLastUser()?->getId() ?? 0);

        if (($isCreator || $isAssignee) && self::STATUS_CLOSED !== (int) $ticket->getStatus()->getId()) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to reply to this ticket.');
    }

    public function assertCanClose(Ticket $ticket, User $user): void
    {
        $this->assertCanView($ticket, $user);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $userId = (int) $user->getId();
        if ($userId === $ticket->getInsertUserId()
            || $userId === (int) ($ticket->getAssignedLastUser()?->getId() ?? 0)
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to close this ticket.');
    }

    /**
     * @param array<string, mixed>     $data
     * @param array<int, UploadedFile> $files
     */
    public function createTicket(User $user, array $data, array $files): Ticket
    {
        $this->assertCanCreate();
        $accessUrl = $this->getCurrentAccessUrl();
        $project = $this->getProject((int) ($data['projectId'] ?? 0), $accessUrl);
        $category = $this->getCategory((int) ($data['categoryId'] ?? 0), $project);
        $subject = mb_substr(trim((string) ($data['subject'] ?? '')), 0, 255);
        $content = trim((string) ($data['content'] ?? ''));

        if ('' === $subject || '' === trim(strip_tags($content))) {
            throw new BadRequestHttpException('Subject and message are required.');
        }

        $courseId = max(0, (int) ($data['courseId'] ?? 0));
        $sessionId = max(0, (int) ($data['sessionId'] ?? 0));
        if ($category->isCourseRequired() && $courseId <= 0) {
            throw new BadRequestHttpException('A course is required for this category.');
        }

        $course = $courseId > 0 ? $this->entityManager->getRepository(Course::class)->find($courseId) : null;
        if ($courseId > 0 && !$course instanceof Course) {
            throw new BadRequestHttpException('The selected course is invalid.');
        }

        $session = $sessionId > 0 ? $this->entityManager->getRepository(Session::class)->find($sessionId) : null;
        if ($sessionId > 0 && !$session instanceof Session) {
            throw new BadRequestHttpException('The selected session is invalid.');
        }

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $this->assertCourseAndSessionSelectionIsAllowed($user, $courseId, $sessionId, $isAdmin);
        $priorityId = $isAdmin ? max(0, (int) ($data['priorityId'] ?? 0)) : self::PRIORITY_NORMAL;
        $statusId = $isAdmin ? max(0, (int) ($data['statusId'] ?? 0)) : 0;
        $priority = $this->getPriority($priorityId ?: self::PRIORITY_NORMAL, $accessUrl);
        $status = $this->getStatus(
            $statusId ?: ($project->getOtherArea() > 0 ? self::STATUS_FORWARDED : self::STATUS_NEW),
            $accessUrl,
        );
        $assignee = null;

        if ($isAdmin && (int) ($data['assignedUserId'] ?? 0) > 0) {
            $assignee = $this->getAssignableUser((int) $data['assignedUserId'], $accessUrl);
        }

        if (!$assignee instanceof User) {
            $assignee = $this->getFirstCategoryUser($category, $accessUrl);
        }

        $validatedFiles = $this->validateFiles($files);
        $phone = trim((string) ($data['phone'] ?? ''));
        $message = (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        if ('' !== $phone) {
            $message .= '<p><strong>'.get_lang('Phone').':</strong> '
                .htmlspecialchars($phone, ENT_QUOTES, 'UTF-8').'</p>';
        }

        $now = new DateTime('now');
        $source = $isAdmin
            ? strtoupper(trim((string) ($data['source'] ?? self::SOURCE_PLATFORM)))
            : self::SOURCE_PLATFORM;
        if (!\in_array($source, ['PLA', 'MAI', 'TEL', 'PRE'], true)) {
            $source = self::SOURCE_PLATFORM;
        }

        $personalEmail = mb_substr(trim((string) ($data['personalEmail'] ?? '')), 0, 255);
        if ('' !== $personalEmail && false === filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('The personal email address is invalid.');
        }

        $ticket = (new Ticket())
            ->setProject($project)
            ->setCategory($category)
            ->setPriority($priority)
            ->setStatus($status)
            ->setCourse($course instanceof Course ? $course : null)
            ->setSession($session instanceof Session ? $session : null)
            ->setPersonalEmail($personalEmail)
            ->setAssignedLastUser($assignee)
            ->setSubject($subject)
            ->setMessage($message)
            ->setCode('')
            ->setSource($source)
            ->setTotalMessages(0)
            ->setStartDate($now)
            ->setInsertUserId((int) $user->getId())
            ->setInsertDateTime($now)
            ->setLastEditUserId((int) $user->getId())
            ->setLastEditDateTime($now)
            ->setAccessUrl($accessUrl)
        ;

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $ticketId = (int) $ticket->getId();
        $ticket->setCode('A'.str_pad((string) $ticketId, 11, '0', STR_PAD_LEFT));
        $category->setTotalTickets($category->getTotalTickets() + 1);
        $this->ticketRelUserRepository->subscribeUserToTicket($user, $ticket);

        if ($assignee instanceof User) {
            $this->addAssignmentLog($ticket, $assignee, $user, $now);
        }

        if ([] !== $validatedFiles) {
            $attachmentMessage = $this->createMessage($ticket, $user, '', '', $now);
            $ticket->setTotalMessages(1);
            $this->entityManager->flush();
            $this->saveAttachments($ticket, $attachmentMessage, $user, $validatedFiles);
        }

        $this->entityManager->flush();
        Event::addEvent(
            'ticket_subscribe',
            'ticket_event',
            ['user_id' => (int) $user->getId(), 'ticket_id' => $ticketId, 'action' => 'subscribe'],
        );
        $this->notifyTicketCreated($ticket, $user, $category, $personalEmail);

        return $ticket;
    }

    /**
     * @param array<string, mixed>     $data
     * @param array<int, UploadedFile> $files
     */
    public function replyToTicket(Ticket $ticket, User $user, array $data, array $files): TicketMessage
    {
        $this->assertCanReply($ticket, $user);
        $validatedFiles = $this->validateFiles($files);
        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        $content = trim((string) ($data['content'] ?? ''));
        $subject = mb_substr(trim((string) ($data['subject'] ?? '')), 0, 255);
        $auditParts = [];
        $now = new DateTime('now');

        if ($isAdmin) {
            $accessUrl = $this->getCurrentAccessUrl();
            $assignedUserId = max(0, (int) ($data['assignedUserId'] ?? 0));
            $currentAssignedUserId = (int) ($ticket->getAssignedLastUser()?->getId() ?? 0);
            if (\array_key_exists('assignedUserId', $data) && $assignedUserId !== $currentAssignedUserId) {
                $oldLabel = $ticket->getAssignedLastUser()?->getFullName() ?: get_lang('Unassigned');
                $newLabel = get_lang('Unassigned');

                if ($assignedUserId > 0) {
                    $assignee = $this->getAssignableUser($assignedUserId, $accessUrl);
                    $ticket->setAssignedLastUser($assignee);
                    $this->addAssignmentLog($ticket, $assignee, $user, $now);
                    $newLabel = $assignee->getFullName();
                } else {
                    $ticket->setAssignedLastUser(null);
                    $this->addAssignmentLog($ticket, null, $user, $now);
                }

                $auditParts[] = \sprintf(
                    get_lang('Assignee changed from %s to %s'),
                    htmlspecialchars($oldLabel, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($newLabel, ENT_QUOTES, 'UTF-8'),
                );
            }

            $priorityId = max(0, (int) ($data['priorityId'] ?? 0));
            if ($priorityId > 0 && $priorityId !== (int) $ticket->getPriority()->getId()) {
                $oldPriority = $ticket->getPriority()->getTitle();
                $newPriority = $this->getPriority($priorityId, $accessUrl);
                $ticket->setPriority($newPriority);
                $auditParts[] = \sprintf(
                    get_lang('Priority changed from %s to %s'),
                    htmlspecialchars((string) $oldPriority, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string) $newPriority->getTitle(), ENT_QUOTES, 'UTF-8'),
                );
            }

            $requestConfirmation = $this->normalizeBoolean($data['requestConfirmation'] ?? false);
            $statusId = $requestConfirmation
                ? self::STATUS_UNCONFIRMED
                : max(0, (int) ($data['statusId'] ?? 0));
            if ($statusId > 0 && $statusId !== (int) $ticket->getStatus()->getId()) {
                $oldStatus = $ticket->getStatus()->getTitle();
                $newStatus = $this->getStatus($statusId, $accessUrl);
                $ticket->setStatus($newStatus);
                $ticket->setEndDate(self::STATUS_CLOSED === $statusId ? $now : null);
                $auditParts[] = \sprintf(
                    get_lang('Status changed from %s to %s'),
                    htmlspecialchars((string) $oldStatus, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars((string) $newStatus->getTitle(), ENT_QUOTES, 'UTF-8'),
                );
            }
        }

        $messageHtml = implode('<br />', $auditParts);
        $safeContent = (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        if ('' !== $safeContent) {
            $messageHtml .= ('' !== $messageHtml ? '<br />' : '').$safeContent;
        }

        if ('' === trim(strip_tags($messageHtml)) && [] === $validatedFiles) {
            throw new BadRequestHttpException('A message, attachment or administrative change is required.');
        }

        $message = $this->createMessage($ticket, $user, $subject, $messageHtml, $now);
        $ticket
            ->setTotalMessages($ticket->getTotalMessages() + 1)
            ->setLastEditUserId((int) $user->getId())
            ->setLastEditDateTime($now)
        ;
        $this->entityManager->flush();
        $this->saveAttachments($ticket, $message, $user, $validatedFiles);

        if (!$this->ticketRelUserRepository->isUserSubscribedToTicket($user, $ticket)) {
            $this->ticketRelUserRepository->subscribeUserToTicket($user, $ticket);
        }

        TicketManager::sendNotification(
            (int) $ticket->getId(),
            get_lang('Ticket updated'),
            $messageHtml,
        );

        return $message;
    }

    public function subscribe(Ticket $ticket, User $user): void
    {
        $this->assertCanView($ticket, $user);
        $this->ticketRelUserRepository->subscribeUserToTicket($user, $ticket);
        Event::addEvent(
            'ticket_subscribe',
            'ticket_event',
            ['user_id' => (int) $user->getId(), 'ticket_id' => (int) $ticket->getId(), 'action' => 'subscribe'],
        );
    }

    public function unsubscribe(Ticket $ticket, User $user): void
    {
        $this->assertCanView($ticket, $user);
        $this->ticketRelUserRepository->unsubscribeUserFromTicket($user, $ticket);
        Event::addEvent(
            'ticket_unsubscribe',
            'ticket_event',
            ['user_id' => (int) $user->getId(), 'ticket_id' => (int) $ticket->getId(), 'action' => 'unsubscribe'],
        );
    }

    public function close(Ticket $ticket, User $user): void
    {
        $this->assertCanClose($ticket, $user);
        if (self::STATUS_CLOSED === (int) $ticket->getStatus()->getId()) {
            return;
        }

        $status = $this->getStatus(self::STATUS_CLOSED, $this->getCurrentAccessUrl());
        $now = new DateTime('now');
        $ticket
            ->setStatus($status)
            ->setEndDate($now)
            ->setLastEditUserId((int) $user->getId())
            ->setLastEditDateTime($now)
        ;
        $this->entityManager->flush();

        TicketManager::sendNotification(
            (int) $ticket->getId(),
            get_lang('Ticket closed'),
            get_lang('Ticket closed'),
        );
    }

    public function respondToConfirmation(Ticket $ticket, User $user, bool $confirmed): void
    {
        $this->assertCanView($ticket, $user);

        if ((int) $ticket->getInsertUserId() !== (int) $user->getId()) {
            throw new AccessDeniedHttpException('Only the ticket reporter can answer this confirmation request.');
        }

        if (self::STATUS_UNCONFIRMED !== (int) $ticket->getStatus()->getId()) {
            throw new ConflictHttpException('This ticket no longer has a pending confirmation request.');
        }

        $accessUrl = $this->getCurrentAccessUrl();
        $oldStatus = $ticket->getStatus();
        $newStatus = $this->getStatus(
            $confirmed ? self::STATUS_CLOSED : self::STATUS_PENDING,
            $accessUrl,
        );
        $now = new DateTime('now');
        $messageHtml = \sprintf(
            get_lang('Status changed from %s to %s'),
            htmlspecialchars((string) $oldStatus->getTitle(), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $newStatus->getTitle(), ENT_QUOTES, 'UTF-8'),
        );

        $this->createMessage(
            $ticket,
            $user,
            get_lang('Re:').' '.$ticket->getSubject(),
            $messageHtml,
            $now,
        );
        $ticket
            ->setStatus($newStatus)
            ->setEndDate($confirmed ? $now : null)
            ->setTotalMessages($ticket->getTotalMessages() + 1)
            ->setLastEditUserId((int) $user->getId())
            ->setLastEditDateTime($now)
        ;
        $this->entityManager->flush();

        if (!$this->ticketRelUserRepository->isUserSubscribedToTicket($user, $ticket)) {
            $this->ticketRelUserRepository->subscribeUserToTicket($user, $ticket);
        }

        TicketManager::sendNotification(
            (int) $ticket->getId(),
            get_lang('Ticket updated'),
            $messageHtml,
        );
    }

    /**
     * @return array<int, array{id: int, label: string, username: string}>
     */
    public function searchAssignableUsers(string $keyword): array
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only administrators can search assignees.');
        }

        $keyword = mb_substr(trim($keyword), 0, 100);
        $accessUrl = $this->getCurrentAccessUrl();
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select([
                'DISTINCT user.id AS id',
                'user.username AS username',
                'user.firstname AS firstname',
                'user.lastname AS lastname',
            ])
            ->from(User::class, 'user')
            ->innerJoin('user.portals', 'portal')
            ->andWhere('IDENTITY(portal.url) = :accessUrlId')
            ->andWhere('user.active = :active')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->orderBy('user.lastname', 'ASC')
            ->addOrderBy('user.firstname', 'ASC')
            ->setMaxResults(20)
        ;

        if ('' !== $keyword) {
            $queryBuilder
                ->andWhere(
                    'LOWER(user.username) LIKE :keyword'
                    .' OR LOWER(user.firstname) LIKE :keyword'
                    .' OR LOWER(user.lastname) LIKE :keyword',
                )
                ->setParameter('keyword', '%'.mb_strtolower($keyword).'%', Types::STRING)
            ;
        }

        $result = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $fullName = trim((string) $row['firstname'].' '.(string) $row['lastname']);
            $result[] = [
                'id' => (int) $row['id'],
                'label' => '' !== $fullName ? $fullName : (string) $row['username'],
                'username' => (string) $row['username'],
            ];
        }

        return $result;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        if (!\is_string($value)) {
            return false;
        }

        return \in_array(mb_strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function getCurrentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        return $accessUrl;
    }

    private function getProject(int $projectId, AccessUrl $accessUrl): TicketProject
    {
        $project = $this->entityManager->getRepository(TicketProject::class)->find($projectId);
        if (!$project instanceof TicketProject
            || ($project->getAccessUrl() instanceof AccessUrl
                && $project->getAccessUrl()->getId() !== $accessUrl->getId())
        ) {
            throw new BadRequestHttpException('The selected ticket project is invalid.');
        }

        return $project;
    }

    private function getCategory(int $categoryId, TicketProject $project): TicketCategory
    {
        $category = $this->entityManager->getRepository(TicketCategory::class)->find($categoryId);
        if (!$category instanceof TicketCategory || $category->getProject()->getId() !== $project->getId()) {
            throw new BadRequestHttpException('The selected ticket category is invalid.');
        }

        return $category;
    }

    private function getPriority(int $priorityId, AccessUrl $accessUrl): TicketPriority
    {
        $priority = $this->entityManager->getRepository(TicketPriority::class)->find($priorityId);
        if (!$priority instanceof TicketPriority
            || ($priority->getAccessUrl() instanceof AccessUrl
                && $priority->getAccessUrl()->getId() !== $accessUrl->getId())
        ) {
            throw new BadRequestHttpException('The selected ticket priority is invalid.');
        }

        return $priority;
    }

    private function getStatus(int $statusId, AccessUrl $accessUrl): TicketStatus
    {
        $status = $this->entityManager->getRepository(TicketStatus::class)->find($statusId);
        if (!$status instanceof TicketStatus
            || ($status->getAccessUrl() instanceof AccessUrl
                && $status->getAccessUrl()->getId() !== $accessUrl->getId())
        ) {
            throw new BadRequestHttpException('The selected ticket status is invalid.');
        }

        return $status;
    }

    private function getAssignableUser(int $userId, AccessUrl $accessUrl): User
    {
        $user = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->innerJoin('user.portals', 'portal')
            ->andWhere('user.id = :userId')
            ->andWhere('IDENTITY(portal.url) = :accessUrlId')
            ->andWhere('user.active = :active')
            ->setParameter('userId', $userId, Types::INTEGER)
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$user instanceof User) {
            throw new BadRequestHttpException('The selected assignee is invalid.');
        }

        return $user;
    }

    private function getFirstCategoryUser(TicketCategory $category, AccessUrl $accessUrl): ?User
    {
        $relation = $this->entityManager->createQueryBuilder()
            ->select('relation')
            ->from(TicketCategoryRelUser::class, 'relation')
            ->innerJoin('relation.user', 'user')
            ->innerJoin('user.portals', 'portal')
            ->andWhere('IDENTITY(relation.category) = :categoryId')
            ->andWhere('IDENTITY(portal.url) = :accessUrlId')
            ->andWhere('user.active = :active')
            ->setParameter('categoryId', (int) $category->getId(), Types::INTEGER)
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->orderBy('relation.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $relation instanceof TicketCategoryRelUser ? $relation->getUser() : null;
    }

    private function assertCourseAndSessionSelectionIsAllowed(
        User $user,
        int $courseId,
        int $sessionId,
        bool $isAdmin,
    ): void {
        if ($sessionId > 0) {
            $sessionIds = [];
            foreach (SessionManager::get_sessions_by_user((int) $user->getId()) as $sessionRow) {
                $allowedSessionId = (int) ($sessionRow['session_id'] ?? $sessionRow['id'] ?? 0);
                if ($allowedSessionId > 0) {
                    $sessionIds[$allowedSessionId] = true;
                }
            }

            if (!$isAdmin && !isset($sessionIds[$sessionId])) {
                throw new AccessDeniedHttpException('You are not allowed to use the selected session.');
            }

            if ($courseId <= 0) {
                return;
            }

            $courseIds = [];
            foreach (SessionManager::get_course_list_by_session_id($sessionId) as $courseRow) {
                $allowedCourseId = (int) ($courseRow['real_id'] ?? $courseRow['course_id'] ?? $courseRow['id'] ?? 0);
                if ($allowedCourseId > 0) {
                    $courseIds[$allowedCourseId] = true;
                }
            }

            if (!isset($courseIds[$courseId])) {
                throw new BadRequestHttpException('The selected course does not belong to the selected session.');
            }

            return;
        }

        if ($courseId <= 0 || $isAdmin) {
            return;
        }

        $courseIds = [];
        foreach (CourseManager::get_courses_list_by_user_id((int) $user->getId(), true) as $courseRow) {
            $allowedCourseId = (int) ($courseRow['real_id'] ?? $courseRow['course_id'] ?? $courseRow['id'] ?? 0);
            if ($allowedCourseId > 0) {
                $courseIds[$allowedCourseId] = true;
            }
        }

        if (!isset($courseIds[$courseId])) {
            throw new AccessDeniedHttpException('You are not allowed to use the selected course.');
        }
    }

    private function addAssignmentLog(Ticket $ticket, ?User $assignee, User $actor, DateTime $date): void
    {
        $log = (new TicketAssignedLog())
            ->setTicket($ticket)
            ->setUser($assignee)
            ->setInsertUserId((int) $actor->getId())
            ->setAssignedDate($date)
        ;
        $this->entityManager->persist($log);
    }

    private function createMessage(
        Ticket $ticket,
        User $user,
        string $subject,
        string $content,
        DateTime $date,
    ): TicketMessage {
        $message = (new TicketMessage())
            ->setTicket($ticket)
            ->setSubject($subject)
            ->setMessage($content)
            ->setStatus('NOL')
            ->setIpAddress((string) api_get_real_ip())
            ->setInsertUserId((int) $user->getId())
            ->setInsertDateTime($date)
            ->setLastEditUserId((int) $user->getId())
            ->setLastEditDateTime($date)
        ;
        $this->entityManager->persist($message);

        return $message;
    }

    /**
     * @param array<int, UploadedFile> $files
     *
     * @return array<int, array{file: UploadedFile, filename: string}>
     */
    private function validateFiles(array $files): array
    {
        if (\count($files) > self::MAX_ATTACHMENTS) {
            throw new BadRequestHttpException('A maximum of six attachments is allowed.');
        }

        $maxUploadSize = max(
            0,
            (int) $this->settingsManager->getSetting('message.message_max_upload_filesize'),
        );
        $result = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                throw new BadRequestHttpException('An attachment could not be uploaded.');
            }

            if ($maxUploadSize > 0 && (int) $file->getSize() > $maxUploadSize) {
                throw new BadRequestHttpException('An attachment exceeds the configured maximum file size.');
            }

            $policy = $this->uploadFilenamePolicy->filter($file->getClientOriginalName());
            if (false === $policy['allowed']) {
                throw new BadRequestHttpException('File upload failed: this file extension or file type is prohibited.');
            }

            $result[] = [
                'file' => $file,
                'filename' => (string) $policy['filename'],
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array{file: UploadedFile, filename: string}> $files
     */
    private function saveAttachments(
        Ticket $ticket,
        TicketMessage $message,
        User $user,
        array $files,
    ): void {
        foreach ($files as $item) {
            $file = $item['file'];
            $attachment = (new TicketMessageAttachment())
                ->setFilename($item['filename'])
                ->setPath(uniqid('ticket_message', true))
                ->setMessage($message)
                ->setSize((int) $file->getSize())
                ->setTicket($ticket)
                ->setInsertUserId((int) $user->getId())
                ->setInsertDateTime(new DateTime('now'))
                ->setParent($user)
            ;

            if ($ticket->getAssignedLastUser() instanceof User) {
                $attachment->addUserLink($ticket->getAssignedLastUser());
            }

            $this->entityManager->persist($attachment);
            $this->entityManager->flush();
            $this->attachmentRepository->addFile($attachment, $file);
        }
    }

    private function notifyTicketCreated(
        Ticket $ticket,
        User $creator,
        TicketCategory $category,
        string $personalEmail,
    ): void {
        $ticketId = (int) $ticket->getId();
        $title = \sprintf(get_lang('Ticket %s created'), $ticket->getCode());
        $message = '<h2>'.get_lang('Ticket info').'</h2>'
            .'<p><strong>'.get_lang('User').':</strong> '
            .htmlspecialchars($creator->getFullName(), ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><strong>'.get_lang('Username').':</strong> '
            .htmlspecialchars($creator->getUsername(), ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><strong>'.get_lang('E-mail').':</strong> '
            .htmlspecialchars($creator->getEmail(), ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><strong>'.get_lang('Phone').':</strong> '
            .htmlspecialchars((string) ($creator->getPhone() ?? ''), ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><strong>'.get_lang('Date').':</strong> '
            .htmlspecialchars($ticket->getInsertDateTime()->format(DATE_ATOM), ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><strong>'.get_lang('Title').':</strong> '
            .htmlspecialchars($ticket->getSubject(), ENT_QUOTES, 'UTF-8').'</p>'
            .'<p><strong>'.get_lang('Description').':</strong></p>'.($ticket->getMessage() ?? '');

        $categoryUsers = $this->entityManager->createQueryBuilder()
            ->select('user.id AS id')
            ->from(TicketCategoryRelUser::class, 'relation')
            ->innerJoin('relation.user', 'user')
            ->andWhere('IDENTITY(relation.category) = :categoryId')
            ->setParameter('categoryId', (int) $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;

        foreach ($categoryUsers as $row) {
            $recipientId = (int) $row['id'];
            if ($recipientId !== (int) $creator->getId()) {
                TicketManager::sendNotification($ticketId, $title, $message, $recipientId);
            }
        }

        if ([] === $categoryUsers
            && 'true' === $this->settingsManager->getSetting('ticket.ticket_warn_admin_no_user_in_category')
            && 'true' === $this->settingsManager->getSetting('ticket.ticket_send_warning_to_all_admins')
        ) {
            $warningTitle = \sprintf(
                get_lang('Warning: No one has been assigned to category %s'),
                $category->getTitle(),
            );
            foreach (UserManager::get_all_administrators() as $adminId => $adminData) {
                if (!empty($adminData['active'])) {
                    TicketManager::sendNotification($ticketId, $warningTitle, $message, (int) $adminId);
                }
            }
        }

        if ('' !== $personalEmail) {
            api_mail_html(
                get_lang('Virtual support'),
                $personalEmail,
                get_lang('The incident has been sent to the virtual support team again'),
                $message,
            );
        }

        TicketManager::sendNotification($ticketId, $title, $message);
    }
}
