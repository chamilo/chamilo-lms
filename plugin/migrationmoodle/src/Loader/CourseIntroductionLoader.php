<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseIntroductionLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseIntroductionLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $intro = new CToolIntro();
        $intro
            ->setSessionId(0)
            ->setCId($incomingData['c_id'])
            ->setId(TOOL_COURSE_HOMEPAGE)
            ->setIntroText($incomingData['description']);

        $em = \Database::getManager();
        $em->persist($intro);
        $em->flush();

        return $intro->getIid();
    }
}
