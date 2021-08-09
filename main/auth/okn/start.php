<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Settings;

require_once '../../../main/inc/global.inc.php';

// Create a settings.dist.php
if (file_exists('settings.php')) {
    require_once 'settings.php';
} else {
    $message = '';
    if (api_is_platform_admin()) {
        $message = 'Create a settings.php';
    }
    api_not_allowed(true, $message);
}

$content = '';
$auth = new Auth($settingsInfo);
$settings = new Settings($settingsInfo);
$authRequest = new AuthnRequest($settings);

$samlRequest = $authRequest->getRequest(true);
$idpData = $settings->getIdPData();

if (isset($_GET['email']) || isset($_GET['email_bis'])) {
    $auth->login();
// If AuthNRequest ID need to be saved in order to later validate it, do instead
    /*$ssoBuiltUrl = $auth->login(null, [], false, false, true);
    $_SESSION['AuthNRequestID'] = $auth->getLastRequestID();
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header('Location: ' . $ssoBuiltUrl);
    exit();*/
} elseif (isset($_GET['slo'])) {
    $returnTo = null;
    $parameters = [];
    $nameId = Session::read('samlNameId');
    $sessionIndex = Session::read('samlSessionIndex');
    $nameIdFormat = Session::read('samlNameIdFormat');
    $auth->logout($returnTo, $parameters, $nameId, $sessionIndex, false, $nameIdFormat);
} elseif (isset($_GET['acs'])) {
    $requestID = Session::read('AuthNRequestID');
    $auth->processResponse($requestID);
    $errors = $auth->getErrors();
    if (!empty($errors)) {
        $content .= '<p>'.implode(', ', $errors).'</p>';
    }

    if (!$auth->isAuthenticated()) {
        api_not_allowed(true, $content.'<p>Not authenticated</p>');
        exit;
    }

    $attributes = $auth->getAttributes();

    $valueList = [];
    $attributeNameList = [
        'email',
        'username',
        'firstname',
        'lastname1',
        'lastname2',
        'courses',
    ];

    // Check normal params
    foreach ($attributeNameList as $name) {
        if (isset($attributes[$name]) && !empty($attributes[$name])) {
            $valueList[$name] = $attributes[$name];
        }
    }

    // Check bis params
    foreach ($attributeNameList as $name) {
        $bisName = $name.'_bis';
        if (isset($attributes[$bisName]) && !empty($attributes[$bisName])) {
            $valueList[$name] = $attributes[$bisName];
        }
    }

    $attributes = $valueList;

    if (!isset($attributes['email']) ||
        !isset($attributes['firstname']) ||
        !isset($attributes['lastname1']) ||
        !isset($attributes['lastname2']) ||
        !isset($attributes['username'])
    ) {
        echo 'Not enough parameters, current values: ';
        echo '<pre>';
        var_dump($auth->getAttributes());
        exit;
    }

    foreach ($attributes as &$attribute) {
        $attribute = implode('', $attribute);
    }

    $username = $attributes['username'];
    $userInfo = api_get_user_info_from_username($username);
    $userId = null;

    if (empty($userInfo)) {
        $lastName = $attributes['lastname1'].' '.$attributes['lastname2'];
        $userId = UserManager::create_user(
            $attributes['firstname'],
            $lastName,
            STUDENT,
            $attributes['email'],
            $username,
            '',
            '',
            '',
            '',
            '',
            'okn'
        );
        if ($userId) {
            $userInfo = api_get_user_info($userId);
        } else {
            echo "Error cannot create user: $username";
        }
    } else {
        // Only load users that were created using this method.
        if ($userInfo['auth_source'] === 'okn') {
            $userId = $userInfo['user_id'];
        } else {
            echo "Error cannot handle user $username, because it was not created by okn";
            exit;
        }
    }

    if (!empty($userId)) {
        $courseCode = null;
        if (isset($settingsInfo['course_list']) && !empty($settingsInfo['course_list'])) {
            foreach ($settingsInfo['course_list'] as $courseCode) {
                CourseManager::subscribeUser($userId, $courseCode, STUDENT, 0, 0, false);
            }
        }

        if (isset($attributes['courses']) && !empty($attributes['courses'])) {
            $courses = explode(',', $attributes['courses']);
            $firstCourseCode = '';
            if (!empty($courses)) {
                $counter = 1;
                foreach ($courses as $course) {
                    $courseInfo = api_get_course_info($course);
                    if ($courseInfo) {
                        if ($counter == 1) {
                            $firstCourseCode = $course;
                        }
                        CourseManager::subscribeUser($userId, $courseInfo['code'], STUDENT, 0, 0, false);
                    }
                    $counter++;
                }
                $courseCode = $firstCourseCode;
            }
        }

        // Clean flash messages
        Session::write('flash_messages', '');

        // Set chamilo sessions
        Session::write('samlUserdata', $auth->getAttributes());
        Session::write('samlNameId', $auth->getNameId());
        Session::write('samlNameIdFormat', $auth->getNameIdFormat());
        Session::write('samlSessionIndex', $auth->getSessionIndex());
        Session::erase('AuthNRequestID');

        // Filling session variables with new data
        Session::write('_uid', $userId);
        Session::write('_user', $userInfo);
        Session::write('is_platformAdmin', false);
        Session::write('is_allowedCreateCourse', false);

        Event::eventLogin($userId);

        if (!empty($courseCode)) {
            $courseInfo = api_get_course_info($courseCode);
            header('Location: '.$courseInfo['course_public_url']);
            exit;
        }
        header('Location: '.api_get_path(WEB_PATH).'user_portal.php');
        exit;
    } else {
        echo 'User not found';
    }
    exit;

    if (!empty($userId)) {
    } else {
        Display::addFlash(Display::return_message(get_lang('InvalidId')));
    }

    /*if (isset($_POST['RelayState']) && \OneLogin\Saml2\Utils::getSelfURL() != $_POST['RelayState']) {
        $auth->redirectTo($_POST['RelayState']);
    }*/
    header('Location: '.api_get_path(WEB_PATH));
    exit;
} elseif (isset($_GET['sls'])) {
    $requestID = Session::read('LogoutRequestID');
    $auth->processSLO(false, $requestID);
    $errors = $auth->getErrors();

    if (empty($errors)) {
        Session::erase('samlNameId');
        Session::erase('samlSessionIndex');
        Session::erase('samlNameIdFormat');
        Session::erase('samlUserdata');
        Session::erase('AuthNRequestID');
        Session::erase('LogoutRequestID');

        Display::addFlash(Display::return_message('Sucessfully logged out'));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    } else {
        api_not_allowed(true, implode(', ', $errors));
    }
}

$template = new Template('');

if (isset($_SESSION['samlUserdata'])) {
    $attributes = Session::read('samlUserdata');
    $params = [];
    if (!empty($attributes)) {
        $content .= 'You have the following attributes:<br>';
        $content .= '<table class="table"><thead><th>Name</th><th>Values</th></thead><tbody>';
        foreach ($attributes as $attributeName => $attributeValues) {
            $content .= '<tr><td>'.htmlentities($attributeName).'</td><td><ul>';
            foreach ($attributeValues as $attributeValue) {
                $content .= '<li>'.htmlentities($attributeValue).'</li>';
            }
            $content .= '</ul></td></tr>';
        }
        $content .= '</tbody></table>';
    } else {
        $content .= "<p>You don't have any attribute</p>";
    }

    $content .= '<p><a href="?slo" >Logout</a></p>';
} else {
    $content .= '<p><a href="?sso" >Login</a></p>';
    //$content .= '<p><a href="?sso2" >Login and access to attrs.php page</a></p>';
}

$template->assign('content', $content);
$template->display_one_col_template();
