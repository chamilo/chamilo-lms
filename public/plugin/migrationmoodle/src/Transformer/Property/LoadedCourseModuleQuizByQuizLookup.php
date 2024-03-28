<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesQuizTask;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class LoadedCourseModuleQuizByQuizLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseModuleQuizByQuizLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseModuleQuizByQuizLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CourseModulesQuizTask::class;
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        $query = "SELECT cm.id FROM mdl_course_modules cm
            INNER JOIN mdl_modules m ON cm.module = m.id
            INNER JOIN mdl_quiz q ON (cm.course = q.course AND cm.instance = q.id)
            WHERE m.name = 'quiz'
            AND q.id = ?";

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
