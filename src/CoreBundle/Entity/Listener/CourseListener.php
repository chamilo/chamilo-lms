<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\CourseRepository;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Class CourseListener.
 * Course entity listener, when a course is created/edited and when the tool chain is loaded.
 *
 * @todo check hosting course limits
 */
class CourseListener
{
    /**
     * @var ToolChain
     */
    protected $toolChain;

    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * CourseListener constructor.
     */
    public function __construct(ToolChain $toolChain, SettingsManager $settingsManager)
    {
        $this->toolChain = $toolChain;
        $this->settingsManager = $settingsManager;
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
     * @throws \Exception
     */
    public function prePersist(Course $course, LifecycleEventArgs $args)
    {
        /** @var AccessUrlRelCourse $urlRelCourse */
        if ($course) {
            /*$urlRelCourse = $course->getUrls()->first();
            $url = $urlRelCourse->getUrl();*/
            //$url = $course->getCurrentUrl();
            //$repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Course');
            ///$this->checkLimit($repo, $course, $url);
           // $this->toolChain->addToolsInCourse($course);
        }
    }

    public function postPersist(Course $course, LifecycleEventArgs $args)
    {
        /** @var AccessUrlRelCourse $urlRelCourse */
        if ($course) {
            /*$urlRelCourse = $course->getUrls()->first();
            $url = $urlRelCourse->getUrl();*/
            //$url = $course->getCurrentUrl();
            //$repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Course');
            ///$this->checkLimit($repo, $course, $url);
            $this->toolChain->addToolsInCourse($course);
        }
    }


    /**
     * This code is executed when a course is updated.
     *
     * @throws \Exception
     */
    public function preUpdate(Course $course, LifecycleEventArgs $args)
    {
        if ($course) {
            /*$url = $course->getCurrentUrl();
            $repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Course');
            $this->checkLimit($repo, $course, $url);*/
        }
    }

    /**
     * @param CourseRepository $repo
     *
     * @throws \Exception
     */
    protected function checkLimit($repo, Course $course, AccessUrl $url)
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
