<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AnnouncementOrderManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private AnnouncementRecipientResolver $recipientResolver,
    ) {}

    public function normalize(Course $course, ?Session $session, ?CGroup $group): void
    {
        $entries = $this->getOrderedEntries($course, $session, $group);

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

        $announcements = [];
        foreach ($queryBuilder->getQuery()->getResult() as $announcement) {
            if (!$announcement instanceof CAnnouncement || null === $announcement->getIid()) {
                continue;
            }

            if ([] === $this->recipientResolver->getScopedLinks($announcement, $course, $session, $group)) {
                continue;
            }

            $announcements[(int) $announcement->getIid()] = $announcement;
        }

        return array_values($announcements);
    }
}
