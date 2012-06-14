<?php
// Show the FACEBOOK login button

$_template['show_message']   = false;

//if (api_is_anonymous() && api_get_setting('facebook_login_activate') == 'true') {
if (api_is_anonymous()) {
    require_once api_get_path(SYS_CODE_PATH)."auth/external_login/facebook.init.php";
    $_template['show_message']   = true;
    // the default title
    $button_url = api_get_path(WEB_PLUGIN_PATH)."add_facebook_login_button/img/cnx_fb.png";
    $href_link = facebook_get_login_url();
    if (!empty($plugin_info['settings']['add_facebook_login_button_facebook_button_url'])) {
        $button_url = api_htmlentities($plugin_info['settings']['add_facebook_login_button_facebook_button_url']);
    }    
    $_template['facebook_button_url'] = $button_url;
    $_template['facebook_href_link'] = $href_link;    
}
