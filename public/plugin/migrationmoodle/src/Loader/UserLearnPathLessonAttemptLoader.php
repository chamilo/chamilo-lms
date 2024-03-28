<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

/**
 * Class UserLearnPathLessonAttemptLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathLessonAttemptLoader extends UserLearnPathLessonBranchLoader
{
    /**
     * @throws \Exception
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $itemViewId = parent::load($incomingData);

        if ((bool) $incomingData['is_correct']) {
            \Database::query("UPDATE $tblLpItemView SET score = max_score WHERE iid = $itemViewId");
        } else {
            \Database::query("UPDATE $tblLpItemView SET score = 0 WHERE iid = $itemViewId");
        }

        return $itemViewId;
    }
}
