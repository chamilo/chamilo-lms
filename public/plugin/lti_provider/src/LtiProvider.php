<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Packback\Lti1p3;
use Packback\Lti1p3\LtiMessageLaunch;
use Packback\Lti1p3\LtiOidcLogin;

require_once __DIR__.'/../db/lti13_cookie.php';
require_once __DIR__.'/../db/lti13_cache.php';
require_once __DIR__.'/../db/lti13_database.php';

/**
 * Class LtiProvider.
 */
class LtiProvider
{
    /**
     * Get the class instance.
     *
     * @staticvar LtiProvider $result
     *
     * @return LtiProvider
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    /**
     * Oidc login and register.
     *
     * @throws Lti1p3\OidcException
     */
    public function login(?array $request = null)
    {
        $launchUrl = Security::remove_XSS($request['target_link_uri']);
        LtiOidcLogin::new(new Lti13Database(), new Lti13Cache(), new Lti13Cookie())
            ->doOidcLoginRedirect($launchUrl, $request)
            ->doRedirect();
    }

    /**
     * It removes user and oLP session.
     */
    public function logout(string $toolName = '')
    {
        Session::erase('_user');
        Session::erase('is_platformAdmin');
        Session::erase('is_allowedCreateCourse');
        Session::erase('_uid');
        if ('lp' == $toolName) {
            // Deleting the objects
            Session::erase('oLP');
            Session::erase('lpobject');
            Session::erase('scorm_view_id');
            Session::erase('scorm_item_id');
            Session::erase('exerciseResult');
            Session::erase('objExercise');
            Session::erase('questionList');
        }
        Session::erase('is_allowed_in_course');
        Session::erase('_real_cid');
        Session::erase('_cid');
        Session::erase('_course');
    }

    /**
     * Lti Message Launch.
     */
    public function launch(bool $fromCache = false, ?string $launchId = null): LtiMessageLaunch
    {
        if ($fromCache) {
            $launch = LtiMessageLaunch::fromCache($launchId, new Lti13Database(), new Lti13Cache());
        } else {
            $launch = LtiMessageLaunch::new(new Lti13Database(), new Lti13Cache(), new Lti13Cookie())->validate();
        }

        return $launch;
    }

    /**
     * Verify if user is in the provider platform to create it and login (true) or not (false).
     */
    public function validateUser(array $launchData, string $courseCode, string $toolName): bool
    {
        if (empty($launchData)) {
            return false;
        }

        $authSource = IMS_LTI_SOURCE;
        $username = md5($launchData['iss'].'_'.$launchData['sub']);
        $userInfo = api_get_user_info_from_username($username, $authSource);
        if (empty($userInfo)) {
            $email = $username.'@'.$authSource.'.com';
            if (!empty($launchData['email'])) {
                $email = $launchData['email'];
            }
            $firstName = $launchData['aud'];
            if (!empty($launchData['given_name'])) {
                $firstName = $launchData['given_name'];
            }
            $lastName = $launchData['sub'];
            if (!empty($launchData['family_name'])) {
                $lastName = $launchData['family_name'];
            }
            $password = api_generate_password();
            $userId = UserManager::create_user(
                $firstName,
                $lastName,
                STUDENT,
                $email,
                $username,
                $password,
                '',
                '',
                '',
                '',
                $authSource
            );
        } else {
            $userId = $userInfo['user_id'];
        }

        if (!CourseManager::is_user_subscribed_in_course($userId, $courseCode)) {
            CourseManager::subscribeUser($userId, $courseCode);
        }

        $this->logout($toolName);

        $login = UserManager::loginAsUser($userId, false);
        if ($login && CourseManager::is_user_subscribed_in_course($userId, $courseCode)) {
            $_course = api_get_course_info($courseCode);
            Session::write('is_allowed_in_course', true);
            Session::write('_real_cid', $_course['real_id']);
            Session::write('_cid', $_course['code']);
            Session::write('_course', $_course);
        }

        return $login;
    }

    /**
     * It checks if request is from lti customer.
     *
     * @param $request
     * @param $session
     *
     * @return bool
     */
    public function isLtiRequest($request, $session)
    {
        $isLti = false;
        if (isset($request['lti_message_hint'])) {
            $isLti = true;
        } elseif (isset($request['state'])) {
            $isLti = true;
        } elseif (isset($request['lti_launch_id']) && 'learnpath' === api_get_origin()) {
            $isLti = true;
        } elseif (isset($request['lti_launch_id'])) {
            $isLti = true;
        } elseif (isset($session['oLP']->lti_launch_id)) {
            $isLti = true;
        }

        return $isLti;
    }
}
