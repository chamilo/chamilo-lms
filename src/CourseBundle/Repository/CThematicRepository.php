<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for course thematics and related advances.
 */
final class CThematicRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CThematic::class);
    }

    /**
     * Get all active thematics linked to a given course/session,
     * ordered by the same rules as other course resources.
     *
     * @return CThematic[]
     */
    public function getThematicListForCourse(Course $course, ?Session $session = null): array
    {
        $qb = $this->getResourcesByCourse($course, $session, null, null, true, true);
        $qb->andWhere('resource.active = 1');

        return $qb->getQuery()->getResult();
    }

    /**
     * Return advances ordered by thematic display order and advance start date.
     *
     * @return CThematicAdvance[]
     */
    public function findOrderedAdvancesForCourse(Course $course, ?Session $session = null): array
    {
        $orderedAdvances = [];

        foreach ($this->getThematicListForCourse($course, $session) as $thematic) {
            $advances = $thematic->getAdvances()->toArray();

            usort(
                $advances,
                static function (CThematicAdvance $first, CThematicAdvance $second): int {
                    $dateComparison = $first->getStartDate() <=> $second->getStartDate();

                    if (0 !== $dateComparison) {
                        return $dateComparison;
                    }

                    return (int) $first->getIid() <=> (int) $second->getIid();
                },
            );

            foreach ($advances as $advance) {
                $orderedAdvances[] = $advance;
            }
        }

        return $orderedAdvances;
    }

    /**
     * Return the last done advance for the given course/session.
     */
    public function findLastDoneAdvanceForCourse(Course $course, ?Session $session = null): ?CThematicAdvance
    {
        $lastDoneAdvance = null;

        foreach ($this->findOrderedAdvancesForCourse($course, $session) as $advance) {
            if (true === $advance->getDoneAdvance()) {
                $lastDoneAdvance = $advance;
            }
        }

        return $lastDoneAdvance;
    }

    /**
     * Return the first $limit advances not done for the given course/session.
     *
     * @return CThematicAdvance[]
     */
    public function findNextNotDoneAdvancesForCourse(
        Course $course,
        ?Session $session = null,
        int $limit = 1,
    ): array {
        if ($limit <= 0) {
            return [];
        }

        $pending = [];

        foreach ($this->findOrderedAdvancesForCourse($course, $session) as $advance) {
            if (true === $advance->getDoneAdvance()) {
                continue;
            }

            $pending[] = $advance;

            if (\count($pending) >= $limit) {
                break;
            }
        }

        return $pending;
    }

    /**
     * Compute the global average of thematic advances for the given course/session.
     */
    public function calculateTotalAverageForCourse(Course $course, ?Session $session = null): float
    {
        $thematics = $this->getThematicListForCourse($course, $session);

        if (empty($thematics)) {
            return 0.0;
        }

        $averages = [];

        foreach ($thematics as $thematic) {
            $advances = $thematic->getAdvances();
            $total = $advances->count();

            if (0 === $total) {
                continue;
            }

            $done = 0;
            foreach ($advances as $advance) {
                if (true === $advance->getDoneAdvance()) {
                    ++$done;
                }
            }

            $averages[] = round(($done * 100) / $total);
        }

        if (empty($averages)) {
            return 0.0;
        }

        return round(array_sum($averages) / \count($thematics));
    }
}
