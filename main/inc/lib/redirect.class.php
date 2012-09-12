<?php

/**
 * Send a redirect to the user agent and exist
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Redirect {

    static function www() {
        return Uri::www();
    }

    static function go($url = '') {
        if (empty($url)) {
            Redirect::session_request_uri();
            $www = self::www();
            self::navigate($www);
        }

        $is_full_uri = (strpos($url, 'http') === 0);
        if ($is_full_uri) {
            self::navigate($url);
        }

        $url = self::www() . $url;
        self::navigate($url);
    }

    /**
     * Redirect to the session "request uri" if it exists. 
     * @param bool Whether the user just logged in (in this case, use page_after_login rules)
     */
    static function session_request_uri($logging_in = false, $user_id = null) {
        $no_redirection = isset($_SESSION['noredirection']) ? $_SESSION['noredirection'] : false;

        if ($no_redirection) {
            unset($_SESSION['noredirection']);
            return;
        }

        $url = isset($_SESSION['request_uri']) ? $_SESSION['request_uri'] : '';
        unset($_SESSION['request_uri']);

        if (!empty($url)) {
            self::navigate($url);
        } elseif ($logging_in || (isset($_REQUEST['sso_referer']) && !empty($_REQUEST['sso_referer']))) {
            if (isset($user_id)) {
                // Make sure we use the appropriate role redirection in case one has been defined                
                $user_status = api_get_user_status($user_id);
                switch ($user_status) {
                    case COURSEMANAGER:
                        $redir = api_get_setting('teacher_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH) . $redir);
                        }
                        break;
                    case STUDENT:
                        $redir = api_get_setting('student_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH) . $redir);
                        }
                        break;
                    case DRH:
                        $redir = api_get_setting('drh_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH) . $redir);
                        }
                        break;
                    case SESSIONADMIN:
                        $redir = api_get_setting('sessionadmin_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH) . $redir);
                        }
                        break;
                    default:
                        break;
                }
            }
            $page_after_login = api_get_setting('page_after_login');
            if (!empty($page_after_login)) {
                self::navigate(api_get_path(WEB_PATH) . $page_after_login);
            }
        }
    }

    static function home() {
        $www = self::www();
        self::navigate($www);
    }

    static function user_home() {
        $www = self::www();
        self::navigate("$www/user_portal.php");
    }

    protected static function navigate($url) {
        session_write_close(); //should not be neeeded 
        header("Location: $url");
        exit;
    }
}
