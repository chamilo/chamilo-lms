<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\ORM\EntityManagerInterface;

trait ForumCourseSettingHelperTrait
{
    private function isCourseSettingEnabled(EntityManagerInterface $entityManager, Course $course, string $variable): bool
    {
        return $this->isTruthyForumCourseSettingValue(
            $this->getCourseSettingValue($entityManager, $course, $variable),
        );
    }

    private function getCourseSettingValue(EntityManagerInterface $entityManager, Course $course, string $variable): mixed
    {
        $repository = $entityManager->getRepository(CCourseSetting::class);
        $criteria = [
            'cId' => (int) $course->getId(),
            'variable' => $variable,
        ];
        $setting = $repository->findOneBy($criteria + ['category' => 'forum'])
            ?? $repository->findOneBy($criteria + ['category' => null])
            ?? $repository->findOneBy($criteria + ['category' => '']);

        $value = $setting instanceof CCourseSetting ? $setting->getValue() : null;

        return $this->courseSettingPolicyManager->effectiveValue($variable, $value, null, 'forum');
    }

    private function isTruthyForumCourseSettingValue(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
