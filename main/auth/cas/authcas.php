<?php
/* Written by Noel Dieschburg <noel@cblue.be> for the paris5 university

* Checks if the user is already logged in via the cas system
* Gets all the info via the ldap module (ldap has to work)

*/
require_once api_get_path(SYS_PATH).'main/auth/cas/cas_var.inc.php';

/**
 * @return bool whether cas is configured
 */
function cas_configured()
{
    foreach(['cas_server', 'cas_protocol', 'cas_port'] as $v) {
        if (is_null(api_get_setting($v))) {
            return false;
        }
    }
    return phpCAS::isInitialized();
}

/*
 * Return the direct URL to a course code with CAS login
 */
function get_cas_direct_URL($in_course_code)
{
    return api_get_path(WEB_PATH).'main/auth/cas/logincas.php?firstpage='.$in_course_code;
}

function getCASLogoHTML()
{
    $out_res = "";
    if (api_get_setting("casLogoURL") != "") {
        $out_res = "<img src='".api_get_setting("casLogoURL")."' alt='CAS Logo' />";
    }

    return $out_res;
}
