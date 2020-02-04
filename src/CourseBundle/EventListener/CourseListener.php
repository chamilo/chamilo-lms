<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class CourseListener.
 * Sets the course and session objects in the controller that implements the CourseControllerInterface.
 */
class CourseListener
{
    use ContainerAwareTrait;

    /**
     * Get request from the URL cidReq, c_id or the "ABC" in the courses url (courses/ABC/index.php).
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        // Ignore debug
        if ('_wdt' === $request->attributes->get('_route')) {
            return;
        }

        // Ignore toolbar
        if ('_wdt' === $request->attributes->get('_profiler')) {
            return;
        }

        $sessionHandler = $request->getSession();

        $container = $this->container;
        $translator = $container->get('translator');

        $course = null;
        // Check if URL has cid value. Using Symfony request.
        $courseId = $request->get('cid');

        if (!empty($courseId)) {
            /** @var EntityManager $em */
            $em = $container->get('doctrine')->getManager();
            $checker = $container->get('security.authorization_checker');
            $course = $em->getRepository('ChamiloCoreBundle:Course')->find($courseId);

            if (null === $course) {
                throw new NotFoundHttpException($translator->trans('Course does not exist'));
            }
        }

        global $cidReset;
        if (true === $cidReset) {
            $this->removeCourseFromSession($request);

            return;
        }

        if (null !== $course) {
            // Setting variables in the session.
            $sessionHandler->set('course', $course);

            $sessionHandler->set('_real_cid', $course->getId());
            $sessionHandler->set('cid', $course->getId());
            $sessionHandler->set('_cid', $course->getCode());

            $courseInfo = api_get_course_info($course->getCode());
            $sessionHandler->set('_course', $courseInfo);

            // Setting variables for the twig templates.
            $container->get('twig')->addGlobal('course', $course);

            // Checking if sid is used.
            $sessionId = (int) $request->get('sid');
            $session = null;
            if (empty($sessionId)) {
                $sessionHandler->remove('session_name');
                $sessionHandler->remove('sid');
                $sessionHandler->remove('session');
                // Check if user is allowed to this course
                // See CourseVoter.php
                if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
                    throw new AccessDeniedException($translator->trans('Unauthorised access to course!'));
                }
            } else {
                $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
                if ($session) {
                    if (false === $session->hasCourse($course)) {
                        throw new AccessDeniedException($translator->trans('Course is not registered in the Session'));
                    }

                    //$course->setCurrentSession($session);
                    $session->setCurrentCourse($course);
                    // Check if user is allowed to this course-session
                    // See SessionVoter.php
                    if (false === $checker->isGranted(SessionVoter::VIEW, $session)) {
                        throw new AccessDeniedException($translator->trans('Unauthorised access to session!'));
                    }

                    $sessionHandler->set('session_name', $session->getName());
                    $sessionHandler->set('sid', $session->getId());
                    $sessionHandler->set('session', $session);

                    $container->get('twig')->addGlobal('session', $session);
                } else {
                    throw new NotFoundHttpException($translator->trans('Session not found'));
                }
            }

            // Group
            $groupId = (int) $request->get('gid');

            if (empty($groupId)) {
                $sessionHandler->remove('gid');
            } else {
                $group = $em->getRepository('ChamiloCourseBundle:CGroupInfo')->find($groupId);

                if (!$group) {
                    throw new NotFoundHttpException($translator->trans('Group not found'));
                }

                if ($course->hasGroup($group)) {
                    // Check if user is allowed to this course-group
                    // See GroupVoter.php
                    if (false === $checker->isGranted(GroupVoter::VIEW, $group)) {
                        throw new AccessDeniedException($translator->trans('Unauthorised access to group'));
                    }
                    $sessionHandler->set('gid', $groupId);
                } else {
                    throw new AccessDeniedException($translator->trans('Group does not exist in course'));
                }
            }

            $origin = $request->get('origin');
            if (!empty($origin)) {
                $sessionHandler->set('origin', $origin);
            }

            $courseParams = $this->generateCourseUrl($course, $sessionId, $groupId, $origin);
            $sessionHandler->set('course_url_params', $courseParams);
            $container->get('twig')->addGlobal('course_url_params', $courseParams);

            /*if (!$alreadyVisited ||
                isset($alreadyVisited) && $alreadyVisited != $courseCode
            ) {
                // Course access events
                $dispatcher = $this->container->get('event_dispatcher');
                if (empty($sessionId)) {
                    $dispatcher->dispatch('chamilo_course.course.access', new CourseAccess($user, $course));
                } else {
                    $dispatcher->dispatch(
                        'chamilo_course.course.access',
                        new SessionAccess($user, $course, $session)
                    );
                }
                $coursesAlreadyVisited[$course->getCode()] = 1;
                $sessionHandler->set('course_already_visited', $courseCode);
            }*/

            Container::setRequest($request);
            Container::setContainer($container);
            Container::setLegacyServices($container);
        }
    }

    public function onKernelResponse(ResponseEvent $event)
    {
    }

    /**
     * Once the onKernelRequest was fired, we check if the course/session object were set and we inject them in the controller.
     */
    public function onKernelController(ControllerEvent $event)
    {
        $controllerList = $event->getController();

        if (!is_array($controllerList)) {
            return;
        }

        $request = $event->getRequest();
        $sessionHandler = $request->getSession();

        /** @var ContainerInterface $container */
        //$container = $this->container;

        /*if ($course) {
            $courseLanguage = $course->getCourseLanguage();
            //error_log('onkernelcontroller request: '.$courseLanguage);
            if (!empty($courseLanguage)) {
                $request->setLocale($courseLanguage);
                $sessionHandler->set('_locale', $courseLanguage);
                $this->container->get('session')->set('_locale', $courseLanguage);
            }
        }*/

        $courseId = (int) $request->get('cid');
        //$groupId = (int) $request->get('gid');
        //$sessionId = (int) $request->get('sid');

        // cidReset is set in the global.inc.php files
        global $cidReset;
        //$cidReset = $sessionHandler->get('cid_reset', false);

        // This controller implements ToolInterface? Then set the course/session
        if (is_array($controllerList) &&
            (
                $controllerList[0] instanceof CourseControllerInterface
                //$controllerList[0] instanceof ResourceController

                //|| $controllerList[0] instanceof LegacyController
            )
        ) {
            //var_dump($courseId);
            if (!empty($courseId)) {
                $controller = $controllerList[0];
                $session = $sessionHandler->get('session');
                $course = $sessionHandler->get('course');

                // Sets the controller course/session in order to use:
                // $this->getCourse() $this->getSession() in controllers
                if ($course) {
                    $controller->setCourse($course);
                    // Legacy code
                    //$courseCode = $course->getCode();
                    //$courseInfo = api_get_course_info($courseCode);
                    //$container->get('twig')->addGlobal('course', $course);
                    //$sessionHandler->set('_real_cid', $course->getId());
                    //$sessionHandler->set('_cid', $course->getCode());
                    //$sessionHandler->set('_course', $courseInfo);
                }

                if ($session) {
                    $controller->setSession($session);
                }
            }

            // Example 'chamilo_notebook.controller.notebook:indexAction'
            //$controllerAction = $request->get('_controller');
            //$controllerActionParts = explode(':', $controllerAction);
            //$controllerNameParts = explode('.', $controllerActionParts[0]);
            //$controllerName = $controllerActionParts[0];
        } else {
            /*$ignore = [
                'fos_js_routing.controller:indexAction',
                'web_profiler.controller.profiler:toolbarAction',
            ];

            $controllerAction = $request->get('_controller');
            if (!in_array($controllerAction, $ignore)) {
                //error_log('remove');
                //$this->removeCourseFromSession($request);
            }*/
        }
    }

    public function removeCourseFromSession(Request $request)
    {
        $sessionHandler = $request->getSession();
        $alreadyVisited = $sessionHandler->get('course_already_visited');
        if ($alreadyVisited) {
            // "Logout" course
            $sessionHandler->remove('course_already_visited');
        }

        $sessionHandler->remove('toolgroup');
        $sessionHandler->remove('_cid');
        $sessionHandler->remove('cid');
        $sessionHandler->remove('sid');
        $sessionHandler->remove('gid');
        $sessionHandler->remove('is_allowed_in_course');
        $sessionHandler->remove('_real_cid');
        $sessionHandler->remove('_course');
        $sessionHandler->remove('_locale_course');
        $sessionHandler->remove('course');
        $sessionHandler->remove('session');
        $sessionHandler->remove('course_url_params');
        $sessionHandler->remove('origin');

        // Remove user temp roles
        /** @var User $user */
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $user->removeRole('ROLE_CURRENT_COURSE_STUDENT');
                $user->removeRole('ROLE_CURRENT_COURSE_TEACHER');
                $user->removeRole('ROLE_CURRENT_SESSION_COURSE_STUDENT');
                $user->removeRole('ROLE_CURRENT_SESSION_COURSE_TEACHER');
            }
        }

        //$request->setLocale($request->getPreferredLanguage());
    }

    /**
     * @param Course $course
     * @param int    $sessionId
     * @param int    $groupId
     * @param string $origin
     */
    private function generateCourseUrl($course, $sessionId, $groupId, $origin): string
    {
        if ($course) {
            $cidReqURL = '&cid='.$course->getId();
            $cidReqURL .= '&sid='.$sessionId;
            $cidReqURL .= '&gid='.$groupId;
            $cidReqURL .= '&origin='.$origin;

            return $cidReqURL;
        }

        return '';
    }
}
