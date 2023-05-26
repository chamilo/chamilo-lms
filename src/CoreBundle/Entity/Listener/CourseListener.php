<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\ToolChain;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Exception;

/**
 * Class CourseListener.
 * Course entity listener, when a course is created/edited and when the tool chain is loaded.
 *
 * @todo check hosting course limits
 */
class CourseListener
{
    public function __construct(
        protected ToolChain $toolChain,
        protected SettingsManager $settingsManager
    ) {
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
     * @throws Exception
     */
    public function prePersist(Course $course, LifecycleEventArgs $args): void
    {
        //error_log('Course listener prePersist');
        /*if ($course) {
            // $this->checkLimit($repo, $course, $url);
            $this->toolChain->addToolsInCourse($course);
        }*/
    }

    public function postPersist(Course $course, LifecycleEventArgs $args): void
    {
        ///$this->checkLimit($repo, $course, $url);
        $this->toolChain->addToolsInCourse($course);

        $args->getObjectManager()->persist($course);
        $args->getObjectManager()->flush();
    }

    /**
     * This code is executed when a course is updated.
     *
     * @throws Exception
     */
    public function preUpdate(Course $course, LifecycleEventArgs $args): void
    {
        //error_log('preUpdate');
        /*$url = $course->getCurrentUrl();
        $repo = $args->getEntityManager()->getRepository('ChamiloCoreBundle:Course');
        $this->checkLimit($repo, $course, $url);*/
    }

    /*protected function checkLimit(CourseRepository $repo, Course $course, AccessUrl $url): void
    {
        $limit = $url->getLimitCourses();

        if (!empty($limit)) {
            $count = $repo->getCountCoursesByUrl($url);
            if ($count >= $limit) {
                api_warn_hosting_contact('hosting_limit_courses', $limit);

                throw new \Exception('PortalCoursesLimitReached');
            }
        }

        if (COURSE_VISIBILITY_HIDDEN != $course->getVisibility()) {
            $limit = $url->getLimitActiveCourses();

            if (!empty($limit)) {
                $count = $repo->getCountActiveCoursesByUrl($url);
                if ($count >= $limit) {
                    api_warn_hosting_contact('hosting_limit_active_courses', $limit);

                    throw new \Exception('PortalActiveCoursesLimitReached');
                }
            }
        }
    }*/
}
