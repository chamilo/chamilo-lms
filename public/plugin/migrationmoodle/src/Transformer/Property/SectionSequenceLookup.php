<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesLessonTask;
use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesQuizTask;
use Doctrine\DBAL\DBALException;

/**
 * Class LoadedLpItemInSequenceLookup.
 *
 * Transform a Moodle's course section sequence in a sort list for learning path item.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class SectionSequenceLookup extends LoadedKeyLookup
{
    /**
     * @throws \Exception
     *
     * @return array
     */
    public function transform(array $data)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $exception) {
            throw new \Exception('Unable to start connection.', 0, $exception);
        }

        $mSequence = explode(',', $data['sequence']);
        $lpOrderList = [];

        foreach ($mSequence as $mModuleId) {
            try {
                $query = "SELECT m.name
                    FROM mdl_course_modules cm
                    INNER JOIN mdl_modules m ON cm.module = m.id
                    WHERE cm.id = ?";

                $result = $connection->fetchAssoc($query, [$mModuleId]);
            } catch (DBALException $exception) {
                throw new \Exception("Unable to execute query \"{$this->query}\".", 0, $exception);
            }

            if (empty($result)) {
                continue;
            }

            switch ($result['name']) {
                case 'lesson':
                    $this->calledClass = CourseModulesLessonTask::class;
                    break;
                case 'quiz':
                    $this->calledClass = CourseModulesQuizTask::class;
                    break;
                default:
                    break;
            }

            $lpItemId = parent::transform([$mModuleId]);

            $lpOrderList[$lpItemId] = 0;
        }

        $connection->close();

        return $lpOrderList;
    }
}
