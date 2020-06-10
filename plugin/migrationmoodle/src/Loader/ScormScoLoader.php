<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class ScormScoLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class ScormScoLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $scorm = new \scorm(
            $incomingData['c_code'],
            $incomingData['lp_id'],
            1
        );

        $itemId = $scorm->add_item(
            $incomingData['parent_item_id'],
            0,
            $incomingData['item_type'],
            0,
            $incomingData['title'],
            ''
        );

        $tblLpItem = \Database::get_course_table(TABLE_LP_ITEM);

        \Database::query(
            "UPDATE $tblLpItem SET path = '{$incomingData['path']}', ref = '{$incomingData['ref']}' WHERE iid = $itemId"
        );

        return $itemId;
    }
}
