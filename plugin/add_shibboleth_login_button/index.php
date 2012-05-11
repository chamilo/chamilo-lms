<?php
// Show the Shibboleth button to login using SHIBBOLETH

$_template['show_message']   = false;

if (api_is_anonymous()) {
    $_template['show_message']   = true;
    // the default title
    $button_label = "Connexion via Shibboleth";
    if (!empty($plugin_info['settings']['add_shibboleth_login_button_shibboleth_button_label'])) {
        $button_label = api_htmlentities($plugin_info['settings']['add_shibboleth_login_button_shibboleth_button_label']);
    }
    // the comm
    $comm_label = api_htmlentities($plugin_info['settings']['add_shibboleth_login_button_shibboleth_button_comment']);;
    // URL of the image
    $url_label = $plugin_info['settings']['add_shibboleth_login_button_shibboleth_image_url'];
    
    $_template['button_label'] = $button_label;
    $_template['comm_label'] = $comm_label;
    $_template['url_label'] = $url_label;
}