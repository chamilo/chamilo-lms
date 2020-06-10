<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class LoadedCourseFromQuestionCategoryLookup.
 *
 * Lookup for a course ID loaded based on the context from a Moodle question category.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseFromQuestionCategoryLookup extends LoadedCourseLookup
{
    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        $instanceId = $data['instanceid'];
        $contextLevel = $data['contextlevel'];

        $mCourseId = 0;

        switch ($contextLevel) {
            case 50: // course
                $mCourseId = $instanceId;
                break;
            case 70: // module
                $mCourseId = $this->searchInModuleContext($instanceId);
                break;
        }

        if (empty($mCourseId)) {
            throw new \Exception("Course not found for context $instanceId with level $contextLevel");
        }

        return parent::transform([$mCourseId]);
    }

    /**
     * @param int $instanceId
     *
     * @throws \Exception
     *
     * @return int
     */
    private function searchInModuleContext($instanceId)
    {
        try {
            $connection = \MigrationMoodlePlugin::create()->getConnection();
        } catch (DBALException $e) {
            throw new \Exception('Unable to start connection.', 0, $e);
        }

        $query = "SELECT course FROM mdl_course_modules WHERE id = ?";

        try {
            $statement = $connection->executeQuery($query, [$instanceId]);
        } catch (DBALException $e) {
            throw new \Exception("Unable to execute query \"$query\".", 0, $e);
        }

        $result = $statement->fetch(FetchMode::ASSOCIATIVE);

        $connection->close();

        if (false === $result) {
            return 0;
        }

        return (int) $result['course'];
    }
}
