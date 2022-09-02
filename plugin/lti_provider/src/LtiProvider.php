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
     *
     * @param string $toolName
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
     * Verify if email user is in the platform to create it and login (true) or not (false).
     */
    public function validateUser(array $launchData, string $courseCode, string $toolName): bool
    {
        if (empty($launchData)) {
            return false;
        }

        $firstName = $launchData['given_name'];
        $lastName = $launchData['family_name'];
        $email = $launchData['email'];
        $status = STUDENT;

        $userInfo = api_get_user_info_from_email($email);
        if (empty($userInfo)) {
            // We create the user
            $username = $launchData['https://purl.imsglobal.org/spec/lti/claim/ext']['user_username'];
            if (!UserManager::is_username_available($username)) {
                $username = UserManager::create_unique_username(
                    $firstName,
                    $lastName
                );
            }
            $password = api_generate_password();
            $userId = UserManager::create_user(
                $firstName,
                $lastName,
                $status,
                $email,
                $username,
                $password,
                '',
                '',
                '',
                '',
                IMS_LTI_SOURCE
            );
        } else {
            $userId = $userInfo['user_id'];
        }

        if (!CourseManager::is_user_subscribed_in_course($userId, $courseCode)) {
            CourseManager::subscribeUser($userId, $courseCode, $status);
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
}
