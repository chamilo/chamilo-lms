<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\SettingsBundle\Manager\SettingsManager;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class LoginSuccessHandler.
 */
//class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
class LoginSuccessHandler
{
    protected $router;
    protected $checker;
    protected $settingsManager;

    /**
     * LoginSuccessHandler constructor.
     *
     * @param UrlGeneratorInterface         $urlGenerator
     * @param AuthorizationCheckerInterface $checker
     * @param SettingsManager               $settingsManager
     */
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
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return RedirectResponse|Response|null
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $user = $event->getAuthenticationToken()->getUser();
        /** @var User $user */
        //$user = $token->getUser();
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

        $legacyIndex = $this->router->generate('legacy_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

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

        $session->set('_uid', $user->getId());
        //$session->set('_user', $userInfo);
        //$session->set('is_platformAdmin', \UserManager::is_admin($userId));
        //$session->set('is_allowedCreateCourse', $userInfo['status'] === 1);
        // Redirecting to a course or a session.
        if (api_get_setting('course.go_to_course_after_login') === 'true') {
            // Get the courses list
            $personal_course_list = \UserManager::get_personal_session_course_list($userId);
            $my_session_list = [];
            $count_of_courses_no_sessions = 0;
            $count_of_courses_with_sessions = 0;

            foreach ($personal_course_list as $course) {
                if (!empty($course['session_id'])) {
                    $my_session_list[$course['session_id']] = true;
                    $count_of_courses_with_sessions++;
                } else {
                    $count_of_courses_no_sessions++;
                }
            }

            $count_of_sessions = count($my_session_list);
            if ($count_of_sessions == 1 && $count_of_courses_no_sessions == 0) {
                $key = array_keys($personal_course_list);
                $course_info = $personal_course_list[$key[0]]['course_info'];
                $id_session = isset($course_info['session_id']) ? $course_info['session_id'] : 0;
                $url = api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/index.php?id_session='.$id_session;
            }

            if ($count_of_sessions == 0 && $count_of_courses_no_sessions == 1) {
                $key = array_keys($personal_course_list);
                $course_info = $personal_course_list[$key[0]]['course_info'];
                $url = api_get_path(WEB_COURSE_PATH).$course_info['directory'].'/index.php?id_session=0';
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
