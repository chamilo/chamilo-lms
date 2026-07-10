<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Announcement\AnnouncementList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<AnnouncementList>
 */
final readonly class AnnouncementListProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AnnouncementList
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

        $studentView = $this->isStudentView($request);
        $canManage = !$studentView && $this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        );

        $result = new AnnouncementList();
        $result->courseId = (int) $course->getId();
        $result->sessionId = $session?->getId();
        $result->groupId = $group?->getIid();
        $result->canManage = $canManage;
        $result->studentView = $studentView;

        $queryBuilder = $this->announcementRepository->getResources();
        $queryBuilder
            ->andWhere('links.course = :course')
            ->setParameter('course', (int) $course->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('links.session = :session')
                ->setParameter('session', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        if ($group instanceof CGroup) {
            $queryBuilder
                ->andWhere('links.group = :group')
                ->setParameter('group', (int) $group->getIid(), Types::INTEGER)
            ;
        }

        $announcements = $queryBuilder->getQuery()->getResult();
        $itemsById = [];
        $authorsById = [];

        foreach ($announcements as $announcement) {
            if (!$announcement instanceof CAnnouncement || null === $announcement->getIid()) {
                continue;
            }

            $announcementId = (int) $announcement->getIid();
            if (isset($itemsById[$announcementId])) {
                continue;
            }

            $contextLinks = $this->getAnnouncementContextLinks($announcement, $course, $session, $group);
            if (!$this->canReadAnnouncement(
                $announcement,
                $contextLinks,
                $this->security,
                $canManage,
                $studentView,
            )) {
                continue;
            }

            $item = $this->normalizeAnnouncement(
                $announcement,
                $contextLinks,
                $course,
                $session,
                $group,
                $canManage,
            );
            $itemsById[$announcementId] = $item;

            $creator = $announcement->getResourceNode()?->getCreator();
            if ($creator instanceof User && null !== $creator->getId()) {
                $fullName = trim($creator->getFullName());
                $authorsById[(int) $creator->getId()] = [
                    'id' => (int) $creator->getId(),
                    'label' => '' !== $fullName ? $fullName : $creator->getUsername(),
                    'username' => $creator->getUsername(),
                ];
            }
        }

        $items = array_values($itemsById);
        usort(
            $items,
            static function (array $left, array $right): int {
                $orderComparison = ((int) $left['displayOrder']) <=> ((int) $right['displayOrder']);
                if (0 !== $orderComparison) {
                    return $orderComparison;
                }

                return strcmp((string) $right['updatedAt'], (string) $left['updatedAt']);
            },
        );

        $authors = array_values($authorsById);
        usort(
            $authors,
            static fn (array $left, array $right): int => strcasecmp(
                (string) $left['label'],
                (string) $right['label'],
            ),
        );

        $result->items = $items;
        $result->authors = $authors;
        $result->totalItems = \count($items);

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
        bool $canManage,
    ): array {
        $resourceNode = $announcement->getResourceNode();
        $creator = $resourceNode?->getCreator();
        $title = trim(html_entity_decode(strip_tags($announcement->getTitle()), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return [
            'id' => (int) $announcement->getIid(),
            'title' => '' !== $title ? $title : 'Announcement',
            'author' => $creator instanceof User ? [
                'id' => (int) $creator->getId(),
                'username' => $creator->getUsername(),
                'fullName' => $creator->getFullName(),
            ] : null,
            'createdAt' => $resourceNode?->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $resourceNode?->getUpdatedAt()?->format(DATE_ATOM),
            'emailSent' => true === $announcement->getEmailSent(),
            'hasAttachments' => !$announcement->getAttachments()->isEmpty(),
            'attachmentCount' => $announcement->getAttachments()->count(),
            'visibility' => $this->getAnnouncementVisibility($contextLinks),
            'displayOrder' => $this->getAnnouncementDisplayOrder($contextLinks),
            'canEdit' => $canManage && $this->canEditAnnouncement(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $announcement,
                $course,
                $session,
                $group,
            ),
        ];
    }
}
