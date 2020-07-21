<?php
// Show the OKTA login button
$_template['show_message'] = false;

if (api_is_anonymous()) {
    require_once api_get_path(SYS_CODE_PATH)."auth/external_login/okta.inc.php";
    $_template['show_message'] = true;
    // the default title
    $buttonImg = api_get_path(WEB_PLUGIN_PATH)."add_okta_login_button/img/logo-okta.png";
    $hrefLink = '?saml_sso='.$GLOBALS['okta_config']['integration_name'];
    //$hrefLink = oktaLoginAuthorization();
    if (!empty($plugin_info['settings']['add_okta_login_button_img'])) {
        $buttonImg = api_htmlentities($plugin_info['settings']['add_okta_login_button_img']);
    }
    $_template['button_img'] = $buttonImg;
    $_template['href_link'] = $hrefLink;
}
