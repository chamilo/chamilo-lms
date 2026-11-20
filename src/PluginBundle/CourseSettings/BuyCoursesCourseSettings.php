<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\State\CourseSettings\AbstractCourseSettingsPlugin;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPaidCourseProviderInterface;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPluginStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class BuyCoursesCourseSettings extends AbstractCourseSettingsPlugin implements CourseSettingsPaidCourseProviderInterface
{
    public function __construct(
        PluginHelper $pluginHelper,
        CourseSettingsPluginStore $store,
        private readonly Connection $connection,
    ) {
        parent::__construct($pluginHelper, $store);
    }

    protected function pluginName(): string
    {
        return 'BuyCourses';
    }

    public function getIntegrationKey(): string
    {
        return 'buyCourses';
    }

    public function getIntegration(Course $course, ?Session $session): array
    {
        return [
            'enabled' => $this->pluginHelper->isPluginEnabled($this->pluginName()),
            'activePaidCourse' => $this->hasActivePaidCourse($course),
        ];
    }

    public function hasActivePaidCourse(Course $course): bool
    {
        return $this->hasActiveBuyCoursesService((int) $course->getId());
    }

    private function hasActiveBuyCoursesService(int $courseId): bool
    {
        if (!$this->pluginHelper->isPluginEnabled($this->pluginName())
            || !$this->tableExists('plugin_buycourses_subscription_course')
            || !$this->tableExists('plugin_buycourses_service_sale')) {
            return false;
        }

        return false !== $this->connection->fetchOne(
            "SELECT 1
             FROM plugin_buycourses_subscription_course sc
             INNER JOIN plugin_buycourses_service_sale ss ON ss.id = sc.service_sale_id
             WHERE sc.course_id = :courseId
               AND sc.status = 'active'
               AND sc.deleted_at IS NULL
               AND ss.status = 1
               AND (ss.date_start IS NULL OR ss.date_start <= UTC_TIMESTAMP())
               AND ss.date_end IS NOT NULL
               AND ss.date_end >= UTC_TIMESTAMP()
             LIMIT 1",
            ['courseId' => $courseId],
            ['courseId' => Types::INTEGER],
        );
    }

    private function tableExists(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }
}
