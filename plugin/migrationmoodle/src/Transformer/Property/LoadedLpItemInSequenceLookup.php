<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CourseModulesLessonTask;
use Doctrine\DBAL\DBALException;
use Exception;
use MigrationMoodlePlugin;

/**
 * Class LoadedLpItemInSequenceLookup.
 *
 * Transform a Moodle course module in a Chamilo learning path item or section.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedLpItemInSequenceLookup extends LoadedKeyLookup
{
    /**
     * @param array $data
     *
     * @throws Exception
     *
     * @return int
     */
    public function transform(array $data)
    {
        $sequence = explode(',', $data['sequence']);

        $index = array_search($data['id'], $sequence);

        if (false === $index || !isset($sequence[$index - 1])) {
            return 0;
        }

        $mPreviousId = $sequence[$index - 1];

        try {
            $connection = MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new Exception('Unable to start connection.', 0, $e);
        }

        try {
            $query = "SELECT m.name
                FROM mdl_course_modules cm
                INNER JOIN mdl_modules m ON cm.module = m.id
                WHERE cm.id = ?";

            $result = $connection->fetchAssoc($query, [$mPreviousId]);
        } catch (DBALException $e) {
            throw new Exception("Unable to execute query \"{$this->query}\".", 0, $e);
        }

        $connection->close();

        switch ($result['name']) {
            case 'lesson':
                $this->calledClass = CourseModulesLessonTask::class;

                return parent::transform([$mPreviousId]);
        }

        return 0;
    }
}
