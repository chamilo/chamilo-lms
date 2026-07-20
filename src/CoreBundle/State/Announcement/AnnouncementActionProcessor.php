<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Announcement\AnnouncementAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\DBAL\ArrayParameterType;
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
 * @implements ProcessorInterface<AnnouncementAction, AnnouncementAction>
 */
final readonly class AnnouncementActionProcessor implements ProcessorInterface
{
    use AnnouncementAccessHelperTrait;

    public const CSRF_TOKEN_ID = 'announcement_manage';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private AnnouncementRecipientResolver $recipientResolver,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AnnouncementAction
    {
        if (!$data instanceof AnnouncementAction) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

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

        if ($this->isStudentView($request) || !$this->canManageAnnouncements(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to manage announcements in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);

        $result = new AnnouncementAction();
        $result->success = true;

        switch ($operation->getName()) {
            case 'post_announcement_visibility':
                $announcement = $this->getEditableAnnouncement(
                    (int) ($uriVariables['id'] ?? 0),
                    $course,
                    $session,
                    $group,
                );
                $visibility = $data->visibility;
                if (!\in_array($visibility, [ResourceLink::VISIBILITY_DRAFT, ResourceLink::VISIBILITY_PUBLISHED], true)) {
                    throw new BadRequestHttpException('The requested visibility is invalid.');
                }

                foreach ($this->recipientResolver->getScopedLinks($announcement, $course, $session, $group) as $link) {
                    $link->setVisibility($visibility);
                }
                $this->entityManager->flush();
                $result->id = (int) $announcement->getIid();
                $result->affectedIds = [(int) $announcement->getIid()];
                $this->registerAnnouncementEventLog(
                    'set_visibility',
                    $course,
                    $session,
                    (int) $announcement->getIid(),
                    details: ResourceLink::VISIBILITY_PUBLISHED === $visibility ? 'visible' : 'invisible',
                );

                return $result;

            case 'post_announcement_move':
                $announcement = $this->getEditableAnnouncement(
                    (int) ($uriVariables['id'] ?? 0),
                    $course,
                    $session,
                    $group,
                );
                if (!\in_array($data->direction, ['up', 'down'], true)) {
                    throw new BadRequestHttpException('The requested move direction is invalid.');
                }

                $this->moveAnnouncement($announcement, $course, $session, $group, $data->direction);
                $result->id = (int) $announcement->getIid();
                $result->affectedIds = [(int) $announcement->getIid()];
                $this->registerAnnouncementEventLog(
                    'move',
                    $course,
                    $session,
                    (int) $announcement->getIid(),
                    details: $data->direction,
                );

                return $result;

            case 'post_announcement_delete':
                $announcement = $this->getEditableAnnouncement(
                    (int) ($uriVariables['id'] ?? 0),
                    $course,
                    $session,
                    $group,
                );
                $announcementId = (int) $announcement->getIid();
                $this->removeAnnouncement($announcement);
                $this->entityManager->flush();
                $this->normalizeDisplayOrder($course, $session, $group);
                $result->id = $announcementId;
                $result->affectedIds = [$announcementId];
                $this->registerAnnouncementEventLog('delete', $course, $session, $announcementId);

                return $result;

            case 'post_announcement_delete_selected':
                $ids = array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => (int) $id, $data->ids))));
                if ([] === $ids) {
                    throw new BadRequestHttpException('At least one announcement is required.');
                }

                $announcements = [];
                foreach ($ids as $id) {
                    $announcements[] = $this->getEditableAnnouncement($id, $course, $session, $group);
                }

                foreach ($announcements as $announcement) {
                    $this->removeAnnouncement($announcement);
                }
                $this->entityManager->flush();
                $this->normalizeDisplayOrder($course, $session, $group);
                $result->affectedIds = $ids;
                $this->registerAnnouncementEventLog(
                    'delete',
                    $course,
                    $session,
                    details: 'selected',
                    info: implode(',', $ids),
                );

                return $result;

            case 'post_announcement_delete_all':
                if (!$this->canDeleteAllAnnouncements(
                    $this->security,
                    $this->settingsManager,
                    $course,
                    $session,
                    $group,
                )) {
                    throw new AccessDeniedHttpException('You are not allowed to delete all announcements in this context.');
                }

                if ($this->isSettingEnabled(
                    $this->settingsManager->getSetting('announcement.disable_delete_all_announcements', true),
                )) {
                    throw new AccessDeniedHttpException('Deleting all announcements is disabled.');
                }

                $affectedIds = [];
                foreach ($this->getContextAnnouncements($course, $session, $group) as $announcement) {
                    if (!$this->canEditAnnouncement(
                        $this->entityManager,
                        $this->security,
                        $this->settingsManager,
                        $announcement,
                        $course,
                        $session,
                        $group,
                    )) {
                        throw new AccessDeniedHttpException('You are not allowed to delete one of the announcements.');
                    }

                    $affectedIds[] = (int) $announcement->getIid();
                    $this->removeAnnouncement($announcement);
                }
                $this->entityManager->flush();
                $result->affectedIds = $affectedIds;
                $this->registerAnnouncementEventLog(
                    'delete_all',
                    $course,
                    $session,
                    details: (string) \count($affectedIds),
                );

                return $result;
        }

        throw new BadRequestHttpException('The requested announcement action is not supported.');
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

    private function getEditableAnnouncement(
        int $announcementId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): CAnnouncement {
        if ($announcementId <= 0) {
            throw new BadRequestHttpException('A valid announcement id is required.');
        }

        $announcement = $this->announcementRepository->find($announcementId);
        if (!$announcement instanceof CAnnouncement) {
            throw new NotFoundHttpException('The requested announcement was not found.');
        }

        if ([] === $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group)) {
            throw new AccessDeniedHttpException('The requested announcement does not belong to the current course context.');
        }

        if ($group instanceof CGroup && $this->hasMultipleAnnouncementGroupTargets(
            $announcement,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('This announcement targets several groups and cannot be managed from one group.');
        }

        if (!$this->canEditAnnouncement(
            $this->entityManager,
            $this->security,
            $this->settingsManager,
            $announcement,
            $course,
            $session,
            $group,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to edit this announcement.');
        }

        return $announcement;
    }

    /**
     * @return array<int, CAnnouncement>
     */
    private function getContextAnnouncements(Course $course, ?Session $session, ?CGroup $group): array
    {
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

        $byId = [];
        foreach ($queryBuilder->getQuery()->getResult() as $announcement) {
            if (!$announcement instanceof CAnnouncement || null === $announcement->getIid()) {
                continue;
            }

            if ([] === $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group)) {
                continue;
            }

            $byId[(int) $announcement->getIid()] = $announcement;
        }

        return array_values($byId);
    }

    private function moveAnnouncement(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $direction,
    ): void {
        $entries = $this->getOrderedEntries($course, $session, $group);
        $currentIndex = null;

        foreach ($entries as $index => $entry) {
            if ($entry['announcement']->getIid() === $announcement->getIid()) {
                $currentIndex = $index;

                break;
            }
        }

        if (null === $currentIndex) {
            throw new AccessDeniedHttpException('The requested announcement does not belong to the current course context.');
        }

        $targetIndex = 'up' === $direction ? $currentIndex - 1 : $currentIndex + 1;
        if (!isset($entries[$targetIndex])) {
            return;
        }

        [$entries[$currentIndex], $entries[$targetIndex]] = [$entries[$targetIndex], $entries[$currentIndex]];

        $this->persistDisplayOrder($entries);
    }

    private function normalizeDisplayOrder(Course $course, ?Session $session, ?CGroup $group): void
    {
        $this->persistDisplayOrder($this->getOrderedEntries($course, $session, $group));
    }

    /**
     * ResourceLink is grouped by recipient for Gedmo sorting. Announcement ordering is global inside
     * the course/session/group context, so bulk DQL updates are required to keep every recipient link
     * of the same announcement at the same position without triggering recipient-specific reordering.
     *
     * @param array<int, array{announcement: CAnnouncement, links: array<int, ResourceLink>, displayOrder: int}> $entries
     */
    private function persistDisplayOrder(array $entries): void
    {
        $this->entityManager->getConnection()->transactional(function () use ($entries): void {
            foreach ($entries as $displayOrder => $entry) {
                $linkIds = [];
                foreach ($entry['links'] as $link) {
                    $linkId = $link->getId();
                    if (null !== $linkId) {
                        $linkIds[] = $linkId;
                    }
                }

                if ([] === $linkIds) {
                    continue;
                }

                $this->entityManager->createQueryBuilder()
                    ->update(ResourceLink::class, 'resourceLink')
                    ->set('resourceLink.displayOrder', ':displayOrder')
                    ->where('resourceLink.id IN (:linkIds)')
                    ->setParameter('displayOrder', $displayOrder, Types::INTEGER)
                    ->setParameter('linkIds', $linkIds, ArrayParameterType::INTEGER)
                    ->getQuery()
                    ->execute()
                ;
            }
        });
    }

    /**
     * @return array<int, array{announcement: CAnnouncement, links: array<int, ResourceLink>, displayOrder: int}>
     */
    private function getOrderedEntries(Course $course, ?Session $session, ?CGroup $group): array
    {
        $entries = [];
        foreach ($this->getContextAnnouncements($course, $session, $group) as $announcement) {
            $links = $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group);
            if ([] === $links) {
                continue;
            }

            $entries[] = [
                'announcement' => $announcement,
                'links' => $links,
                'displayOrder' => min(array_map(
                    static fn (ResourceLink $link): int => $link->getDisplayOrder(),
                    $links,
                )),
            ];
        }

        usort(
            $entries,
            static function (array $left, array $right): int {
                $comparison = $left['displayOrder'] <=> $right['displayOrder'];
                if (0 !== $comparison) {
                    return $comparison;
                }

                $leftUpdatedAt = $left['announcement']->getResourceNode()?->getUpdatedAt()?->getTimestamp() ?? 0;
                $rightUpdatedAt = $right['announcement']->getResourceNode()?->getUpdatedAt()?->getTimestamp() ?? 0;
                $updatedComparison = $rightUpdatedAt <=> $leftUpdatedAt;
                if (0 !== $updatedComparison) {
                    return $updatedComparison;
                }

                return ((int) $right['announcement']->getIid()) <=> ((int) $left['announcement']->getIid());
            },
        );

        return $entries;
    }

    private function removeAnnouncement(CAnnouncement $announcement): void
    {
        $resourceNode = $announcement->getResourceNode();
        if (null !== $resourceNode) {
            $this->entityManager->remove($resourceNode);

            return;
        }

        $this->entityManager->remove($announcement);
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }
}
