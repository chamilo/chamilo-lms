<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Interfaces;

/**
 * Interface ExtractorInterface.
 */
interface ExtractorInterface
{
    /**
     * @return bool
     */
    public function filter(array $sourceData);

    /**
     * @throws Exception
     *
     * @return iterable
     */
    public function extract();
}
