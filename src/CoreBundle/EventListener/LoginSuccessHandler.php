<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use UserManager;

//class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
class LoginSuccessHandler
{
    protected UrlGeneratorInterface $router;
    protected AuthorizationCheckerInterface $checker;
    protected SettingsManager $settingsManager;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        AuthorizationCheckerInterface $checker,
        SettingsManager $settingsManager
    ) {
        $this->router = $urlGenerator;
        $this->checker = $checker;
        $this->settingsManager = $settingsManager;
    }

    /**
     * @return null|RedirectResponse
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $userId = $user->getId();
        $session = $request->getSession();

        //$userInfo = api_get_user_info($user->getId());
        //$userInfo['is_anonymous'] = false;

        // Backward compatibility.
        //$ip = $request->getClientIp();

        // Setting user info.
        //$session->set('_user', $user);

        // Setting admin permissions for.
        if ($this->checker->isGranted('ROLE_ADMIN')) {
            $session->set('is_platformAdmin', true);
        }

        // Setting teachers permissions.
        if ($this->checker->isGranted('ROLE_TEACHER')) {
            $session->set('is_allowedCreateCourse', true);
        }

        // Setting last login datetime
        $session->set('user_last_login_datetime', api_get_utc_datetime());

        $response = null;
        /* Possible values: index.php, user_portal.php, main/auth/courses.php */
        $pageAfterLogin = $this->settingsManager->getSetting('registration.page_after_login');

        $legacyIndex = $this->router->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // Default redirect:
        $url = $legacyIndex;

        if ($this->checker->isGranted('ROLE_STUDENT') && !empty($pageAfterLogin)) {
            switch ($pageAfterLogin) {
                case 'index.php':
                    $url = $legacyIndex;

                    break;
                case 'user_portal.php':
                    $url = $legacyIndex.'user_portal.php';

                    break;
                case 'main/auth/courses.php':
                    $url = $legacyIndex.'/'.$pageAfterLogin;

                    break;
            }
        }

        $goToCourse = $this->settingsManager->getSetting('course.go_to_course_after_login');

        $session->set('_uid', $user->getId());
        //$session->set('_user', $userInfo);
        //$session->set('is_platformAdmin', \UserManager::is_admin($userId));
        //$session->set('is_allowedCreateCourse', $userInfo['status'] === 1);
        // Redirecting to a course or a session.
        if ('true' === $goToCourse) {
            // Get the courses list
            $personal_course_list = UserManager::get_personal_session_course_list($userId);
            $my_session_list = [];
            $count_of_courses_no_sessions = 0;
            foreach ($personal_course_list as $course) {
                if (!empty($course['session_id'])) {
                    $my_session_list[$course['session_id']] = true;
                } else {
                    $count_of_courses_no_sessions++;
                }
            }

            $count_of_sessions = \count($my_session_list);
            if (1 === $count_of_sessions && 0 === $count_of_courses_no_sessions) {
                $key = array_keys($personal_course_list);
                $course_info = $personal_course_list[$key[0]]['course_info'];
                $sessionId = isset($course_info['session_id']) ? $course_info['session_id'] : 0;
                $url = api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/index.php?sid='.$sessionId;
            }

            if (0 === $count_of_sessions && 1 === $count_of_courses_no_sessions) {
                $key = array_keys($personal_course_list);
                $course_info = $personal_course_list[$key[0]]['course_info'];
                $url = api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/index.php?sid=0';
            }
        }

        if (!empty($url)) {
            $response = new RedirectResponse($url);
        }

        // Redirect the user to where they were before the login process begun.
        if (empty($response)) {
            $url = $request->headers->get('referer');
            $response = new RedirectResponse($url);
        }

        return $response;
    }
}
