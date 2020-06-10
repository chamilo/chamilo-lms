<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Interfaces;

/**
 * Interface LoaderInterface.
 */
interface LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData);
}
