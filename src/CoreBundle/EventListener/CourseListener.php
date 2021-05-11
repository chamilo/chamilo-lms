<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Controller\EditorController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

/**
 * Class CourseListener.
 * Sets the course and session objects in the controller that implements the CourseControllerInterface.
 */
class CourseListener
{
    use ContainerAwareTrait;

    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Get request from the URL cidReq, c_id or the "ABC" in the courses url (courses/ABC/index.php).
     */
    public function onKernelRequest(RequestEvent $event): void
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
        $twig = $this->twig;

        $course = null;
        $courseInfo = [];

        // Check if URL has cid value. Using Symfony request.
        $courseId = (int) $request->get('cid');
        $checker = $container->get('security.authorization_checker');

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();

        //dump("cid value in request: $courseId");
        if (!empty($courseId)) {
            $course = null;
            if ($sessionHandler->has('course')) {
                /** @var Course $courseFromSession */
                $courseFromSession = $sessionHandler->get('course');
                if ($courseId === $courseFromSession->getId()) {
                    $course = $courseFromSession;
                    $courseInfo = $sessionHandler->get('_course');
                    //dump("Course #$courseId loaded from Session ");
                }
            }

            //$course = null; //force loading from database
            //if (null === $course) {
            $course = $em->getRepository(Course::class)->find($courseId);
            if (null === $course) {
                throw new NotFoundHttpException($translator->trans('Course does not exist'));
            }

            //dump("Course loaded from DB #$courseId");
            $courseInfo = api_get_course_info($course->getCode());
            //}

            /*if (null === $course) {
                throw new NotFoundHttpException($translator->trans('Course does not exist'));
            }*/
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
            $sessionHandler->set('_course', $courseInfo);

            // Setting variables for the twig templates.
            $twig->addGlobal('course', $course);

            // Checking if sid is used.
            $sessionId = (int) $request->get('sid');
            $session = null;
            if (empty($sessionId)) {
                $sessionHandler->remove('session_name');
                $sessionHandler->remove('sid');
                $sessionHandler->remove('session');
                // Check if user is allowed to this course
                // See CourseVoter.php
                //dump("Checkisgranted");
                if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
                    throw new AccessDeniedException($translator->trans('Unauthorised access to course!'));
                }
            } else {
                //dump("Load chamilo session from DB");
                $session = $em->getRepository(Session::class)->find($sessionId);
                if (null !== $session) {
                    if (!$session->hasCourse($course)) {
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

                    $twig->addGlobal('session', $session);
                } else {
                    throw new NotFoundHttpException($translator->trans('Session not found'));
                }
            }

            // Group
            $groupId = (int) $request->get('gid');

            if (empty($groupId)) {
                $sessionHandler->remove('gid');
            } else {
                //dump('Load chamilo group from DB');
                $group = $em->getRepository(CGroup::class)->find($groupId);

                if (null === $group) {
                    throw new NotFoundHttpException($translator->trans('Group not found'));
                }

                if (false === $checker->isGranted(GroupVoter::VIEW, $group)) {
                    throw new AccessDeniedException($translator->trans('Unauthorised access to group'));
                }

                $sessionHandler->set('gid', $groupId);
                // @todo check if course has group
                /*if ($course->hasGroup($group)) {
                    // Check if user is allowed to this course-group
                    // See GroupVoter.php
                    if (false === $checker->isGranted(GroupVoter::VIEW, $group)) {
                        throw new AccessDeniedException($translator->trans('Unauthorised access to group'));
                    }
                    $sessionHandler->set('gid', $groupId);
                } else {
                    throw new AccessDeniedException($translator->trans('Group does not exist in course'));
                }*/
            }

            $origin = $request->get('origin');
            if (!empty($origin)) {
                $sessionHandler->set('origin', $origin);
            }

            $courseParams = $this->generateCourseUrl($course, $sessionId, $groupId, $origin);
            $sessionHandler->set('course_url_params', $courseParams);
            $twig->addGlobal('course_url_params', $courseParams);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
    }

    /**
     * Once the onKernelRequest was fired, we check if the course/session object were set and we inject them in the controller.
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $controllerList = $event->getController();

        if (!\is_array($controllerList)) {
            return;
        }

        $request = $event->getRequest();
        $sessionHandler = $request->getSession();
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
        //global $cidReset;
        //$cidReset = $sessionHandler->get('cid_reset', false);

        // This controller implements ToolInterface? Then set the course/session
        if (\is_array($controllerList) &&
            (
                $controllerList[0] instanceof CourseControllerInterface ||
                $controllerList[0] instanceof EditorController
                //$controllerList[0] instanceof ResourceController
                //|| $controllerList[0] instanceof LegacyController
            )
        ) {
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
        }
    }

    public function removeCourseFromSession(Request $request): void
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
        $token = $this->container->get('security.token_storage')->getToken();
        if (null !== $token) {
            /** @var User $user */
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $user->removeRole('ROLE_CURRENT_COURSE_GROUP_TEACHER');
                $user->removeRole('ROLE_CURRENT_COURSE_GROUP_STUDENT');
                $user->removeRole('ROLE_CURRENT_COURSE_STUDENT');
                $user->removeRole('ROLE_CURRENT_COURSE_TEACHER');
                $user->removeRole('ROLE_CURRENT_COURSE_SESSION_STUDENT');
                $user->removeRole('ROLE_CURRENT_COURSE_SESSION_TEACHER');
            }
        }

        //$request->setLocale($request->getPreferredLanguage());
    }

    private function generateCourseUrl(?Course $course, int $sessionId, int $groupId, ?string $origin): string
    {
        if (null !== $course) {
            $cidReqURL = '&cid='.$course->getId();
            $cidReqURL .= '&sid='.$sessionId;
            $cidReqURL .= '&gid='.$groupId;
            $cidReqURL .= '&origin='.$origin;

            return $cidReqURL;
        }

        return '';
    }
}
