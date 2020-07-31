<?php

use ChamiloSession as Session;

/**
 * This file contains the necessary elements to allow a Single Sign On
 * based on a validation of a hmac computed hash.
 *
 * To allow the SSO access /main/auth/hmac/login.php must receive as
 * query string parameters the following parameters:
 *
 * 'email': user email.
 *
 * 'time': time of the request, as HH:mm.
 *
 * 'system': System name, a control value.
 *
 * 'Token': a HMAC computed SHA256 algorithm based on the concatenation of
 * the 'time' and 'email' value.
 *
 * Example:
 *
 * https://campus.chamilo/main/auth/hmac/login.php?email=user@domain.com&time=10:48&system=SystemName&Token=XYZ
 *
 * Also a settings.php file must be configured the set the following values:
 *
 * 'secret': secret key used to generate a HMAC computed hash to validate the
 * received 'Token' parameter on the query string.
 *
 * 'secret': secret key used to generate a HMAC computed hash to validate the 'Token' parameter on the query string.
 *
 * 'expiration_time': integer value, maximum time in minutes of the request lifetime.
 */
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

// Check if we have all the parameters from the query string
if (isset($_GET['email']) && isset($_GET['time']) && isset($_GET['system']) && isset($_GET['Token'])) {
    $email = $_GET['email'];
    $time = $_GET['time'];
    $system = $_GET['system'];
    $token = $_GET['Token'];

    // Generate the token
    $validToken = hash_hmac('sha256', $time.$email, $settingsInfo['secret'], false);

    // Compare the received token & the valid token
    if ($token !== $validToken) {
        Display::addFlash(Display::return_message('Incorrect token', 'error'));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    // Check the system is correct
    if ($settingsInfo['system'] !== $system) {
        Display::addFlash(Display::return_message('Incorrect client', 'error'));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    // Check if the request expired with a diff between the query string parameter & the actual time
    if ($settingsInfo['expiration_time'] && $settingsInfo['expiration_time'] > 0) {
        $tokenTime = strtotime($time);
        $diff = abs($tokenTime - time()) / 60;
        if ($diff > $settingsInfo['expiration_time']) {
            Display::addFlash(Display::return_message('Token expired', 'error'));
            header('Location: '.api_get_path(WEB_PATH));
            exit;
        }
    }

    // Get the user info
    $userInfo = api_get_user_info_from_email($email);

    // Log-in user if exists or a show error message
    if (!empty($userInfo)) {
        Session::write('_user', $userInfo);
        Session::write('is_platformAdmin', false);
        Session::write('is_allowedCreateCourse', false);
        Event::eventLogin($userInfo['user_id']);
        Session::write('flash_messages', '');
    } else {
        Display::addFlash(Display::return_message(get_lang('UserNotFound'), 'error'));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    }

    header('Location: '.api_get_path(WEB_PATH).'user_portal.php');
    exit;
} else {
    Display::addFlash(Display::return_message('Invalid request', 'error'));
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}
