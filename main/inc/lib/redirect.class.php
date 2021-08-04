<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Send a redirect to the user agent and exist.
 *
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Redirect
{
    /**
     * Returns the result of api_get_path() (a web path to the root of Chamilo).
     *
     * @return string
     */
    public static function www()
    {
        return api_get_path(WEB_PATH);
    }

    /**
     * Checks whether the given URL contains "http". If not, prepend the web
     * root of Chamilo and send the browser there (HTTP redirect).
     *
     * @param string $url
     */
    public static function go($url = '')
    {
        if (empty($url)) {
            self::session_request_uri();
            $www = self::www();
            self::navigate($www);
        }

        $is_full_uri = (0 === strpos($url, 'http'));
        if ($is_full_uri) {
            self::navigate($url);
        }

        $url = self::www().$url;
        self::navigate($url);
    }

    /**
     * Redirect to the current session's "request uri" if it is defined, or
     * check sso_referer, user's role and page_after_login settings to send
     * the user to some predefined URL.
     *
     * @param bool Whether the user just logged in (in this case, use page_after_login rules)
     * @param int  The user_id, if defined. Otherwise just send to where the page_after_login setting says
     */
    public static function session_request_uri($logging_in = false, $user_id = null)
    {
        $no_redirection = isset($_SESSION['noredirection']) ? $_SESSION['noredirection'] : false;

        if ($no_redirection) {
            unset($_SESSION['noredirection']);

            return;
        }

        $url = isset($_SESSION['request_uri']) ? Security::remove_XSS($_SESSION['request_uri']) : '';
        unset($_SESSION['request_uri']);

        $afterLogin = Session::read('redirect_after_not_allow_page');

        if (!empty($afterLogin) && isset($_GET['redirect_after_not_allow_page'])) {
            Session::erase('redirect_after_not_allow_page');
            self::navigate($afterLogin);
        }
        if (!empty($url)) {
            self::navigate($url);
        } elseif ($logging_in ||
            (isset($_REQUEST['sso_referer']) && !empty($_REQUEST['sso_referer']))
        ) {
            if (isset($user_id)) {
                $allow = api_get_configuration_value('plugin_redirection_enabled');
                if ($allow) {
                    $allow = api_get_configuration_value('plugin_redirection_enabled');
                    if ($allow) {
                        RedirectionPlugin::redirectUser($user_id);
                    }
                }

                // Make sure we use the appropriate role redirection in case one has been defined
                $user_status = api_get_user_status($user_id);
                switch ($user_status) {
                    case COURSEMANAGER:
                        $redir = api_get_setting('teacher_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH).$redir);
                        }
                        break;
                    case STUDENT:
                        $redir = api_get_setting('student_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH).$redir);
                        }
                        break;
                    case DRH:
                        $redir = api_get_setting('drh_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH).$redir);
                        }
                        break;
                    case SESSIONADMIN:
                        $redir = api_get_setting('sessionadmin_page_after_login');
                        if (!empty($redir)) {
                            self::navigate(api_get_path(WEB_PATH).$redir);
                        }
                        break;
                    default:
                        break;
                }
            }
            $redirect = api_get_setting('redirect_admin_to_courses_list');
            if ('true' !== $redirect) {
                // If the user is a platform admin, redirect to the main admin page
                if (api_is_multiple_url_enabled()) {
                    // if multiple URLs are enabled, make sure he's admin of the
                    // current URL before redirecting
                    $url = api_get_current_access_url_id();
                    if (api_is_platform_admin_by_id($user_id, $url)) {
                        self::navigate(api_get_path(WEB_CODE_PATH).'admin/index.php');
                    }
                } else {
                    // if no multiple URL, then it's enough to be platform admin
                    if (api_is_platform_admin_by_id($user_id)) {
                        self::navigate(api_get_path(WEB_CODE_PATH).'admin/index.php');
                    }
                }
            }
            $page_after_login = api_get_setting('page_after_login');
            if (!empty($page_after_login)) {
                self::navigate(api_get_path(WEB_PATH).$page_after_login);
            }
        }
    }

    /**
     * Sends the user to the web root of Chamilo (e.g. http://my.chamiloportal.com/ ).
     */
    public static function home()
    {
        $www = self::www();
        self::navigate($www);
    }

    /**
     * Sends the user to the user_portal.php page.
     */
    public static function user_home()
    {
        $www = self::www();
        self::navigate("$www/user_portal.php");
    }

    /**
     * Redirects the user to a given URL through the header('location: ...') function.
     *
     * @param string $url
     */
    protected static function navigate($url)
    {
        session_write_close(); //should not be needed
        header("Location: $url");
        exit;
    }
}
