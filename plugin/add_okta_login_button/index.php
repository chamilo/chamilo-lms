<?php
// Show the OKTA login button
$_template['show_message'] = false;

if (api_is_anonymous()) {
    require_once api_get_path(SYS_CODE_PATH)."auth/external_login/okta.inc.php";
    $_template['show_message'] = true;
    // the default title
    $button_url = api_get_path(WEB_PLUGIN_PATH)."add_okta_login_button/img/logo-okta.png";
    $href_link = oktaLoginAuthorization();
    if (!empty($plugin_info['settings']['add_okta_login_button_okta_button_url'])) {
        $button_url = api_htmlentities($plugin_info['settings']['add_okta_login_button_okta_button_url']);
    }
    $_template['okta_button_url'] = $button_url;
    $_template['okta_href_link'] = $href_link;
}
