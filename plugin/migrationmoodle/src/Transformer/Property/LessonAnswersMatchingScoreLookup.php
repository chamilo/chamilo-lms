<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Doctrine\DBAL\DBALException;

/**
 * Class LessonAnswersMatchingScoreLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LessonAnswersMatchingScoreLookup implements TransformPropertyInterface
{
    /**
     * @param array $data
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        list($pageid, $lessonid, $course) = array_values($data);

        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        try {
            $query = "SELECT la.score
                FROM mdl_lesson_answers la
                INNER JOIN mdl_lesson l ON (la.lessonid = l.id)
                WHERE la.pageid = ?
                    AND la.lessonid = ?
                    AND l.course = ?
                    AND la.score > 0";

            $score = $connection->fetchColumn($query, [$pageid, $lessonid, $course], 0);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"{$this->query}\".", 0, $e);
        }

        $connection->close();

        if (empty($score)) {
            return 1;
        }

        return $score;
    }
}
