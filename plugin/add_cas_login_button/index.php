<?php
// Show the CAS button to login using CAS

$_template['show_message']   = false;

if (api_is_anonymous() && api_get_setting('cas_activate') == 'true') {
    $_template['show_message']   = true;
    // the default title
    $button_label = "Connexion via CAS";
    if (!empty($plugin_info['settings']['add_cas_login_button_cas_button_label'])) {
        $button_label = api_htmlentities($plugin_info['settings']['add_cas_login_button_cas_button_label']);
    }
    // the comm
    $comm_label = api_htmlentities($plugin_info['settings']['add_cas_login_button_cas_button_comment']);;
    // URL of the image
    $url_label = $plugin_info['settings']['add_cas_login_button_cas_image_url'];
    
    $_template['button_label'] = $button_label;
    $_template['comm_label'] = $comm_label;
    $_template['url_label'] = $url_label;
}