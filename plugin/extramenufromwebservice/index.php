<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is the main script of the extra menu from webservice plugin.
 *
 * @author Borja Sanchez
 *
 * @package chamilo.plugin.extramenufromwebservice
 */

// This plugin doesn't work for anonymous users
if (!api_is_anonymous()) {
    $extraMenuFromWebservice = ExtraMenuFromWebservicePlugin::create();
    $pluginEnabled = $extraMenuFromWebservice->get('tool_enable');
    // If the configuration option 'tool_enable' is disabled, doesn't show the menu
    if ($pluginEnabled === 'true') {
        $menuContent = "";
        $userId = api_get_user_id();
        $userData = $originalUserInfo = api_get_user_info(
            api_get_user_id(),
            false,
            false,
            false,
            false,
            false,
            true
        );
        $pluginPath = api_get_path(WEB_PLUGIN_PATH).'extramenufromwebservice/resources/';
        //Check if the token is in session, if not get a new token and write in session
        if (
            Session::has('extramenufromwebservice_plugin_token') &&
            Session::has('extramenufromwebservice_plugin_token_start')
        ) {
            //if no session lifetime exists, set 1 day
            $pluginSessionTimeout = !empty((int) $extraMenuFromWebservice->get('session_timeout')) ?
                $extraMenuFromWebservice->get('session_timeout') :
                86400;

            $tokenStartTime = new DateTime(Session::read('extramenufromwebservice_plugin_token_start'));

            // If token is expired, get other new token
            if ($extraMenuFromWebservice::tokenIsExpired($tokenStartTime->getTimestamp(), $pluginSessionTimeout)) {
                $loginToken = $extraMenuFromWebservice->getToken();
                Session::write('extramenufromwebservice_plugin_token', $loginToken);
                $now = api_get_utc_datetime();
                Session::write('extramenufromwebservice_plugin_token_start', $now);
            }
        } else {
            $loginToken = $extraMenuFromWebservice->getToken();
            if (!empty($loginToken)) {
                Session::write('extramenufromwebservice_plugin_token', $loginToken);
                $now = api_get_utc_datetime();
                Session::write('extramenufromwebservice_plugin_token_start', $now);
            }
        }

        $isMobile = api_is_browser_mobile();
        $menuResponse = $extraMenuFromWebservice->getMenu(
            Session::read('extramenufromwebservice_plugin_token'),
            $userData['email'],
            $isMobile
        );
        if (!empty($menuResponse)) {
            $menuContent = $menuResponse;
            $fh = '<script type="text/javascript" src="'.$pluginPath.'js/extramenufromwebservice.js" ></script>';
            $fh .= '<link href="'.$pluginPath.'css/extramenufromwebservice.css" rel="stylesheet" type="text/css">';
            if (!empty($extraMenuFromWebservice->get('list_css_imports'))) {
                $cssListToImport = $extraMenuFromWebservice->getImports(
                    $extraMenuFromWebservice->get('list_css_imports')
                );
            }
            if (!empty($extraMenuFromWebservice->get('list_fonts_imports'))) {
                $fontListToImport = $extraMenuFromWebservice->getImports(
                    $extraMenuFromWebservice->get('list_fonts_imports')
                );
            }
            $fh .= '<div class="extra-menu-from-webservice">';
            $fh .= '<input id="menu-toggle" type="checkbox" />';
            $fh .= '<label class="menu-btn" for="menu-toggle">';
            $fh .= '<span></span>';
            $fh .= '</label>';
            $fh .= '<div class="nav-from-webservice" id="nav-from-webservice">';

            if (isset($cssListToImport)) {
                foreach ($cssListToImport as $cssUrl) {
                    $fh .= '<link href="'.$cssUrl.'" rel="stylesheet" type="text/css">';
                }
            }

            $fh .= '<style>';
            if (isset($fontListToImport)) {
                foreach ($fontListToImport as $fontUrl) {
                    $fh .= '@import url("'.$fontUrl.'");';
                }
            }
            $fh .= $menuContent['css'];
            $fh .= '</style>';

            $fh .= $menuContent['html'];

            $fh .= '<script>';
            $fh .= $menuContent['js'];
            $fh .= '</script>';

            $fh .= '</div>';
            $fh .= '</div>';

            echo $fh;
        }
    }
}
