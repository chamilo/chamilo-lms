<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\State\CourseSettings\AbstractCourseSettingsPlugin;

final class CourseBlockCourseSettings extends AbstractCourseSettingsPlugin
{
    protected function pluginName(): string
    {
        return 'CourseBlock';
    }

    public function getIntegrationKey(): string
    {
        return 'courseBlock';
    }

    public function getSection(Course $course, ?Session $session, array $values): ?array
    {
        return $this->section('course_block', 'Course block', 'admin-settings', [
            $this->field('course_block_pre_footer', 'Before footer', 'editor'),
            $this->field('course_block_footer_left', 'Footer left', 'editor'),
            $this->field('course_block_footer_center', 'Footer center', 'editor'),
            $this->field('course_block_footer_right', 'Footer right', 'editor'),
        ], collapsed: true);
    }
}
