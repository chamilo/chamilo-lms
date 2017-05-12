<?php

namespace Shibboleth;

use \Redirect;

/**
 * Shibboleth main class. Provides access to various Shibboleth sub components and
 * provides the high level functionalities.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class Shibboleth
{

    const NAME = 'shibboleth';
    const UNKNOWN_STATUS = -1;
    const TEACHER_STATUS = 1;
    const STUDENT_STATUS = 5;

    static $config = null;

    public static function format_status($status)
    {
        if ($status == Shibboleth::TEACHER_STATUS) {
            return 'Teacher';
        } else if ($status == Shibboleth::STUDENT_STATUS) {
            return 'Student';
        } else if ($status == Shibboleth::UNKNOWN_STATUS) {
            return 'Unknown';
        } else {
            return '???';
        }
    }

    /**
     *
     * @return ShibbolethConfig
     */
    public static function config()
    {
        self::$config = self::$config ? self::$config : new ShibbolethConfig();
        return self::$config;
    }

    public static function set_config($config)
    {
        self::$config = $config;
    }

    /**
     *
     * @return ShibbolethSession
     */
    public static function session()
    {
        return ShibbolethSession::instance();
    }

    /**
     *
     * @return ShibbolethStore
     */
    public static function store()
    {
        return ShibbolethStore::instance();
    }

    /**
     *
     * @return ShibbolethDisplay
     */
    public static function display()
    {
        return ShibbolethDisplay::instance();
    }

    public static function sys_path()
    {
        $path = __DIR__.'/../';
        return $path;
    }

    public static function url($path = '')
    {
        $result = api_get_path('WEB_PATH');
        $result .= '/main/auth/shibboleth/' . $path;
        return $result;
    }

    public static function redirect($url = '')
    {
        if (empty($url)) {
            $url = isset($_SESSION['shibb_direct_url']) ? $_SESSION['shibb_direct_url'] : '';
            unset($_SESSION['shibb_direct_url']);

            /*
             * Tests if the user tried to login directly in a protected course before to come here
             * (this variable could be set in the modified code of /chamilo/inc/lib/main_api.lib.php)
             *
             * Note:
             *       this part was added to give the possibility to access Chamilo directly on a course URL from a link diplayed in a portal.
             *       This is not a direct Shibboleth related functionnality, but this could be used in a shibbolethized
             *       Dokeos installation, mainly if you have a SSO system in your network.
             *       Please note that the file /claroline/inc/lib/main_api.lib.php must be adapted to your Shibboleth settings
             *       If any interest or question, please contact Nicolas.Rod_at_adm.unige.ch
             *
             */
        }
        if ($url) {
            //needed to log the user in his courses. Normally it is done by visiting /chamilo/index.php
//            $include_path = api_get_path(INCLUDE_PATH);
//            require("$include_path/local.inc.php");
//
//            if (strpos($url, '?') === false) {
//                $url = "$url?";
//            }
//
//            $rootWeb = api_get_path('WEB_PATH');
//            $first_slash_pos = strpos($rootWeb, '/', 8);
//            $rootWeb_wo_uri = substr($rootWeb, 0, $first_slash_pos);
//            $url = $rootWeb_wo_uri . $course_url . '_stop';
            Redirect::go($url);
        }
        Redirect::home();
    }

    /**
     *
     * @param ShibbolethUser $shibb_user
     */
    public static function save($shibb_user)
    {
        $shibb_user->status = self::infer_user_status($shibb_user);
        $shibb_user->status_request = self::infer_status_request($shibb_user);
        $shibb_user->shibb_unique_id = $shibb_user->unique_id;
        $shibb_user->shibb_persistent_id = $shibb_user->persistent_id;

        $user = User::store()->get_by_shibboleth_id($shibb_user->unique_id);
        if (empty($user)) {
            $shibb_user->auth_source == self::NAME;
            return User::create($shibb_user)->save();
        }

        $shibb_user->status_request = false;
        $fields = self::config()->update_fields;
        foreach ($fields as $key => $updatable) {
            if ($updatable) {
                $user->{$key} = $shibb_user->{$key};
            }
        }
        $user->auth_source == self::NAME;
        $user->shibb_unique_id = $shibb_user->shibb_unique_id;
        $user->shibb_persistent_id = $shibb_user->shibb_persistent_id;
        $user->save();
        return $result;
    }

    /**
     * Infer the rights/status the user can have in Chamilo based on his affiliation attribute
     *
     * @param  ShibbolethUser $user
     * @return The Chamilo user status, one of TEACHER, STUDENT or UNKNOWN
     */
    public static function infer_user_status($user)
    {
        $affiliations = $user->affiliation;
        $affiliations = is_array($affiliations) ? $affiliations : array($affiliations);

        $map = self::config()->affiliation_status;

        $rights = array();
        foreach ($affiliations as $affiliation) {
            $affiliation = strtolower($affiliation);
            if (isset($map[$affiliation])) {
                $right = $map[$affiliation];
                $rights[$right] = $right;
            }
        }

        $teacher_status = isset($rights[self::TEACHER_STATUS]);
        $student_status = isset($rights[self::STUDENT_STATUS]);

        //if the user has got teacher rights, we doesn't check anything else
        if ($teacher_status) {
            return self::TEACHER_STATUS;
        }

        if ($student_status) {
            return self::STUDENT_STATUS;
        }

        $result = self::config()->default_status;
        $result = (int) $result;
        $result = ($result == Shibboleth::TEACHER_STATUS || $result == Shibboleth::STUDENT_STATUS) ? $result : Shibboleth::UNKNOWN_STATUS;
        return $result;
    }

    /**
     * Return true if the user can ask for a greater status than student.
     * This happens for staff members.
     *
     * @param ShibbolethUser $user
     * @return boolean
     */
    public static function infer_status_request($user)
    {
        if ($user->status == self::TEACHER_STATUS) {
            return false;
        }
        if ($user->status == self::UNKNOWN_STATUS) {
            return true;
        }

        $config = Shibboleth::config();
        $affiliations = $user->affiliation;
        $affiliations = is_array($affiliations) ? $affiliations : array($affiliations);
        foreach ($affiliations as $affiliation) {
            $result = isset($config->affiliation_status_request[$affiliation]) ? $config->affiliation_status_request[$affiliation] : false;
            if ($result) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sends an email to the Chamilo and Shibboleth administrators in the name
     * of the logged-in user.
     *
     * @param string $subject
     */
    public static function email_admin($subject, $message)
    {
        $user = Shibboleth::session()->user();
        $firstname = $user['firstname'];
        $lastname = $user['lastname'];
        $email = $user['email'];
        $status = $user['status'];
        $status = self::format_status($status);

        $signagure = <<<EOT

_________________________
$firstname $lastname
$email
$status
EOT;

        $message .= $signagure;

        $header = "From: $email \n";

        $shibb_admin_email = Shibboleth::config()->admnistrator_email;
        if ($shibb_admin_email) {
            $header .= "Cc: $shibb_admin_email";
        }

        $administrator_email = api_get_setting('emailAdministrator');
        $result = mail($administrator_email, $subject, $message);
        return (bool) $result;
    }

}
