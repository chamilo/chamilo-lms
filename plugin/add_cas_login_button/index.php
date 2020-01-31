<?php
// Show the CAS button to login using CAS
require_once api_get_path(SYS_PATH).'main/auth/cas/cas_var.inc.php';

$_template['show_message'] = false;

if (api_is_anonymous()) {
    $_template['cas_activated'] = api_is_cas_activated();
    $_template['cas_configured'] = api_is_cas_activated() && phpCAS::isInitialized();
    $_template['show_message'] = true;
    // the default title
    $button_label = "Connexion via CAS";
    if (!empty($plugin_info['settings']['add_cas_login_button_cas_button_label'])) {
        $button_label = api_htmlentities(
            $plugin_info['settings']['add_cas_login_button_cas_button_label']
        );
    }
    // the comm
    $comm_label = api_htmlentities(
        $plugin_info['settings']['add_cas_login_button_cas_button_comment']
    );
    // URL of the image
    $url_label = $plugin_info['settings']['add_cas_login_button_cas_image_url'];

    $_template['button_label'] = $button_label;
    $_template['comm_label'] = $comm_label;
    $_template['url_label'] = $url_label;
    $_template['form'] = Template::displayCASLoginButton(get_lang('LoginEnter'));
}
