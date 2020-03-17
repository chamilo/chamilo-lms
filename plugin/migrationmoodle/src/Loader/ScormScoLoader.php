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
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $scorm = new \scorm(
            $incomingData['c_code'],
            $incomingData['lp_id'],
            api_get_user_id()
        );

        $itemId = $scorm->add_item(
            $incomingData['parent_item_id'],
            0,
            $incomingData['item_type'],
            0,
            $incomingData['title'],
            ''
        );

        $item = $em->find('ChamiloCourseBundle:CLpItem', $itemId);
        $item
            ->setPath($incomingData['path'])
            ->setRef($incomingData['ref']);

        $em->persist($item);
        $em->flush();

        return $item->getId();
    }
}
