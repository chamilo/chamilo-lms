<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseDescription\CourseDescriptionOrder;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseDescriptionHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @implements ProcessorInterface<CourseDescriptionOrder, CourseDescriptionOrder>
 */
final readonly class CourseDescriptionOrderProcessor implements ProcessorInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CourseDescriptionHelper $courseDescriptionHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseDescriptionOrder
    {
        if (!$data instanceof CourseDescriptionOrder || $data->iid <= 0 || !\in_array($data->direction, ['up', 'down'], true)) {
            throw new BadRequestHttpException('A valid description and move direction are required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->courseDescriptionHelper->assertToolEnabled($course);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->courseDescriptionHelper->assertSessionBelongsToCourse($session, $course);

        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to reorder course descriptions in this context.');
        }

        $this->entityManager->getConnection()->transactional(function () use ($course, $session, $data): void {
            // Serialize reorders of the same course. The neighbor is resolved on the server.
            $this->entityManager->lock($course, LockMode::PESSIMISTIC_WRITE);
            $links = [];
            foreach ($this->courseDescriptionRepository->findAllInCourse($course, $session) as $description) {
                $link = $this->courseDescriptionHelper->getReorderLink($description, $course, $session);
                if ($link instanceof ResourceLink) {
                    $links[(int) $description->getIid()] = $link;
                }
            }

            // Use the same order as the list, but only movable links in this scope.
            $ids = array_keys($links);
            $index = array_search($data->iid, $ids, true);
            if (false === $index) {
                throw new AccessDeniedHttpException('The requested course description cannot be reordered in this context.');
            }

            $target = $index + ('up' === $data->direction ? -1 : 1);
            if (!isset($ids[$target])) {
                return;
            }

            $link = $links[$data->iid];
            $neighbor = $links[$ids[$target]];
            $position = $neighbor->getDisplayOrder();
            if ($position === $link->getDisplayOrder()) {
                throw new ConflictHttpException('The current resource link positions are inconsistent.');
            }

            // Gedmo Sortable shifts the other positions within this link's existing group.
            // Do not write to the description, its progress, or links in another context.
            $link->setDisplayOrder($position);
            $this->entityManager->flush();
        });

        $data->saved = true;

        return $data;
    }
}
