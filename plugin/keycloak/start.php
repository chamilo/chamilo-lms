<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\AuthnRequest;
use OneLogin\Saml2\Settings;

require_once '../../main/inc/global.inc.php';

$pluginKeycloak = api_get_plugin_setting('keycloak', 'tool_enable') === 'true';

if (!$pluginKeycloak) {
    api_not_allowed(true);
}

// Create a settings.dist.php
if (file_exists('settings.php')) {
    require_once 'settings.php';
} else {
    $message = '';
    if (api_is_platform_admin()) {
        $message = 'Create a settings.php file in plugin/keycloak/';
    }
    api_not_allowed(true, $message);
}

$content = '';
$auth = new Auth($settingsInfo);

/*if (isset($_REQUEST['delete'])) {
    Session::erase('samlNameId');
    Session::erase('samlSessionIndex');
    Session::erase('samlNameIdFormat');
    Session::erase('samlUserdata');
    Session::erase('AuthNRequestID');
    Session::erase('LogoutRequestID');
    echo 'delete all';
    exit;
}*/

$settings = new Settings($settingsInfo);
$authRequest = new AuthnRequest($settings);

$samlRequest = $authRequest->getRequest();
$idpData = $settings->getIdPData();

if (isset($_GET['sso'])) {
    $auth->login();
// If AuthNRequest ID need to be saved in order to later validate it, do instead
    /*$ssoBuiltUrl = $auth->login(null, [], false, false, true);
    $_SESSION['AuthNRequestID'] = $auth->getLastRequestID();
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header('Location: ' . $ssoBuiltUrl);
    exit();*/
} elseif (isset($_GET['slo'])) {
    /*
    if (isset($idpData['singleLogoutService']) && isset($idpData['singleLogoutService']['url'])) {
        $sloUrl = $idpData['singleLogoutService']['url'];
    } else {
        throw new Exception("The IdP does not support Single Log Out");
    }

    if (isset($_SESSION['samlSessionIndex']) && !empty($_SESSION['samlSessionIndex'])) {
        $logoutRequest = new \OneLogin\Saml2\LogoutRequest($settings, null, $_SESSION['samlSessionIndex']);
    } else {
        $logoutRequest = new \OneLogin\Saml2\LogoutRequest($settings);
    }
    $samlRequest = $logoutRequest->getRequest();
    $parameters = array('SAMLRequest' => $samlRequest);
    $url = \OneLogin\Saml2\Utils::redirect($sloUrl, $parameters, true);
    header("Location: $url");
    exit;*/
    $returnTo = null;
    $parameters = [];
    $nameId = Session::read('samlNameId');
    $sessionIndex = Session::read('samlSessionIndex');
    $nameIdFormat = Session::read('samlNameIdFormat');
    $auth->logout($returnTo, $parameters, $nameId, $sessionIndex, false, $nameIdFormat);

// If LogoutRequest ID need to be saved in order to later validate it, do instead
    // $sloBuiltUrl = $auth->logout(null, [], $nameId, $sessionIndex, true);
    /*$_SESSION['LogoutRequestID'] = $auth->getLastRequestID();
    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header('Location: ' . $sloBuiltUrl);
    exit();*/
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

    $keyCloackUserName = $auth->getNameId();
    $userInfo = api_get_user_info_from_username($keyCloackUserName);
    $attributes = $auth->getAttributes();
    $userId = 0;
    if (!empty($attributes) && empty($userInfo)) {
        $firstName = '';
        if (isset($attributes['FirstName']) && !empty($attributes['FirstName'])) {
            $firstName = reset($attributes['FirstName']);
        }

        $lastName = '';
        if (isset($attributes['LastName']) && !empty($attributes['LastName'])) {
            $lastName = reset($attributes['LastName']);
        }

        $email = '';
        if (isset($attributes['Email']) && !empty($attributes['Email'])) {
            $email = reset($attributes['Email']);
        }

        if (empty($email)) {
            api_not_allowed(true);
        }

        $userId = UserManager::create_user(
            $firstName,
            $lastName,
            STUDENT,
            $email,
            $keyCloackUserName,
            '',
            '',
            '',
            '',
            '',
            'keycloak'
        );

        if ($userId) {
            $userInfo = api_get_user_info($userId);
        }
    } else {
        // Only load users that were created using this method.
        if ($userInfo['auth_source'] === 'keycloak') {
            $userId = $userInfo['user_id'];
        }
    }

    if (!empty($userId)) {
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
    $content .= '<p><a href="?sso2" >Login and access to attrs.php page</a></p>';
}

$template->assign('content', $content);
$template->display_one_col_template();
