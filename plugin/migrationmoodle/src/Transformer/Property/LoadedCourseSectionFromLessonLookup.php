<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class LoadedCourseSectionFromLessonLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseSectionFromLessonLookup extends LoadedCourseSectionLookup
{
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

        $query = "SELECT cs.id FROM mdl_course_sections cs
            INNER JOIN mdl_course_modules cm ON (cs.id = cm.section AND cs.course = cm.course)
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

        $sectionId = $result['id'];

        return parent::transform([$sectionId]);
    }
}
