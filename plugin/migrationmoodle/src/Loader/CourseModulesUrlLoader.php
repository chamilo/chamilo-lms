<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseModulesUrlLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseModulesUrlLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $courseId = api_get_course_int_id($incomingData['c_code']);

        $link = new \Link();
        $linkId = $link->save(
            [
                'c_id' => $courseId,
                'url' => $incomingData['url'],
                'title' => $incomingData['title'],
                'description' => null,
                'category_id' => null,
                'on_homepage' => '0',
                'target' => '_self',
                'session_id' => 0,
            ]
        );

        $lp = new \learnpath(
            $incomingData['c_code'],
            $incomingData['lp_id'],
            api_get_user_id()
        );

        return $lp->add_item(
            0,
            $incomingData['previous'],
            'link',
            $linkId,
            $incomingData['title'],
            ''
        );
    }
}
