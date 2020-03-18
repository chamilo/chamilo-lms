<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;
use Doctrine\DBAL\DBALException;

/**
 * Class LessonAnswersMatchingScore.
 *
 * Calculate the score for Matching answers. Correct score / count of options.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LessonAnswersMatchingScore implements TransformPropertyInterface
{
    /**
     * @throws \Exception
     *
     * @return float|int
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
            $query = "SELECT
                    la.score,
                    COUNT(IF(score = 0 AND response IS NOT NULL, 1, NULL)) 'count'
                FROM mdl_lesson_answers la
                INNER JOIN mdl_lesson l ON (la.lessonid = l.id)
                WHERE la.pageid = ?
                    AND la.lessonid = ?
                    AND l.course = ?";

            $result = $connection->fetchAssoc($query, [$pageid, $lessonid, $course]);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"{$this->query}\".", 0, $e);
        }

        $connection->close();

        $score = (float) $result['score'];
        $count = (int) $result['count'];

        if (0 === $count) {
            return 0;
        }

        return $score / $count;
    }
}
