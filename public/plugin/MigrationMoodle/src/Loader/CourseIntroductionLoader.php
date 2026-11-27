<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
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
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $course = $em->find(Course::class, (int) $incomingData['c_id']);

        if (!$course) {
            throw new \Exception("Course ({$incomingData['c_id']}) not found.");
        }

        // c_tool_intro has no c_id, session_id nor id any more: the introduction belongs to the course homepage
        // tool of the course (c_tool) and to the course through its resource node and link.
        $courseTool = (new CToolIntroHelper($em))->getOrCreateCourseTool($course, TOOL_COURSE_HOMEPAGE, null);

        if (!$courseTool) {
            throw new \Exception('The course homepage tool is not available on this platform.');
        }

        $intro = (new CToolIntro())
            ->setCourseTool($courseTool)
            ->setIntroText((string) $incomingData['description'])
            ->setCreator($em->getReference(User::class, 1))
            ->setParent($course)
        ;
        $intro->addCourseLink($course, null);

        $em->persist($intro);
        $em->flush();

        return $intro->getIid();
    }
}
