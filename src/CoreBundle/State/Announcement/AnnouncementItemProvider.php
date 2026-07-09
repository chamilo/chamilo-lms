<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Announcement\AnnouncementItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<AnnouncementItem>
 */
final readonly class AnnouncementItemProvider implements ProviderInterface
{
    use AnnouncementAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AnnouncementItem
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $this->assertAnnouncementToolEnabled($this->entityManager, $course);

        $session = $this->getSession($request);
        $this->assertSessionBelongsToCourse($session, $course);

        $group = $this->getGroup($request);
        $this->assertGroupBelongsToContext($group, $course, $session);

        if (!$this->canReadAnnouncementContext(
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to view announcements in this context.');
        }

        $announcementId = (int) ($uriVariables['id'] ?? 0);
        if ($announcementId <= 0) {
            throw new BadRequestHttpException('A valid announcement id is required.');
        }

        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        $studentView = $this->isStudentView($request);
        $canManage = !$studentView && $this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );

        $contextLinks = $this->getAnnouncementContextLinks($announcement, $course, $session, $group);
        if (!$this->canReadAnnouncement(
            $announcement,
            $contextLinks,
            $this->security,
            $canManage,
            $studentView,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to view this announcement.');
        }

        $canViewRecipients = $canManage && !$this->isSettingEnabled(
            $this->settingsManager->getSetting('announcement.hide_announcement_sent_to_users_info', true),
        );

        $result = new AnnouncementItem();
        $result->id = $announcementId;
        $result->courseId = (int) $course->getId();
        $result->sessionId = $session?->getId();
        $result->groupId = $group?->getIid();
        $result->canManage = $canManage;
        $result->canViewRecipients = $canViewRecipients;
        $result->studentView = $studentView;
        $result->item = $this->normalizeAnnouncement(
            $announcement,
            $contextLinks,
            $course,
            $session,
            $group,
            $canViewRecipients,
        );

        return $result;
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

    private function getGroup(Request $request): ?CGroup
    {
        $groupId = $request->query->getInt('gid');
        if ($groupId <= 0) {
            return null;
        }

        $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new BadRequestHttpException('The requested group was not found.');
        }

        return $group;
    }

    /**
     * @param array<int, ResourceLink> $contextLinks
     *
     * @return array<string, mixed>
     */
    private function normalizeAnnouncement(
        CAnnouncement $announcement,
        array $contextLinks,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $canViewRecipients,
    ): array {
        $resourceNode = $announcement->getResourceNode();
        $creator = $resourceNode?->getCreator();
        $title = trim(html_entity_decode(strip_tags($announcement->getTitle()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $content = $this->parseContentForCurrentReader(
            (string) $announcement->getContent(),
            $course,
            $session,
        );

        return [
            'id' => (int) $announcement->getIid(),
            'title' => '' !== $title ? $title : 'Announcement',
            'content' => $content,
            'author' => $creator instanceof User ? [
                'id' => (int) $creator->getId(),
                'username' => $creator->getUsername(),
                'fullName' => $creator->getFullName(),
            ] : null,
            'createdAt' => $resourceNode?->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $resourceNode?->getUpdatedAt()?->format(DATE_ATOM),
            'emailSent' => true === $announcement->getEmailSent(),
            'visibility' => $this->getAnnouncementVisibility($contextLinks),
            'displayOrder' => $this->getAnnouncementDisplayOrder($contextLinks),
            'language' => $resourceNode?->getLanguage()?->getIsocode(),
            'attachments' => $this->normalizeAttachments($announcement, $course, $session, $group),
            'recipients' => $canViewRecipients ? $this->normalizeRecipients($contextLinks) : null,
        ];
    }

    private function parseContentForCurrentReader(
        string $content,
        Course $course,
        ?Session $session,
    ): string {
        if (!\class_exists(\AnnouncementManager::class)) {
            return $content;
        }

        $user = $this->security->getUser();
        $userId = $user instanceof User ? (int) $user->getId() : 0;

        return (string) \AnnouncementManager::parseContent(
            $userId,
            $content,
            $course->getCode(),
            null !== $session ? (int) $session->getId() : 0,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeAttachments(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $contextQuery = [
            'cid' => (int) $course->getId(),
        ];

        if ($session instanceof Session && null !== $session->getId()) {
            $contextQuery['sid'] = (int) $session->getId();
        }

        if ($group instanceof CGroup && null !== $group->getIid()) {
            $contextQuery['gid'] = (int) $group->getIid();
        }

        $attachments = [];
        foreach ($announcement->getAttachments() as $attachment) {
            if (!$attachment instanceof CAnnouncementAttachment || null === $attachment->getIid()) {
                continue;
            }

            $downloadUrl = \sprintf(
                '/api/announcement/%d/attachment/%d/download?%s',
                (int) $announcement->getIid(),
                (int) $attachment->getIid(),
                http_build_query($contextQuery),
            );

            $attachments[] = [
                'id' => (int) $attachment->getIid(),
                'filename' => $attachment->getFilename(),
                'comment' => (string) $attachment->getComment(),
                'size' => (int) $attachment->getSize(),
                'downloadUrl' => $downloadUrl,
            ];
        }

        return $attachments;
    }

    /**
     * @param array<int, ResourceLink> $contextLinks
     *
     * @return array<string, mixed>
     */
    private function normalizeRecipients(array $contextLinks): array
    {
        $users = [];
        $groups = [];
        $everyone = false;

        foreach ($contextLinks as $link) {
            $user = $link->getUser();
            $group = $link->getGroup();

            if ($user instanceof User && null !== $user->getId()) {
                $users[(int) $user->getId()] = [
                    'id' => (int) $user->getId(),
                    'username' => $user->getUsername(),
                    'fullName' => $user->getFullName(),
                ];

                continue;
            }

            if ($group instanceof CGroup && null !== $group->getIid()) {
                $groups[(int) $group->getIid()] = [
                    'id' => (int) $group->getIid(),
                    'title' => $group->getTitle(),
                ];

                continue;
            }

            $everyone = true;
        }

        return [
            'everyone' => $everyone,
            'users' => array_values($users),
            'groups' => array_values($groups),
        ];
    }
}
