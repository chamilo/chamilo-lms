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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Sets the course and session objects in the controller that implements the CourseControllerInterface.
 */
class CidReqListener
{
    /**
     * These roles are context roles and must be cleared every request
     * to avoid "role leakage" between courses/groups/sessions.
     */
    private const CONTEXT_ROLES = [
        'ROLE_CURRENT_COURSE_GROUP_TEACHER',
        'ROLE_CURRENT_COURSE_GROUP_STUDENT',
        'ROLE_CURRENT_COURSE_STUDENT',
        'ROLE_CURRENT_COURSE_TEACHER',
        'ROLE_CURRENT_COURSE_SESSION_STUDENT',
        'ROLE_CURRENT_COURSE_SESSION_TEACHER',
    ];

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
            // Do nothing on subrequests
            return;
        }

        $request = $event->getRequest();

        // Ignore debug toolbar
        if ('_wdt' === $request->attributes->get('_route')) {
            return;
        }

        // Ignore profiler toolbar
        if ('_wdt' === $request->attributes->get('_profiler')) {
            return;
        }

        // Skip all course/session logic during installation pages.
        // This prevents crashes when APP_INSTALLED is wrong or the DB is not ready yet.
        $path = $request->getPathInfo();
        if (\is_string($path) && str_starts_with($path, '/main/install/')) {
            return;
        }

        // Always reset context roles at the beginning of each main request
        $this->resetContextRolesOnTokenUser();

        if (true === $cidReset) {
            $this->cleanSessionHandler($request);

            return;
        }

        // Prevent "Session has not been set" during early bootstrap or install.
        if (!$request->hasSession()) {
            // No session available yet, so we cannot safely store course/session context.
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
                if ($courseFromSession instanceof Course && $courseId === $courseFromSession->getId()) {
                    $course = $courseFromSession;
                    $courseInfo = (array) $sessionHandler->get('_course');
                }
            }

            if (null === $course) {
                $course = $this->entityManager->find(Course::class, $courseId);

                if (null === $course) {
                    throw new NotFoundHttpException($this->translator->trans('Course does not exist'));
                }

                $courseInfo = api_get_course_info($course->getCode());
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
                throw new NotAllowedException($this->translator->trans("You're not allowed in this course"));
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
                $session = $this->entityManager->find(Session::class, $sessionId);
                if (null !== $session) {
                    $session->setCurrentCourse($course);

                    if (false === $checker->isGranted(SessionVoter::VIEW, $session)) {
                        throw new AccessDeniedHttpException($this->translator->trans("You're not allowed in this session"));
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
                $group = $this->entityManager->getRepository(CGroup::class)->find($groupId);

                if (null === $group) {
                    throw new NotFoundHttpException($this->translator->trans('Group not found'));
                }

                $group->setParent($course);

                if (false === $checker->isGranted(GroupVoter::VIEW, $group)) {
                    throw new AccessDeniedHttpException($this->translator->trans("You're not allowed in this group"));
                }

                $sessionHandler->set('group', $group);
                $sessionHandler->set('gid', $groupId);
            }

            $origin = $request->get('origin');
            if (!empty($origin)) {
                $sessionHandler->set('origin', $origin);
            }

            $courseParams = $this->generateCourseUrl($course, $sessionId, $groupId, $origin);
            $sessionHandler->set('course_url_params', $courseParams);
            $twig->addGlobal('course_url_params', $courseParams);
        } else {
            $this->cleanSessionHandler($request);
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

        // Prevent "Session has not been set" during early bootstrap or install.
        if (!$request->hasSession()) {
            return;
        }

        // Skip injection during installation pages.
        $path = $request->getPathInfo();
        if (\is_string($path) && str_starts_with($path, '/main/install/')) {
            return;
        }

        $sessionHandler = $request->getSession();

        $courseId = (int) $request->get('cid');

        if (\is_array($controllerList)
            && (
                $controllerList[0] instanceof CourseControllerInterface
                || $controllerList[0] instanceof EditorController
            )
        ) {
            if (!empty($courseId)) {
                $controller = $controllerList[0];
                $session = $sessionHandler->get('session');
                $course = $sessionHandler->get('course');

                if ($course) {
                    $controller->setCourse($course);
                }

                if ($session) {
                    $controller->setSession($session);
                }
            }
        }
    }

    public function cleanSessionHandler(Request $request): void
    {
        // If there is no session available, just ensure we clear context roles and exit.
        if (!$request->hasSession()) {
            $this->resetContextRolesOnTokenUser();

            return;
        }

        $sessionHandler = $request->getSession();
        $alreadyVisited = $sessionHandler->get('course_already_visited');
        if ($alreadyVisited) {
            // "Logout" course
            $sessionHandler->remove('course_already_visited');
            ChamiloSession::erase('course_already_visited');
        }

        $courseId = (int) $sessionHandler->get('cid', 0);
        $sessionId = (int) $sessionHandler->get('sid', 0);
        $ip = (string) ($request->getClientIp() ?? '');

        // Track course logout only when we have a valid course context and a valid Chamilo User entity.
        if (0 !== $courseId) {
            $token = $this->tokenStorage->getToken();
            if (null !== $token) {
                $tokenUser = $token->getUser();

                // The token user might be a string (e.g., "anon.") or another UserInterface implementation.
                // We must only log access when it is the Doctrine-backed Chamilo User entity with a valid ID.
                if ($tokenUser instanceof User && (int) $tokenUser->getId() > 0) {
                    $this->entityManager
                        ->getRepository(TrackECourseAccess::class)
                        ->logoutAccess($tokenUser, $courseId, $sessionId, $ip)
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

        // Remove context roles also when leaving the course/session/group
        $this->resetContextRolesOnTokenUser();
    }

    private function resetContextRolesOnTokenUser(): void
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        // We only know removeRole exists on Chamilo User entity
        if ($user instanceof User) {
            foreach (self::CONTEXT_ROLES as $role) {
                $user->removeRole($role);
            }
            $token->setUser($user);
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
