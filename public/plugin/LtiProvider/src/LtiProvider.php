<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Packback\Lti1p3\LtiMessageLaunch;
use Packback\Lti1p3\LtiOidcLogin;
use Packback\Lti1p3\LtiServiceConnector;

/**
 * Class LtiProvider.
 */
class LtiProvider
{
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    private function getCache(): Lti13Cache
    {
        return new Lti13Cache();
    }

    private function getCookie(): Lti13Cookie
    {
        return new Lti13Cookie();
    }

    private function getDatabase(): Lti13Database
    {
        return new Lti13Database();
    }

    private function getServiceConnector(): LtiServiceConnector
    {
        return new LtiServiceConnector(
            $this->getCache(),
            new Client()
        );
    }

    /**
     * OIDC login and redirect.
     *
     * @throws \Packback\Lti1p3\OidcException
     */
    public function login(?array $request = null): void
    {
        JWT::$leeway = 5;

        $request ??= $_REQUEST;

        $launchUrl = Security::remove_XSS($request['target_link_uri'] ?? '');

        $login = new LtiOidcLogin(
            $this->getDatabase(),
            $this->getCache(),
            $this->getCookie()
        );

        $redirectUrl = $login->getRedirectUrl($launchUrl, $request);

        header('Location: '.$redirectUrl);
        exit;
    }

    /**
     * LTI Message Launch.
     */
    public function launch(bool $fromCache = false, ?string $launchId = null): LtiMessageLaunch
    {
        JWT::$leeway = 5;

        $database = $this->getDatabase();
        $cache = $this->getCache();
        $cookie = $this->getCookie();
        $serviceConnector = $this->getServiceConnector();

        if ($fromCache) {
            return LtiMessageLaunch::fromCache(
                (string) $launchId,
                $database,
                $cache,
                $cookie,
                $serviceConnector
            );
        }

        $launch = LtiMessageLaunch::new(
            $database,
            $cache,
            $cookie,
            $serviceConnector
        );

        return $launch->initialize($_REQUEST);
    }

    /**
     * It removes user and LP session.
     */
    public function logout(string $toolName = '')
    {
        Session::erase('_user');
        Session::erase('is_platformAdmin');
        Session::erase('is_allowedCreateCourse');
        Session::erase('_uid');

        if ('lp' === $toolName) {
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
     * Verify if user exists in provider platform, create if needed and login.
     */
    public function validateUser(array $launchData, string $courseCode, string $toolName): bool
    {
        if (empty($launchData)) {
            return false;
        }

        // Use a stable auth source label for provider-created LTI users.
        $authSource = defined('IMS_LTI_SOURCE') ? IMS_LTI_SOURCE : 'lti_provider';
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
                [$authSource]
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
     * Check if request is from LTI customer.
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
