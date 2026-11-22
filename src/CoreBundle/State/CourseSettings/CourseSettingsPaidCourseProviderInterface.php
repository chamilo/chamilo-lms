<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;

/**
 * Optional capability for plugins that manage paid-course visibility restrictions.
 */
interface CourseSettingsPaidCourseProviderInterface
{
    public function hasActivePaidCourse(Course $course): bool;
}
