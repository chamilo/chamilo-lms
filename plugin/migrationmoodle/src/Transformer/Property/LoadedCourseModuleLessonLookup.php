<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesLessonTask;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class LoadedCourseModuleLessonLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseModuleLessonLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseModuleLessonLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesLessonTask::class;
    }

    public function transform(array $data)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        $query = "SELECT cm.id FROM mdl_course_modules cm
            INNER JOIN mdl_modules m ON cm.module = m.id
            INNER JOIN mdl_lesson l ON (cm.course = l.course AND cm.instance = l.id)
            WHERE m.name = 'lesson'
            AND l.id = ?";

        $lessonId = current($data);

        try {
            $statement = $connection->executeQuery($query, [$lessonId]);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"$query\".", 0, $e);
        }

        $result = $statement->fetch(FetchMode::ASSOCIATIVE);

        $connection->close();

        $lessonId = $result['id'];

        return parent::transform([$lessonId]);
    }
}
