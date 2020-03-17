<?php
// Show the CAS button to logout to your CAS session
global $_user;
$_template['show_message'] = false;

if (!api_is_anonymous() &&
    api_get_setting('cas_activate') == 'true' &&
    $_user['auth_source'] == CAS_AUTH_SOURCE
) {
    $_template['show_message'] = true;
    // the default title
    $logout_label = "Deconnexion de CAS";
    if (!empty($plugin_info['settings']['add_cas_logout_button_cas_logout_label'])) {
        $logout_label = api_htmlentities($plugin_info['settings']['add_cas_logout_button_cas_logout_label']);
    }
    // the comm
    $logout_comment = api_htmlentities($plugin_info['settings']['add_cas_logout_button_cas_logout_comment']);

    // URL of the image
    $logout_image_url = $plugin_info['settings']['add_cas_logout_button_cas_logout_image_url'];

    $_template['logout_label'] = $logout_label;
    $_template['form'] = Template::displayCASLogoutButton(get_lang('Logout'));
    $_template['logout_comment'] = $logout_comment;
    $_template['logout_image_url'] = $logout_image_url;
}
