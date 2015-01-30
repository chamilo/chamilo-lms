<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Doctrine\ORM\EntityManager;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Chamilo\CourseBundle\Controller\ToolInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class CourseListener
 * @package Chamilo\CourseBundle\EventListener
 */
class CourseListener
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {

    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }

        // This controller implements ToolInterface? Then set the course/session
        if ($controller[0] instanceof ToolInterface) {
        //if ($controller[0] instanceof ToolBaseController) {
            //$token = $event->getRequest()->query->get('token');
            $kernel = $event->getKernel();
            $request = $event->getRequest();

            /** @var ContainerInterface $container */
            $container = $this->container;

            // Course
            // The 'course' variable example "123" for this URL: courses/123/
            $courseCode = $request->get('course');

            // Detect if the course was set with a cidReq:
            if (empty($courseCode)) {
                $courseCodeFromRequest = $request->get('cidReq');
                $courseCode = $courseCodeFromRequest;
            }

            /** @var EntityManager $em */
            $em = $container->get('doctrine')->getManager();

            $securityChecker = $container->get('security.authorization_checker');
            /** @var User $user */
            $user = $container->get('security.token_storage')->getToken()->getUser();

            if (!empty($courseCode)) {
                /** @var Course $course */
                $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneByCode($courseCode);
                if ($course) {
                    // Session
                    $sessionId = $request->get('id_session');

                    if (empty($sessionId)) {
                        // Check if user is allowed to this course
                        // See CourseVoter.php
                        if (false === $securityChecker->isGranted(CourseVoter::VIEW, $course)) {
                            throw new AccessDeniedException('Unauthorised access to course!');
                        }
                    } else {
                        $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
                        if ($session) {
                            //$course->setCurrentSession($session);
                            $controller[0]->setSession($session);
                            $session->setCurrentCourse($course);
                            // Check if user is allowed to this course-session
                            // See SessionVoter.php
                            if (false === $securityChecker->isGranted(SessionVoter::VIEW, $session)) {
                                throw new AccessDeniedException('Unauthorised access to session!');
                            }
                        } else {
                            throw new NotFoundHttpException('Session not found');
                        }
                    }

                    // Legacy code

                    $courseInfo = api_get_course_info($course->getCode());
                    $container->get('twig')->addGlobal('course', $course);
                    $request->getSession()->set('_real_cid', $course->getId());
                    $request->getSession()->set('_cid', $course->getCode());
                    $request->getSession()->set('_course', $courseInfo);

                    /*
                    Sets the controller course in order to use $this->getCourse()
                    */
                    $controller[0]->setCourse($course);
                } else {
                    throw new NotFoundHttpException('Course not found');
                }
            }
        }
    }
}
