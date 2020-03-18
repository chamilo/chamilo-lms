<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CourseModulesScormLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseModulesScormLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $tblLpMain = \Database::get_course_table(TABLE_LP_MAIN);

        $resultDisplayOrder = \Database::query(
            "SELECT
                CASE WHEN MAX(display_order) > 0 THEN MAX(display_order) + 1 ELSE 1 END AS display_order
                FROM $tblLpMain WHERE c_id = {$incomingData['c_id']}"
        );
        $row = \Database::fetch_assoc($resultDisplayOrder);
        $displayOrder = $row['display_order'];

        $courseInfo = api_get_course_info_by_id($incomingData['c_id']);
        $userId = 1;

        $incomingData['path'] = $this->createDirectory($incomingData['name'], $courseInfo['code']);
        $incomingData['use_max_score'] = $incomingData['use_max_score'] == 100;

        $incomingData['created_on'] = $incomingData['created_on']
            ? $incomingData['created_on']->format('Y-m-d h:i:s')
            : null;
        $incomingData['modified_on'] = $incomingData['modified_on']
            ? $incomingData['modified_on']->format('Y-m-d h:i:s')
            : null;
        $incomingData['publicated_on'] = $incomingData['publicated_on']
            ? $incomingData['publicated_on']->format('Y-m-d h:i:s')
            : null;

        $params = array_merge(
            $incomingData,
            [
                'lp_type' => 2,
                'description' => '',
                'force_commit' => 0,
                'default_view_mod' => 'embedded',
                'default_encoding' => 'UTF-8',
                'js_lib' => 'scorm_api.php',
                'display_order' => $displayOrder,
                'session_id' => 0,
                'content_maker' => '',
                'content_license' => '',
                'debug' => 0,
                'theme' => '',
                'preview_image' => '',
                'author' => '',
                'prerequisite' => 0,
                'seriousgame_mode' => 0,
                'autolaunch' => 0,
                'category_id' => 0,
                'max_attempts' => 0,
                'subscribe_users' => 0,
            ]
        );

        $lpId = \Database::insert($tblLpMain, $params);

        if ($lpId) {
            \Database::query("UPDATE $tblLpMain SET id = iid WHERE iid = $lpId");

            api_item_property_update($courseInfo, TOOL_LEARNPATH, $lpId, 'LearnpathAdded', $userId);
            api_item_property_update($courseInfo, TOOL_LEARNPATH, $lpId, 'visible', $userId);
        }

        return $lpId;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public static function generateDirectoryName($fileName)
    {
        $newDirectory = trim($fileName);
        $newDirectory = trim($newDirectory, '/');

        return api_replace_dangerous_char($newDirectory);
    }

    /**
     * @param string $name
     * @param string $courseCode
     *
     * @return string
     */
    private function createDirectory($name, $courseCode)
    {
        $courseRelDir = api_get_path(SYS_COURSE_PATH).api_get_course_path($courseCode).'/scorm';

        $newDirectory = self::generateDirectoryName($name);

        $fullPath = "$courseRelDir/$newDirectory";

        $fileSystem = new Filesystem();

        if (!is_dir($fullPath)) {
            $fileSystem->mkdir(
                $fullPath,
                api_get_permissions_for_new_directories()
            );
        }

        return "$newDirectory/.";
    }
}
