<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonPagesLoader.
 *
 * Loader for create a HTML document with the transformed data coming from a Moodle lesson page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonPagesDocumentLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $courseInfo = api_get_course_info_by_id($incomingData['c_id']);

        $lp = new \learnpath(
            $courseInfo['code'],
            $incomingData['lp_id'],
            1
        );

        $lp->generate_lp_folder($courseInfo);

        $docId = $lp->create_document(
            $courseInfo,
            $incomingData['item_content'],
            $incomingData['item_title'],
            'html'
        );

        $tblLpItem = \Database::get_course_table(TABLE_LP_ITEM);

        \Database::query("UPDATE $tblLpItem SET path = '$docId' WHERE iid = {$incomingData['item_id']}");

        return $docId;
    }
}
