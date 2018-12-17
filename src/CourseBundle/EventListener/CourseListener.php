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
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class CourseListener.
 * Sets the course and session objects in the controller that implements the CourseControllerInterface.
 *
 * @package Chamilo\CourseBundle\EventListener
 */
class CourseListener
{
    use ContainerAwareTrait;

    /**
     * Get request from the URL cidReq, c_id or the "ABC" in the courses url (courses/ABC/index.php).
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
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
        if ($request->attributes->get('_route') === '_wdt') {
            return;
        }
        // Ignore toolbar
        if ($request->attributes->get('_profiler') === '_wdt') {
            return;
        }

        $sessionHandler = $request->getSession();

        $container = $this->container;
        $translator = $container->get('translator');
        $courseCode = $request->get('course');

        // Detect if the course was set with a cidReq:
        if (empty($courseCode)) {
            $courseCodeFromRequest = $request->get('cidReq');
            $courseCode = $courseCodeFromRequest;
        }

        if (empty($courseCode)) {
            if (!empty($request->get('cDir'))) {
                $courseCode = $request->get('cDir');
            }
        }

        $courseId = null;
        if (empty($courseCode)) {
            $courseId = $request->get('c_id');
        }

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $checker = $container->get('security.authorization_checker');
        //$alreadyVisited = $sessionHandler->get('course_already_visited');

        $course = null;
        if (!empty($courseCode)) {
            // Try with the course code
            /** @var Course $course */
            $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneBy(['code' => $courseCode]);
            if ($course === null) {
                $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneBy(['directory' => $courseCode]);
                if ($course === null) {
                    throw new NotFoundHttpException($translator->trans('Course does not exist'));
                }
                //throw new NotFoundHttpException($translator->trans('Course does not exist'));
            }
        }

        if ($course === null && !empty($courseId)) {
            /** @var Course $course */
            $course = $em->getRepository('ChamiloCoreBundle:Course')->find($courseId);
            if ($course === null) {
                throw new NotFoundHttpException($translator->trans('Course does not exist'));
            }
        }

        global $cidReset;
        if ($cidReset === true) {
            $this->removeCourseFromSession($request);

            return;
        }

        if (!empty($course)) {
            $sessionHandler->set('courseObj', $course);
            $courseInfo = api_get_course_info($course->getCode());
            $container->get('twig')->addGlobal('course', $course);

            $sessionHandler->set('_real_cid', $course->getId());
            $sessionHandler->set('_cid', $course->getCode());
            $sessionHandler->set('_course', $courseInfo);

            // Session
            $sessionId = (int) $request->get('id_session');
            $session = null;
            if (empty($sessionId)) {
                $sessionHandler->remove('session_name');
                $sessionHandler->remove('id_session');
                $sessionHandler->remove('sessionObj');
                // Check if user is allowed to this course
                // See CourseVoter.php
                if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
                    throw new AccessDeniedException($translator->trans('Unauthorised access to course!'));
                }
            } else {
                $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
                if ($session) {
                    if ($session->hasCourse($course) === false) {
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
                    $sessionHandler->set('id_session', $session->getId());
                    $sessionHandler->set('sessionObj', $session);
                } else {
                    throw new NotFoundHttpException($translator->trans('Session not found'));
                }
            }

            // Group
            $groupId = (int) $request->get('gidReq');

            if (!empty($groupId)) {
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
                    $sessionHandler->set('_gid', $groupId);
                } else {
                    throw new AccessDeniedException($translator->trans('Group does not exist in course'));
                }
            }

            $origin = $request->get('origin');
            if (!empty($origin)) {
                $sessionHandler->set('origin', $origin);
            }

            $sessionHandler->set('cid_req_url', $this->generateCourseUrl($course, $sessionId, $groupId, $origin));

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

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * Once the onKernelRequest was fired, we check if the session object were set and we inject them in the controller.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controllerList = $event->getController();

        if (!is_array($controllerList)) {
            return;
        }

        $request = $event->getRequest();
        $sessionHandler = $request->getSession();

        /** @var ContainerInterface $container */
        $container = $this->container;

        /*if ($course) {
            $courseLanguage = $course->getCourseLanguage();
            //error_log('onkernelcontroller request: '.$courseLanguage);
            if (!empty($courseLanguage)) {
                $request->setLocale($courseLanguage);
                $sessionHandler->set('_locale', $courseLanguage);
                $this->container->get('session')->set('_locale', $courseLanguage);
            }
        }*/

        $groupId = (int) $request->get('gidReq');
        $sessionId = (int) $request->get('id_session');

        // cidReset is set in the global.inc.php files
        global $cidReset;
        //$cidReset = $sessionHandler->get('cid_reset', false);

        // This controller implements ToolInterface? Then set the course/session
        if (is_array($controllerList) &&
            (
                $controllerList[0] instanceof CourseControllerInterface ||
                $controllerList[0] instanceof ResourceController

                //|| $controllerList[0] instanceof LegacyController
            )
        ) {
            $controller = $controllerList[0];

            $session = $sessionHandler->get('sessionObj');
            $course = $sessionHandler->get('courseObj');

            // Sets the controller course/session in order to use:
            // $this->getCourse() $this->getSession() in controllers
            if ($course) {
                $controller->setCourse($course);

                // Legacy code
                $courseCode = $course->getCode();

                //$courseInfo = api_get_course_info($courseCode);
                //$container->get('twig')->addGlobal('course', $course);

                //$sessionHandler->set('_real_cid', $course->getId());
                //$sessionHandler->set('_cid', $course->getCode());
                //$sessionHandler->set('_course', $courseInfo);
            }

            if ($session) {
                $controller->setSession($session);
            }

            // Example 'chamilo_notebook.controller.notebook:indexAction'
            $controllerAction = $request->get('_controller');
            $controllerActionParts = explode(':', $controllerAction);
            $controllerNameParts = explode('.', $controllerActionParts[0]);
            $controllerName = $controllerActionParts[0];

        /*$toolName = null;
        $toolAction = null;
        if (isset($controllerNameParts[1]) &&
            $controllerNameParts[1] == 'controller'
        ) {
            $toolName = $this->container->get($controllerName)->getToolName();
            $action = str_replace('action', '', $controllerActionParts[1]);
            $toolAction = $toolName.'.'.$action;
        }*/

            //$container->get('twig')->addGlobal('tool.name', $toolName);
            //$container->get('twig')->addGlobal('tool.action', $toolAction);

            //$sessionHandler->set('_gid', $groupId);
            //$sessionHandler->set('is_allowed_in_course', true);
            //$sessionHandler->set('id_session', $sessionId);
        } else {
            $ignore = [
                'fos_js_routing.controller:indexAction',
                'web_profiler.controller.profiler:toolbarAction',
            ];

            $controllerAction = $request->get('_controller');
            if (!in_array($controllerAction, $ignore)) {
                //error_log('remove');
                //$this->removeCourseFromSession($request);
            }
        }
    }

    /**
     * @param Request $request
     */
    public function removeCourseFromSession(Request $request)
    {
        $sessionHandler = $request->getSession();
        $alreadyVisited = $sessionHandler->get('course_already_visited');
        if ($alreadyVisited) {
            // "Logout" course
            $sessionHandler->remove('course_already_visited');
        }

        $sessionHandler->remove('toolgroup');
        $sessionHandler->remove('_gid');
        $sessionHandler->remove('is_allowed_in_course');
        $sessionHandler->remove('_real_cid');
        $sessionHandler->remove('_cid');
        $sessionHandler->remove('_course');
        $sessionHandler->remove('id_session');
        $sessionHandler->remove('_locale_course');
        $sessionHandler->remove('courseObj');
        $sessionHandler->remove('sessionObj');
        $sessionHandler->remove('cid_req_url');
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
     *
     * @return string
     */
    private function generateCourseUrl($course, $sessionId, $groupId, $origin): string
    {
        if ($course) {
            $cidReqURL = '&cidReq='.$course->getCode();
            $cidReqURL .= '&id_session='.$sessionId;
            $cidReqURL .= '&gidReq='.$groupId;
            $cidReqURL .= '&origin='.$origin;

            return $cidReqURL;
        }

        return '';
    }
}
