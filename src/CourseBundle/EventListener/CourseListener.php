<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CoreBundle\Controller\LegacyController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CourseBundle\Controller\ToolInterface;
use Chamilo\CourseBundle\Event\CourseAccess;
use Chamilo\CourseBundle\Event\SessionAccess;
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

/**
 * Class CourseListener.
 *
 * @package Chamilo\CourseBundle\EventListener
 */
class CourseListener
{
    use ContainerAwareTrait;

    /**
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

        $sessionHandler = $event->getRequest()->getSession();

        $container = $this->container;
        $translator = $container->get('translator');
        $courseCode = $request->get('course');

        // Detect if the course was set with a cidReq:
        if (empty($courseCode)) {
            $courseCodeFromRequest = $request->get('cidReq');
            $courseCode = $courseCodeFromRequest;
        }

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();

        $checker = $container->get('security.authorization_checker');

        $user = $request->getUser();
        var_dump(get_class($user));
        var_dump(get_class($sessionHandler));
        var_dump(get_class($event));

        /*$user = $this->container->get('security.token_storage')->getToken()->getUser();
        var_dump(get_class($user));*/
        var_dump($courseCode);
        $alreadyVisited = $sessionHandler->get('course_already_visited');

        if (!empty($courseCode)) {
            /** @var Course $course */
            $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneByCode($courseCode);
            if ($course) {
                $sessionHandler->set('courseObj', $course);

                // Session
                $sessionId = intval($request->get('id_session'));
                $session = null;

                // Group
                $groupId = intval($request->get('gidReq'));
                if (empty($sessionId)) {
                    // Check if user is allowed to this course
                    // See CourseVoter.php
                    if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
                        throw new AccessDeniedException(
                            $translator->trans(
                                'Unauthorised access to course!'
                            )
                        );
                    }
                } else {
                    $session = $em->getRepository('ChamiloCoreBundle:Session')->find($sessionId);
                    if ($session) {
                        $sessionHandler->set('sessionObj', $session);
                        //$course->setCurrentSession($session);
                        $session->setCurrentCourse($course);
                        // Check if user is allowed to this course-session
                        // See SessionVoter.php
                        if (false === $checker->isGranted(
                                SessionVoter::VIEW,
                                $session
                            )
                        ) {
                            throw new AccessDeniedException(
                                $translator->trans(
                                    'Unauthorised access to session!'
                                )
                            );
                        }

                        $sessionHandler->set('session_name', $session->getName());
                        $sessionHandler->set('id_session', $session->getId());
                        $sessionHandler->set('sessionObj', $session);
                    } else {
                        throw new NotFoundHttpException(
                            $translator->trans('Session not found')
                        );
                    }
                }

                if (!empty($groupId)) {
                    $group = $em->getRepository('ChamiloCourseBundle:CGroupInfo')->find($groupId);
                    if ($course->hasGroup($group)) {
                        if ($group) {
                            // Check if user is allowed to this course-group
                            // See GroupVoter.php
                            if (false === $checker->isGranted(
                                    GroupVoter::VIEW,
                                    $group
                                )
                            ) {
                                throw new AccessDeniedException(
                                    $translator->trans(
                                        'Unauthorised access to group'
                                    )
                                );
                            }
                        } else {
                            throw new NotFoundHttpException(
                                $translator->trans('Group not found')
                            );
                        }
                    } else {
                        throw new AccessDeniedException(
                            $translator->trans('Group does not exist in course')
                        );
                    }
                }

                if (!$alreadyVisited ||
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
                }
            } else {
                throw new NotFoundHttpException(
                    $translator->trans('CourseDoesNotExist')
                );
            }
        }
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
        $controllerList = $event->getController();

        if (!is_array($controllerList)) {
            return;
        }

        $request = $event->getRequest();
        $sessionHandler = $request->getSession();

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

        /** @var Course $course */
        $course = $sessionHandler->get('courseObj');

        /*if ($course) {
            $courseLanguage = $course->getCourseLanguage();
            //error_log('onkernelcontroller request: '.$courseLanguage);
            if (!empty($courseLanguage)) {
                $request->setLocale($courseLanguage);
                $sessionHandler->set('_locale', $courseLanguage);
                $this->container->get('session')->set('_locale', $courseLanguage);
            }
        }*/

        $groupId = intval($request->get('gidReq'));
        $sessionId = intval($request->get('id_session'));
        $cidReset = $sessionHandler->get('cid_reset', false);

        // This controller implements ToolInterface? Then set the course/session
        if (is_array($controllerList) &&
            (
                $controllerList[0] instanceof ToolInterface ||
                $controllerList[0] instanceof LegacyController
            ) && $cidReset == false
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

                $courseInfo = api_get_course_info($courseCode);
                $container->get('twig')->addGlobal('course', $course);

                $sessionHandler->set('_real_cid', $course->getId());
                $sessionHandler->set('_cid', $course->getCode());
                $sessionHandler->set('_course', $courseInfo);
            }

            if ($session) {
                $controller->setSession($session);
            }

            // Example 'chamilo_notebook.controller.notebook:indexAction'
            $controllerAction = $request->get('_controller');
            $controllerActionParts = explode(':', $controllerAction);
            $controllerNameParts = explode('.', $controllerActionParts[0]);
            $controllerName = $controllerActionParts[0];

            $toolName = null;
            $toolAction = null;
            if (isset($controllerNameParts[1]) &&
                $controllerNameParts[1] == 'controller'
            ) {
                $toolName = $this->container->get($controllerName)->getToolName();
                $action = str_replace('action', '', $controllerActionParts[1]);
                $toolAction = $toolName.'.'.$action;
            }

            $container->get('twig')->addGlobal('tool.name', $toolName);
            $container->get('twig')->addGlobal('tool.action', $toolAction);

            $sessionHandler->set('_gid', $groupId);
            $sessionHandler->set('is_allowed_in_course', true);
            $sessionHandler->set('id_session', $sessionId);
        } else {
            $ignore = [
                'fos_js_routing.controller:indexAction',
                'web_profiler.controller.profiler:toolbarAction',
            ];

            $controllerAction = $request->get('_controller');
            if (!in_array($controllerAction, $ignore)) {
                //error_log('remove');
                $this->removeCourseFromSession($request);
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
        //$request->setLocale($request->getPreferredLanguage());
    }
}
