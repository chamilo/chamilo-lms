<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Repository\CourseRepository;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CourseBundle\ToolChain;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Class CourseListener
 * Course entity listener, when a course is created/edited and when the tool chain is loaded.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class CourseListener
{
    protected $toolChain;

    /**
     * @param ToolChain $toolChain
     */
    public function __construct(ToolChain $toolChain)
    {
        $this->toolChain = $toolChain;
    }

    /**
     * This code is executed when a new course is created.
     *
     * new object : prePersist
     * edited object: preUpdate
     *
     * This function add the course tools to the current course entity
     * thanks to the tool chain see src/Chamilo/CourseBundle/ToolChain.php
     *
     * @param Course             $course
     * @param LifecycleEventArgs $args
     *
     * @throws \Exception
     */
    public function prePersist(Course $course, LifecycleEventArgs $args)
    {
        /** @var AccessUrlRelCourse $urlRelCourse */
        $urlRelCourse = $course->getUrls()->first();
        $url = $urlRelCourse->getUrl();

        $repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Course');

        $this->checkLimit($repo, $course, $url);

        $this->toolChain->addToolsInCourse($course);
        /*
        error_log('ddd');
        $course->setDescription( ' dq sdqs dqs dqs ');

        $args->getEntityManager()->persist($course);
        $args->getEntityManager()->flush();*/
    }

    /**
     * This code is executed when a course is updated.
     *
     * @param Course             $course
     * @param LifecycleEventArgs $args
     *
     * @throws \Exception
     */
    public function preUpdate(Course $course, LifecycleEventArgs $args)
    {
        $url = $course->getCurrentUrl();

        $repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Course');

        $this->checkLimit($repo, $course, $url);

        /*if ($eventArgs->getEntity() instanceof User) {
            if ($eventArgs->hasChangedField('name') && $eventArgs->getNewValue('name') == 'Alice') {
                $eventArgs->setNewValue('name', 'Bob');
            }
        }*/
    }

    /**
     * @param CourseRepository $repo
     * @param Course           $course
     * @param AccessUrl        $url
     *
     * @throws \Exception
     */
    private function checkLimit($repo, Course $course, AccessUrl $url)
    {
        $limit = $url->getLimitCourses();

        if (!empty($limit)) {
            $count = $repo->getCountCoursesByUrl($url);
            if ($count >= $limit) {
                api_warn_hosting_contact('hosting_limit_courses', $limit);

                throw new \Exception('PortalCoursesLimitReached');
            }
        }

        if ($course->getVisibility() != COURSE_VISIBILITY_HIDDEN) {
            $limit = $url->getLimitActiveCourses();

            if (!empty($limit)) {
                $count = $repo->getCountActiveCoursesByUrl($url);
                if ($count >= $limit) {
                    api_warn_hosting_contact('hosting_limit_active_courses', $limit);

                    throw new \Exception('PortalActiveCoursesLimitReached');
                }
            }
        }
    }
}
