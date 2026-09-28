<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use Chamilo\CourseBundle\Repository\CGroupCategoryRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;

final readonly class AssignmentGroupCategoryResolver
{
    public const int ALL_GROUPS = -1;

    public function __construct(
        private CGroupCategoryRepository $categoryRepository,
        private CGroupRepository $groupRepository,
    ) {}

    public function getEffectiveCategoryId(CGroup $group, Course $course, ?Session $session): int
    {
        $categoryId = (int) ($group->getCategory()?->getIid() ?? 0);
        if ($categoryId > 0) {
            return $categoryId;
        }

        return $this->getDefaultCategoryId($course, $session);
    }

    public function assignmentAppliesToGroup(
        int $groupCategoryWorkId,
        CGroup $group,
        Course $course,
        ?Session $session,
    ): bool {
        if (0 === $groupCategoryWorkId) {
            return false;
        }

        if (self::ALL_GROUPS === $groupCategoryWorkId) {
            return true;
        }

        return $groupCategoryWorkId === $this->getEffectiveCategoryId($group, $course, $session);
    }

    public function courseHasGroups(Course $course, ?Session $session): bool
    {
        $groups = $this->groupRepository
            ->getResourcesByCourse($course, $session, displayOnlyPublished: false)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;

        return [] !== $groups;
    }

    public function categoryHasGroups(int $categoryId, Course $course, ?Session $session): bool
    {
        if ($categoryId <= 0) {
            return false;
        }

        $groups = $this->groupRepository
            ->getResourcesByCourse($course, $session, displayOnlyPublished: false)
            ->getQuery()
            ->getResult()
        ;

        foreach ($groups as $group) {
            if (!$group instanceof CGroup) {
                continue;
            }

            if ($categoryId === $this->getEffectiveCategoryId($group, $course, $session)) {
                return true;
            }
        }

        return false;
    }

    private function getDefaultCategoryId(Course $course, ?Session $session): int
    {
        $categories = $this->categoryRepository
            ->getResourcesByCourse(
                course: $course,
                session: $session,
                displayOnlyPublished: false,
                displayOrder: true,
            )
            ->getQuery()
            ->getResult()
        ;

        $category = $categories[0] ?? null;

        return $category instanceof CGroupCategory ? (int) $category->getIid() : 0;
    }
}
