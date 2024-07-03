<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Controller\EditorController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Exception\NotAllowedException;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\GroupVoter;
use Chamilo\CoreBundle\Security\Authorization\Voter\SessionVoter;
use Chamilo\CourseBundle\Controller\CourseControllerInterface;
use Chamilo\CourseBundle\Entity\CGroup;
use ChamiloSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Sets the course and session objects in the controller that implements the CourseControllerInterface.
 */
class CidReqListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage
    ) {}

    /**
     * Get request from the URL cidReq, c_id or the "ABC" in the courses url (courses/ABC/index.php).
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        global $cidReset;

        if (!$event->isMainRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        $request = $event->getRequest();

        // Ignore debug
        if ('_wdt' === $request->attributes->get('_route')) {
            return;
        }

        // Ignore toolbar
        if ('_wdt' === $request->attributes->get('_profiler')) {
            return;
        }

        if (true === $cidReset) {
            self::cleanSessionHandler(
                $request,
                $this->tokenStorage->getToken()
            );

            return;
        }

        $sessionHandler = $request->getSession();
        $twig = $this->twig;

        $course = null;
        $courseInfo = [];

        // Check if URL has cid value. Using Symfony request.
        $courseId = (int) $request->get('cid');
        $checker = $this->authorizationChecker;

        if (!empty($courseId)) {
            if ($sessionHandler->has('course')) {
                /** @var Course $courseFromSession */
                $courseFromSession = $sessionHandler->get('course');
                if ($courseId === $courseFromSession->getId()) {
                    $course = $courseFromSession;
                    $courseInfo = $sessionHandler->get('_course');
                }
            }

            if (null === $course) {
                $course = $this->entityManager->find(Course::class, $courseId);
                $courseInfo = api_get_course_info($course->getCode());
            }

            if (null === $course) {
                throw new NotFoundHttpException($this->translator->trans('Course does not exist'));
            }

            // Setting variables in the session.
            $sessionHandler->set('course', $course);
            $sessionHandler->set('_real_cid', $course->getId());
            $sessionHandler->set('cid', $course->getId());
            $sessionHandler->set('_cid', $course->getCode());
            $sessionHandler->set('_course', $courseInfo);
            ChamiloSession::write('cid', $course->getId());
            ChamiloSession::write('_real_cid', $course->getId());
            ChamiloSession::write('_course', $courseInfo);

            // Setting variables for the twig templates.
            $twig->addGlobal('course', $course);

            if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
                throw new NotAllowedException($this->translator->trans('You\'re not allowed in this course'));
            }

            // Checking if sid is used.
            $sessionId = (int) $request->get('sid');

            if (empty($sessionId)) {
                $sessionHandler->remove('session_name');
                $sessionHandler->remove('sid');
                $sessionHandler->remove('session');
                ChamiloSession::erase('session_name');
                ChamiloSession::erase('sid');
                ChamiloSession::erase('session');
            } else {
                // dump("Load chamilo session from DB");
                $session = $this->entityManager->find(Session::class, $sessionId);
                if (null !== $session) {
                    // $course->setCurrentSession($session);
                    $session->setCurrentCourse($course);
                    // Check if user is allowed to this course-session
                    // See SessionVoter.php
                    if (false === $checker->isGranted(SessionVoter::VIEW, $session)) {
                        throw new AccessDeniedException($this->translator->trans('You\'re not allowed in this session'));
                    }
                    $sessionHandler->set('session_name', $session->getTitle());
                    $sessionHandler->set('sid', $session->getId());
                    $sessionHandler->set('session', $session);
                    ChamiloSession::write('sid', $session->getId());

                    $twig->addGlobal('session', $session);
                } else {
                    throw new NotFoundHttpException($this->translator->trans('Session not found'));
                }
            }

            // Group
            $groupId = (int) $request->get('gid');

            if (empty($groupId)) {
                $sessionHandler->remove('gid');
                ChamiloSession::erase('gid');
            } else {
                // dump('Load chamilo group from DB');
                $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);

                if (null === $group) {
                    throw new NotFoundHttpException($this->translator->trans('Group not found'));
                }

                $group->setParent($course);

                if (false === $checker->isGranted(GroupVoter::VIEW, $group)) {
                    throw new AccessDeniedException($this->translator->trans('You\'re not allowed in this group'));
                }

                $sessionHandler->set('gid', $groupId);
                // @todo check if course has group
                /*if ($course->hasGroup($group)) {
                    // Check if user is allowed to this course-group
                    // See GroupVoter.php
                    if (false === $checker->isGranted(GroupVoter::VIEW, $group)) {
                        throw new AccessDeniedException($this->translator->trans('Unauthorised access to group'));
                    }
                    $sessionHandler->set('gid', $groupId);
                } else {
                    throw new AccessDeniedException($this->translator->trans('Group does not exist in course'));
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

        $courseId = (int) $request->get('cid');
        // $groupId = (int) $request->get('gid');
        // $sessionId = (int) $request->get('sid');

        // cidReset is set in the global.inc.php files
        // global $cidReset;
        // $cidReset = $sessionHandler->get('cid_reset', false);

        // This controller implements ToolInterface? Then set the course/session
        if (\is_array($controllerList)
            && (
                $controllerList[0] instanceof CourseControllerInterface
                || $controllerList[0] instanceof EditorController
                // $controllerList[0] instanceof ResourceController
                // || $controllerList[0] instanceof LegacyController
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
                    // $courseCode = $course->getCode();
                    // $courseInfo = api_get_course_info($courseCode);
                    // $sessionHandler->set('_real_cid', $course->getId());
                    // $sessionHandler->set('_cid', $course->getCode());
                    // $sessionHandler->set('_course', $courseInfo);
                }

                if ($session) {
                    $controller->setSession($session);
                }
            }

            // Example 'chamilo_notebook.controller.notebook:indexAction'
            // $controllerAction = $request->get('_controller');
            // $controllerActionParts = explode(':', $controllerAction);
            // $controllerNameParts = explode('.', $controllerActionParts[0]);
            // $controllerName = $controllerActionParts[0];
        }
    }

    public static function cleanSessionHandler(Request $request, ?TokenInterface $token): void
    {
        $sessionHandler = $request->getSession();
        $alreadyVisited = $sessionHandler->get('course_already_visited');
        if ($alreadyVisited) {
            // "Logout" course
            $sessionHandler->remove('course_already_visited');
            ChamiloSession::erase('course_already_visited');
        }

        $courseId = $sessionHandler->get('cid', 0);
        $sessionId = $sessionHandler->get('sid', 0);
        $ip = $request->getClientIp();
        if (0 !== $courseId) {
            $token = $this->tokenStorage->getToken();
            if (null !== $token) {
                /** @var User $user */
                $user = $token->getUser();
                if ($user instanceof UserInterface) {
                    $this->entityManager->getRepository(TrackECourseAccess::class)
                        ->logoutAccess($user, $courseId, $sessionId, $ip)
                    ;
                }
            }
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
        ChamiloSession::erase('toolgroup');
        ChamiloSession::erase('_cid');
        ChamiloSession::erase('cid');
        ChamiloSession::erase('sid');
        ChamiloSession::erase('gid');
        ChamiloSession::erase('is_allowed_in_course');
        ChamiloSession::erase('_real_cid');
        ChamiloSession::erase('_course');
        ChamiloSession::erase('_locale_course');
        ChamiloSession::erase('course');
        ChamiloSession::erase('session');
        ChamiloSession::erase('course_url_params');
        ChamiloSession::erase('origin');

        // Remove user temp roles
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
