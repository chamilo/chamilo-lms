<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UrlLoader.
 *
 * The Link created is added in a learning path (from a Moodle course section).
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UrlLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $params = [
            'c_id' => $incomingData['c_id'],
            'url' => $incomingData['url'],
            'title' => $incomingData['title'],
            'description' => null,
            'category_id' => null,
            'on_homepage' => '0',
            'target' => '_self',
            'session_id' => 0,
        ];

        $link = new \Link();
        $link->setCourse(
            api_get_course_info_by_id($incomingData['c_id'])
        );
        $linkId = $link->save($params);

        $tblLpItem = \Database::get_course_table(TABLE_LP_ITEM);

        \Database::query("UPDATE $tblLpItem SET path = '$linkId' WHERE iid = {$incomingData['item_id']}");

        return $linkId;
    }
}
