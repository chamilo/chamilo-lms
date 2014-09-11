<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Chamilo\UserBundle\Entity\User;

/**
 * Class LoginSuccessHandler
 */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $router;
    protected $security;

    /**
     * @param Router $urlGenerator
     * @param SecurityContext $security
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, SecurityContext $security)
    {
        $this->router = $urlGenerator;
        $this->security = $security;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return null|RedirectResponse|\Symfony\Component\Security\Http\Authentication\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();
        $userId = $user->getId();

        $session = $request->getSession();
        //event_login($user);

        $userInfo = api_get_user_info($user->getId());
        $userInfo['is_anonymous'] = false;

        $request->getSession()->set('_user', $userInfo);

        // Setting admin permissions.
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $request->getSession()->set('is_platformAdmin', true);
        }

        // Setting teachers permissions.
        if ($this->security->isGranted('ROLE_TEACHER')) {
            $request->getSession()->set('is_allowedCreateCourse', true);
        }

        // Setting last login datetime
        $session->set('user_last_login_datetime', api_get_utc_datetime());

        $response = null;
        /* Possible values: index.php, user_portal.php, main/auth/courses.php */
        $pageAfterLogin = api_get_setting('registration.page_after_login');

        $url = null;
        if ($this->security->isGranted('ROLE_STUDENT') && !empty($pageAfterLogin)) {
            switch ($pageAfterLogin) {
                case 'index.php':
                    $url = $this->router->generate('index');
                    break;
                case 'user_portal.php':
                    $url = $this->router->generate('userportal');
                    break;
                case 'main/auth/courses.php':
                    $url = api_get_path(WEB_PUBLIC_PATH).$pageAfterLogin;
                    break;
            }
        }

        // Redirecting to a course or a session
        if (api_get_setting('course.go_to_course_after_login') == 'true') {

            // Get the courses list
            $personal_course_list = \UserManager::get_personal_session_course_list($userId);

            $my_session_list = array();
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
            $refererUrl = $request->headers->get('referer');
            $response = new RedirectResponse($refererUrl);
        }

        return $response;
    }
}
