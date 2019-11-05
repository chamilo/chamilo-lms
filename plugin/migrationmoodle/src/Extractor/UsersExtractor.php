<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

/**
 * Class UsersExtractor.
 */
class UsersExtractor extends BaseExtractor
{
    /**
     * @param array $sourceData
     *
     * @return bool
     */
    public function filter(array $sourceData)
    {
        return in_array(
            $sourceData['username'],
            ['admin', 'guest']
        );
    }
}
