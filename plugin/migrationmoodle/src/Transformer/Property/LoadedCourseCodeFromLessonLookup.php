<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class LoadedCourseCodeFromLessonLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseCodeFromLessonLookup extends LoadedCourseCodeLookup
{
    public function transform(array $data)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        $query = "SELECT course FROM mdl_lesson WHERE id = ?";

        $lessonId = current($data);

        try {
            $statement = $connection->executeQuery($query, [$lessonId]);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"$query\".", 0, $e);
        }

        $result = $statement->fetch(FetchMode::ASSOCIATIVE);

        $connection->close();

        $courseId = $result['course'];

        return parent::transform([$courseId]);
    }
}
