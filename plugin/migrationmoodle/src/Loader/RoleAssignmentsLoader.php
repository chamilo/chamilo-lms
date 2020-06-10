<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class RoleAssignmentsLoader.
 *
 * Loader to subscribe a Chamilo user in a course according their Moodle role assignment.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class RoleAssignmentsLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $result = \CourseManager::subscribeUser(
            $incomingData['user_id'],
            $incomingData['course_code'],
            $incomingData['status']
        );

        return (int) $result;
    }
}
