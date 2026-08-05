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
        $setting = $entityManager->getRepository(CCourseSetting::class)->findOneBy([
            'cId' => (int) $course->getId(),
            'variable' => $variable,
        ]);

        if (!$setting instanceof CCourseSetting) {
            return null;
        }

        return $setting->getValue();
    }

    private function isTruthyForumCourseSettingValue(mixed $value): bool
    {
        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
